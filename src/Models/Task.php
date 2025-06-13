<?php

require_once __DIR__ . '/BaseModel.php';

class Task extends BaseModel {
    protected $table = 'tasks';
    protected $fillable = [
        'project_id', 'title', 'description', 'is_completed', 'priority',
        'deadline', 'estimated_minutes', 'actual_minutes', 'dependencies',
        'assigned_to', 'notes', 'completed_at'
    ];
    
    public function createTask($data) {
        $this->validateRequired($data, ['project_id', 'title']);
        
        $taskData = [
            'project_id' => $data['project_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'priority' => $data['priority'] ?? 1,
            'deadline' => $data['deadline'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? 0,
            'dependencies' => isset($data['dependencies']) ? json_encode($data['dependencies']) : null,
            'assigned_to' => $data['assigned_to'] ?? '',
            'notes' => $data['notes'] ?? ''
        ];
        
        return $this->create($taskData);
    }
    
    public function completeTask($id, $actualMinutes = null) {
        $updateData = [
            'is_completed' => true,
            'completed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($actualMinutes !== null) {
            $updateData['actual_minutes'] = $actualMinutes;
        }
        
        $result = $this->update($id, $updateData);
        
        // Update project progress
        $task = $this->find($id);
        if ($task) {
            $this->updateProjectProgress($task['project_id']);
        }
        
        return $result;
    }
    
    public function uncompleteTask($id) {
        $result = $this->update($id, [
            'is_completed' => false,
            'completed_at' => null
        ]);
        
        // Update project progress
        $task = $this->find($id);
        if ($task) {
            $this->updateProjectProgress($task['project_id']);
        }
        
        return $result;
    }
    
    public function addTime($id, $minutes) {
        $task = $this->find($id);
        if (!$task) {
            throw new InvalidArgumentException("Task not found");
        }
        
        $newActualMinutes = $task['actual_minutes'] + $minutes;
        return $this->update($id, ['actual_minutes' => $newActualMinutes]);
    }
    
    public function getByProject($projectId, $includeCompleted = true) {
        $conditions = ['project_id' => $projectId];
        
        if (!$includeCompleted) {
            $conditions['is_completed'] = false;
        }
        
        $tasks = $this->findAll($conditions, 'priority DESC, created_at ASC');
        
        foreach ($tasks as &$task) {
            $task['dependencies'] = $task['dependencies'] ? json_decode($task['dependencies'], true) : [];
        }
        
        return $tasks;
    }
    
    public function getByStatus($isCompleted = false) {
        $tasks = $this->findAll(['is_completed' => $isCompleted], 'priority DESC, deadline ASC');
        
        foreach ($tasks as &$task) {
            $task['dependencies'] = $task['dependencies'] ? json_decode($task['dependencies'], true) : [];
        }
        
        return $tasks;
    }
    
    public function getOverdue() {
        $sql = "
            SELECT t.*, p.name as project_name 
            FROM {$this->table} t
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE t.deadline < CURDATE() 
            AND t.is_completed = 0
            ORDER BY t.deadline ASC
        ";
        
        $tasks = $this->select($sql);
        
        foreach ($tasks as &$task) {
            $task['dependencies'] = $task['dependencies'] ? json_decode($task['dependencies'], true) : [];
        }
        
        return $tasks;
    }
    
    public function getUpcoming($days = 7) {
        $sql = "
            SELECT t.*, p.name as project_name 
            FROM {$this->table} t
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND t.is_completed = 0
            ORDER BY t.deadline ASC
        ";
        
        $tasks = $this->select($sql, [$days]);
        
        foreach ($tasks as &$task) {
            $task['dependencies'] = $task['dependencies'] ? json_decode($task['dependencies'], true) : [];
        }
        
        return $tasks;
    }
    
    public function getTasksByPriority($priority) {
        $sql = "
            SELECT t.*, p.name as project_name 
            FROM {$this->table} t
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE t.priority = ? AND t.is_completed = 0
            ORDER BY t.deadline ASC
        ";
        
        $tasks = $this->select($sql, [$priority]);
        
        foreach ($tasks as &$task) {
            $task['dependencies'] = $task['dependencies'] ? json_decode($task['dependencies'], true) : [];
        }
        
        return $tasks;
    }
    
    public function getAvailableTasks($projectId = null) {
        // Tasks that have no incomplete dependencies
        $sql = "
            SELECT t.*, p.name as project_name 
            FROM {$this->table} t
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE t.is_completed = 0
        ";
        
        $params = [];
        
        if ($projectId) {
            $sql .= " AND t.project_id = ?";
            $params[] = $projectId;
        }
        
        $sql .= " ORDER BY t.priority DESC, t.deadline ASC";
        
        $tasks = $this->select($sql, $params);
        
        foreach ($tasks as &$task) {
            $task['dependencies'] = $task['dependencies'] ? json_decode($task['dependencies'], true) : [];
            
            // Check if dependencies are met
            $task['can_start'] = $this->checkDependencies($task['id']);
        }
        
        return $tasks;
    }
    
    public function checkDependencies($taskId) {
        $task = $this->find($taskId);
        if (!$task || !$task['dependencies']) {
            return true;
        }
        
        $dependencies = json_decode($task['dependencies'], true);
        if (empty($dependencies)) {
            return true;
        }
        
        $sql = "
            SELECT COUNT(*) as incomplete_deps
            FROM {$this->table}
            WHERE id IN (" . str_repeat('?,', count($dependencies) - 1) . "?)
            AND is_completed = 0
        ";
        
        $result = $this->selectOne($sql, $dependencies);
        return $result['incomplete_deps'] == 0;
    }
    
    private function updateProjectProgress($projectId) {
        $sql = "
            SELECT 
                COUNT(*) as total_tasks,
                COUNT(CASE WHEN is_completed = 1 THEN 1 END) as completed_tasks
            FROM {$this->table}
            WHERE project_id = ?
        ";
        
        $result = $this->selectOne($sql, [$projectId]);
        
        if ($result['total_tasks'] > 0) {
            $percentage = round(($result['completed_tasks'] / $result['total_tasks']) * 100);
            
            require_once __DIR__ . '/Project.php';
            $projectModel = new Project();
            $projectModel->updateProgress($projectId, $percentage);
        }
    }
    
    public function search($query, $limit = 20) {
        $sql = "
            SELECT t.*, p.name as project_name
            FROM {$this->table} t
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE t.title LIKE ? OR t.description LIKE ?
            ORDER BY t.created_at DESC
            LIMIT ?
        ";
        
        $searchTerm = "%$query%";
        $tasks = $this->select($sql, [$searchTerm, $searchTerm, $limit]);
        
        foreach ($tasks as &$task) {
            $task['dependencies'] = $task['dependencies'] ? json_decode($task['dependencies'], true) : [];
        }
        
        return $tasks;
    }
    
    public function getTaskStatistics($projectId = null) {
        $sql = "
            SELECT 
                COUNT(*) as total_tasks,
                COUNT(CASE WHEN is_completed = 1 THEN 1 END) as completed_tasks,
                COUNT(CASE WHEN is_completed = 0 THEN 1 END) as pending_tasks,
                COUNT(CASE WHEN deadline < CURDATE() AND is_completed = 0 THEN 1 END) as overdue_tasks,
                AVG(CASE WHEN is_completed = 1 AND estimated_minutes > 0 THEN (actual_minutes / estimated_minutes) * 100 END) as avg_time_accuracy,
                SUM(estimated_minutes) as total_estimated_minutes,
                SUM(actual_minutes) as total_actual_minutes
            FROM {$this->table}
        ";
        
        $params = [];
        
        if ($projectId) {
            $sql .= " WHERE project_id = ?";
            $params[] = $projectId;
        }
        
        return $this->selectOne($sql, $params);
    }
}