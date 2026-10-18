<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table as Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\DatabaseDriver;
use stdClass;

/**
 * Volunteer Table class
 * @since 4.0.0
 */
class VolunteerTable extends Table implements VersionableTableInterface, TaggableTableInterface
{
    use TaggableTableTrait;

    protected stdClass $myData;

    public int $version;
    /**
     * Constructor
     *
     * @param   DatabaseDriver  &$db  A database connector object
     *
     * @since 4.0.0
     */
    public function __construct(DatabaseDriver $db)
    {
        $this->myData    = new stdClass();
        $this->typeAlias = 'com_volunteers.volunteers';
        parent::__construct('#__volunteers_volunteers', 'id', $db);

        // Set the published column alias
        $this->setColumnAlias('published', 'state');
    }

    /**
     * get
     *
     * @param   string  $property  lookup variable
     *
     * @return  mixed  variable looked up.
     *
     * @since 4.0.0
     */
    public function get($property, $default = null)
    {
        return $this->myData->{$property};
    }

    /**
     * set
     *
     * @param   string  $property  lookup variable
     * @param   mixed  $value  set variable
     *
     * @since 4.0.0
     */
    public function set($property, $value = null)
    {
        $this->myData->{$property} = $value;
    }

    /**
     * Method to bind the data.
     *
     * @param   array  $src     The data to bind.
     * @param   mixed  $ignore  An array or space separated list of fields to ignore.
     *
     * @return  boolean  True on success, false on failure.
     * @since 4.0.0
     */
    public function bind($src, $ignore = [])
    {
        // send_permission checkbox default
        if (!isset($src['send_permission'])) {
            $src['send_permission'] = 0;
        }

        // coc checkbox default
        if (!isset($src['coc'])) {
            $src['coc'] = 0;
        }

        return parent::bind($src, $ignore);
    }

    /**
     * Overloaded delete method
     *
     * @param   mixed  $pk  An optional primary key value to delete.  If not set the instance property value is used.
     *
     * @return  boolean  True on success.
     * @since 4.0.0
     */
    public function delete($pk = null)
    {
        $return = parent::delete($pk);

        // Delete the Joomla User

        $user = Factory::getContainer()->get('user.factory')->loadUserById($this->user_id ?? null);


        if (!$user->delete()) {
            return false;
        }

        return $return;
    }

    /**
     * Get the type alias for the history table
     *
     * @return  string  The alias as described above
     *
     * @since   4.0.0
     */
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }

    /**
     * Overload the store method for the table.
     *
     * @param   boolean  $updateNulls  Toggle whether null values should be updated.
     *
     * @return  boolean  True on success, false on failure.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function store($updateNulls = false)
    {
        $date = Factory::getDate();
        $user = Factory::getApplication()->getIdentity();

        $this->modified = $date->toSql();

        if ($this->getId()) {
            // Existing item

            $this->modified_by = $user->id;
        } else {
            // New item. An item created and created_by field can be set by the user,
            // so we don't touch either of these if they are set.
            if (!(int) ($this->created ?? null)) {
                $this->created = $date->toSql();
            }

            if (empty($this->created_by)) {
                $this->created_by = $user->id;
            }
        }

        // Birthday format
        if (($this->birthday ?? null) && ($this->birthday ?? null) != '0000-00-00 00:00:00') {
            $this->birthday = Factory::getDate('0000-' . ($this->birthday ?? null))->format('Y-m-d');
        }

        return parent::store($updateNulls);
    }
    /**
     * Get the Properties of the table
     *
     * * @param   boolean  $public  If true, returns only the public properties.
     *
     * @return array
     *
     * @since 4.0.0
     */
    public function getTableProperties(bool $public = true)
    {
        $vars = get_object_vars($this);

        if ($public) {
            foreach ($vars as $key => $value) {
                if (str_starts_with((string) $key, '_')) {
                    unset($vars[$key]);
                }
            }

            // Collect all none public properties of the current class and it's parents
            $nonePublicProperties = [];
            $reflection           = new \ReflectionObject($this);
            do {
                $nonePublicProperties = array_merge(
                    $reflection->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED),
                    $nonePublicProperties
                );
            } while ($reflection = $reflection->getParentClass());

            // Unset all none public properties, this is needed as get_object_vars returns now all vars
            // from the current object and not only the CMSObject and the public ones from the inheriting classes
            foreach ($nonePublicProperties as $prop) {
                if (\array_key_exists($prop->getName(), $vars)) {
                    unset($vars[$prop->getName()]);
                }
            }
        }

        return $vars;
    }
}
