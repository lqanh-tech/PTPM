<?php

/**
 * Enhanced Base Model with Active Record Pattern
 * Modern ORM-like functionality for all models
 */

abstract class BaseModel
{
    protected static $table = '';
    protected static $primaryKey = 'id';
    protected static $timestamps = true;
    protected static $fillable = [];
    protected static $hidden = [];

    protected $attributes = [];
    protected $original = [];
    protected $exists = false;

    private static $db = null;

    public function __construct($attributes = [])
    {
        if (self::$db === null) {
            self::$db = Database::getInstance()->getConnection();
        }

        $this->fill($attributes);
        $this->syncOriginal();
    }

    /**
     * Get table name
     */
    protected static function getTable()
    {
        return static::$table ?: strtolower(static::class) . 's';
    }

    /**
     * Get primary key
     */
    protected static function getPrimaryKey()
    {
        return static::$primaryKey;
    }

    /**
     * Fill model with attributes
     */
    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (empty(static::$fillable) || in_array($key, static::$fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Sync original attributes
     */
    private function syncOriginal()
    {
        $this->original = $this->attributes;
    }

    /**
     * Get all records
     */
    public static function all()
    {
        $sql = "SELECT * FROM " . static::getTable();
        $stmt = self::$db->prepare($sql);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $model = new static($row);
            $model->exists = true;
            $model->syncOriginal();
            $results[] = $model;
        }

        return $results;
    }

    /**
     * Find record by ID
     */
    public static function find($id)
    {
        $sql = "SELECT * FROM " . static::getTable() . " WHERE " . static::getPrimaryKey() . " = ? LIMIT 1";
        $stmt = self::$db->prepare($sql);
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $model = new static($row);
        $model->exists = true;
        $model->syncOriginal();

        return $model;
    }

    /**
     * Find records by condition
     */
    public static function where($column, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $sql = "SELECT * FROM " . static::getTable() . " WHERE $column $operator ?";
        $stmt = self::$db->prepare($sql);
        $stmt->execute([$value]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $model = new static($row);
            $model->exists = true;
            $model->syncOriginal();
            $results[] = $model;
        }

        return $results;
    }

    /**
     * Save model to database
     */
    public function save()
    {
        if ($this->exists) {
            return $this->update();
        }

        return $this->insert();
    }

    /**
     * Insert new record
     */
    private function insert()
    {
        $attributes = $this->getAttributesForSave();

        if (static::$timestamps) {
            $attributes['created_at'] = date('Y-m-d H:i:s');
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $columns = array_keys($attributes);
        $placeholders = array_fill(0, count($attributes), '?');

        $sql = "INSERT INTO " . static::getTable() . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = self::$db->prepare($sql);
        $result = $stmt->execute(array_values($attributes));

        if ($result) {
            $this->attributes[static::getPrimaryKey()] = self::$db->lastInsertId();
            $this->exists = true;
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    /**
     * Update existing record
     */
    private function update()
    {
        $attributes = $this->getAttributesForSave();

        if (static::$timestamps) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $setParts = [];
        $values = [];

        foreach ($attributes as $column => $value) {
            $setParts[] = "$column = ?";
            $values[] = $value;
        }

        $values[] = $this->attributes[static::getPrimaryKey()];

        $sql = "UPDATE " . static::getTable() . " SET " . implode(', ', $setParts) . " WHERE " . static::getPrimaryKey() . " = ?";

        $stmt = self::$db->prepare($sql);
        $result = $stmt->execute($values);

        if ($result) {
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    /**
     * Delete record
     */
    public function delete()
    {
        if (!$this->exists) {
            return false;
        }

        $sql = "DELETE FROM " . static::getTable() . " WHERE " . static::getPrimaryKey() . " = ?";
        $stmt = self::$db->prepare($sql);

        return $stmt->execute([$this->attributes[static::getPrimaryKey()]]);
    }

    /**
     * Get attributes for save (exclude hidden)
     */
    private function getAttributesForSave()
    {
        $attributes = $this->attributes;

        // Remove primary key for insert
        if (!$this->exists) {
            unset($attributes[static::getPrimaryKey()]);
        }

        // Remove hidden attributes
        foreach (static::$hidden as $hidden) {
            unset($attributes[$hidden]);
        }

        return $attributes;
    }

    /**
     * Get attribute value
     */
    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set attribute value
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Check if attribute exists
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Convert to array
     */
    public function toArray()
    {
        $array = $this->attributes;

        // Remove hidden attributes
        foreach (static::$hidden as $hidden) {
            unset($array[$hidden]);
        }

        return $array;
    }

    /**
     * Convert to JSON
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Create new instance
     */
    public static function create($attributes)
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Get count
     */
    public static function count()
    {
        $sql = "SELECT COUNT(*) FROM " . static::getTable();
        $stmt = self::$db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * Check if model exists
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * Get the primary key value
     */
    public function getKey()
    {
        return $this->attributes[static::getPrimaryKey()] ?? null;
    }

    /**
     * Refresh model from database
     */
    public function refresh()
    {
        if (!$this->exists) {
            return $this;
        }

        $fresh = static::find($this->getKey());

        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->syncOriginal();
        }

        return $this;
    }
}
