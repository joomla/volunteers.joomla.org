<?php
/**
 * @package     FOF
 * @copyright   Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 3 or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;use Joomla\CMS\Language\Text;use Joomla\CMS\Uri\Uri;

/**
 * User entry field, allowing selection of a user from a modal dialog
 *
 * Use this by extending it (I'm using -at- instead of the actual at-sign)
 * -at-include('any:lib_fof40/Common/user_select', $params)
 *
 * This is the variant used when using the FEF renderer under Joomla 4.
 *
 * $params is an array defining the following keys (they are expanded into local scope vars automatically):
 *
 * @var   string                 $field           The user field's name, e.g. "user_id"
 * @var   \FOF40\Model\DataModel $item            The item we're editing. The user ID is stored in $item->{$field}
 * @var   string                 $id              The id of the field, default is $field
 * @var   bool                   $readonly        Is this a read only field? Default: false
 * @var   string                 $placeholder     Placeholder text, also used as the button's tooltip
 * @var   bool                   $required        Is a value required for this field? Default: false
 * @var   string                 $width           Width of the modal box (default: 800)
 * @var   string                 $height          Height of the modal box (default: 500)
 * @var   string                 $autocomplete    Autocomplete attribute for the field.
 * @var   boolean                $autofocus       Is autofocus enabled?
 * @var   string                 $class           Classes for the input.
 * @var   string                 $description     Description of the field.
 * @var   boolean                $disabled        Is this field disabled?
 * @var   string                 $group           Group the field belongs to. <fields> section in form XML.
 * @var   boolean                $hidden          Is this field hidden in the form?
 * @var   string                 $id              DOM id of the field.
 * @var   string                 $label           Label of the field.
 * @var   string                 $labelclass      Classes to apply to the label.
 * @var   boolean                $multiple        Does this field support multiple values?
 * @var   string                 $onchange        Onchange attribute for the field.
 * @var   string                 $onclick         Onclick attribute for the field.
 * @var   string                 $pattern         Pattern (Reg Ex) of value of the form field.
 * @var   boolean                $readonly        Is this field read only?
 * @var   boolean                $repeat          Allows extensions to duplicate elements.
 * @var   boolean                $required        Is this field required?
 * @var   integer                $size            Size attribute of the input.
 * @var   boolean                $spellcheck      Spellcheck state for the form field.
 * @var   string                 $validate        Validation rules to apply.
 * @var   mixed                  $groups          The filtering groups (null means no filtering)
 * @var   mixed                  $excluded        The users to exclude from the list of users
 * @var   string                 $dataAttribute   Miscellaneous data attributes preprocessed for HTML output
 * @var   array                  $dataAttributes  Miscellaneous data attribute for eg, data-*.
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
$userName    = (is_object($user) && ($user instanceof \Joomla\CMS\User\User) && !$user->guest) ? $user->name : Text::_('JLIB_FORM_SELECT_USER');

if (!$readonly)
{
	Factory::getDocument()->getWebAssetManager()
		->useScript('webcomponent.field-user');
}

$uri = new Uri('index.php?option=com_users&view=users&layout=modal&tmpl=component&required=0');

$uri->setVar('field', $this->escape($id));

if ($required)
{
	$uri->setVar('required', 1);
}

if (!empty($groups))
{
	$uri->setVar('groups', base64_encode(json_encode($groups)));
}

if (!empty($excluded))
{
	$uri->setVar('excluded', base64_encode(json_encode($excluded)));
}

// Invalidate the input value if no user selected
if ($this->escape($userName) === Text::_('JLIB_FORM_SELECT_USER'))
{
	$userName = '';
}

$inputAttributes = array(
	'type' => 'text', 'id' => $id, 'class' => 'form-control field-user-input-name', 'value' => $this->escape($userName)
);
if ($class)
{
	$inputAttributes['class'] .= ' ' . $class;
}
if ($size)
{
	$inputAttributes['size'] = (int) $size;
}
if ($required)
{
	$inputAttributes['required'] = 'required';
}
if (!$readonly)
{
	$inputAttributes['placeholder'] = $placeholder;
}
?>
<?php // Create a dummy text field with the user name. ?>
<joomla-field-user class="field-user-wrapper"
                   url="<?php echo (string) $uri; ?>"
                   modal=".modal"
                   modal-width="100%"
                   modal-height="400px"
                   input=".field-user-input"
                   input-name=".field-user-input-name"
                   button-select=".userSelectModal_{{{ $field }}}">
    <div class="akeeba-input-group">
        <input {{ \FOF40\Utils\ArrayHelper::toString($inputAttributes) }} {{ $dataAttribute ?? '' }} readonly>
		@if (!$readonly)
		<span class="akeeba-input-group-btn">
			<button type="button"
					class="akeeba-btn--grey userSelectModal_{{{ $field }}}" title="{{{ $placeholder }}}">
				<span class="akion-person" aria-hidden="true" aria-label="{{ $placeholder }}"></span>
        	</button>
		</span>
	@endif
    </div>
	{{-- Create the real field, hidden, that stored the user id.--}}
	@if(!$readonly)
    <input type="hidden" id="{{ $id }}_id" name="{{ $field }}"
           value="{{{ $userID }}}"
           class="field-user-input {{ $class ? (string) $class : '' }}"
           data-onchange="{{{ $onchange }}}">
	@jhtml(
		'bootstrap.renderModal',
		'userModal_' . $id,
		array(
			'url'         => $uri,
			'title'       => $placeholder,
			'closeButton' => true,
			'height'      => '100%',
			'width'       => '100%',
			'modalWidth'  => $width / 10,
			'bodyHeight'  => $height / 10,
			'footer'      => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Text::_('JCANCEL') . '</button>',
		)
	)
	@endif
</joomla-field-user>
