<?php
/**
 * AI Project Manager - Main Entry Point
 * 
 * This file serves as the main entry point for the AI Meeting Notes & Project Management System.
 * It handles routing and initializes the application.
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration and core files
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

// Initialize the application
App::init();

// Simple routing system
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Parse query string manually to handle ?page= URLs
$queryString = parse_url($requestUri, PHP_URL_QUERY);
parse_str($queryString ?? '', $queryParams);

// Handle different URL patterns
if (isset($queryParams['page']) && !empty($queryParams['page'])) {
    // Query parameter routing: /?page=upload
    $route = $queryParams['page'];
} elseif (strpos($requestUri, '/index.php/') !== false) {
    // index.php/route format: /index.php/upload
    $route = str_replace('/index.php/', '', $requestUri);
    $route = strtok($route, '?');
    $route = trim($route, '/');
} else {
    // Try to parse clean URLs: /upload
    $basePath = dirname($scriptName);
    $route = str_replace($basePath, '', $requestUri);
    $route = strtok($route, '?');
    $route = trim($route, '/');
}

// If route is empty, default to dashboard
if (empty($route) || $route === 'index.php') {
    $route = 'dashboard';
}

// Set current page for navigation highlighting
$currentPage = explode('/', $route)[0];

// Basic routing
try {
    switch ($currentPage) {
        case 'dashboard':
        case '':
            include __DIR__ . '/templates/dashboard/index.php';
            break;
            
        case 'meetings':
            include __DIR__ . '/templates/meetings/index.php';
            break;
            
        case 'projects':
            if (isset($queryParams['action']) && $queryParams['action'] === 'create') {
                include __DIR__ . '/templates/projects/create.php';
            } else {
                include __DIR__ . '/templates/projects/index.php';
            }
            break;
            
        case 'upload':
            include __DIR__ . '/templates/meetings/upload.php';
            break;
            
        case 'meeting-summarizer':
            include __DIR__ . '/templates/meetings/summarizer.php';
            break;
            
        case 'suggestions':
            include __DIR__ . '/templates/meetings/suggestions.php';
            break;
            
        case 'analytics':
            include __DIR__ . '/templates/dashboard/analytics.php';
            break;
            
        case 'api':
            include __DIR__ . '/public/api/index.php';
            break;
            
        case 'setup':
            include __DIR__ . '/templates/setup/index.php';
            break;
            
        case 'settings':
            // Settings page (placeholder)
            $title = 'Stillingar - AI Verkefnastjóri';
            $pageHeader = [
                'title' => 'Stillingar',
                'subtitle' => 'Breyta stillingum kerfisins'
            ];
            ob_start();
            ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-gear-fill text-primary" style="font-size: 4rem;"></i>
                            <h3 class="mt-3">Stillingar</h3>
                            <p class="text-muted">Stillingasíða er í þróun.</p>
                            <a href="<?php echo App::url(); ?>" class="btn btn-primary">
                                <i class="bi bi-house"></i> Til baka á stjórnborð
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $content = ob_get_clean();
            include __DIR__ . '/templates/layout/main.php';
            break;
            
        case 'patterns':
            // AI Patterns page (placeholder)
            $title = 'AI Mynstur - AI Verkefnastjóri';
            $pageHeader = [
                'title' => 'AI Mynstur',
                'subtitle' => 'Stillingar fyrir AI hegðun'
            ];
            ob_start();
            ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-robot text-info" style="font-size: 4rem;"></i>
                            <h3 class="mt-3">AI Mynstur</h3>
                            <p class="text-muted">AI mynstrasíða er í þróun.</p>
                            <a href="<?php echo App::url(); ?>" class="btn btn-primary">
                                <i class="bi bi-house"></i> Til baka á stjórnborð
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $content = ob_get_clean();
            include __DIR__ . '/templates/layout/main.php';
            break;
            
        case 'help':
            // Help page (placeholder)
            $title = 'Hjálp - AI Verkefnastjóri';
            $pageHeader = [
                'title' => 'Hjálp',
                'subtitle' => 'Leiðbeiningar og stuðningur'
            ];
            ob_start();
            ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-question-circle-fill text-success" style="font-size: 4rem;"></i>
                            <h3 class="mt-3">Hjálp</h3>
                            <p class="text-muted">Hjálparsíða er í þróun.</p>
                            <a href="<?php echo App::url(); ?>" class="btn btn-primary">
                                <i class="bi bi-house"></i> Til baka á stjórnborð
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $content = ob_get_clean();
            include __DIR__ . '/templates/layout/main.php';
            break;
            
        case 'profile':
            include __DIR__ . '/templates/profile/index.php';
            break;
            
        default:
            // 404 page
            http_response_code(404);
            $title = '404 - Page Not Found';
            $pageHeader = [
                'title' => '404 - Page Not Found',
                'subtitle' => 'The page you are looking for does not exist.'
            ];
            
            ob_start();
            ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 4rem;"></i>
                            <h3 class="mt-3">Page Not Found</h3>
                            <p class="text-muted">The page you are looking for does not exist.</p>
                            <a href="<?php echo App::url(); ?>" class="btn btn-primary">
                                <i class="bi bi-house"></i> Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $content = ob_get_clean();
            include __DIR__ . '/templates/layout/main.php';
            break;
    }
    
} catch (Exception $e) {
    // Error page
    http_response_code(500);
    $title = 'Error';
    $pageHeader = [
        'title' => 'Application Error',
        'subtitle' => 'An error occurred while processing your request.'
    ];
    
    ob_start();
    ?>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-circle"></i> Error</h5>
                </div>
                <div class="card-body">
                    <p><strong>An error occurred:</strong></p>
                    
                    <?php if (App::config('APP_DEBUG', 'false') === 'true'): ?>
                        <div class="alert alert-warning">
                            <strong>Debug Information:</strong><br>
                            <?php echo App::sanitize($e->getMessage()); ?><br>
                            <small class="text-muted">File: <?php echo $e->getFile(); ?> (Line: <?php echo $e->getLine(); ?>)</small>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Please try again later or contact support if the problem persists.</p>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="<?php echo App::url(); ?>" class="btn btn-primary">
                            <i class="bi bi-house"></i> Go to Dashboard
                        </a>
                        <button onclick="window.history.back()" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Go Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    include __DIR__ . '/templates/layout/main.php';
}