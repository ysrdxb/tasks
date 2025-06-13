<?php
/**
 * Dashboard - Main dashboard page
 */

require_once __DIR__ . '/../../src/Models/Project.php';
require_once __DIR__ . '/../../src/Models/Task.php';
require_once __DIR__ . '/../../src/Models/Meeting.php';

try {
    $projectModel = new Project();
    $taskModel = new Task();
    $meetingModel = new Meeting();
    
    // Get dashboard data
    $dashboardData = $projectModel->getDashboardData();
    $recentProjects = $projectModel->findAll([], 'created_at DESC', 3);
    $upcomingTasks = $taskModel->getUpcoming(7);
    $overdueTasks = $taskModel->getOverdue();
    $recentMeetings = $meetingModel->getRecent(3);
    
    // Get enhanced meeting statistics
    $meetingStats = $meetingModel->getMeetingStats();
    $conversationMeetings = $meetingModel->count(['input_type' => 'conversation']);
    $meetingsWithAnalysis = $meetingModel->countWithAnalysis();
    $totalMeetings = $meetingModel->count();
    $completedMeetings = $meetingModel->count(['processing_status' => 'completed']);
    
} catch (Exception $e) {
    // If database is not set up, show setup page
    $showSetupButton = true;
    $dashboardData = [
        'total_projects' => 0,
        'active_projects' => 0,
        'completed_projects' => 0,
        'overdue_projects' => 0,
        'avg_completion' => 0
    ];
    $meetingStats = [
        'total_meetings' => 0,
        'completed_meetings' => 0,
        'conversation_meetings' => 0,
        'meetings_with_analysis' => 0
    ];
    $totalMeetings = 0;
    $completedMeetings = 0;
    $recentProjects = [];
    $upcomingTasks = [];
    $overdueTasks = [];
    $recentMeetings = [];
    $conversationMeetings = 0;
    $meetingsWithAnalysis = 0;
}

// Page configuration
$title = 'Stjórnborð - AI Verkefnastjóri';
$currentPage = 'dashboard';
$pageHeader = [
    'title' => 'Stjórnborð',
    'subtitle' => 'Yfirlit yfir verkefni þín og fundir',
    'actions' => [
        [
            'label' => 'Samtals-fundur',
            'url' => '/?page=meeting-summarizer',
            'type' => 'primary',
            'icon' => 'chat'
        ],
        [
            'label' => 'Allir fundir',
            'url' => '/?page=meetings',
            'type' => 'secondary',
            'icon' => 'calendar'
        ],
        [
            'label' => 'Hlaða upp minnismiðum',
            'url' => '/?page=upload',
            'type' => 'secondary',
            'icon' => 'cloud-upload'
        ]
    ]
];

ob_start();
?>

<style>
/* Clean, modern dashboard styling */
.dashboard-container {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    padding-right: 8px;
}

.dashboard-container::-webkit-scrollbar {
    width: 6px;
}

.dashboard-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.dashboard-container::-webkit-scrollbar-thumb {
    background: rgba(255, 107, 53, 0.5);
    border-radius: 3px;
}

.dashboard-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 107, 53, 0.7);
}

.stat-card {
    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
    border: none;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #ff6b35;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
    margin-top: 4px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.content-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    height: 280px;
    display: flex;
    flex-direction: column;
}

.content-header {
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    color: white;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 0.95rem;
}

.content-body {
    flex: 1;
    overflow-y: auto;
    padding: 0;
}

.content-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background-color 0.2s ease;
}

.content-item:hover {
    background-color: #f9fafb;
}

.content-item:last-child {
    border-bottom: none;
}

.item-title {
    font-weight: 500;
    color: #111827;
    margin: 0 0 2px 0;
    font-size: 0.875rem;
}

