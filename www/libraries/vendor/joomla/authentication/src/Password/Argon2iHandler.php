<?php

/**
 * Part of the Joomla Framework Authentication Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Authentication\Password;

/**
 * Password handler for Argon2i hashed passwords
 *
 * @since  1.2.0
 */
class Argon2iHandler implements HandlerInterface
{
    /**
     * Generate a hash for a plaintext password
     *
     * @param   string  $plaintext  The plaintext password to validate
     * @param   array   $options    Options for the hashing operation
     *
     * @return  string
     *
     * @since   1.2.0
     */
    public function hashPassword($plaintext, array $options = [])
    {
        // Use the password extension if able
        if (\defined('PASSWORD_ARGON2I')) {
            return password_hash($plaintext, \PASSWORD_ARGON2I, $options);
        }

        $hash = sodium_crypto_pwhash_str(
            $plaintext,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );

        sodium_memzero($plaintext);

        return $hash;
    }

    /**
     * Check that the password handler is supported in this environment
     *
     * @return  boolean
     *
     * @since   1.2.0
     */
    public static function isSupported()
    {
        // Check for native PHP engine support in the password extension
        if (\defined('PASSWORD_ARGON2I')) {
            return true;
        }

        // Check for support from the (lib)sodium extension
        return \function_exists('sodium_crypto_pwhash_str');
    }

    /**
     * Validate a password
     *
     * @param   string  $plaintext  The plain text password to validate
     * @param   string  $hashed     The password hash to validate against
     *
     * @return  boolean
     *
     * @since   1.2.0
     */
    public function validatePassword($plaintext, $hashed)
    {
        // Use the password extension if able
        if (\defined('PASSWORD_ARGON2I')) {
            return password_verify($plaintext, $hashed);
        }

        $valid = sodium_crypto_pwhash_str_verify($hashed, $plaintext);
        sodium_memzero($plaintext);

        return $valid;
    }
}
