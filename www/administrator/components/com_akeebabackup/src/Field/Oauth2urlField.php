<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Field;

defined('_JEXEC') || die;

use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Form field for the custom OAuth2 helper URLs
 */
class Oauth2urlField extends NoteField
{
	protected function getLabel()
	{
		return '';
	}

	protected function getInput()
	{
		$engine = $this->element['engine'] ?? 'example';
		$uri = rtrim(Uri::base() ,'/');

		if (str_ends_with($uri, '/administrator'))
		{
			$uri = substr($uri, 0, -14);
		}

		$text1 = Text::_('COM_AKEEBABACKUP_CONFIG_OAUTH2URLFIELD_YOU_WILL_NEED');
		$text2 = Text::_('COM_AKEEBABACKUP_CONFIG_OAUTH2URLFIELD_CALLBACK_URL');
		$text3 = Text::_('COM_AKEEBABACKUP_CONFIG_OAUTH2URLFIELD_HELPER_URL');
		$text4 = Text::_('COM_AKEEBABACKUP_CONFIG_OAUTH2URLFIELD_REFRESH_URL');

		return <<< HTML
<div class="alert alert-info mx-2 my-2">
	<p>
		$text1
	</p>
	<p>
		<strong>$text2</strong>:
		<br/>
		<code>$uri/index.php?option=com_akeebabackup&view=oauth2&task=step2&format=raw&engine={$engine}</code>
	</p>
	<p>
		<strong>$text3</strong>:
		<br/>
		<code>$uri/index.php?option=com_akeebabackup&view=oauth2&task=step1&format=raw&engine={$engine}</code>
	</p>
	<p>
		<strong>$text4</strong>:
		<br/>
		<code>$uri/index.php?option=com_akeebabackup&view=oauth2&task=refresh&format=raw&engine={$engine}</code>
	</p>
</div>
HTML;

	}
}