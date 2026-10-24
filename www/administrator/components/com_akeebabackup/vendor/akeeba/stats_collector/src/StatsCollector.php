<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector;

use Akeeba\UsageStats\Collector\CmsInfo\CmsInfo;
use Akeeba\UsageStats\Collector\CommonVariables\CommonVariables;
use Akeeba\UsageStats\Collector\Constants\SoftwareType;
use Akeeba\UsageStats\Collector\DatabaseInfo\DatabaseInfo;
use Akeeba\UsageStats\Collector\LegacyShim\HashHelper;
use Akeeba\UsageStats\Collector\Random\Random;
use Akeeba\UsageStats\Collector\Sender\Sender;
use Akeeba\UsageStats\Collector\SiteUrl\SiteUrl;
use Akeeba\UsageStats\Collector\Version\Version;
use DateInterval;
use DateTime;
use Throwable;
use function strlen;

/**
 * Usage Statistics collector
 *
 * @since  1.0.0
 * @api
 */
final class StatsCollector
{
	/**
	 * Forbidden domains.
	 *
	 * When the current site's domain name ends with this substring no information will be sent
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	private const FORBIDDEN_DOMAINS = [
		'.web',
		'.akeeba.rocks',
		'.akeeba.dev',
		'.invalid',
		'.test',
		'example.com',
		'example.net',
		'example.org',
		'.local',
		'.localdomain',
	];

	/**
	 * Common Variables abstraction object
	 *
	 * @var   CommonVariables|null;
	 * @since 1.0.0
	 */
	private $commonVariables = null;

	/**
	 * Random Bytes Generator abstraction object
	 *
	 * @var   Random|null;
	 * @since 1.0.0
	 */
	private $randomGenerator = null;

	/**
	 * The CMS type
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	private $cmsType;

	/**
	 * The (unparsed) CMS version
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	private $cmsVersion;

	/**
	 * The software type
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	private $softwareType;

	/**
	 * The (unparsed) software version
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	private $softwareVersion;

	/**
	 * Is this a Professional version of the software?
	 *
	 * @var   bool
	 * @since 1.0.0
	 */
	private $softwarePro;

	/**
	 * The database type
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	private $databaseType;

	/**
	 * The database version
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	private $databaseVersion;

	/**
	 * The URL of the site we are running under
	 *
	 * @var   null
	 * @since 1.0.0
	 */
	private $siteUrl = null;

	/**
	 * The URL to send the statistics to
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	private $serverUrl = 'https://abrandnewsite.com/index.php';

	/**
	 * The request timeout for sending the usage statistics, in seconds
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	private $timeout = 5;

	/**
	 * The minimum period between successive reports of usage statistics
	 *
	 * @var   DateInterval
	 * @since 1.0.0
	 */
	private $statsInterval;

