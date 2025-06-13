<!DOCTYPE html>
<html lang="is">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'AI Verkefnastjóri'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link href="<?php echo App::asset('css/app.css'); ?>" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo App::asset('images/favicon.ico'); ?>">
</head>
<body style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 25%, #ffcc02 50%, #7bc043 75%, #00a8cc 100%); background-size: 400% 400%; animation: gradientShift 15s ease infinite; min-height: 100vh; display: flex; flex-direction: column;">
<style>
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Fix dropdown z-index issues with proper positioning */
.navbar {
    z-index: 1050 !important;
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(15px) !important;
    border: none !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
}

.navbar .dropdown-menu {
    z-index: 1051 !important;
}

/* Orange text for navbar consistency */
.navbar .nav-link {
    color: #ff6b35 !important;
    font-weight: 600 !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
}

.navbar .navbar-brand {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
}

/* Lower page header buttons to ensure dropdown appears above them */
main .d-flex.gap-2 {
    z-index: 1 !important;
    position: relative !important;
}

/* Ensure proper flexbox footer positioning */
body {
    display: flex !important;
    flex-direction: column !important;
    min-height: 100vh !important;
}

main {
    flex: 1 !important;
}

/* Override any main styling to ensure consistent footer positioning */
main.container-fluid {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
}

