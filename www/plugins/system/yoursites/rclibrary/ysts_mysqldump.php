<?php

/**
 * @version    CVS: 1.65.0
 * @package    com_yoursites
 * @author     Geraint Edwards <via website>
 * @copyright  2016-YOURSITES_COPYRIGHT GWE Systems Ltd
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ifsnop\Mysqldump;

defined('_JEXEC') or die;

include_once( __DIR__ . "/Mysqldump.php" );

class Ysts_Mysqldump extends Mysqldump {

    const CLEANED = 'Cleaned';

    /**
     * Table rows extractor - YourSites custom version that outputs a blank line every 50 rows.
     *
     * @param   string  $tableName  Name of table to export
     *
     * @return null
     */
    private function XXXlistValues( $tableName ) {
    }

}

CompressMethod::$enums[] = Ysts_Mysqldump::CLEANED;

class CompressCleaned extends CompressNone
{

    public static $oldPrefix;
    public static $newPrefix;

    public function write($str)
    {

        //if (strpos($str, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;") !== false)
        //{
        //   return 0;
        // }
        //if (strpos($str, "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;") !== false)
        //{
        //   return 0;
        //}

        // GWE MOD
        $str = str_replace(self::$oldPrefix, self::$newPrefix, $str);

        return parent::write($str);
    }

}