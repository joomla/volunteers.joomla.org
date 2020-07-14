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

namespace Akeeba\Engine\Postproc\Connector\Azure\Http\Transport;



use Akeeba\Engine\Postproc\Connector\Azure\Exception\Transport as TransportException;
use Akeeba\Engine\Postproc\Connector\Azure\Http\Response;
use Akeeba\Engine\Postproc\Connector\Azure\Http\Transport;
use Akeeba\Engine\Postproc\Connector\S3v4\Input;

/**
 * @category   Microsoft
 * @package    Microsoft_Http
 * @subpackage Transport
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class Curl extends Transport
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		if (!extension_loaded('curl'))
		{
			throw new TransportException('cURL extension has to be loaded to use the Azure feature.');
		}
	}

	/**
	 * Perform request
	 *
	 * @param   string  $httpVerb     Http verb to use in the request
	 * @param   string  $url          Url to request
	 * @param   array   $variables    Array of key-value pairs to use in the request
	 * @param   array   $headers      Array of key-value pairs to use as additional headers
	 * @param   string  $inputObject  Raw body to send to server
	 *
	 * @return Response
	 */
	public function request($httpVerb, $url, $variables = [], $headers = [], Input $inputObject = null)
	{
		// Create a new cURL instance
		$curlHandle = curl_init();
		@curl_setopt($curlHandle, CURLOPT_CAINFO, AKEEBA_CACERT_PEM);
		curl_setopt($curlHandle, CURLOPT_USERAGENT, $this->_userAgent);
		curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 0);

		// Set URL
		curl_setopt($curlHandle, CURLOPT_URL, $url);

		// Set HTTP parameters (version and request method)
		curl_setopt($curlHandle, CURL_HTTP_VERSION_1_1, true);
		switch ($httpVerb)
		{
			case Transport::VERB_GET:
				curl_setopt($curlHandle, CURLOPT_HTTPGET, true);
				break;
			case Transport::VERB_POST:
				curl_setopt($curlHandle, CURLOPT_POST, true);
				break;
			/*case Transport::VERB_PUT:
				curl_setopt($curlHandle, CURLOPT_PUT,     true);
				break;*/
			case Transport::VERB_HEAD:
				// http://stackoverflow.com/questions/770179/php-curl-head-request-takes-a-long-time-on-some-sites
				curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'HEAD');
				curl_setopt($curlHandle, CURLOPT_NOBODY, true);
				break;
			default:
				curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $httpVerb);
				break;
		}

		// Clear Content-Length header
		$headers["Content-Length"] = 0;

		// Ensure headers are returned
		curl_setopt($curlHandle, CURLOPT_HEADER, true);

		// Do not verify SSl peer (Windows versions of cURL have an outdated CA)
		curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);

		// Set proxy?
		if ($this->_useProxy)
		{
			curl_setopt($curlHandle, CURLOPT_PROXY, $this->_proxyUrl);
			curl_setopt($curlHandle, CURLOPT_PROXYPORT, $this->_proxyPort);
			curl_setopt($curlHandle, CURLOPT_PROXYUSERPWD, $this->_proxyCredentials);
		}

		// Ensure response is returned
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

		// Set post fields / raw data
		// http://www.php.net/manual/en/function.curl-setopt.php#81161
		switch ($httpVerb)
		{
			case Transport::VERB_GET:
				break;

			case Transport::VERB_PUT:
			case Transport::VERB_POST:

				if (!is_object($inputObject) || !($inputObject instanceof Input))
				{
					$inputObject = new Input();
				}

				if (isset($headers["Content-Length"]))
				{
					unset($headers["Content-Length"]);
				}

				$headers["Content-Length"] = $inputObject->getSize();

				$type = $inputObject->getInputType();

				if ($type == Input::INPUT_DATA)
				{
					curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $httpVerb);

					$data = $inputObject->getDataReference();

					if (strlen($data))
					{
						curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
					}

					if ($headers["Content-Length"] > 0)
					{
						curl_setopt($curlHandle, CURLOPT_BUFFERSIZE, $headers["Content-Length"]);
					}
				}
				else
				{
					curl_setopt($curlHandle, CURLOPT_PUT, true);
					curl_setopt($curlHandle, CURLOPT_INFILE, $inputObject->getFp());

					if ($headers["Content-Length"] > 0)
					{
						curl_setopt($curlHandle, CURLOPT_INFILESIZE, $headers["Content-Length"]);
					}
				}


				break;

			case 'HEAD':
				curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'HEAD');
				curl_setopt($curlHandle, CURLOPT_NOBODY, true);
				break;

			case 'DELETE':
				curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;

			default:
				break;
		}

		// Set Content-Type header if required
		if (!isset($headers["Content-Type"]))
		{
			$headers["Content-Type"] = '';
		}

		// Disable Expect: 100-Continue
		// http://be2.php.net/manual/en/function.curl-setopt.php#82418
		$headers["Expect"] = '';

		// Add additional headers to cURL instance
		$curlHeaders = [];

		foreach ($headers as $key => $value)
		{
			$curlHeaders[] = $key . ': ' . $value;
		}

		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $curlHeaders);

		// DEBUG: curl_setopt($curlHandle, CURLINFO_HEADER_OUT, true);

		// Execute request
		$rawResponse = curl_exec($curlHandle);
		$response    = null;
		if ($rawResponse)
		{
			$response = Response::fromString($rawResponse);
			// DEBUG: var_dump($url);
			// DEBUG: var_dump(curl_getinfo($curlHandle,CURLINFO_HEADER_OUT));
			// DEBUG: var_dump($rawResponse);
		}
		else
		{
			throw new TransportException('cURL error occured during request for ' . $url . ': ' . curl_errno($curlHandle) . ' - ' . curl_error($curlHandle));
		}
		curl_close($curlHandle);

		return $response;
	}
}