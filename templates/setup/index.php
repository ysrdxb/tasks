<?php
/**
 * Setup Page - Initialize the database and application
 */

$title = 'Uppsetning - AI Verkefnastjóri';
$currentPage = 'setup';

// Handle setup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_action'])) {
    $setupAction = $_POST['setup_action'];
    $results = [];
    
    try {
        if ($setupAction === 'migrate') {
            // Run database migrations
            require_once __DIR__ . '/../../database/migrate.php';
            
            ob_start();
            $migrator = new Migrator();
            $migrator->run();
            $output = ob_get_clean();
            
            $results[] = [
                'type' => 'success',
                'title' => 'Database Migration',
                'message' => 'Database tables created successfully.',
                'details' => $output
            ];
        }
        
        if ($setupAction === 'seed') {
            // Run database seeding
            require_once __DIR__ . '/../../database/seed.php';
            
            ob_start();
            $seeder = new Seeder();
            $seeder->run();
            $output = ob_get_clean();
            
            $results[] = [
                'type' => 'success',
                'title' => 'Database Seeding',
                'message' => 'Sample data added successfully.',
                'details' => $output
            ];
        }
        
        if ($setupAction === 'test_anthropic') {
            // Test Anthropic API connection
            require_once __DIR__ . '/../../src/Services/AnthropicService.php';
            
            $anthropicService = new AnthropicService();
            $testResult = $anthropicService->testConnection();
            
            if ($testResult['status'] === 'ok') {
                $results[] = [
                    'type' => 'success',
                    'title' => 'Anthropic API Test',
                    'message' => 'API connection successful.',
                    'details' => $testResult['message'] ?? ''
                ];
            } else {
                $results[] = [
                    'type' => 'danger',
                    'title' => 'Anthropic API Test',
                    'message' => 'API connection failed: ' . $testResult['message'],
                    'details' => ''
                ];
            }
        }
        
    } catch (Exception $e) {
        $results[] = [
            'type' => 'danger',
            'title' => 'Setup Error',
            'message' => $e->getMessage(),
            'details' => ''
        ];
    }
}

// Check current setup status
$setupStatus = [
    'database' => false,
    'anthropic' => false,
    'sample_data' => false
];

try {
    // Check database connection
    $db = Database::getInstance();
    $setupStatus['database'] = true;
    
    // Check if tables exist
    $tables = $db->select("SHOW TABLES");
    $setupStatus['tables'] = count($tables) > 0;
    
    // Check if sample data exists
    if ($setupStatus['tables']) {
        $projects = $db->select("SELECT COUNT(*) as count FROM projects");
        $setupStatus['sample_data'] = $projects[0]['count'] > 0;
    }
    
    // Check Anthropic API
    if (AnthropicConfig::isConfigured()) {
        $setupStatus['anthropic'] = true;
    }
    
} catch (Exception $e) {
    // Database not accessible
}

