<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\View\Volunteers;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\ListView;
use Joomla\CMS\Toolbar\ToolbarButton;

/**
 * View class for a list of Joomlers.
 *
 * @since  4.0.0
 */
class HtmlView extends ListView
{
    /**
     * The view supports archiving items
     *
     * @var    boolean
     * @since  6.1.0
     */
    protected $supportsArchive = true;

    /**
     * Constructor
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   4.0.0
     */
    public function __construct(array $config)
    {
        if (empty($config['option'])) {
            $config['option'] = 'com_volunteers';
        }

        $config['toolbar_title']  = Text::_('COM_VOLUNTEERS') . ': ' . Text::_('COM_VOLUNTEERS_TITLE_VOLUNTEERS');
        $config['toolbar_icon']   = 'joomla';
        $config['supports_batch'] = false;

        parent::__construct($config);
    }

    /**
     * Prepare view data
     *
     * @return  void
     *
     * @since   6.1.0
     */
    protected function initializeView()
    {
        parent::initializeView();

        $this->canDo = ContentHelper::getActions('com_volunteers');
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   6.1.0
     */
    protected function addToolbar()
    {
        parent::addToolbar();

        if ($this->canDo->get('core.edit')) {
            $toolbar = $this->getDocument()->getToolbar();

            // Take the options button off, so the reset button shows before it
            $items   = $toolbar->getItems();
            $options = array_filter($items, static fn ($button) => $button instanceof ToolbarButton && $button->getName() === 'options');
            $toolbar->setItems(array_diff_key($items, $options));

            $toolbar->standardButton('refresh', 'COM_VOLUNTEERS_TOOLBAR_RESET', 'volunteers.resetspam')
                ->listCheck(false);

            $toolbar->setItems(array_merge($toolbar->getItems(), array_values($options)));
        }
    }
}
