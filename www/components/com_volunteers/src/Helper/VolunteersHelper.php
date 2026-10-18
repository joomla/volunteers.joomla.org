<?php

/**
 * @package    Com_Volunteers
 * @version    4.0.0
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Volunteers\Administrator\Model\MemberModel;
use Joomla\Component\Volunteers\Administrator\Model\PositionModel;
use Joomla\Component\Volunteers\Administrator\Service\AclService;
use Joomla\Component\Volunteers\Administrator\Service\VolunteersService;
use Joomla\Component\Volunteers\Site\Model\TeamModel;
use Joomla\Component\Volunteers\Site\Model\VolunteerModel;
use RuntimeException;
use stdClass;

/**
 * Class VolunteersHelper
 *
 * @since  4.0.0
 */
class VolunteersHelper
{
    public static array $countries = [
        'AD' => 'Andorra',
        'AE' => 'United Arab Emirates',
        'AF' => 'Afghanistan',
        'AG' => 'Antigua and Barbuda',
        'AI' => 'Anguilla',
        'AL' => 'Albania',
        'AM' => 'Armenia',
        'AO' => 'Angola',
        'AQ' => 'Antarctica',
        'AR' => 'Argentina',
        'AS' => 'American Samoa',
        'AT' => 'Austria',
        'AU' => 'Australia',
        'AW' => 'Aruba',
        'AX' => 'Aland Islands',
        'AZ' => 'Azerbaijan',
        'BA' => 'Bosnia and Herzegovina',
        'BB' => 'Barbados',
        'BD' => 'Bangladesh',
        'BE' => 'Belgium',
        'BF' => 'Burkina Faso',
        'BG' => 'Bulgaria',
        'BH' => 'Bahrain',
        'BI' => 'Burundi',
        'BJ' => 'Benin',
        'BL' => 'Saint Barthélemy',
        'BM' => 'Bermuda',
        'BN' => 'Brunei Darussalam',
        'BO' => 'Bolivia, Plurinational State of',
        'BQ' => 'Bonaire, Saint Eustatius and Saba',
        'BR' => 'Brazil',
        'BS' => 'Bahamas',
        'BT' => 'Bhutan',
        'BV' => 'Bouvet Island',
        'BW' => 'Botswana',
        'BY' => 'Belarus',
        'BZ' => 'Belize',
        'CA' => 'Canada',
        'CC' => 'Cocos (Keeling) Islands',
        'CD' => 'Congo, the Democratic Republic of the',
        'CF' => 'Central African Republic',
        'CG' => 'Congo',
        'CH' => 'Switzerland',
        'CI' => 'Cote d\'Ivoire',
        'CK' => 'Cook Islands',
        'CL' => 'Chile',
        'CM' => 'Cameroon',
        'CN' => 'China',
        'CO' => 'Colombia',
        'CR' => 'Costa Rica',
        'CU' => 'Cuba',
        'CV' => 'Cape Verde',
        'CW' => 'Curaçao',
        'CX' => 'Christmas Island',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DE' => 'Germany',
        'DJ' => 'Djibouti',
        'DK' => 'Denmark',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'DZ' => 'Algeria',
        'EC' => 'Ecuador',
        'EE' => 'Estonia',
        'EG' => 'Egypt',
        'EH' => 'Western Sahara',
        'ER' => 'Eritrea',
        'ES' => 'Spain',
        'ET' => 'Ethiopia',
        'FI' => 'Finland',
        'FJ' => 'Fiji',
        'FK' => 'Falkland Islands (Malvinas)',
        'FM' => 'Micronesia, Federated States of',
        'FO' => 'Faroe Islands',
        'FR' => 'France',
        'GA' => 'Gabon',
        'GB' => 'United Kingdom',
        'GD' => 'Grenada',
        'GE' => 'Georgia',
        'GF' => 'French Guiana',
        'GG' => 'Guernsey',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GL' => 'Greenland',
        'GM' => 'Gambia',
        'GN' => 'Guinea',
        'GP' => 'Guadeloupe',
        'GQ' => 'Equatorial Guinea',
        'GR' => 'Greece',
        'GS' => 'South Georgia and the South Sandwich Islands',
        'GT' => 'Guatemala',
        'GU' => 'Guam',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HK' => 'Hong Kong',
        'HM' => 'Heard Island and McDonald Islands',
        'HN' => 'Honduras',
        'HR' => 'Croatia',
        'HT' => 'Haiti',
        'HU' => 'Hungary',
        'ID' => 'Indonesia',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IM' => 'Isle of Man',
        'IN' => 'India',
        'IO' => 'British Indian Ocean Territory',
        'IQ' => 'Iraq',
        'IR' => 'Iran, Islamic Republic of',
        'IS' => 'Iceland',
        'IT' => 'Italy',
        'JE' => 'Jersey',
        'JM' => 'Jamaica',
        'JO' => 'Jordan',
        'JP' => 'Japan',
        'KE' => 'Kenya',
        'KG' => 'Kyrgyzstan',
        'KH' => 'Cambodia',
        'KI' => 'Kiribati',
        'KM' => 'Comoros',
        'KN' => 'Saint Kitts and Nevis',
        'KP' => 'Korea, Democratic People\'s Republic of',
        'KR' => 'Korea, Republic of',
        'KW' => 'Kuwait',
        'KY' => 'Cayman Islands',
        'KZ' => 'Kazakhstan',
        'LA' => 'Lao People\'s Democratic Republic',
        'LB' => 'Lebanon',
        'LC' => 'Saint Lucia',
        'LI' => 'Liechtenstein',
        'LK' => 'Sri Lanka',
        'LR' => 'Liberia',
        'LS' => 'Lesotho',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'LV' => 'Latvia',
        'LY' => 'Libyan Arab Jamahiriya',
        'MA' => 'Morocco',
        'MC' => 'Monaco',
        'MD' => 'Moldova, Republic of',
        'ME' => 'Montenegro',
        'MF' => 'Saint Martin (French part)',
        'MG' => 'Madagascar',
        'MH' => 'Marshall Islands',
        'MK' => 'Macedonia, the former Yugoslav Republic of',
        'ML' => 'Mali',
        'MM' => 'Myanmar',
        'MN' => 'Mongolia',
        'MO' => 'Macao',
        'MP' => 'Northern Mariana Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MS' => 'Montserrat',
        'MT' => 'Malta',
        'MU' => 'Mauritius',
        'MV' => 'Maldives',
        'MW' => 'Malawi',
        'MX' => 'Mexico',
        'MY' => 'Malaysia',
        'MZ' => 'Mozambique',
        'NA' => 'Namibia',
        'NC' => 'New Caledonia',
        'NE' => 'Niger',
        'NF' => 'Norfolk Island',
        'NG' => 'Nigeria',
        'NI' => 'Nicaragua',
        'NL' => 'Netherlands',
        'NO' => 'Norway',
        'NP' => 'Nepal',
        'NR' => 'Nauru',
        'NU' => 'Niue',
        'NZ' => 'New Zealand',
        'OM' => 'Oman',
        'PA' => 'Panama',
        'PE' => 'Peru',
        'PF' => 'French Polynesia',
        'PG' => 'Papua New Guinea',
        'PH' => 'Philippines',
        'PK' => 'Pakistan',
        'PL' => 'Poland',
        'PM' => 'Saint Pierre and Miquelon',
        'PN' => 'Pitcairn',
        'PR' => 'Puerto Rico',
        'PS' => 'Palestinian Territory, Occupied',
        'PT' => 'Portugal',
        'PW' => 'Palau',
        'PY' => 'Paraguay',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RS' => 'Serbia',
        'RU' => 'Russian Federation',
        'RW' => 'Rwanda',
        'SA' => 'Saudi Arabia',
        'SB' => 'Solomon Islands',
        'SC' => 'Seychelles',
        'SD' => 'Sudan',
        'SE' => 'Sweden',
        'SG' => 'Singapore',
        'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
        'SI' => 'Slovenia',
        'SJ' => 'Svalbard and Jan Mayen',
        'SK' => 'Slovakia',
        'SL' => 'Sierra Leone',
        'SM' => 'San Marino',
        'SN' => 'Senegal',
        'SO' => 'Somalia',
        'SR' => 'Suriname',
        'ST' => 'Sao Tome and Principe',
        'SV' => 'El Salvador',
        'SX' => 'Sint Maarten',
        'SY' => 'Syrian Arab Republic',
        'SZ' => 'Swaziland',
        'TC' => 'Turks and Caicos Islands',
        'TD' => 'Chad',
        'TF' => 'French Southern Territories',
        'TG' => 'Togo',
        'TH' => 'Thailand',
        'TJ' => 'Tajikistan',
        'TK' => 'Tokelau',
        'TL' => 'Timor-Leste',
        'TM' => 'Turkmenistan',
        'TN' => 'Tunisia',
        'TO' => 'Tonga',
        'TR' => 'Turkey',
        'TT' => 'Trinidad and Tobago',
        'TV' => 'Tuvalu',
        'TW' => 'Taiwan',
        'TZ' => 'Tanzania, United Republic of',
        'UA' => 'Ukraine',
        'UG' => 'Uganda',
        'UM' => 'United States Minor Outlying Islands',
        'US' => 'United States',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VA' => 'Holy See (Vatican City State)',
        'VC' => 'Saint Vincent and the Grenadines',
        'VE' => 'Venezuela, Bolivarian Republic of',
        'VG' => 'Virgin Islands, British',
        'VI' => 'Virgin Islands, U.S.',
        'VN' => 'Viet Nam',
        'VU' => 'Vanuatu',
        'WF' => 'Wallis and Futuna',
        'WS' => 'Samoa',
        'YE' => 'Yemen',
        'YT' => 'Mayotte',
        'ZA' => 'South Africa',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    ];

