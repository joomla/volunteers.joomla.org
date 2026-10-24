<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Backup;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Helper\Status;
use Akeeba\Component\AkeebaBackup\Administrator\Helper\Utils;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileIdAndNameTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileListTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ControlpanelModel;
use Akeeba\Engine\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewProfileListTrait;
	use ViewProfileIdAndNameTrait;
	use ViewLoadAnyTemplateTrait;
	use ViewTaskBasedEventsTrait;

	/**
	 * Do we have errors preventing the backup from starting?
	 *
	 * @var  bool
	 */
	public $hasErrors = false;

	/**
	 * Do we have warnings which may affect –but do not prevent– the backup from running?
	 *
	 * @var  bool
	 */
	public $hasWarnings = false;

	/**
	 * The HTML of the warnings cell
	 *
	 * @var  string
	 */
	public $warningsCell = '';

	/**
	 * Backup description
	 *
	 * @var  string
	 */
	public $description = '';

	/**
	 * Default backup description
	 *
	 * @var  string
	 */
	public $defaultDescription = '';

	/**
	 * Backup comment
	 *
	 * @var  string
	 */
	public $comment = '';

	/**
	 * JSON string of the backup domain name to titles associative array
	 *
	 * @var  array
	 */
	public $domains = '';

	/**
	 * Maximum execution time in seconds
	 *
	 * @var  int
	 */
	public $maxExecutionTime = 10;

	/**
	 * Execution time bias, in percentage points (0-100)
	 *
	 * @var  int
	 */
	public $runtimeBias = 75;

	/**
	 * URL to return to after the backup is complete
	 *
	 * @var  string
	 */
	public $returnURL = '';

	/**
	 * Is the output directory unwritable?
	 *
	 * @var  bool
	 */
	public $unwriteableOutput = false;

	/**
	 * has the user configured an ANGIE password?
	 *
	 * @var  string
	 */
	public $hasANGIEPassword = '';

	/**
	 * Should I autostart the backup?
	 *
	 * @var  string
	 */
	public $autoStart = false;

	/**
	 * Should I display desktop notifications? 0/1
	 *
	 * @var  int
	 */
	public $desktopNotifications = 0;

	/**
	 * Should I try to automatically resume the backup in case of an error? 0/1
	 *
	 * @var  int
	 */
	public $autoResume = 0;

	/**
	 * After how many seconds should I try to automatically resume the backup?
	 *
	 * @var  int
	 */
	public $autoResumeTimeout = 10;

	/**
	 * How many times in total should I try to automatically resume the backup?
	 *
	 * @var  int
	 */
	public $autoResumeRetries = 3;

	/**
	 * Should I prompt the user to run the Configuration Wizard?
	 *
	 * @var  bool
	 */
	public $promptForConfigurationwizard = false;

	/**
	 * Runs before displaying the backup page
	 */
	public function onBeforeMain()
	{
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_BACKUP'), 'icon-akeeba');

		// Load the view-specific Javascript
		$this->getDocument()->getWebAssetManager()
			->useScript('com_akeebabackup.backup')
			->usePreset('choicesjs')
			->useScript('webcomponent.field-fancy-select');

		// Load the models
		/** @var ControlpanelModel $cpanelmodel */
		$cpanelmodel = $this->getModel('controlpanel');
		$model = $this->getModel();

		// Load the Status Helper
		$helper = Status::getInstance();

		// Determine default description
		$default_description = $this->getDefaultDescription();

		// Load data from the model state
		$backup_description = $model->getState('description', $default_description);
		$comment            = $model->getState('comment', '');
		$returnurl          = Utils::safeDecodeReturnUrl($model->getState('returnurl', ''));

		// Get the maximum execution time and bias
		$engineConfiguration = Factory::getConfiguration();
		$maxexec             = $engineConfiguration->get('akeeba.tuning.max_exec_time', 14) * 1000;
		$bias                = $engineConfiguration->get('akeeba.tuning.run_time_bias', 75);

		// Check if the output directory is writable
		$warnings         = Factory::getConfigurationChecks()->getDetailedStatus();
		$unwritableOutput = array_key_exists('001', $warnings);

		// Get the component parameters
		$params = ComponentHelper::getParams('com_akeebabackup');

		// Pass on data
		$this->getProfileList();
		$this->getProfileIdAndName();

		$this->hasErrors                    = !$helper->status;
		$this->hasWarnings                  = $helper->hasQuirks();
		$this->warningsCell                 = $helper->getQuirksCell(!$helper->status);
		$this->description                  = $backup_description;
		$this->defaultDescription           = $default_description;
		$this->comment                      = $comment;
		$this->domains                      = $this->getDomains();
		$this->maxExecutionTime             = $maxexec;
		$this->runtimeBias                  = $bias;
		$this->returnURL                    = $returnurl;
		$this->unwriteableOutput            = $unwritableOutput;
		$this->autoStart                    = (bool) $model->getState('autostart', 0);
		$this->desktopNotifications         = $params->get('desktop_notifications', '0') ? 1 : 0;
		$this->autoResume                   = $engineConfiguration->get('akeeba.advanced.autoresume', 1);
		$this->autoResumeTimeout            = $engineConfiguration->get('akeeba.advanced.autoresume_timeout', 10);
		$this->autoResumeRetries            = $engineConfiguration->get('akeeba.advanced.autoresume_maxretries', 3);
		$this->promptForConfigurationwizard = $engineConfiguration->get('akeeba.flag.confwiz', 0) == 0;
		$this->hasANGIEPassword = !empty(trim($engineConfiguration->get('engine.installer.angie.key', '')));
	}

	/**
	 * Get the default description for this backup attempt
	 *
	 * @return  string
	 */
	private function getDefaultDescription()
	{
		return $this->getModel()->getDefaultDescription();
	}

	/**
	 * Get a list of backup domain keys and titles
	 *
	 * @return  array
	 */
	private function getDomains()
	{
		$engineConfiguration = Factory::getConfiguration();
		$script              = $engineConfiguration->get('akeeba.basic.backup_type', 'full');
		$scripting           = Factory::getEngineParamsProvider()->loadScripting();
		$domains             = [];

		if (empty($scripting))
		{
			return $domains;
		}

		foreach ($scripting['scripts'][$script]['chain'] as $domain)
		{
			$description = Text::_($scripting['domains'][$domain]['text']);
			$domain_key  = $scripting['domains'][$domain]['domain'];
			$domains[]   = [$domain_key, $description];
		}

		// Backup steps use COM_AKEEBA_* lang constants for compatibility reasons. We need to change them.
		$domains = array_map(function($domain){
			$domain[1] = Text::_(str_replace('COM_AKEEBA_', 'COM_AKEEBABACKUP_', $domain[1]));

			return $domain;
		}, $domains);

		return $domains;
	}
}