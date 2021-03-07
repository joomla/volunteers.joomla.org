<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Render;

defined('_JEXEC') || die;

use FOF40\Container\Container;

/**
 * Renderer class for use with Akeeba FEF
 *
 * Renderer options
 *
 * wrapper_id           The ID of the wrapper DIV. Default: akeeba-rendered-fef
 * linkbar_style        Style for linkbars: joomla3|classic. Default: joomla3
 * load_fef             Load FEF CSS? Set to false if you are loading it outside the renderer. Default: true
 * load_fef_js          Load FEF JS? Set to false if you are loading it outside the rendered. Default: true
 * load_fef_js_minimal  Load the minimal FEF JS (without features depending on FEF CSS)? Default: inverse of load_fef
 * fef_reset            Should I reset the CSS styling for basic HTML elements inside the FEF container? Default: true
 * fef_dark             Should I load the FEF Dark Mode CSS and supporting JS? Default: 0 (no). Options: 1 (yes and
 *                      activate immediately), -1 (include dark.css but not enable by default, also enables auto mode
 *                      for Safari)
 * custom_css           Comma-separated list of custom CSS files to load _after_ the main FEF CSS file, e.g.
 *                      media://com_foo/css/bar.min.css,media://com_foo/css/baz.min.css
 * remove_wrapper_classes  Comma-separated list of classes to REMOVE from the container
 * add_wrapper_classes     Comma-separated list of classes to ADD to the container
 *
 * Note: when Dark Mode is enabled the class akeeba-renderer-fef--dark is applied to the container DIV. You can use
 * remove_wrapper_classes to remove it e.g. when you want it to be enabled only through a JavaScript-powered toggle.
 *
 * @package FOF40\Render
 */
class FEF extends Joomla
{
	public function __construct(Container $container)
	{
		parent::__construct($container);

		$helperFile = JPATH_SITE . '/media/fef/fef.php';

		if (!class_exists('AkeebaFEFHelper') && is_file($helperFile))
		{
			include_once $helperFile;
		}

		$this->priority = 20;
		$this->enabled  = class_exists('AkeebaFEFHelper');
	}

	public function initialise(string $view, string $task): void
	{
		$useReset    = $this->getOption('fef_reset', 1);
		$useFefCss   = $this->getOption('load_fef', 1);
		$useFefJs    = $this->getOption('load_fef_js', 1);
		$minimalJs   = $this->getOption('load_fef_js_minimal', $useFefCss ? 0 : 1);
		$useDarkMode = $this->getOption('fef_dark', 0);

		if (class_exists('AkeebaFEFHelper'))
		{
			if ($useFefCss)
			{
				\AkeebaFEFHelper::loadCSSFramework((bool) $useReset, (bool) $useDarkMode != 0);
			}

			if ($useFefJs)
			{
				\AkeebaFEFHelper::loadJSFramework((bool) $minimalJs);
			}
		}

		// Unlike the Joomla renderer we do NOT load jQuery unless explicitly enabled
		$loadJQuery = $this->getOption('load_jquery', false);
		$this->setOption('load_jquery', $loadJQuery ? 1 : 0);

		parent::initialise($view, $task);
	}

	/**
	 * Opens the FEF styling wrapper element. Our component's output will be inside this wrapper.
	 *
	 * @param   array  $classes  An array of additional CSS classes to add to the outer page wrapper element.
	 *
	 * @return  void
	 */
	protected function openPageWrapper(array $classes): void
	{
		$useDarkMode = $this->getOption('fef_dark', false);

		if (($useDarkMode == 1) && !in_array('akeeba-renderer-fef--dark', $classes))
		{
			$classes[] = 'akeeba-renderer-fef--dark';
		}

		/**
		 * Remove wrapper classes. By default these are classes for the Joomla 3 sidebar which is not used in FEF
		 * components anymore.
		 */
		$removeClasses = $this->getOption('remove_wrapper_classes', [
			'j-toggle-main',
			'j-toggle-transition',
			'row-fluid',
		]);

		if (!is_array($removeClasses))
		{
			$removeClasses = explode(',', $removeClasses);
		}

		$removeClasses = array_map('trim', $removeClasses);

		foreach ($removeClasses as $class)
		{
			$x = array_search($class, $classes);

			if ($x !== false)
			{
				unset($classes[$x]);
			}
		}

		// Add the following classes to the wrapper div
		$addClasses = $this->getOption('add_wrapper_classes', '');

		if (!is_array($addClasses))
		{
			$addClasses = explode(',', $addClasses);
		}

		$addClasses    = array_map('trim', $addClasses);
		$customClasses = implode(' ', array_unique(array_merge($classes, $addClasses)));

		$id = $this->getOption('wrapper_id', 'akeeba-renderer-fef');
		$id = empty($id) ? "" : sprintf(' id="%s"', $id);

		echo <<< HTML
<div id="akeeba-renderer-fef" class="akeeba-renderer-fef $customClasses"$id>

HTML;
	}

	/**
	 * Close the FEF styling wrapper element.
	 *
	 * @return  void
	 */
	protected function closePageWrapper(): void
	{
		echo <<< HTML
</div>

HTML;

	}
}
