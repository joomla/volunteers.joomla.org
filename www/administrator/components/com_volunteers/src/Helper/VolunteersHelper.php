<?php

/**
 * @package        VOLUNTEERS_PORTAL
 *
 * @copyright  (C) 2023 Open Source Matters, Inc.  <https://www.joomla.org>
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
use Exception;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User;
use Joomla\Component\Volunteers\Administrator\Model\MemberModel;
use Joomla\Component\Volunteers\Administrator\Model\PositionModel;
use Joomla\Component\Volunteers\Administrator\Model\TeamModel;
use Joomla\Component\Volunteers\Administrator\Model\VolunteerModel;
use RuntimeException;
use stdClass;

/**
 * Volunteers Helper
 *
 * @package   Volunteer Portal
 * @since     4.0.0
 */
class VolunteersHelper
{
    public static array $countries = [
        'AD' => 'Andorra', 'AE' => 'United Arab Emirates', 'AF' => 'Afghanistan',
        'AG' => 'Antigua and Barbuda', 'AI' => 'Anguilla', 'AL' => 'Albania',
        'AM' => 'Armenia', 'AO' => 'Angola',
        'AQ' => 'Antarctica', 'AR' => 'Argentina', 'AS' => 'American Samoa',
        'AT' => 'Austria', 'AU' => 'Australia', 'AW' => 'Aruba',
        'AX' => 'Aland Islands', 'AZ' => 'Azerbaijan', 'BA' => 'Bosnia and Herzegovina',
        'BB' => 'Barbados', 'BD' => 'Bangladesh', 'BE' => 'Belgium',
        'BF' => 'Burkina Faso', 'BG' => 'Bulgaria', 'BH' => 'Bahrain',
        'BI' => 'Burundi', 'BJ' => 'Benin', 'BL' => 'Saint Barthélemy',
        'BM' => 'Bermuda', 'BN' => 'Brunei Darussalam', 'BO' => 'Bolivia, Plurinational State of',
        'BQ' => 'Bonaire, Saint Eustatius and Saba',
        'BR' => 'Brazil', 'BS' => 'Bahamas', 'BT' => 'Bhutan', 'BV' => 'Bouvet Island',
        'BW' => 'Botswana', 'BY' => 'Belarus', 'BZ' => 'Belize', 'CA' => 'Canada',
        'CC' => 'Cocos (Keeling) Islands', 'CD' => 'Congo, the Democratic Republic of the',
        'CF' => 'Central African Republic', 'CG' => 'Congo', 'CH' => 'Switzerland',
        'CI' => 'Cote d\'Ivoire', 'CK' => 'Cook Islands', 'CL' => 'Chile',
        'CM' => 'Cameroon', 'CN' => 'China', 'CO' => 'Colombia', 'CR' => 'Costa Rica',
        'CU' => 'Cuba', 'CV' => 'Cape Verde', 'CW' => 'Curaçao', 'CX' => 'Christmas Island', 'CY' => 'Cyprus',
        'CZ' => 'Czech Republic', 'DE' => 'Germany', 'DJ' => 'Djibouti', 'DK' => 'Denmark',
        'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'DZ' => 'Algeria',
        'EC' => 'Ecuador', 'EE' => 'Estonia', 'EG' => 'Egypt', 'EH' => 'Western Sahara',
        'ER' => 'Eritrea', 'ES' => 'Spain', 'ET' => 'Ethiopia', 'FI' => 'Finland',
        'FJ' => 'Fiji', 'FK' => 'Falkland Islands (Malvinas)', 'FM' => 'Micronesia, Federated States of',
        'FO' => 'Faroe Islands', 'FR' => 'France', 'GA' => 'Gabon', 'GB' => 'United Kingdom',
        'GD' => 'Grenada', 'GE' => 'Georgia', 'GF' => 'French Guiana', 'GG' => 'Guernsey',
        'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GL' => 'Greenland', 'GM' => 'Gambia',
        'GN' => 'Guinea', 'GP' => 'Guadeloupe', 'GQ' => 'Equatorial Guinea', 'GR' => 'Greece',
        'GS' => 'South Georgia and the South Sandwich Islands', 'GT' => 'Guatemala',
        'GU' => 'Guam', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HK' => 'Hong Kong',
        'HM' => 'Heard Island and McDonald Islands', 'HN' => 'Honduras', 'HR' => 'Croatia',
        'HT' => 'Haiti', 'HU' => 'Hungary', 'ID' => 'Indonesia', 'IE' => 'Ireland',
        'IL' => 'Israel', 'IM' => 'Isle of Man', 'IN' => 'India', 'IO' => 'British Indian Ocean Territory',
        'IQ' => 'Iraq', 'IR' => 'Iran, Islamic Republic of', 'IS' => 'Iceland',
        'IT' => 'Italy', 'JE' => 'Jersey', 'JM' => 'Jamaica', 'JO' => 'Jordan',
        'JP' => 'Japan', 'KE' => 'Kenya', 'KG' => 'Kyrgyzstan', 'KH' => 'Cambodia',
        'KI' => 'Kiribati', 'KM' => 'Comoros', 'KN' => 'Saint Kitts and Nevis',
        'KP' => 'Korea, Democratic People\'s Republic of', 'KR' => 'Korea, Republic of',
        'KW' => 'Kuwait', 'KY' => 'Cayman Islands', 'KZ' => 'Kazakhstan',
        'LA' => 'Lao People\'s Democratic Republic', 'LB' => 'Lebanon',
        'LC' => 'Saint Lucia', 'LI' => 'Liechtenstein', 'LK' => 'Sri Lanka',
        'LR' => 'Liberia', 'LS' => 'Lesotho', 'LT' => 'Lithuania', 'LU' => 'Luxembourg',
        'LV' => 'Latvia', 'LY' => 'Libyan Arab Jamahiriya', 'MA' => 'Morocco',
        'MC' => 'Monaco', 'MD' => 'Moldova, Republic of', 'ME' => 'Montenegro',
        'MF' => 'Saint Martin (French part)', 'MG' => 'Madagascar', 'MH' => 'Marshall Islands',
        'MK' => 'Macedonia, the former Yugoslav Republic of', 'ML' => 'Mali',
        'MM' => 'Myanmar', 'MN' => 'Mongolia', 'MO' => 'Macao', 'MP' => 'Northern Mariana Islands',
        'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MS' => 'Montserrat', 'MT' => 'Malta',
        'MU' => 'Mauritius', 'MV' => 'Maldives', 'MW' => 'Malawi', 'MX' => 'Mexico',
        'MY' => 'Malaysia', 'MZ' => 'Mozambique', 'NA' => 'Namibia', 'NC' => 'New Caledonia',
        'NE' => 'Niger', 'NF' => 'Norfolk Island', 'NG' => 'Nigeria', 'NI' => 'Nicaragua',
        'NL' => 'Netherlands', 'NO' => 'Norway', 'NP' => 'Nepal', 'NR' => 'Nauru', 'NU' => 'Niue',
        'NZ' => 'New Zealand', 'OM' => 'Oman', 'PA' => 'Panama', 'PE' => 'Peru', 'PF' => 'French Polynesia',
        'PG' => 'Papua New Guinea', 'PH' => 'Philippines', 'PK' => 'Pakistan', 'PL' => 'Poland',
        'PM' => 'Saint Pierre and Miquelon', 'PN' => 'Pitcairn', 'PR' => 'Puerto Rico',
        'PS' => 'Palestinian Territory, Occupied', 'PT' => 'Portugal', 'PW' => 'Palau',
        'PY' => 'Paraguay', 'QA' => 'Qatar', 'RE' => 'Reunion', 'RO' => 'Romania',
        'RS' => 'Serbia', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'SA' => 'Saudi Arabia',
        'SB' => 'Solomon Islands', 'SC' => 'Seychelles', 'SD' => 'Sudan', 'SE' => 'Sweden',
        'SG' => 'Singapore', 'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
        'SI' => 'Slovenia', 'SJ' => 'Svalbard and Jan Mayen', 'SK' => 'Slovakia',
        'SL' => 'Sierra Leone', 'SM' => 'San Marino', 'SN' => 'Senegal', 'SO' => 'Somalia',
        'SR' => 'Suriname', 'ST' => 'Sao Tome and Principe', 'SV' => 'El Salvador', 'SX' => 'Sint Maarten',
        'SY' => 'Syrian Arab Republic', 'SZ' => 'Swaziland', 'TC' => 'Turks and Caicos Islands',
        'TD' => 'Chad', 'TF' => 'French Southern Territories', 'TG' => 'Togo',
        'TH' => 'Thailand', 'TJ' => 'Tajikistan', 'TK' => 'Tokelau', 'TL' => 'Timor-Leste',
        'TM' => 'Turkmenistan', 'TN' => 'Tunisia', 'TO' => 'Tonga', 'TR' => 'Turkey',
        'TT' => 'Trinidad and Tobago', 'TV' => 'Tuvalu', 'TW' => 'Taiwan',
        'TZ' => 'Tanzania, United Republic of', 'UA' => 'Ukraine', 'UG' => 'Uganda',
        'UM' => 'United States Minor Outlying Islands', 'US' => 'United States',
        'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VA' => 'Holy See (Vatican City State)',
        'VC' => 'Saint Vincent and the Grenadines', 'VE' => 'Venezuela, Bolivarian Republic of',
        'VG' => 'Virgin Islands, British', 'VI' => 'Virgin Islands, U.S.', 'VN' => 'Viet Nam',
        'VU' => 'Vanuatu', 'WF' => 'Wallis and Futuna', 'WS' => 'Samoa', 'YE' => 'Yemen',
        'YT' => 'Mayotte', 'ZA' => 'South Africa', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe',
    ];
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
        // Base ACL
        $acl                  = new stdClass();
        $acl->edit_department = false;
        $acl->edit            = false;
        $acl->create_report   = false;
        $acl->create_team     = false;
        // Set ID
        $departmentId = ($type == 'department') ? $id : null;
        $teamId       = ($type == 'team') ? $id : null;
        // Get User ID
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        // Guest
        if ($user->guest) {
            return $acl;
        }

