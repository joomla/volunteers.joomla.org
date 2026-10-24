<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\View\Sso;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Rolandd\Component\Sso\Administrator\Model\SsoModel;
use Rolandd\Component\Sso\Administrator\Sso\Config;

/**
 * SSO view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * List of Identity Providers
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected array $identityProviders = [];

	/**
	 * The SimpleSAMLphp version
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected string $samlVersion = '';

	/**
	 * The SimpleSAMLphp configuration
	 *
	 * @var    Config
	 * @since  1.0.0
	 */
	protected Config $config;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @see     fetch()
	 *
	 * @since   1.0.0
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		/** @var SsoModel $model */
		$model                   = $this->getModel();
		$this->identityProviders = $model->getIdentityProviderAliases();
		$this->samlVersion       = $model->getSamlVersion();
		$this->config            = new Config;

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
		ToolBarHelper::title(Text::_('COM_SSO'), 'signup');

        $toolbar    = $this->getDocument()->getToolbar();
        $toolbar->standardButton('verifyDatabase', Text::_('COM_SSO_TOOLBAR_DATABASE_VERIFY'), 'sso.verifyDatabase')
            ->icon('icon-refresh');

		if ($this->getCurrentUser()->authorise('core.admin', 'com_sso'))
		{
			ToolBarHelper::preferences('com_sso');
		}
	}
}
