<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Finalization;

use AKAbstractUnarchiver;
use Akeeba\Engine\Core\Domain\Finalization;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use AKFactory;
use JText;
use Psr\Log\LogLevel;
use RuntimeException;

// Protection against direct access
defined('AKEEBAENGINE') or die();

/**
 * Performs a dry-run extraction to ensure the integrity of the backup archive.
 */
class TestExtract
{
	/**
	 * Checks the archive's data integrity by performing a dry-run extraction (no data is written to disk, just making
	 * sure the archive can be extracted). If you have enabled "Upload archive part immediately" this won't run and
	 * produce a warning instead.
	 *
	 * @param   Finalization  $parent
	 *
	 * @return  bool  True when done or an error occurred
	 */
	public function test_extract($parent)
	{
		// If for any reason we're called outside the Finalization context let's give up without a fight
		if (!($parent instanceof Finalization))
		{
			return true;
		}

		// Load the configuration engine
		$config = Factory::getConfiguration();

		// Only run if we're explicitly enabled
		$enabled = $config->get('akeeba.advanced.integritycheck', 0);

		if (!$enabled)
		{
			return true;
		}

		// Make sure the "Process each part immediately" option is not enabled
		$postProcImmediately = $config->get('engine.postproc.common.after_part', 0, false);
		$postProcEngine      = $config->get('akeeba.advanced.postproc_engine', 'none');

		if ($postProcImmediately && ($postProcEngine != 'none'))
		{
			Factory::getLog()->warning(JText::_('COM_AKEEBA_ENGINE_TEXTEXTRACT_ERR_PROCESSIMMEDIATELY'));

			return true;
		}

		// Make sure an archiver engine producing backup archives of JPA, JPS or ZIP files is in use
		$archiver  = Factory::getArchiverEngine();
		$extension = $archiver->getExtension();
		$extension = strtoupper($extension);
		$extension = ltrim($extension, '.');

		if (!in_array($extension, ['JPA', 'JPS', 'ZIP']))
		{
			Factory::getLog()->warning(JText::_('COM_AKEEBA_ENGINE_TEXTEXTRACT_ERR_INVALIDARCHIVERTYPE'));

			return true;
		}

		// Set the KICKSTART constant to prevent Akeeba Restore from taking over the page output
		if (!defined('KICKSTART'))
		{
			define('KICKSTART', 1);
		}

		// Try to load Akeeba Restore
		$this->loadAkeebaRestore();

		// Set up the Akeeba Restore engine, either from a serialised factory or from scratch
		$factory = $config->get('volatile.finalization.testextract.factory', null, false);

		if (!is_null($factory) && is_string($factory))
		{
			AKFactory::unserialize($factory);
		}
		else
		{
			$this->setUpAkeebaRestore();
		}

		$parent->relayStep('Archive integrity check');
		$parent->relaySubstep('Testing if archive can be extracted');

		AKFactory::set('kickstart.enabled', true);

		/** @var AKAbstractUnarchiver $engine */
		$engine   = AKFactory::getUnarchiver();
		$observer = new FakeRestorationObserver();
		$engine->attach($observer);

		$engine->tick();
		$ret = $engine->getStatusArray();

		// Did an error occur?
		if ($ret['Error'] != '')
		{
			throw new RuntimeException(JText::sprintf('COM_AKEEBA_ENGINE_TEXTEXTRACT_ERR_INTEGRITYCHECKFAILED', $ret['Error']));
		}

		// Did we finish successfully?
		if (!$ret['HasRun'])
		{
			Factory::getLog()->log(LogLevel::INFO, __CLASS__ . ": The archive's integrity has been validated");
			$config->set('volatile.finalization.testextract.factory', null, false);

			return true;
		}

		// Step finished and we need one more step to proceed.
		$factory = AKFactory::serialize();
		$config->set('volatile.finalization.testextract.factory', $factory, false);

		return false;
	}

	/**
	 * Try to load the Akeeba Restore engine
	 */
	private function loadAkeebaRestore()
	{
		$path = __DIR__ . '/../../../restore.php';

		if (!file_exists($path) || !include_once($path))
		{
			throw new RuntimeException(JText::_('COM_AKEEBA_ENGINE_TEXTEXTRACT_ERR_ENGINENOTFOUND'), 500);
		}
	}

	/**
	 * Set up the Akeeba Restore engine for the current archive
	 */
	private function setUpAkeebaRestore()
	{
		$config = Factory::getConfiguration();

		$maxTime = Factory::getTimer()->getTimeLeft();
		$maxTime = floor($maxTime);
		$maxTime = max(2, $maxTime);

		$statistics   = Factory::getStatistics();
		$stat         = $statistics->getRecord();
		$backup_parts = Factory::getStatistics()->get_all_filenames($stat, false);
		$filePath     = array_shift($backup_parts);

		$specialDirs = Platform::getInstance()->get_stock_directories();
		$tmpPath     = $specialDirs['[SITETMP]'];

		$archiver  = Factory::getArchiverEngine();
		$extension = $archiver->getExtension();
		$extension = strtoupper($extension);
		$extension = ltrim($extension, '.');

		$ksOptions = [
			'kickstart.tuning.max_exec_time' => $maxTime,
			'kickstart.tuning.run_time_bias' => $config->get('akeeba.tuning.run_time_bias', 75),
			'kickstart.tuning.min_exec_time' => '0',
			'kickstart.procengine'           => 'direct',
			'kickstart.setup.sourcefile'     => $filePath,
			'kickstart.setup.destdir'        => $tmpPath,
			'kickstart.setup.restoreperms'   => '0',
			'kickstart.setup.filetype'       => $extension,
			'kickstart.setup.dryrun'         => '1',
			'kickstart.jps.password'         => $config->get('engine.archiver.jps.key', '', false),
		];

		AKFactory::nuke();

		foreach ($ksOptions as $k => $v)
		{
			AKFactory::set($k, $v);
		}

		AKFactory::set('kickstart.enabled', true);
	}
}
