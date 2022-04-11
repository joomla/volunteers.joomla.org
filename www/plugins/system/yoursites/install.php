<?php

/**
 * @version    CVS: 1.27.0
 * @package    com_yoursites
 * @author     Geraint Edwards
 * @copyright  2017-2020 GWE Systems Ltd
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class plgsystemyoursitesInstallerScript
{
	public function preflight($type, $parent)
	{

	}

	// TODO enable plugins
	public function update()
	{

		return true;
	}

	public function install($adapter)
	{
		return true;
	}

	public function uninstall($adapter)
	{

	}

	/*
	 * enable the plugins
	 */
	function postflight($type, $parent)
	{

		$db = JFactory::getDbo();
		$charset = ($db->hasUTFSupport()) ? 'DEFAULT CHARACTER SET ' . $db->quoteName('utf8') : '';

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__ysts_tokens(
	tokenvalue varchar(225) NOT NULL default '',
	expires datetime NOT NULL default '2018-01-01 00:00:00',
	PRIMARY KEY (tokenvalue),
	index expires (expires)
)   ENGINE=InnoDB $charset;
SQL;
		$db->setQuery($sql);
		$db->execute();

		$sql = "SHOW COLUMNS FROM #__ysts_tokens";
		$db->setQuery($sql);
		$cols = @$db->loadObjectList("Field");
		if (!array_key_exists("expires", $cols))
		{
			$sql = "alter table #__ysts_tokens ADD COLUMN expires datetime NOT NULL default '2018-01-01 00:00:00'";
			$db->setQuery($sql);
			@$db->execute();
		}


	}

}
