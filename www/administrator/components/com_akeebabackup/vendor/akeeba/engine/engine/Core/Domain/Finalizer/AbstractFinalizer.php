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
use Akeeba\Engine\Psr\Log\LogLevel;

/**
 * Abstract implementation of a finalizer class
 *
 * @since       9.3.1
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 */
abstract class AbstractFinalizer implements FinalizerInterface
{
	/**
	 * The part we belong to
	 *
	 * @since 9.3.1
	 * @var   Finalization
	 */
	private $finalizationPart;

	/**
	 * Public constructor
	 *
	 * @param   Finalization  $finalizationPart  The part we belong to.
	 *
	 * @since   9.3.1
	 */
	public function __construct(Finalization $finalizationPart)
	{
		$this->finalizationPart = $finalizationPart;
	}

	/**
	 * Relays an exception so it can be logged
	 *
	 * @param   \Throwable  $e
	 * @param   string      $logLevel
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	protected function logErrorsFromException(\Throwable $e, string $logLevel = LogLevel::ERROR): void
	{
		$this->finalizationPart->relayException($e, $logLevel);
	}

	/**
	 * Relays the current step back to the parent finalization engine part
	 *
	 * @param   string  $step  The step name to set
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	protected function setStep(string $step): void
	{
		$this->finalizationPart->relayStep($step);
	}

	/**
	 * Relays the current sub-step back to the parent finalization engine part
	 *
	 * @param   string  $substep  The sub-step name to set
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	protected function setSubstep(string $substep): void
	{
		$this->finalizationPart->relaySubstep($substep);
	}
}