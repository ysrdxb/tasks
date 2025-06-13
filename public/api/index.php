<?php
/**
 * Simple API Handler
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

App::init();

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'testAnthropic':
            require_once __DIR__ . '/../../src/Services/AnthropicService.php';
            
            try {
                $anthropicService = new AnthropicService();
                $result = $anthropicService->testConnection();
                
                echo json_encode([
                    'success' => true,
                    'test_result' => $result
                ]);
            } catch (Exception $e) {
                throw new Exception('Anthropic test failed: ' . $e->getMessage());
            }
            break;
            
        case 'analyzeNotes':
            // Increase timeout for AI analysis
            @set_time_limit(300); // 5 minutes - use @ to suppress warnings
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Ógild JSON gögn: ' . json_last_error_msg());
            }
            
            $title = $input['title'] ?? '';
            $notes = $input['notes'] ?? '';
            
            if (!$notes) {
                throw new Exception('Fundargerðir eru nauðsynlegar');
            }
            
            if (strlen($notes) > 50000) {
                throw new Exception('Fundargerðir eru of langar (hámark 50,000 stafir)');
            }
            
            require_once __DIR__ . '/../../src/Services/AnthropicService.php';
            
            try {
                $anthropicService = new AnthropicService();
                $analysis = $anthropicService->analyzeNotes($notes);
                
                // Add confidence scores and reasoning to suggestions
                if (isset($analysis['projects'])) {
                    foreach ($analysis['projects'] as &$project) {
                        if (!isset($project['confidence'])) {
                            $project['confidence'] = 0.7; // Default confidence
                        }
                        if (!isset($project['ai_reasoning'])) {
                            $project['ai_reasoning'] = 'Verkefni greint út frá lýsingu og samhengi í fundargerðum';
                        }
                    }
                }
                
                if (isset($analysis['tasks'])) {
                    foreach ($analysis['tasks'] as &$task) {
                        if (!isset($task['confidence'])) {
                            $task['confidence'] = 0.6; // Default confidence  
                        }
                        if (!isset($task['ai_reasoning'])) {
                            $task['ai_reasoning'] = 'Verkþáttur greindur út frá aðgerðum og verkhlutum í texta';
                        }
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'analysis' => $analysis
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            break;
            
        case 'aiChat':
            // Increase timeout for AI chat
            set_time_limit(300); // 5 minutes
            
            $input = json_decode(file_get_contents('php://input'), true);
            $prompt = $input['prompt'] ?? '';
            $context = $input['context'] ?? [];
            
            if (!$prompt) {
                throw new Exception('Fyrirspurn er nauðsynleg');
            }
            
            require_once __DIR__ . '/../../src/Services/AnthropicService.php';
            
            $anthropicService = new AnthropicService();
            
            // Build context-aware prompt in Icelandic
            $contextualPrompt = "Þú ert AI verkefnastjórnunarráðgjafi. Út frá eftirfarandi verkefnasamhengi, gefðu hjálplegar innsýn og tillögur á íslensku.\n\n";
            
            if (!empty($context['project'])) {
                $project = $context['project'];
                $contextualPrompt .= "Verkefni: {$project['name']}\n";
                $contextualPrompt .= "Lýsing: {$project['description']}\n";
                $contextualPrompt .= "Staða: {$project['status']}\n";
                $contextualPrompt .= "Forgangur: {$project['priority']}\n";
                
                if (!empty($context['tasks'])) {
                    $contextualPrompt .= "\nVerkþættir:\n";
                    foreach ($context['tasks'] as $task) {
                        $status = $task['is_completed'] ? '[LOKIÐ]' : '[Í VINNSLU]';
                        $contextualPrompt .= "- {$status} {$task['title']}: {$task['description']}\n";
                    }
                }
            }
            
            if (!empty($context['selectedText'])) {
                $contextualPrompt .= "\nValinn texti: \"{$context['selectedText']}\"\n";
            }
            
            $contextualPrompt .= "\nSpurning notanda: {$prompt}\n\nGefðu hjálplegt og framkvæmanlegt svar á íslensku:";
            
            try {
                $response = $anthropicService->chat($contextualPrompt);
                
                echo json_encode([
                    'success' => true,
                    'response' => $response
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            break;
            
        case 'updateProject':
            $input = json_decode(file_get_contents('php://input'), true);
            $projectId = $input['projectId'] ?? 0;
            $changes = $input['changes'] ?? [];
            
            if (!$projectId || empty($changes)) {
                throw new Exception('Verkefnisauðkenni og breytingar eru nauðsynlegar');
            }
            
            require_once __DIR__ . '/../../src/Models/Project.php';
            $projectModel = new Project();
            
            // Map frontend field names to database fields
            $fieldMap = [
                'project-name' => 'name',
                'project-description' => 'description',
                'status' => 'status',
                'priority' => 'priority',
                'deadline' => 'deadline',
                'estimated_hours' => 'estimated_hours'
            ];
            
            $updateData = [];
            foreach ($changes as $fieldName => $value) {
                if (isset($fieldMap[$fieldName])) {
                    $updateData[$fieldMap[$fieldName]] = $value;
                } elseif (array_key_exists($fieldName, $fieldMap)) {
                    $updateData[$fieldName] = $value;
                }
            }
            
            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $result = $projectModel->update($projectId, $updateData);
                
                if ($result) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception('Tókst ekki að uppfæra verkefni');
                }
            } else {
                echo json_encode(['success' => true, 'message' => 'Engar gildar breytingar til að vista']);
            }
            break;
            
        case 'toggleTask':
            $input = json_decode(file_get_contents('php://input'), true);
            $taskId = $input['taskId'] ?? 0;
            $completed = $input['completed'] ?? false;
            
            if (!$taskId) {
                throw new Exception('Verkþáttarauðkenni er nauðsynlegt');
            }
            
            require_once __DIR__ . '/../../src/Models/Task.php';
            $taskModel = new Task();
            
            $result = $taskModel->update($taskId, [
                'is_completed' => $completed ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Tókst ekki að uppfæra verkþátt');
            }
            break;
            
        case 'createTask':
            $input = json_decode(file_get_contents('php://input'), true);
            $projectId = $input['projectId'] ?? 0;
            $title = $input['title'] ?? '';
            $description = $input['description'] ?? '';
            $priority = $input['priority'] ?? 3;
            
            if (!$projectId || !$title) {
                throw new Exception('Verkefnisauðkenni og titill eru nauðsynleg');
            }
            
            require_once __DIR__ . '/../../src/Models/Task.php';
            $taskModel = new Task();
            
            $taskData = [
                'project_id' => $projectId,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'is_completed' => 0,
                'estimated_minutes' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $taskId = $taskModel->create($taskData);
            
            if ($taskId) {
                echo json_encode([
                    'success' => true,
                    'taskId' => $taskId
                ]);
            } else {
                throw new Exception('Tókst ekki að búa til verkþátt');
            }
            break;
            
        case 'duplicateProject':
            $input = json_decode(file_get_contents('php://input'), true);
            $projectId = $input['projectId'] ?? 0;
            
            if (!$projectId) {
                throw new Exception('Project ID is required');
            }
            
            require_once __DIR__ . '/../../src/Models/Project.php';
            require_once __DIR__ . '/../../src/Models/Task.php';
            
            $projectModel = new Project();
            $taskModel = new Task();
            
            // Get original project
            $originalProject = $projectModel->find($projectId);
            if (!$originalProject) {
                throw new Exception('Project not found');
            }
            
            // Create new project with copied data
            $newProjectData = [
                'name' => $originalProject['name'] . ' (Afrit)',
                'description' => $originalProject['description'],
                'status' => 'planning', // Start as planning
                'priority' => $originalProject['priority'],
                'deadline' => $originalProject['deadline'],
                'estimated_hours' => $originalProject['estimated_hours'],
                'tags' => $originalProject['tags'],
                'ai_confidence' => $originalProject['ai_confidence'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $newProjectId = $projectModel->create($newProjectData);
            
            if ($newProjectId) {
                // Copy tasks
                $originalTasks = $taskModel->getByProject($projectId);
                foreach ($originalTasks as $task) {
                    $newTaskData = [
                        'project_id' => $newProjectId,
                        'title' => $task['title'],
                        'description' => $task['description'],
                        'priority' => $task['priority'],
                        'estimated_minutes' => $task['estimated_minutes'],
                        'is_completed' => 0, // Reset completion status
                        'deadline' => $task['deadline'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $taskModel->create($newTaskData);
                }
                
                echo json_encode([
                    'success' => true,
                    'newProjectId' => $newProjectId
                ]);
            } else {
                throw new Exception('Failed to duplicate project');
            }
            break;
            
        case 'deleteProject':
            $input = json_decode(file_get_contents('php://input'), true);
            $projectId = $input['projectId'] ?? 0;
            
            if (!$projectId) {
                throw new Exception('Project ID is required');
            }
            
            require_once __DIR__ . '/../../src/Models/Project.php';
            require_once __DIR__ . '/../../src/Models/Task.php';
            
            $projectModel = new Project();
            $taskModel = new Task();
            
            // Delete all tasks first
            $tasks = $taskModel->getByProject($projectId);
            foreach ($tasks as $task) {
                $taskModel->delete($task['id']);
            }
            
            // Delete the project
            $result = $projectModel->delete($projectId);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to delete project');
            }
            break;
            
        case 'createFullProject':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON data');
            }
            
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $priority = $input['priority'] ?? 'medium';
            $deadline = !empty($input['deadline']) ? $input['deadline'] : null;
            $estimatedHours = $input['estimated_hours'] ?? 0;
            $tags = $input['tags'] ?? [];
            $tasks = $input['tasks'] ?? [];
            
            if (!$name) {
                throw new Exception('Verkefnisnafn er nauðsynlegt');
            }
            
            if (count($tasks) === 0) {
                throw new Exception('Verkefni þarf að hafa að minnsta kosti einn verkþátt');
            }
            
            require_once __DIR__ . '/../../src/Models/Project.php';
            require_once __DIR__ . '/../../src/Models/Task.php';
            
            $projectModel = new Project();
            $taskModel = new Task();
            
            // Create the project
            $projectData = [
                'name' => $name,
                'description' => $description,
                'status' => 'planning',
                'priority' => $priority,
                'deadline' => $deadline,
                'estimated_hours' => $estimatedHours,
                'tags' => json_encode($tags),
                'ai_confidence' => 0.9, // High confidence for manually created projects
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $projectId = $projectModel->create($projectData);
            
            if (!$projectId) {
                throw new Exception('Tókst ekki að búa til verkefni');
            }
            
            // Create the tasks
            $createdTasks = 0;
            foreach ($tasks as $task) {
                $taskData = [
                    'project_id' => $projectId,
                    'title' => $task['name'] ?? '',
                    'description' => $task['description'] ?? '',
                    'priority' => $task['priority'] ?? 3,
                    'estimated_minutes' => ($task['hours'] ?? 0) * 60,
                    'is_completed' => 0,
                    'deadline' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if ($taskModel->create($taskData)) {
                    $createdTasks++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'project_id' => $projectId,
                'tasks_created' => $createdTasks,
                'message' => "Verkefni \"{$name}\" búið til með {$createdTasks} verkþætti"
            ]);
            break;
            
        case 'deleteMeeting':
            $input = json_decode(file_get_contents('php://input'), true);
            $meetingId = $input['meetingId'] ?? 0;
            
            if (!$meetingId) {
                throw new Exception('Meeting ID is required');
            }
            
            require_once __DIR__ . '/../../src/Models/Meeting.php';
            require_once __DIR__ . '/../../src/Models/Project.php';
            require_once __DIR__ . '/../../src/Models/Task.php';
            
            $meetingModel = new Meeting();
            $projectModel = new Project();
            $taskModel = new Task();
            
            // Check if meeting exists
            $meeting = $meetingModel->find($meetingId);
            if (!$meeting) {
                throw new Exception('Meeting not found');
            }
            
            // Delete related tasks first
            $projects = $projectModel->findAll(['meeting_id' => $meetingId]);
            foreach ($projects as $project) {
                $tasks = $taskModel->getByProject($project['id']);
                foreach ($tasks as $task) {
                    $taskModel->delete($task['id']);
                }
            }
            
            // Delete related projects
            foreach ($projects as $project) {
                $projectModel->delete($project['id']);
            }
            
            // Delete the meeting
            $result = $meetingModel->delete($meetingId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Fundur og tengd verkefni eytt'
                ]);
            } else {
                throw new Exception('Failed to delete meeting');
            }
            break;
            
        case 'getMeeting':
            $meetingId = $_GET['id'] ?? 0;
            if (!$meetingId) {
                throw new Exception('Meeting ID required');
            }
            
            require_once __DIR__ . '/../../src/Models/Meeting.php';
            require_once __DIR__ . '/../../src/Models/Project.php';
            require_once __DIR__ . '/../../src/Models/Task.php';
            
            $meetingModel = new Meeting();
            $projectModel = new Project();
            $taskModel = new Task();
            
            $meeting = $meetingModel->find($meetingId);
            if (!$meeting) {
                throw new Exception('Meeting not found');
            }
            
            // Get AI analysis - DEBUG VERSION
            $analysis = null;
            
            // Add to frontend response for debugging
            $debugInfo = [
                'ai_analysis_raw' => $meeting['ai_analysis'] ?? 'NULL',
                'ai_analysis_empty' => empty($meeting['ai_analysis']),
                'ai_analysis_length' => isset($meeting['ai_analysis']) ? strlen($meeting['ai_analysis']) : 0
            ];
            
            if (!empty($meeting['ai_analysis'])) {
                $analysis = json_decode($meeting['ai_analysis'], true);
                $debugInfo['json_decode_error'] = json_last_error();
                $debugInfo['json_decode_msg'] = json_last_error_msg();
            }
            
            // Get related projects and tasks
            $projects = $projectModel->findAll(['meeting_id' => $meetingId]);
            $projectCount = count($projects);
            $taskCount = 0;
            
            foreach ($projects as $project) {
                $tasks = $taskModel->getByProject($project['id']);
                $taskCount += count($tasks);
            }
            
            echo json_encode([
                'success' => true,
                'meeting' => $meeting,
                'analysis' => $analysis,
                'project_count' => $projectCount,
                'task_count' => $taskCount,
                'projects' => $projects,
                'debug' => $debugInfo
            ]);
            break;
            
        case 'exportMeeting':
            $meetingId = $_GET['id'] ?? 0;
            $format = $_GET['format'] ?? 'md'; // 'md' or 'pdf'
            
            if (!$meetingId) {
                throw new Exception('Meeting ID required');
            }
            
            require_once __DIR__ . '/../../src/Models/Meeting.php';
            $meetingModel = new Meeting();
            
            $meeting = $meetingModel->find($meetingId);
            if (!$meeting) {
                throw new Exception('Meeting not found');
            }
            
            // Get AI analysis
            $analysis = null;
            if (!empty($meeting['ai_analysis'])) {
                $analysis = json_decode($meeting['ai_analysis'], true);
            }
            
            if ($format === 'pdf') {
                // PDF Export - Return HTML that browser can print to PDF
                $html = generateMeetingHTML($meeting, $analysis);
                
                // Set content type as HTML for browser PDF printing
                header('Content-Type: text/html; charset=utf-8');
                echo $html;
                
                // Add JavaScript to automatically trigger print dialog
                echo '<script>window.onload = function() { window.print(); }</script>';
                exit;
            } else {
                // Markdown Export (existing functionality)
                $exportContent = "# " . $meeting['title'] . "\n\n";
                $exportContent .= "**Dagsetning:** " . date('j. F Y', strtotime($meeting['date'])) . "\n";
                $exportContent .= "**Tegund:** " . ucfirst($meeting['input_type']) . "\n";
                $exportContent .= "**Staða:** " . ucfirst($meeting['processing_status']) . "\n\n";
                
                if ($analysis && isset($analysis['summary'])) {
                    $exportContent .= $analysis['summary'] . "\n\n";
                }
                
                $exportContent .= "## Upprunalegt inntak\n\n";
                $exportContent .= $meeting['original_input'] . "\n\n";
                
                if ($analysis && isset($analysis['conversation']) && is_array($analysis['conversation'])) {
                    $exportContent .= "## Samtal við Claude\n\n";
                    foreach ($analysis['conversation'] as $msg) {
                        $role = $msg['type'] === 'user' ? 'Þú' : 'Claude';
                        $exportContent .= "**{$role}:** " . $msg['message'] . "\n\n";
                    }
                }
                
                $exportContent .= "---\n";
                $exportContent .= "*Flutt út frá AI Verkefnastjóra á " . date('j. F Y, H:i') . "*\n";
                
                header('Content-Type: text/markdown; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . 
                       preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $meeting['title']) . 
                       '_' . date('Y-m-d') . '.md"');
                header('Content-Length: ' . strlen($exportContent));
                
                echo $exportContent;
                exit;
            }
            
        case 'project':
            $projectId = $_GET['id'] ?? 0;
            if (!$projectId) {
                throw new Exception('Project ID required');
            }
            
            require_once __DIR__ . '/../../src/Models/Project.php';
            require_once __DIR__ . '/../../src/Models/Task.php';
            
            $projectModel = new Project();
            $taskModel = new Task();
            
            $project = $projectModel->find($projectId);
            if (!$project) {
                throw new Exception('Project not found');
            }
            
            // Parse tags if they exist
            if ($project['tags']) {
                $project['tags'] = json_decode($project['tags'], true);
            } else {
                $project['tags'] = [];
            }
            
            $tasks = $taskModel->getByProject($projectId);
            
            echo json_encode([
                'success' => true,
                'project' => $project,
                'tasks' => $tasks
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    // Ensure we're returning JSON even on errors
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    
    // Log the error for debugging
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => get_class($e)
    ]);
} catch (Error $e) {
    // Handle fatal errors
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    
    error_log("API Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'message' => 'Kerfisvilla: ' . $e->getMessage(),
        'error_type' => 'Fatal Error'
    ]);
}

// PDF Generation Functions
function generateMeetingHTML($meeting, $analysis) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($meeting['title']) . '</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #374151;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        h1 {
            color: #111827;
            font-weight: 700;
            font-size: 2rem;
            margin: 24px 0 16px 0;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }
        h2 {
            color: #374151;
            font-weight: 600;
            font-size: 1.5rem;
            margin: 20px 0 12px 0;
        }
        h3 {
            color: #4b5563;
            font-weight: 600;
            font-size: 1.25rem;
            margin: 16px 0 8px 0;
        }
        .bullet-point {
            margin: 8px 0;
            padding-left: 20px;
            position: relative;
        }
        .bullet-point::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #ff6b35;
            font-weight: bold;
        }
        strong {
            color: #111827;
            font-weight: 600;
        }
        .header-info {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #ff6b35;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 0.9rem;
            color: #6b7280;
            text-align: center;
        }
        @media print {
            html, body { 
                height: 100%; 
                margin: 0; 
                padding: 0; 
                font-size: 12pt; 
            }
            body {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
                padding: 20px;
            }
            .header-info {
                border-left: 4px solid #ff6b35 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            .ai-analysis {
                flex: 1;
            }
            .footer {
                margin-top: auto;
                page-break-inside: avoid;
                position: fixed;
                bottom: 20px;
                left: 20px;
                right: 20px;
                border-top: 1px solid #e5e7eb;
                background: white;
                padding-top: 15px;
            }
        }
    </style>
</head>
<body>';
    
    // Header information
    $html .= '<div class="header-info">';
    $html .= '<h1>' . htmlspecialchars($meeting['title']) . '</h1>';
    $html .= '<p><strong>Dagsetning:</strong> ' . formatIcelandicDate($meeting['date']) . '</p>';
    $html .= '<p><strong>Tegund:</strong> ' . translateInputType($meeting['input_type']) . '</p>';
    $html .= '<p><strong>Staða:</strong> ' . translateStatus($meeting['processing_status']) . '</p>';
    $html .= '</div>';
    
    // AI Analysis (formatted summary)
    if ($analysis && isset($analysis['summary'])) {
        $html .= '<div class="ai-analysis">';
        // Decode HTML entities and clean up the summary
        $summary = $analysis['summary'];
        $summary = html_entity_decode($summary, ENT_QUOTES, 'UTF-8');
        $html .= $summary;
        $html .= '</div>';
    }
    
    $html .= '<div class="footer">';
    $html .= '<p>Flutt út frá AI Verkefnastjóra á ' . date('j. F Y, H:i') . '</p>';
    $html .= '</div>';
    
    $html .= '</body></html>';
    
    return $html;
}

function generatePDF($html) {
    // Simple HTML to PDF conversion
    // For now, return HTML with PDF content type (browser will handle conversion)
    return $html;
}

function formatIcelandicDate($dateString) {
    $months = [
        1 => 'janúar', 2 => 'febrúar', 3 => 'mars', 4 => 'apríl',
        5 => 'maí', 6 => 'júní', 7 => 'júlí', 8 => 'ágúst',
        9 => 'september', 10 => 'október', 11 => 'nóvember', 12 => 'desember'
    ];
    
    $timestamp = strtotime($dateString);
    $day = date('j', $timestamp);
    $month = $months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "{$day}. {$month} {$year}";
}

function translateInputType($inputType) {
    $translations = [
        'text' => 'Texti',
        'ocr' => 'OCR',
        'voice' => 'Raddupptaka',
        'conversation' => 'Samtal'
    ];
    
    return $translations[$inputType] ?? ucfirst($inputType);
}

function translateStatus($status) {
    $translations = [
        'pending' => 'Í bið',
        'processing' => 'Í vinnslu',
        'completed' => 'Lokið',
        'error' => 'Villa'
    ];
    
    return $translations[$status] ?? ucfirst($status);
}
?>