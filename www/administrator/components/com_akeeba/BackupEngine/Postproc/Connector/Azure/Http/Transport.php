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

use Akeeba\Engine\Postproc\Connector\S3v4\Input;

/**
 * @category   Microsoft
 * @package    Microsoft_Http
 * @subpackage Transport
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
abstract class Transport
{
	/** HTTP VERBS */
	const VERB_GET = 'GET';

	const VERB_PUT = 'PUT';

	const VERB_POST = 'POST';

	const VERB_DELETE = 'DELETE';

	const VERB_HEAD = 'HEAD';

	const VERB_MERGE = 'MERGE';

	/**
	 * Use proxy?
	 *
	 * @var boolean
	 */
	protected $_useProxy = false;

	/**
	 * Proxy url
	 *
	 * @var string
	 */
	protected $_proxyUrl = '';

	/**
	 * Proxy port
	 *
	 * @var int
	 */
	protected $_proxyPort = 80;

	/**
	 * Proxy credentials
	 *
	 * @var string
	 */
	protected $_proxyCredentials = '';
	/**
	 * User agent string
	 *
	 * @var string
	 */
	protected $_userAgent = '\\Akeeba\\Engine\\Postproc\\Connector\\Azure\\Http\\Transport';

	/**
	 * Create channel
	 *
	 * @param $type string   Transport channel type
	 *
	 * @return Response
	 */
	public static function createChannel($type = '\\Akeeba\\Engine\\Postproc\\Connector\\Azure\\Http\\Transport\\Curl')
	{
		return new $type();
	}

	/**
	 * Set proxy
	 *
	 * @param   boolean  $useProxy          Use proxy?
	 * @param   string   $proxyUrl          Proxy URL
	 * @param   int      $proxyPort         Proxy port
	 * @param   string   $proxyCredentials  Proxy credentials
	 */
	public function setProxy($useProxy = false, $proxyUrl = '', $proxyPort = 80, $proxyCredentials = '')
	{
		$this->_useProxy         = $useProxy;
		$this->_proxyUrl         = $proxyUrl;
		$this->_proxyPort        = $proxyPort;
		$this->_proxyCredentials = $proxyCredentials;
	}

	/**
	 * Perform GET request
	 *
	 * @param   string  $url        Url to request
	 * @param   array   $variables  Array of key-value pairs to use in the request
	 * @param   array   $headers    Array of key-value pairs to use as additional headers
	 * @param   string  $rawBody    Raw body to send to server
	 *
	 * @return Response
	 */
	public function get($url, $variables = [], $headers = [], $rawBody = null)
	{
		return $this->request(self::VERB_GET, $url, $variables, $headers, $rawBody);
	}

	/**
	 * Perform PUT request
	 *
	 * @param   string  $url        Url to request
	 * @param   array   $variables  Array of key-value pairs to use in the request
	 * @param   array   $headers    Array of key-value pairs to use as additional headers
	 * @param   string  $rawBody    Raw body to send to server
	 *
	 * @return Response
	 */
	public function put($url, $variables = [], $headers = [], $rawBody = null)
	{
		return $this->request(self::VERB_PUT, $url, $variables, $headers, $rawBody);
	}

	/**
	 * Perform POST request
	 *
	 * @param   string  $url        Url to request
	 * @param   array   $variables  Array of key-value pairs to use in the request
	 * @param   array   $headers    Array of key-value pairs to use as additional headers
	 * @param   string  $rawBody    Raw body to send to server
	 *
	 * @return Response
	 */
	public function post($url, $variables = [], $headers = [], $rawBody = null)
	{
		return $this->request(self::VERB_POST, $url, $variables, $headers, $rawBody);
	}

	/**
	 * Perform DELETE request
	 *
	 * @param   string  $url        Url to request
	 * @param   array   $variables  Array of key-value pairs to use in the request
	 * @param   array   $headers    Array of key-value pairs to use as additional headers
	 * @param   string  $rawBody    Raw body to send to server
	 *
	 * @return Response
	 */
	public function delete($url, $variables = [], $headers = [], $rawBody = null)
	{
		return $this->request(self::VERB_DELETE, $url, $variables, $headers, $rawBody);
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
	public abstract function request($httpVerb, $url, $variables = [], $headers = [], Input $inputObject = null);
}