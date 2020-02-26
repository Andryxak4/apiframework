<?php namespace ApiFramework;

/**
 * Model class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class Model extends Core {

    /**
     * @var object Database instance
     */
    protected $db;

    /**
     * @var string Table name
     */
    protected $table = '';

    /**
     * @var string Records offset
     */
    protected $offset = 0;

    /**
     * @var string Records limit
     */
    protected $limit = 100;

    /**
     * @var string Order by
     */
    protected $orderBy = '';

    /**
     * @var string Records collection
     */
    protected $records;

    /**
     * @var string Primary key
     */
    protected $primaryKey = 'id';

    /**
     * @var array Columns to retrieve
     */
    protected $columns = [];

    /**
     * @var array Applicable filters
     */
    protected $filters = [];

    /**
     * @var array Fields that could be written
     */
    protected $fillable = [];

    /**
     * @var array Fields that have to be validated before writing
     */
    protected $validate = [];

    /**
     * @var array Model relationships declaration
     */
    protected $relationships = [
        'hasOne' => [],
        'hasMany' => [],
        'belongsToMany' => []
    ];

    /**
     * @var array Default validation rules
     */
    protected $rules = [];

    /**
     * @var array Validation rules
     */
    protected $defaultRules = [
        'alpha' => '/[a-zA-Z\s]+/',
        'numeric' => '/[0-9]+/',
        'alphanumeric' => '/[-\w\s]+/',
        'email' => '/[-\w]+(\.-\w+)*@[-\w]+(\.[-\w]+)*(\.[a-zA-Z]{2,6})/'
    ];

    /**
     * @var int Number of retrieved models
     */
    protected $count;

    /**
     * Class constructor
     *
     * @param App $app App instance
     */
    public function __construct (App $app) {

        // Construct from parent
        parent::__construct($app);

        // Setup configurations and boot
        $this->boot();
    }

    /**
     * Class constructor
     *
     */
    public function boot () {

        // Setup database
        $this->db = $this->app->db;

        // Set table name and columns to select
        $this->db->table($this->table)->select($this->columns);

        // Merge validation rules
        $this->rules = array_merge($this->defaultRules, $this->rules);

        // Return instance
        return $this;
    }

    /**
     * Sets the offset
     *
     * @param int $offset Offset number
     * @return object Model instance
     */
    public function offset ($offset) {
        if (is_numeric($offset)) {
            $this->offset = $offset;
        }
        return $this;
    }

    /**
     * Sets the limit
     *
     * @param int $limit Limit number
     * @return object Model instance
     */
    public function limit ($limit) {
        if (is_numeric($limit)) {
            $this->limit = $limit;
        }
        return $this;
    }

    /**
     * Sets the order
     *
     * @param int $orderBy Order column
     * @return object Model instance
     */
    public function orderBy ($orderBy) {
        if (is_string($orderBy)) {
            $this->orderBy = $orderBy;
        }
        return $this;
    }

    /**
     * Sets the columns to be selected
     *
     * @param array $columns Columns to retrieve
     * @return object Model instance
     */
    public function columns ($columns) {
        if (is_array($columns)) {
            $this->columns = $columns;
        }
        return $this;
    }

    /**
     * Sets a where condition
     *
     * @param string $column Column name
     * @param string $value Value to match
     * @param string $operator Operator to compare with
     * @return object Database instance
     */
    public function where ($column, $value, $operator = '=', $table = null) {
        $table = $table? $table : $this->table;
        $this->db->where($column, $value, $operator, $table);
        return $this;
    }

    /**
     * Sets a where in condition
     *
     * @param string $column Column name
     * @param string $values Values to match
     * @return object Database instance
     */
    public function whereIn ($column, $values, $table = null) {
        $this->db->whereIn($column, $values, $table);
        return $this;
    }

    /**
     * Gets the primary key
     *
     * @return string Primary key column name
     */
    public function getPrimaryKey () {
        return $this->primaryKey;
    }

    /**
     * Gets the table
     *
     * @return string Table name
     */
    public function getTable () {
        return $this->table;
    }

    /**
     * Sets an array of filters as where conditions
     *
     * @param array $params Search parameters
     * @return bool|array Array of results, or false
     */
    function search ($filters = []) {

        // Set resolved
        $resolved = [];

        // Check the filters
        if (!isset($filters) || !is_array($filters)) {
            throw new \InvalidArgumentException('Undefined search filters', 400);
        }

        // Set table
        $this->db->table = $this->table;

        // Set limit, offset and order
        if (isset($filters['limit'])) {
            $this->limit($filters['limit']);
        }
        if (isset($filters['offset'])) {
            $this->offset($filters['offset']);
        }
        if (isset($filters['order'])) {
            $this->orderBy($filters['order']);
        }

        // Remove invalid filters
        $filters = array_intersect_key($filters, array_flip(array_keys($this->filters)));

        // Set where in conditions
        foreach ($filters as $key => $value) {

            // Get filter data
            $current = $this->filters[$key];
            $filterName = $current[0];

            // Add condition
            foreach ($this->relationships['belongsToMany'] as $join) {
                if ($filterName === $join['alias']) {
                    $this->whereIn($join['pivot'] . '.' . $join['foreignKey'], (array) $value, $join['pivot']);
                    $resolved[] = $key;
                }
            }
        }

        // Set where conditions
        foreach ($filters as $key => $value) {
            if (!in_array($key, $resolved)) {

                // Get filter data
                $current = $this->filters[$key];
                $column = $current[0];
                $operator = isset($current[1])? $current[1] : '=';
                if ($operator === 'LIKE') {
                    $value = '%' . $value . '%';
                }

                // Add condition
                $table = isset($current[2])? $current[2] : $this->table;
                $this->where($column, $value, $operator, $table);
            }
        }

        // Return model instance
        return $this;
    }

    /**
     * Returns an array of models
     *
     * @return bool|array Array of models, or false
     */
    function get () {

        // Perform before hook
        $this->beforeGet();

        // Set basic query
        $this->db->table($this->table)->limit($this->limit)->offset($this->offset)->orderBy($this->orderBy);

        // Set columns to select
        $this->db->select($this->columns);

        // Resolve relationships
        $this->hasOne()->belongsToMany();

        // Get results
        $this->records = $this->db->get();

        // Add has many relationships
        $this->hasMany();

        // Format relationships
        $this->formatBelongsToMany()->formatHasOne();

        // Perform after hook
        $this->afterGet();

        // Return query results
        return $this->records;
    }

    /**
     * Returns the total number of models
     *
     * @param string $column Column to count by
     * @return int Total number of models
     */
    function count () {
        $this->db->table($this->table)->groupBy($this->table . '.' . $this->primaryKey);
        $this->hasOne()->belongsToMany();
        $count = $this->db->count($this->primaryKey);
        return $count;
    }

    /**
     * Returns a single model by primary key
     *
     * @param string $id Primary key value
     * @return bool|array Model attributes, or false
     */
    function find ($id = null) {

        // ID has to be defined
        if (!isset($id)) {
            throw new \InvalidArgumentException('Undefined ID', 400);
        }

        // Use the primary key for the where
        $this->db->table($this->table)->where($this->primaryKey, $id, '=', $this->table);

        // Return the first found record
        $result = $this->get();

        // Abort if no models where found
        if (!$result || !is_array($result) || empty($result)) {
            throw new \Exception('Element not found', 404);
        }

        // Return the first matching record
        return reset($result);
    }

    /**
     * Returns the first model found
     *
     * @return bool|array Model attributes, or false
     */
    function first () {

        // Perform query
        $result = $this->get();

        // Abort if no models where found
        if (!$result || !is_array($result) || empty($result)) {
            return false;
        }

        // Return the first matching model
        return reset($result);
    }

    /**
     * Creates a new model
     *
     * @param $attributes Model attributes
     * @return bool|array Created model ID, or false
     */
    function create ($attributes = []) {

        // Attributes have to be array
        if (!(isset($attributes)) || !is_array($attributes)) {
            throw new \InvalidArgumentException('Undefined attributes', 400);
        }

        // Perform before hook
        $attributes = $this->beforeCreate($attributes);

        // Remove non fillable attributes
        $fields = array_intersect_key($attributes, array_flip($this->fillable));

        // Validate attributes
        $errors = $this->validationErrors($fields);
        if ($errors) {
            $this->app->response->extra(['error' => ['errors' => $errors]]);
            throw new \InvalidArgumentException('Invalid attributes', 400);
        }

        // Create the model and return its ID
        $result = $this->db->table($this->table)->insertGetId($fields);

        // Return false if create fails
        if (!$result) {
            throw new \Exception('Could not create the model', 500);
        }

        // Sync many to many relationships
        $this->sync($result, $attributes);

        // Upsert has many relationships
        $this->updateHasMany($result, $attributes);

        // Get the created model
        $model = $this->find($result);

        // Run after hook
        $model = $this->afterCreate($result, $attributes, $model);

        // Return the created model
        return $model;
    }

    /**
     * Updates a model
     *
     * @param $id Model primary key
     * @param $attributes Model attributes
     * @return bool|array Updated model, or false
     */
    function update ($id = null, $attributes = []) {

        // ID has to be defined
        if (!isset($id)) {
            throw new \InvalidArgumentException('Undefined ID', 400);
        }

        // Attributes have to be array
        if (!(isset($attributes)) || !is_array($attributes)) {
            throw new \InvalidArgumentException('Undefined attributes', 400);
        }

        // Perform before hook
        $attributes = $this->beforeUpdate($id, $attributes);

        // Remove non fillable attributes
        $fields = array_intersect_key($attributes, array_flip($this->fillable));

        // Validate attributes
        $errors = $this->validationErrors($fields);
        if ($errors) {
            $this->app->response->extra(['error' => ['errors' => $errors]]);
            throw new \InvalidArgumentException('Invalid attributes', 400);
        }

        // Update the model
        if ($fields && count($fields)) {
            $result = $this->db->table($this->table)->where($this->primaryKey, $id, '=', $this->table)->update($fields);

            // Return false if the update failed
            if (!$result) {
                throw new \Exception('Could not update the model', 500);
            }
        }

        // Sync many to many relationships
        $this->sync($id, $attributes);

        // Upsert has many relationships
        $this->updateHasMany($id, $attributes);

        // Get the created model
        $model = $this->find($id);

        // Run after hook
        $model = $this->afterUpdate($id, $attributes, $model);

        // Return the created model
        return $model;
    }

    /**
     * Destroys a model
     *
     * @return bool|array Deleted model ID, or false
     */
    function destroy ($id = null) {

        // ID has to be defined
        if (!isset($id)) {
            throw new \InvalidArgumentException('Undefined ID', 400);
        }

        // Perform before hook
        $this->beforeDestroy($id);

        // Update the model
        $result = $this->db->table($this->table)->where($this->primaryKey, $id)->limit(1)->delete();

        // Return false on failures
        if (!$result) {
            throw new \Exception('Could not destroy the model', 500);
        }

        // Perform after hook
        $this->afterDestroy($id);

        // Return the destroyed model ID, or false on fail
        return $id;
    }

    /**
     * Hook that runs before getting an array of models
     *
     * @param bool Success or failure
     */
    function beforeGet () {
        return true;
    }

    /**
     * Hook that runs after getting an array of models
     *
     * @param bool Success or failure
     */
    function afterGet () {
        return true;
    }

    /**
     * Hook that runs before creating a model
     *
     * @param $attributes Original attributes
     * @return bool|array Customized attributes
     */
    function beforeCreate ($attributes) {
        return $attributes;
    }

    /**
     * Hook that runs after creating a model
     *
     * @param $id Model primary key
     * @param $attributes Create attributes
     * @param $model Created model
     * @return array Created model
     */
    function afterCreate ($id, $attributes, $model) {
        return $model;
    }

    /**
     * Hook that runs before updating a model
     *
     * @param $id Model primary key
     * @param $attributes Original attributes
     * @return bool|array Customized attributes
     */
    function beforeUpdate ($id, $attributes) {
        return $attributes;
    }

    /**
     * Hook that runs after updating a model
     *
     * @param $id Model primary key
     * @param $attributes Update attributes
     * @param $model Updated model
     * @return array Updated model
     */
    function afterUpdate ($id, $attributes, $model) {
        return $model;
    }

    /**
     * Hook that runs before deleting a model
     *
     * @param $id Model primary key
     * @return int Model primary key
     */
    function beforeDestroy ($id) {
        return $id;
    }

    /**
     * Hook that runs after deleting a model
     *
     * @param $id Model primary key
     * @return int Model primary key
     */
    function afterDestroy ($id) {
        return $id;
    }

    /**
     * Resolves one to one relationships
     *
     * @return object Model instance
     */
    private function hasOne () {

        // Return if there are no relationships of that type
        if (!isset($this->relationships['hasOne']) || empty($this->relationships['hasOne'])) {
            return $this;
        }

        // Iterate relationships
        foreach ($this->relationships['hasOne'] as $join) {

            // Perform join
            $this->db->join($join['table'], $join['localKey'], '=', $join['foreignKey']);

            // Add related columns to select
            foreach ($join['columns'] as $column) {
                $column = $join['table'] . '.' . $column . ' as _' . $join['alias'] . '_' . $column;
                $this->db->addSelect($column);
            }
        }

        // Return model instance
        return $this;
    }

    /**
     * Resolves one to many relationships
     *
     * @return object Model instance
     */
    private function hasMany () {

        // Return if there are no relationships of that type
        if (!isset($this->relationships['hasMany']) || empty($this->relationships['hasMany'])) {
            return $this;
        }

        // Get current records IDs
        $ids = array_column($this->records, 'id');

        // Return if no records where found
        if (empty($ids)) {
            return $this;
        }

        // Make a associative array from current records
        $records = array_combine($ids, $this->records);

        // Iterate relationships
        foreach ($this->relationships['hasMany'] as $join) {

            // Get relationship results trought the model
            if (isset($join['model'])) {
                if (isset($this->models) && isset($this->models[$join['model']])) {
                    $instance = $this->models[$join['model']];
                } else {
                    $instance = $this->app->make($join['model']);
                }
                $childs = $instance->whereIn($join['foreignKey'], $ids)->limit($join['limit'])->orderBy($join['orderBy'])->get();
            }

            // Or get relationship results by database
            if (!isset($join['model'])) {
                $childs = $this->db->table($join['table'])->select(array_merge([$join['foreignKey']], $join['columns']))->whereIn($join['foreignKey'], $ids)->limit($join['limit'])->orderBy($join['orderBy'])->get();
            }

            // Group them by foreign key
            if ($childs) {
                foreach ($childs as $child) {
                    $key = $join['foreignKey'];
                    $id = $child[$key];

                    // If the ID was not required as a column to show, leave it out
                    if (!in_array($key, $join['columns'])) {
                        unset($child[$key]);
                    }

                    // Save the result
                    $join['results'][$id][] = $child;
                }
            }

            // Add relationship results to the main collection
            foreach ($records as $key => $value) {
                $id = $value[$join['localKey']];
                $records[$id][$join['alias']] = (isset($join['results'][$id]))? $join['results'][$value[$join['localKey']]] : [];
            }
        }

        // Save the records with the resolved relationships
        $this->records = array_values($records);

        // Return model instance
        return $this;
    }

    /**
     * Resolves many to many relationships
     *
     * @return object Model instance
     */
    private function belongsToMany () {

        // Return if there are no relationships of that type
        if (!isset($this->relationships['belongsToMany']) || empty($this->relationships['belongsToMany'])) {
            return $this;
        }

        // Group by primary key
        $this->db->groupBy($this->table . '.' . $this->primaryKey);

        // Iterate relationships
        foreach ($this->relationships['belongsToMany'] as $join) {

            // Perform join
            $this->db->join($join['pivot'], $this->primaryKey, '=', $join['localKey']);

            // Add related columns to select
            $column = 'GROUP_CONCAT(' . $join['pivot'] . '.' . $join['foreignKey'] . ') as concat_' . $join['alias'];
            $this->db->addSelect($column);
        }

        // Return model instance
        return $this;
    }

    /**
     * Syncs many to many relationships
     *
     * @param $attributes Model attributes
     * @return object Model instance
     */
    private function sync ($id, $attributes) {

        // Return if the ID is invalid, or attributes is not an array
        if (!isset($id) || !isset($attributes) || empty($attributes)) {
            return $this;
        }

        // Return if there are no relationships of that type
        if (!isset($this->relationships['belongsToMany']) || empty($this->relationships['belongsToMany'])) {
            return $this;
        }

        // Sync each relationship
        foreach ($this->relationships['belongsToMany'] as $join) {

            // The sync option has to be true, the related attribute an array
            if (isset($join['sync']) && $join['sync'] && isset($attributes[$join['alias']]) && is_array($attributes[$join['alias']])) {

                // Delete old values
                $delete = $this->db->table($join['pivot'])->where($join['localKey'], $id, '=')->delete();

                // Insert new values
                foreach ($attributes[$join['alias']] as $value) {
                    $this->db->table($join['pivot'])->insert([$join['localKey'] => $id, $join['foreignKey'] => $value]);
                }
            }
        }

        // Return model instance
        return $this;
    }

    /**
     * Upserts one to many relationships
     *
     * @param $attributes Model attributes
     * @return object Model instance
     */
    private function updateHasMany ($id, $attributes) {

        // Return if the ID is invalid, or attributes is not an array
        if (!isset($id) || !isset($attributes) || empty($attributes)) {
            return $this;
        }

        // Return if there are no relationships of that type
        if (!isset($this->relationships['hasMany']) || empty($this->relationships['hasMany'])) {
            return $this;
        }

        // Sync each relationship
        foreach ($this->relationships['hasMany'] as $join) {

            // The sync option has to be true, the related attribute an array
            if (isset($join['sync']) && is_array($join['sync']) && isset($attributes[$join['alias']]) && is_array($attributes[$join['alias']])) {

                // Get related elements
                $related = $attributes[$join['alias']];

                // Write trough the model
                if (isset($join['model'])) {

                    // Get or instance the related model
                    if (isset($this->models) && isset($this->models[$join['model']])) {
                        $instance = $this->models[$join['model']];
                    } else {
                        $instance = $this->app->make($join['model']);
                    }

                    // Delete elements if the overwrite option was set
                    if (in_array('overwrite', $join['sync'])) {
                        $results = $instance->where($join['foreignKey'], $id, '=', $instance->getTable())->limit($join['limit'])->get();
                        if ($results) {
                            foreach ($results as $result) {
                                $instance->destroy($result[$instance->getPrimaryKey()]);
                            }
                        }
                    }

                    // Iterate related elements
                    foreach ($related as $value) {

                        // Add the foreign key
                        $value[$join['foreignKey']] = $id;

                        // Insert elements whitout ID
                        if (in_array('insert', $join['sync']) && !isset($value['id'])) {
                            $instance->create($value);
                        }

                        // Update elements width ID
                        if (in_array('update', $join['sync']) && isset($value['id'])) {

                            // Check if the existing element belongs to this record
                            $current = $instance->find($value['id']);
                            if ($current && isset($current[$join['foreignKey']]) && $current[$join['foreignKey']] == $id) {
                                $instance->update($value['id'], $value);
                            }
                        }
                    }
                }

                // Write trough database
                if (!isset($join['model'])) {

                    // Delete elements if the overwrite option was set
                    if (in_array('overwrite', $join['sync'])) {
                        $results = $this->db->table($join['table'])->where($join['foreignKey'], $id)->limit(100)->get();
                        if ($results) {
                            $ids = array_diff(array_column($results, 'id'), array_column($related, 'id'));
                            $this->db->table($join['table'])->whereIn('id', $ids)->delete();
                        }
                    }

                    // Iterate related elements
                    foreach ($related as $value) {

                        // Leave only writable fields
                        $fields = array_intersect_key($value, array_flip($join['columns']));

                        // Add the foreign key
                        $fields[$join['foreignKey']] = $id;

                        // Insert elements whitout ID
                        if (in_array('insert', $join['sync']) && !isset($value['id'])) {
                            $this->db->table($join['table'])->insert($fields);
                        }

                        // Update elements width ID
                        if (in_array('update', $join['sync']) && isset($value['id'])) {

                            // Check if the existing element belongs to this record
                            $current = $this->db->table($join['table'])->where('id', $value['id'])->limit(1)->getOne();
                            if ($current && isset($current[$join['foreignKey']]) && $current[$join['foreignKey']] == $id) {
                                $this->db->table($join['table'])->where('id', $value['id'])->update($fields);
                            }
                        }
                    }
                }
            }
        }

        // Return model instance
        return $this;
    }

    /**
     * Format many to many relationships results
     *
     * @return object Model instance
     */
    private function formatBelongsToMany () {

        // Return if there are no relationships of that type
        if (!isset($this->relationships['belongsToMany']) || empty($this->relationships['belongsToMany'])) {
            return $this;
        }

        // Iterate relationships
        foreach ($this->relationships['belongsToMany'] as $join) {
            $this->records = array_map(function ($record) use ($join) {
                foreach ($record as $key => $value) {
                    if ($key === 'concat_' . $join['alias']) {
                        $record[$join['alias']] = ($value)? explode(',', $value) : [];
                        unset($record[$key]);
                    }
                }
                return $record;
            }, $this->records);
        }

        // Return model instance
        return $this;
    }

    /**
     * Format has many relationships results
     *
     * @return object Model instance
     */
    private function formatHasOne () {

        // Return if there are no relationships of that type
        if (!isset($this->relationships['hasOne']) || empty($this->relationships['hasOne'])) {
            return $this;
        }

        // Iterate relationships
        foreach ($this->relationships['hasOne'] as $join) {
            $this->records = array_map(function ($record) use ($join) {
                foreach ($record as $key => $value) {
                    $prefix = '_' . $join['alias'] . '_';
                    if (preg_match('/^' . $prefix . '[\w]+/', $key)) {
                        $record[$join['alias']][str_replace($prefix, '', $key)] = $value;
                        unset($record[$key]);
                    }
                }
                return $record;
            }, $this->records);
        }

        // Return model instance
        return $this;
    }

    /**
     * Validates a model
     *
     * @param $attributes Attributes to validate
     * @param $rules Rules to validate
     * @return array|bool Array of errors, or false if the model is valid
     */
    function validationErrors ($attributes, $rules = null) {

        // Ruleset
        $ruleSet = isset($rules)? $rules : $this->validate;

        // Errors collector
        $errors = [];

        // Leave only the validable attributes
        $attributes = array_intersect_key($attributes, array_flip(array_keys($ruleSet)));

        // Check for required attributes
        foreach ($ruleSet as $key => $rules) {
            if (in_array('required', $rules) && (!isset($attributes[$key]) || trim($attributes[$key]) === '')) {
                $errors[$key] = 'required';
            }
            $ruleSet[$key] = array_diff($rules, ['required']);
        }

        // Check for format errors
        foreach ($attributes as $key => $value) {
            foreach ($ruleSet[$key] as $name => $rule) {
                if (!preg_match($this->rules[$rule], $value)) {
                    $errors[$key] = $rule;
                }
            }
        }

        // Return errors or false
        return count($errors)? $errors : false;
    }

    /**
     * Returns the limit and offset options
     *
     * @param int $limit Limit number
     * @return object Model instance
     */
    public function pagination () {
        return ['offset' => (int) $this->offset, 'limit' => (int) $this->limit];
    }

}