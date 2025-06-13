<?php

require_once __DIR__ . '/BaseModel.php';

class Project extends BaseModel {
    protected $table = 'projects';
    protected $fillable = [
        'meeting_id', 'name', 'description', 'priority', 'status',
        'start_date', 'deadline', 'estimated_hours', 'actual_hours',
        'completion_percentage', 'ai_confidence', 'color_code', 'tags'
    ];
    
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';
    
    const STATUS_PLANNING = 'planning';
    const STATUS_ACTIVE = 'active';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    public function createProject($data) {
        $this->validateRequired($data, ['name', 'description']);
        
        $projectData = [
            'meeting_id' => $data['meeting_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? self::PRIORITY_MEDIUM,
            'status' => $data['status'] ?? self::STATUS_PLANNING,
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'deadline' => $data['deadline'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? 0,
            'ai_confidence' => $data['ai_confidence'] ?? 0,
            'color_code' => $data['color_code'] ?? $this->getRandomColor(),
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : null
        ];
        
        return $this->create($projectData);
    }
    
    public function updateProgress($id, $percentage) {
        if ($percentage < 0 || $percentage > 100) {
            throw new InvalidArgumentException("Completion percentage must be between 0 and 100");
        }
        
        $status = $percentage == 100 ? self::STATUS_COMPLETED : self::STATUS_ACTIVE;
        
        return $this->update($id, [
            'completion_percentage' => $percentage,
            'status' => $status
        ]);
    }
    
    public function updateStatus($id, $status) {
        $validStatuses = [
            self::STATUS_PLANNING, self::STATUS_ACTIVE, 
            self::STATUS_ON_HOLD, self::STATUS_COMPLETED, self::STATUS_CANCELLED
        ];
        
        if (!in_array($status, $validStatuses)) {
            throw new InvalidArgumentException("Invalid status: $status");
        }
        
        return $this->update($id, ['status' => $status]);
    }
    
    public function addTime($id, $hours) {
        $project = $this->find($id);
        if (!$project) {
            throw new InvalidArgumentException("Project not found");
        }
        
        $newActualHours = $project['actual_hours'] + $hours;
        return $this->update($id, ['actual_hours' => $newActualHours]);
    }
    
    public function getWithTasks($id) {
        $sql = "
            SELECT p.*, 
                   COUNT(t.id) as task_count,
                   COUNT(CASE WHEN t.is_completed = 1 THEN 1 END) as completed_tasks,
                   COALESCE(SUM(t.estimated_minutes), 0) as total_estimated_minutes,
                   COALESCE(SUM(t.actual_minutes), 0) as total_actual_minutes
            FROM projects p
            LEFT JOIN tasks t ON t.project_id = p.id
            WHERE p.id = ?
            GROUP BY p.id
        ";
        
        $project = $this->selectOne($sql, [$id]);
        
        if ($project) {
            $project['tags'] = $project['tags'] ? json_decode($project['tags'], true) : [];
            $project['task_completion_rate'] = $project['task_count'] > 0 
                ? round(($project['completed_tasks'] / $project['task_count']) * 100, 1)
                : 0;
        }
        
        return $project;
    }
    
    public function getByStatus($status) {
        $projects = $this->findAll(['status' => $status], 'created_at DESC');
        
        foreach ($projects as &$project) {
            $project['tags'] = $project['tags'] ? json_decode($project['tags'], true) : [];
        }
        
        return $projects;
    }
    
    public function getByPriority($priority) {
        $projects = $this->findAll(['priority' => $priority], 'deadline ASC');
        
        foreach ($projects as &$project) {
            $project['tags'] = $project['tags'] ? json_decode($project['tags'], true) : [];
        }
        
        return $projects;
    }
    
    public function getOverdue() {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE deadline < CURDATE() 
            AND status NOT IN ('completed', 'cancelled')
            ORDER BY deadline ASC
        ";
        
        $projects = $this->select($sql);
        
        foreach ($projects as &$project) {
            $project['tags'] = $project['tags'] ? json_decode($project['tags'], true) : [];
        }
        
        return $projects;
    }
    
    public function getUpcoming($days = 7) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND status NOT IN ('completed', 'cancelled')
            ORDER BY deadline ASC
        ";
        
        $projects = $this->select($sql, [$days]);
        
        foreach ($projects as &$project) {
            $project['tags'] = $project['tags'] ? json_decode($project['tags'], true) : [];
        }
        
        return $projects;
    }
    
    public function getDashboardData() {
        $sql = "
            SELECT 
                COUNT(*) as total_projects,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_projects,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_projects,
                COUNT(CASE WHEN status = 'on_hold' THEN 1 END) as on_hold_projects,
                COUNT(CASE WHEN deadline < CURDATE() AND status NOT IN ('completed', 'cancelled') THEN 1 END) as overdue_projects,
                AVG(completion_percentage) as avg_completion,
                SUM(estimated_hours) as total_estimated_hours,
                SUM(actual_hours) as total_actual_hours
            FROM {$this->table}
        ";
        
        return $this->selectOne($sql);
    }
    
    public function search($query, $limit = 20) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE name LIKE ? OR description LIKE ?
            ORDER BY created_at DESC
            LIMIT ?
        ";
        
        $searchTerm = "%$query%";
        $projects = $this->select($sql, [$searchTerm, $searchTerm, $limit]);
        
        foreach ($projects as &$project) {
            $project['tags'] = $project['tags'] ? json_decode($project['tags'], true) : [];
        }
        
        return $projects;
    }
    
    private function getRandomColor() {
        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', 
            '#9b59b6', '#1abc9c', '#34495e', '#e67e22'
        ];
        
        return $colors[array_rand($colors)];
    }
    
    public function getProjectProgress($id) {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.completion_percentage,
                p.estimated_hours,
                p.actual_hours,
                COUNT(t.id) as total_tasks,
                COUNT(CASE WHEN t.is_completed = 1 THEN 1 END) as completed_tasks,
                COALESCE(SUM(CASE WHEN t.is_completed = 0 THEN t.estimated_minutes END), 0) as remaining_minutes
            FROM projects p
            LEFT JOIN tasks t ON t.project_id = p.id
            WHERE p.id = ?
            GROUP BY p.id
        ";
        
        return $this->selectOne($sql, [$id]);
    }
}