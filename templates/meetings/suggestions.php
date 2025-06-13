<?php
/**
 * AI Suggestions Review Page
 */

$title = 'AI Tillögur - AI Verkefnastjóri';
$currentPage = 'suggestions';
$pageHeader = [
    'title' => 'AI Tillögur',
    'subtitle' => 'Farið yfir og samþykkið AI tillögur fyrir verkefni og verkþætti'
];

// Check if we have pending analysis
if (!isset($_SESSION['pending_analysis']) || empty($_SESSION['pending_analysis'])) {
    $_SESSION['flash_message'] = 'Engar fundargerðir til skoðunar. Vinsamlegast hlaðið upp fundargerðum fyrst.';
    $_SESSION['flash_type'] = 'warning';
    App::redirect('<?php echo App::url()?>?page=upload');
    exit;
}

$pendingData = $_SESSION['pending_analysis'];

// Handle form submission (approve/reject suggestions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once __DIR__ . '/../../src/Services/MeetingProcessor.php';
        require_once __DIR__ . '/../../src/Models/Project.php';
        require_once __DIR__ . '/../../src/Models/Task.php';
        require_once __DIR__ . '/../../src/Models/Meeting.php';
        
        $processor = new MeetingProcessor();
        $projectModel = new Project();
        $taskModel = new Task();
        $meetingModel = new Meeting();
        
        // Get approved suggestions from form data
        $approvedProjects = $_POST['approved_projects'] ?? [];
        $approvedTasks = $_POST['approved_tasks'] ?? [];
        $modifiedData = $_POST['modified_data'] ?? [];
        
        // Create meeting record
        $meetingId = $meetingModel->createMeeting([
            'title' => $pendingData['title'],
            'original_input' => $pendingData['notes'],
            'input_type' => 'text'
        ]);
        
        $createdProjects = [];
        $createdTasks = [];
        
        // Create approved projects
        foreach ($approvedProjects as $projectIndex) {
            $projectData = $modifiedData['projects'][$projectIndex] ?? null;
            if ($projectData) {
                $projectId = $projectModel->createProject([
                    'meeting_id' => $meetingId,
                    'name' => $projectData['name'],
                    'description' => $projectData['description'],
                    'priority' => $projectData['priority'],
                    'estimated_hours' => $projectData['estimated_hours'] ?? 0,
                    'deadline' => $projectData['deadline'] ?? null,
                    'tags' => isset($projectData['tags']) ? json_encode($projectData['tags']) : null,
                    'ai_confidence' => $projectData['confidence'] ?? 0.5
                ]);
                
                $createdProjects[$projectIndex] = $projectId;
            }
        }
        
        // Create approved tasks
        foreach ($approvedTasks as $taskIndex) {
            $taskData = $modifiedData['tasks'][$taskIndex] ?? null;
            if ($taskData) {
                $projectId = null;
                
                // Find the project ID (either existing or newly created)
                if (isset($taskData['project_index']) && isset($createdProjects[$taskData['project_index']])) {
                    $projectId = $createdProjects[$taskData['project_index']];
                }
                
                if ($projectId) {
                    $taskModel->createTask([
                        'project_id' => $projectId,
                        'title' => $taskData['title'],
                        'description' => $taskData['description'],
                        'priority' => $taskData['priority'] ?? 3,
                        'estimated_minutes' => $taskData['estimated_minutes'] ?? 0,
                        'deadline' => $taskData['deadline'] ?? null
                    ]);
                    
                    $createdTasks[] = $taskData['title'];
                }
            }
        }
        
        // Clear pending data
        unset($_SESSION['pending_analysis']);
        
        $_SESSION['flash_message'] = 'Tillögur samþykktar! Búin til ' . 
            count($createdProjects) . ' verkefni og ' . 
            count($createdTasks) . ' verkþætti.';
        $_SESSION['flash_type'] = 'success';
        
        App::redirect('<?php echo App::url()?>?page=projects');
        
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Villa við að búa til verkefni: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
}

ob_start();
?>

