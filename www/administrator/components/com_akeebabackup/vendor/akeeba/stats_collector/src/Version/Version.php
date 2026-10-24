<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Version;

/**
 * Version manipulation library
 *
 * Based on VersionParser by Sebastian Mordziol <s.mordziol@mistralys.eu>
 *
 * The original code carries the following copyright notice:
 * ====== ORIGINAL COPYRIGHT NOTICE START ======
 * MIT License
 *
 * Copyright (c) 2020 Mistralys
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * ====== ORIGINAL COPYRIGHT NOTICE END ======
 *
 * @since 1.0.0
 */
final class Version
{
	const TAG_TYPE_NONE = 'none';

	const TAG_TYPE_DEV = 'dev';

	const TAG_TYPE_BETA = 'beta';

	const TAG_TYPE_ALPHA = 'alpha';

	const TAG_TYPE_RELEASE_CANDIDATE = 'rc';

	/**
	 * The version number we are processing
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	private $version;

	/**
	 * The literal tag (qualifiers) of this version
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	private $tag = '';

	/**
	 * The parts the version number consists of
	 *
	 * @var   array
	 * @since 1.0.0
	 */
	private $parts = [];

	/**
	 * The normalised tag of this version
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	private $tagType = self::TAG_TYPE_NONE;

	/**
	 * The sub-minor version number in the tag
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	private $tagNumber = 0;

	/**
	 * The branch name, if present in the version string
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	private $branchName = '';

	/**
	 * Internal map of tag weights
	 *
	 * @var   array|int[]
	 * @since 1.0.0
	 */
	private $tagWeights = [
		self::TAG_TYPE_DEV               => 8,
		self::TAG_TYPE_ALPHA             => 6,
		self::TAG_TYPE_BETA              => 4,
		self::TAG_TYPE_RELEASE_CANDIDATE => 2,
		self::TAG_TYPE_NONE              => 0,
	];

	/**
	 * Public constructor
	 *
	 * @param   string  $version  The version to parse
	 *
	 * @since   1.0.0
	 */
	public function __construct(string $version)
	{
		$this->version = $version;

		$this->parse();
		$this->postParse();
	}

	/**
	 * Major version, e.g. 1 for 1.2.3.4-beta4
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function major(): int
	{
		return $this->parts[0];
	}

	/**
	 * Minor version, e.g. 2 for 1.2.3.4-beta4
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function minor(): int
	{
		return $this->parts[1];
	}

	/**
	 * Patch version, e.g. 3 for 1.2.3.4-beta4
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function patch(): int
	{
		return $this->parts[2];
	}

	/**
	 * Returns the normalised full version
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function fullVersion(): string
	{
		$version = $this->version;

		if (!$this->hasTag())
		{
			return $version;
		}

		return $version . '-' . $this->tag();
	}

	/**
	 * Returns the short version (x.y.z)
	 *
	 * @param   bool  $forceThreeParts  To force three parts, even if minor and patch are zero
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function shortVersion(bool $forceThreeParts = false): string
	{
		$keep = [];

		if ($forceThreeParts || $this->parts[2] > 0)
		{
			$keep = $this->parts;
		}
		elseif ($this->parts[1] > 0)
		{
			$keep = [$this->parts[0], $this->parts[1]];
		}
		else
		{
			$keep = [$this->parts[0]];
		}

		return implode('.', $keep);
	}

	/**
	 * Returns the version family, e.g. 1.2
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function versionFamily(): string
	{
		return implode('.', [$this->major(), $this->minor()]);
	}

	/**
	 * Returns the tag of the parsed version
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function tag(): string
	{
		return $this->tag;
	}

	/**
	 * Does the version have a tag?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function hasTag(): bool
	{
		return !empty($this->tag);
	}

	/**
	 * The type of the version tag
	 *
	 * @return  string
	 * @since   1.0.0
	 *
	 * @see     self::TAG_TYPE_NONE
	 * @see     self::TAG_TYPE_DEV
	 * @see     self::TAG_TYPE_ALPHA
	 * @see     self::TAG_TYPE_BETA,
	 * @see     self::TAG_TYPE_RELEASE_CANDIDATE
	 */
	public function tagType(): string
	{
		return $this->tagType;
	}

