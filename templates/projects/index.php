<?php
/**
 * Projects List Page
 */

$title = 'Verkefni - AI Verkefnastjóri';
$currentPage = 'projects';
$pageHeader = [
    'title' => 'Verkefni',
    'subtitle' => 'Stjórnaðu verkefnum þínum og verkþáttum',
    'actions' => [
        [
            'label' => 'Nýtt verkefni',
            'url' => App::url('projects/create'),
            'type' => 'primary',
            'icon' => 'plus-circle'
        ]
    ]
];

try {
    require_once __DIR__ . '/../../src/Models/Project.php';
    require_once __DIR__ . '/../../src/Models/Task.php';
    
    $projectModel = new Project();
    $taskModel = new Task();
    
    $projects = $projectModel->findAll([], 'created_at DESC');
    
    // Add task counts to each project
    foreach ($projects as &$project) {
        $project['tasks'] = $taskModel->getByProject($project['id']);
        $project['task_count'] = count($project['tasks']);
        $project['completed_tasks'] = count(array_filter($project['tasks'], function($task) {
            return $task['is_completed'];
        }));
        $project['tags'] = $project['tags'] ? json_decode($project['tags'], true) : [];
    }
    
} catch (Exception $e) {
    $projects = [];
    $error = $e->getMessage();
}

ob_start();
?>

