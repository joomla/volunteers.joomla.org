<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Date\Date;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use stdClass;

/**
 * Volunteers Service for Volunteers Component.
 *
 * @since  6.1.0
 */
class VolunteersService
{
    /**
     * @var DatabaseInterface
     */
    protected $db;

    /**
     * @var CMSApplicationInterface
     */
    protected $app;

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
     * Constructor.
     *
     * @param   DatabaseInterface        $db   The database driver
     * @param   CMSApplicationInterface  $app  The application
     */
    public function __construct(DatabaseInterface $db, CMSApplicationInterface $app)
    {
        $this->db  = $db;
        $this->app = $app;
    }

    /**
     * Creates a list of active departments.
     *
     * @param   bool  $prefix  Whether to prefix the ID with 'd.'
     *
     * @return  array|null
     */
    public function getDepartments(bool $prefix = false): ?array
    {
        $query = $this->db->getQuery(true);

        if ($prefix) {
            $query->select(['CONCAT(\'d.\', ' . $this->db->quoteName('id') . ') AS value', $this->db->quoteName('title', 'text')]);
        } else {
            $query->select([$this->db->quoteName('id', 'value'), $this->db->quoteName('title', 'text')]);
        }

        $query->from($this->db->quoteName('#__volunteers_departments'))
            ->where($this->db->quoteName('state') . ' = 1')
            ->order($this->db->quoteName('title') . ' ASC');

        $this->db->setQuery($query);

        try {
            return $this->db->loadObjectList();
        } catch (RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'warning');
        }

