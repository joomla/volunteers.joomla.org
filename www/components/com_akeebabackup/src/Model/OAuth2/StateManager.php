<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\OAuth2;

defined('_JEXEC') || die;

/**
 * Generates and validates the anti-CSRF `state` value for the OAuth2 helper's step1/step2 round trip.
 *
 * `$session` is deliberately left untyped. It is normally `Factory::getApplication()->getSession()`
 * (`Joomla\Session\Session`), but this class only ever calls `get()`, `set()` and `remove()` on it, so any object
 * exposing that shape works — which keeps this class framework-free and unit-testable without a Joomla stub.
 *
 * @since   9.9.1
 */
final class StateManager
{
	private $session;

	public function __construct($session)
	{
		$this->session = $session;
	}

	/**
	 * Creates a fresh, random state value for the given engine and stashes it in the session.
	 *
	 * @param   string  $engine  The OAuth2 engine name, e.g. `dropbox`.
	 *
	 * @return  string  The state value to append to the provider's authentication URL.
	 * @since   9.9.1
	 */
	public function generate(string $engine): string
	{
		$state = bin2hex(random_bytes(32));

		$this->session->set($this->getSessionKey($engine), $state);

		return $state;
	}

	/**
	 * Validates a returned state value against the one stashed for the given engine, then consumes it — the
	 * stashed value is removed whether or not it matches, so it can never be replayed.
	 *
	 * @param   string       $engine   The OAuth2 engine name, e.g. `dropbox`.
	 * @param   string|null  $state    The `state` value returned by the provider.
	 *
	 * @return  bool  True if, and only if, a state had been stashed for this engine and it matches.
	 * @since   9.9.1
	 */
	public function validate(string $engine, ?string $state): bool
	{
		$key      = $this->getSessionKey($engine);
		$expected = $this->session->get($key);

		$this->session->remove($key);

		if (!is_string($expected) || $expected === '' || !is_string($state) || $state === '')
		{
			return false;
		}

		return hash_equals($expected, $state);
	}

	private function getSessionKey(string $engine): string
	{
		return 'com_akeebabackup.oauth2.state.' . $engine;
	}
}
