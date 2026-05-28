<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;
use ReflectionClass;

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

    /** @var array<string, static> In-memory cache */
    protected static array $cache = [];

    /** @var bool Enable/disable caching */
    protected static bool $cacheEnabled = true;

    /** @var int Cache TTL in seconds */
    protected static int $cacheTTL = 300;

    /**
     * Get database connection, initializing if needed.
     */
    protected static function getConnection(): PDO
    {
        if (self::$db === null) {
            self::$db = Database::getInstance()->getConnection();
        }
        return self::$db;
    }

    public function __construct($attributes = [])
    {
        // Ensure DB connection is available
        self::getConnection();

        $this->fill($attributes);
        $this->syncOriginal();
    }

    protected static function getTable(): string
    {
        if (static::$table) {
            return static::$table;
        }

        $reflection = new ReflectionClass(static::class);
        return strtolower($reflection->getShortName()) . 's';
    }

    protected static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }

    /**
     * Get allowed columns for this model (fillable + primary key + timestamps).
     */
    protected static function getAllowedColumns(): array
    {
        $columns = static::$fillable;
        $columns[] = static::getPrimaryKey();

        if (static::$timestamps) {
            $columns[] = 'created_at';
            $columns[] = 'updated_at';
        }

        return array_unique($columns);
    }

    /**
     * Validate column name against SQL injection.
     * Only allows alphanumeric chars and underscores.
     */
    protected static function validateColumn(string $column): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new \InvalidArgumentException("Invalid column name: {$column}");
        }
        return $column;
    }

    /**
     * Validate operator for WHERE clause.
     */
    public static function validateOperator(string $operator): string
    {
        $allowed = ['=', '!=', '<', '>', '<=', '>=', '<>', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS', 'IS NOT'];
        $operator = strtoupper(trim($operator));

        if (!in_array($operator, $allowed)) {
            throw new \InvalidArgumentException("Invalid operator: {$operator}");
        }
        return $operator;
    }

    public function fill($attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (empty(static::$fillable) || in_array($key, static::$fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    private function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    public static function all(): array
    {
        $sql = "SELECT * FROM " . static::getTable();
        $stmt = self::getConnection()->prepare($sql);
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
     * Find a model by primary key (with optional caching).
     */
    public static function find($id): ?static
    {
        $cacheKey = static::getTable() . ':' . $id;

        // Check in-memory cache
        if (static::$cacheEnabled && isset(static::$cache[$cacheKey])) {
            return static::$cache[$cacheKey];
        }

        $sql = "SELECT * FROM " . static::getTable() . " WHERE " . static::getPrimaryKey() . " = ? LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $model = new static($row);
        $model->exists = true;
        $model->syncOriginal();

        // Store in cache
        if (static::$cacheEnabled) {
            static::$cache[$cacheKey] = $model;
        }

        return $model;
    }

    /**
     * Query with WHERE clause. Column and operator are validated against injection.
     */
    public static function where(string $column, $operator = '=', $value = null): array
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $operator = (string) $operator;
        $column = static::validateColumn($column);
        $operator = static::validateOperator($operator);

        $sql = "SELECT * FROM " . static::getTable() . " WHERE {$column} {$operator} ?";
        $stmt = self::getConnection()->prepare($sql);
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
     * Query with multiple WHERE conditions.
     */
    public static function whereMultiple(array $conditions): array
    {
        if (empty($conditions)) {
            return static::all();
        }

        $whereParts = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            $column = static::validateColumn($column);
            $whereParts[] = "{$column} = ?";
            $params[] = $value;
        }

        $sql = "SELECT * FROM " . static::getTable() . " WHERE " . implode(' AND ', $whereParts);
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $model = new static($row);
            $model->exists = true;
            $model->syncOriginal();
            $results[] = $model;
        }

        return $results;
    }

    public function save(): bool
    {
        if ($this->exists) {
            return $this->update();
        }

        return $this->insert();
    }

    private function insert(): bool
    {
        $attributes = $this->getAttributesForSave();

        if (static::$timestamps) {
            $attributes['created_at'] = date('Y-m-d H:i:s');
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        // Validate column names
        foreach (array_keys($attributes) as $column) {
            static::validateColumn($column);
        }

        $columns = array_keys($attributes);
        $placeholders = array_fill(0, count($attributes), '?');

        $sql = "INSERT INTO " . static::getTable() . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = self::getConnection()->prepare($sql);
        $result = $stmt->execute(array_values($attributes));

        if ($result) {
            $this->attributes[static::getPrimaryKey()] = self::getConnection()->lastInsertId();
            $this->exists = true;
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    private function update(): bool
    {
        $attributes = $this->getAttributesForSave();

        if (static::$timestamps) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $setParts = [];
        $values = [];

        foreach ($attributes as $column => $value) {
            $column = static::validateColumn($column);
            $setParts[] = "{$column} = ?";
            $values[] = $value;
        }

        $values[] = $this->attributes[static::getPrimaryKey()];

        $sql = "UPDATE " . static::getTable() . " SET " . implode(', ', $setParts) . " WHERE " . static::getPrimaryKey() . " = ?";

        $stmt = self::getConnection()->prepare($sql);
        $result = $stmt->execute($values);

        if ($result) {
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $sql = "DELETE FROM " . static::getTable() . " WHERE " . static::getPrimaryKey() . " = ?";
        $stmt = self::getConnection()->prepare($sql);

        return $stmt->execute([$this->attributes[static::getPrimaryKey()]]);
    }

    private function getAttributesForSave(): array
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

    public function __set($key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset($key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function toArray(): array
    {
        $array = $this->attributes;

        foreach (static::$hidden as $hidden) {
            unset($array[$hidden]);
        }

        return $array;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public static function count(): int
    {
        $sql = "SELECT COUNT(*) FROM " . static::getTable();
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function getKey()
    {
        return $this->attributes[static::getPrimaryKey()] ?? null;
    }

    public function refresh(): self
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

    /**
     * Check if attribute was changed since last sync.
     */
    public function isDirty(string $key): bool
    {
        return !array_key_exists($key, $this->original) || $this->attributes[$key] !== $this->original[$key];
    }

    /**
     * Get only changed attributes.
     */
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    // ═══════════════════════════════════════════
    //  CACHE MANAGEMENT
    // ═══════════════════════════════════════════

    /**
     * Clear in-memory cache for this model.
     */
    public static function clearCache(): void
    {
        static::$cache = [];
    }

    /**
     * Clear specific item from cache.
     */
    public static function forgetCache($id): void
    {
        $cacheKey = static::getTable() . ':' . $id;
        unset(static::$cache[$cacheKey]);
    }

    /**
     * Enable or disable caching.
     */
    public static function cacheable(bool $enabled = true): void
    {
        static::$cacheEnabled = $enabled;
    }

    /**
     * Check if caching is enabled.
     */
    public static function isCacheEnabled(): bool
    {
        return static::$cacheEnabled;
    }

    /**
     * Get cache statistics.
     */
    public static function getCacheStats(): array
    {
        return [
            'enabled' => static::$cacheEnabled,
            'size' => count(static::$cache),
            'keys' => array_keys(static::$cache),
        ];
    }

    /**
     * Paginate results.
     */
    public static function paginate(int $page = 1, int $perPage = 20, array $conditions = []): array
    {
        $offset = ($page - 1) * $perPage;

        // Build WHERE clause
        $where = '';
        $params = [];
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $column => $value) {
                $column = static::validateColumn($column);
                $whereParts[] = "{$column} = ?";
                $params[] = $value;
            }
            $where = ' WHERE ' . implode(' AND ', $whereParts);
        }

        // Get total count
        $countSql = "SELECT COUNT(*) FROM " . static::getTable() . $where;
        $countStmt = self::getConnection()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Get paginated results
        $sql = "SELECT * FROM " . static::getTable() . $where . " LIMIT {$perPage} OFFSET {$offset}";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $model = new static($row);
            $model->exists = true;
            $model->syncOriginal();
            $results[] = $model;
        }

        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }
}
