<?php

require_once __DIR__ . '/../../config/database.php';

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->selectOne($sql, [$id]);
    }
    
    public function findAll($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $placeholders = str_repeat('?,', count($value) - 1) . '?';
                    $whereClause[] = "$field IN ($placeholders)";
                    $params = array_merge($params, $value);
                } else {
                    $whereClause[] = "$field = ?";
                    $params[] = $value;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        return $this->db->select($sql, $params);
    }
    
    public function create($data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ($placeholders)";
        
        return $this->db->insert($sql, array_values($data));
    }
    
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $sql = "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = ?";
        $params = array_merge(array_values($data), [$id]);
        
        return $this->db->update($sql, $params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->delete($sql, [$id]);
    }
    
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "$field = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $result = $this->db->selectOne($sql, $params);
        return (int) $result['count'];
    }
    
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        // Allow timestamp fields even if not in fillable
        $allowedFields = array_merge($this->fillable, ['created_at', 'updated_at']);
        $filtered = array_intersect_key($data, array_flip($allowedFields));
        
        // Convert empty strings to null for date fields
        foreach ($filtered as $key => $value) {
            if (($key === 'deadline' || strpos($key, '_date') !== false || strpos($key, '_at') !== false) && $value === '') {
                $filtered[$key] = null;
            }
        }
        
        return $filtered;
    }
    
    protected function validateRequired($data, $required = []) {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new InvalidArgumentException("Missing required fields: " . implode(', ', $missing));
        }
    }
    
    public function query($sql, $params = []) {
        return $this->db->query($sql, $params);
    }
    
    public function select($sql, $params = []) {
        return $this->db->select($sql, $params);
    }
    
    public function selectOne($sql, $params = []) {
        return $this->db->selectOne($sql, $params);
    }
    
    protected function hasColumn($columnName) {
        static $columnCache = [];
        
        $cacheKey = $this->table . '.' . $columnName;
        
        if (!isset($columnCache[$cacheKey])) {
            $columns = $this->db->select("DESCRIBE {$this->table}");
            $columnCache[$cacheKey] = false;
            
            foreach ($columns as $column) {
                if ($column['Field'] === $columnName) {
                    $columnCache[$cacheKey] = true;
                    break;
                }
            }
        }
        
        return $columnCache[$cacheKey];
    }
}