<?php

/**
 * @version    CVS: 1.23.0
 * @package    com_yoursites
 * @author     Geraint Edwards <yoursites@gwesystems.com>
 * @copyright  2016-2020 GWE Systems Ltd
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

Use Joomla\Filesystem\Folder;
Use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');


class JFormFieldClonealiases extends JFormFieldList
{

	protected $type = 'Clonealises';

	protected function getInput()
	{
		$options = array();
		if (!is_array($this->value))
		{
			$this->value = array();
		}

		$folders = Folder::folders(JPATH_SITE , "._ysts_", false, false, array('.svn', 'CVS', '.DS_Store', '__MACOSX'), array());

		$input = "";
		foreach ($folders as $folder)
		{
			$value = isset($this->value[$folder]) && !empty(trim($this->value[$folder])) ? $this->value[$folder] : $folder;
			$input .= "<input type='text' name='" . $this->name. "[$folder]' value='" . $value . "'/> => $folder <br>\n";
		}
		if (empty($input))
		{
			$this->hidden = true;
		}
		return $input;
	}


}
