<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Map;

use Propel\Runtime\Map\Exception\TableNotFoundException;
use Propel\Runtime\Propel;

/**
 * DatabaseMap is used to model a database.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime. These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 */
class DatabaseMap
{
    /**
     * True if all tables were loaded.
     *
     * @var bool
     */
    protected $loadedTables = false;

    /**
     * Holds all registered tables.
     *
     * @var array<int, class-string>
     */
    protected $registeredTables = [];

    /**
     * SHows if the table was successfully resolved by its name.
     *
     * @var array<string, bool>
     */
    protected $resolvedTableNames = [];

    /**
     * Name of the database.
     *
     * @var string
     */
    protected $name;

    /**
     * Tables in the database, using table name as key
     *
     * @var array<\Propel\Runtime\Map\TableMap|class-string<\Propel\Runtime\Map\TableMap>>
     */
    protected $tables = [];

    /**
     * Tables in the database, using table phpName as key
     *
     * @var array<\Propel\Runtime\Map\TableMap|class-string<\Propel\Runtime\Map\TableMap>>
     */
    protected $tablesByPhpName = [];

    /**
     * @param string $name Name of the database.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of this database.
     *
     * @return string The name of the database.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add a new table to the database by name.
     *
     * @param string $tableName The name of the table.
     *
     * @return \Propel\Runtime\Map\TableMap The newly created TableMap.
     */
    public function addTable($tableName)
    {
        $this->tables[$tableName] = new TableMap($tableName, $this);

        return $this->tables[$tableName];
    }

    /**
     * Add a new table object to the database.
     *
     * @param \Propel\Runtime\Map\TableMap $table The table to add
     *
     * @return void
     */
    public function addTableObject(TableMap $table)
    {
        $table->setDatabaseMap($this);

        $tableName = $table->getName();
        if ($tableName && (!$this->hasTable($tableName) || is_string($this->tables[$tableName]))) {
            $this->tables[$tableName] = $table;
        }

        $phpName = $table->getClassName();
        $this->addTableByPhpName($phpName, $table);
    }

    /**
     * @param string|null $phpName
     * @param \Propel\Runtime\Map\TableMap|class-string<\Propel\Runtime\Map\TableMap> $tableOrClassMap
     *
     * @return void
     */
    protected function addTableByPhpName(?string $phpName, $tableOrClassMap): void
    {
        if (!$phpName) {
            return;
        }
        if ($phpName[0] !== '\\') {
            $phpName = '\\' . $phpName;
        }
        $this->tablesByPhpName[$phpName] = $tableOrClassMap;
    }

    /**
     * Add a new table to the database, using the tablemap class name.
     *
     * @param string $tableMapClass The name of the table map to add
     *
     * @return \Propel\Runtime\Map\TableMap The TableMap object
     */
    public function addTableFromMapClass($tableMapClass)
    {
        $table = new $tableMapClass();
        $this->addTableObject($table);

        return $this->getTable($table->getName());
    }

    /**
     * Registers a table map classes (by qualified name) as table belonging
     * to this database.
     *
     * Classes added like this will only be instantiated when accessed
     * through {@link DatabaseMap::getTable()},
     * {@link DatabaseMap::getTableByPhpName()}, or
     * {@link DatabaseMap::getTables()}
     *
     * @param class-string<\Propel\Runtime\Map\TableMap>|string $tableMapClass The name of the table map to add
     *
     * @return void
     */
    public function registerTableMapClass(string $tableMapClass): void
    {
        $tableName = $tableMapClass::TABLE_NAME;
        $this->tables[$tableName] = $tableMapClass;

        $tablePhpName = $tableMapClass::TABLE_PHP_NAME;
        $this->addTableByPhpName($tablePhpName, $tableMapClass);
    }

    /**
     * Registers a list of table map classes (by qualified name) as table maps
     * belonging to this database.
     *
     * @param array<class-string> $tableMapClasses
     *
     * @return void
     */
    public function registerTableMapClasses(array $tableMapClasses): void
    {
        $this->registeredTables = array_unique(array_merge($this->registeredTables, $tableMapClasses));
    }

    /**
     * Tries to resolve a table by the name via PHP name class.
     *
     * @return bool if the table was resolved by the name
     */
    protected function loadTableMap($name): bool
    {
        if ($this->loadedTables) {
            return true;
        }
        if (isset($this->resolvedTableNames[$name])) {
            return $this->resolvedTableNames[$name];
        }
        $className = ucfirst(str_replace('_', '', ucwords($name, '_')));
        $className .= 'TableMap';
        $results = array_filter($this->registeredTables, function ($registeredTableName) use ($className) {
            return str_ends_with($registeredTableName, $className);
        });

        array_map([$this, 'registerTableMapClass'], $results);
        $this->resolvedTableNames[$name] = count($results) > 0;

        return $this->resolvedTableNames[$name];
    }

