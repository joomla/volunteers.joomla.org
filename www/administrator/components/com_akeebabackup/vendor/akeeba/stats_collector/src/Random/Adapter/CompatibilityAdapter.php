<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Random\Adapter;

use Akeeba\UsageStats\Collector\LegacyShim\HashHelper;

/**
 * Random Bytes adapter with a pure PHP implementation for maximum backwards compatibility.
 *
 * This was adapted from Joomla 3.2's code base which, in turn, was based on ircmaxell/random-lib.
 *
 * @since  1.0.0
 */
final class CompatibilityAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getRandomBytes(int $length = 120): string
	{
		/*
		 * Collect any entropy available in the system along with a number
		 * of time measurements of operating system randomness.
		 */
		$bitsPerRound  = 2;
		$maxTimeMicro  = 400;
		$shaHashLength = 20;
		$randomStr     = '';
		$total         = $length;

		// Check if we can use /dev/urandom.
		$urandom = false;
		$handle  = null;

		// This is PHP 5.3.3 and up
		if (function_exists('stream_set_read_buffer') && @is_readable('/dev/urandom'))
		{
			$handle = @fopen('/dev/urandom', 'r');

			if ($handle)
			{
				$urandom = true;
			}
		}

		while ($length > strlen($randomStr))
		{
			$bytes = ($total > $shaHashLength) ? $shaHashLength : $total;
			$total -= $bytes;

			/*
			 * Collect any entropy available from the PHP system and filesystem.
			 * If we have ssl data that isn't strong, we use it once.
			 */
			$entropy = function_exists('random_bytes') && function_exists('bin2hex')
				? bin2hex(random_bytes($bytes))
				: uniqid(mt_rand(), true);
			$entropy .= rand();
			$entropy .= implode('', @fstat(fopen(__FILE__, 'r')));
			$entropy .= memory_get_usage();

			if ($urandom)
			{
				stream_set_read_buffer($handle, 0);
				$entropy .= @fread($handle, $bytes);
			}
			else
			{
				/*
				 * There is no external source of entropy so we repeat calls
				 * to mt_rand until we are assured there's real randomness in
				 * the result.
				 *
				 * Measure the time that the operations will take on average.
				 */
				$samples  = 3;
				$duration = 0;

				for ($pass = 0; $pass < $samples; ++$pass)
				{
					$microStart = microtime(true) * 1000000;
					$hash       = HashHelper::sha1(mt_rand(), true);

					for ($count = 0; $count < 50; ++$count)
					{
						$hash = HashHelper::sha1($hash, true);
					}

					$microEnd = microtime(true) * 1000000;
					$entropy  .= $microStart . $microEnd;

					if ($microStart >= $microEnd)
					{
						$microEnd += 1000000;
					}

					$duration += $microEnd - $microStart;
				}

				$duration = $duration / $samples;

				/*
				 * Based on the average time, determine the total rounds so that
				 * the total running time is bounded to a reasonable number.
				 */
				$rounds = (int) (($maxTimeMicro / $duration) * 50);

				/*
				 * Take additional measurements. On average we can expect
				 * at least $bitsPerRound bits of entropy from each measurement.
				 */
				$iter = $bytes * (int) ceil(8 / $bitsPerRound);

				for ($pass = 0; $pass < $iter; ++$pass)
				{
					$microStart = microtime(true);
					$hash       = HashHelper::sha1(mt_rand(), true);

					for ($count = 0; $count < $rounds; ++$count)
					{
						$hash = HashHelper::sha1($hash, true);
					}

					$entropy .= $microStart . microtime(true);
				}
			}

			$randomStr .= HashHelper::sha1($entropy, true);
		}

		if ($urandom)
		{
			@fclose($handle);
		}

		return substr($randomStr, 0, $length);
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return true;
	}
}