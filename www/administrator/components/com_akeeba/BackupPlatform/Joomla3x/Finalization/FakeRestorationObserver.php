<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Finalization;

// Protection against direct access
defined('AKEEBAENGINE') or die();

class FakeRestorationObserver extends \AKAbstractPartObserver
{
	public $compressedTotal = 0;
	public $uncompressedTotal = 0;
	public $filesProcessed = 0;

	public function update($object, $message)
	{
		if(!is_object($message)) return;

		if( !array_key_exists('type', get_object_vars($message)) ) return;

		if( $message->type == 'startfile' )
		{
			$this->filesProcessed++;
			$this->compressedTotal += $message->content->compressed;
			$this->uncompressedTotal += $message->content->uncompressed;
		}
	}

	public function __toString()
	{
		return __CLASS__;
	}
}