$pageHeader = [
    'title' => 'Uppsetning forrits',
    'subtitle' => 'Frumstilla AI Verkefnastjórann þínn'
];

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        
        <?php if (isset($results)): ?>
            <?php foreach ($results as $result): ?>
                <div class="alert alert-<?php echo $result['type']; ?> alert-dismissible fade show">
                    <h5 class="alert-heading"><?php echo App::sanitize($result['title']); ?></h5>
                    <p><?php echo App::sanitize($result['message']); ?></p>
                    <?php if (!empty($result['details'])): ?>
                        <hr>
                        <pre class="mb-0" style="font-size: 0.875rem;"><?php echo App::sanitize($result['details']); ?></pre>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Modern Setup Status -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 32px;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #f3f4f6; background: #fafafa;">
                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 20px; height: 20px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Staða uppsetningar
                </h3>
            </div>
            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px;">
                    <div style="text-align: center; padding: 24px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb;">
                        <div style="display: inline-block; padding: 16px; border-radius: 12px; margin-bottom: 16px; <?php echo $setupStatus['database'] ? 'background: #dcfce7; color: #166534;' : 'background: #fef2f2; color: #991b1b;'; ?>">
                            <svg style="width: 32px; height: 32px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                        </div>
                        <h6 style="color: #374151; font-weight: 600; margin-bottom: 8px;">Gagnagrunns tenging</h6>
                        <span style="font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; font-weight: 500; <?php echo $setupStatus['database'] ? 'background: #dcfce7; color: #166534;' : 'background: #fef2f2; color: #991b1b;'; ?>">
                            <?php echo $setupStatus['database'] ? 'Tengt' : 'Ekki tengt'; ?>
                        </span>
                    </div>
                    
                    <div style="text-align: center; padding: 24px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb;">
                        <div style="display: inline-block; padding: 16px; border-radius: 12px; margin-bottom: 16px; <?php echo ($setupStatus['tables'] ?? false) ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;'; ?>">
                            <svg style="width: 32px; height: 32px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0V7a2 2 0 012-2h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h6 style="color: #374151; font-weight: 600; margin-bottom: 8px;">Gagnagrunnstöflur</h6>
                        <span style="font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; font-weight: 500; <?php echo ($setupStatus['tables'] ?? false) ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;'; ?>">
                            <?php echo ($setupStatus['tables'] ?? false) ? 'Búnar til' : 'Ekki búnar til'; ?>
                        </span>
                    </div>
                    
                    <div style="text-align: center; padding: 24px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb;">
                        <div style="display: inline-block; padding: 16px; border-radius: 12px; margin-bottom: 16px; <?php echo $setupStatus['anthropic'] ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;'; ?>">
                            <svg style="width: 32px; height: 32px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h6 style="color: #374151; font-weight: 600; margin-bottom: 8px;">Anthropic API</h6>
                        <span style="font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; font-weight: 500; <?php echo $setupStatus['anthropic'] ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;'; ?>">
                            <?php echo $setupStatus['anthropic'] ? 'Stilltur' : 'Ekki stilltur'; ?>
                        </span>
                    </div>
                    
                    <div style="text-align: center; padding: 24px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb;">
                        <div style="display: inline-block; padding: 16px; border-radius: 12px; margin-bottom: 16px; <?php echo $setupStatus['sample_data'] ? 'background: #dcfce7; color: #166534;' : 'background: #f3f4f6; color: #6b7280;'; ?>">
                            <svg style="width: 32px; height: 32px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <h6 style="color: #374151; font-weight: 600; margin-bottom: 8px;">Prófugogn</h6>
                        <span style="font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; font-weight: 500; <?php echo $setupStatus['sample_data'] ? 'background: #dcfce7; color: #166534;' : 'background: #f3f4f6; color: #6b7280;'; ?>">
                            <?php echo $setupStatus['sample_data'] ? 'Hlaðið' : 'Ekki hlaðið'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modern Setup Actions -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 32px;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #f3f4f6; background: #fafafa;">
                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 20px; height: 20px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M19 10a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Uppsetningaraðgerðir
                </h3>
            </div>
            <div style="padding: 24px;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6><i class="bi bi-database"></i> Database Migration</h6>
                                <p class="card-text">Create the required database tables and structure.</p>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="setup_action" value="migrate">
                                    <button type="submit" class="btn btn-primary" 
                                            <?php echo ($setupStatus['tables'] ?? false) ? 'disabled' : ''; ?>>
                                        <i class="bi bi-play"></i> Run Migration
                                    </button>
                                </form>
                                <?php if ($setupStatus['tables'] ?? false): ?>
                                    <span class="text-success ms-2">
                                        <i class="bi bi-check-circle"></i> Already completed
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6><i class="bi bi-collection"></i> Sample Data</h6>
                                <p class="card-text">Load sample projects, tasks, and meetings for testing.</p>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="setup_action" value="seed">
                                    <button type="submit" class="btn btn-outline-primary"
                                            <?php echo !($setupStatus['tables'] ?? false) ? 'disabled' : ''; ?>>
                                        <i class="bi bi-play"></i> Load Sample Data
                                    </button>
                                </form>
                                <?php if ($setupStatus['sample_data']): ?>
                                    <span class="text-success ms-2">
                                        <i class="bi bi-check-circle"></i> Already loaded
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6><i class="bi bi-robot"></i> Test Anthropic API</h6>
                                <p class="card-text">Verify that the AI service is properly configured.</p>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="setup_action" value="test_anthropic">
                                    <button type="submit" class="btn btn-outline-info">
                                        <i class="bi bi-play"></i> Test API Connection
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6><i class="bi bi-house"></i> Complete Setup</h6>
                                <p class="card-text">Go to the main dashboard when setup is complete.</p>
                                <a href="<?php echo App::url(); ?>" class="btn btn-success">
                                    <i class="bi bi-arrow-right"></i> Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configuration Help -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-question-circle"></i> Configuration Help</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Database Configuration</h6>
                        <p>Make sure your <code>.env</code> file has the correct database settings:</p>
                        <pre class="bg-light p-2 rounded">
DB_HOST=localhost
DB_NAME=tasks
DB_USER=root
DB_PASS=</pre>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Anthropic API Configuration</h6>
                        <p>Add your Anthropic API key to the <code>.env</code> file:</p>
                        <pre class="bg-light p-2 rounded">
ANTHROPIC_API_KEY=your_api_key_here
ANTHROPIC_MODEL=claude-3-sonnet-20240229</pre>
                        <small class="text-muted">
                            Get your API key from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a>
                        </small>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-12">
                        <h6>MAMP Database Setup</h6>
                        <ol>
                            <li>Open MAMP and start the servers</li>
                            <li>Open phpMyAdmin (usually at <code>http://localhost:8888/phpMyAdmin</code>)</li>
                            <li>Create a new database called <code>tasks</code></li>
                            <li>Update your <code>.env</code> file with the correct database settings</li>
                            <li>Run the database migration above</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>