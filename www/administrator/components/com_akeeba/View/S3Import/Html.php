<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\S3Import;

use Akeeba\Backup\Admin\Model\S3Import;
use FOF30\View\DataView\Html as BaseView;

// Protect from unauthorized access
defined('_JEXEC') or die();

class Html extends BaseView
{
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
		$this->container->template->addJS('media://com_akeeba/js/S3Import.min.js');

		/** @var S3Import $model */
		$model = $this->getModel();
		$model->getS3Credentials();

		$contents     = $model->getContents();
		$buckets      = $model->getBuckets();
		$bucketSelect = $model->getBucketsDropdown();
		$platform     = $this->container->platform;
		$input        = $this->input;
		$root         = $platform->getUserStateFromRequest('com_akeeba.folder', 'folder', $input, '', 'raw');

		// Assign variables
		$this->s3access     = $model->getState('s3access');
		$this->s3secret     = $model->getState('s3secret');
		$this->buckets      = $buckets;
		$this->bucketSelect = $bucketSelect;
		$this->contents     = $contents;
		$this->root         = $root;
		$this->crumbs       = $model->getCrumbs();

		// Script options
		$platform = $this->container->platform;
		$platform->addScriptOptions('akeeba.S3Import.accessKey', $this->s3access);
		$platform->addScriptOptions('akeeba.S3Import.secretKey', $this->s3secret);
		$platform->addScriptOptions('akeeba.S3Import.importURL', 'index.php?option=com_akeeba&view=S3Import&task=dltoserver&part=-1&frag=-1&layout=downloading');
	}

	public function onBeforeDltoserver()
	{
		$this->container->template->addJS('media://com_akeeba/js/S3Import.min.js');

		$this->setLayout('downloading');

		/** @var S3Import $model */
		$model    = $this->getModel();
		$platform = $this->container->platform;

		$total = $platform->getSessionVar('s3import.totalsize', 0, 'com_akeeba');
		$done  = $platform->getSessionVar('s3import.donesize', 0, 'com_akeeba');
		$part  = $platform->getSessionVar('s3import.part', 0, 'com_akeeba') + 1;
		$parts = $platform->getSessionVar('s3import.totalparts', 0, 'com_akeeba');

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
		$step     = $model->getState('step', 1, 'int') + 1;
		$location = 'index.php?option=com_akeeba&view=S3Import&layout=downloading&task=dltoserver&step=' . $step;
		$platform->addScriptOptions('akeeba.S3Import.autoRedirectURL', $location);
	}
}
