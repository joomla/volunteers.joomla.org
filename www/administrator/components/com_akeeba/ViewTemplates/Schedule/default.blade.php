<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

use Joomla\CMS\Language\Text as JText;

?>
@jhtml('bootstrap.startTabSet', 'akeebabackup-scheduling', ['active' => 'akeebabackup-scheduling-backups'])
@jhtml('bootstrap.addTab', 'akeebabackup-scheduling', 'akeebabackup-scheduling-backups', JText::_('COM_AKEEBA_SCHEDULE_LBL_RUN_BACKUPS', true))
@include('admin:com_akeeba/Schedule/backup')
@jhtml('bootstrap.endTab')
@jhtml('bootstrap.addTab', 'akeebabackup-scheduling', 'akeebabackup-scheduling-checkbackups', JText::_('COM_AKEEBA_SCHEDULE_LBL_CHECK_BACKUPS', true))
@include('admin:com_akeeba/Schedule/check')
@jhtml('bootstrap.endTab')
@jhtml('bootstrap.endTabSet')