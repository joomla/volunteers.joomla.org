<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use stdClass;

/**
 * ACL Service for Volunteers Component.
 *
 * @since  6.1.0
 */
class AclService
{
    /**
     * @var CMSApplicationInterface
     */
    protected $app;

    /**
     * @var MVCFactoryInterface
     */
    protected $mvcFactory;

    /**
     * Constructor.
     *
     * @param   CMSApplicationInterface  $app         The application
     * @param   MVCFactoryInterface      $mvcFactory  The MVC factory
     */
    public function __construct(CMSApplicationInterface $app, MVCFactoryInterface $mvcFactory)
    {
        $this->app        = $app;
        $this->mvcFactory = $mvcFactory;
    }

    /**
     * Check ACL for a specific entity.
     *
     * @param   string  $type  The entity type ('department' or 'team')
     * @param   int     $id    The entity ID
     *
     * @return  stdClass
     */
    public function getAcl(string $type, int $id): stdClass
    {
        // Base ACL
        $acl                  = new stdClass();
        $acl->edit_department = false;
        $acl->edit            = false;
        $acl->create_report   = false;
        $acl->create_team     = false;

        // Set ID
        $departmentId = ($type === 'department') ? $id : null;
        $teamId       = ($type === 'team') ? $id : null;

        // Get User
        $user = $this->app->getIdentity();

        // Guest
        if ($user->guest) {
            return $acl;
        }

        // Admin
        if ($user->authorise('core.admin', 'com_volunteers')) {
            $acl->edit_department = true;
            $acl->edit            = true;
            $acl->create_report   = true;
            $acl->create_team     = true;

            return $acl;
        }

        $volmodel      = $this->mvcFactory->createModel('Volunteer', 'Administrator', ['ignore_request' => true]);
        $teammodel     = $this->mvcFactory->createModel('Team', 'Administrator', ['ignore_request' => true]);
        $membermodel   = $this->mvcFactory->createModel('Member', 'Administrator', ['ignore_request' => true]);
        $positionmodel = $this->mvcFactory->createModel('Position', 'Administrator', ['ignore_request' => true]);

        // Get Volunteer ID
        $volunteerId = (int) $volmodel->getVolunteerId($user->id);
        if ($volunteerId === -1) {
            return $acl;
        }

        $parentTeamId = null;

        // Get Department ID if type is team
        if ($type === 'team') {
            $team         = $teammodel->getItem($id);
            $departmentId = (int) $team->department;
            $parentTeamId = (int) $team->parent_id;
        }

        // Check for department involvement
        $positionId = (int) $membermodel->getPosition($volunteerId, $departmentId, $teamId);

        // Get ACL for position
        $positionDepartment = $positionmodel->getItem($positionId);

        if ($positionDepartment) {
            foreach (get_object_vars($acl) as $action => $value) {
                if (!empty($positionDepartment->{$action})) {
                    $acl->{$action} = true;
                }
            }
        }

        // Check for parent team involvement
        if ($type === 'team' && $parentTeamId) {
            $positionId = (int) $membermodel->getPosition($volunteerId, null, $parentTeamId);

            // Get ACL for position
            $positionTeamParent = $positionmodel->getItem($positionId);

            if ($positionTeamParent) {
                foreach (get_object_vars($acl) as $action => $value) {
                    if (!empty($positionTeamParent->{$action})) {
                        $acl->{$action} = true;
                    }
                }
            }
        }

        // Check for team involvement
        if ($type === 'team') {
            $positionId = (int) $membermodel->getPosition($volunteerId, null, $teamId);

            // Get ACL for position
            $positionTeam = $positionmodel->getItem($positionId);

            if ($positionTeam) {
                foreach (get_object_vars($acl) as $action => $value) {
                    if (!empty($positionTeam->{$action})) {
                        $acl->{$action} = true;
                    }
                }
            }
        }

        return $acl;
    }
}
