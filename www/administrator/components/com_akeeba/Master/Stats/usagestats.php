<?php
/**
 * @package   Usagestats
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

class AkeebaUsagestats
{
	/**
	 * Unique identifier for the site, created from server variables
	 *
	 * @var string
	 */
	private $siteId;

	/**
	 * Associative array of data being sent
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Remote url to upload the stats
	 *
	 * @var string
	 */
	private $remoteUrl = 'https://abrandnewsite.com/index.php';

	/**
	 * Set the unique, anonymous site identifier
	 *
	 * @param   string  $siteId  The site ID to set
	 *
	 * @return  void
	 */
	public function setSiteId($siteId)
	{
		$this->siteId = $siteId;
	}

	/**
	 * Sets the value of a collected variable. Use NULL to unset it.
	 *
	 * @param   string  $key    Variable name
	 * @param   string  $value  Variable value
	 */
	public function setValue($key, $value)
	{
		$this->data[$key] = $value;

		if (is_null($value))
		{
			unset($this->data[$key]);
		}
	}

	/**
	 * Uploads collected data to the remote server
	 *
	 * @param   bool  $useIframe  Should I create an iframe to upload data or should I use cURL/fopen?
	 *
	 * @return  string|bool  The HTML code if an iframe is requested or a boolean if we're using cURL/fopen
	 */
	public function sendInfo($useIframe = false)
	{
		// No site ID? Well, simply do nothing
		if (!$this->siteId)
		{
			return '';
		}

		// First of all let's add the siteId
		$this->setValue('sid', $this->siteId);

		// Then let's create the url
		$url = $this->remoteUrl . '?' . http_build_query($this->data);

		// Should I create an iframe?
		if ($useIframe)
		{
			return '<!-- Anonymous usage statistics collection for Akeeba software --><iframe style="display: none" src="' . $url . '"></iframe>';
		}

		// Do we have cURL installed?
		if (
			function_exists('curl_init')
			&& function_exists('curl_setopt')
			&& function_exists('curl_exec'))
		{
			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_TIMEOUT, 5);

			return curl_exec($ch);
		}

		// We do not have cURL. Let's try with fopen instead.
		return @fopen($url, 'r');
	}
}
