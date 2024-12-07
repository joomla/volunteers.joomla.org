<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector\AzureModern;

defined('AKEEBAENGINE') || die();

/**
 * Microsoft Azure Storage request signing with the Storage account key access method.
 *
 * @since 9.2.1
 */
class Credentials
{
	/**
	 * Account name for Microsoft Azure
	 *
	 * @var   string
	 * @since 9.2.1
	 */
	private $accountName = '';

	/**
	 * Account key for Microsoft Azure
	 *
	 * @var   string
	 * @since 9.2.1
	 */
	private $accountKey = '';

	/**
	 * Use path-style URI's
	 *
	 * @var   bool
	 * @since 9.2.1
	 */
	private $usePathStyleUri = false;

	/**
	 * Creates a new Credentials instance
	 *
	 * @param   string  $accountName      Account name for Microsoft Azure
	 * @param   string  $accountKey       Account key for Microsoft Azure
	 * @param   bool    $usePathStyleUri  Use path-style URI's?
	 *
	 * @since   9.2.1
	 */
	public function __construct(string $accountName, string $accountKey, bool $usePathStyleUri = false)
	{
		$this->accountName     = $accountName;
		$this->accountKey      = base64_decode($accountKey);
		$this->usePathStyleUri = $usePathStyleUri;
	}

	/**
	 * Get a credentials object given a connection string.
	 *
	 * The connection string looks like this:
	 *   DefaultEndpointsProtocol=https;AccountName=foobar;AccountKey=AAAAAA=;EndpointSuffix=core.windows.net
	 *
	 * @param   string  $connectionString  The connection string to parse
	 *
	 * @return  static
	 * @since   9.2.1
	 */
	public static function fromConnectionString(string $connectionString): self
	{
		$lines = explode(';', $connectionString);
		$data  = [];

		foreach ($lines as $line)
		{
			$parts = explode('=', $line, 2);

			if (count($parts) != 2)
			{
				continue;
			}

			$data[strtolower($parts[0])] = $parts[1];
		}

		return new self($data['accountname'] ?? '', $data['accountkey'] ?? '', false);
	}

	/**
	 * Set account name for Microsoft Azure
	 *
	 * @param   string  $value
	 *
	 * @since   9.2.1
	 */
	public function setAccountName(string $value): void
	{
		$this->accountName = $value;
	}

	/**
	 * Set account key for Microsoft Azure
	 *
	 * @param   string  $value  The base64-encoded Key
	 *
	 * @since   9.2.1
	 */
	public function setAccountKey(string $value): void
	{
		$this->accountKey = base64_decode($value);
	}

	/**
	 * Set use path-style URI's
	 *
	 * @param   boolean  $value
	 *
	 * @since   9.2.1
	 */
	public function setUsePathStyleUri(bool $value)
	{
		$this->usePathStyleUri = $value;
	}