<div class="row">
    <div class="col-12">
        
        <!-- Analysis Status -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-robot"></i> AI Greining á fundargerðum
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="spinner-border text-primary" role="status" id="analysisSpinner">
                            <span class="visually-hidden">Greinir...</span>
                        </div>
                    </div>
                    <div>
                        <h6 class="mb-1" id="analysisStatus">Greinir fundargerðir...</h6>
                        <p class="mb-0 text-muted" id="analysisSubtext">Claude AI er að greina efnið og búa til tillögur</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Suggestions Container (Hidden initially) -->
        <div id="suggestionsContainer" class="d-none">
            <form method="POST" id="suggestionsForm">
                
                <!-- Statistics Overview -->
                <div class="row mb-4" id="suggestionsStats">
                    <div class="col-md-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h3 class="text-success mb-1" id="highConfidenceCount">0</h3>
                                <small class="text-muted">🟢 Hátt öryggi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h3 class="text-warning mb-1" id="mediumConfidenceCount">0</h3>
                                <small class="text-muted">🟡 Miðlungs öryggi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <h3 class="text-danger mb-1" id="lowConfidenceCount">0</h3>
                                <small class="text-muted">🔴 Lágt öryggi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h3 class="text-primary mb-1" id="totalSuggestions">0</h3>
                                <small class="text-muted">📋 Allar tillögur</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bulk Actions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Fjöldaaðgerðir</h5>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-success" onclick="approveAllHigh()">
                                    ✅ Samþykkja allt með hátt öryggi
                                </button>
                                <button type="button" class="btn btn-outline-warning" onclick="reviewMedium()">
                                    👀 Fara yfir miðlungs öryggi
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="selectAll()">
                                    📋 Velja allt
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="selectNone()">
                                    ❌ Velja ekkert
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Project Groups with Tasks -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-kanban"></i> Verkefni og verkþættir
                            <span class="badge bg-primary ms-2" id="projectCount">0</span> verkefni
                            <span class="badge bg-secondary ms-1" id="taskCount">0</span> verkþættir
                        </h5>
                    </div>
                    <div class="card-body" id="projectGroupSuggestions">
                        <!-- Project groups with their tasks will be loaded here by JavaScript -->
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="<?php echo App::url()?>?page=upload" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Til baka
                                </a>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-danger me-2" onclick="rejectAll()">
                                    <i class="bi bi-x-circle"></i> Hafna öllu
                                </button>
                                <button type="submit" class="btn btn-success" id="createSelectedBtn" disabled>
                                    <i class="bi bi-check-circle"></i> Búa til valin atriði
                                    <span class="badge bg-light text-dark ms-1" id="selectedCount">0</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
            </form>
        </div>
        
    </div>
</div>

<script>
// Store the analysis data
let analysisData = null;
let selectedProjects = new Set();
let selectedTasks = new Set();

// Start AI analysis when page loads
document.addEventListener('DOMContentLoaded', function() {
    startAIAnalysis();
});

function startAIAnalysis() {
    // Simulate AI analysis (replace with actual API call)
    setTimeout(() => {
        // Get the analysis from our AI service
        fetch('<?php echo App::url()?>?page=api&action=analyzeNotes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                title: '<?php echo addslashes($pendingData['title']); ?>',
                notes: <?php echo json_encode($pendingData['notes']); ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                analysisData = data.analysis;
                showSuggestions();
            } else {
                showAnalysisError(data.message);
            }
        })
        .catch(error => {
            console.error('Analysis error:', error);
            showAnalysisError('Villa við AI greiningu');
        });
    }, 2000); // Simulate processing time
}

function showSuggestions() {
    // Hide analysis spinner
    document.getElementById('analysisSpinner').classList.add('d-none');
    document.getElementById('analysisStatus').textContent = 'Greining lokið!';
    document.getElementById('analysisSubtext').textContent = 'Farðu yfir tillögurnar hér að neðan';
    
    // Show suggestions container
    document.getElementById('suggestionsContainer').classList.remove('d-none');
    
    // Populate statistics
    updateStatistics();
    
    // Populate suggestions
    populateProjectGroupSuggestions();
}

function showAnalysisError(message) {
    document.getElementById('analysisSpinner').classList.add('d-none');
    document.getElementById('analysisStatus').textContent = 'Villa við greiningu';
    document.getElementById('analysisSubtext').innerHTML = `<span class="text-danger">${message}</span>`;
}

function updateStatistics() {
    if (!analysisData) return;
    
    const projects = analysisData.projects || [];
    const tasks = analysisData.tasks || [];
    
    let highConf = 0, medConf = 0, lowConf = 0;
    
    [...projects, ...tasks].forEach(item => {
        const conf = item.confidence || 0;
        if (conf >= 0.8) highConf++;
        else if (conf >= 0.5) medConf++;
        else lowConf++;
    });
    
    document.getElementById('highConfidenceCount').textContent = highConf;
    document.getElementById('mediumConfidenceCount').textContent = medConf;
    document.getElementById('lowConfidenceCount').textContent = lowConf;
    document.getElementById('totalSuggestions').textContent = projects.length + tasks.length;
    document.getElementById('projectCount').textContent = projects.length;
    document.getElementById('taskCount').textContent = tasks.length;
}

