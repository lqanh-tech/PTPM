<?php

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

    protected static function getTable()
    {
        return static::$table ?: strtolower(static::class) . 's';
    }

    protected static function getPrimaryKey()
    {
        return static::$primaryKey;
    }

    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (empty(static::$fillable) || in_array($key, static::$fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    private function syncOriginal()
    {
        $this->original = $this->attributes;
    }

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

    public function save()
    {
        if ($this->exists) {
            return $this->update();
        }

        return $this->insert();
    }

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

    public function delete()
    {
        if (!$this->exists) {
            return false;
        }

        $sql = "DELETE FROM " . static::getTable() . " WHERE " . static::getPrimaryKey() . " = ?";
        $stmt = self::$db->prepare($sql);

        return $stmt->execute([$this->attributes[static::getPrimaryKey()]]);
    }

    private function getAttributesForSave()
    {
        $attributes = $this->attributes;

        if (!$this->exists) {
            unset($attributes[static::getPrimaryKey()]);
        }

        foreach (static::$hidden as $hidden) {
            unset($attributes[$hidden]);
        }

        return $attributes;
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    public function toArray()
    {
        $array = $this->attributes;

        foreach (static::$hidden as $hidden) {
            unset($array[$hidden]);
        }

        return $array;
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public static function create($attributes)
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public static function count()
    {
        $sql = "SELECT COUNT(*) FROM " . static::getTable();
        $stmt = self::$db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function exists()
    {
        return $this->exists;
    }

    public function getKey()
    {
        return $this->attributes[static::getPrimaryKey()] ?? null;
    }

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
