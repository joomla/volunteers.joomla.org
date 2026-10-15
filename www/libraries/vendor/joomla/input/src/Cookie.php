<?php

/**
 * Part of the Joomla Framework Input Package
 *
 * @copyright  Copyright (C) 2005 - 2022 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Input;

/**
 * Joomla! Input Cookie Class
 *
 * @since  1.0
 */
class Cookie extends Input
{
    /**
     * Constructor.
     *
     * @param   array|null  $source   Source data (Optional, default is $_COOKIE)
     * @param   array       $options  Array of configuration parameters (Optional)
     *
     * @since   1.0
     */
    public function __construct($source = null, array $options = [])
    {
        $source = $source ?? $_COOKIE;
        parent::__construct($source, $options);
    }

    /**
     * Sets a value
     *
     * @param   string $name        Name of the value to set.
     * @param   mixed  $value       Value to assign to the input.
     * @param array    $options     An associative array which may have any of the keys expires, path, domain,
     *                              secure, httponly and samesite. The values have the same meaning as described
     *                              for the parameters with the same name. The value of the samesite element
     *                              should be either Lax or Strict. If any of the allowed options are not given,
     *                              their default values are the same as the default values of the explicit
     *                              parameters. If the samesite element is omitted, no SameSite cookie attribute
     *                              is set.
     *
     * @return  void
     *
     * @link    https://www.ietf.org/rfc/rfc2109.txt
     * @link    https://php.net/manual/en/function.setcookie.php
     *
     * @since   1.0
     */
    public function set($name, $value, $options = [])
    {
        // Set the cookie
        setcookie($name, $value, $options);

        $this->data[$name] = $value;
    }
}
