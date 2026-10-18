<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2005 - 2024 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\VolunteersLatest\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\Module\VolunteersLatest\Site\Helper\VolunteersLatestHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        $app = $this->getApplication();
        $app->getDocument()->getWebAssetManager()->useStyle('com_volunteers.frontend');

        $data['volunteers'] = $this->getHelperFactory()
            ->getHelper('VolunteersLatestHelper')
            ->getVolunteers($data['params'], $app);

        return $data;
    }
}
