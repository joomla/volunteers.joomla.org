<?php
/**
 * @package     CSVI
 * @subpackage  JoomlaCategories
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2006 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Rolandd\Plugin\Console\Sso\Extension;

\defined('_JEXEC') or die;

use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Rolandd\Plugin\Console\Sso\Console\LogrotateCommand;

class Sso extends CMSPlugin implements SubscriberInterface
{
    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::BEFORE_EXECUTE => 'registerCLICommands',
        ];
    }

    /**
     * Register the commands that can be executed via the CLI.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function registerCLICommands(): void
    {
        $commandObject = new LogrotateCommand();
        $commandObject->setParams($this->params);
        $this->getApplication()->addCommand($commandObject);
    }
}
