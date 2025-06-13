<?php
/**
 * Analytics Page
 */

$title = 'Analytics - AI Project Manager';
$currentPage = 'analytics';
$pageHeader = [
    'title' => 'Analytics',
    'subtitle' => 'Project performance and productivity insights'
];

try {
    require_once __DIR__ . '/../../src/Models/Project.php';
    require_once __DIR__ . '/../../src/Models/Task.php';
    require_once __DIR__ . '/../../src/Models/Meeting.php';
    
    $projectModel = new Project();
    $taskModel = new Task();
    $meetingModel = new Meeting();
    
    $projectStats = $projectModel->getDashboardData();
    $taskStats = $taskModel->getTaskStatistics();
    $meetingStats = $meetingModel->getStatistics();
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $projectStats = $taskStats = $meetingStats = [];
}

ob_start();
?>

<?php if (isset($error)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        Unable to load analytics: <?php echo App::sanitize($error); ?>
        <a href="<?php echo App::url('setup'); ?>" class="alert-link">Run setup</a> to initialize the database.
    </div>
<?php endif; ?>

<div class="row">
    
    <!-- Project Analytics -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-kanban"></i> Project Analytics</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="projectStatusChart"></canvas>
                </div>
                
                <div class="row mt-3">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-primary"><?php echo $projectStats['total_projects'] ?? 0; ?></h4>
                            <small class="text-muted">Total Projects</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-success"><?php echo round($projectStats['avg_completion'] ?? 0, 1); ?>%</h4>
                            <small class="text-muted">Avg Completion</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Task Analytics -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-task"></i> Task Analytics</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="taskStatusChart"></canvas>
                </div>
                
                <div class="row mt-3">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-info"><?php echo $taskStats['total_tasks'] ?? 0; ?></h4>
                            <small class="text-muted">Total Tasks</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-warning"><?php echo $taskStats['overdue_tasks'] ?? 0; ?></h4>
                            <small class="text-muted">Overdue</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<div class="row">
    
    <!-- Time Tracking -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock"></i> Time Tracking</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="timeTrackingChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Key Metrics -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-speedometer2"></i> Key Metrics</h5>
            </div>
            <div class="card-body">
                <div class="metric-item mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Project Success Rate</span>
                        <strong class="text-success">
                            <?php 
                            $total = $projectStats['total_projects'] ?? 0;
                            $completed = $projectStats['completed_projects'] ?? 0;
                            echo $total > 0 ? round(($completed / $total) * 100, 1) : 0;
                            ?>%
                        </strong>
                    </div>
                    <div class="progress mt-1" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $total > 0 ? ($completed / $total) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="metric-item mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Time Estimation Accuracy</span>
                        <strong class="text-info">
                            <?php echo round($taskStats['avg_time_accuracy'] ?? 0, 1); ?>%
                        </strong>
                    </div>
                    <div class="progress mt-1" style="height: 4px;">
                        <div class="progress-bar bg-info" style="width: <?php echo min(100, $taskStats['avg_time_accuracy'] ?? 0); ?>%"></div>
                    </div>
                </div>
                
                <div class="metric-item mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Meeting Processing Rate</span>
                        <strong class="text-primary">
                            <?php 
                            $totalMeetings = $meetingStats['total_meetings'] ?? 0;
                            $completedMeetings = $meetingStats['completed_meetings'] ?? 0;
                            echo $totalMeetings > 0 ? round(($completedMeetings / $totalMeetings) * 100, 1) : 0;
                            ?>%
                        </strong>
                    </div>
                    <div class="progress mt-1" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: <?php echo $totalMeetings > 0 ? ($completedMeetings / $totalMeetings) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <h5><?php echo $projectStats['total_estimated_hours'] ?? 0; ?>h</h5>
                    <small class="text-muted">Total Estimated Hours</small>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Project Status Chart
    const projectCtx = document.getElementById('projectStatusChart').getContext('2d');
    new Chart(projectCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Completed', 'On Hold', 'Planning'],
            datasets: [{
                data: [
                    <?php echo $projectStats['active_projects'] ?? 0; ?>,
                    <?php echo $projectStats['completed_projects'] ?? 0; ?>,
                    <?php echo $projectStats['on_hold_projects'] ?? 0; ?>,
                    <?php echo ($projectStats['total_projects'] ?? 0) - ($projectStats['active_projects'] ?? 0) - ($projectStats['completed_projects'] ?? 0) - ($projectStats['on_hold_projects'] ?? 0); ?>
                ],
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Task Status Chart
    const taskCtx = document.getElementById('taskStatusChart').getContext('2d');
    new Chart(taskCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending', 'Overdue'],
            datasets: [{
                data: [
                    <?php echo $taskStats['completed_tasks'] ?? 0; ?>,
                    <?php echo $taskStats['pending_tasks'] ?? 0; ?>,
                    <?php echo $taskStats['overdue_tasks'] ?? 0; ?>
                ],
                backgroundColor: ['#198754', '#0dcaf0', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Time Tracking Chart
    const timeCtx = document.getElementById('timeTrackingChart').getContext('2d');
    new Chart(timeCtx, {
        type: 'bar',
        data: {
            labels: ['Estimated', 'Actual'],
            datasets: [{
                label: 'Hours',
                data: [
                    <?php echo round(($taskStats['total_estimated_minutes'] ?? 0) / 60, 1); ?>,
                    <?php echo round(($taskStats['total_actual_minutes'] ?? 0) / 60, 1); ?>
                ],
                backgroundColor: ['#0d6efd', '#198754']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Hours'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>