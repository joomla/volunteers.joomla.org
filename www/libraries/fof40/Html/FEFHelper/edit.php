<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory as JoomlaFactory;

/**
 * Custom JHtml (HTMLHelper) class. Offers edit (form) view controls compatible with Akeeba Frontend
 * Framework (FEF).
 *
 * Call these methods as HTMLHelper::_('FEFHelp.edit.methodName', $parameter1, $parameter2, ...)
 */
abstract class FEFHelpEdit
{
	public static function editor(string $fieldName, ?string $value, array $params = []): string
	{
		$params = array_merge([
			'id'         => null,
			'editor'     => null,
			'width'      => '100%',
			'height'     => 500,
			'columns'    => 50,
			'rows'       => 20,
			'created_by' => null,
			'asset_id'   => null,
			'buttons'    => true,
			'hide'       => false,
		], $params);

		$editorType = $params['editor'];

		if (is_null($editorType))
		{
			$editorType = JoomlaFactory::getConfig()->get('editor');
			$user   = JoomlaFactory::getUser();

			if (!$user->guest)
			{
				$editorType = $user->getParam('editor', $editorType);
			}
		}

		if (is_null($params['id']))
		{
			$params['id'] = $fieldName;
		}

		$editor = Editor::getInstance($editorType);

		return $editor->display($fieldName, $value, $params['width'], $params['height'],
			$params['columns'],  $params['rows'], $params['buttons'], $params['id'],
			$params['asset_id'], $params['created_by'], $params);
	}
}
