<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Configuration\Domain;

use SimpleXMLElement;

defined('_JEXEC') || die;

/**
 * Configuration parser for the dispatcher-specific settings
 *
 * @since    2.1
 */
class Dispatcher implements DomainInterface
{
	/**
	 * Parse the XML data, adding them to the $ret array
	 *
	 * @param   SimpleXMLElement   $xml  The XML data of the component's configuration area
	 * @param   array             &$ret  The parsed data, in the form of a hash array
	 *
	 * @return  void
	 */
	public function parseDomain(SimpleXMLElement $xml, array &$ret): void
	{
		// Initialise
		$ret['dispatcher'] = [];

		// Parse the dispatcher configuration
		$dispatcherData = $xml->dispatcher;

		// Sanity check

		if (empty($dispatcherData))
		{
			return;
		}

		$options = $xml->xpath('dispatcher/option');

		foreach ($options as $option)
		{
			$key                     = (string) $option['name'];
			$ret['dispatcher'][$key] = (string) $option;
		}
	}

	/**
	 * Return a configuration variable
	 *
	 * @param   string  &$configuration  Configuration variables (hashed array)
	 * @param   string   $var            The variable we want to fetch
	 * @param   mixed    $default        Default value
	 *
	 * @return  mixed  The variable's value
	 */
	public function get(array &$configuration, string $var, $default = null)
	{
		if ($var == '*')
		{
			return $configuration['dispatcher'];
		}

		if (isset($configuration['dispatcher'][$var]))
		{
			return $configuration['dispatcher'][$var];
		}
		else
		{
			return $default;
		}
	}
}