        return null;
    }

    /**
     * Creates a list of active teams.
     *
     * @param   bool  $parent  Whether to exclude the current team ID from the list
     * @param   bool  $prefix  Whether to prefix the ID with 't.'
     *
     * @return  array|null
     */
    public function getTeams(bool $parent = false, bool $prefix = false): ?array
    {
        $query = $this->db->getQuery(true);

        if ($prefix) {
            $query->select('CONCAT(\'t.\', id) AS value, title AS text');
        } else {
            $query->select('id AS value, title AS text');
        }

        $query->from($this->db->quoteName('#__volunteers_teams'))
            ->where($this->db->quoteName('state') . ' = 1');

        if ($parent) {
            $teamId = $this->app->getInput()->getInt('id', 0);
            $query->where($this->db->quoteName('id') . ' != ' . (int) $teamId);
        }

        $query->order($this->db->quoteName('title') . ' ASC');

        $this->db->setQuery($query);

        try {
            return $this->db->loadObjectList();
        } catch (RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'warning');
        }

        return null;
    }

    /**
     * Creates a list of active positions.
     *
     * @return  array|null
     */
    public function getPositions(): ?array
    {
        $departmentId = $this->app->getUserState('com_volunteers.edit.member.departmentid');
        $teamId       = $this->app->getUserState('com_volunteers.edit.member.teamid');

        $query = $this->db->getQuery(true)
            ->select('id AS value, title AS text, type')
            ->from($this->db->quoteName('#__volunteers_positions'))
            ->where($this->db->quoteName('state') . ' = 1');

        if ($departmentId) {
            $query->where($this->db->quoteName('type') . ' = 1');
        }

        if ($teamId) {
            $query->where($this->db->quoteName('type') . ' = 2');
        }

        $query->order($this->db->quoteName('type') . ' ASC')
            ->order($this->db->quoteName('ordering') . ' ASC');

        $this->db->setQuery($query);

        try {
            return $this->db->loadObjectList();
        } catch (RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'warning');
        }

        return null;
    }

    /**
     * Creates a list of active roles.
     *
     * @param   int|null  $teamId  The team ID
     *
     * @return  array|null
     */
    public function getRoles(?int $teamId = null): ?array
    {
        if (empty($teamId)) {
            $teamId = $this->app->getUserState('com_volunteers.edit.member.teamid');
        }

        $query = $this->db->getQuery(true)
            ->select('id AS value, title AS text')
            ->from($this->db->quoteName('#__volunteers_roles'))
            ->where($this->db->quoteName('state') . ' = 1')
            ->where($this->db->quoteName('team') . ' = ' . (int) $teamId)
            ->order($this->db->quoteName('title') . ' ASC');

        $this->db->setQuery($query);

        try {
            return $this->db->loadObjectList();
        } catch (RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'warning');
        }

        return null;
    }

    /**
     * Creates a list of active volunteers.
     *
     * @return  array|null
     */
    public function getVolunteers(): ?array
    {
        $query = $this->db->getQuery(true)
            ->select('a.id AS value, user.name AS text')
            ->from($this->db->quoteName('#__volunteers_volunteers', 'a'))
            ->join('LEFT', $this->db->quoteName('#__users', 'user'), $this->db->quoteName('user.id') . ' = ' . $this->db->quoteName('a.user_id'))
            ->where($this->db->quoteName('a.state') . ' = 1')
            ->where($this->db->quoteName('user.email') . ' NOT LIKE ' . $this->db->quote('%identity.joomla.org%'))
            ->order($this->db->quoteName('user.name') . ' ASC');

        $this->db->setQuery($query);

        try {
            return $this->db->loadObjectList();
        } catch (RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'warning');
        }

        return null;
    }
    /**
     * Creates a list of departments and teams.
     *
     * @return  array
     */
    public function getReportCategories(): array
    {
        $groups                         = [];
        $groups[]['items'][]            = HTMLHelper::_('select.option', '', Text::_('COM_VOLUNTEERS_SELECT_REPORTCATEGORY'));
        $groups['departments']          = [];
        $groups['departments']['text']  = Text::_('COM_VOLUNTEERS_FIELD_DEPARTMENTS');
        $groups['departments']['items'] = [];

        foreach ($this->getDepartments(true) as $department) {
            $groups['departments']['items'][] = HTMLHelper::_('select.option', $department->value, $department->text);
        }

        $groups['teams']          = [];
        $groups['teams']['text']  = Text::_('COM_VOLUNTEERS_FIELD_TEAMS');
        $groups['teams']['items'] = [];

        foreach ($this->getTeams(false, true) as $team) {
            $groups['teams']['items'][] = HTMLHelper::_('select.option', $team->value, $team->text);
        }

        return $groups;
    }

    /**
     * Formats a date.
     *
     * @param   string  $date    The date string
     * @param   string  $format  The format
     *
     * @return  string
     */
    public function formatDate($date, string $format): string
    {
        if (empty($date) || $date === '0000-00-00') {
            return '';
        }

        return (new Date($date))->format($format);
    }

    /**
     * Generates HTML for an image.
     *
     * @param   string       $image    The image path
     * @param   string       $size     'small' or 'large'
     * @param   bool         $urlOnly  Whether to return only the URL
     * @param   string|null  $alt      Alt text
     * @param   string       $class    CSS class
     *
     * @return  string
     */
    public function getImage(?string $image, string $size, bool $urlOnly = false, ?string $alt = '', string $class = 'rounded'): string
    {
        if (empty($image)) {
            $image = Uri::root() . 'media/com_volunteers/images/joomlaperson.png';
        }

        if ($urlOnly) {
            return $image;
        }

        $width = ($size === 'small') ? '50px' : '100%';

        return '<img class="' . $class . '" alt="' . htmlspecialchars((string) $alt, ENT_QUOTES) . '" src="' . $image . '" width="' . $width . '"/>';
    }

    /**
     * Formats a location.
     *
     * @param   string|null  $country  Country code
     * @param   string|null  $city     City name
     *
     * @return  string
     */
    public function formatLocation(?string $country = null, ?string $city = null): string
    {
        $text = [];

        if ($city) {
            $text[] = $city;
        }

        if ($country && isset(self::$countries[$country])) {
            $text[] = self::$countries[$country];
        }

        return !empty($text) ? implode(', ', $text) : Text::_('COM_VOLUNTEERS_UNKNOWN');
    }

    /**
     * Outputs a volunteer block.
     *
     * @param   stdClass  $volunteer  The volunteer object
     *
     * @return  string
     */
    public function outputVolunteer(stdClass $volunteer): string
    {
        $html = [];
        $link = Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->volunteer);

        $html[] = '<a class="float-start" href="' . $link . '">';
        $html[] = $this->getImage($volunteer->volunteer_image, 'small', false, $volunteer->volunteer_image);
        $html[] = '</a>';
        $html[] = '<a href="' . $link . '">';
        $html[] = htmlspecialchars($volunteer->volunteer_name, ENT_QUOTES);
        $html[] = '</a>';
        $html[] = '<span class="text-muted volunteer-location">';
        $html[] = '<span class="icon-location" aria-hidden="true"></span> ';
        $html[] = $this->formatLocation($volunteer->volunteer_country);
        $html[] = '</span>';

        return implode('', $html);
    }
}
