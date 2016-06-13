<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

$this->loadHelper('select');

class VolunteersHelperFormat
{
	public static function search($title)
	{
		$html[] = '<div class="input-append search">';
		$html[] = '<input type="text" name="title" class="span searchinput" value="'.$title.'" class="text_area" onchange="document.adminForm.submit();" />';
		$html[] = '<button class="btn add-on" type="button" onclick="this.form.submit();"><i class="icon-search"></i></button>';
		$html[] = '<button class="btn add-on" type="button" onclick="document.adminForm.title.value=\'\';this.form.submit();"><i class="icon-remove"></i></button>';
		$html[] = '</div>';

		return implode(' ',$html);
	}

	public static function enabled($enabled)
	{
		$html[] = '<input type="hidden" name="enabled" value="'.$enabled.'"/>';
		$html[] = '<div class="btn-group status">';
		if($enabled == true) {
			$html[] = '<button class="btn btn-micro btn-success" type="button" onclick="document.adminForm.enabled.value=\'\';this.form.submit();"><i class="icon-publish icon-white"></i></button>';
			$html[] = '<button class="btn btn-micro" type="button" onclick="document.adminForm.enabled.value=\'0\';this.form.submit();"><i class="icon-unpublish"></i></button>';
		} elseif($enabled == null) {
			$html[] = '<button class="btn btn-micro" type="button" onclick="document.adminForm.enabled.value=\'1\';this.form.submit();"><i class="icon-publish"></i></button>';
			$html[] = '<button class="btn btn-micro" type="button" onclick="document.adminForm.enabled.value=\'0\';this.form.submit();"><i class="icon-unpublish"></i></button>';
		} elseif($enabled == false) {
			$html[] = '<button class="btn btn-micro" type="button" onclick="document.adminForm.enabled.value=\'1\';this.form.submit();"><i class="icon-publish"></i></button>';
			$html[] = '<button class="btn btn-micro btn-danger" type="button" onclick="document.adminForm.enabled.value=\'\';this.form.submit();"><i class="icon-unpublish icon-white"></i></button>';
		}
		$html[] = '</div>';

		return implode(' ',$html);
	}

	public static function status($status_id)
	{
		$status = JComponentHelper::getParams('com_volunteers')->get('statusoptions');
		$status = explode("\n", $status);
		foreach($status as $state) {
			$list[] = explode("=", $state);
		}

		foreach($list as $item) {
			if($status_id == $item[0]) {
				$status = $item;
			}
		}

		return $status;
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

	public static function groupmember($group, $type)
	{
		if(JFactory::getUser()->id == 0)
		{
			return false;
		}

		$volunteer = FOFModel::getTmpInstance('Volunteers', 'VolunteersModel')
			->user_id(JFactory::getUser()->id)
			->getFirstItem();

		if($type == 'member')
		{
			$groupmember = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
				->volunteer($volunteer->volunteers_volunteer_id)
				->group($group)
				->getFirstItem();
		}

		if($type == 'lead')
		{
			$groupmember = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
				->volunteer($volunteer->volunteers_volunteer_id)
				->group($group)
				->role(2,3,4,5)
				->getFirstItem();
		}

		if($groupmember->volunteers_groupmember_id)
		{
			return true;
		}
		else
		{
			return false;
		}
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