/* Ensure content areas expand to fill available space */
.main-content-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
}
</style>
    <!-- SUPER FUN Navigation 🎨 -->
    <nav class="navbar navbar-expand-lg">
        <div class="container px-5">
            <a class="navbar-brand fw-bold" href="<?php echo App::url(); ?>" style="background: linear-gradient(45deg, #ff6b35, #ffcc02); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 1.5rem; text-shadow: 0 0 20px rgba(255, 107, 53, 0.3);">
                🚀 Verkefnastjóri ✨
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link fw-bold" 
                           href="<?php echo App::url()?>?page=dashboard" 
                           style="color: #ff6b35; transition: all 0.3s ease; padding: 8px 16px; border-radius: 15px; <?php echo ($currentPage ?? '') === 'dashboard' ? 'background: linear-gradient(45deg, #ff6b35, #f7931e); box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4); color: white;' : ''; ?>"
                           onmouseover="this.style.background='linear-gradient(45deg, #ff6b35, #f7931e)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(255, 107, 53, 0.4)'; this.style.color='white'"
                           onmouseout="this.style.background='<?php echo ($currentPage ?? '') === 'dashboard' ? 'linear-gradient(45deg, #ff6b35, #f7931e)' : 'transparent'; ?>'; this.style.transform='translateY(0)'; this.style.boxShadow='<?php echo ($currentPage ?? '') === 'dashboard' ? '0 4px 15px rgba(255, 107, 53, 0.4)' : 'none'; ?>'; this.style.color='<?php echo ($currentPage ?? '') === 'dashboard' ? 'white' : '#ff6b35'; ?>'">
                            🏠 Yfirlitið
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold" 
                           href="<?php echo App::url()?>?page=meetings" 
                           style="color: #ff6b35; transition: all 0.3s ease; padding: 8px 16px; border-radius: 15px; <?php echo ($currentPage ?? '') === 'meetings' ? 'background: linear-gradient(45deg, #f7931e, #ffcc02); box-shadow: 0 4px 15px rgba(247, 147, 30, 0.4); color: white;' : ''; ?>"
                           onmouseover="this.style.background='linear-gradient(45deg, #f7931e, #ffcc02)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(247, 147, 30, 0.4)'; this.style.color='white'"
                           onmouseout="this.style.background='<?php echo ($currentPage ?? '') === 'meetings' ? 'linear-gradient(45deg, #f7931e, #ffcc02)' : 'transparent'; ?>'; this.style.transform='translateY(0)'; this.style.boxShadow='<?php echo ($currentPage ?? '') === 'meetings' ? '0 4px 15px rgba(247, 147, 30, 0.4)' : 'none'; ?>'; this.style.color='<?php echo ($currentPage ?? '') === 'meetings' ? 'white' : '#ff6b35'; ?>'">
                            📝 Fundir
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold" 
                           href="<?php echo App::url()?>?page=projects" 
                           style="color: #ff6b35; transition: all 0.3s ease; padding: 8px 16px; border-radius: 15px; <?php echo ($currentPage ?? '') === 'projects' ? 'background: linear-gradient(45deg, #ffcc02, #7bc043); box-shadow: 0 4px 15px rgba(255, 204, 2, 0.4); color: white;' : ''; ?>"
                           onmouseover="this.style.background='linear-gradient(45deg, #ffcc02, #7bc043)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(255, 204, 2, 0.4)'; this.style.color='white'"
                           onmouseout="this.style.background='<?php echo ($currentPage ?? '') === 'projects' ? 'linear-gradient(45deg, #ffcc02, #7bc043)' : 'transparent'; ?>'; this.style.transform='translateY(0)'; this.style.boxShadow='<?php echo ($currentPage ?? '') === 'projects' ? '0 4px 15px rgba(255, 204, 2, 0.4)' : 'none'; ?>'; this.style.color='<?php echo ($currentPage ?? '') === 'projects' ? 'white' : '#ff6b35'; ?>'">
                            ✨ Verkefni
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold" 
                           href="<?php echo App::url()?>?page=upload" 
                           style="color: #ff6b35; transition: all 0.3s ease; padding: 8px 16px; border-radius: 15px; <?php echo ($currentPage ?? '') === 'upload' ? 'background: linear-gradient(45deg, #7bc043, #00a8cc); box-shadow: 0 4px 15px rgba(123, 192, 67, 0.4); color: white;' : ''; ?>"
                           onmouseover="this.style.background='linear-gradient(45deg, #7bc043, #00a8cc)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(123, 192, 67, 0.4)'; this.style.color='white'"
                           onmouseout="this.style.background='<?php echo ($currentPage ?? '') === 'upload' ? 'linear-gradient(45deg, #7bc043, #00a8cc)' : 'transparent'; ?>'; this.style.transform='translateY(0)'; this.style.boxShadow='<?php echo ($currentPage ?? '') === 'upload' ? '0 4px 15px rgba(123, 192, 67, 0.4)' : 'none'; ?>'; this.style.color='<?php echo ($currentPage ?? '') === 'upload' ? 'white' : '#ff6b35'; ?>'">
                            🚀 Hlaða upp
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link fw-bold" href="#" role="button" data-bs-toggle="dropdown" 
                           style="color: #ff6b35; transition: all 0.3s ease; padding: 8px 16px; border-radius: 15px;"
                           onmouseover="this.style.background='linear-gradient(45deg, #00a8cc, #7bc043)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0, 168, 204, 0.4)'; this.style.color='white'"
                           onmouseout="this.style.background='transparent'; this.style.transform='translateY(0)'; this.style.boxShadow='none'; this.style.color='#ff6b35'">
                            ⚙️ Stillingar
                        </a>
                        <ul class="dropdown-menu border-0 shadow-lg" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; overflow: hidden; margin-top: 8px;">
                            <li><a class="dropdown-item py-3 fw-bold" href="<?php echo App::url()?>?page=profile" 
                                   style="color: #7bc043; transition: all 0.3s ease; border-radius: 10px; margin: 5px;"
                                   onmouseover="this.style.background='linear-gradient(45deg, #7bc043, #00a8cc)'; this.style.color='white'; this.style.transform='translateX(5px)'"
                                   onmouseout="this.style.background='transparent'; this.style.color='#7bc043'; this.style.transform='translateX(0)'">
                                👤 Um mig
                            </a></li>
                            <li><a class="dropdown-item py-3 fw-bold" href="<?php echo App::url()?>?page=settings" 
                                   style="color: #ff6b35; transition: all 0.3s ease; border-radius: 10px; margin: 5px;"
                                   onmouseover="this.style.background='linear-gradient(45deg, #ff6b35, #f7931e)'; this.style.color='white'; this.style.transform='translateX(5px)'"
                                   onmouseout="this.style.background='transparent'; this.style.color='#ff6b35'; this.style.transform='translateX(0)'">
                                🎛️ Kjörstillingar
                            </a></li>
                            <li><a class="dropdown-item py-3 fw-bold" href="<?php echo App::url()?>?page=patterns"
                                   style="color: #f7931e; transition: all 0.3s ease; border-radius: 10px; margin: 5px;"
                                   onmouseover="this.style.background='linear-gradient(45deg, #f7931e, #ffcc02)'; this.style.color='white'; this.style.transform='translateX(5px)'"
                                   onmouseout="this.style.background='transparent'; this.style.color='#f7931e'; this.style.transform='translateX(0)'">
                                🤖 AI Mynstur
                            </a></li>
                            <li><hr class="dropdown-divider" style="border-color: rgba(0,0,0,0.1); margin: 10px 0;"></li>
                            <li><a class="dropdown-item py-3 fw-bold" href="<?php echo App::url()?>?page=help"
                                   style="color: #7bc043; transition: all 0.3s ease; border-radius: 10px; margin: 5px;"
                                   onmouseover="this.style.background='linear-gradient(45deg, #7bc043, #00a8cc)'; this.style.color='white'; this.style.transform='translateX(5px)'"
                                   onmouseout="this.style.background='transparent'; this.style.color='#7bc043'; this.style.transform='translateX(0)'">
                                💡 Hjálp
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid px-4 py-4" style="flex: 1;">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo App::sanitize($_SESSION['flash_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php 
            unset($_SESSION['flash_message'], $_SESSION['flash_type']); 
            ?>
        <?php endif; ?>
        
        <!-- Clean Page Header -->
        <?php if (isset($pageHeader)): ?>
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 fw-normal mb-1 text-dark"><?php echo App::sanitize($pageHeader['title']); ?></h1>
                        <?php if (isset($pageHeader['subtitle'])): ?>
                            <p class="text-muted mb-0"><?php echo App::sanitize($pageHeader['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($pageHeader['actions'])): ?>
                        <div class="d-flex gap-2" style="z-index: 1 !important; position: relative;">
                            <?php foreach ($pageHeader['actions'] as $action): ?>
                                <a href="<?php echo $action['url']; ?>" 
                                   class="btn btn-dark btn-sm px-3" style="z-index: 1 !important; position: relative;">
                                    <?php echo App::sanitize($action['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Page Content -->
        <div class="main-content-wrapper">
            <?php echo $content ?? ''; ?>
        </div>
    </main>

    <!-- Clean Footer -->
    <footer class="bg-white border-top mt-auto py-4" style="border-color: #e0e0e0 !important; margin-top: auto;">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-dark">Verkefnastjóri</h6>
                    <p class="text-muted small mb-0">AI-knúið verkefnastjórnunarkerfi</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted small mb-0">© <?php echo date('Y'); ?></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="d-none">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Hleður...</span>
            </div>
            <p class="mt-3">Vinnsla...</p>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for convenience) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo App::asset('js/app.js'); ?>"></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?php echo App::asset('js/' . $script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline scripts -->
    <?php if (isset($inlineScripts)): ?>
        <script>
            <?php echo $inlineScripts; ?>
        </script>
    <?php endif; ?>
</body>
</html>