	/**
	 * Sign request headers with credentials
	 *
	 * @param   string  $httpVerb     HTTP verb the request will use
	 * @param   string  $path         Path for the request
	 * @param   string  $queryString  Query string for the request
	 * @param   array   $headers      x-ms headers to add
	 *
	 * @return  array   Array of headers
	 * @see     https://docs.microsoft.com/en-us/azure/storage/common/storage-rest-api-auth#creating-the-authorization-header
	 * @since   9.2.1
	 */
	public function signRequestHeaders(string $httpVerb, string $path = '/', string $queryString = '', array $headers = [])
	{
		// Determine path and query
		$queryString = $this->prepareQueryStringForSigning($queryString);

		// Request date (RFC 1123) must always be present
		$requestDate          = $this->extractHeader(
			$headers, 'x-ms-date',
			$this->extractHeader($headers, 'Date', gmdate('D, d M Y H:i:s', time()) . ' GMT')
		);
		$headers['x-ms-date'] = $requestDate;

		// Cast header values to strings
		$headers = array_map(function ($x) {
			if (is_bool($x))
			{
				$x = $x ? 'True' : 'False';
			}
			elseif (is_scalar($x))
			{
				$x = (string) $x;
			}
			else
			{
				$x = null;
			}

			return $x;
		}, $headers);

		$headers = array_filter($headers, function ($x) {
			return $x !== null;
		});

		// Build canonicalized headers
		$canonicalizedHeaders = array_filter($headers, function ($x) {
			return strpos(strtolower($x), 'x-ms-') === 0;
		}, ARRAY_FILTER_USE_KEY);
		$canonicalizedHeaders = array_map(function ($k, $v) {
			return strtolower($k) . ':' . $v;
		}, array_keys($canonicalizedHeaders), array_values($canonicalizedHeaders));
		sort($canonicalizedHeaders);

		// Build canonicalized resource string
		$canonicalizedResource = '/' . $this->accountName
			. ($this->usePathStyleUri ? ('/' . $this->accountName) : '')
			. ($this->usePathStyleUri ? substr($path, strpos($path, '/')) : $path)
			. ($queryString ?: '');

		/**
		 * Create the string to sign. It consists of the following:
		 *  VERB + "\n" +
		 *  Content-Encoding + "\n" +
		 *  Content-Language + "\n" +
		 *  Content-Length + "\n" +
		 *  Content-MD5 + "\n" +
		 *  Content-Type + "\n" +
		 *  Date + "\n" +
		 *  If-Modified-Since + "\n" +
		 *  If-Match + "\n" +
		 *  If-None-Match + "\n" +
		 *  If-Unmodified-Since + "\n" +
		 *  Range + "\n" +
		 *  CanonicalizedHeaders +
		 *  CanonicalizedResource
		 *
		 * @see https://docs.microsoft.com/en-us/rest/api/storageservices/authorize-with-shared-key#specifying-the-authorization-header
		 */
		$stringToSign = implode(
			"\n", array_filter([
				strtoupper($httpVerb), // VERB
				$this->extractHeader($headers, 'Content-Encoding', '') ?: '',
				$this->extractHeader($headers, 'Content-Language', '') ?: '',
				intval($this->extractHeader($headers, 'Content-Length', 0)) ?: '',
				$this->extractHeader($headers, 'Content-MD5', '') ?: '',
				$this->extractHeader($headers, 'Content-Type', '') ?: '',
				$this->extractHeader($headers, 'Date', '') ?: '',
				$this->extractHeader($headers, 'If-Modified-Since', '') ?: '',
				$this->extractHeader($headers, 'If-Match', '') ?: '',
				$this->extractHeader($headers, 'If-None-Match', '') ?: '',
				$this->extractHeader($headers, 'If-Unmodified-Since', '') ?: '',
				$this->extractHeader($headers, 'Range', '') ?: '',
				count($canonicalizedHeaders) ? implode("\n", $canonicalizedHeaders) : null,
				$canonicalizedResource,
			], function ($x) {
				return $x !== null;
			})
		);

		//echo "\n/**/" . $stringToSign . "/**/\n";

		$signString = base64_encode(hash_hmac('sha256', $stringToSign, $this->accountKey, true));

		// Sign request
		$headers['x-ms-date']     = $requestDate;
		$headers['Authorization'] = 'SharedKey ' . $this->accountName . ':' . $signString;

		// Return headers
		return $headers;
	}

	/**
	 * Am I supposed to use path style URIs?
	 *
	 * @return  bool
	 * @since   9.2.1
	 */
	public function isUsePathStyleUri(): bool
	{
		return $this->usePathStyleUri;
	}

	/**
	 * Get the Microsoft Azure account name
	 *
	 * @return  string
	 * @since   9.2.1
	 */
	public function getAccountName(): string
	{
		return $this->accountName;
	}

	/**
	 * Get the (decoded) account key
	 *
	 * @return  string
	 */
	public function getAccountKey(): string
	{
		return $this->accountKey;
	}

	/**
	 * Prepare query string for signing
	 *
	 * @param   string  $value  Original query string
	 *
	 * @return  string  Query string for signing
	 * @since   9.2.1
	 */
	private function prepareQueryStringForSigning($value): string
	{
		$value = substr($value, 0, 1) === '?' ? substr($value, 1) : $value;

		parse_str($value, $variables);

		$variables = array_map(function ($k, $v) {
			if (!is_scalar($v))
			{
				return null;
			}

			return strtolower($k) . ':' . $v;
		}, array_keys($variables), array_values($variables));

		$variables = array_filter($variables, function ($x) {
			return !empty($x);
		});

		if (empty($variables))
		{
			return '';
		}

		asort($variables);

		return "\n" . (implode("\n", $variables));
	}

	/**
	 * Extract a header value, case-insensitive
	 *
	 * @param   array        $headers  The dictionary of headers
	 * @param   string       $key      The key to extract, case-insensitive
	 * @param   string|null  $default  The default value to return if the key is missing
	 *
	 * @return  string|null
	 * @since   9.2.1
	 */
	private function extractHeader(array $headers, string $key, ?string $default = null): ?string
	{
		static $convertedHeaders = [];

		if (md5(serialize($convertedHeaders)) != md5(serialize($headers)))
		{
			$convertedHeaders = array_combine(
				array_map('strtolower', array_keys($headers)),
				array_values($headers)
			);
		}

		return $convertedHeaders[strtolower($key)] ?? $default;
	}
}