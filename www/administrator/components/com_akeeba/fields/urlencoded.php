<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

if (class_exists('JFormFieldUrlencoded'))
{
	return;
}

JFormHelper::loadFieldClass('text');

class JFormFieldUrlencoded extends JFormFieldText
{
	protected function getInput()
	{
		$this->value = urlencode($this->value);

		return parent::getInput();
	}
}
