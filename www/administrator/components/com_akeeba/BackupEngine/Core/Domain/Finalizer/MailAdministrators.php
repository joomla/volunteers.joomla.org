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
use Akeeba\Engine\Platform;

/**
 * Sends an email to the site administrators on backup completion
 *
 * @since       9.3.1
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 */
final class MailAdministrators extends AbstractFinalizer
{
	/**
	 * @inheritDoc
	 */
	public function __invoke()
	{
		$this->setStep('Processing emails to administrators');
		$this->setSubstep('');

		$platform = Platform::getInstance();

		// Skip email for back-end backups
		if ($platform->get_backup_origin() == 'backend')
		{
			return true;
		}

		// Is the feature enabled?
		if ($platform->get_platform_configuration_option('frontend_email_on_finish', 0) == 0)
		{
			return true;
		}

		Factory::getLog()->debug("Preparing to send e-mail to administrators");

		$email = trim($platform->get_platform_configuration_option('frontend_email_address', ''));

		if (!empty($email))
		{
			Factory::getLog()->debug("Using pre-defined list of emails");

			$emails = explode(',', $email);
		}
		else
		{
			Factory::getLog()->debug("Fetching list of site administrator emails");

			$emails = $platform->get_administrator_emails();
		}

		if (empty($emails))
		{
			Factory::getLog()->debug("No email recipients found! Skipping email.");

			return true;
		}

		Factory::getLog()->debug("Creating email subject and body");

		// Get the statistics
		$statistics    = Factory::getStatistics();
		$profileNumber = $platform->get_active_profile();
		$profileName   = $platform->get_profile_name($profileNumber);
		$statsRecord   = $statistics->getRecord();
		$partsCount    = max(1, $statsRecord['multipart']);
		$allFilenames  = $this->getPartFilenames($statsRecord);
		$filesList     = implode(
			"\n", array_map(function ($file) {
				return "\t" . $file;
			}, $allFilenames)
		);
		$totalSize     = (int) ($statsRecord['total_size'] ?? 0);

		// Get the approximate part sizes and create a list of files and sizes
		$configuration     = Factory::getConfiguration();
		$partSize          = $configuration->get('engine.archiver.common.part_size', 0);
		$lastPartSize      = $totalSize - (($partsCount - 1) * $partSize);
		$partSizes         = array_fill(0, $partsCount - 1, $partSize);
		$partSizes[]       = $lastPartSize;
		$filesAndSizesList = implode(
			"\n",
			array_map(
				function ($file, $size) {
					return sprintf(
						"\t%s (approx. %s)",
						$file,
						$this->formatByteSize($size)
					);
				},
				$allFilenames,
				$partSizes
			)
		);

		// Determine the upload to remote storage status
		$remoteStatus   = '';
		$failedUpdate   = false;
		$postProcEngine = Factory::getConfiguration()->get('akeeba.advanced.postproc_engine');

		if (!empty($postProcEngine) && ($postProcEngine != 'none'))
		{
			$remoteStatus = $platform->translate('COM_AKEEBA_EMAIL_POSTPROCESSING_SUCCESS');

			if (empty($statsRecord['remote_filename']))
			{
				$failedUpdate = true;
				$remoteStatus = $platform->translate('COM_AKEEBA_EMAIL_POSTPROCESSING_FAILED');
			}
		}

		// Did the user ask to be emailed only on failed uploads but the upload has succeeded?
		if (
			!$failedUpdate
			&& ($platform->get_platform_configuration_option('frontend_email_when', 'always') == 'failedupload')
		)
		{
			return true;
		}

		// Fetch user's preferences
		$subject = trim($platform->get_platform_configuration_option('frontend_email_subject', ''));
		$body    = trim($platform->get_platform_configuration_option('frontend_email_body', ''));

		// Get a default subject or post-process a manually defined subject
		$subject = empty($subject)
			? $platform->translate('COM_AKEEBA_COMMON_EMAIL_SUBJECT_OK')
			: Factory::getFilesystemTools()->replace_archive_name_variables($subject);

		// Do we need a default body?
		if (empty($body))
		{
			$body = $platform->translate('COM_AKEEBA_COMMON_EMAIL_BODY_OK');
			$body .= "\n\n";
			$body .= sprintf(
				$platform->translate('COM_AKEEBA_COMMON_EMAIL_BODY_INFO'),
				$profileNumber,
				$partsCount
			);
			$body .= "\n\n";
			$body .= $filesAndSizesList;
		}
		else
		{
			// Post-process the body
			$body = Factory::getFilesystemTools()->replace_archive_name_variables($body);
			$body = str_replace('[PROFILENUMBER]', $profileNumber, $body);
			$body = str_replace('[PROFILENAME]', $profileName, $body);
			$body = str_replace('[PARTCOUNT]', $partsCount, $body);
			$body = str_replace('[FILELIST]', $filesList, $body);
			$body = str_replace('[FILESIZESLIST]', $filesAndSizesList, $body);
			$body = str_replace('[REMOTESTATUS]', $remoteStatus, $body);
			$body = str_replace('[TOTALSIZE]', $this->formatByteSize($totalSize), $body);
		}

		// Post-process the subject (support the [REMOTESTATUS] variable)
		$subject = str_replace('[REMOTESTATUS]', $remoteStatus, $subject);

		// Sometimes $body contains literal \n instead of newlines
		$body = str_replace('\\n', "\n", $body);

		foreach ($emails as $email)
		{
			Factory::getLog()->debug("Sending email to $email");
			try
			{
				$platform->send_email($email, $subject, $body);
			}
			catch (\Exception $e)
			{
				// Don't cry if we cannot send an email; just log it as a warning
				Factory::getLog()->warning(
					sprintf(
						'Cannot send email to ‘%s’. Error message: “%s”',
						$email,
						$e->getMessage()
					)
				);
			}
		}

		return true;
	}

	/**
	 * Returns a list of files' base names for the given backup statistics record
	 *
	 * @param   array  $statsRecord  The statistics record
	 *
	 * @return  array  List of file names
	 *
	 * @since   9.3.1
	 */
	private function getPartFilenames(array $statsRecord): array
	{
		$baseFile = basename(
			$statsRecord['absolute_path'] ?? $statsRecord['archivename'] ?? $statsRecord['remote_filename'] ?? ''
		);

		if (empty($baseFile))
		{
			return [];
		}

		$partsCount = max($statsRecord['multipart'] ?? 1, 1);

		if ($partsCount === 1)
		{
			return [$baseFile];
		}

		$ret       = [];
		$extension = substr($baseFile, strrpos($baseFile, '.'));
		$bareName  = basename($baseFile, $extension);

		for ($i = 1; $i < $partsCount; $i++)
		{
			$ret[] = $bareName . substr($extension, 0, 2) . sprintf('%02d', $i);
		}

		$ret[] = $baseFile;

		return $ret;
	}

	/**
	 * Formats a number of bytes in human-readable format
	 *
	 * @param   int|float  $size  The size in bytes to format, e.g. 8254862
	 *
	 * @return  string  The human-readable representation of the byte size, e.g. "7.87 Mb"
	 * @since   9.3.1
	 */
	private function formatByteSize($size): string
	{
		$unit = ['b', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

		return @round($size / 1024 ** ($i = floor(log($size, 1024))), 2) . ' ' . $unit[$i];
	}

}