<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\View\Certificates;

use Exception;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;
use Rolandd\Component\Sso\Administrator\Model\CertificatesModel;

defined('_JEXEC') or die;

/**
 * Clients view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Array with certificates
     *
     * @var    array
     * @since  1.0.0
     */
    protected array $items;

    /**
     * Access rights of a user
     *
     * @var    Registry
     * @since  1.1.0
     */
    protected Registry $canDo;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @throws  Exception
     *
     * @since   1.0.0
     */
    public function display($tpl = null)
    {
        /** @var CertificatesModel $model */
        $model       = $this->getModel();
        $this->items = $model->getItems();
        $this->canDo = ContentHelper::getActions('com_sso');

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Displays a toolbar for a specific page.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_SSO_CERTIFICATES'), 'file-2');
        $toolbar = Toolbar::getInstance();

        if ($this->canDo->get('core.create')) {
            $toolbar->addNew('certificate.add');
        }

        if ($this->canDo->get('core.edit') || $this->canDo->get('core.edit.own'))
        {
            /** @var DropdownButton $dropdown */
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            $childBar->delete('certificates.delete', 'JTOOLBAR_DELETE')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }
    }
}
