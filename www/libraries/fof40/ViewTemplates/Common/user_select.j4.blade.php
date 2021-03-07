<?php
/**
 * @package     FOF
 * @copyright   Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 3 or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;

/**
 * User entry field, allowing selection of a user from a modal dialog
 *
 * Use this by extending it (I'm using -at- instead of the actual at-sign)
 * -at-include('any:lib_fof40/Common/user_select', $params)
 *
 * This is the generic variant used in Joomla 4 (when NOT using the FEF renderer)
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
$placeholder = isset($placeholder) ? Text::_($placeholder) : Text::_('JLIB_FORM_SELECT_USER');
$userID      = $item->getFieldValue($field, 0);
$user        = $item->getContainer()->platform->getUser($userID);
$width       = isset($width) ? $width : 800;
$height      = isset($height) ? $height : 500;
$class       = isset($class) ? $class : '';
$size        = isset($size) ? $size : 0;
$onchange    = isset($onchange) ? $onchange : '';

?>
@jlayout('joomla/form/field/user', [
'name' => $field,
'id' => $id,
'class' => $class,
'size' => $size,
'value' => $userID,
'userName' => $user->name,
'hint' => $placeholder,
'readonly' => $readonly,
'required' => $required,
'onchange' => $onchange,
'dataAttribute' => '',
])