<?php

require_once __DIR__ . '/AnthropicService.php';
require_once __DIR__ . '/../Models/Meeting.php';
require_once __DIR__ . '/../Models/Project.php';
require_once __DIR__ . '/../Models/Task.php';
require_once __DIR__ . '/../Models/UserPattern.php';

class MeetingProcessor {
    private $anthropicService;
    private $meetingModel;
    private $projectModel;
    private $taskModel;
    private $patternModel;
    
    public function __construct() {
        $this->anthropicService = new AnthropicService();
        $this->meetingModel = new Meeting();
        $this->projectModel = new Project();
        $this->taskModel = new Task();
        $this->patternModel = new UserPattern();
    }
    
    public function processInput($input, $type, $title = null, $filePath = null) {
        try {
            // Create meeting record
            $meetingData = [
                'title' => $title ?: 'Meeting ' . date('Y-m-d H:i'),
                'original_input' => $input,
                'input_type' => $type,
                'raw_file_path' => $filePath
            ];
            
            $meetingId = $this->meetingModel->createMeeting($meetingData);
            
            // Set processing status
            $this->meetingModel->setProcessingStatus($meetingId, Meeting::STATUS_PROCESSING);
            
            // Get user patterns for better analysis
            $patterns = $this->getUserPatterns();
            
            // Analyze with AI
            try {
                $analysis = $this->analyzeWithAI($input, $patterns);
            } catch (Exception $e) {
                // If AI analysis fails (timeout, etc.), create a basic fallback
                if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'timed out') !== false) {
                    $analysis = $this->createFallbackAnalysis($input, $title);
                } else {
                    throw $e; // Re-throw non-timeout errors
                }
            }
            
            // Validate and clean analysis
            $validatedAnalysis = $this->validateExtraction($analysis);
            
            // Store analysis results
            $this->meetingModel->updateAnalysis($meetingId, $validatedAnalysis);
            
            // Create projects and tasks from analysis
            $createdItems = $this->createProjectsFromAnalysis($validatedAnalysis, $meetingId);
            
            // Update user patterns based on analysis
            $this->updateUserPatterns($validatedAnalysis);
            
            // Log activity
            $this->logActivity('meeting_upload', 'meeting', $meetingId, [
                'projects_created' => count($createdItems['projects']),
                'tasks_created' => count($createdItems['tasks']),
                'input_type' => $type
            ]);
            
