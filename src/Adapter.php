<?php

namespace EFxPHP;

use Exception;
use PDO;
use PDOException;
use PHPAnnotations\Reflection\ReflectionProperty;

class Adapter {
    /** @var PDO $db */
    private $db;
    /** @var string $server */
    private $server = '';
    /** @var string $dbName */
    private $dbName = '';
    /** @var string $username */
    private $username = '';
    /** @var string $password */
    private $password = '';
    /** @var array $models */
    private $models = [];

    /**
     * Registers a class as a model handler.
     * @param object $model
     * @param bool $migrate
     * @throws Exception
     */
    public function registerModel($model, $migrate = false) {
        $this->checkDB();

        if (!class_has_annotation($model, '\EFxPHP\Models\Model'))
            throw new Exception("No Model annotation found in class " . get_class($model));

        $table = get_class_annotation($model, '\EFxPHP\Models\Model')->name;

        if (!isset($this->models[$this->dbName])) $this->models[$this->dbName] = [];

        $this->models[$this->dbName][$table] = $model;

        if ($migrate) $this->migrate($model, $table);
    }

    /**
     * Checks if a models has been registered for the given db - table pair.
     * @param string $model
     * @return bool
     * @throws Exception
     */
    public function isModelRegistered(string $model) {
        $this->checkDB();

        if (!isset($this->models[$this->dbName])) return false;
        if (!isset($this->models[$this->dbName][$model])) return false;

        return true;
    }

    /**
     * Gets the model registered for the given db - table pair.
     * @param string $table
     * @return object
     * @throws Exception
     */
    public function getModel(string $table) {
        if (!$this->isModelRegistered($table)) return null;

        return $this->models[$this->dbName][$table];
    }

    private function migrate($model, string $table) {
        $this->checkDB();

        // If it has been created there is no need to check for updates
        if ($this->createTableIfNotExists($model, $table))
            return;

        $this->updateTable($model, $table);
    }

    private function tableExists(string $table) {
        $exists = true;

        try {
            $this->db->query("SELECT 1 FROM $table");
        } catch (PDOException $ex) {
            $exists = false;
        }

        return $exists;
    }

    private function createTableIfNotExists($model, string $table) {
        if ($this->tableExists($table))
            return false;

        $query = "CREATE TABLE $table (\nid INT AUTO_INCREMENT PRIMARY KEY,\n";

        foreach (get_class_properties_annotations($model) as $name => $property) {
            $name = strtolower($name);

            /** @var ReflectionProperty $property */
            if (!$property->hasAnnotation("EFxPHP\Models\Fields\Type") || $name === 'id')
                continue;

            $type = $property->getAnnotation("EFxPHP\Models\Fields\Type");

            $query .= "$name {$type->type}";

            if ($type->length > 0)
                $query .= "({$type->length})";

            if (!$property->hasAnnotation("EFxPHP\Models\Fields\Nullable"))
                $query .= " NOT NULL";

            if ($property->hasAnnotation("EFxPHP\Models\Fields\Default")) {
                $default = $property->getAnnotation("EFxPHP\Models\Fields\Default");

                $query .= " DEFAULT ";

                switch ($type->getDatatype()) {
                    case 'Numeric':
                        $query.= "{$default->value}";
                        break;

                    default:
                        $query.= "'{$default->value}'";
                        break;
                }
            }

            $query .= ",\n";
        }

        $query = trim($query, ",\n");
        $query .= "\n);";

        $this->db->exec($query);

        return true;
    }

    private function updateTable($model, string $table) {
        $fields = array_keys((array)$model);
        $add = [];
        $remove = [];

        $columns = [];

        foreach ($this->getTableColumns($table) as $column) {
            if ($column['Field'] === 'id')
                continue;

            $columns[] = $column['Field'];
        }

        foreach ($fields as $field)
            if (!in_array($field, $columns))
                $add[] = $field;

        foreach ($columns as $column)
            if (!in_array($column, $fields))
                $remove[] = $column;

        if ((count($add) + count($remove)) === 0)
            return false;

        $query = "ALTER TABLE $table\n";

        foreach ($add as $name) {
            if (!property_has_annotation($model, $name, "EFxPHP\Models\Fields\Type"))
                continue;

            $type = get_property_annotation($model, $name, "EFxPHP\Models\Fields\Type");

            $query .= "ADD COLUMN $name {$type->type}";

            if ($type->length > 0)
                $query .= "({$type->length})";

            if (!property_has_annotation($model, $name, "EFxPHP\Models\Fields\Nullable"))
                $query .= " NOT NULL";

            if (property_has_annotation($model, $name, "EFxPHP\Models\Fields\Default")) {
                $default = get_property_annotation($model, $name, "EFxPHP\Models\Fields\Default");

                $query .= " DEFAULT ";

                switch ($type->getDatatype()) {
                    case 'Numeric':
                        $query.= "{$default->value}";
                        break;

                    default:
                        $query.= "'{$default->value}'";
                        break;
                }
            }

            $query .= ",\n";
        }

        foreach ($remove as $name)
            $query .= "DROP COLUMN $name,\n";

        $query = trim($query, ",\n") . ";";

        $this->db->exec($query);

        return true;
    }

