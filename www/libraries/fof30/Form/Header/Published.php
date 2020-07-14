<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Header;

use JHtml;

defined('_JEXEC') or die;

/**
 * Field header for Published (enabled) columns
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Published extends Selectable
{
	/**
	 * Create objects for the options
	 *
	 * @return  array  The array of option objects
	 */
	protected function getOptions()
	{
		$config = array(
			'published'		 => 1,
			'unpublished'	 => 1,
			'archived'		 => 0,
			'trash'			 => 0,
			'all'			 => 0,
		);

		if ($this->element['show_published'] == 'false')
		{
			$config['published'] = 0;
		}

		if ($this->element['show_unpublished'] == 'false')
		{
			$config['unpublished'] = 0;
		}

		if ($this->element['show_archived'] == 'true')
		{
			$config['archived'] = 1;
		}

		if ($this->element['show_trash'] == 'true')
		{
			$config['trash'] = 1;
		}

		if ($this->element['show_all'] == 'true')
		{
			$config['all'] = 1;
		}

		$options = JHtml::_('jgrid.publishedOptions', $config);

		reset($options);

		return $options;
	}
}
