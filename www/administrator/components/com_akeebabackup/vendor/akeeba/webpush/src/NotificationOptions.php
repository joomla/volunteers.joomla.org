<?php
/**
 * Akeeba WebPush
 *
 * An abstraction layer for easier implementation of WebPush in Joomla components.
 *
 * @copyright Copyright (c) 2022-2026 Akeeba Ltd
 * @license   GNU GPL v3 or later; see LICENSE.txt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\WebPush;

use Joomla\Utilities\ArrayHelper;

/**
 * Abstraction of the notification options recognised by browsers.
 *
 * IMPORTANT! Items marked as `experimental` may NOT work correctly, or at all, on some browsers.
 *
 * @since       1.0.0
 * @see         https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
 * @package     Akeeba\WebPush
 *
 * @property array|null  $actions            An array of actions to display in the notification.
 * @property string|null $badge              URL to a badge icon.
 * @property string|null $body               A string representing an extra content to display within the notification.
 * @property mixed       $data               Arbitrary data that you want to be associated with the notification.
 * @property string|null $dir                The direction of the notification; it can be auto, ltr or rtl.
 * @property string|null $icon               URL of an image to be used as an icon by the notification.
 * @property string|null $image              URL of an image to be displayed in the notification.
 * @property string|null $lang               Specify the language used within the notification.
 * @property bool        $renotify           Whether to suppress vibrations and audible alerts when reusing a tag value.
 * @property bool        $requireInteraction Should the notification remain on screen until dismissed?
 * @property bool        $silent             When set indicates that no sounds or vibrations should be made.
 * @property string|null $tag                A tag to group related notifications.
 * @property int|null    $timestamp          UNIX timestamp IN MILLISECONDS of the date and time applicable to a notification.
 * @property array|null  $vibrate            A vibration pattern to run with the display of the notification.
 */
class NotificationOptions implements \JsonSerializable, \ArrayAccess, \Countable
{
	/**
	 * An array of actions to display in the notification.
	 *
	 * Do note that this also needs support to be added to your Web Push service worker JavaScript file for each
	 * individual action. Actions are more prominent on Android than on desktop; in the latter case you need to click on
	 * the notification to see the actions and that's only if the browser supports actions.
	 *
	 * @since 1.0.0
	 * @var   array|null
	 * @see   https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
	 * @experimental
	 */
	private $actions = null;

	/**
	 * URL to a badge icon.
	 *
	 * This is a string containing the URL of an image to represent the notification when there is not enough space to
	 * display the notification itself such as for example, the Android Notification Bar. On Android devices, the badge
	 * should accommodate devices up to 4x resolution, about 96 by 96 px, and the image will be automatically masked.
	 *
	 * @since 1.0.0
	 * @var   string|null
	 * @experimental
	 */
	private $badge = null;

	/**
	 * A string representing an extra content to display within the notification.
	 *
	 * @since 1.0.0
	 * @var   string|null
	 */
	private $body = null;

	/**
	 * Arbitrary data that you want to be associated with the notification.
	 *
	 * @since 1.0.0
	 * @var   string|int|float|array|\stdClass|\JsonSerializable|null
	 * @experimental
	 */
	private $data = null;

	/**
	 * The direction of the notification; it can be auto, ltr or rtl.
	 *
	 * @since 1.0.0
	 * @var   string|null
	 */
	private $dir = null;

	/**
	 * URL of an image to be used as an icon by the notification.
	 *
	 * @since 1.0.0
	 * @var   string|null
	 */
	private $icon = null;

	/**
	 * URL of an image to be displayed in the notification.
	 *
	 * @since 1.0.0
	 * @var   string|null
	 * @experimental
	 */
	private $image = null;

	/**
	 * Specify the language used within the notification.
	 *
	 * This string must be a valid language tag according to RFC 5646: Tags for Identifying Languages (also known as
	 * BCP 47).
	 *
	 * @since 1.0.0
	 * @var   string|null
	 */
	private $lang = null;

	/**
	 * Whether to suppress vibrations and audible alerts when reusing a tag value.
	 *
	 * If options' renotify is true and optionss tag is the empty string a TypeError will be thrown by the JavaScript.
	 * The default is false.
	 *
	 * @since 1.0.0
	 * @var   bool
	 * @experimental
	 */
	private $renotify = false;

	/**
	 * Should the notification remain on screen until dismissed?
	 *
	 * Indicates that on devices with sufficiently large screens, a notification should remain active until the user
	 * clicks or dismisses it. If this value is absent or false, the desktop version of Chrome will auto-minimize
	 * notifications after approximately twenty seconds. The default value is false.
	 *
	 * @since 1.0.0
	 * @var   bool
	 * @experimental
	 */
	private $requireInteraction = false;

	/**
	 * When set indicates that no sounds or vibrations should be made.
	 *
	 * If options' silent is true and options' vibrate is present the JavaScript will throw a TypeError exception. The
	 * default value is false.
	 *
	 * @since 1.0.0
	 * @var   bool
	 */
	private $silent = false;

	/**
	 * A tag to group related notifications.
	 *
	 * An ID for a given notification that allows you to find, replace, or remove the notification using a script if
	 * necessary.
	 *
	 * @since 1.0.0
	 * @var   string|null
	 */
	private $tag = null;

