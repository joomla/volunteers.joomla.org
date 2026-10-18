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
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table as Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\DatabaseDriver;
use stdClass;

/**
 * Team Table class
 *
 * @since 4.0.0
 */
class TeamTable extends Table implements VersionableTableInterface, TaggableTableInterface
{
    use TaggableTableTrait;

    protected stdClass $myData;

    /**
     * Constructor
     *
     * @param DatabaseDriver $myDB A database connector object
     *
     * @since 4.0.0
     */
    public function __construct(protected DatabaseDriver $myDB)
    {
        $this->myData    = new stdClass();
        $this->typeAlias = 'com_volunteers.team';
        parent::__construct('#__volunteers_teams', 'id', $this->myDB);

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
     * Overloaded check method to ensure data integrity.
     *
     * @return  boolean  True on success.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function check()
    {
        // check for valid name
        if (trim((string) ($this->title ?? null)) == '') {
            throw new Exception(Text::_('COM_VOLUNTEERS_ERR_TABLES_NAME'));
        }

        // Check for existing name
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__volunteers_teams'))
            ->where($db->quoteName('title') . ' = ' . $db->quote($this->title ?? null));
        $db->setQuery($query);

        $xid = (int) $db->loadResult();

        if ($xid && $xid != (int) ($this->id ?? null)) {
            throw new Exception(Text::_('COM_VOLUNTEERS_ERR_TABLES_NAME'));
        }

        if (empty($this->alias ?? null)) {
            $this->alias = $this->title ?? null;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias ?? null);

        if (trim(str_replace('-', '', $this->alias ?? null)) == '') {
            $this->alias = Factory::getDate()->format("Y-m-d-H-i-s");
        }

        return true;
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

            if (empty($this->created_by ?? null)) {
                $this->created_by = $user->id;
            }
        }

        // Verify that the alias is unique
        $table = new TeamTable($this->myDB);
        //      $table = JTable::getInstance('Team', 'VolunteersTable');

        if ($table->load(['alias' => $this->alias ?? null]) && ($table->get('id') != ($this->id ?? null) || ($this->id ?? null) == 0)) {
            throw new Exception(Text::_('COM_VOLUNTEERS_ERROR_UNIQUE_ALIAS'));
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