	/**
	 * Public constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct(
		int $softwareType, string $softwareVersion, bool $softwarePro = false
	)
	{
		// We'll need these objects further down
		$this->commonVariables = new CommonVariables();
		$this->randomGenerator = new Random();
		$this->statsInterval   = new DateInterval('P1W');

		// Set the software and software version from the constructor arguments
		$this->setSoftware($softwareType, $softwareVersion, $softwarePro);

		// Auto-detect the database information
		$databaseInfo = new DatabaseInfo();

		$this->setDatabase($databaseInfo->getType(), $databaseInfo->getVersion());

		// Auto-detect the CMS information
		$this->cmsType    = SoftwareType::getCMSType($this->softwareType) ?: null;
		$cmsInfo          = new CmsInfo();
		$this->cmsType    = $this->cmsType ?? $cmsInfo->getType();
		$this->cmsVersion = $cmsInfo->getVersion();

		// Auto-detect the site URL (used to decide whether the unique Site ID needs to be regenerated; never transmitted!)
		$siteUrl       = new SiteUrl();
		$this->siteUrl = trim($siteUrl->getUrl()) ?: '';
	}

	/**
	 * Sends the usage statistics information to the collection server
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function sendStatistics(): void
	{
		$sender = new Sender($this->serverUrl, $this->timeout);

		$sender->sendStatistics($this->getQueryParameters());

		$this->commonVariables->setCommonVariable('stats_lastrun', (new DateTime())->getTimestamp());
	}

	/**
	 * Should I send the statistics information to the server?
	 *
	 * @param   bool  $updateLastRun        True to also update the last sent flag
	 * @param   bool  $useForbiddenDomains  True to prevent sending information if the site belongs to the forbidden
	 *                                      domains.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function shouldSendStatistics(bool $updateLastRun = true, bool $useForbiddenDomains = true): bool
	{
		// Check 1. Forbidden domain
		if ($useForbiddenDomains)
		{
			$currentUrl = $this->siteUrl ?? $this->commonVariables->getCommonVariable('stats_siteurl', null);

			if ($this->isForbiddenDomain($currentUrl))
			{
				return false;
			}
		}

		// Check 2. Minimum interval between successive information reporting
		$lastTimeStamp = (int) $this->commonVariables->getCommonVariable('stats_lastrun', 0);
		try
		{
			$lastDateTime   = new DateTime('@' . $lastTimeStamp);
			$nextReportDate = (clone $lastDateTime)->add($this->statsInterval);
			$now            = new DateTime();
		}
		catch (Throwable $e)
		{
			return false;
		}

		$mustSendStatistics = $nextReportDate->diff($now)->invert == 0;

		if (!$mustSendStatistics)
		{
			return false;
		}

		// Should I update the last statistics sending date and time?
		if ($updateLastRun)
		{
			$this->commonVariables->setCommonVariable('stats_lastrun', $now->getTimestamp());
		}

		return true;
	}

	/**
	 * Conditionally send usage statistics
	 *
	 * @param   bool  $useForbiddenDomains   True to prevent sending information if the site belongs to the forbidden
	 *                                       domains.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function conditionalSendStatistics(bool $useForbiddenDomains = true): void
	{
		if (!$this->shouldSendStatistics(true, $useForbiddenDomains))
		{
			return;
		}

		$this->sendStatistics();
	}

	/**
	 * Get (or generate) the pseudonymous Site ID.
	 *
	 * The Site ID is generated randomly and is guaranteed to be **most likely** unique. This allows us to collect
	 * statistics without counting the same site multiple times, without violating the privacy of the user.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getSiteId(): string
	{
		$siteId     = $this->commonVariables->getCommonVariable('stats_siteid', null);
		$currentUrl = $this->siteUrl ?? $this->commonVariables->getCommonVariable('stats_siteurl', null);

		if (empty($siteId)
		    || (!empty($currentUrl)
		        && HashHelper::md5($currentUrl) !== $this->commonVariables->getCommonVariable(
					'stats_siteurl', null
				)))
		{
			$siteUrl = HashHelper::md5($currentUrl);
			$this->commonVariables->setCommonVariable('stats_siteurl', $siteUrl);

			$siteId = HashHelper::sha1($this->randomGenerator->getRandomBytes(120));
			$this->commonVariables->setCommonVariable('stats_siteid', $siteId);
		}

		return $siteId;
	}

	/**
	 * Set the software information.
	 *
	 * @param   int     $type     The software type
	 * @param   string  $version  The software version
	 * @param   bool    $isPro    Is this the Professional version of the software? FALSE for software without a Pro
	 *                            version.
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public function setSoftware(int $type, string $version, bool $isPro = false): self
	{
		$this->softwareType    = $type;
		$this->softwareVersion = $version;
		$this->softwarePro     = $isPro;

		return $this;
	}

	/**
	 * Set the CMS type and version we are running under
	 *
	 * @param   int     $cmsType     The CMS type
	 * @param   string  $cmsVersion  The CMS version
	 *
	 * @return  self
	 */
	public function setCms(int $cmsType, string $cmsVersion): self
	{
		$this->cmsType    = $cmsType;
		$this->cmsVersion = $cmsVersion;

		return $this;
	}

