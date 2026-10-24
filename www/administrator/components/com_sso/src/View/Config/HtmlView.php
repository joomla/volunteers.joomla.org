<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\View\Config;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\View\FormView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die;

/**
 * SSO view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class HtmlView extends FormView
{
    /**
     * Constructor
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   9.0.0
     */
    public function __construct(array $config)
    {
        if (empty($config['option'])) {
            $config['option'] = 'com_sso';
        }

        $config['toolbar_icon'] = 'equalizer';

        parent::__construct($config);
    }

    /**
     * Prepare view data
     *
     * @return  void
     *
     * @since   9.0.0
     */
    protected function initializeView()
    {
        /** @var AdminModel */
        $model       = $this->getModel();
        $this->form  = $model->getForm();

        $this->form
            ->addControlField('task');
    }

	/**
	 * Displays a toolbar for a specific page.
	 *
	 * @return  void.
	 *
	 * @since   1.0.0
	 */
	protected function addToolbar(): void
	{
        /**
         * @var Toolbar $toolbar
         */
        $toolbar = $this->getDocument()->getToolbar();

		ToolBarHelper::title(Text::_('COM_SSO_CONFIG'), 'equalizer');
		$toolbar->apply('config.save');

        if ($this->form instanceof Form) {
            $formConfig  = $this->form->getXml()->config->inlinehelp;

            if ($formConfig && (string) $formConfig['button'] === 'show') {
                $targetClass = (string) $formConfig['targetclass'] ?: 'hide-aware-inline-help';

                $toolbar->inlinehelp($targetClass);
            }
        }
	}
}
