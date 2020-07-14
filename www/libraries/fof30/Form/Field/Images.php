<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

use JHtml;
use JText;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('imagelist');

/**
 * Form Field class for the FOF framework
 * Images field.
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Images extends ImageList
{
	/**
	 * Method to get the field input markup.
	 *
	 * @param   array   $fieldOptions  Options to be passed into the field
	 *
	 * @return  string  The field HTML
	 */
	public function getFieldContents(array $fieldOptions = array())
	{
		$id    = isset($fieldOptions['id']) ? 'id="' . $fieldOptions['id'] . '" ' : '';
		$class = $this->class . (isset($fieldOptions['class']) ? ' ' . $fieldOptions['class'] : '');

		if (!is_array($this->value))
		{
			$this->value = (array) $this->value;
		}

		$html = '<span ' . ($id ? $id : '') . 'class="'. $class . '">';

		foreach ($this->value as $image)
		{
			$imgattr = array();
            $alt     = null;

			if ($class)
			{
				$imgattr['class'] = $class;
			}

			if ($this->element['style'])
			{
				$imgattr['style'] = (string) $this->element['style'];
			}

			if ($this->element['width'])
			{
				$imgattr['width'] = (string) $this->element['width'];
			}

			if ($this->element['height'])
			{
				$imgattr['height'] = (string) $this->element['height'];
			}

			if ($this->element['align'])
			{
				$imgattr['align'] = (string) $this->element['align'];
			}

			if ($this->element['rel'])
			{
				$imgattr['rel'] = (string) $this->element['rel'];
			}

			if ($this->element['alt'])
			{
				$alt = JText::_((string) $this->element['alt']);
			}

			if ($this->element['title'])
			{
				$imgattr['title'] = JText::_((string) $this->element['title']);
			}

			$path = (string) $this->element['directory'];
			$path = trim($path, '/' . DIRECTORY_SEPARATOR);

            $platform = $this->form->getContainer()->platform;
            $baseDirs = $platform->getPlatformBaseDirs();

			if ($image && file_exists($baseDirs['root'] . '/' . $path . '/' . $image))
			{
				$src   = $platform->URIroot() . '/' . $path . '/' . $image;
                $html .= JHtml::image($src, $alt, $imgattr);
			}
			else
			{
                // JHtml::image returns weird stuff when an empty path is provided, so let's be safe than sorry and return empty
				$html .= '';
			}
		}

		$html .= '</span>';

		return $html;
	}
}
