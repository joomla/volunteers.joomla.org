<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

include_once JPATH_LIBRARIES.'/fof/include.php';
require_once JPATH_SITE.'/components/com_volunteers/helpers/router.php';

/**
 * Which views does this router handle?
 */
global $volunteersHandleViews;
$volunteersHandleViews = array('groups', 'group', 'volunteers', 'volunteer', 'reports', 'report');

function VolunteersBuildRoute(&$query)
{
	global $volunteersHandleViews;
	$segments = array();

	// We need to find out if the menu item link has a view param
	$menuQuery = array();
	$menuView = 'categories';
	$Itemid = VolunteersHelperRouter::getAndPop($query, 'Itemid', 0);

	// Get the menu view, if an Item ID exists
	if($Itemid) {
		$menu = JFactory::getApplication()->getMenu()->getItem($Itemid);
		if(is_object($menu))
		{
			parse_str(str_replace('index.php?',  '',$menu->link), $menuQuery); // remove "index.php?" and parse
			if(array_key_exists('view', $menuQuery))
			{
				$menuView = $menuQuery['view'];
			}
		}

		$query['Itemid'] = $Itemid;
	}

	// Add the view
	$newView = array_key_exists('view', $query) ? $query['view'] : $menuView;

	// We can only handle specific views. Is it one of them?
	if(!in_array($newView, $volunteersHandleViews))
	{
		if($Itemid) $query['Itemid'] = $Itemid;
		return array();
	}

	// Remove the option and view from the query
	VolunteersHelperRouter::getAndPop($query, 'view');

	// @todo Build the URL
	switch($newView)
	{
		case 'group':
			$id = VolunteersHelperRouter::getAndPop($query, 'id', 0);
			if($id)
			{
				$item = FOFModel::getTmpInstance('Groups', 'VolunteersModel')
					->setId($id)
					->getItem();
				// Append the group slug
				$segments[] = $item->slug;
			}

			$found = false;
			$menu = JFactory::getApplication()->getMenu()->getItem($Itemid);
			$qoptions = array(
				'option'	=> 'com_volunteers',
				'view'		=> 'groups',
			);

			$found = VolunteersHelperRouter::checkMenu($menu, $qoptions);
			if(!$found)
			{
				$item = VolunteersHelperRouter::findMenu($qoptions);

				if(!is_null($item)) {
					$Itemid = $item->id;
					$found = true;
				}
			}

			break;

		case 'volunteer':
			$id = VolunteersHelperRouter::getAndPop($query, 'id', 0);
			if($id)
			{
				$item = FOFModel::getTmpInstance('Volunteers', 'VolunteersModel')
					->setId($id)
					->getItem();
				// Append the group slug
				$segments[] = $item->slug;
			}

			$found = false;
			$menu = JFactory::getApplication()->getMenu()->getItem($Itemid);
			$qoptions = array(
				'option'	=> 'com_volunteers',
				'view'		=> 'volunteers',
			);

			$found = VolunteersHelperRouter::checkMenu($menu, $qoptions);
			if(!$found)
			{
				$item = VolunteersHelperRouter::findMenu($qoptions);

				if(!is_null($item))
				{
					$Itemid = $item->id;
					$found = true;
				}
			}

			break;

		case 'report':
			$id = VolunteersHelperRouter::getAndPop($query, 'id', 0);
			if($id)
			{
				$item = FOFModel::getTmpInstance('Reports', 'VolunteersModel')
					->setId($id)
					->getItem();
				// Append the group slug
				$segments[] = $id . ':' . $item->slug;
			}

			$found = false;
			$menu = JFactory::getApplication()->getMenu()->getItem($Itemid);
			$qoptions = array(
				'option'	=> 'com_volunteers',
				'view'		=> 'reports',
			);

			$found = VolunteersHelperRouter::checkMenu($menu, $qoptions);
			if(!$found)
			{
				$item = VolunteersHelperRouter::findMenu($qoptions);

				if(!is_null($item))
				{
					$Itemid = $item->id;
					$found = true;
				}
			}

			break;
/*

		case 'reports':
			$found = false;
			$menu = JFactory::getApplication()->getMenu()->getItem($Itemid);
			$qoptions = array(
				'option'	=> 'com_volunteers',
				'view'		=> $newView,
			);
			$found = VolunteersHelperRouter::checkMenu($menu, $qoptions);
			if(!$found)
			{
				$item = VolunteersHelperRouter::findMenu($qoptions);

				if(!is_null($item))
				{
					$Itemid = $item->id;
					$found = true;
				}
			}

			break;
*/
	}

	// Process the Itemid
	$menuView = null;
	if($Itemid)
	{
		$menu = JFactory::getApplication()->getMenu()->getItem($Itemid);
		if(is_object($menu))
		{
			parse_str(str_replace('index.php?',  '',$menu->link), $menuQuery); // remove "index.php?" and parse
			if(array_key_exists('view', $menuQuery))
			{
				$menuView = $menuQuery['view'];
			}
		}

		$query['Itemid'] = $Itemid;
	}

	// If the menu's view is different to the new view, add the view name to the URL
	if(!empty($newView) && ($newView != $menuView))
	{
		if((($menuView != 'groups') && ($menuView != 'volunteers') && ($menuView != 'reports')) || empty($menuView) )
		{
			array_unshift($segments, $newView);
		}
		elseif(!in_array($newView, array('groups', 'group', 'volunteers', 'volunteer', 'reports', 'report')))
		{
			array_unshift($segments, $newView);
		}
	}

	return $segments;
}

function VolunteersParseRoute(&$segments)
{
	$query = array();

	global $volunteersHandleViews;

	// Fetch the default query from the active menu item
	$mObject = JFactory::getApplication()->getMenu()->getActive();
	$query = is_object($mObject) ? $mObject->query : array();

	if(!array_key_exists('option', $query)) $query['option'] = 'com_volunteers';
	if(!array_key_exists('view', $query)) $query['view'] = 'volunteers';
	$view = $query['view'];

	// Replace : with - in segments
	$segments = VolunteersHelperRouter::preconditionSegments($segments);

	// Do not process an empty segment list (just in case...)
	if(empty($segments)) return $query;

	// Do not process a view I know jack shit about
	if(!in_array($view, $volunteersHandleViews)) return $query;

	if(in_array($view, $volunteersHandleViews))
	{

		switch($view)
		{
			case 'groups':
				$query['view'] = 'group';

				$db = JFactory::getDBO();
				$dbquery = $db->getQuery(true)
					->select('volunteers_group_id')
					->from($db->qn('#__volunteers_groups'))
					->where($db->qn('slug').' = '.$db->q($segments[0]));
				$db->setQuery($dbquery);
				$id = $db->loadResult();
				$query['id'] = $id;
				break;

			case 'volunteers':
				$query['view'] = 'volunteer';

				$db = JFactory::getDBO();
				$dbquery = $db->getQuery(true)
					->select('volunteers_volunteer_id')
					->from($db->qn('#__volunteers_volunteers'))
					->where($db->qn('slug').' = '.$db->q($segments[0]));
				$db->setQuery($dbquery);
				$id = $db->loadResult();
				$query['id'] = $id;
				break;

			case 'reports':
				$query['view'] = 'report';

				$lastSegment = array_pop($segments);
				$sParts = explode('-', $lastSegment);
				$query['id'] = (int)$sParts[0];
				break;
		}
	}

	return $query;
}