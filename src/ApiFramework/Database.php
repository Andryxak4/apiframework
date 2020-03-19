<?php namespace ApiFramework;

/**
 * Database class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class Database extends Core {

    /**
     * @var PDO PDO Instance
     */
    public $pdo;

    /**
     * @var object PDO statement
     */
    public $statement;

    /**
     * @var string SQL query string
     */
    public $query;

    /**
     * @var string TABLE to query
     */
    public $table;

    /**
     * @var array Columns to retrieve in SELECT
     */
    public $columns;

    /**
     * @var array Tables to JOIN in SELECT
     */
    public $joins;

    /**
     * @var array BETWEEN conditions
     */
    public $betweens;

    /**
     * @var array REGEXP conditions
     */
    public $regexps;

    /**
     * @var array WHERE conditions
     */
    public $wheres;

    /**
     * @var array WHERE IN conditions
     */
    public $whereIn;

    /**
     * @var string Column to GROUP BY
     */
    public $groupBy;

    /**
     * @var string Columns to ORDER BY
     */
    public $orderBy;

    /**
     * @var string Query OFFSET
     */
    public $offset;

    /**
     * @var string Query LIMIT
     */
    public $limit;

    /**
     * @var string Columns to UPDATE or INSERT
     */
    public $fields;

    /**
     * @var int Number of retrieved records
     */
    private $count;

    /**
     * Class constructor
     *
     * @param App $app App instance
     * @param PDO $pdo PDO instance
     */
    public function __construct (App $app) {

        // Construct from parent
        parent::__construct($app);

        // Setup PDO
        $this->pdo = $this->app->pdo;

        // Set default values
        $this->reset();
    }

    /**
     * Reset to default values
     *
     * @return object Database instance
     */
    public function reset () {
        $this->table = '';
        $this->columns = [];
        $this->joins = [];
        $this->betweens = [];
        $this->wheres = [];
        $this->whereIn = [];
        $this->regexps = [];
        $this->groupBy = false;
        $this->orderBy = false;
        $this->offset = 0;
        $this->limit = 10;
        $this->fields = [];
        return $this;
    }

    /**
     * Sets the columns to be selected
     *
     * @param array $columns Columns to select
     * @return object Database instance
     */
    public function select ($columns) {
        if (is_array($columns)) {
            $this->columns = $columns;
        }
        return $this;
    }

    /**
     * Adds a column to be selected
     *
     * @param string $column Column to select
     * @return object Database instance
     */
    public function addSelect ($column) {
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Sets the TABLE to use
     *
     * @param string $table Table to use
     * @return object Database instance
     */
    public function table ($table) {

        // User other table
        $this->table = $table;

        // Return database instance
        return $this;
    }

    /**
     * Sets a table to JOIN with
     *
     * @param string $table Table name
     * @param string $first Left key for the ON condition
     * @param string $operator Operator for the ON condition
     * @param string $second Right key for the ON condition
     * @param string $type Join type
     * @return object Database instance
     */
    public function join ($table, $first, $operator, $second, $type = 'LEFT') {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];
        return $this;
    }

    /**
     * Sets a BETWEEN condition
     *
     * @param string $column Column name
     * @param string $value_from Value to match from
     * @param string $value_to Value to match to
     * @param string $table Table
     * @return object Database instance
     */
    public function between ($column, $value_from, $value_to, $table = null) {
        $table = !is_null($table) ? $table : $this->table;
        if ($value_from != '' && $value_to != '') {
            $this->betweens[] = [
                'table'         => $table,
                'column'        => $column,
                'value_from'    => $value_from,
                'value_to'      => $value_to,
            ];
        } else if($value_from != '') {
            $this->where($column, $value_from, '>', $this->table);
        } else if ($value_to != '') {
            $this->where($column, $value_to, '<', $this->table);
        }
        return $this;
    }

    /**
     * Sets a REGEXP condition
     *
     * @param string $column Column name
     * @param string $value Value to match
     * @param string $table Table
     * @return object Database instance
     */
    public function regexp ($column, $pattern, $table = null) {
        $table = !is_null($table) ? $table : $this->table;
        $this->regexps[] = [
            'table'         => $table,
            'column'        => $column,
            'pattern'       => $pattern,
        ];
        return $this;
    }

    /**
     * Sets a WHERE condition
     *
     * @param string $column Column name
     * @param string $value Value to match
     * @param string $operator Operator to compare with
     * @param string $table Table
     * @return object Database instance
     */
    public function where ($column, $value, $operator = '=', $table = null) {
        $table = $table? $table : $this->table;
        $this->wheres[] = [
            'table' => $table,
            'column' => $column,
            'value' => $value,
            'operator' => $operator
        ];
        return $this;
    }

    /**
     * Sets a WHERE IN condition
     *
     * @param string $column Column name
     * @param string $values Values to match
     * @param string $table Table
     * @return object Database instance
     */
    public function whereIn ($column, $values, $table = null) {
        $table = isset($table)? $table : $this->table;
        $this->whereIn[] = [
            'table' => $table,
            'column' => $column,
            'values' => $values
        ];
        return $this;
    }

    /**
     * Sets the group by column
     *
     * @param string $groupBy Column name
     * @return object Database instance
     */
    public function groupBy ($groupBy) {
        if (trim($groupBy) !== '') {
            $this->groupBy = $groupBy;
        }
        return $this;
    }

    /**
     * Sets the column to order by
     *
     * @param string $order Order column and type
     * @return object Database instance
     */
    public function orderBy ($order) {
        if (trim($order) !== '') {
            $this->orderBy[] = $order;
        }
        return $this;
    }

    /**
     * Sets the offset
     *
     * @param int $offset Offset number
     * @return object Database instance
     */
    public function offset ($offset) {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Sets the limit
     *
     * @param int $limit Limit number
     * @return object Database instance
     */
    public function limit ($limit) {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets the fields to be inserted or updated
     *
     * @param array $fields Array of columns names and values
     * @return object Database instance
     */
    public function fields ($fields) {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Performs a SELECT query
     *
     * @return bool|array Array of results, or false
     */
    public function get () {

        // Define columns
        $columns = count($this->columns)? $this->getColumnsString() : $this->table . '.*';

        // Define root
        $root = 'SELECT ' . $columns . ' FROM ' . $this->table;

        // Define joins
        $joins = count($this->joins)? $this->getJoinString() : '';

        // Define between conditions
        $between = $this->getBetweenString();

        // Define regexp conditions
        $regexp = $this->getRegexpString();

        // Define where conditions
        $where = $this->getWhereString();

        // Define where in conditions
        $whereIn = $this->getWhereInString();

        // Define group by
        $groupBy = ($this->groupBy)? 'GROUP BY ' . $this->groupBy : '';

        // Define order
        $orderBy = ($this->orderBy)? $this->getOrderByString() : '';

        // Define limit
        $limit = 'LIMIT ' . $this->offset . ',' . $this->limit;

        // Build query
        $this->query = implode(' ', [$root, $joins, $where, $whereIn, $between, $regexp,  $groupBy, $orderBy, $limit]);

        // Debug
        if ($this->app->config('debug.queries')) {
            $this->app->file->append($this->app->config('debug.queries'), date('Y-m-d h:i:s') . ' - ' . $this->query . "\r\n");
        }

        // Prepare statement
        $this->statement = $this->pdo->prepare($this->query);

        // Bind where values
        if (count($this->wheres)) {
            $this->bindWheres();
        }

        // Execute statement
        if (!$this->statement->execute()) {
            throw new \PDOException('Error reading from database', 500);
        }

        // Return results
        $result = $this->statement->fetchAll(\PDO::FETCH_ASSOC);

        // Reset
        $this->reset();

        // Return result
        return $result;
    }

    /**
     * Returns the first record found
     *
     * @return bool|array Record data, or false
     */
    function getOne () {

        // Perform query
        $result = $this->get();

        // Return the first matching record, or false if no records were found
        return is_array($result) ? reset($result) : false;
    }

    /**
     * Performs a SELECT COUNT query
     *
     * @param string $column Column to count by
     * @return int Number of rows matching the SELECT query
     */
    public function count ($column) {

        // Reset limit
        $this->limit(1000 * 1000);

        // Select only the COUNT
        $result = $this->select(['COUNT(' . $this->table . '.' . $column .') as count'])->get();

        // Reset
        $this->reset();

        // Return number of records
        return (count($result) === 1)? (int) reset($result)['count'] : count($result);
    }

    /**
     * Performs an INSERT query
     *
     * @param array $fields Array of columns names and values to insert
     * @return bool Query success or fail
     */
    public function insert ($fields) {

        // Set fields
        $this->fields($fields);

        // Define fields
        $fields = $this->getInsertFieldsString();

        // Build query
        $this->query = 'INSERT INTO ' . $this->table . $fields;

        // Prepare statement
        $this->statement = $this->pdo->prepare($this->query);

        // Bind fields values
        $this->bindFields();

        // Execute statement
        $result = $this->statement->execute();

        // Reset values
        $this->reset();

        // Return result
        return $result;
    }

    /**
     * Performs an INSERT query and returns the inserted record ID
     *
     * @param array $fields Array of columns names and values to insert
     * @return int Inserted record ID
     */
    public function insertGetId ($fields) {

        // Internal call to insert
        if (!$this->insert($fields)) {
            throw new \PDOException('Error writing to database', 500);
        }

        // Get last id
        return $this->pdo->lastInsertId();
    }

    /**
     * Performs an UPDATE query
     *
     * @param array $fields Array of columns names and values to insert
     * @return bool Query success or fail
     */
    public function update ($fields) {

        // Set fields
        $this->fields($fields);

        // Resolve fields
        $fields = $this->getUpdateFieldsString();

        // Define wheres
        $where = $this->getWhereString();

        // Build query
        $this->query = 'UPDATE ' . $this->table . ' SET ' . $fields . ' '. $where;

        // Prepare statement
        $this->statement = $this->pdo->prepare($this->query);

        // Bind fields values
        $this->bindFields();

        // Bind where values
        if (count($this->wheres)) {
            $this->bindWheres();
        }

        // Execute statement
        $result = $this->statement->execute();

        // Reset values
        $this->reset();

        // Return result
        return $result;
    }

    /**
     * Performs a DELETE query
     *
     * @return bool Query success or fail
     */
    public function delete () {

        // Define wheres
        $where = $this->getWhereString();

        // Define betweens
        $between = $this->getBetweenString();

        // Define where in
        $whereIn = $this->getWhereInString();

        // Define where regexp
        $regexp = $this->getRegexpString();

        // Build query
        $this->query = 'DELETE FROM ' . $this->table . ' '. implode(' ', [$where, $whereIn, $between, $regexp]);

        // Prepare statement
        $this->statement = $this->pdo->prepare($this->query);

        // Bind where values
        if (count($this->wheres)) {
            $this->bindWheres();
        }

        // Execute statement
        $result = $this->statement->execute();

        // Reset values
        $this->reset();

        // Return result
        return $result;
    }

    /**
     * Returns the last query as an string
     *
     * @return string Query
     */
    public function lastQuery () {
        return $this->query;
    }

    /**
     * Returns the last error, if any
     *
     * @return mixed Error
     */
    public function lastError () {
        return $this->statement->errorInfo();
    }

    /**
     * Returns the number of rows affected by the last query
     *
     * @return int Number of rows
     */
    public function affectedRows() {
        return $this->statement->rowCount();
    }

    /**
     * Returns the wheres array as a WHERE string
     *
     * @return string WHERE string
     */
    private function getWhereString () {

        // If there are not where conditions, return empty
        if (!count($this->wheres)) {
            return 'WHERE 1=1 ';
        }

        // Create where named placeholders
        $wheres = array_map(function ($where) {
            return $where['table'] . '.' . $where['column'] . ' ' . $where['operator'] . ' :' . $where['table'] . $where['column'];
        }, $this->wheres);

        // String holder
        return 'WHERE 1 AND ' . implode(' AND ', $wheres);
    }

    /**
     * Returns the betweens array as a WHERE string
     *
     * @return string WHERE string
     */
    private function getBetweenString () {

        // String holder
        $string = '';

        // If there are not betweens in conditions, return empty
        if (!count($this->betweens)) {
            return $string;
        }

        // Iterate where creating the full conditions
        foreach ($this->betweens as $where) {
            $string .= ' AND ' . $where['table'] . '.' . $where['column'] . ' BETWEEN "' . $where['value_from'] . '" AND "' . $where['value_to'] . '" ';
        }

        // Return complete where in string
        return $string;
    }

    /**
     * Returns the regexps array as a WHERE string
     *
     * @return string WHERE string
     */
    private function getRegexpString () {

        // String holder
        $string = '';

        // If there are not betweens in conditions, return empty
        if (!count($this->regexps)) {
            return $string;
        }

        // Iterate where creating the full conditions
        foreach ($this->regexps as $where) {
            $string .= ' AND ' . $where['table'] . '.' . $where['column'] . ' REGEXP "' . $where['pattern'] . '"';
        }

        // Return complete where in string
        return $string;
    }

    /**
     * Returns the where in array as a WHERE IN string
     *
     * @return string WHERE string
     */
    private function getWhereInString () {

        // String holder
        $string = '';

        // If there are not where in conditions, return empty
        if (!count($this->whereIn)) {
            return $string;
        }

        // Iterate where creating the full conditions
        foreach ($this->whereIn as $where) {
            $string .= ' AND ' . $where['column'] . ' IN (' . implode(', ', $where['values']) . ')';
        }

        // Return complete where in string
        return $string;
    }

    /**
     * Returns the columns array as a field list string
     *
     * @return string Field list string
     */
    private function getColumnsString () {
        $columns = array_map(function ($column) {
            return (strpos($column, '.') === false)? $this->table . '.' . $column : $column;
        }, $this->columns);
        return implode(', ', $columns);
    }

    /**
     * Returns the joins array as a JOIN string
     *
     * @return string JOIN string
     */
    private function getJoinString () {

        // If there are not joins, return empty
        if (!count($this->orderBy)) {
            return '';
        }

        // Create join strings
        $joins = array_map(function ($join) {
            return $join['type'] . ' JOIN ' . $join['table'] . ' ON ' . $this->table . '.' . $join['first'] . $join['operator'] . $join['table'] . '.' . $join['second'];
        }, $this->joins);

        // Return complete join string
        return implode(' ', $joins);
    }

    /**
     * Returns the orders array as an ORDER BY string
     *
     * @return string ORDER BY string
     */
    private function getOrderByString () {

        // If there are not order conditions, return empty
        if (!count($this->orderBy)) {
            return '';
        }

        // Return complete order string
        return 'ORDER BY ' . implode(', ', $this->orderBy);
    }

    /**
     * Returns the fields array as a VALUES string for inserts
     *
     * @return string VALUES string for inserts
     */
    private function getInsertFieldsString () {

        // Get field keys
        $fieldKeys = array_keys($this->fields);

        // Create placeholders
        $fieldsPlaceholders = array_map(function ($field) {
            return ':' . $field;
        }, $fieldKeys);

        // Return complete fields string
        return '(' . implode(',', $fieldKeys) . ') VALUES ('. implode(',', $fieldsPlaceholders) . ')';
    }

    /**
     * Returns the fields array as a 'foo=:foo, bar=:bar' string for updates
     *
     * @return string String for updates
     */
    private function getUpdateFieldsString () {

        // Create placeholders
        $fieldsPlaceholders = array_map(function ($field) {
            return $field . '=:' . $field;
        }, array_keys($this->fields));

        // Return complete fields string
        return implode(',', $fieldsPlaceholders);
    }

    /**
     * Binds the values of the WHERE conditions
     *
     */
    private function bindWheres () {
        foreach ($this->wheres as $where) {
            $this->statement->bindValue(':' . $where['table'] . $where['column'], $where['value']);
        }
    }

    /**
     * Binds the values of the fields to insert or update
     *
     */
    private function bindFields () {
        foreach ($this->fields as $key => $value) {
            $this->statement->bindValue(':' . $key, $value);
        }
    }
}