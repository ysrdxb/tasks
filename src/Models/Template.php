<?php

require_once __DIR__ . '/BaseModel.php';

class Template extends BaseModel {
    protected $table = 'templates';
    protected $fillable = [
        'name', 'description', 'template_data', 'usage_count', 'effectiveness_score'
    ];
    
    public function createTemplate($name, $description, $templateData) {
        $this->validateRequired(['name' => $name], ['name']);
        
        $data = [
            'name' => $name,
            'description' => $description,
            'template_data' => json_encode($templateData),
            'usage_count' => 0,
            'effectiveness_score' => 0.5
        ];
        
        return $this->create($data);
    }
    
    public function useTemplate($id, $wasEffective = true) {
        $template = $this->find($id);
        if (!$template) {
            throw new InvalidArgumentException("Template not found");
        }
        
        $newUsageCount = $template['usage_count'] + 1;
        $newEffectivenessScore = $wasEffective
            ? (($template['effectiveness_score'] * $template['usage_count']) + 1) / $newUsageCount
            : (($template['effectiveness_score'] * $template['usage_count'])) / $newUsageCount;
        
        return $this->update($id, [
            'usage_count' => $newUsageCount,
            'effectiveness_score' => $newEffectivenessScore
        ]);
    }
    
    public function getTemplate($id) {
        $template = $this->find($id);
        if ($template) {
            $template['template_data'] = json_decode($template['template_data'], true);
        }
        return $template;
    }
    
    public function getAllTemplates() {
        $templates = $this->findAll([], 'effectiveness_score DESC, usage_count DESC');
        
        foreach ($templates as &$template) {
            $template['template_data'] = json_decode($template['template_data'], true);
        }
        
        return $templates;
    }
    
    public function getPopularTemplates($limit = 5) {
        $templates = $this->findAll([], 'usage_count DESC', $limit);
        
        foreach ($templates as &$template) {
            $template['template_data'] = json_decode($template['template_data'], true);
        }
        
        return $templates;
    }
    
    public function getEffectiveTemplates($minScore = 0.7, $limit = 10) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE effectiveness_score >= ?
            ORDER BY effectiveness_score DESC, usage_count DESC
            LIMIT ?
        ";
        
        $templates = $this->select($sql, [$minScore, $limit]);
        
        foreach ($templates as &$template) {
            $template['template_data'] = json_decode($template['template_data'], true);
        }
        
        return $templates;
    }
    
    public function searchTemplates($query) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE name LIKE ? OR description LIKE ?
            ORDER BY effectiveness_score DESC, usage_count DESC
        ";
        
        $searchTerm = "%$query%";
        $templates = $this->select($sql, [$searchTerm, $searchTerm]);
        
        foreach ($templates as &$template) {
            $template['template_data'] = json_decode($template['template_data'], true);
        }
        
        return $templates;
    }
    
    public function generateFromProject($projectId) {
        require_once __DIR__ . '/Project.php';
        require_once __DIR__ . '/Task.php';
        
        $projectModel = new Project();
        $taskModel = new Task();
        
        $project = $projectModel->getWithTasks($projectId);
        if (!$project) {
            throw new InvalidArgumentException("Project not found");
        }
        
        $tasks = $taskModel->getByProject($projectId);
        
        $templateData = [
            'project_type' => $this->inferProjectType($project),
            'phases' => $this->extractPhases($tasks),
            'common_tasks' => $this->extractCommonTasks($tasks),
            'estimated_duration' => $project['total_estimated_minutes'],
            'priority_distribution' => $this->analyzePriorityDistribution($tasks),
            'tags' => $project['tags']
        ];
        
        $templateName = "Template from: " . $project['name'];
        $description = "Auto-generated template based on project: " . $project['name'];
        
        return $this->createTemplate($templateName, $description, $templateData);
    }
    
    private function inferProjectType($project) {
        $tags = $project['tags'] ?? [];
        
        if (in_array('web', $tags) || in_array('website', $tags)) {
            return 'Web Development';
        } elseif (in_array('mobile', $tags) || in_array('app', $tags)) {
            return 'Mobile Development';
        } elseif (in_array('design', $tags) || in_array('ui', $tags)) {
            return 'Design Project';
        } else {
            return 'General Project';
        }
    }
    
    private function extractPhases($tasks) {
        $phases = [];
        $phaseKeywords = [
            'Planning' => ['plan', 'design', 'research', 'analysis'],
            'Development' => ['implement', 'build', 'create', 'develop', 'code'],
            'Testing' => ['test', 'debug', 'validate', 'verify'],
            'Deployment' => ['deploy', 'launch', 'publish', 'release']
        ];
        
        foreach ($tasks as $task) {
            $taskTitle = strtolower($task['title']);
            foreach ($phaseKeywords as $phase => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($taskTitle, $keyword) !== false) {
                        $phases[$phase][] = $task['title'];
                        break 2;
                    }
                }
            }
        }
        
        return $phases;
    }
    
    private function extractCommonTasks($tasks) {
        return array_map(function($task) {
            return [
                'title' => $task['title'],
                'estimated_minutes' => $task['estimated_minutes'],
                'priority' => $task['priority']
            ];
        }, array_slice($tasks, 0, 10)); // Limit to first 10 tasks
    }
    
    private function analyzePriorityDistribution($tasks) {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        
        foreach ($tasks as $task) {
            $priority = $task['priority'] ?? 1;
            if (isset($distribution[$priority])) {
                $distribution[$priority]++;
            }
        }
        
        $total = count($tasks);
        if ($total > 0) {
            foreach ($distribution as $priority => $count) {
                $distribution[$priority] = round(($count / $total) * 100, 1);
            }
        }
        
        return $distribution;
    }
    
    public function applyTemplate($templateId, $projectName, $projectDescription) {
        $template = $this->getTemplate($templateId);
        if (!$template) {
            throw new InvalidArgumentException("Template not found");
        }
        
        require_once __DIR__ . '/Project.php';
        require_once __DIR__ . '/Task.php';
        
        $projectModel = new Project();
        $taskModel = new Task();
        
        // Create project
        $projectData = [
            'name' => $projectName,
            'description' => $projectDescription,
            'estimated_hours' => isset($template['template_data']['estimated_duration']) 
                ? round($template['template_data']['estimated_duration'] / 60, 2) 
                : 0,
            'tags' => $template['template_data']['tags'] ?? []
        ];
        
        $projectId = $projectModel->createProject($projectData);
        
        // Create tasks from template
        if (isset($template['template_data']['common_tasks'])) {
            foreach ($template['template_data']['common_tasks'] as $taskTemplate) {
                $taskData = [
                    'project_id' => $projectId,
                    'title' => $taskTemplate['title'],
                    'estimated_minutes' => $taskTemplate['estimated_minutes'] ?? 0,
                    'priority' => $taskTemplate['priority'] ?? 1
                ];
                
                $taskModel->createTask($taskData);
            }
        }
        
        // Track template usage
        $this->useTemplate($templateId, true);
        
        return $projectId;
    }
    
    public function getTemplateStatistics() {
        $sql = "
            SELECT 
                COUNT(*) as total_templates,
                AVG(effectiveness_score) as avg_effectiveness,
                SUM(usage_count) as total_usage,
                MAX(usage_count) as max_usage,
                COUNT(CASE WHEN effectiveness_score >= 0.8 THEN 1 END) as highly_effective_count
            FROM {$this->table}
        ";
        
        return $this->selectOne($sql);
    }
}