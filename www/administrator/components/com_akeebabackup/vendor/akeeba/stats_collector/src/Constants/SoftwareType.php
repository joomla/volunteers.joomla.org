<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Constants;

/**
 * Software Types known to Akeeba Usage Stats
 *
 * @since  1.0.0
 */
final class SoftwareType
{
	public const AB_JOOMLA_CORE = 1;

	public const AB_JOOMLA_PRO = 2;

	public const AT_JOOMLA_CORE = 3;

	public const AT_JOOMLA_PRO = 4;

	public const SOLO_CORE = 5;

	public const SOLO_PRO = 6;

	public const AB_WP_CORE = 7;

	public const AB_WP_PRO = 8;

	public const ATS_JOOMLA_CORE = 9;

	public const ATS_JOOMLA_PRO = 10;

	public const ARS_JOOMLA = 11;

	public const DOCIMPORT_JOOMLA = 12;

	public const AKEEBASUBS_JOOMLA = 13;

	public const CMSUPDATE_JOOMLA = 14;

	public const AT_WP_PRO = 15;

	public const AT_WP_CORE = 16;

	public const PANOPTICON = 17;

	public const DARKLINK = 18;

	/**
	 * Get the correct software type code depending on whether it is Core or Professional
	 *
	 * @param   int   $softwareType  The software type; either a core or pro type will do
	 * @param   bool  $isPro         Is this the Pro version?
	 *
	 * @return  int  The software type you should be using
	 */
	public static function changeCoreOrPro(int $softwareType, bool $isPro = false): int
	{
		if (in_array($softwareType, [self::AB_JOOMLA_CORE, self::AB_JOOMLA_PRO]))
		{
			return $isPro ? self::AB_JOOMLA_PRO : self::AB_JOOMLA_CORE;
		}

		if (in_array($softwareType, [self::AT_JOOMLA_CORE, self::AT_JOOMLA_PRO]))
		{
			return $isPro ? self::AT_JOOMLA_PRO : self::AT_JOOMLA_CORE;
		}

		if (in_array($softwareType, [self::SOLO_CORE, self::SOLO_PRO]))
		{
			return $isPro ? self::SOLO_PRO : self::SOLO_CORE;
		}

		if (in_array($softwareType, [self::AB_WP_CORE, self::AB_WP_PRO]))
		{
			return $isPro ? self::AB_WP_PRO : self::AB_WP_CORE;
		}

		if (in_array($softwareType, [self::ATS_JOOMLA_CORE, self::ATS_JOOMLA_PRO]))
		{
			return $isPro ? self::ATS_JOOMLA_PRO : self::ATS_JOOMLA_CORE;
		}

		if (in_array($softwareType, [self::AT_WP_PRO, self::AT_WP_CORE]))
		{
			return $isPro ? self::AT_WP_PRO : self::AT_WP_CORE;
		}

		return $softwareType;
	}

	public static function getCMSType(int $softwareType): int
	{
		if (in_array(
			$softwareType,
			[
				self::AB_JOOMLA_CORE,
				self::AB_JOOMLA_PRO,
				self::AT_JOOMLA_CORE,
				self::AT_JOOMLA_PRO,
				self::ATS_JOOMLA_PRO,
				self::ATS_JOOMLA_CORE,
				self::ARS_JOOMLA,
				self::DOCIMPORT_JOOMLA,
				self::AKEEBASUBS_JOOMLA,
				self::CMSUPDATE_JOOMLA,
			]
		))
		{
			return CmsType::JOOMLA;
		}

		if (in_array(
			$softwareType,
			[
				self::AB_WP_CORE,
				self::AB_WP_PRO,
				self::AT_WP_CORE,
				self::AT_WP_PRO,
			]
		))
		{
			return CmsType::WORDPRESS;
		}

		return CmsType::STANDALONE;
	}
}