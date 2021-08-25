<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Document;

defined('JPATH_PLATFORM') or die;

/**
 * ImageDocument class, provides an easy interface to output image data
 *
 * @since  3.0.0
 */
class ImageDocument extends Document
{
	/**
	 * Class constructor
	 *
	 * @param   array  $options  Associative array of options
	 *
	 * @since   3.0.0
	 */
	public function __construct($options = array())
	{
		parent::__construct($options);

		// Set mime type
		$this->_mime = 'image/png';

		// Set document type
		$this->_type = 'image';
	}

	/**
	 * Render the document.
	 *
	 * @param   boolean  $cache   If true, cache the output
	 * @param   array    $params  Associative array of attributes
	 *
	 * @return  string  The rendered data
	 *
	 * @since   3.0.0
	 */
	public function render($cache = false, $params = array())
	{
		// Get the image type
		$type = \JFactory::getApplication()->input->get('type', 'png');

		switch ($type)
		{
			case 'jpg':
			case 'jpeg':
				$this->_mime = 'image/jpeg';
				break;
			case 'gif':
				$this->_mime = 'image/gif';
				break;
			case 'png':
			default:
				$this->_mime = 'image/png';
				break;
		}

		$this->_charset = null;

		parent::render($cache, $params);

		return $this->getBuffer();
	}
}