    /**
     * Displays Volunteer Block
     *
     * @param $volunteer
     *
     *
     * @since 4.0.0
     */
    public static function outputVolunteer($volunteer)
    {
        /** @var VolunteersService $volunteersService */
        $volunteersService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(VolunteersService::class);
        echo $volunteersService->outputVolunteer($volunteer);
    }

    /**
     * Displays Volunteer Block Horizontaly
     *
     * @param $volunteer
     *
     *
     * @since 4.0.0
     */
    public static function outputHorizontalVolunteer($volunteer)
    {
        echo '<a href="' . Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->id) . '">';
        echo self::image($volunteer->image, 'small', false, $volunteer->name, 'joomlers rounded w-100');
        echo '</a>';
        echo '<h4 class="text-center vol_h4">';
        echo '<a href="' . Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->id) . '">';
        echo $volunteer->name;
        echo '</a>';
        echo '</h4>';
    }

    /**
     * @param $type
     * @param $id
     *
     * @return stdClass
     *
     * @since version
     * @throws Exception
     */
    public static function acl($type, $id)
    {
        /** @var AclService $aclService */
        $aclService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        return $aclService->getAcl($type, (int) $id);
    }

    /**
     * @param $date
     * @param $format
     *
     * @return string
     *
     * @since version
     */
    public static function date($date, $format)
    {
        /** @var VolunteersService $volunteersService */
        $volunteersService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(VolunteersService::class);
        return $volunteersService->formatDate($date, $format);
    }

    /**
     * Creates a list of active departments.
     *
     * @return  array  An array containing the departments that can be selected.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public static function departments($prefix = false)
    {
        /** @var VolunteersService $volunteersService */
        $volunteersService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(VolunteersService::class);
        return $volunteersService->getDepartments($prefix);
    }

    /**
     * @param                $image
     * @param                $size
     * @param   bool         $urlonly
     * @param   string|null  $alt
     * @param   string       $class
     *
     * @return mixed|string
     *
     * @since version
     */
    public static function image($image, $size, bool $urlonly = false, string|null $alt = '', string $class = 'rounded')
    {
        /** @var VolunteersService $volunteersService */
        $volunteersService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(VolunteersService::class);
        return $volunteersService->getImage((string) $image, $size, $urlonly, $alt, $class);
    }

    /**
     * @param $country
     * @param $city
     *
     * @return string
     *
     * @since version
     */
    public static function location($country = null, $city = null)
    {
        /** @var VolunteersService $volunteersService */
        $volunteersService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(VolunteersService::class);
        return $volunteersService->formatLocation($country, $city);
    }

    /**
     * Creates a list of active positions.
     *
     * @return  array  An array containing the positions that can be selected.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public static function positions()
    {
        /** @var VolunteersService $volunteersService */
        $volunteersService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(VolunteersService::class);
        return $volunteersService->getPositions();
    }

    /**
     * Creates a list of departments and teams.
     *
     * @return  array  An array containing the departments and teams that can be selected.
     *
     * @since 4.0.0
     *
     * @throws Exception
     */
    public static function reportcategories()
    {
        /** @var VolunteersService $volunteersService */
        $volunteersService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(VolunteersService::class);
        return $volunteersService->getReportCategories();
    }

    /**
     * Creates a list of active roles.
     *
     * @return  array  An array containing the positions that can be selected.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public static function roles($team = null)
    {
        /** @var VolunteersService $volunteersService */
        $volunteersService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(VolunteersService::class);
        return $volunteersService->getRoles($team ? (int) $team : null);
    }

    /**
     * Creates a list of active teams.
     *
     * @return  array  An array containing the teams that can be selected.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public static function teams($parent = false, $prefix = false)
    {
        /** @var VolunteersService $volunteersService */
        $volunteersService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(VolunteersService::class);
        return $volunteersService->getTeams((bool) $parent, (bool) $prefix);
    }

    /**
     * Creates a list of active volunteers.
     *
     * @return  array  An array containing the volunteers that can be selected.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public static function volunteers()
    {
        /** @var VolunteersService $volunteersService */
        $volunteersService = Factory::getApplication()->bootComponent('com_volunteers')->getContainer()->get(VolunteersService::class);
        return $volunteersService->getVolunteers();
    }
}