    /**
     * Loads all registered tables classes and fills in name and PHP name lookup indices.
     *
     * @return void
     */
    protected function loadTableMaps(): void
    {
        if ($this->loadedTables) {
            return;
        }

        array_map([$this, 'registerTableMapClass'], $this->registeredTables);
        $this->loadedTables = true;
    }

    /**
     * Does this database contain this specific table?
     *
     * @param string $name The String representation of the table.
     *
     * @return bool True if the database contains the table.
     */
    public function hasTable($name)
    {
        if (strpos($name, '.') > 0) {
            $name = substr($name, 0, strpos($name, '.'));
        }

        if (isset($this->tables[$name])) {
            return true;
        }

        return $this->loadTableMap($name) && isset($this->tables[$name]);
    }

    /**
     * Get a TableMap for the table by name.
     *
     * @param string $name Name of the table.
     *
     * @throws \Propel\Runtime\Map\Exception\TableNotFoundException If the table is undefined
     *
     * @return \Propel\Runtime\Map\TableMap A TableMap
     */
    public function getTable($name)
    {
        if (!isset($this->tables[$name])) {
            $this->loadTableMap($name);
            if (!isset($this->tables[$name])) {
                $this->loadTableMaps();
            }

            if (!isset($this->tables[$name])) {
                throw new TableNotFoundException(sprintf('Cannot fetch TableMap for undefined table `%s` in database `%s`.', $name, $this->getName()));
            }
        }

        $tableOrClass = $this->tables[$name];

        return is_string($tableOrClass) ? $this->addTableFromMapClass($tableOrClass) : $tableOrClass;
    }

    /**
     * Get a TableMap[] of all of the tables in the database.
     *
     * @return \Propel\Runtime\Map\TableMap[]
     */
    public function getTables()
    {
        foreach ($this->tables as $tableOrClassMap) {
            if (!is_string($tableOrClassMap)) {
                continue;
            }
            $this->addTableFromMapClass($tableOrClassMap);
        }

        /** @var array<\Propel\Runtime\Map\TableMap> */
        return $this->tables;
    }

    /**
     * Get a ColumnMap for the column by name.
     * Name must be fully qualified, e.g. book.AUTHOR_ID
     *
     * @param string $qualifiedColumnName Name of the column.
     *
     * @return \Propel\Runtime\Map\ColumnMap A TableMap
     */
    public function getColumn($qualifiedColumnName)
    {
        [$tableName, $columnName] = explode('.', $qualifiedColumnName);

        return $this->getTable($tableName)->getColumn($columnName, false);
    }

    /**
     * @param string $phpName
     *
     * @throws \Propel\Runtime\Map\Exception\TableNotFoundException
     *
     * @return \Propel\Runtime\Map\TableMap
     */
    public function getTableByPhpName($phpName)
    {
        if ($phpName[0] !== '\\') {
            $phpName = '\\' . $phpName;
        }
        if (isset($this->tablesByPhpName[$phpName])) {
            $tableOrClassMap = $this->tablesByPhpName[$phpName];

            return is_string($tableOrClassMap) ? $this->addTableFromMapClass($tableOrClassMap) : $tableOrClassMap;
        }

        if (class_exists($tmClass = $phpName . 'TableMap')) {
            $this->addTableFromMapClass($tmClass);

            return $this->tablesByPhpName[$phpName];
        }

        if (
            class_exists($tmClass = substr_replace($phpName, '\\Map\\', (int)strrpos($phpName, '\\'), 1) . 'TableMap')
            || class_exists($tmClass = '\\Map\\' . $phpName . 'TableMap')
        ) {
            $this->addTableFromMapClass($tmClass);

            if (isset($this->tablesByPhpName[$phpName])) {
                return $this->tablesByPhpName[$phpName];
            }

            if (isset($this->tablesByPhpName[$phpName])) {
                return $this->tablesByPhpName[$phpName];
            }
        }

        throw new TableNotFoundException(sprintf('Cannot fetch TableMap for undefined table phpName: %s in database %s.', $phpName, $this->getName()));
    }

    /**
     * Convenience method to get the AdapterInterface registered with Propel for this database.
     *
     * @see Propel::getServiceContainer()->getAdapter(string) .
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface
     */
    public function getAbstractAdapter()
    {
        return Propel::getServiceContainer()->getAdapter($this->name);
    }
}
