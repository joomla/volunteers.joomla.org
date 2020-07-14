<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('number');

/**
 * Backwards compatibility field. DO NOT USE IN PHP 7.2 AND LATER.
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Numeric extends Number
{
	public function __construct($form = null)
	{
		parent::__construct($form);

		$this->form->getModel()->getContainer()->platform->logDeprecated("The numeric field is deprecated and may cause fatal errors in PHP 7.2 and later. Use the number field type instead.");
	}

}
