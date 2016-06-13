<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersTableVolunteer extends FOFTable
{
	public function check()
	{
	    $slug	= $this->getColumnAlias('slug');

	    $this->setColumnAlias('title', 'lastname'); // this is needed to trigger the unicity check

	    if (!$this->$slug)
	    {
	        $this->$slug = !$this->firstname ? $this->lastname : $this->firstname .' '. $this->lastname;
	        $this->$slug = FOFStringUtils::toSlug($this->$slug);
	    }
	    return parent::check();
	}
}