            return [
                'meeting_id' => $meetingId,
                'analysis' => $validatedAnalysis,
                'created_items' => $createdItems,
                'status' => 'success'
            ];
            
        } catch (Exception $e) {
            if (isset($meetingId)) {
                $this->meetingModel->setProcessingStatus($meetingId, Meeting::STATUS_ERROR);
            }
            
            throw new Exception("Processing failed: " . $e->getMessage());
        }
    }
    
    public function extractTextFromImage($imagePath) {
        // This would integrate with Tesseract OCR
        // For now, return a placeholder implementation
        
        if (!file_exists($imagePath)) {
            throw new Exception("Image file not found: $imagePath");
        }
        
        // TODO: Implement actual OCR using Tesseract
        // $command = escapeshellcmd("tesseract " . escapeshellarg($imagePath) . " stdout -l isl+eng");
        // $output = shell_exec($command);
        
        // Placeholder return
        return "OCR text extraction not yet implemented. Please use text input for now.";
    }
    
    public function analyzeWithAI($text, $userPatterns = []) {
        return $this->anthropicService->analyzeNotes($text, $userPatterns);
    }
    
    public function createProjectsFromAnalysis($analysis, $meetingId) {
        $createdItems = [
            'projects' => [],
            'tasks' => []
        ];
        
        if (!isset($analysis['projects'])) {
            return $createdItems;
        }
        
        foreach ($analysis['projects'] as $projectData) {
            try {
                // Create project
                $projectInfo = [
                    'meeting_id' => $meetingId,
                    'name' => $projectData['name'],
                    'description' => $projectData['description'],
                    'priority' => $projectData['priority'] ?? 'medium',
                    'estimated_hours' => $projectData['estimated_hours'] ?? 0,
                    'deadline' => $projectData['deadline'] ?? null,
                    'ai_confidence' => $projectData['confidence'] ?? 0.5,
                    'tags' => $projectData['tags'] ?? []
                ];
                
                $projectId = $this->projectModel->createProject($projectInfo);
                $createdItems['projects'][] = $projectId;
                
                // Log project creation
                $this->logActivity('project_created', 'project', $projectId, [
                    'ai_confidence' => $projectData['confidence'] ?? 0.5,
                    'from_meeting' => $meetingId
                ]);
                
            } catch (Exception $e) {
                error_log("Failed to create project: " . $e->getMessage());
            }
        }
        
        // Create tasks
        if (isset($analysis['tasks'])) {
            foreach ($analysis['tasks'] as $taskData) {
                try {
                    $projectIndex = $taskData['project_index'] ?? 0;
                    
                    if (!isset($createdItems['projects'][$projectIndex])) {
                        continue; // Skip if project wasn't created
                    }
                    
                    $projectId = $createdItems['projects'][$projectIndex];
                    
                    $taskInfo = [
                        'project_id' => $projectId,
                        'title' => $taskData['title'],
                        'description' => $taskData['description'] ?? '',
                        'priority' => $taskData['priority'] ?? 1,
                        'estimated_minutes' => $taskData['estimated_minutes'] ?? 0,
                        'deadline' => $taskData['deadline'] ?? null,
                        'dependencies' => $taskData['dependencies'] ?? []
                    ];
                    
                    $taskId = $this->taskModel->createTask($taskInfo);
                    $createdItems['tasks'][] = $taskId;
                    
                } catch (Exception $e) {
                    error_log("Failed to create task: " . $e->getMessage());
                }
            }
        }
        
        return $createdItems;
    }
    
    public function validateExtraction($extraction) {
        // Ensure required structure exists
        $validated = [
            'projects' => $extraction['projects'] ?? [],
            'tasks' => $extraction['tasks'] ?? [],
            'key_dates' => $extraction['key_dates'] ?? [],
            'risks' => $extraction['risks'] ?? [],
            'missing_info' => $extraction['missing_info'] ?? [],
            'user_patterns_detected' => $extraction['user_patterns_detected'] ?? []
        ];
        
        // Validate each project
        foreach ($validated['projects'] as &$project) {
            $project['name'] = $project['name'] ?? 'Unnamed Project';
            $project['description'] = $project['description'] ?? '';
            $project['priority'] = $this->validatePriority($project['priority'] ?? 'medium');
            $project['confidence'] = $this->validateConfidence($project['confidence'] ?? 0.5);
            $project['estimated_hours'] = max(0, floatval($project['estimated_hours'] ?? 0));
            $project['tags'] = $project['tags'] ?? [];
            
            // Validate deadline format
            if (isset($project['deadline']) && $project['deadline'] !== null) {
                if (!$this->isValidDate($project['deadline'])) {
                    $project['deadline'] = null;
                }
            }
        }
        
        // Validate each task
        foreach ($validated['tasks'] as &$task) {
            $task['title'] = $task['title'] ?? 'Unnamed Task';
            $task['description'] = $task['description'] ?? '';
            $task['priority'] = max(1, min(5, intval($task['priority'] ?? 1)));
            $task['estimated_minutes'] = max(0, intval($task['estimated_minutes'] ?? 0));
            $task['confidence'] = $this->validateConfidence($task['confidence'] ?? 0.5);
            $task['project_index'] = max(0, intval($task['project_index'] ?? 0));
            $task['dependencies'] = $task['dependencies'] ?? [];
            
            // Validate deadline format
            if (isset($task['deadline']) && $task['deadline'] !== null) {
                if (!$this->isValidDate($task['deadline'])) {
                    $task['deadline'] = null;
                }
            }
        }
        
        return $validated;
    }
    
    private function validatePriority($priority) {
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        return in_array($priority, $validPriorities) ? $priority : 'medium';
    }
    
    private function validateConfidence($confidence) {
        return max(0, min(1, floatval($confidence)));
    }
    
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    private function getUserPatterns() {
        $allPatterns = $this->patternModel->getAllPatterns();
        
        $patterns = [];
        foreach ($allPatterns as $type => $typePatterns) {
            if (!empty($typePatterns)) {
                $patterns[$type] = $typePatterns[0]['pattern_data']; // Get best pattern
            }
        }
        
        return $patterns;
    }
    
    private function updateUserPatterns($analysis) {
        if (!isset($analysis['user_patterns_detected'])) {
            return;
        }
        
        $detectedPatterns = $analysis['user_patterns_detected'];
        
        // Update priority keywords pattern
        if (isset($detectedPatterns['priority_keywords'])) {
            $this->patternModel->learnFromFeedback(
                UserPattern::TYPE_PRIORITY_KEYWORDS,
                [],
                $detectedPatterns['priority_keywords'],
                'accepted'
            );
        }
        
        // Update time indicators pattern
        if (isset($detectedPatterns['time_indicators'])) {
            $this->patternModel->learnFromFeedback(
                UserPattern::TYPE_TIME_ESTIMATION,
                [],
                $detectedPatterns['time_indicators'],
                'accepted'
            );
        }
        
        // Update naming style pattern
        if (isset($detectedPatterns['naming_style'])) {
            $this->patternModel->learnFromFeedback(
                UserPattern::TYPE_NAMING_CONVENTION,
                [],
                ['style' => $detectedPatterns['naming_style']],
                'accepted'
            );
        }
    }
    
    private function logActivity($actionType, $entityType, $entityId, $metadata = []) {
        try {
            require_once __DIR__ . '/../Models/BaseModel.php';
            $db = Database::getInstance();
            
            $sql = "INSERT INTO activity_log (action_type, entity_type, entity_id, metadata) VALUES (?, ?, ?, ?)";
            $db->query($sql, [
                $actionType,
                $entityType,
                $entityId,
                json_encode($metadata)
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
    
    public function reprocessMeeting($meetingId) {
        $meeting = $this->meetingModel->find($meetingId);
        if (!$meeting) {
            throw new Exception("Meeting not found");
        }
        
        // Delete existing projects and tasks from this meeting
        $existingProjects = $this->projectModel->findAll(['meeting_id' => $meetingId]);
        foreach ($existingProjects as $project) {
            $this->projectModel->delete($project['id']);
        }
        
        // Reprocess the original input
        return $this->processInput(
            $meeting['original_input'],
            $meeting['input_type'],
            $meeting['title'],
            $meeting['raw_file_path']
        );
    }
    
    private function createFallbackAnalysis($input, $title) {
        // Create a basic analysis when AI fails
        return [
            'projects' => [
                [
                    'name' => $title ?: 'Project from Meeting Notes',
                    'description' => 'Project created automatically due to AI processing timeout. Please review and edit as needed.',
                    'priority' => 'medium',
                    'estimated_hours' => 8,
                    'deadline' => null,
                    'responsible_party' => null,
                    'confidence' => 0.3,
                    'tags' => ['auto-generated', 'needs-review']
                ]
            ],
            'tasks' => [
                [
                    'project_index' => 0,
                    'title' => 'Review and organize meeting notes',
                    'description' => 'Review the original meeting notes and break down into specific tasks',
                    'priority' => 2,
                    'estimated_minutes' => 60,
                    'deadline' => null,
                    'dependencies' => [],
                    'confidence' => 0.5
                ]
            ],
            'key_dates' => [],
            'risks' => ['AI processing timeout - manual review required'],
            'missing_info' => ['Detailed project breakdown due to AI timeout'],
            'user_patterns_detected' => []
        ];
    }
}