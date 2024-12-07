<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Engine\Core\Domain\Finalizer;

use Akeeba\Engine\Factory;

/**
 * Updates the file sizes in the statistics records
 *
 * @since       9.3.1
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 *
 */
final class UpdateFileSizes extends AbstractFinalizer
{
	/**
	 * @inheritDoc
	 */
	public function __invoke()
	{
		$this->setStep('Updating file sizes');
		$this->setSubstep('');
		Factory::getLog()->debug("Updating statistics with file sizes");

		// Fetch the stats record
		$statistics    = Factory::getStatistics();
		$configuration = Factory::getConfiguration();
		$record        = $statistics->getRecord();
		$filenames     = $statistics->get_all_filenames($record) ?: [];
		$filesize      = 0.0;

		// Calculate file sizes of files remaining on the server
		foreach ($filenames as $file)
		{
			$filesize += ((@filesize($file)) ?: 0) * 1.0;
		}

		// Get the part size in volatile storage, set from the immediate part uploading effected by the
		// "Process each part immediately" option, and add it to the total file size
		$config              = $configuration;
		$postProcImmediately = $config->get('engine.postproc.common.after_part', 0, false);
		$deleteAfter         = $config->get('engine.postproc.common.delete_after', 0, false);
		$postProcEngine      = $config->get('akeeba.advanced.postproc_engine', 'none');

		if ($postProcImmediately && $deleteAfter && ($postProcEngine != 'none'))
		{
			$filesize += $configuration->get('volatile.engine.archiver.totalsize', 0) ?: 0;
		}

		$data = [
			'total_size' => $filesize,
		];

		Factory::getLog()->debug("Total size of backup archive (in bytes): $filesize");

		$statistics->setStatistics($data);

		return true;
	}
}