    private function getTableColumns($table) {
        return ($this->db->query("SHOW COLUMNS FROM $table")->fetchAll());
    }

    /**
     * Connects to a mysql database.
     * @param string $server
     * @param string $username
     * @param string $password
     * @param string $dbName
     */
    public function connect(string $server, string $username, string $password, string $dbName = '') {
        $this->db = null;
        $this->username = $username;
        $this->password = $password;
        $this->server = $server;

        if ($dbName !== '') $this->selectDB($dbName);
    }

    /**
     * Selects the database.
     * @param $dbName
     */
    public function selectDB($dbName) {
        $this->db = null;
        $this->db = new PDO("mysql:host={$this->server};dbname=$dbName", $this->username, $this->password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->dbName = $dbName;
    }

    private function checkDB() { if ($this->dbName === '') throw new \Exception("No database has been selected!"); }

    /**
     * Finds the first item in the collection that matches the filters.
     * @param string $table
     * @param Filter[]|Filter $filters
     * @param array $options
     * @return Result
     * @throws Exception
     */
    public function findOne(string $table, $filters = [], $options = []) {
        $options['limit'] = 1;

        return $this->find($table, $filters, $options);
    }

    /**
     * Finds the items in the collection that match the filters.
     * @param string $table
     * @param Filter[]|Filter $filters
     * @param array $options
     * @return Result
     * @throws Exception
     */
    public function find(string $table, $filters = [], $options = []) {
        $this->checkDB();

        if (is_a($filters, '\EFxPHP\Filter'))
            $filters = [$filters];

        $query = "SELECT * FROM $table ";

        if (count($filters) > 0) {
            $query .= "WHERE ";

            foreach ($filters as $filter)
                $query .= $filter->buildFilterQuery() . " AND ";

            $query = trim($query, " AND ");
        }

        foreach ($options as $key => $option) {
            switch ($key) {
                case 'limit':
                    $query .= " LIMIT $option";

                    break;
            }
        }

        $rows = $this->db->query("$query;");

        return $this->getResult($rows, $table);
    }

    /**
     * Inserts an item.
     * @param object $item
     * @return mixed
     * @throws Exception
     */
    public function insert($item) { $this->bulkInsert([$item]); }

    /**
     * Inserts an array of items.
     * @param array $items
     * @throws Exception
     */
    public function bulkInsert(array $items) {
        $this->checkDB();

        if (!is_array($items) || count($items) == 0) return;

        if (!class_has_annotation($items[0], "\EFxPHP\Models\Model"))
            throw new Exception("No Model annotation found in class " . get_class($items[0]));

        $table = get_class_annotation($items[0], "\EFxPHP\Models\Model")->name;

        $this->db->beginTransaction(); // also helps speed up your inserts.
        $insert_values = array();

        $columns = [];

        foreach ($this->getTableColumns($table) as $column) {
            if ($column['Field'] === 'id')
                continue;

            $columns[] = $column['Field'];
        }

        $query = "INSERT INTO $table (id, " . implode(", ", $columns) . ") VALUES ";

        foreach ($items as $item) {
            $itemAsArray = (array)$item;
            $values = [];

            foreach ($columns as $column) {
                $value = isset(($itemAsArray)[$column]) ? $itemAsArray[$column] : null;

                if (is_null($value)) {
                    if (property_has_annotation($item, $column, "EFxPHP\Models\Fields\Default"))
                        $value = "DEFAULT";
                    else
                        $value = "NULL";
                } else {
                    switch (get_property_annotation($item, $column, "EFxPHP\Models\Fields\Type")->getDatatype()) {
                        case 'String':
                            $value = "'$value'";
                            break;

                        default:
                            break;
                    }
                }

                $values[] = $value;
            }

            $query .= "(NULL, " . implode(", ", $values) . "), ";
        }

        $query = trim($query, ", ") . ";";

        $this->db->query($query);
    }

    /**
     * Deletes an item.
     * @param object $item
     * @throws Exception
     */
    public function delete($item) {
        $id = -1;

        if (isset(((array)$item)['id']))
            $id = $item->id;

        // TODO: implement
    }

    private function getResult(\PDOStatement $rows, $table = '') {
        $result = [];

        foreach ($rows->fetchAll() as $row) $result[] = $row;

        return new Result($result, $this->dbName, $table, $this);
    }
}