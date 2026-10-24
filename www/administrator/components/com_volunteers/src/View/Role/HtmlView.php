<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\View\Role;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\FormView;

/**
 * View to edit a team role.
 *
 * @since  4.0.0
 */
class HtmlView extends FormView
{
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

        $config['toolbar_icon'] = 'joomla';

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

        $this->canDo        = ContentHelper::getActions('com_volunteers');
        $this->toolbarTitle = Text::_('COM_VOLUNTEERS') . ': ' . Text::_('COM_VOLUNTEERS_TITLE_ROLES_' . (empty($this->item->id) ? 'NEW' : 'EDIT'));

        $this->form->addControlField('task');
    }
}