        // Admin
        if ($user->authorise('code.admin', 'com_volunteers')) {
            $acl->edit_department = true;
            $acl->edit            = true;
            $acl->create_report   = true;
            $acl->create_team     = true;
            return $acl;
        }

        $volmodel      = new VolunteerModel();
        $teammodel     = new TeamModel();
        $membermodel   = new MemberModel();
        $positionmodel = new PositionModel();
        // Get Volunteer ID
        $volunteerId = (int) $volmodel->getVolunteerId($user->id);
        if ($volunteerId == -1) { // Found a logged in user who is not in department or team. Think this will only occur when being tested on development machines.
            return $acl;
        }

        // Get Department ID
        if ($type == 'team') {
            $team         = $teammodel->getItem($id);
            $departmentId = (int) $team->department;
            $parentTeamId = (int) $team->parent_id;
        }

        // Check for department involvement
        $positionId = (int) $membermodel->getPosition($volunteerId, $departmentId, $teamId);
        // Get ACL for position
        $positionDepartment = $positionmodel->getItem($positionId);
        foreach ($acl as $action => $value) {
            if ($positionDepartment->{$action}) {
                $acl->{$action} = true;
            }
        }

        // Check for parent team involvement
        if ($type == 'team' && $parentTeamId) {
            $positionId = (int) $membermodel->getPosition($volunteerId, null, $parentTeamId);
            // Get ACL for position
            $positionTeamParent = $positionmodel->getItem($positionId);
            foreach ($acl as $action => $value) {
                if ($positionTeamParent->{$action}) {
                    $acl->{$action} = true;
                }
            }
        }

