#!/usr/bin/env bash
# =============================================================================
# Akeeba Engine — database driver integration test runner
#
# Spins up an ephemeral Docker container for each configured server version,
# runs the database driver test suite against it, then tears it down.
#
# Usage:
#   ./run-integration-tests.sh                 # test every configured image
#   ./run-integration-tests.sh postgres:17     # test one specific image
#   ./run-integration-tests.sh mariadb         # test every image of one family
#
# Which versions get spun up is configured in Test/.env (see Test/.env.sample),
# or overridden per run from the shell:
#
#   INTEGRATION_POSTGRES_IMAGES="postgres:16 postgres:17" ./run-integration-tests.sh
#
# Requirements: Docker, PHP CLI with the mysqli, pdo_mysql and pdo_pgsql
# extensions. A driver whose extension is missing skips itself.
# =============================================================================

set -euo pipefail

cd "$(dirname "$0")"

# ---------------------------------------------------------------------------
# Load Test/.env, exactly like Test/bootstrap.php does: variables already
# exported into the shell win over the ones defined in the file.
# ---------------------------------------------------------------------------

load_env_file() {
	local file="$1" line key value

	[ -f "$file" ] || return 0

	while IFS= read -r line || [ -n "$line" ]; do
		# Trim leading whitespace, then skip blanks and comments.
		line="${line#"${line%%[![:space:]]*}"}"
		case "$line" in '' | \#*) continue ;; esac

		line="${line#export }"
		key="${line%%=*}"
		value="${line#*=}"

		# Ignore anything that is not a plain KEY=VALUE assignment.
		case "$key" in '' | *[!A-Za-z0-9_]*) continue ;; esac

		# Surrounding quotes are optional and stripped.
		value="${value%\"}" ; value="${value#\"}"
		value="${value%\'}" ; value="${value#\'}"

		if [ -z "${!key+set}" ]; then
			export "$key=$value"
		fi
	done < "$file"
}

load_env_file "Test/.env"

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------

# Server versions to test, per family. Override in Test/.env or the shell.
INTEGRATION_MYSQL_IMAGES="${INTEGRATION_MYSQL_IMAGES:-mysql:8.0 mysql:8.4}"
INTEGRATION_MARIADB_IMAGES="${INTEGRATION_MARIADB_IMAGES:-mariadb:11.4 mariadb:12.3}"
INTEGRATION_POSTGRES_IMAGES="${INTEGRATION_POSTGRES_IMAGES:-postgres:16 postgres:17}"

# Host ports mapped to the container. Change these if they are already taken.
INTEGRATION_DB_HOST_PORT="${INTEGRATION_DB_HOST_PORT:-13306}"
INTEGRATION_PGSQL_HOST_PORT="${INTEGRATION_PGSQL_HOST_PORT:-15432}"

# Credentials seeded into the container.
DB_PASSWORD="${DB_PASSWORD:-akeebatest}"
DB_NAME="${DB_NAME:-akeebatest}"

# How long to wait for a server to accept connections, in seconds.
MAX_WAIT_SECONDS="${MAX_WAIT_SECONDS:-120}"

CONTAINER_NAME="akeebaengine_integration_$$"
FAILED=0

# ---------------------------------------------------------------------------
# Resolve which images to test
# ---------------------------------------------------------------------------

ALL_IMAGES=($INTEGRATION_MYSQL_IMAGES $INTEGRATION_MARIADB_IMAGES $INTEGRATION_POSTGRES_IMAGES)

case "${1:-}" in
	'')
		IMAGES=("${ALL_IMAGES[@]}")
		;;
	mysql)
		IMAGES=($INTEGRATION_MYSQL_IMAGES)
		;;
	mariadb)
		IMAGES=($INTEGRATION_MARIADB_IMAGES)
		;;
	postgres | postgresql | pgsql)
		IMAGES=($INTEGRATION_POSTGRES_IMAGES)
		;;
	*)
		# A specific image, e.g. postgres:17
		IMAGES=("$1")
		;;
esac

if [ "${#IMAGES[@]}" -eq 0 ]; then
	echo "No images configured to test."
	exit 1
fi

# The family decides the port, the seeding variables and the readiness probe.
family_of() {
	case "${1%%:*}" in
		mysql) echo "mysql" ;;
		mariadb) echo "mariadb" ;;
		postgres | postgresql) echo "postgres" ;;
		*)
			echo "unknown"
			;;
	esac
}

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

cleanup_container() {
	docker rm --force "$CONTAINER_NAME" >/dev/null 2>&1 || true
}

# Kill the container even if the run is interrupted.
trap cleanup_container EXIT INT TERM

