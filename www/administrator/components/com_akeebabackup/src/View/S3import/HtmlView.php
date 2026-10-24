<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\S3import;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\S3importModel;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Session\SessionInterface;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewTaskBasedEventsTrait;
	use ViewLoadAnyTemplateTrait;
	use ViewToolbarTrait;

	public $s3access;

	public $s3secret;

	public $buckets;

	public $bucketSelect;

	public $contents;

	public $root;

	public $crumbs;

	public $total;

	public $done;

	public $percent;

	public $total_parts;

	public $current_part;

	public function onBeforeMain()
	{
		$this->addToolbar();

		$this->getDocument()->getWebAssetManager()
			->useScript('com_akeebabackup.s3import');

		/** @var S3importModel $model */
		$model = $this->getModel();

		// Assign variables
		$this->s3access     = $model->getState('s3access');
		$this->s3secret     = $model->getState('s3secret');
		$this->buckets      = $model->getBuckets();
		$this->bucketSelect = $this->getBucketsDropdown();
		$this->contents     = $model->getContents();
		$this->root         = $model->getState('folder');
		$this->crumbs       = $model->getCrumbs();

		// Script options
		$this->getDocument()
			->addScriptOptions('akeebabackup.S3import.accessKey', $this->s3access)
			->addScriptOptions('akeebabackup.S3import.secretKey', $this->s3secret)
			->addScriptOptions('akeebabackup.S3import.importURL', Route::_(
				sprintf(
					"index.php?option=com_akeebabackup&view=S3import&task=dltoserver&part=-1&frag=-1&layout=downloading&%s=1",
					Factory::getApplication()->getFormToken()
				), false, Route::TLS_IGNORE, true));
	}

	public function onBeforeDltoserver()
	{
		$this->addToolbar();

		$this->getDocument()->getWebAssetManager()
			->useScript('com_akeebabackup.s3import');

		$this->setLayout('downloading');

		/** @var S3importModel $model */
		$model = $this->getModel();

		/** @var SessionInterface $session */
		$session = Factory::getApplication()->getSession();

		$total = $session->get('com_akeebabackup.s3import.totalsize', 0);
		$done  = $session->get('com_akeebabackup.s3import.donesize', 0);
		$part  = $session->get('com_akeebabackup.s3import.part', 0) + 1;
		$parts = $session->get('com_akeebabackup.s3import.totalparts', 0);

		$percent = 0;

		if ($total > 0)
		{
			$percent = (int) (100 * ($done / $total));
			$percent = max(0, $percent);
			$percent = min($percent, 100);
		}

		$this->total        = $total;
		$this->done         = $done;
		$this->percent      = $percent;
		$this->total_parts  = $parts;
		$this->current_part = $part;

		// Add an immediate redirection URL as a script option
		$step     = (int) $model->getState('step', 1) + 1;
		$location = Route::_('index.php?option=com_akeebabackup&view=S3import&layout=downloading&task=dltoserver&step=' . $step . '&' . Factory::getApplication()->getFormToken() . '=1', false, Route::TLS_IGNORE, true);
		$this->getDocument()
			->addScriptOptions('akeebabackup.S3import.autoRedirectURL', $location);
	}

	/**
	 * Get the Joomla HTML drop-down for the S3 buckets
	 *
	 * @return mixed
	 */
	public function getBucketsDropdown()
	{
		/** @var S3importModel $model */
		$model     = $this->getModel();
		$options   = [];
		$buckets   = $model->getBuckets();
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_AKEEBABACKUP_S3IMPORT_LABEL_SELECTBUCKET'));

		if (!empty($buckets))
		{
			foreach ($buckets as $b)
			{
				$options[] = HTMLHelper::_('select.option', $b, $b);
			}
		}

		$selected = $model->getState('s3bucket', '');

		return HTMLHelper::_('select.genericlist', $options, 's3bucket', [
			'list.attr' => [
				'class' => 'form-select',
			],
		], 'value', 'text', $selected);
	}

	private function addToolbar(): void
	{
		$toolbar = $this->getToolbarCompat();
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_S3IMPORT'), 'icon-akeeba');

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');

		$toolbar->help(null, false, 'https://www.akeebabackup .com/documentation/akeeba-backup-joomla/import-s3.html');
	}

}