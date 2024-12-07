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

use Akeeba\Engine\Core\Domain\Finalization;
use Exception;

/**
 * Interface to a finalizer invokable class.
 *
 * @since 9.3.1
 */
interface FinalizerInterface
{
	/**
	 * Public constructor
	 *
	 * @param   Finalization  $finalizationPart  The part we belong to.
	 *
	 * @since   9.3.1
	 */
	public function __construct(Finalization $finalizationPart);

	/**
	 * Executes the finalizer job. Returns true when done, false if it needs to run further.
	 *
	 * @return  bool  True if we are fully done. False if we must be called again.
	 * @throws  Exception  When an error occurs.
	 *
	 * @since   9.3.1
	 */
	public function __invoke();
}