# Poll until the server accepts connections, or give up.
wait_for_db() {
	local family="$1" waited=0 probe

	case "$family" in
		postgres)
			probe=(pg_isready --username=postgres --dbname="$DB_NAME")
			;;
		mariadb)
			# MariaDB 11+ ships mariadb-admin; its mysqladmin symlink is deprecated.
			probe=(mariadb-admin --user=root "--password=${DB_PASSWORD}" ping)
			;;
		*)
			probe=(mysqladmin --user=root "--password=${DB_PASSWORD}" ping)
			;;
	esac

	echo "  Waiting for the server to accept connections..."

	while ! docker exec "$CONTAINER_NAME" "${probe[@]}" >/dev/null 2>&1; do
		waited=$((waited + 2))

		if [ "$waited" -ge "$MAX_WAIT_SECONDS" ]; then
			echo "  Timed out after ${MAX_WAIT_SECONDS} seconds."
			return 1
		fi

		sleep 2
	done

	echo "  The server is ready."
	return 0
}

start_container() {
	local image="$1" family="$2"

	if [ "$family" = "postgres" ]; then
		docker run --detach \
			--name "$CONTAINER_NAME" \
			--env "POSTGRES_PASSWORD=${DB_PASSWORD}" \
			--env "POSTGRES_DB=${DB_NAME}" \
			--publish "127.0.0.1:${INTEGRATION_PGSQL_HOST_PORT}:5432" \
			"$image" >/dev/null

		return
	fi

	# Both MYSQL_* and MARIADB_* variables are supplied so the container is
	# seeded regardless of which set the image recognises.
	docker run --detach \
		--name "$CONTAINER_NAME" \
		--env "MYSQL_ROOT_PASSWORD=${DB_PASSWORD}" \
		--env "MARIADB_ROOT_PASSWORD=${DB_PASSWORD}" \
		--env "MYSQL_DATABASE=${DB_NAME}" \
		--env "MARIADB_DATABASE=${DB_NAME}" \
		--publish "127.0.0.1:${INTEGRATION_DB_HOST_PORT}:3306" \
		"$image" >/dev/null
}

# Run the driver suite against the container we just started.
#
# Only the test classes of this family are run. Filtering by class rather than by leaving the other family's connection
# variables unset is deliberate: Test/bootstrap.php reads Test/.env itself, so a developer who has INTEGRATION_DB_HOST
# configured there would otherwise have the MySQL tests silently run against their own server while we are iterating
# over PostgreSQL images. The connection variables we export here take precedence over Test/.env, so the tests of this
# family always talk to the container.
run_suite() {
	local family="$1"

	if [ "$family" = "postgres" ]; then
		INTEGRATION_PGSQL_HOST=127.0.0.1 \
		INTEGRATION_PGSQL_PORT="$INTEGRATION_PGSQL_HOST_PORT" \
		INTEGRATION_PGSQL_USER=postgres \
		INTEGRATION_PGSQL_PASSWORD="$DB_PASSWORD" \
		INTEGRATION_PGSQL_NAME="$DB_NAME" \
			vendor/bin/phpunit --configuration phpunit.integration.xml --testsuite drivers \
				--filter 'PostgresqlTest'

		return
	fi

	INTEGRATION_DB_HOST=127.0.0.1 \
	INTEGRATION_DB_PORT="$INTEGRATION_DB_HOST_PORT" \
	INTEGRATION_DB_USER=root \
	INTEGRATION_DB_PASSWORD="$DB_PASSWORD" \
	INTEGRATION_DB_NAME="$DB_NAME" \
		vendor/bin/phpunit --configuration phpunit.integration.xml --testsuite drivers \
			--filter '(MysqliTest|PdomysqlTest)'
}

run_tests_against() {
	local image="$1"
	local family

	family="$(family_of "$image")"

	echo ""
	echo "=========================================="
	echo "  Image : $image"
	echo "=========================================="

	if [ "$family" = "unknown" ]; then
		echo "  FAILED: cannot tell which database family '$image' belongs to."
		echo "          Expected an image named mysql:*, mariadb:* or postgres:*."

		return 1
	fi

	# Remove any leftover container of the same name.
	cleanup_container

	start_container "$image" "$family"

	if ! wait_for_db "$family"; then
		echo "  FAILED: the server never became ready ($image)"
		cleanup_container

		return 1
	fi

	if ! run_suite "$family"; then
		echo ""
		echo "  FAILED: $image (test failures above)"
		cleanup_container

		return 1
	fi

	echo ""
	echo "  PASSED: $image"

	cleanup_container

	return 0
}

# ---------------------------------------------------------------------------
# Main loop
# ---------------------------------------------------------------------------

for IMAGE in "${IMAGES[@]}"; do
	if ! run_tests_against "$IMAGE"; then
		FAILED=$((FAILED + 1))
	fi
done

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------

TOTAL="${#IMAGES[@]}"
PASSED=$((TOTAL - FAILED))

echo ""
echo "=========================================="
echo "  Results: $PASSED/$TOTAL image(s) passed"
echo "=========================================="

if [ "$FAILED" -ne 0 ]; then
	exit 1
fi
