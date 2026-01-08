<?php

class QueryBuilder
{
    private $pdo;
    private $table;
    private $select = ['*'];
    private $where = [];
    private $whereParams = [];
    private $orderBy = [];
    private $limit;
    private $offset;
    private $joins = [];
    private $groupBy = [];
    private $having = [];
    private $cache = null;
    private $cacheTTL = 300;
    
    public function __construct($pdo = null)
    {
        if ($pdo === null) {
            require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
            $this->pdo = Database::getInstance()->getConnection();
        } else {
            $this->pdo = $pdo;
        }
    }
    
    public static function table($table)
    {
        $builder = new self();
        $builder->table = $table;
        return $builder;
    }
    
    public function select(...$columns)
    {
        $this->select = $columns;
        return $this;
    }
    
    public function where($column, $operator = null, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = "`{$column}` {$operator} ?";
        $this->whereParams[] = $value;
        return $this;
    }
    
    public function whereIn($column, array $values)
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where[] = "`{$column}` IN ({$placeholders})";
        $this->whereParams = array_merge($this->whereParams, $values);
        return $this;
    }
    
    public function whereBetween($column, $min, $max)
    {
        $this->where[] = "`{$column}` BETWEEN ? AND ?";
        $this->whereParams[] = $min;
        $this->whereParams[] = $max;
        return $this;
    }
    
    public function whereNull($column)
    {
        $this->where[] = "`{$column}` IS NULL";
        return $this;
    }
    
    public function whereNotNull($column)
    {
        $this->where[] = "`{$column}` IS NOT NULL";
        return $this;
    }
    
    public function whereLike($column, $value)
    {
        $this->where[] = "`{$column}` LIKE ?";
        $this->whereParams[] = $value;
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy[] = "`{$column}` {$direction}";
        return $this;
    }
    
    public function limit($limit)
    {
        $this->limit = (int)$limit;
        return $this;
    }
    
    public function offset($offset)
    {
        $this->offset = (int)$offset;
        return $this;
    }
    
    public function join($table, $first, $operator, $second)
    {
        $this->joins[] = "INNER JOIN `{$table}` ON `{$first}` {$operator} `{$second}`";
        return $this;
    }
    
    public function leftJoin($table, $first, $operator, $second)
    {
        $this->joins[] = "LEFT JOIN `{$table}` ON `{$first}` {$operator} `{$second}`";
        return $this;
    }
    
    public function groupBy(...$columns)
    {
        $this->groupBy = $columns;
        return $this;
    }
    
    public function having($column, $operator, $value)
    {
        $this->having[] = "`{$column}` {$operator} ?";
        $this->whereParams[] = $value;
        return $this;
    }
    
    public function cache($ttl = 300)
    {
        $this->cache = true;
        $this->cacheTTL = $ttl;
        return $this;
    }
    
    public function noCache()
    {
        $this->cache = false;
        return $this;
    }
    
    public function get()
    {
        $sql = $this->buildSelectQuery();
        
        if ($this->cache) {
            require_once __DIR__ . '/advanced_cache.php';
            $cacheKey = 'query_' . md5($sql . serialize($this->whereParams));
            return cache()->remember($cacheKey, $this->cacheTTL, function() use ($sql) {
                return $this->executeQuery($sql);
            });
        }
        
        return $this->executeQuery($sql);
    }
    
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }
    
    public function count()
    {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];
        $result = $this->first();
        $this->select = $originalSelect;
        return $result ? (int)$result->count : 0;
    }
    
    public function sum($column)
    {
        $originalSelect = $this->select;
        $this->select = ["SUM(`{$column}`) as total"];
        $result = $this->first();
        $this->select = $originalSelect;
        return $result ? (float)$result->total : 0;
    }
    
    public function avg($column)
    {
        $originalSelect = $this->select;
        $this->select = ["AVG(`{$column}`) as average"];
        $result = $this->first();
        $this->select = $originalSelect;
        return $result ? (float)$result->average : 0;
    }
    
    public function pluck($column)
    {
        $originalSelect = $this->select;
        $this->select = [$column];
        $results = $this->get();
        $this->select = $originalSelect;
        return array_map(function($row) use ($column) {
            return $row->$column;
        }, $results);
    }
    
    public function insert(array $data)
    {
        $columns = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $columnList = '`' . implode('`,`', $columns) . '`';
        
        $sql = "INSERT INTO `{$this->table}` ({$columnList}) VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        $this->invalidateCache();
        
        return $this->pdo->lastInsertId();
    }
    
    public function update(array $data)
    {
        $sets = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $sets[] = "`{$column}` = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets);
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
            $params = array_merge($params, $this->whereParams);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $this->invalidateCache();
        
        return $stmt->rowCount();
    }
    
    public function delete()
    {
        $sql = "DELETE FROM `{$this->table}`";
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->whereParams);
        
        $this->invalidateCache();
        
        return $stmt->rowCount();
    }
    
    private function buildSelectQuery()
    {
        $columns = implode(', ', array_map(function($col) {
            return $col === '*' ? '*' : "`{$col}`";
        }, $this->select));
        
        $sql = "SELECT {$columns} FROM `{$this->table}`";
        
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }
        
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY `' . implode('`, `', $this->groupBy) . '`';
        }
        
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        
        return $sql;
    }
    
    private function executeQuery($sql)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->whereParams);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    private function invalidateCache()
    {
        if ($this->cache) {
            require_once __DIR__ . '/advanced_cache.php';
            cache()->invalidateTag($this->table);
        }
    }
    
    public function toSql()
    {
        return $this->buildSelectQuery();
    }
}

function DB($table) {
    return QueryBuilder::table($table);
}