	/**
	 * UNIX timestamp IN MILLISECONDS of the date and time applicable to a notification.
	 *
	 * Represents the time when the notification was created. It can be used to indicate the time at which a
	 * notification is actual. For example, this could be in the past when a notification is used for a message that
	 * couldn't immediately be delivered because the device was offline, or in the future for a meeting that is about to
	 * start.
	 *
	 * @since 1.0.0
	 * @var   int|null
	 */
	private $timestamp = null;

	/**
	 * A vibration pattern to run with the display of the notification.
	 *
	 * A vibration pattern can be an array with as few as one member. The values are times in milliseconds where the
	 * even indices (0, 2, 4, etc.) indicate how long to vibrate and the odd indices indicate how long to pause. For
	 * example, [300, 100, 400] would vibrate 300ms, pause 100ms, then vibrate 400ms.
	 *
	 * @since 1.0.0
	 * @var   array|null
	 * @experimental
	 */
	private $vibrate = null;

	/**
	 * Magic getter
	 *
	 * @param   string  $name  The property to get
	 *
	 * @return  mixed The property value
	 * @since   1.0.0
	 */
	public function __get($name)
	{
		return $this->offsetGet($name);
	}

	/**
	 * Magic setter
	 *
	 * @param   string  $name   The property to set
	 * @param   mixed   $value  The value to set
	 *
	 * @since   1.0.0
	 */
	public function __set($name, $value)
	{
		$this->offsetSet($name, $value);
	}

	/**
	 * Is a property set?
	 *
	 * @param   string  $name  The name of the property to check
	 *
	 * @return  bool  True if it exists
	 *
	 * @since   1.0.0
	 */
	#[\ReturnTypeWillChange]
	public function __isset($name)
	{
		return $this->offsetExists($name);
	}

	/**
	 * Converts the object to string
	 *
	 * @return  string  The JSON-serialised form of this object
	 *
	 * @since   1.0.0
	 */
	#[\ReturnTypeWillChange]
	public function __toString()
	{
		return json_encode($this);
	}

	/**
	 * Count elements of an object.
	 *
	 * This method only returns the number of top-level elements which will end up in the JSON-serialised format of this
	 * object.
	 *
	 * @return  int  The custom count as an integer.
	 * @since   1.0.0
	 */
	#[\ReturnTypeWillChange]
	public function count()
	{
		return count($this->toArray());
	}

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @return  mixed Data which can be serialized by <b>json_encode</b>.
	 * @since   1.0.0
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * Whether an offset exists
	 *
	 * @param   mixed  $offset  An offset to check for.
	 *
	 * @return  bool True on success
	 * @since   1.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		return property_exists($this, $offset);
	}

	/**
	 * Offset to retrieve
	 *
	 * @param   mixed  $offset  The offset to retrieve.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		if (!$this->offsetExists($offset))
		{
			throw new \InvalidArgumentException(
				sprintf(
					'Class %s does not support array offset %s',
					__CLASS__,
					$offset
				)
			);
		}

		return $this->{$offset};
	}

	/**
	 * Offset to set
	 *
	 * @param   string  $offset  The offset to assign the value to.
	 * @param   mixed   $value   The value to set.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
		switch ($offset)
		{
			case 'actions':
				if ($value !== null && !is_array($value))
				{
					throw new \InvalidArgumentException(sprintf('%s[\'%s\'] must be an array or null', __CLASS__, $offset));
				}
				break;

			case 'body':
			case 'lang':
			case 'tag':
				if ($value !== null && !is_string($value))
				{
					throw new \InvalidArgumentException(sprintf('%s[\'%s\'] must be null or string', __CLASS__, $offset));
				}
				break;

			case 'dir':
				if (($value !== null && !is_string($value)) || !in_array($value, ['auto', 'ltr', 'rtl']))
				{
					throw new \InvalidArgumentException(sprintf('%s[\'%s\'] must be one of "auto", "ltr", "rtl" or null', __CLASS__, $offset));
				}
				break;

			case 'badge':
			case 'icon':
			case 'image':
				$var = filter_var($value, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE);

				if (($value !== null && !is_string($value)) || ($var !== $value))
				{
					throw new \InvalidArgumentException(sprintf('%s[\'%s\'] must be null or a URL', __CLASS__, $offset));
				}
				break;

			case 'renotify':
			case 'requireInteraction':
			case 'silent':
				$value = filter_var($value, FILTER_VALIDATE_BOOL);
				break;

			case 'timestamp':
				$value = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
				break;

			case 'vibrate':
				$value = ArrayHelper::toInteger($value);
				break;
		}

		$this->{$offset} = $value;
	}

	/**
	 * Unsupported
	 *
	 * @param   string  $offset  Ignored.
	 *
	 * @throws  \BadMethodCallException
	 * @since   1.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
		throw new \BadFunctionCallException(
			sprintf(
				'Class %s does not allow unsetting virtual array elements (you tried to unset %s)',
				__CLASS__,
				$offset
			)
		);
	}

	/**
	 * Returns an array with the non-null, non-empty-array arguments.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	public function toArray(): array
	{
		return array_filter(
			get_object_vars($this),
			function ($x) {
				return ($x !== null) && ($x !== []);
			}
		);
	}


}