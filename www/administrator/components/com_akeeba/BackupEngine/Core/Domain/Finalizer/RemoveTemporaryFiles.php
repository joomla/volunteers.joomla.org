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
 * Removes temporary files.
 *
 * @since       9.3.1
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 *
 */
final class RemoveTemporaryFiles extends AbstractFinalizer
{
	/**
	 * @inheritDoc
	 */
	public function __invoke()
	{
		$this->setStep('Removing temporary files');
		$this->setSubstep('');
		Factory::getLog()->debug("Removing temporary files");
		Factory::getTempFiles()->deleteTempFiles();

		return true;
	}
}