<?php if (isset($error)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        Ekki tókst að hlaða verkefnum: <?php echo App::sanitize($error); ?>
        <a href="<?php echo App::url('setup'); ?>" class="alert-link">Keyra uppsetningu</a> til að frumstilla gagnagrunninn.
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        
        <?php if (empty($projects)): ?>
            <!-- Empty State -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-kanban text-muted" style="font-size: 4rem;"></i>
                    <h3 class="mt-3 text-muted">Engin verkefni ennþá</h3>
                    <p class="text-muted">Hladdu upp fundargerðum eða búðu til þitt fyrsta verkefni til að byrja.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="<?php echo App::url('upload'); ?>" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i> Hlaða upp fundargerðum
                        </a>
                        <a href="<?php echo App::url('projects/create'); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle"></i> Búa til verkefni
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Filter Bar -->
            <div class="card mb-4">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted">Sía:</span>
                        <div class="filter-chips">
                            <span class="filter-chip active" data-filter="all">Allt</span>
                            <span class="filter-chip" data-filter="active">Virk</span>
                            <span class="filter-chip" data-filter="completed">Lokið</span>
                            <span class="filter-chip" data-filter="overdue">Seint</span>
                            <span class="filter-chip" data-filter="high-priority">Mikilvæg</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Verkefni Töflur -->
            <div class="row" id="projectsContainer">
                <?php foreach ($projects as $project): ?>
                    <?php
                    $priorityClass = 'priority-' . $project['priority'];
                    $statusClass = 'status-' . $project['status'];
                    $isOverdue = $project['deadline'] && strtotime($project['deadline']) < time() && $project['status'] !== 'completed';
                    
                    // Icelandic status labels
                    $statusLabels = [
                        'planning' => 'Skipulagning',
                        'active' => 'Virkt',
                        'on_hold' => 'Í bið',
                        'completed' => 'Lokið',
                        'cancelled' => 'Aflýst'
                    ];
                    
                    // Icelandic priority labels
                    $priorityLabels = [
                        'low' => 'Lágur',
                        'medium' => 'Miðlungs',
                        'high' => 'Hár',
                        'urgent' => 'Brýnn'
                    ];
                    ?>
                    <div class="col-lg-4 col-md-6 mb-4 filterable-item <?php echo $statusClass; ?> <?php echo $priorityClass; ?> <?php echo $isOverdue ? 'filter-overdue' : ''; ?>"
                         data-status="<?php echo $project['status']; ?>"
                         data-priority="<?php echo $project['priority']; ?>"
                         data-overdue="<?php echo $isOverdue ? 'true' : 'false'; ?>"
                         data-project-id="<?php echo $project['id']; ?>">
                        
                        <div class="card h-100 project-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0">
                                        <?php echo App::sanitize($project['name']); ?>
                                    </h5>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="viewProject(<?php echo $project['id']; ?>)">
                                                <i class="bi bi-eye"></i> Skoða verkefni
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="editProject(<?php echo $project['id']; ?>)">
                                                <i class="bi bi-pencil"></i> Breyta verkefni
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="duplicateProject(<?php echo $project['id']; ?>)">
                                                <i class="bi bi-files"></i> Afrita verkefni
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo addslashes($project['name']); ?>')">
                                                <i class="bi bi-trash"></i> Eyða verkefni
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <p class="card-text text-muted mb-3">
                                    <?php echo App::sanitize(substr($project['description'], 0, 100)) . (strlen($project['description']) > 100 ? '...' : ''); ?>
                                </p>
                                
                                <!-- Merki -->
                                <?php if (!empty($project['tags'])): ?>
                                    <div class="mb-3">
                                        <?php foreach (array_slice($project['tags'], 0, 3) as $tag): ?>
                                            <span class="badge bg-light text-dark me-1 mb-1"><?php echo App::sanitize($tag); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($project['tags']) > 3): ?>
                                            <span class="text-muted small">+<?php echo count($project['tags']) - 3; ?> fleiri</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Framfarir -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">Framfarir</small>
                                        <small class="text-muted"><?php echo $project['completion_percentage']; ?>%</small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" style="width: <?php echo $project['completion_percentage']; ?>%"></div>
                                    </div>
                                </div>
                                
                                <!-- Verkþættir yfirlit -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <small class="text-muted task-count">
                                        <i class="bi bi-list-task"></i>
                                        <?php echo $project['completed_tasks']; ?>/<?php echo $project['task_count']; ?> verkþættir
                                    </small>
                                    <?php if ($project['deadline']): ?>
                                        <small class="<?php echo $isOverdue ? 'text-danger' : 'text-muted'; ?>">
                                            <i class="bi bi-calendar-event"></i>
                                            <?php echo date('j. M', strtotime($project['deadline'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Staða og forgangur -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-<?php echo $project['status'] === 'active' ? 'primary' : ($project['status'] === 'completed' ? 'success' : 'secondary'); ?>">
                                        <?php echo $statusLabels[$project['status']] ?? ucfirst($project['status']); ?>
                                    </span>
                                    <span class="badge bg-<?php 
                                        echo $project['priority'] === 'urgent' ? 'danger' : 
                                             ($project['priority'] === 'high' ? 'warning' : 
                                             ($project['priority'] === 'medium' ? 'info' : 'secondary')); 
                                    ?>">
                                        <?php echo $priorityLabels[$project['priority']] ?? ucfirst($project['priority']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Flýtiaðgerðir -->
                            <div class="card-footer bg-transparent">
                                <div class="btn-group w-100">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewProject(<?php echo $project['id']; ?>)">
                                        <i class="bi bi-eye"></i> Skoða
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="editProject(<?php echo $project['id']; ?>)">
                                        <i class="bi bi-pencil"></i> Breyta
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="quickAddTask(<?php echo $project['id']; ?>)">
                                        <i class="bi bi-plus"></i> Verkþáttur
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- Enhanced Project Details Modal with AI Assistant -->
<div class="modal fade" id="projectModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-white border-0 py-4">
                <h5 class="modal-title text-dark fw-normal">
                    Verkefni
                </h5>
                <div class="d-flex gap-3 align-items-center">
                    <button class="btn btn-sm text-muted hover-text-dark border-0 p-2" onclick="toggleEditMode()">
                        <i class="bi bi-pencil me-1"></i> <span id="editModeBtn">Breyta</span>
                    </button>
                    <button type="button" class="btn-close opacity-50 hover-opacity-100" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0 h-100">
                    <!-- Main Project Content -->
                    <div class="col-lg-8" id="projectMainContent" style="background: #fafafa;">
                        <div class="p-6 h-100 overflow-auto">
                            <!-- Selected Text Display -->
                            <div class="mb-4 p-4 rounded-lg d-none" id="selectedTextPanel" style="background: #fff3cd; border: 1px solid #ffeaa7;">
                                <div class="small text-muted mb-2">Valinn texti</div>
                                <div class="bg-white p-3 rounded border-0 small" id="selectedText"></div>
                            </div>
                            
                            <div id="projectContent">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Hleður...</span>
                                    </div>
                                    <p class="mt-2">Hleður verkefni...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Clean Chat Panel -->
                    <div class="col-lg-4 d-flex flex-column bg-white" id="aiPanel" style="border-left: 1px solid #e0e0e0;">
                        <div class="p-4 border-bottom" style="border-color: #f0f0f0;">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-light p-2">
                                        <i class="bi bi-robot text-muted"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-medium">Claude</h6>
                                    <small class="text-muted">AI Assistant</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Clean Chat History -->
                        <div class="flex-grow-1 p-4" style="overflow-y: auto;">
                            <div id="aiChatHistory">
                                <div class="text-center py-5">
                                    <div class="text-muted mb-3">
                                        <i class="bi bi-chat-text" style="font-size: 2rem; opacity: 0.3;"></i>
                                    </div>
                                    <p class="text-muted mb-0">Byrjaðu samtal</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Clean Chat Input -->
                        <div class="p-4 border-top" style="border-color: #f0f0f0;">
                            <div class="d-flex">
                                <input type="text" class="form-control border-0 me-2 py-2 px-3" 
                                       id="chatInput" placeholder="Skrifa skilaboð..."
                                       onkeypress="if(event.key==='Enter') sendChatMessage()"
                                       style="background: #f8f9fa; border-radius: 20px;">
                                <button class="btn btn-dark rounded-circle p-2" onclick="sendChatMessage()" style="width: 40px; height: 40px;">
                                    <i class="bi bi-arrow-up"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AI Response Modal -->
<div class="modal fade" id="aiResponseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-robot text-primary"></i> Claude's Response
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="aiResponseContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Processing...</span>
                        </div>
                        <p class="mt-2">Claude is thinking...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="applyAISuggestion()">Apply Suggestion</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewProject(projectId) {
    const modal = new bootstrap.Modal(document.getElementById('projectModal'));
    document.getElementById('projectContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading project details...</p>
        </div>
    `;
    modal.show();
    
    // Load project details via AJAX
    fetch(`/?page=api&action=project&id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showProjectDetails(data.project, data.tasks);
            } else {
                document.getElementById('projectContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Villa við að hlaða verkefni: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('projectContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    Villa við að hlaða verkefnaupplýsingar.
                </div>
            `;
        });
}

// Global variables for the enhanced project interface
let currentProject = null;
let currentTasks = [];
let editMode = false;
let selectedText = '';
let aiChatHistory = [];

function showProjectDetails(project, tasks) {
    currentProject = project;
    currentTasks = tasks;
    
    const priorityColors = {
        'urgent': 'danger',
        'high': 'warning', 
        'medium': 'info',
        'low': 'secondary'
    };
    
    const statusColors = {
        'active': 'primary',
        'completed': 'success',
        'planning': 'secondary',
        'on_hold': 'warning',
        'cancelled': 'danger'
    };
    
    let tasksHtml = '';
    if (tasks && tasks.length > 0) {
        tasks.forEach((task, index) => {
            const completedClass = task.is_completed ? 'text-decoration-line-through opacity-75' : '';
            const checkIcon = task.is_completed ? 'bi-check-circle-fill text-success' : 'bi-circle';
            
            tasksHtml += `
                <div class="task-item border rounded p-3 mb-2 selectable-text" data-task-id="${task.id}">
                    <div class="d-flex align-items-start">
                        <input type="checkbox" class="form-check-input me-3 mt-1" 
                               ${task.is_completed ? 'checked' : ''} 
                               onchange="toggleTaskCompletion(${task.id}, this.checked)">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 ${completedClass} editable-field" 
                                data-field="task-title-${task.id}" 
                                data-original="${task.title}">
                                ${task.title}
                            </h6>
                            ${task.description ? `
                                <p class="text-muted small mb-2 ${completedClass} editable-field" 
                                   data-field="task-desc-${task.id}" 
                                   data-original="${task.description}">
                                    ${task.description}
                                </p>
                            ` : ''}
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <select class="badge-select priority-select" data-task-id="${task.id}" ${!editMode ? 'disabled' : ''}>
                                    ${[1,2,3,4,5].map(p => `<option value="${p}" ${task.priority == p ? 'selected' : ''}>P${p}</option>`).join('')}
                                </select>
                                <span class="editable-time text-muted" data-field="task-time-${task.id}" data-minutes="${task.estimated_minutes}">
                                    ${task.estimated_minutes ? `${Math.round(task.estimated_minutes/60)}klst áætlað` : 'Ekkert áætlun'}
                                </span>
                                ${task.deadline ? `
                                    <span class="text-muted editable-date" data-field="task-deadline-${task.id}">
                                        <i class="bi bi-calendar"></i> ${task.deadline}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        <div class="task-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="askAIAboutTask(${task.id})">
                                <i class="bi bi-robot"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        tasksHtml = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-plus-circle" style="font-size: 2rem;"></i>
                <p class="mt-2">Engir verkþættir ennþá</p>
                <button class="btn btn-outline-primary" onclick="addNewTask()">
                    <i class="bi bi-plus"></i> Bæta við verkþætti
                </button>
            </div>
        `;
    }
    
    document.getElementById('projectContent').innerHTML = `
        <div class="project-header mb-4">
            <h2 class="editable-field" data-field="project-name" data-original="${project.name}">
                ${project.name}
            </h2>
            <p class="text-muted editable-field" data-field="project-description" data-original="${project.description}">
                ${project.description}
            </p>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body p-3">
                                <small class="text-muted">Staða</small>
                                <div class="d-flex align-items-center mt-1">
                                    <select class="form-select form-select-sm status-select" data-field="status" ${!editMode ? 'disabled' : ''}>
                                        <option value="planning" ${project.status === 'planning' ? 'selected' : ''}>Skipulagning</option>
                                        <option value="active" ${project.status === 'active' ? 'selected' : ''}>Virkt</option>
                                        <option value="on_hold" ${project.status === 'on_hold' ? 'selected' : ''}>Í bið</option>
                                        <option value="completed" ${project.status === 'completed' ? 'selected' : ''}>Lokið</option>
                                        <option value="cancelled" ${project.status === 'cancelled' ? 'selected' : ''}>Aflýst</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body p-3">
                                <small class="text-muted">Forgangur</small>
                                <div class="d-flex align-items-center mt-1">
                                    <select class="form-select form-select-sm priority-select" data-field="priority" ${!editMode ? 'disabled' : ''}>
                                        <option value="low" ${project.priority === 'low' ? 'selected' : ''}>Lágur</option>
                                        <option value="medium" ${project.priority === 'medium' ? 'selected' : ''}>Miðlungs</option>
                                        <option value="high" ${project.priority === 'high' ? 'selected' : ''}>Hár</option>
                                        <option value="urgent" ${project.priority === 'urgent' ? 'selected' : ''}>Brýnn</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body p-3">
                                <small class="text-muted">Skiladagur</small>
                                <div class="mt-1">
                                    <input type="date" class="form-control form-control-sm" 
                                           value="${project.deadline || ''}" 
                                           data-field="deadline" ${!editMode ? 'disabled' : ''}>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body p-3">
                                <small class="text-muted">Áætlaðar klukkustundir</small>
                                <div class="mt-1">
                                    <input type="number" class="form-control form-control-sm" 
                                           value="${project.estimated_hours || 0}" 
                                           data-field="estimated_hours" ${!editMode ? 'disabled' : ''}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6>Framfarir</h6>
                        <span class="badge bg-primary">${project.completion_percentage}%</span>
                    </div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar" style="width: ${project.completion_percentage}%"></div>
                    </div>
                    <small class="text-muted">
                        ${tasks.filter(t => t.is_completed).length} af ${tasks.length} verkþáttum lokið
                    </small>
                </div>
                
                ${project.tags && project.tags.length > 0 ? `
                <div class="mt-4">
                    <h6>Merki</h6>
                    <div class="editable-tags">
                        ${project.tags.map(tag => `
                            <span class="badge bg-secondary me-1 mb-1">
                                ${tag}
                                ${editMode ? `<i class="bi bi-x-circle ms-1" onclick="removeTag('${tag}')"></i>` : ''}
                            </span>
                        `).join('')}
                        ${editMode ? `
                            <button class="btn btn-outline-secondary btn-sm" onclick="addTag()">
                                <i class="bi bi-plus"></i> Bæta við merki
                            </button>
                        ` : ''}
                    </div>
                </div>
                ` : ''}
            </div>
            
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-robot"></i> AI Innsýn
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>AI Öryggi:</span>
                            <span class="badge bg-success">${Math.round((project.ai_confidence || 0.5) * 100)}%</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Búið til:</span>
                            <span>${new Date(project.created_at).toLocaleDateString('is-IS')}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Síðast uppfært:</span>
                            <span>${new Date(project.updated_at).toLocaleDateString('is-IS')}</span>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-success btn-sm" onclick="aiQuickAction('suggest')">
                                💡 Stinga upp á umbótum
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="aiQuickAction('breakdown')">
                                📋 Skipta niður verkþáttum
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="aiQuickAction('risks')">
                                ⚠️ Greina áhættu
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="bi bi-list-task"></i> Verkþættir (${tasks.length})</h5>
            <button class="btn btn-primary btn-sm" onclick="addNewTask()">
                <i class="bi bi-plus"></i> Bæta við verkþætti
            </button>
        </div>
        
        <div class="tasks-container selectable-text">
            ${tasksHtml}
        </div>
    `;
    
    // Initialize text selection
    initTextSelection();
    
    // Initialize auto-save
    if (editMode) {
        initAutoSave();
    }
}

// ==================== VERKEFNI CRUD AÐGERÐIR ====================

function editProject(projectId) {
    // Opna verkefni í breytingaham
    viewProject(projectId);
    // Virkja breytingaham sjálfkrafa
    setTimeout(() => {
        toggleEditMode();
    }, 500);
}

function duplicateProject(projectId) {
    if (!confirm('Ertu viss um að þú viljir afrita þetta verkefni?')) {
        return;
    }
    
    fetch('/?page=api&action=duplicateProject', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ projectId: projectId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Endurhlaða síðuna til að sýna nýja verkefnið
            location.reload();
        } else {
            alert('Villa við að afrita verkefni: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error duplicating project:', error);
        alert('Villa við að afrita verkefni');
    });
}

function deleteProject(projectId, projectName) {
    if (!confirm(`Ertu viss um að þú viljir eyða verkefninu "${projectName}"?\n\nÞessi aðgerð er óafturkræf og mun eyða öllum verkþáttum og gögnum sem tengjast verkefninu.`)) {
        return;
    }
    
    fetch('/?page=api&action=deleteProject', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ projectId: projectId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fjarlægja verkefnakortið úr DOM
            const projectCard = document.querySelector(`[data-project-id="${projectId}"]`);
            if (projectCard) {
                projectCard.remove();
            }
            
            // Sýna árangursmelding
            showSuccessMessage('Verkefni eytt!');
            
            // Endurhlaða síðuna til að uppfæra tölfræði
            setTimeout(() => location.reload(), 1500);
        } else {
            alert('Villa við að eyða verkefni: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting project:', error);
        alert('Villa við að eyða verkefni');
    });
}

function quickAddTask(projectId) {
    const taskTitle = prompt('Skrifaðu titil fyrir nýjan verkþátt:');
    if (!taskTitle || taskTitle.trim() === '') {
        return;
    }
    
    fetch('/?page=api&action=createTask', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            projectId: projectId,
            title: taskTitle.trim(),
            description: '',
            priority: 3
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Verkþáttur bætt við!');
            // Uppfæra verkefnakortið
            updateProjectCard(projectId);
        } else {
            alert('Villa við að búa til verkþátt: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error creating task:', error);
        alert('Villa við að búa til verkþátt');
    });
}

function updateProjectCard(projectId) {
    // Sækja uppfærðar upplýsingar um verkefni
    fetch(`/?page=api&action=project&id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Uppfæra verkþáttafjölda á kortinu
                const taskCountElement = document.querySelector(`[data-project-id="${projectId}"] .task-count`);
                if (taskCountElement && data.tasks) {
                    const completed = data.tasks.filter(t => t.is_completed).length;
                    const total = data.tasks.length;
                    taskCountElement.textContent = `${completed}/${total} verkþættir`;
                }
            }
        })
        .catch(error => {
            console.error('Error updating project card:', error);
        });
}

function showSuccessMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Fjarlægja eftir 3 sekúndur
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 3000);
}

function addTask(projectId) {
    quickAddTask(projectId);
}

// ==================== AI-POWERED INTERFACE FUNCTIONS ====================

function initTextSelection() {
    const selectableElements = document.querySelectorAll('.selectable-text');
    
    selectableElements.forEach(element => {
        element.addEventListener('mouseup', handleTextSelection);
        element.addEventListener('touchend', handleTextSelection);
    });
}

function handleTextSelection() {
    const selection = window.getSelection();
    const selectedTextValue = selection.toString().trim();
    
    if (selectedTextValue.length > 5) {
        selectedText = selectedTextValue;
        updateSelectedTextPanel(selectedTextValue);
        showAIPanelIfHidden();
    } else {
        hideSelectedTextPanel();
    }
}

function updateSelectedTextPanel(text) {
    const panel = document.getElementById('selectedTextPanel');
    const textDiv = document.getElementById('selectedText');
    
    if (text && text.length > 0) {
        textDiv.textContent = text.length > 200 ? text.substring(0, 200) + '...' : text;
        panel.classList.remove('d-none');
    } else {
        panel.classList.add('d-none');
    }
}

function hideSelectedTextPanel() {
    document.getElementById('selectedTextPanel').classList.add('d-none');
    selectedText = '';
}

function showAIPanelIfHidden() {
    // AI panel is always visible now, so this function does nothing
}

function toggleEditMode() {
    editMode = !editMode;
    const btn = document.getElementById('editModeBtn');
    const mainContent = document.getElementById('projectMainContent');
    
    if (editMode) {
        btn.textContent = 'Vista';
        mainContent.classList.add('edit-mode');
        enableEditableFields();
        initAutoSave();
    } else {
        btn.textContent = 'Breyta';
        mainContent.classList.remove('edit-mode');
        disableEditableFields();
        saveAllChanges();
    }
}

function enableEditableFields() {
    // Enable text editing
    document.querySelectorAll('.editable-field').forEach(field => {
        field.contentEditable = true;
        field.classList.add('editable-active');
    });
    
    // Enable form controls
    document.querySelectorAll('select[disabled], input[disabled]').forEach(control => {
        control.disabled = false;
    });
}

function disableEditableFields() {
    document.querySelectorAll('.editable-field').forEach(field => {
        field.contentEditable = false;
        field.classList.remove('editable-active');
    });
    
    document.querySelectorAll('select:not(.always-enabled), input:not(.always-enabled)').forEach(control => {
        control.disabled = true;
    });
}

function initAutoSave() {
    // Auto-save when fields change
    document.querySelectorAll('.editable-field').forEach(field => {
        field.addEventListener('blur', handleFieldChange);
        field.addEventListener('input', debounce(handleFieldChange, 1000));
    });
    
    document.querySelectorAll('select, input[type="date"], input[type="number"]').forEach(field => {
        field.addEventListener('change', handleFieldChange);
    });
}

function handleFieldChange(event) {
    const field = event.target;
    const fieldName = field.dataset.field;
    const newValue = field.contentEditable ? field.textContent.trim() : field.value;
    
    // Visual feedback
    field.style.backgroundColor = '#fff3cd';
    setTimeout(() => {
        field.style.backgroundColor = '';
    }, 500);
    
    // Store change for batch save
    if (!window.pendingChanges) {
        window.pendingChanges = {};
    }
    window.pendingChanges[fieldName] = newValue;
}

function saveAllChanges() {
    if (!window.pendingChanges || Object.keys(window.pendingChanges).length === 0) {
        return;
    }
    
    // Save changes via API
    fetch('/?page=api&action=updateProject', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            projectId: currentProject.id,
            changes: window.pendingChanges
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.pendingChanges = {};
            showSaveSuccessIndicator();
        } else {
            showSaveErrorIndicator();
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        showSaveErrorIndicator();
    });
}

function showSaveSuccessIndicator() {
    // You could implement a toast notification here
    console.log('Changes saved successfully');
}

function showSaveErrorIndicator() {
    console.error('Failed to save changes');
}

function aiQuickAction(action) {
    const context = gatherProjectContext();
    const selection = selectedText || '';
    
    let prompt = '';
    switch (action) {
        case 'analyze':
            prompt = `Greindu þetta verkefni og valinn texti: "${selection}". Gefðu innsýn í framfarir, hugsanleg vandamál og ráðleggingar.`;
            break;
        case 'suggest':
            prompt = `Út frá þessu verkefni og völdum texta: "${selection}", stingdu upp á sérstökum umbótum eða hagræðingu.`;
            break;
        case 'risks':
            prompt = `Greindu hugsanlega áhættu og áskoranir fyrir þetta verkefni, sérstaklega með tilliti til: "${selection}".`;
            break;
        case 'breakdown':
            prompt = `Skiptu völdum texta eða verkefnasvæði: "${selection}" niður í smærri, framkvæmanlega verkþætti.`;
            break;
        case 'timeline':
            prompt = `Búðu til tímalínu og áfanga fyrir þetta verkefni, með tilliti til: "${selection}".`;
            break;
    }
    
    sendAIRequest(prompt, context);
}

function sendCustomPrompt() {
    const promptInput = document.getElementById('customPrompt');
    const prompt = promptInput.value.trim();
    
    if (!prompt) return;
    
    const context = gatherProjectContext();
    const selection = selectedText || '';
    
    const fullPrompt = selection 
        ? `${prompt}\n\nValinn texti: "${selection}"`
        : prompt;
    
    sendAIRequest(fullPrompt, context);
    promptInput.value = '';
}

function sendChatMessage() {
    const chatInput = document.getElementById('chatInput');
    const prompt = chatInput.value.trim();
    
    if (!prompt) return;
    
    const context = gatherProjectContext();
    const selection = selectedText || '';
    
    const fullPrompt = selection 
        ? `${prompt}\n\nValinn texti: "${selection}"`
        : prompt;
    
    sendAIRequest(fullPrompt, context);
    chatInput.value = '';
}

function gatherProjectContext() {
    return {
        project: currentProject,
        tasks: currentTasks,
        selectedText: selectedText
    };
}

function sendAIRequest(prompt, context) {
    addToAIChatHistory('user', prompt);
    addToAIChatHistory('assistant', '', true); // Loading message
    
    fetch('/?page=api&action=aiChat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            prompt: prompt,
            context: context
        })
    })
    .then(response => response.json())
    .then(data => {
        removeLoadingMessage();
        if (data.success) {
            addToAIChatHistory('assistant', data.response);
        } else {
            addToAIChatHistory('assistant', `Error: ${data.message}`, false, true);
        }
    })
    .catch(error => {
        removeLoadingMessage();
        addToAIChatHistory('assistant', 'Því miður kom upp villa við að vinna úr beiðni þinni.', false, true);
    });
}

function addToAIChatHistory(role, message, isLoading = false, isError = false) {
    const chatHistory = document.getElementById('aiChatHistory');
    
    // Clear welcome message if it exists
    if (chatHistory.querySelector('.text-center.text-muted')) {
        chatHistory.innerHTML = '';
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${role}-message mb-3`;
    
    let content = '';
    if (isLoading) {
        content = `
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                <small class="text-muted">Claude hugsar...</small>
            </div>
        `;
        messageDiv.id = 'loading-message';
    } else {
        const avatarIcon = role === 'user' ? 'bi-person-circle' : 'bi-robot';
        const roleLabel = role === 'user' ? 'Þú' : 'Claude';
        const messageClass = isError ? 'text-danger' : '';
        
        content = `
            <div class="d-flex align-items-start">
                <i class="bi ${avatarIcon} me-2 mt-1"></i>
                <div class="flex-grow-1">
                    <small class="fw-bold">${roleLabel}</small>
                    <div class="message-content ${messageClass}">${message}</div>
                    <small class="text-muted">${new Date().toLocaleTimeString()}</small>
                </div>
            </div>
        `;
    }
    
    messageDiv.innerHTML = content;
    chatHistory.appendChild(messageDiv);
    chatHistory.scrollTop = chatHistory.scrollHeight;
}

function removeLoadingMessage() {
    const loadingMsg = document.getElementById('loading-message');
    if (loadingMsg) {
        loadingMsg.remove();
    }
}

function toggleTaskCompletion(taskId, completed) {
    // Update UI immediately
    const taskElement = document.querySelector(`[data-task-id="${taskId}"]`);
    const titleElement = taskElement?.querySelector('.editable-field');
    const checkbox = taskElement?.querySelector('input[type="checkbox"]');
    
    if (titleElement) {
        if (completed) {
            titleElement.classList.add('text-decoration-line-through', 'opacity-75');
        } else {
            titleElement.classList.remove('text-decoration-line-through', 'opacity-75');
        }
    }
    
    fetch('/?page=api&action=toggleTask', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            taskId: taskId,
            completed: completed
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the task in currentTasks
            const task = currentTasks.find(t => t.id == taskId);
            if (task) {
                task.is_completed = completed;
            }
            
            // Update progress bar
            updateProgressBar();
            
            // Show success feedback
            console.log('Task updated successfully');
        } else {
            // Revert UI changes if API failed
            if (checkbox) checkbox.checked = !completed;
            if (titleElement) {
                if (!completed) {
                    titleElement.classList.add('text-decoration-line-through', 'opacity-75');
                } else {
                    titleElement.classList.remove('text-decoration-line-through', 'opacity-75');
                }
            }
            console.error('Failed to update task:', data.message);
        }
    })
    .catch(error => {
        console.error('Error updating task:', error);
        // Revert UI changes if request failed
        if (checkbox) checkbox.checked = !completed;
        if (titleElement) {
            if (!completed) {
                titleElement.classList.add('text-decoration-line-through', 'opacity-75');
            } else {
                titleElement.classList.remove('text-decoration-line-through', 'opacity-75');
            }
        }
    });
}

function updateProgressBar() {
    if (!currentTasks || currentTasks.length === 0) return;
    
    const completedTasks = currentTasks.filter(t => t.is_completed).length;
    const totalTasks = currentTasks.length;
    const percentage = Math.round((completedTasks / totalTasks) * 100);
    
    // Update progress bar
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = percentage + '%';
    }
    
    // Update progress text (look for the text that shows "X af Y verkþáttum lokið")
    const progressTexts = document.querySelectorAll('small.text-muted');
    progressTexts.forEach(text => {
        if (text.textContent.includes('verkþáttum lokið')) {
            text.textContent = `${completedTasks} af ${totalTasks} verkþáttum lokið`;
        }
    });
}

function askAIAboutTask(taskId) {
    const task = currentTasks.find(t => t.id == taskId);
    if (!task) return;
    
    const prompt = `Greindu þennan verkþátt og gefðu tillögur að umbótum eða áætlunum til að ljúka honum: "${task.title}" - ${task.description}`;
    const context = gatherProjectContext();
    sendAIRequest(prompt, context);
    
    // Show AI panel if hidden
    showAIPanelIfHidden();
}

function addNewTask() {
    if (!editMode) {
        toggleEditMode();
    }
    
    // Add a new task placeholder
    const tasksContainer = document.querySelector('.tasks-container');
    const newTaskHtml = `
        <div class="task-item border rounded p-3 mb-2 selectable-text new-task" data-task-id="new">
            <div class="d-flex align-items-start">
                <input type="checkbox" class="form-check-input me-3 mt-1" disabled>
                <div class="flex-grow-1">
                    <h6 class="mb-1 editable-field" contenteditable="true" data-field="new-task-title" placeholder="Sláðu inn titil verkþáttar...">
                        Nýr verkþáttur
                    </h6>
                    <p class="text-muted small mb-2 editable-field" contenteditable="true" data-field="new-task-desc" placeholder="Sláðu inn lýsingu verkþáttar...">
                        Smelltu til að bæta við lýsingu...
                    </p>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <select class="badge-select priority-select" data-task-id="new">
                            <option value="1">P1</option>
                            <option value="2" selected>P2</option>
                            <option value="3">P3</option>
                            <option value="4">P4</option>
                            <option value="5">P5</option>
                        </select>
                        <span class="text-muted">Ekkert áætlun</span>
                    </div>
                </div>
                <div class="task-actions">
                    <button class="btn btn-sm btn-success" onclick="saveNewTask()">
                        <i class="bi bi-check"></i>
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="cancelNewTask()">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    tasksContainer.insertAdjacentHTML('beforeend', newTaskHtml);
    
    // Focus on the title field
    const titleField = tasksContainer.querySelector('[data-field="new-task-title"]');
    titleField.focus();
    titleField.select();
}

function saveNewTask() {
    const newTaskDiv = document.querySelector('.new-task');
    const title = newTaskDiv.querySelector('[data-field="new-task-title"]').textContent.trim();
    const description = newTaskDiv.querySelector('[data-field="new-task-desc"]').textContent.trim();
    const priority = newTaskDiv.querySelector('.priority-select').value;
    
    if (!title || title === 'Nýr verkþáttur') {
        alert('Vinsamlegast sláðu inn titil verkþáttar');
        return;
    }
    
    fetch('/?page=api&action=createTask', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            projectId: currentProject.id,
            title: title,
            description: description === 'Smelltu til að bæta við lýsingu...' ? '' : description,
            priority: priority
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the new task placeholder
            newTaskDiv.remove();
            // Reload the project to show the new task
            viewProject(currentProject.id);
        } else {
            alert('Villa við að búa til verkþátt: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error creating task:', error);
        alert('Villa við að búa til verkþátt');
    });
}

function cancelNewTask() {
    document.querySelector('.new-task').remove();
}

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Filter functionality
$(document).ready(function() {
    $('.filter-chip').click(function() {
        $('.filter-chip').removeClass('active');
        $(this).addClass('active');
        
        const filter = $(this).data('filter');
        
        $('.filterable-item').each(function() {
            const item = $(this);
            let show = true;
            
            if (filter === 'active') {
                show = item.data('status') === 'active';
            } else if (filter === 'completed') {
                show = item.data('status') === 'completed';
            } else if (filter === 'overdue') {
                show = item.data('overdue') === true;
            } else if (filter === 'high-priority') {
                show = item.data('priority') === 'high' || item.data('priority') === 'urgent';
            }
            
            if (show) {
                item.show();
            } else {
                item.hide();
            }
        });
    });
});
</script>

<style>
/* AI-Enhanced Project Interface Styles */
.editable-field {
    transition: all 0.3s ease;
    border-radius: 4px;
    padding: 2px 4px;
}

.editable-field:hover {
    background-color: #f8f9fa;
    cursor: text;
}

.editable-field.editable-active {
    background-color: #fff3cd;
    border: 1px dashed #ffc107;
    outline: none;
}

.editable-field.editable-active:focus {
    background-color: #ffffff;
    border: 2px solid #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.edit-mode .editable-field::before {
    content: "✏️";
    position: absolute;
    margin-left: -20px;
    margin-top: -2px;
    font-size: 0.8em;
    opacity: 0.5;
}

.selectable-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

.selectable-text::selection {
    background-color: #0d6efd;
    color: white;
}

.selectable-text::-moz-selection {
    background-color: #0d6efd;
    color: white;
}

.chat-message {
    border-radius: 8px;
    padding: 10px;
}

.user-message {
    background-color: #e3f2fd;
    margin-left: 20px;
}

.assistant-message {
    background-color: #f5f5f5;
    margin-right: 20px;
}

.message-content {
    margin: 5px 0;
    line-height: 1.4;
    white-space: pre-wrap;
}

.task-item:hover {
    background-color: #f8f9fa;
}

.task-item.new-task {
    border: 2px dashed #28a745;
    background-color: #d4edda;
}

.priority-select, .status-select {
    border: none;
    background: transparent;
    font-size: 0.875rem;
}

.priority-select:focus, .status-select:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

#aiPanel {
    height: calc(100vh - 120px);
}

/* Clean Chat Messages */
.chat-message {
    margin-bottom: 12px;
    max-width: 80%;
    word-wrap: break-word;
}

.user-message {
    margin-left: auto;
    background: #000;
    color: white;
    padding: 8px 12px;
    border-radius: 16px 16px 4px 16px;
    font-size: 14px;
}

.assistant-message {
    margin-right: auto;
    background: #f5f5f5;
    color: #333;
    padding: 8px 12px;
    border-radius: 16px 16px 16px 4px;
    font-size: 14px;
}

/* Improve the overall layout */
.modal-fullscreen .modal-body {
    height: calc(100vh - 60px); /* Account for header */
}

.modal-fullscreen .row.h-100 {
    height: 100%;
}

.filter-chips {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-chip {
    padding: 4px 12px;
    background: #e9ecef;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.filter-chip:hover {
    background: #dee2e6;
}

.filter-chip.active {
    background: #0d6efd;
    color: white;
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .modal-dialog.modal-fullscreen .modal-body .row {
        flex-direction: column;
    }
    
    #projectMainContent {
        order: 2;
    }
    
    #aiPanel {
        order: 1;
        height: 300px;
        margin-bottom: 1rem;
    }
    
    #aiChatHistory {
        height: 200px;
    }
}

/* Animation for field changes */
@keyframes fieldSaved {
    0% { background-color: #d1ecf1; }
    100% { background-color: transparent; }
}

.field-saved {
    animation: fieldSaved 0.5s ease-out;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>