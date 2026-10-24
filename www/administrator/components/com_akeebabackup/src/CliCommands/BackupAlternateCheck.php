<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\CliCommands;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ArgumentUtilities;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ConfigureIO;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\InitialiseEngine;
use Akeeba\Engine\Platform;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Http\HttpFactory;
use Joomla\Http\Transport\Curl as CurlTransport;
use Joomla\Http\Transport\Stream as StreamTransport;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * akeeba:backup:alternate_check
 *
 * Checks for failed backups using the front-end failed backup check feature
 *
 * @since   7.5.0
 */
class BackupAlternateCheck extends AbstractCommand
{
	use MVCFactoryAwareTrait;
	use ConfigureIO;
	use ArgumentUtilities;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  9.0.0
	 */
	protected static $defaultName = 'akeeba:backup:alternate_check';

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   9.0.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureSymfonyIO($input, $output);

		try
		{
			$this->initialiseComponent($this->getApplication());
		}
		catch (\Throwable $e)
		{
			$this->ioStyle->error([
				Text::_('COM_AKEEBABACKUP_CLI_ERR_CANNOT_LOAD_BACKUP_ENGINGE'),
				$e->getMessage(),
			]);

			return 255;
		}

		$this->ioStyle->title(Text::_('COM_AKEEBABACKUP_CLI_HEAD_CHECK_ALTERNATE'));

		if (function_exists('set_time_limit'))
		{
			$this->ioStyle->comment(Text::_('COM_AKEEBABACKUP_CLI_LBL_UNSET_TIME_LIMITS'));

			@set_time_limit(0);
		}
		else
		{
			$this->ioStyle->warning(Text::_('COM_AKEEBABACKUP_CLI_ERR_UNSET_TIME_LIMITS'));
		}

		$url           = Platform::getInstance()->get_platform_configuration_option('siteurl', '');

		if (empty($url))
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_ERR_NO_LIVE_SITE'));

			return 255;
		}

		// Get the front-end backup settings
		$frontend_enabled = Platform::getInstance()->get_platform_configuration_option('akeebabackup', 'legacyapi_enabled');
		$secret           = Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', '');

		if (!$frontend_enabled)
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_ERR_FEBACKUP_DISABLED'));

			return 255;
		}

		if (empty($secret))
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_ERR_FEBACKUP_NOSECRET'));

			return 255;
		}

		$httpAdapters = [];

		if (CurlTransport::isSupported())
		{
			$httpAdapters[] = 'Curl';
		}

		if (StreamTransport::isSupported())
		{
			$httpAdapters[] = 'Stream';
		}

		if (empty($httpAdapters))
		{
			$this->ioStyle->error([
				Text::_('COM_AKEEBABACKUP_CLI_ERR_FECHECK_NOMETHOD'),
				Text::_('COM_AKEEBABACKUP_CLI_ERR_FEBACKUPORCHECK_METHOD_CURL'),
				Text::_('COM_AKEEBABACKUP_CLI_ERR_FEBACKUPORCHECK_METHOD_FOPEN'),
				Text::_('COM_AKEEBABACKUP_CLI_ERR_FECHECK_METHOD_NOMETHOD'),
			]);

			return 255;
		}

		// Perform the backup
		$url          = rtrim($url, '/');
		$secret       = urlencode($secret);
		$url          .= "/index.php?option=com_akeebabackup&view=Check&key={$secret}";

		$timestamp = date('Y-m-d H:i:s');

		$this->ioStyle->writeln(Text::sprintf('COM_AKEEBABACKUP_CLI_LBL_CONSOLELOG_BEGINNING_CHECK', $timestamp));
		$this->ioStyle->writeln(sprintf('URL: %s', $url), SymfonyStyle::VERBOSITY_VERY_VERBOSE);

		$httpOptions = [
			'follow_location'  => true,
			'transport.curl'   => [
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_FOLLOWLOCATION => 1,
				CURLOPT_TIMEOUT        => 600,
			],
			'transport.stream' => [
				'timeout' => 600,
			],
		];

		$http = (new HttpFactory())->getHttp($httpOptions, $httpAdapters);

		$response  = $http->get($url);
		$timestamp = date('Y-m-d H:i:s');

		if (($response->getStatusCode() < 200) || ($response->getStatusCode() >= 300))
		{
			$this->ioStyle->writeln(Text::sprintf('COM_AKEEBABACKUP_CLI_LBL_CONSOLELOG_RECEIVED_HTTP', $timestamp, $response->getStatusCode()));
			$this->ioStyle->error(Text::sprintf(
				'COM_AKEEBABACKUP_CLI_ERR_FECHECK_HTTP_ERROR',
				$response->getStatusCode(),
				$response->getReasonPhrase()
			));

			return 100;
		}

		$result = (string) $response->getBody();

		if (empty($result) || ($result === false))
		{
			$this->ioStyle->writeln(Text::sprintf('COM_AKEEBABACKUP_CLI_LBL_CONSOLELOG_NO_MESSAGE', $timestamp));
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_ERR_FECHECK_NO_MESSAGE_FROM_SERVER'));

			return 100;
		}

		if (strpos($result, '200 ') !== false)
		{
			$this->ioStyle->writeln(Text::sprintf('COM_AKEEBABACKUP_CLI_LBL_CONSOLELOG_CHECKS_FINALISATION_RECEIVED', $timestamp));
			$this->ioStyle->success([
				Text::_('COM_AKEEBABACKUP_CLI_LBL_FECHECK_FINISH_HEAD'),
			]);

			return 0;
		}
		elseif (strpos($result, '500 ') !== false)
		{
			// Backup error
			$this->ioStyle->writeln(Text::sprintf('COM_AKEEBABACKUP_CLI_LBL_CONSOLELOG_ERROR500_RECEIVED', $timestamp));
			$this->ioStyle->error([
				Text::_('COM_AKEEBABACKUP_CLI_ERR_FECHECK_ERROR_RECEIVED'),
				$result,
			]);

			return 2;
		}
		elseif (strpos($result, '403 ') !== false)
		{
			// This should never happen: invalid authentication or front-end backup disabled
			$this->ioStyle->writeln(Text::sprintf('COM_AKEEBABACKUP_CLI_LBL_CONSOLELOG_ERROR403_RECEIVED', $timestamp));
			$this->ioStyle->error([
				Text::_('COM_AKEEBABACKUP_CLI_ERR_FEBACKUP_ERROR403_RECEIVED'),
				Text::_('COM_AKEEBABACKUP_CLI_ERR_SERVER_RESPONSE'),
				$result,
			]);

			return 103;
		}
		else
		{
			// Unknown result?!
			$this->ioStyle->writeln(Text::sprintf('COM_AKEEBABACKUP_CLI_LBL_CONSOLELOG_CORRUPT', $timestamp));
			$this->ioStyle->error([
				Text::_('COM_AKEEBABACKUP_CLI_ERR_FECHECKS_CORRUPT_RESPONSE'),
				$result,
				Text::_('COM_AKEEBABACKUP_CLI_ERR_FECHECKS_CORRUPT_RESPONSE_INFO'),
			]);

			return 1;
		}
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   9.0.0
	 */
	protected function configure(): void
	{
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_CHECK_ALT_COMMAND_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_CHECK_ALT_COMMAND_HELP'));
	}
}