function populateProjectGroupSuggestions() {
    const container = document.getElementById('projectGroupSuggestions');
    const projects = analysisData.projects || [];
    const tasks = analysisData.tasks || [];
    
    if (projects.length === 0) {
        container.innerHTML = '<p class="text-muted">Engar verkefnatillögur fundust.</p>';
        return;
    }
    
    let html = '';
    
    projects.forEach((project, projectIndex) => {
        const projectConfidence = Math.round((project.confidence || 0) * 100);
        const projectConfLevel = projectConfidence >= 80 ? 'success' : projectConfidence >= 50 ? 'warning' : 'danger';
        const projectConfIcon = projectConfidence >= 80 ? '🟢' : projectConfidence >= 50 ? '🟡' : '🔴';
        
        // Get tasks for this project
        const projectTasks = tasks.filter(task => task.project_index === projectIndex);
        
        html += `
            <div class="project-group mb-4 border rounded">
                <!-- Project Header -->
                <div class="project-item bg-light border-bottom p-3" data-type="project" data-index="${projectIndex}">
                    <div class="d-flex align-items-start">
                        <div class="form-check me-3 mt-1">
                            <input class="form-check-input" type="checkbox" name="approved_projects[]" 
                                   value="${projectIndex}" id="project_${projectIndex}" onchange="updateSelection(); toggleProjectTasks(${projectIndex})">
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0 text-primary">
                                    <i class="bi bi-kanban"></i> ${project.name}
                                </h5>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-${projectConfLevel} me-2">${projectConfIcon} ${projectConfidence}%</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editProject(${projectIndex})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-muted mb-2">${project.description}</p>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <small class="text-muted">Forgangur:</small>
                                    <span class="badge bg-info">${project.priority || 'medium'}</span>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Áætlað:</small>
                                    <span class="text-primary">${project.estimated_hours || 0}h</span>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Verkþættir:</small>
                                    <span class="badge bg-secondary">${projectTasks.length}</span>
                                </div>
                                ${project.deadline ? `
                                <div class="col-md-3">
                                    <small class="text-muted">Skiladagur:</small>
                                    <span class="text-warning">${project.deadline}</span>
                                </div>
                                ` : ''}
                            </div>
                            ${project.ai_reasoning ? `
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-lightbulb"></i> AI röksemdafærsla: ${project.ai_reasoning}
                                    </small>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Hidden inputs for modified data -->
                    <input type="hidden" name="modified_data[projects][${projectIndex}][name]" value="${project.name}">
                    <input type="hidden" name="modified_data[projects][${projectIndex}][description]" value="${project.description}">
                    <input type="hidden" name="modified_data[projects][${projectIndex}][priority]" value="${project.priority || 'medium'}">
                    <input type="hidden" name="modified_data[projects][${projectIndex}][estimated_hours]" value="${project.estimated_hours || 0}">
                    <input type="hidden" name="modified_data[projects][${projectIndex}][deadline]" value="${project.deadline || ''}">
                    <input type="hidden" name="modified_data[projects][${projectIndex}][confidence]" value="${project.confidence || 0.5}">
                </div>
                
                <!-- Project Tasks -->
                <div class="project-tasks p-3" id="project_${projectIndex}_tasks">`;
        
        if (projectTasks.length > 0) {
            html += `<h6 class="text-muted mb-3"><i class="bi bi-list-task"></i> Verkþættir (${projectTasks.length})</h6>`;
            
            projectTasks.forEach((task, taskArrayIndex) => {
                const taskIndex = tasks.indexOf(task); // Get original task index
                const taskConfidence = Math.round((task.confidence || 0) * 100);
                const taskConfLevel = taskConfidence >= 80 ? 'success' : taskConfidence >= 50 ? 'warning' : 'danger';
                const taskConfIcon = taskConfidence >= 80 ? '🟢' : taskConfidence >= 50 ? '🟡' : '🔴';
                
                html += `
                    <div class="task-item border rounded p-3 ms-4 mb-2 bg-white" data-type="task" data-index="${taskIndex}">
                        <div class="d-flex align-items-start">
                            <div class="form-check me-3 mt-1">
                                <input class="form-check-input task-checkbox" type="checkbox" name="approved_tasks[]" 
                                       value="${taskIndex}" id="task_${taskIndex}" data-project="${projectIndex}" onchange="updateSelection()">
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">
                                        <i class="bi bi-check-square"></i> ${task.title}
                                    </h6>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-${taskConfLevel} me-2">${taskConfIcon} ${taskConfidence}%</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editTask(${taskIndex})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-muted mb-2">${task.description}</p>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <small class="text-muted">Forgangur:</small>
                                        <span class="badge bg-info">P${task.priority || 3}</span>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Áætlað:</small>
                                        <span class="text-primary">${Math.round((task.estimated_minutes || 0) / 60)}h</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden inputs for modified data -->
                        <input type="hidden" name="modified_data[tasks][${taskIndex}][title]" value="${task.title}">
                        <input type="hidden" name="modified_data[tasks][${taskIndex}][description]" value="${task.description}">
                        <input type="hidden" name="modified_data[tasks][${taskIndex}][priority]" value="${task.priority || 3}">
                        <input type="hidden" name="modified_data[tasks][${taskIndex}][estimated_minutes]" value="${task.estimated_minutes || 0}">
                        <input type="hidden" name="modified_data[tasks][${taskIndex}][project_index]" value="${task.project_index || 0}">
                        <input type="hidden" name="modified_data[tasks][${taskIndex}][confidence]" value="${task.confidence || 0.5}">
                    </div>
                `;
            });
        } else {
            html += `<p class="text-muted mb-0 ms-4"><i class="bi bi-info-circle"></i> Engir verkþættir fundust fyrir þetta verkefni</p>`;
        }
        
        html += `
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function toggleProjectTasks(projectIndex) {
    const projectCheckbox = document.getElementById(`project_${projectIndex}`);
    const taskCheckboxes = document.querySelectorAll(`input[data-project="${projectIndex}"]`);
    
    // If project is selected, also select all its tasks
    if (projectCheckbox.checked) {
        taskCheckboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
    } else {
        // If project is deselected, also deselect all its tasks
        taskCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
}

function updateSelection() {
    const projectCheckboxes = document.querySelectorAll('input[name="approved_projects[]"]:checked');
    const taskCheckboxes = document.querySelectorAll('input[name="approved_tasks[]"]:checked');
    
    const totalSelected = projectCheckboxes.length + taskCheckboxes.length;
    
    document.getElementById('selectedCount').textContent = totalSelected;
    document.getElementById('createSelectedBtn').disabled = totalSelected === 0;
}

function approveAllHigh() {
    document.querySelectorAll('.suggestion-item').forEach(item => {
        const badge = item.querySelector('.badge');
        if (badge && badge.textContent.includes('🟢')) {
            const checkbox = item.querySelector('input[type="checkbox"]');
            checkbox.checked = true;
        }
    });
    updateSelection();
}

function reviewMedium() {
    // Scroll to first medium confidence item
    const mediumItem = document.querySelector('.badge:contains("🟡")');
    if (mediumItem) {
        mediumItem.closest('.suggestion-item').scrollIntoView({ behavior: 'smooth' });
    }
}

function selectAll() {
    document.querySelectorAll('input[name^="approved_"]').forEach(cb => cb.checked = true);
    updateSelection();
}

function selectNone() {
    document.querySelectorAll('input[name^="approved_"]').forEach(cb => cb.checked = false);
    updateSelection();
}

function rejectAll() {
    if (confirm('Ertu viss um að þú viljir hafna öllum tillögum?')) {
        window.location.href = '<?php echo App::url()?>?page=upload';
    }
}

function editProject(index) {
    // TODO: Implement inline editing
    alert('Breytingaraðgerð í þróun');
}

function editTask(index) {
    // TODO: Implement inline editing  
    alert('Breytingaraðgerð í þróun');
}
</script>

<style>
.project-group {
    transition: all 0.2s ease;
}

.project-item {
    transition: all 0.2s ease;
}

.project-item:hover {
    background-color: #e3f2fd !important;
}

.task-item {
    transition: all 0.2s ease;
    border-left: 4px solid #dee2e6;
}

.task-item:hover {
    background-color: #f8f9fa !important;
    border-left-color: #0d6efd !important;
}

.project-item input[type="checkbox"]:checked ~ .flex-grow-1 {
    opacity: 0.9;
}

.task-item input[type="checkbox"]:checked ~ .flex-grow-1 {
    opacity: 0.8;
}

.badge {
    font-size: 0.75em;
}

#analysisSpinner {
    width: 2rem;
    height: 2rem;
}

.project-tasks {
    background-color: #fafafa;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>