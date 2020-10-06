<?php
/**
 * @package    CopyMe
 *
 * @author     Carlos Cámara <carlos@hepta.es>
 * @copyright  2019 Hepta Technologies SL
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.hepta.es
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseDriver;

/**
 * Copy Me plugin.
 *
 * @package  Benetrends
 * @since    1.0
 */
class plgContentCopyme extends CMSPlugin
{
	/**
	 * Application object
	 *
	 * @var    CMSApplication
	 * @since  1.0
	 */
	protected $app;

	/**
	 * Database object
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	protected $db;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * replace copy me texts in the document
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   object   &$row     The article object.  Note $article->text is also available
	 * @param   mixed    &$params  The article params
	 * @param   integer  $page     The 'page' number
	 *
	 * @return  void.
	 *
	 * @since   1.0
	 */
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{		
		// Don't run this plugin when the content is being indexed
		if ($context === 'com_finder.indexer')
		{
			return true;
		}

		if (is_object($row))
		{
			return $this->addCopyToClipboard($row->text, $params);
		}

		return $this->addCopyToClipboard($row, $params);
	}

	/**
	 * Replace {copyme} instances inside the document
	 * 
	 * @param	string	$text	Text to find the replacement
	 * @param	object	$params	Plugin parameters
	 * 
	 * @return void
	 */
	protected function addCopyToClipboard(&$text, &$params)
	{
		$regex = '/{copyme\s?(.*?)}/i';

		$closure = "{/copyme}";

		$closureCount = substr_count($text, $closure);

		for ($repeats = 0; $repeats < $closureCount; $repeats++)
		{
			preg_match($regex, $text, $matches);
			$opening = $matches[0];
			$start = strpos($text, $opening);
			$end = stripos($text, $closure, $start);

			$copyMeContent = substr(
				$text,
				$start + strlen($opening),
				$end - strlen($closure) - $start + 1
			);

			$this->replaceTags($copyMeContent);

			$path = JPluginHelper::getLayoutPath('content', 'copyme');
			ob_start();
			include $path;
			$html = ob_get_clean();

			$text = substr_replace($text, $html, $start, $end - $start + strlen($closure));
		}
	}

	/**
	 * Replace custom tags instances inside the text
	 *
	 * @param	string	$text	Text to find the replacement
	 *
	 * @return void
	 */
	protected function replaceTags(&$text)
	{
		$userEmail = CMSFactory::getUser()->email;

		$text = str_replace('{userEmail}', $userEmail, $text);
	}

}