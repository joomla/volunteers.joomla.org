<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\View\Compiler;

defined('_JEXEC') || die;

interface CompilerInterface
{
	/**
	 * Are the results of this compiler engine cacheable? If the engine makes use of the forcedParams it must return
	 * false.
	 *
	 * @return  bool
	 */
	public function isCacheable(): bool;

	/**
	 * Compile a view template into PHP and HTML
	 *
	 * @param string $path        The absolute filesystem path of the view template
	 * @param array  $forceParams Any parameters to force (only for engines returning raw HTML)
	 *
	 * @return mixed
	 */
	public function compile(string $path, array $forceParams = []);

	/**
	 * Returns the file extension supported by this compiler
	 *
	 * @return  string
	 *
	 * @since   3.3.1
	 */
	public function getFileExtension(): string;
}