        // Check for team involvement
        if ($type == 'team') {
            $positionId = (int) $membermodel->getPosition($volunteerId, null, $teamId);
            // Get ACL for position
            $positionTeam = $positionmodel->getItem($positionId);
            foreach ($acl as $action => $value) {
                if ($positionTeam->{$action}) {
                    $acl->{$action} = true;
                }
            }
        }

        return $acl;
    }

    /**
     * Configure the Linkbar.
     *
     * @param   string  $vName  The name of the active view.
     *
     * @return  void
     * @since 4.0.0
     */
    public static function addSubmenu(string $vName = 'volunteers')
    {
        SideBar::addEntry(Text::_('COM_VOLUNTEERS_TITLE_VOLUNTEERS'), 'index.php?option=com_volunteers&view=volunteers', $vName == 'volunteers');
        SideBar::addEntry(Text::_('COM_VOLUNTEERS_TITLE_TEAMS'), 'index.php?option=com_volunteers&view=teams', $vName == 'teams');
        SideBar::addEntry(Text::_('COM_VOLUNTEERS_TITLE_ROLES'), 'index.php?option=com_volunteers&view=roles', $vName == 'roles');
        SideBar::addEntry(Text::_('COM_VOLUNTEERS_TITLE_MEMBERS'), 'index.php?option=com_volunteers&view=members', $vName == 'members');
        SideBar::addEntry(Text::_('COM_VOLUNTEERS_TITLE_REPORTS'), 'index.php?option=com_volunteers&view=reports', $vName == 'reports');
        SideBar::addEntry('<hr>');
        SideBar::addEntry(Text::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS'), 'index.php?option=com_volunteers&view=departments', $vName == 'departments');
        SideBar::addEntry(Text::_('COM_VOLUNTEERS_TITLE_POSITIONS'), 'index.php?option=com_volunteers&view=positions', $vName == 'positions');
        SideBar::addEntry('<hr>');
        SideBar::addEntry(Text::_('COM_VOLUNTEERS_TITLE_CONTACT'), 'index.php?option=com_volunteers&view=contact', $vName == 'contact');
    }

    /**
     * Creates a list of countries.
     *
     * @return  array  An array containing the countries that can be selected.
     *
     * @since 4.0.0
     */
    public static function countries()
    {
        $items = self::$countries;
        asort($items);
        $options   = [];
        $options[] = HTMLHelper::_('select.option', '', Text::_('COM_VOLUNTEERS_SELECT_COUNTRY'));
        foreach ($items as $iso => $item) {
            $options[] = HTMLHelper::_('select.option', $iso, $item);
        }

        return $options;
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
        $db      = Factory::getContainer()->get('DatabaseDriver');
        $query   = $db->getQuery(true);
        $options = null;
        if ($prefix) {
            $query->select('CONCAT(\'d.\', id) AS value, title AS text');
        } else {
            $query->select('id AS value, title AS text');
        }

        $query->from('#__volunteers_departments')
            ->where('state = 1')
            ->order('title asc');
        // Get the options.
        $db->setQuery($query);
        try {
            $options = $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
        }

        return $options;
    }

    /**
     * Gets the current User .
     *
     * @return User\User
     *
     * @since    4.0.0
     */
    public static function getUser()
    {
        try {
            $app = Factory::getApplication();
            return $app->getIdentity();
        } catch (Exception) {
            return new User\User();
        }
    }

    /**
     * Gets a user by ID number.
     *
     * @param $userId
     *
     * @return User\User
     *
     * @since    4.0.0
     */
    public static function getUserById($userId)
    {

        try {
            $container   = Factory::getContainer();
            $userFactory = $container->get('user.factory');
            return $userFactory->loadUserById($userId);
        } catch (Exception) {
            return new User\User();
        }
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
        $departmentId = Factory::getApplication()->getUserState('com_volunteers.edit.member.departmentid');
        $teamId       = Factory::getApplication()->getUserState('com_volunteers.edit.member.teamid');
        $options      = null;
        $db           = Factory::getContainer()->get('DatabaseDriver');
        $query        = $db->getQuery(true)
            ->select('id AS value, title AS text')
            ->from('#__volunteers_positions')
            ->where('state = 1');
        if ($departmentId) {
            $query->where('type = 1');
        }

        if ($teamId) {
            $query->where('type = 2');
        }

        $query->order('ordering asc');
        // Get the options.
        $db->setQuery($query);
        try {
            $options = $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
        }

        return $options;
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
        $department[] = HtmlHelper::_('select.optgroup', Text::_('COM_VOLUNTEERS_FIELD_DEPARTMENTS'));
        $departments  = array_merge($department, self::departments(true));
        $team[]       = HtmlHelper::_('select.optgroup', Text::_('COM_VOLUNTEERS_FIELD_TEAMS'));
        $teams        = array_merge($team, self::teams());
        return array_merge($departments, $teams);
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
        $options = null;
        if (empty($team)) {
            // Get team
            $team = Factory::getApplication()->getUserState('com_volunteers.edit.member.teamid');
        }

        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select('id AS value, title AS text')
            ->from('#__volunteers_roles')
            ->where('state = 1')
            ->where($db->quoteName('team') . ' = ' . (int) $team)
            ->order('title asc');
        // Get the options.
        $db->setQuery($query);
        try {
            $options = $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
        }

        return $options;
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
        $db      = Factory::getContainer()->get('DatabaseDriver');
        $query   = $db->getQuery(true);
        $options = null;
        if ($prefix) {
            $query->select('CONCAT(\'t.\', id) AS value, title AS text');
        } else {
            $query->select('id AS value, title AS text');
        }

        $query
            ->from('#__volunteers_teams')
            ->where('state = 1');
        if ($parent) {
            $teamId = Factory::getApplication()->input->getInt('id', 0);
            $query->where('id != ' . $teamId);
        }

        $query->order('title asc');
        // Get the options.
        $db->setQuery($query);
        try {
            $options = $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
        }

        return $options;
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
        $db      = Factory::getContainer()->get('DatabaseDriver');
        $options = null;
        $query   = $db->getQuery(true)
            ->select('a.id AS value, user.name AS text')
            ->from($db->quoteName('#__volunteers_volunteers') . ' AS a')
            ->join('LEFT', '#__users AS ' . $db->quoteName('user') . ' ON user.id = a.user_id')
            ->where('state = 1')
            ->where($db->quoteName('user.email') . ' NOT LIKE ' . $db->quote('%identity.joomla.org%'))
            ->order('name asc');
        // Get the options.
        $db->setQuery($query);
        try {
            $options = $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
        }

        return $options;
    }
}
