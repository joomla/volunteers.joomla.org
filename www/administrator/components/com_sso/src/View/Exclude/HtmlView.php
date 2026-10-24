<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\View\Exclude;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;
use Rolandd\Component\Sso\Administrator\Model\ExcludeModel;
use stdClass;

defined('_JEXEC') or die;

/**
 * Exclude view.
 *
 * @package  SSO.Component
 * @since    1.5.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Form with settings
	 *
	 * @var    Form
	 * @since  1.5.0
	 */
	protected Form $form;

	/**
	 * The item object
	 *
	 * @var    stdClass
	 * @since  1.5.0
	 */
	protected stdClass $item;

	/**
	 * Access rights of a user
	 *
	 * @var    Registry
	 * @since  1.5.0
	 */
	protected Registry $canDo;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 *
	 * @throws  Exception
	 */
	public function display($tpl = null)
	{
		/** @var ExcludeModel $model */
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->canDo = ContentHelper::getActions('com_sso');

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Displays a toolbar for a specific page.
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 *
	 * @throws  Exception
	 */
	private function addToolbar(): void
	{
		Factory::getApplication()->input->set('hidemainmenu', true);
        $user       = $this->getCurrentUser();
        $userId     = $user->id;
        $checkedOut = !(\is_null($this->item->checked_out) || $this->item->checked_out == $userId);
        $toolbar    = $this->getDocument()->getToolbar();
        $canDo      = $this->canDo;

		ToolbarHelper::title(Text::_('COM_SSO_EDIT_EXCLUDE'), 'undo-2');

        $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

        if (!$checkedOut && $itemEditable) {
            $toolbar->apply('exclude.apply');
        }

        $saveGroup = $toolbar->dropdownButton('save-group');

        $saveGroup->configure(
            function (Toolbar $childBar) use ($checkedOut, $itemEditable, $canDo, $user) {
                // Can't save the record if it's checked out and editable
                if (!$checkedOut && $itemEditable) {
                    $childBar->save('exclude.save');

                    // We can save this record, but check the create permission to see if we can return to make a new one.
                    if ($canDo->get('core.create')) {
                        $childBar->save2new('exclude.save2new');
                    }
                }

                // If checked out, we can still save
                if ($canDo->get('core.create')) {
                    $childBar->save2copy('exclude.save2copy');
                }
            }
        );

        $toolbar->cancel('exclude.cancel', 'JTOOLBAR_CLOSE');

        $toolbar->divider();
        $toolbar->inlinehelp();
	}
}
