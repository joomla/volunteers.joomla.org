<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JLoader::register('JHtmlVolunteers', JPATH_ADMINISTRATOR . '/components/com_volunteers/helpers/html/volunteers.php');

abstract class VolunteersHelper
{
	public static function location($country = null, $city = null)
	{
		$countries = JHtmlVolunteers::$countries;

		$text = '';

		if ($city)
		{
			$text .= $city;
		}

		if ($city && $country)
		{
			$text .= ', ';
		}

		if ($country)
		{
			$text .= $countries[$country];
		}

		return $text;
	}

	public static function image($image, $size, $urlonly = false, $alt = '')
	{
		// No image, size small
		if (empty($image) && ($size == 'small'))
		{
			$image_path = 'images/joomla_50x50.png';
		}

		// No image, size large
		if (empty($image) && ($size == 'large'))
		{
			$image_path = 'images/joomla.png';
		}

		if ($image)
		{
			$image_filename  = pathinfo($image, PATHINFO_FILENAME);
			$image_extension = pathinfo($image, PATHINFO_EXTENSION);
		}

		if ($image && ($size == 'small'))
		{
			$image_path = 'images/volunteers/thumbs/' . $image_filename . '_50x50.' . $image_extension;
		}

		if ($image && ($size == 'large'))
		{
			$image_path = 'images/volunteers/' . $image_filename . '.' . $image_extension;
		}

		if ($urlonly)
		{
			$html = JUri::base() . $image_path;
		}
		else
		{
			$html = '<img class="img-rounded" alt="' . $alt . '" src="' . $image_path . '"/>';
		}

		return $html;
	}

	public static function date($date, $format)
	{
		if ($date == '0000-00-00')
		{
			$date = '';
		}

		if ($date !== '0000-00-00')
		{
			$date = new JDate($date);
			$date = $date->format($format);
		}

		return $date;
	}

	public static function acl($type, $id)
	{
		// Base ACL
		$acl                  = new stdClass;
		$acl->edit_department = false;
		$acl->edit            = false;
		$acl->create_report   = false;
		$acl->create_team     = false;

		// Set ID
		$departmentId = ($type == 'department') ? $id : null;
		$teamId       = ($type == 'team') ? $id : null;

		// Get User ID
		$user = JFactory::getUser();

		// Guest
		if ($user->guest)
		{
			return $acl;
		}

		// Admin
		if ($user->authorise('code.admin', 'com_volunteers'))
		{
			$acl->edit_department = true;
			$acl->edit            = true;
			$acl->create_report   = true;
			$acl->create_team     = true;

			return $acl;
		}

		// Get Volunteer ID
		$volunteerId = (int) JModelLegacy::getInstance('Volunteer', 'VolunteersModel', array('ignore_request' => true))->getVolunteerId($user->id);

		// Get Department ID
		if ($type == 'team')
		{
			$team         = JModelLegacy::getInstance('Team', 'VolunteersModel', array('ignore_request' => true))->getItem($id);
			$departmentId = (int) $team->department;
			$parentTeamId = (int) $team->parent_id;
		}

		// Check for department involvement
		$positionId = (int) JModelLegacy::getInstance('Member', 'VolunteersModel', array('ignore_request' => true))->getPosition($volunteerId, $departmentId, $teamId);

		// Get ACL for position
		$positionDepartment = JModelLegacy::getInstance('Position', 'VolunteersModel', array('ignore_request' => true))->getItem($positionId);

		foreach ($acl as $action => $value)
		{
			if ($positionDepartment->{$action})
			{
				$acl->{$action} = true;
			}
		}

		// Check for parent team involvement
		if ($type == 'team' && $parentTeamId)
		{
			$positionId = (int) JModelLegacy::getInstance('Member', 'VolunteersModel', array('ignore_request' => true))->getPosition($volunteerId, null, $parentTeamId);

			// Get ACL for position
			$positionTeamParent = JModelLegacy::getInstance('Position', 'VolunteersModel', array('ignore_request' => true))->getItem($positionId);

			foreach ($acl as $action => $value)
			{
				if ($positionTeamParent->{$action})
				{
					$acl->{$action} = true;
				}
			}
		}

		// Check for team involvement
		if ($type == 'team')
		{
			$positionId = (int) JModelLegacy::getInstance('Member', 'VolunteersModel', array('ignore_request' => true))->getPosition($volunteerId, null, $teamId);

			// Get ACL for position
			$positionTeam = JModelLegacy::getInstance('Position', 'VolunteersModel', array('ignore_request' => true))->getItem($positionId);

			foreach ($acl as $action => $value)
			{
				if ($positionTeam->{$action})
				{
					$acl->{$action} = true;
				}
			}
		}

		return $acl;
	}
}