	/**
	 * The number in the tag, if one exists
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function tagNumber(): int
	{
		return $this->tagNumber;
	}

	/**
	 * Is this a beta version?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isBeta(): bool
	{
		return $this->tagType() === self::TAG_TYPE_BETA;
	}

	/**
	 * Is this an alpha version?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isAlpha(): bool
	{
		return $this->tagType() === self::TAG_TYPE_ALPHA;
	}

	/**
	 * Is this a Release Candidate version?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isRC(): bool
	{
		return $this->tagType() === self::TAG_TYPE_RELEASE_CANDIDATE;
	}

	/**
	 * Is this a Development version?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isDev(): bool
	{
		return $this->tagType() === self::TAG_TYPE_DEV;
	}

	/**
	 * Is this a stable version?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isStable(): bool
	{
		return $this->tagType() === self::TAG_TYPE_NONE;
	}

	/**
	 * Is this a testing (dev, alpha, beta, RC) version?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isTesting(): bool
	{
		return !$this->isStable();
	}

	/**
	 * Is a branch name present in the version?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function hasBranch(): bool
	{
		return !empty($this->branchName);
	}

	/**
	 * The branch name of the version string
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function branchName(): string
	{
		return $this->branchName;
	}

	/**
	 * Parse the version number
	 *
	 * @internal
	 * @return  void
	 * @since   1.0.0
	 */
	private function parse(): void
	{
		$parts = explode('.', $this->extractTag());
		$parts = array_map('trim', $parts);

		while (count($parts) < 3)
		{
			$parts[] = 0;
		}

		for ($i = 0; $i < 3; $i++)
		{
			$this->parts[] = intval($parts[$i]);
		}
	}

	/**
	 * Extract the tag from the version
	 *
	 * @internal
	 * @return  string
	 * @since   1.0.0
	 */
	private function extractTag(): string
	{
		$version = $this->version;
		$version = str_replace('_', '-', $version);

		$hyphen = strpos($version, '-');

		if ($hyphen !== false)
		{
			$tag     = substr($version, $hyphen + 1);
			$version = substr($version, 0, $hyphen);
			$this->parseTag($tag);
		}

		return $version;
	}

	/**
	 * Runs after parsing the version number
	 *
	 * @internal
	 * @return  void
	 * @since   1.0.0
	 */
	private function postParse(): void
	{
		$this->tag = $this->normalizeTag();
	}

	/**
	 * Normalises the tag representation
	 *
	 * @internal
	 * @return  string
	 * @since   1.0.0
	 */
	private function normalizeTag(): string
	{
		if ($this->tagType === self::TAG_TYPE_NONE)
		{
			return $this->branchName();
		}

		$tag = $this->tagType;

		if ($this->tagNumber > 1)
		{
			$tag .= $this->tagNumber;
		}

		if ($this->hasBranch())
		{
			$tag = $this->branchName() . '-' . $tag;
		}

		return $tag;
	}

	/**
	 * Returns the formatted tag number
	 *
	 * @internal
	 * @return  string
	 * @since   1.0.0
	 */
	private function formatTagNumber(): string
	{
		$positions = 2 * 3;
		$weight    = $this->tagWeights[$this->tagType()];

		if ($weight > 0)
		{
			$number = sprintf('%0' . $weight . 'd', $this->tagNumber);
			$number = str_pad($number, $positions, '0', STR_PAD_RIGHT);

			$number = intval(str_repeat('9', $positions)) - intval($number);

			return '.' . $number;
		}

		return '';
	}

	/**
	 * Parse the tag specified in the version
	 *
	 * @param   string  $tag
	 *
	 * @internal
	 * @return  void
	 * @since   1.0.0
	 */
	private function parseTag(string $tag): void
	{
		$parts = explode('-', $tag);

		foreach ($parts as $part)
		{
			$this->parseTagPart($part);
		}

		if ($this->tagNumber === 0)
		{
			$this->tagNumber = 1;
		}

		if ($this->tagType === self::TAG_TYPE_NONE)
		{
			$this->tagNumber = 0;
		}
	}

	/**
	 * Parse a part of the tag specified in the version
	 *
	 * @param   string  $part
	 *
	 * @internal
	 * @return  void
	 * @since   1.0.0
	 */
	private function parseTagPart(string $part): void
	{
		if (is_numeric($part))
		{
			$this->tagNumber = intval($part);

			return;
		}

		$types = array_keys($this->tagWeights);
		$type  = '';
		$lower = strtolower($part);

		foreach ($types as $tagType)
		{
			if (strstr($lower, $tagType))
			{
				$type = $tagType;
				$part = str_replace($tagType, '', $lower);
			}
		}

		if (empty($type))
		{
			if (!empty($part))
			{
				$this->branchName = $part;
			}

			return;
		}

		$this->tagType = $type;

		if (is_numeric($part))
		{
			$this->tagNumber = intval($part);
		}
	}
}