.item-meta {
    font-size: 0.75rem;
    color: #6b7280;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-completed { background: #dcfce7; color: #166534; }
.status-active { background: #dbeafe; color: #1e40af; }
.status-pending { background: #fef3c7; color: #92400e; }

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 200px;
    color: #9ca3af;
    text-align: center;
}

.empty-state svg {
    width: 32px;
    height: 32px;
    margin-bottom: 8px;
    opacity: 0.6;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
</style>

<?php if (isset($showSetupButton)): ?>
    <div style="background: #fffbeb; border: 1px solid #fed7aa; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
        <h4 style="color: #92400e; margin-bottom: 8px; font-size: 1.125rem; font-weight: 600;">Uppsetning nauðsynleg</h4>
        <p style="color: #b45309; margin-bottom: 16px;">Gagnagrunnurinn hefur ekki verið settur upp ennþá.</p>
        <a href="<?php echo App::url('setup'); ?>" style="background: #d97706; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500;">
            Keyra uppsetningu
        </a>
    </div>
<?php endif; ?>

<div class="dashboard-container">
    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="stat-card">
            <div>
                <div class="stat-number"><?php echo $meetingStats['total_meetings'] ?? 0; ?></div>
                <div class="stat-label">Fundir</div>
            </div>
            <div class="stat-icon" style="background: linear-gradient(45deg, #ff6b35, #f7931e);">
                📝
            </div>
        </div>
        
        <div class="stat-card">
            <div>
                <div class="stat-number"><?php echo $meetingStats['meetings_with_analysis'] ?? 0; ?></div>
                <div class="stat-label">AI Greining</div>
            </div>
            <div class="stat-icon" style="background: linear-gradient(45deg, #f7931e, #ffcc02);">
                🤖
            </div>
        </div>
        
        <div class="stat-card">
            <div>
                <div class="stat-number"><?php echo $dashboardData['total_projects'] ?? 0; ?></div>
                <div class="stat-label">Verkefni</div>
            </div>
            <div class="stat-icon" style="background: linear-gradient(45deg, #ffcc02, #7bc043);">
                🚀
            </div>
        </div>
        
        <div class="stat-card">
            <div>
                <div class="stat-number"><?php echo $dashboardData['active_projects'] ?? 0; ?></div>
                <div class="stat-label">Virk verkefni</div>
            </div>
            <div class="stat-icon" style="background: linear-gradient(45deg, #7bc043, #00a8cc);">
                ⚡
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="row g-3">
        <!-- Recent Meetings -->
        <div class="col-12 col-lg-4">
            <div class="content-card">
                <div class="content-header">
                    📅 Nýlegir fundir
                </div>
                <div class="content-body">
                    <?php if (empty($recentMeetings)): ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p style="margin: 0; font-size: 0.875rem;">Engir fundir ennþá</p>
                            <a href="/?page=meeting-summarizer" style="color: #ff6b35; text-decoration: none; font-size: 0.8rem; margin-top: 8px;">Búa til fund →</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentMeetings as $meeting): ?>
                            <div class="content-item" style="cursor: pointer;" onclick="window.location.href='/?page=meetings'">
                                <div>
                                    <div class="item-title"><?php echo App::sanitize($meeting['title']); ?></div>
                                    <div class="item-meta"><?php echo date('j. M Y', strtotime($meeting['date'])); ?> • <?php echo ucfirst($meeting['input_type']); ?></div>
                                </div>
                                <span class="status-badge <?php echo $meeting['processing_status'] === 'completed' ? 'status-completed' : ($meeting['processing_status'] === 'processing' ? 'status-active' : 'status-pending'); ?>">
                                    <?php echo $meeting['processing_status'] === 'completed' ? '✓' : ($meeting['processing_status'] === 'processing' ? '⏳' : '⏸️'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="col-12 col-lg-4">
            <div class="content-card">
                <div class="content-header">
                    🚀 Nýleg verkefni
                </div>
                <div class="content-body">
                    <?php if (empty($recentProjects)): ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <p style="margin: 0; font-size: 0.875rem;">Engin verkefni ennþá</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentProjects as $project): ?>
                            <div class="content-item">
                                <div>
                                    <div class="item-title"><?php echo App::sanitize($project['name']); ?></div>
                                    <div class="item-meta"><?php echo date('j. M Y', strtotime($project['created_at'])); ?></div>
                                </div>
                                <span class="status-badge <?php echo $project['status'] === 'completed' ? 'status-completed' : 'status-active'; ?>">
                                    <?php echo $project['status'] === 'completed' ? 'Lokið' : 'Virkt'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Tasks -->
        <div class="col-12 col-lg-4">
            <div class="content-card">
                <div class="content-header">
                    📋 Verkþættir í viku
                </div>
                <div class="content-body">
                    <?php if (empty($upcomingTasks)): ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p style="margin: 0; font-size: 0.875rem;">Engir verkþættir næstu daga</p>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($upcomingTasks, 0, 6) as $task): ?>
                            <div class="content-item">
                                <div>
                                    <div class="item-title"><?php echo App::sanitize($task['title']); ?></div>
                                    <div class="item-meta"><?php echo App::sanitize($task['project_name'] ?? ''); ?></div>
                                </div>
                                <div class="item-meta"><?php echo $task['deadline'] ? date('j. M', strtotime($task['deadline'])) : ''; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>