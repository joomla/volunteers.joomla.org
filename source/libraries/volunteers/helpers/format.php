<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersHelperFormat
{
	public static function getNsStateText($value)
	{
		$options = array(
					JText::_('COM_VOLUNTEERS_NS_STATE_0'),
					JText::_('COM_VOLUNTEERS_NS_STATE_1'),
					JText::_('COM_VOLUNTEERS_NS_STATE_2')
		);

		if (array_key_exists($value, $options))
		{
			return $options[$value];
		}

		return '';
	}
	
	public static function getNsPositionText($value)
	{
		// Todo: implement
		return '';
	}
	
	public static function role($id)
	{
		if($id == 1)
		{
			$text = JText::_('COM_VOLUNTEERS_ROLE_MEMBER');
		}
		if($id == 2)
		{
			$text = JText::_('COM_VOLUNTEERS_ROLE_LEAD');
		}
		if($id == 3)
		{
			$text = JText::_('COM_VOLUNTEERS_ROLE_LIAISON_CLT');
		}
		if($id == 4)
		{
			$text = JText::_('COM_VOLUNTEERS_ROLE_LIAISON_PLT');
		}
		if($id == 5)
		{
			$text = JText::_('COM_VOLUNTEERS_ROLE_LIAISON_OSM');
		}

		return $text;
	}

	public static function ownership($id)
	{
		$group = FOFModel::getTmpInstance('Groups', 'VolunteersModel')
			->setID($id)
			->getItem();

		if($group->volunteers_group_id)
		{
			$text = $group->title;
		}
		else
		{
			$text = '';
		}

		return $text;
	}

	public static function date($date, $format)
	{
		if($date == '0000-00-00')
		{
			$date = '';
		}

		if($date !== '0000-00-00')
		{
			$date = new JDate($date);
			$date = $date->format($format);
		}

		return $date;
	}


	public static function location($city, $country)
	{
		$countries = VolunteersHelperSelect::$countries;

		if($city)
		{
			$text = $city;
		}

		if($country)
		{
			$text = $countries[$country];
		}

		if($city && $country)
		{
			$text = $city.', '.$countries[$country];
		}

		return $text;
	}

	public static function image($image, $size)
	{
		// No image, size small
		if(empty($image) && ($size == 'small'))
		{
			$html = '<img class="img-rounded" src="images/joomla_50x50.png"/>';
		}

		// No image, size large
		if(empty($image) && ($size == 'large'))
		{
			$html = '<img class="img-rounded" src="images/joomla.png"/>';
		}

		if($image)
		{
			$image_filename		= pathinfo($image, PATHINFO_FILENAME);
			$image_extension	= pathinfo($image, PATHINFO_EXTENSION);
		}

		if($image && ($size == 'small'))
		{
			$image_path = 'images/volunteers/thumbs/'.$image_filename.'_50x50.'.$image_extension;
			$html = '<img class="img-rounded" src="'.$image_path.'"/>';
		}

		if($image && ($size == 'large'))
		{
			$image_path = 'images/volunteers/thumbs/'.$image_filename.'_250x250.'.$image_extension;
			$html = '<img class="img-rounded" src="'.$image_path.'"/>';
		}

		return $html;
	}
}