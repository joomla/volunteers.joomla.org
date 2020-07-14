<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Factory\Scaffolding\Layout;

defined('_JEXEC') or die;

/**
 * Erects a scaffolding XML for read views
 *
 * @package FOF30\Factory\Scaffolding
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class ItemErector extends FormErector implements ErectorInterface
{
	public function build()
	{
		$this->addDescriptions = false;

		parent::build();

		$this->xml->addAttribute('type', 'read');

		$this->pushResults();
	}
}
