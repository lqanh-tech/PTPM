<?php

abstract class BaseModel {
    protected static $table;
    protected static $primaryKey = 'id';
    protected static $timestamps = true;
    protected static $fillable = [];
    protected static $hidden = [];
    
    protected $attributes = [];
    protected $original = [];
    protected $exists = false;
    
    private static $optimizer;
    
    public function __construct($attributes = []) {
        if (self::$optimizer === null) {
            self::$optimizer = DatabaseOptimizer::getInstance();
        }
        
        $this->fill($attributes);
        $this->syncOriginal();
    }
    
    public static function all() {
        $sql = "SELECT * FROM " . static::getTable();
        $results = self::$optimizer->executeQuery($sql, [], true);
        
        return array_map(function($row) {
            return new static($row);
        }, $results);
    }
    
    public static function find($id) {
        $sql = "SELECT * FROM " . static::getTable() . " WHERE " . static::getPrimaryKey() . " = ? LIMIT 1";
        $results = self::$optimizer->executeQuery($sql, [$id], true);
        
        if (empty($results)) {
            return null;
        }
        
        $instance = new static($results[0]);
        $instance->exists = true;
        return $instance;
    }
    
    public static function where($column, $operator = '=', $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        return new QueryBuilder(static::class, $column, $operator, $value);
    }
    
    public static function create($attributes) {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }
    
    public function save() {
        if ($this->exists) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }
    
    private function insert() {
        $attributes = $this->getAttributesForSave();
        
        if (static::$timestamps) {
            $attributes['created_at'] = date('Y-m-d H:i:s');
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $columns = array_keys($attributes);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO " . static::getTable() . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        self::$optimizer->executeQuery($sql, array_values($attributes), false);
        
        $db = Database::getInstance()->getConnection();
        $this->attributes[static::getPrimaryKey()] = $db->lastInsertId();
        
        $this->exists = true;
        $this->syncOriginal();
        
        return true;
    }
    
    private function update() {
        $attributes = $this->getDirtyAttributes();
        
        if (empty($attributes)) {
            return true;
        }
        
        if (static::$timestamps) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $sets = array_map(function($column) {
            return "$column = ?";
        }, array_keys($attributes));
        
        $sql = "UPDATE " . static::getTable() . " SET " . implode(', ', $sets) . " WHERE " . static::getPrimaryKey() . " = ?";
        
        $values = array_values($attributes);
        $values[] = $this->getKey();
        
        self::$optimizer->executeQuery($sql, $values, false);
        
        $this->syncOriginal();
        
        return true;
    }
    
    public function delete() {
        if (!$this->exists) {
            return false;
        }
        
        $sql = "DELETE FROM " . static::getTable() . " WHERE " . static::getPrimaryKey() . " = ?";
        self::$optimizer->executeQuery($sql, [$this->getKey()], false);
        
        $this->exists = false;
        
        return true;
    }
    
    public function fill($attributes) {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        
        return $this;
    }
    
    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
        return $this;
    }
    
    public function getAttribute($key) {
        return $this->attributes[$key] ?? null;
    }
    
    public function __get($key) {
        return $this->getAttribute($key);
    }
    
    public function __set($key, $value) {
        $this->setAttribute($key, $value);
    }
    
    public function __isset($key) {
        return isset($this->attributes[$key]);
    }
    
    public function toArray() {
        $attributes = $this->attributes;
        
        foreach (static::$hidden as $hidden) {
            unset($attributes[$hidden]);
        }
        
        return $attributes;
    }
    
    public function toJson() {
        return json_encode($this->toArray());
    }
    
    protected function getKey() {
        return $this->getAttribute(static::getPrimaryKey());
    }
    
    protected function isFillable($key) {
        return empty(static::$fillable) || in_array($key, static::$fillable);
    }
    
    protected function getAttributesForSave() {
        $attributes = $this->attributes;
        
        if (!$this->exists) {
            unset($attributes[static::getPrimaryKey()]);
        }
        
        return $attributes;
    }
    
    protected function getDirtyAttributes() {
        $dirty = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        
        return $dirty;
    }
    
    protected function syncOriginal() {
        $this->original = $this->attributes;
    }
    
    protected static function getTable() {
        return static::$table;
    }
    
    protected static function getPrimaryKey() {
        return static::$primaryKey;
    }
}

class QueryBuilder {
    private $model;
    private $wheres = [];
    private $orders = [];
    private $limit;
    private $offset;
    
    public function __construct($model, $column = null, $operator = null, $value = null) {
        $this->model = $model;
        
        if ($column !== null) {
            $this->where($column, $operator, $value);
        }
    }
    
    public function where($column, $operator = '=', $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [$column, $operator, $value];
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC') {
        $this->orders[] = [$column, $direction];
        return $this;
    }
    
    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }
    
    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }
    
    public function get() {
        $sql = $this->buildQuery();
        $bindings = $this->getBindings();
        
        $optimizer = DatabaseOptimizer::getInstance();
        $results = $optimizer->executeQuery($sql, $bindings, true);
        
        return array_map(function($row) {
            $instance = new $this->model($row);
            $instance->exists = true;
            return $instance;
        }, $results);
    }
    
    public function first() {
        $this->limit(1);
        $results = $this->get();
        
        return empty($results) ? null : $results[0];
    }
    
    public function count() {
        $sql = $this->buildCountQuery();
        $bindings = $this->getBindings();
        
        $optimizer = DatabaseOptimizer::getInstance();
        $result = $optimizer->executeQuery($sql, $bindings, true);
        
        return (int)$result[0]['count'];
    }
    
    private function buildQuery() {
        $table = $this->model::getTable();
        $sql = "SELECT * FROM $table";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . $this->buildWhereClause();
        }
        
        if (!empty($this->orders)) {
            $sql .= " ORDER BY " . $this->buildOrderClause();
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
        }
        
        if ($this->offset !== null) {
            $sql .= " OFFSET " . $this->offset;
        }
        
        return $sql;
    }
    
    private function buildCountQuery() {
        $table = $this->model::getTable();
        $sql = "SELECT COUNT(*) as count FROM $table";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . $this->buildWhereClause();
        }
        
        return $sql;
    }
    
    private function buildWhereClause() {
        $clauses = [];
        
        foreach ($this->wheres as $where) {
            $clauses[] = $where[0] . ' ' . $where[1] . ' ?';
        }
        
        return implode(' AND ', $clauses);
    }
    
    private function buildOrderClause() {
        $clauses = [];
        
        foreach ($this->orders as $order) {
            $clauses[] = $order[0] . ' ' . $order[1];
        }
        
        return implode(', ', $clauses);
    }
    
    private function getBindings() {
        $bindings = [];
        
        foreach ($this->wheres as $where) {
            $bindings[] = $where[2];
        }
        
        return $bindings;
    }
}