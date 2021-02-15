<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

defined('_JEXEC') or die;

?>
<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="span10">
	<?php
	echo JHtmlBootstrap::startTabSet('sso', array('active' => 'systembasic'));

	echo JHtmlBootstrap::addTab('sso', 'systembasic', Text::_('COM_SSO_TAB_SYSTEMBASIC'));
		echo $this->loadTemplate('systembasic');
	echo JHtmlBootstrap::endTab();

	echo JHtmlBootstrap::addTab('sso', 'serviceprovider', Text::_('COM_SSO_TAB_SERVICEPROVIDER'));
		echo $this->loadTemplate('serviceprovider');
	echo JHtmlBootstrap::endTab();

	echo JHtmlBootstrap::addTab('sso', 'identityprovider', Text::_('COM_SSO_TAB_IDENTITYPROVIDER'));
		echo $this->loadTemplate('identityprovider');
	echo JHtmlBootstrap::endTab();

	echo JHtmlBootstrap::addTab('sso', 'tipsinformation', Text::_('COM_SSO_TAB_TIPSINFORMATION'));
		echo $this->loadTemplate('tipsinformation');
	echo JHtmlBootstrap::endTab();

	echo JHtmlBootstrap::endTabSet();
	?>
</div>
