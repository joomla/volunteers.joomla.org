<?php
/**
 * @package     CSVI
 * @subpackage  JoomlaCategories
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2006 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Rolandd\Plugin\Console\Sso\Console;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Filesystem\Exception\FilesystemException;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;
use Rolandd\Component\Sso\Site\Traits\SimpleSamlPhpTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rotate SimpleSAMLphp logfile
 *
 * @since   2.0.0
 */
class LogrotateCommand extends AbstractCommand
{
    use SimpleSamlPhpTrait;

    /**
     * The default command name
     *
     * @var    string
     * @since  2.0.0
     */
    protected static $defaultName = 'sso:logrotate';

    /**
     * SymfonyStyle Object
     *
     * @var    SymfonyStyle
     * @since  2.0.0
     */
    private SymfonyStyle $ioStyle;

    protected Registry $params;

    /**
     * Initialise the command.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function configure(): void
    {
        $this->setDescription('RO Single Sign On Rotate SimpleSAMLphp Log');
    }

    /**
     * @param   Registry  $params
     *
     * @return LogrotateCommand
     *
     * @since  2.0.0
     */
    public function setParams(Registry $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Internal function to execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  int  The command exit code
     *
     * @since   2.0.0
     * @throws  Exception
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
	    $this->ioStyle = new SymfonyStyle($input, $output);

	    try
	    {
            if ($this->ioStyle->isVerbose()) {
                $this->ioStyle->info(Text::_('PLG_CONSOLE_SSO_LOGROTATE_START'));
            }

            if (!file_exists(JPATH_LIBRARIES . '/simplesamlphp/config/config.php')) {
                throw new \InvalidArgumentException(Text::_('PLG_CONSOLE_SSO_SSO_CONFIG_FILE_NOT_FOUND'));
            }

            $this->checkSimpleSamlPhp();
            /** @var array $config */
            require_once JPATH_LIBRARIES . '/simplesamlphp/config/config.php';

            if ($config['logging.handler'] !== 'file') {
                if ($this->ioStyle->isVerbose()) {
                    $this->ioStyle->info(Text::_('PLG_CONSOLE_SSO_LOGGING_HANDLER_NOT_FILE'));
                }
                return Command::SUCCESS;
            }

            if (!isset($config['loggingdir'])) {
                if ($this->ioStyle->isVerbose()) {
                    $this->ioStyle->error(Text::_('PLG_CONSOLE_SSO_LOGGING_DIR_MISSING'));
                }
                return Command::FAILURE;
            }

            $logFile = $config['loggingdir'] . $config['logging.logfile'];

            if (!file_exists($logFile)) {
                if ($this->ioStyle->isVerbose()) {
                    $this->ioStyle->info(Text::sprintf('PLG_CONSOLE_SSO_LOGFILE_NOT_FOUND', $logFile));
                }

                return Command::SUCCESS;
            }
            $logsToKeep = $this->params->get('logstokeep', 5);
            $logFiles = $this->getLogFiles($config['loggingdir']);

            // Sort log files by version number in reverse order
            krsort($logFiles, SORT_NUMERIC);

            foreach ($logFiles as $version => $files) {
                if ($version >= $logsToKeep) {
                    // Delete files which have version greater than or equals $logsToKeep
                    foreach ($files as $file) {
                        try {
                            File::delete($config['loggingdir'] . $file);
                        } catch (FilesystemException) {
                        }
                    }
                } else {
                    // For files which have version smaller than $logsToKeep, rotate (increase version number)
                    foreach ($files as $file) {
                        $this->rotate($config['loggingdir'], $file, $version);
                    }
                }
            }
	    }
	    catch (Exception $e)
	    {
			$this->ioStyle->error($e->getMessage());

			return Command::FAILURE;
	    }

	    return Command::SUCCESS;
    }

    /**
     * Get log files from log folder
     *
     * @param   string  $path  The folder to get log files
     *
     * @return  array   The log files in the given path grouped by version number (not rotated files have number 0)
     *
     * @since   2.0.0
     */
    private function getLogFiles(string $path): array
    {
        $logFiles = [];
        $files    = Folder::files($path, '\.log$');

        foreach ($files as $file) {
            $parts = explode('.', $file);

            /*
             * Rotated log file has this filename format [VERSION].[FILENAME].log. So if $parts has at least 3 elements
             * and the first element is a number, we know that it's a rotated file and can get it's current version
             */
            if (\count($parts) >= 3 && is_numeric($parts[0])) {
                $version = (int) $parts[0];
            } else {
                $version = 0;
            }

            if (!isset($logFiles[$version])) {
                $logFiles[$version] = [];
            }

            $logFiles[$version][] = $file;
        }

        return $logFiles;
    }

    /**
     * Method to rotate (increase version) of a log file
     *
     * @param   string  $path            Path to file to rotate
     * @param   string  $filename        Name of file to rotate
     * @param   int     $currentVersion  The current version number
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function rotate(string $path, string $filename, int $currentVersion): void
    {
        if ($currentVersion === 0) {
            $rotatedFile = $path . '1.' . $filename;
        } else {
            /*
             * Rotated log file has this filename format [VERSION].[FILENAME].php. To rotate it, we just need to explode
             * the filename into an array, increase value of first element (keep version) and implode it back to get the
             * rotated file name
             */
            $parts    = explode('.', $filename);
            $parts[0] = $currentVersion + 1;

            $rotatedFile = $path . implode('.', $parts);
        }

        try {
            File::move($path . $filename, $rotatedFile);
        } catch (FilesystemException) {
        }
    }
}
