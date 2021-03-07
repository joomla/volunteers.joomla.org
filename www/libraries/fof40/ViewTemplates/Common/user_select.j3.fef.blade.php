<?php
/**
 * @package     FOF
 * @copyright   Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 3 or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * User entry field, allowing selection of a user from a modal dialog
 *
 * Use this by extending it (I'm using -at- instead of the actual at-sign)
 * -at-include('any:lib_fof40/Common/user_select', $params)
 *
 * This is the variant used when using the FEF renderer under Joomla 3.
 *
 * $params is an array defining the following keys (they are expanded into local scope vars automatically):
 *
 * @var string                  $field       The user field's name, e.g. "user_id"
 * @var \FOF40\Model\DataModel  $item        The item we're editing. The user ID is stored in $item->{$field}
 * @var string                  $id          The id of the field, default is $field
 * @var bool                    $readonly    Is this a read only field? Default: false
 * @var string                  $placeholder Placeholder text, also used as the button's tooltip
 * @var bool                    $required    Is a value required for this field? Default: false
 * @var string                  $width       Width of the modal box (default: 800)
 * @var string                  $height      Height of the modal box (default: 500)
 *
 * Variables made automatically available to us by FOF:
 *
 * @var \FOF40\View\DataView\DataViewInterface $this
 */

$id          = isset($id) ? $id : $field;
$readonly    = isset($readonly) ? ($readonly ? true : false) : false;
$placeholder = isset($placeholder) ? JText::_($placeholder) : JText::_('JLIB_FORM_SELECT_USER');
$userID      = $item->getFieldValue($field, 0);
$user        = $item->getContainer()->platform->getUser($userID);
$width       = isset($width) ? $width : 800;
$height      = isset($height) ? $height : 500;
$class       = isset($class) ? $class : '';
$size        = isset($size) ? $size : 0;

$uri = new JUri('index.php?option=com_users&view=users&layout=modal&tmpl=component');
$uri->setVar('required', (isset($required) ? ($required ? 1 : 0) : 0));
$uri->setVar('field', $field);
$url = 'index.php' . $uri->toString(['query']);
?>

@unless($readonly)
	@jhtml('behavior.modal', 'a.userSelectModal_' . $this->escape($field))
	@jhtml('script', 'jui/fielduser.min.js', ['version' => 'auto', 'relative' => true])
@endunless

<div class="akeeba-input-group">
	<input readonly type="text"
		   id="{{{ $field }}}" value="{{{ $user->username }}}"
		   placeholder="{{{ $placeholder }}}"/>
	<span class="akeeba-input-group-btn">
	<a href="@route($url)"
	   class="akeeba-btn--grey userSelectModal_{{{ $field }}}" title="{{{ $placeholder }}}"
	   rel="{handler: 'iframe', size: {x: {{$width}}, y: {{$height}} }}">
		<span class="akion-person"></span>
	</a>
</span>
</div>
@unless($readonly)
	<input type="hidden" id="{{{ $field }}}_id" name="{{{ $field }}}" value="{{ (int) $userID }}"/>
@endunless
