<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Component\Volunteers\Administrator\View\Roles\HtmlView;

/** @var HtmlView $this */

$displayData = [
    'textPrefix' => 'COM_VOLUNTEERS_ROLES',
    'formURL'    => 'index.php?option=com_volunteers&view=roles',
    'icon'       => 'icon-joomla',

    'controlFields' => $this->filterForm->renderControlFields(),
];

if ($this->getCurrentUser()->authorise('core.create', 'com_volunteers')) {
    $displayData['createURL'] = 'index.php?option=com_volunteers&task=role.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
