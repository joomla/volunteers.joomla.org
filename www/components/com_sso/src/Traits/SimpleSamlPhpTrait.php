<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Site\Traits;

use SimpleSAML\Auth\Simple;

/**
 * SimpleSAMLphp trait.
 *
 * @package  SSO.Component
 * @since    2.0.0
 */
trait SimpleSamlPhpTrait {

    /**
     * Check if the SimpleSAMLphp library can be found.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function checkSimpleSamlPhp(): void
    {
        if (!defined('PATH_SIMPLESAMLPHP'))
        {
            define('PATH_SIMPLESAMLPHP', JPATH_LIBRARIES .'/simplesamlphp');
        }

        if (!class_exists('SimpleSAML_Configuration'))
        {
            include_once PATH_SIMPLESAMLPHP . '/vendor/autoload.php';
        }
    }

    /**
     * Load SimpleSAMLphp.
     *
     * @param   string  $profile
     *
     * @return  Simple
     *
     * @since   1.0.0
     */
    public function loadSimpleSamlPhp(string $profile): Simple
    {
        $this->checkSimpleSamlPhp();

        return (new Simple($profile));
    }
}