	/**
	 * Set the database information
	 *
	 * @param   int     $type     The database type
	 * @param   string  $version  The database version
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public function setDatabase(int $type, string $version): self
	{
		$this->databaseType    = $type;
		$this->databaseVersion = $version;

		return $this;
	}

	/**
	 * Set the URL of the current site
	 *
	 * @param   null  $siteUrl
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public function setSiteUrl($siteUrl): self
	{
		$this->siteUrl = $siteUrl;

		return $this;
	}

	/**
	 * Get the URL query parameters corresponding to the information currently set in the object
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	public function getQueryParameters(): array
	{
		$swVersion  = new Version($this->softwareVersion);
		$phpVersion = new Version(PHP_VERSION);
		$dbVersion  = new Version($this->databaseVersion);
		$cmsVersion = new Version($this->cmsVersion);

		return [
			'sid' => $this->getSiteId(),
			'sw'  => SoftwareType::changeCoreOrPro($this->softwareType, $this->softwarePro),
			'pro' => $this->softwarePro ? 1 : 0,
			'sm'  => $swVersion->major(),
			'sn'  => $swVersion->minor(),
			'sr'  => $swVersion->patch(),
			'pm'  => $phpVersion->major(),
			'pn'  => $phpVersion->minor(),
			'pr'  => $phpVersion->patch(),
			'pq'  => $phpVersion->tag(),
			'dt'  => $this->databaseType,
			'dm'  => $dbVersion->major(),
			'dn'  => $dbVersion->minor(),
			'dr'  => $dbVersion->patch(),
			'dq'  => $dbVersion->tag(),
			'ct'  => $this->cmsType,
			'cm'  => $cmsVersion->major(),
			'cn'  => $cmsVersion->minor(),
			'cr'  => $cmsVersion->patch(),
		];
	}

	/**
	 * Set the statistics collection server URL.
	 *
	 * @param   string  $serverUrl  The URL to set
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public function setServerUrl(string $serverUrl): self
	{
		$this->serverUrl = $serverUrl;

		return $this;
	}

	/**
	 * Returns the statistics collection server URL.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getServerUrl(): string
	{
		return $this->serverUrl;
	}

	/**
	 * Sets the request timeout, in seconds, for sending the statistics to the server.
	 *
	 * @param   int  $timeout  The request timeout, in seconds
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public function setTimeout(int $timeout): self
	{
		$this->timeout = $timeout;

		return $this;
	}

	/**
	 * Sets the minimum period between successive reports of information to the server.
	 *
	 * @param   DateInterval  $statsInterval
	 *
	 * @return  self
	 */
	public function setStatsInterval(DateInterval $statsInterval): self
	{
		$this->statsInterval = $statsInterval;

		return $this;
	}

	/**
	 * Does the domain name of the given URL belong in the list of forbidden domains?
	 *
	 * @param   string|null  $currentUrl
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	private function isForbiddenDomain(?string $currentUrl): bool
	{
		if (empty($currentUrl))
		{
			return false;
		}

		$host = @parse_url($currentUrl, PHP_URL_HOST);

		if (empty($host))
		{
			return false;
		}

		foreach (self::FORBIDDEN_DOMAINS as $domain)
		{
			if (!empty($host) && $this->str_ends_with($host, $domain))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Polyfill for str_ends_with, for hosts running on PHP 7.
	 *
	 * @param   string  $haystack
	 * @param   string  $needle
	 *
	 * @return  bool
	 */
	private function str_ends_with(string $haystack, string $needle): bool
	{
		if (function_exists('str_ends_with'))
		{
			return str_ends_with($haystack, $needle);
		}

		if ($needle === '' || $needle === $haystack)
		{
			return true;
		}

		if ($haystack === '')
		{
			return false;
		}

		$needleLength = strlen($needle);

		return $needleLength <= strlen($haystack) && 0 === substr_compare($haystack, $needle, -$needleLength);
	}

}