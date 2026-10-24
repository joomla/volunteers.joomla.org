<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
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