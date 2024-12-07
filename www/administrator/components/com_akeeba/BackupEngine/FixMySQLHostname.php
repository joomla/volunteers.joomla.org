<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine;

defined('AKEEBAENGINE') || die();

trait FixMySQLHostname
{
	/**
	 * Tries to parse all the weird hostname definitions and normalize them into something that the MySQLi connector
	 * will understand. Please note that there are some differences to the old MySQL driver:
	 *
	 * * Port and socket MUST be provided separately from the hostname. Hostnames in the form of 127.0.0.1:8336 are no
	 *   longer acceptable.
	 *
	 * * The hostname "localhost" has special meaning. It means "use named pipes / sockets". Anything else uses TCP/IP.
	 *   This is the ONLY way to specify a. TCP/IP or b. named pipes / sockets connection.
	 *
	 * * You SHOULD NOT use a numeric TCP/IP port with hostname localhost. For some strange reason it's still allowed
	 *   but the manual is self-contradicting over what this really does...
	 *
	 * * Likewise you CANNOT use a socket / named pipe path with hostname other than localhost. Named pipes and sockets
	 *   can only be used with the local machine, therefore the hostname MUST be localhost.
	 *
	 * * You cannot give a TCP/IP port number in the socket parameter or a named pipe / socket path to the port
	 *   parameter. This leads to an error.
	 *
	 * * You cannot use an empty string, 0 or any other non-null value when you want to omit either of the port or
	 *   socket parameters.
	 *
	 * * Persistent connections must be prefixed with the string literal 'p:'. Therefore you cannot have a hostname
	 *   called 'p' (not to mention that'd be daft). You can also not specify something like 'p:1234' to make a
	 *   persistent connection to a port. This wasn't even supported by the old MySQL driver. As a result we don't even
	 *   try to catch that degenerate case.
	 *
	 * This method will try to apply all of the aforementioned rules with one additional disambiguation rule:
	 *
	 * A port / socket set in the hostname overrides a port specified separately. A port specified separately overrides
	 * a socket specified separately.
	 *
	 * @param   string  $host    The hostname. Can contain legacy hostname:port or hostname:sc=ocket definitions.
	 * @param   int     $port    The port. Alternatively it can contain the path to the socket.
	 * @param   string  $socket  The path to the socket. You could abuse it to enter the port number. DON'T!
	 *
	 * @return  void  All parameters are passed by reference.
	 *
	 * @since   9.2.3
	 */
	protected function fixHostnamePortSocket(&$host, &$port, &$socket)
	{
		// Is this a persistent connection? Persistent connections are indicated by the literal "p:" in front of the hostname
		$isPersistent = (substr($host, 0, 2) == 'p:');
		$host         = $isPersistent ? substr($host, 2) : $host;

		// If the hostname looks like a *NIX filename we need to treat it as a socket.
		if (preg_match('#^/([^/]*/)?[^/]#', $host))
		{
			$socket = $host;
			$host = null;
		}

		// Special case: Windows named pipe (\\.\something\or\another), with or without parentheses.
		$isNamedPipe = false;

		if (preg_match("#^\(?\\\\\\\\\.\\\\#", $host))
		{
			$isNamedPipe = true;
			$socket = $host;
			$host = '.';
		}

		/*
		 * Unlike mysql_connect(), mysqli_connect() takes the port and socket as separate arguments. Therefore, we
		 * have to extract them from the host string.
		 */
		$port = !empty($port) ? $port : 3306;

		if ($host === 'localhost')
		{
			$port = null;
		}
		// UNIX socket URI, e.g. 'unix:/path/to/unix/socket.sock'
		elseif (preg_match('/^unix:(?P<socket>[^:]+)$/', $host, $matches))
		{
			$host   = null;
			$socket = $matches['socket'];
			$port   = null;
		}
		// It's an IPv4 address with or without port
		elseif (preg_match('/^(?P<host>((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))(:(?P<port>.+))?$/', $host, $matches))
		{
			$host = $matches['host'];

			if (!empty($matches['port']))
			{
				$port = $matches['port'];
			}
		}
		// Square-bracketed IPv6 address with or without port, e.g. [fe80:102::2%eth1]:3306
		elseif (preg_match('/^(?P<host>\[.*\])(:(?P<port>.+))?$/', $host, $matches))
		{
			$host = $matches['host'];

			if (!empty($matches['port']))
			{
				$port = $matches['port'];
			}
		}
		// Named host (e.g example.com or localhost) with or without port
		elseif (preg_match('/^(?P<host>(\w+:\/{2,3})?[a-z0-9\.\-]+)(:(?P<port>[^:]+))?$/i', $host, $matches))
		{
			$host = $matches['host'];

			if (!empty($matches['port']))
			{
				$port = $matches['port'];
			}
		}
		// Empty host, just port, e.g. ':3306'
		elseif (preg_match('/^:(?P<port>[^:]+)$/', $host, $matches))
		{
			$host = '127.0.0.1';
			$port = $matches['port'];
		}
		// ... else we assume normal (naked) IPv6 address, so host and port stay as they are or default

		// If there is both a valid port and a valid socket we will choose the socket instead
		if (is_numeric($port) && !empty($socket))
		{
			$port = null;
		}

		// Get the port number or socket name
		if (is_numeric($port))
		{
			$port   = (int) $port;
			$socket = '';
		}
		elseif (is_string($port) && empty($socket))
		{
			$socket = $port;
			$port = null;
		}

		// If there is a socket the hostname must be null
		if (!empty($socket))
		{
			$host = null;
		}

		// If there is a socket the port must be null
		if (!empty($socket))
		{
			$port = null;
		}

		// If there is a numeric port and the hostname is 'localhost' convert to 127.0.0.1
		if (is_numeric($port) && ($host === 'localhost'))
		{
			$host = '127.0.0.1';
		}

		/**
		 * Special case: MySQL sockets on Windows need to be enclosed with parentheses and have \\.\ in front.
		 *
		 * @see https://dev.mysql.com/doc/mysql-shell/8.0/en/mysql-shell-connection-socket.html
		 * @see https://www.php.net/manual/en/mysqli.quickstart.connections.php
		 */
		if (!empty($socket) && $isNamedPipe)
		{
			$host = '.';

			/**
			 * Remove any existing parentheses, otherwise URL-decode the socket (in case it was given in the correct
			 * percent encoded format).
			 */
			if (substr($socket, 0, 1) === '(' && substr($socket, -1) === ')')
			{
				$socket = substr($socket, 1, -1);

			}
			else
			{
				$socket = rawurldecode($socket);
			}

			// If the socket doesn't already start with \\.\ add it
			if (substr($socket, 0, 4) !== '\\\\.\\')
			{
				$socket = '\\\\.\\' . $socket;
			}

			$socket = '(' . $socket . ')';
		}

		// Finally, if it's a persistent connection we have to prefix the hostname with 'p:'
		$host = ($isPersistent && $host !== null) ? "p:$host" : $host;
	}

}