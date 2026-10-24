<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Model;

defined('_JEXEC') or die;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use RuntimeException;
use SimpleXMLElement;
use stdClass;

/**
 * Database model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class DatabaseModel extends BaseDatabaseModel
{
    /**
     * List of tables in the database
     *
     * @var    array
     * @since  $db->
     */
    private array $tables = [];

    /**
     * List of queries to execute
     *
     * @var    array
     * @since  $db->
     */
    private array $queries = [];

    /**
     * Verify database structure.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   2.3.0
     */
    public function process(): void
    {
        $this->processStructure();
        $this->processData();
    }

    /**
     * Process the database XML definition file.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   2.3.0
     */
    private function processStructure(): void
    {
        $source = JPATH_ADMINISTRATOR . '/components/com_sso/assets/core/database.xml';

        if (!file_exists($source))
        {
            throw new InvalidArgumentException(Text::_('COM_SSO_NOT_VALID_DATABASE_SOURCE'));
        }

        /** @var object $xml */
        $xml = new SimpleXMLElement($source, 0, true);

        foreach ($xml->tables->table as $table)
        {
            $table       = json_decode(json_encode($table));
            $tableName   = (string) $table->name;
            $fields      = ((array) $table->fields);
            $tableFields = $fields['field'];

            if (!$this->tableExists($tableName))
            {
                $indexes = [];

                if (isset($table->indexes))
                {
                    $indexes = ((array) $table->indexes);
                }

                $this->createTable($table, $tableFields, $indexes);
            }
            else
            {
                $this->checkFields($tableName, $tableFields);

                if (isset($table->indexes))
                {
                    $indexes = ((array) $table->indexes);
                    $this->checkIndexes($tableName, $indexes);
                }

                if (isset($table->primaryKey))
                {
                    $primaryKeys = (array) $table->primaryKey;
                    $this->checkPrimarykeys($tableName, $primaryKeys);
                }
            }
        }

        if ($this->queries)
        {
            $this->runQueries();
        }
    }

    private function processData(): void
    {
        $db = $this->getDatabase();
        $db->truncateTable('#__tableVersion');
        $query = $db->createQuery()
            ->insert($db->quoteName('#__tableVersion'))
            ->columns($db->quoteName(['_name', '_version']))
            ->values($db->quote('kvstore') .',2')
            ->values($db->quote('saml_LogoutStore') .',5')
            ->values($db->quote('tableVersion') .',1');
        $db->setQuery($query)
            ->execute();
    }

    /**
     * Check if a given table name exists.
     *
     * @param   string  $tableName  The name of the table to check
     *
     * @return  boolean  True if table exists | false otherwise.
     *
     * @since   2.3.0
     */
    private function tableExists(string $tableName): bool
    {
        $db = $this->getDatabase();

        if (!$this->tables)
        {
            $this->tables = $db->getTableList();
        }

        return in_array($db->getPrefix() . $tableName, $this->tables);
    }

    /**
     * Get the CREATE table statement.
     *
     * @param   stdClass  $table        The table to create
     * @param   array     $tableFields  A list of fields that must exist
     * @param   array     $indexes      The list of indexes to add
     *
     * @return  void
     *
     * @since   2.3.0
     */
    private function createTable(stdClass $table, array $tableFields, array $indexes): void
    {
        $db          = $this->getDatabase();
        $tableName   = (string) $table->name;
        $createTable = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName('#__' . $tableName) . ' (';
        $fields      = [];
        $primaryKey  = '';

        foreach ($tableFields as $tableField)
        {
            $fieldName     = (string) $tableField->name;
            $fieldType     = (string) $tableField->type;
            $fieldNull     = (string) $tableField->null;
            $fieldDefault  = (isset($tableField->default) && (string) $tableField->default !== '') ? 'DEFAULT ' . ((string) $tableField->default === 'NULL' ? 'NULL' : ($tableField->default !== 'CURRENT_TIMESTAMP' ? $db->quote($tableField->default) : 'CURRENT_TIMESTAMP')) : '';
            $nullValue     = 'NULL';
            $autoIncrement = (bool) $tableField->autoIncrement ? 'AUTO_INCREMENT' : '';

            if ($fieldNull === 'NO')
            {
                $nullValue = 'NOT NULL';
            }

            if (strtolower((string) $tableField->unsigned) === 'true')
            {
                $fieldType .= ' UNSIGNED';
            }

            if ((bool) $tableField->primaryKey)
            {
                $primaryKey = 'PRIMARY KEY (' . $db->quoteName($fieldName) . ')';
            }

            if ($autoIncrement)
            {
                $fieldDefault = '';
            }

            $fields[] = $db->quoteName($fieldName) . ' ' . $fieldType . ' ' . $nullValue . ' ' . $fieldDefault . ' ' . $autoIncrement;
        }

        if (!$primaryKey && isset($table->primaryKey))
        {
            $primaryKeyFields = (array) $table->primaryKey;
            $primaryKey       = 'PRIMARY KEY (' . implode(',', $db->quoteName((array) $primaryKeyFields['field'])) . ')';
        }

        $createTable .= implode(',', $fields);

        if ($primaryKey)
        {
            $createTable .= ", \r\n" . $primaryKey;
        }

        $indexQueries = [];

        foreach ($indexes as $index)
        {
            if (is_object($index))
            {
                $index = (array) [$index];
            }

            foreach ($index as $item)
            {
                $indexName   = (string) $item->name;
                $indexFields = (array) $item->fields;
                $indexType   = (string) $item->type;

                if (!$indexFields)
                {
                    throw new InvalidArgumentException(Text::sprintf('COM_SSO_MISSING_INDEX_FIELDS', $indexName, $tableName));
                }

                $query  = strtoupper($indexType) . ' INDEX ' . $db->quoteName($indexName) . ' (';
                $fields = [];

                if (isset($indexFields['field']->name))
                {
                    $fieldName = $db->quoteName((string) $indexFields['field']->name);

                    if (isset($indexFields['field']->length))
                    {
                        $fieldName .= '(' . (string) $indexFields['field']->length . ')';
                    }

                    $fields[] = $fieldName;
                }
                else
                {
                    foreach ($indexFields['field'] as $indexField)
                    {
                        $fields[] = $db->quoteName((string) $indexField->name);
                    }
                }

                $query .= implode(',', $fields) . ')';

                $indexQueries[] = $query;
            }
        }

        if ($indexQueries)
        {
            $createTable .= ",\r\n" . implode(',', $indexQueries);
        }

        $createTable .= ') CHARSET=utf8';

        $comment = (string) $table->comment;

        if ($comment)
        {
            $createTable .= ' COMMENT=' . $db->quote($comment);
        }

        $createTable .= ';';

        $this->queries[] = $createTable;
    }

    /**
     * Check the fields in a given table.
     *
     * @param   string  $tableName    The name of the table to check
     * @param   array   $tableFields  A list of fields that must exist
     *
     * @return  void
     *
     * @since   2.3.0
     */
    private function checkFields(string $tableName, array $tableFields): void
    {
        $db               = $this->getDatabase();
        $existingFields   = $db->getTableColumns($db->getPrefix() . $tableName, false);
        $existingIndexes  = $this->getIndexes($db->getTableKeys($db->getPrefix() . $tableName));
        $extraQueries     = [];
        $primaryKeyFields = $existingIndexes['PRIMARY'] ?? [];

        foreach ($tableFields as $tableField)
        {
            $fieldName     = (string) $tableField->name;
            $fieldType     = (string) $tableField->type;
            $fieldNull     = (string) $tableField->null;
            $fieldDefault  = '';
            $comment       = '';
            $nullValue     = '';
            $autoIncrement = $tableField->autoIncrement ? 'AUTO_INCREMENT' : '';
            $fieldExists   = array_key_exists($fieldName, $existingFields);

            if (!$fieldExists || strtolower($fieldNull) !== strtolower($existingFields[$fieldName]->Null))
            {
                $nullValue = 'NULL';

                if ($fieldNull === 'NO')
                {
                    $nullValue = 'NOT NULL';

                    if ($tableField->default)
                    {
                        $extraQueries[] = $db->createQuery()
                                ->update($db->quoteName($db->getPrefix() . $tableName))
                                ->set($db->quoteName($fieldName) . ' = ' . $db->quote($tableField->default))
                                ->where($db->quoteName($fieldName) . ' IS NULL') . ';';
                    }
                }
            }

            if (isset($tableField->default))
            {
                if ($fieldNull === 'NO')
                {
                    $fieldDefault = 'NOT NULL ';
                }

                $fieldDefault .= 'DEFAULT ' . ((string) $tableField->default !== 'CURRENT_TIMESTAMP' ? $db->quote($tableField->default) : $tableField->default);

                if ((string) $tableField->default === 'NULL')
                {
                    $fieldDefault = 'DEFAULT NULL';
                }
            }

            if (strtolower((string) $tableField->unsigned) === 'true')
            {
                $fieldType .= ' UNSIGNED';
            }

            if ((bool) $tableField->primaryKey)
            {
                $setPrimaryKey = !array_key_exists('PRIMARY', $existingIndexes);

                if (!in_array($fieldName, $primaryKeyFields))
                {
                    foreach ($primaryKeyFields as $primaryKeyField)
                    {
                        $extraQueries[] = 'ALTER TABLE ' . $db->quoteName($db->getPrefix() . $tableName)
                            . ' ALTER ' . $db->quoteName($primaryKeyField) . ' DROP DEFAULT;';
                        $extraQueries[] = 'ALTER TABLE ' . $db->quoteName($db->getPrefix() . $tableName)
                            . ' CHANGE COLUMN ' . $db->quoteName($primaryKeyField) . ' ' . $db->quoteName($primaryKeyField)
                            . $existingFields[$primaryKeyField]->Type
                            . ($existingFields[$primaryKeyField]->Null === 'NO' ? ' NOT NULL' : 'NULL')
                            . ' FIRST, DROP PRIMARY KEY';
                    }

                    $setPrimaryKey = true;
                }

                if ($setPrimaryKey)
                {
                    $nullValue = 'NOT NULL PRIMARY KEY';
                }
            }

            if (!$fieldExists || strtolower((string) $tableField->comment) !== strtolower($existingFields[$fieldName]->Comment))
            {
                $comment = ' COMMENT ' . $db->quote((string) $tableField->comment);
            }

            if ($autoIncrement
                || ($this->isInt($fieldType) && filter_var($tableField->default, FILTER_VALIDATE_INT) === false)
                || in_array(strtoupper($fieldType), ['BLOB', 'TEXT', 'GEOMETRY', 'JSON'])
            )
            {
                $fieldDefault = '';
            }

            if ($fieldExists)
            {
                if ($fieldDefault
                    || strtolower($fieldType) !== strtolower($existingFields[$fieldName]->Type)
                    || strtolower($fieldNull) !== strtolower($existingFields[$fieldName]->Null)
                    || (isset($existingFields[$fieldName]->Default) && strtolower($fieldDefault) !== strtolower($existingFields[$fieldName]->Default))
                )
                {
                    $addType = 'MODIFY';
                }
                else
                {
                    continue;
                }
            }
            else {
                $addType = 'ADD';
            }

            if ($addType === 'MODIFY' && $extraQueries)
            {
                $this->queries = array_merge($this->queries, $extraQueries);
                $extraQueries = [];
            }

            $this->queries[] = 'ALTER TABLE '
                . $db->quoteName($db->getPrefix() . $tableName) . ' '
                . $addType . ' '
                . $db->quoteName($fieldName) . ' '
                . $fieldType . ' '
                . $nullValue . ' '
                . $fieldDefault . ' '
                . $autoIncrement . ' '
                . $comment . ';';

            if ($addType === 'ADD' && $extraQueries)
            {
                $this->queries = array_merge($this->queries, $extraQueries);
                $extraQueries = [];
            }
        }
    }

    /**
     * Check if a field is of the type int.
     *
     * @return  boolean  True if field is of type int || False otherwise.
     *
     * @since   8.2.0
     */
    private function isInt(string $fieldType): bool
    {
        return strtolower(substr($fieldType, 0, 3)) === 'int'
            || strtolower(substr($fieldType, 0, 6)) === 'bigint'
            || strtolower(substr($fieldType, 0, 9)) === 'mediumint'
            || strtolower(substr($fieldType, 0, 8)) === 'smallint'
            || strtolower(substr($fieldType, 0, 7)) === 'tinyint';
    }

    /**
     * Check the indexes in a given table.
     *
     * @param   string  $tableName  The name of the table to check
     * @param   array   $indexes    A list of indexes that must exist
     *
     * @return  void
     *
     * @since   2.3.0
     */
    private function checkIndexes(string $tableName, array $indexes): void
    {
        $db              = $this->getDatabase();
        $existingIndexes = $this->getIndexes($db->getTableKeys($db->getPrefix() . $tableName));

        foreach ($existingIndexes as $indexName => $existingIndex)
        {
            if (strtoupper($indexName) !== 'PRIMARY')
            {
                array_unshift($this->queries, 'ALTER TABLE ' . $db->quoteName($db->getPrefix() . $tableName) . ' DROP INDEX ' . $db->quoteName($indexName) . ';');
            }
        }

        foreach ($indexes as $index)
        {
            if (is_object($index))
            {
                $index = (array) [$index];
            }

            foreach ($index as $item)
            {
                $indexName   = (string) $item->name;
                $indexFields = (array) $item->fields;
                $indexType   = (string) $item->type;

                if (!$indexFields)
                {
                    throw new InvalidArgumentException(Text::sprintf('COM_SSO_MISSING_INDEX_FIELDS', $indexName, $tableName));
                }

                $query  = 'ALTER TABLE ' . $db->quoteName($db->getPrefix() . $tableName) . ' ADD ' . $indexType . ' INDEX ' . $db->quoteName($indexName) . ' (';
                $fields = [];

                if (isset($indexFields['field']->name))
                {
                    $fieldName = $db->quoteName((string) $indexFields['field']->name);

                    if (isset($indexFields['field']->length))
                    {
                        $fieldName .= '(' . (string) $indexFields['field']->length . ')';
                    }

                    $fields[] = $fieldName;
                }
                else
                {
                    foreach ($indexFields['field'] as $indexField)
                    {
                        $fieldName = $db->quoteName((string) $indexField->name);

                        if (isset($indexField->length))
                        {
                            $fieldName .= '(' . (string) $indexField->length . ')';
                        }

                        $fields[] = $fieldName;
                    }
                }

                $query .= implode(',', $fields) . ');';

                $this->queries[] = $query;
            }
        }
    }

    /**
     * Check the compound primary keys in a given table.
     *
     * @param   string  $tableName    The name of the table to check
     * @param   array   $primaryKeys  A list of primary keys that must exist
     *
     * @return  void
     *
     * @since   2.3.0
     */
    private function checkPrimaryKeys(string $tableName, array $primaryKeys): void
    {
        $db              = $this->getDatabase();
        $existingIndexes = $this->getIndexes($db->getTableKeys($db->getPrefix() . $tableName));

        foreach ($existingIndexes as $indexName => $existingIndex)
        {
            if (strtoupper($indexName) === 'PRIMARY')
            {
                array_unshift($this->queries, 'ALTER TABLE ' . $db->quoteName($db->getPrefix() . $tableName) . ' DROP PRIMARY KEY;');
            }
        }

        $query  = 'ALTER TABLE ' . $db->quoteName($db->getPrefix() . $tableName) . ' ADD CONSTRAINT ' . $db->getPrefix() . $tableName . '_pk PRIMARY KEY (' . implode(',', $db->quoteName($primaryKeys['field'])) . ');';
        $this->queries[] = $query;
    }


    /**
     * Get the list of indexes.
     *
     * @param   array  $indexes  The indexes to rework
     *
     * @return  array  List of indexes.
     *
     * @since   2.3.0
     */
    private function getIndexes(array $indexes): array
    {
        $cleanIndexes = [];

        foreach ($indexes as $index)
        {
            $cleanIndexes[$index->Key_name][$index->Seq_in_index] = $index->Column_name;
        }

        return $cleanIndexes;
    }

    /**
     * Run the queries.
     *
     * @return  void
     *
     * @throws  RuntimeException
     *
     * @since   2.3.0
     */
    private function runQueries(): void
    {
        if (!$this->queries)
        {
            return;
        }

        $db = $this->getDatabase();

        foreach ($this->queries as $query)
        {
            try
            {
                $db->setQuery($query)->execute();
            }
            catch (Exception $exception)
            {
                throw new RuntimeException(Text::sprintf('COM_SSO_FAILED_QUERY', $query, $exception->getMessage()));
            }
        }
    }
}
