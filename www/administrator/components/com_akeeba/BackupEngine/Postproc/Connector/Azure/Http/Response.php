<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Copyright (c) 2009, RealDolmen
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of RealDolmen nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY RealDolmen ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL RealDolmen BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Microsoft
 * @package    Microsoft
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */

namespace Akeeba\Engine\Postproc\Connector\Azure\Http;



use Akeeba\Engine\Postproc\Connector\Azure\Exception\Http;

/**
 * Response
 *
 * This class is partially based on Zend Framework Zend_Http_Response - http://framework.zend.com
 *
 * @category   Microsoft
 * @package    Microsoft_Http
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Response
{
	/**
	 * List of all known HTTP response status codes
	 *
	 * @var array
	 */
	protected static $_statusMessages = [
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',

		// Success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 is deprecated but reserved
		307 => 'Temporary Redirect',

		// Client Error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',

		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded',
	];

	/**
	 * The HTTP version (1.0, 1.1)
	 *
	 * @var string
	 */
	protected $_version;

	/**
	 * The HTTP response code
	 *
	 * @var int
	 */
	protected $_code;

	/**
	 * The HTTP response code as string
	 * (e.g. 'Not Found' for 404 or 'Internal Server Error' for 500)
	 *
	 * @var string
	 */
	protected $_message;

	/**
	 * The HTTP response headers array
	 *
	 * @var array
	 */
	protected $_headers = [];

	/**
	 * The HTTP response body
	 *
	 * @var string
	 */
	protected $_body;

	/**
	 * HTTP response constructor
	 *
	 * @param   int     $code     Response code (200, 404, 500, ...)
	 * @param   array   $headers  Headers array
	 * @param   string  $body     Response body
	 * @param   string  $version  HTTP version
	 *
	 * @throws Http
	 */
	public function __construct($code, $headers, $body = null, $version = '1.1')
	{
		// Code
		$this->_code = $code;

		// Message
		$this->_message = self::$_statusMessages[$code];

		// Body
		$this->_body = $body;

		// Version
		if (!preg_match('/^\d(\.\d)?$/', $version))
		{
			throw new Http('No valid HTTP version was passed: ' . $version);
		}
		$this->_version = $version;

		// Headers
		if (!is_array($headers))
		{
			throw new Http('No valid headers were passed');
		}
		else
		{
			foreach ($headers as $name => $value)
			{
				if (is_int($name))
				{
					list($name, $value) = explode(":", $value, 1);
				}

				$this->_headers[ucwords(strtolower($name))] = trim($value);
			}
		}
	}

	/**
	 * Extract the response code from a response string
	 *
	 * @param   string  $responseString
	 *
	 * @return int
	 */
	public static function extractCode($responseString)
	{
		preg_match("|^HTTP/[\d\.x]+ (\d+)|", $responseString, $m);

		if (isset($m[1]))
		{
			return (int) $m[1];
		}
		else
		{
			return false;
		}
	}

	/**
	 * Extract the HTTP message from a response
	 *
	 * @param   string  $responseString
	 *
	 * @return string
	 */
	public static function extractMessage($responseString)
	{
		preg_match("|^HTTP/[\d\.x]+ \d+ ([^\r\n]+)|", $responseString, $m);

		if (isset($m[1]))
		{
			return $m[1];
		}
		else
		{
			return false;
		}
	}

	/**
	 * Extract the HTTP version from a response
	 *
	 * @param   string  $responseString
	 *
	 * @return string
	 */
	public static function extractVersion($responseString)
	{
		preg_match("|^HTTP/([\d\.x]+) \d+|", $responseString, $m);

		if (isset($m[1]))
		{
			return $m[1];
		}
		else
		{
			return false;
		}
	}

	/**
	 * Extract the headers from a response string
	 *
	 * @param   string  $responseString
	 *
	 * @return array
	 */
	public static function extractHeaders($responseString)
	{
		$headers = [];

		// First, split body and headers
		$parts = preg_split('|(?:\r?\n){2}|m', $responseString, 2);
		if (!$parts[0])
		{
			return $headers;
		}

		// Split headers part to lines
		$lines = explode("\n", $parts[0]);
		unset($parts);
		$last_header = null;

		foreach ($lines as $line)
		{
			$line = trim($line, "\r\n");
			if ($line == "")
			{
				break;
			}

			if (preg_match("|^([\w-]+):\s+(.+)|", $line, $m))
			{
				unset($last_header);
				$h_name  = strtolower($m[1]);
				$h_value = $m[2];

				if (isset($headers[$h_name]))
				{
					if (!is_array($headers[$h_name]))
					{
						$headers[$h_name] = [$headers[$h_name]];
					}

					$headers[$h_name][] = $h_value;
				}
				else
				{
					$headers[$h_name] = $h_value;
				}
				$last_header = $h_name;
			}
			elseif (preg_match("|^\s+(.+)$|", $line, $m) && $last_header !== null)
			{
				if (is_array($headers[$last_header]))
				{
					end($headers[$last_header]);
					$last_header_key                         = key($headers[$last_header]);
					$headers[$last_header][$last_header_key] .= $m[1];
				}
				else
				{
					$headers[$last_header] .= $m[1];
				}
			}
		}

		return $headers;
	}

	/**
	 * Extract the body from a response string
	 *
	 * @param   string  $response_str
	 *
	 * @return string
	 */
	public static function extractBody($responseString)
	{
		$parts = preg_split('|(?:\r?\n){2}|m', $responseString, 2);
		if (isset($parts[1]))
		{
			return $parts[1];
		}

		return '';
	}

	/**
	 * Create a new Response object from a string
	 *
	 * @param   string  $response_str
	 *
	 * @return Response
	 */
	public static function fromString($response_str)
	{
		$code    = self::extractCode($response_str);
		$headers = self::extractHeaders($response_str);
		$body    = self::extractBody($response_str);
		$version = self::extractVersion($response_str);
		$message = self::extractMessage($response_str);

		return new Response($code, $headers, $body, $version, $message);
	}

	/**
	 * Check whether the response is an error
	 *
	 * @return boolean
	 */
	public function isError()
	{
		$restype = floor($this->_code / 100);

		return ($restype == 4 || $restype == 5);
	}

	/**
	 * Check whether the response in successful
	 *
	 * @return boolean
	 */
	public function isSuccessful()
	{
		$restype = floor($this->_code / 100);

		return ($restype == 2 || $restype == 1);
	}

	/**
	 * Check whether the response is a redirection
	 *
	 * @return boolean
	 */
	public function isRedirect()
	{
		$restype = floor($this->_code / 100);

		return ($restype == 3);
	}

	/**
	 * Get the HTTP version (1.0, 1.1)
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return $this->_version;
	}

	/**
	 * Get the HTTP response code
	 *
	 * @return int
	 */
	public function getCode()
	{
		return $this->_code;
	}

	/**
	 * Get the HTTP response code as string
	 * (e.g. 'Not Found' for 404 or 'Internal Server Error' for 500)
	 *
	 * @return string
	 */
	public function getMessage()
	{
		return $this->_message;
	}

	/**
	 * Get the HTTP response headers array
	 *
	 * @return array
	 */
	public function getHeaders()
	{
		if (!is_array($this->_headers))
		{
			$this->_headers = [];
		}

		return $this->_headers;
	}

	/**
	 * Get a specific header as string, or null if it is not set
	 *
	 * @param   string  $header
	 *
	 * @return string|array|null
	 */
	public function getHeader($header)
	{
		$header = ucwords(strtolower($header));
		if (!is_string($header) || !isset($this->_headers[$header]))
		{
			return null;
		}

		return $this->_headers[$header];
	}

	/**
	 * The HTTP response body
	 *
	 * @return string
	 */
	public function getBody()
	{
		return $this->_body;
	}
}
