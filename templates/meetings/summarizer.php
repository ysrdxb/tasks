<?php
/**
 * Meeting Summarizer - Conversational meeting summary builder
 */

$title = 'Fundarsamantekt - AI Verkefnastjóri';
$currentPage = 'meetings';
$pageHeader = [
    'title' => 'Fundarsamantekt',
    'subtitle' => 'Búðu til ítarlega fundarsamantekt í gegnum samtal við Claude',
    'actions' => [
        [
            'label' => 'Nýr fundur',
            'url' => '<?php echo App::url()?>?page=meeting-summarizer&restart=1'
        ],
        [
            'label' => 'Til baka á fundi',
            'url' => '<?php echo App::url()?>?page=meetings'
        ]
    ]
];

// Handle restart parameter
if (isset($_GET['restart']) && $_GET['restart'] == '1') {
    unset($_SESSION['summarizer_step']);
    unset($_SESSION['summarizer_conversation']);
    unset($_SESSION['summarizer_meeting_points']);
    unset($_SESSION['summarizer_summary']);
    // Redirect to clean URL
    header('Location: <?php echo App::url()?>?page=meeting-summarizer');
    exit;
}

// Handle conversation flow
$conversationStep = $_SESSION['summarizer_step'] ?? 'start';
$conversation = $_SESSION['summarizer_conversation'] ?? [];
$meetingPoints = $_SESSION['summarizer_meeting_points'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'start_conversation') {
        // Initialize conversation
        $_SESSION['summarizer_meeting_points'] = App::sanitize($_POST['meeting_points']);
        $_SESSION['summarizer_step'] = 'conversation';
        $_SESSION['summarizer_conversation'] = [];
        
        try {
            require_once __DIR__ . '/../../src/Services/AnthropicService.php';
            $anthropicService = new AnthropicService();
            
            // Build context with user profile
            $userProfile = $_SESSION['user_profile'] ?? [];
            $profileContext = "";
            
            if (!empty($userProfile)) {
                $profileContext = "\n\nSAMHENGI UM NOTANDA:\n";
                if (!empty($userProfile['name'])) $profileContext .= "Nafn: " . $userProfile['name'] . "\n";
                if (!empty($userProfile['role'])) $profileContext .= "Hlutverk: " . $userProfile['role'] . "\n";
                if (!empty($userProfile['company'])) $profileContext .= "Fyrirtæki: " . $userProfile['company'] . "\n";
                if (!empty($userProfile['industry'])) $profileContext .= "Svið: " . $userProfile['industry'] . "\n";
                if (!empty($userProfile['team_size'])) $profileContext .= "Teymisstærð: " . $userProfile['team_size'] . "\n";
                if (!empty($userProfile['responsibilities'])) $profileContext .= "Ábyrgðarsvið: " . $userProfile['responsibilities'] . "\n";
                if (!empty($userProfile['meeting_types'])) $profileContext .= "Tegundir funda: " . $userProfile['meeting_types'] . "\n";
                if (!empty($userProfile['work_style'])) $profileContext .= "Vinnustíll: " . $userProfile['work_style'] . "\n";
                if (!empty($userProfile['priorities'])) $profileContext .= "Forgangsröðun: " . $userProfile['priorities'] . "\n";
                if (!empty($userProfile['context_notes'])) $profileContext .= "Sérstakar athugasemdir: " . $userProfile['context_notes'] . "\n";
            }
            
            // Get first question from Claude
            $prompt = "Þú ert aðstoðarmaður við að búa til fundarsamantektir fyrir þennan notanda.

FUNDARUPPLÝSINGAR:
" . $_SESSION['summarizer_meeting_points'] . "
" . $profileContext . "

Þitt hlutverk er að spyrja spurninga til að fá betri samhengi og geta búið til ítarlega samantekt sem hentar þessum notanda og hans vinnuumhverfi.

Bygðu á bakgrunni notandans í spurningum þínum. Byrjaðu með að kveðja notandann og spyrja þinnar fyrstu spurningu til að skilja betur hvað gerðist í fundinum. Einbeittu þér að efninu, ekki þátttakendum.

Vertu vingjarnlegur og hjálplegur. Svaraðu AÐEINS með þinni spurningu - ekki útskýra ferli eða annað.";

            $claudeResponse = $anthropicService->chat($prompt);
            
            $conversation = [
                [
                    'type' => 'claude',
                    'message' => $claudeResponse,
                    'timestamp' => time()
                ]
            ];
            $_SESSION['summarizer_conversation'] = $conversation;
            $conversationStep = 'conversation';
            
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Villa við tengingu við AI þjónustu: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        
    } elseif ($action === 'send_message_ajax') {
        // Handle AJAX conversation
        $userMessage = App::sanitize($_POST['message'] ?? '');
        $conversation = $_SESSION['summarizer_conversation'] ?? [];
        
        $conversation[] = [
            'type' => 'user',
            'message' => $userMessage,
            'timestamp' => time()
        ];
        
        try {
            require_once __DIR__ . '/../../src/Services/AnthropicService.php';
            $anthropicService = new AnthropicService();
            
            // Build conversation context for Claude with profile
            $userProfile = $_SESSION['user_profile'] ?? [];
            $profileContext = "";
            
            if (!empty($userProfile)) {
                $profileContext = "\n\nSAMHENGI UM NOTANDA:\n";
                if (!empty($userProfile['name'])) $profileContext .= "Nafn: " . $userProfile['name'] . "\n";
                if (!empty($userProfile['role'])) $profileContext .= "Hlutverk: " . $userProfile['role'] . "\n";
                if (!empty($userProfile['company'])) $profileContext .= "Fyrirtæki: " . $userProfile['company'] . "\n";
                if (!empty($userProfile['industry'])) $profileContext .= "Svið: " . $userProfile['industry'] . "\n";
                if (!empty($userProfile['responsibilities'])) $profileContext .= "Ábyrgðarsvið: " . $userProfile['responsibilities'] . "\n";
                if (!empty($userProfile['work_style'])) $profileContext .= "Vinnustíll: " . $userProfile['work_style'] . "\n";
                if (!empty($userProfile['context_notes'])) $profileContext .= "Sérstakar athugasemdir: " . $userProfile['context_notes'] . "\n";
            }
            
            $conversationContext = "FUNDARUPPLÝSINGAR:\n" . $_SESSION['summarizer_meeting_points'] . "\n" . $profileContext . "\n\nSamtal hingað til:\n";
            
            foreach ($conversation as $msg) {
                $role = $msg['type'] === 'user' ? 'Notandi' : 'Þú';
                $conversationContext .= $role . ": " . $msg['message'] . "\n";
            }
            
            // Determine if we should continue asking questions or suggest summary
            $questionCount = count(array_filter($conversation, fn($msg) => $msg['type'] === 'claude'));
            $readyForSummary = false;
            
            if ($questionCount < 6) {
                $prompt = $conversationContext . "\nÞú ert að hjálpa við að búa til ítarlega fundarsamantekt. Byggt á samtalinu hingað til, spyrðu næstu rökréttu spurningu til að fá betri samhengi um fundinn. 

Einbeittu þér að:
- Hvað var rætt
- Hvað var ákveðið
- Hvað voru næstu skref
- Vandamál eða hindranir
- Mikilvægar niðurstöður

Svaraðu AÐEINS með þinni næstu spurningu - ekkert annað.";
            } else {
                $prompt = $conversationContext . "\nÞú hefur fengið nægar upplýsingar. Spyrðu hvort notandinn vilji að þú búir til samantektina núna. Vertu stuttur og hnitmiðaður.";
                $readyForSummary = true;
            }
            
            $claudeResponse = $anthropicService->chat($prompt);
            
            $conversation[] = [
                'type' => 'claude',
                'message' => $claudeResponse,
                'timestamp' => time()
            ];
            
            $_SESSION['summarizer_conversation'] = $conversation;
            
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'response' => $claudeResponse,
                'ready_for_summary' => $readyForSummary
            ]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
        
    } elseif ($action === 'send_message') {
        // Legacy form submission (keeping for fallback)
        $userMessage = App::sanitize($_POST['message'] ?? '');
        $conversation = $_SESSION['summarizer_conversation'];
        
        $conversation[] = [
            'type' => 'user',
            'message' => $userMessage,
            'timestamp' => time()
        ];
        
        try {
            require_once __DIR__ . '/../../src/Services/AnthropicService.php';
            $anthropicService = new AnthropicService();
            
            // Build conversation context for Claude with profile
            $userProfile = $_SESSION['user_profile'] ?? [];
            $profileContext = "";
            
            if (!empty($userProfile)) {
                $profileContext = "\n\nSAMHENGI UM NOTANDA:\n";
                if (!empty($userProfile['name'])) $profileContext .= "Nafn: " . $userProfile['name'] . "\n";
                if (!empty($userProfile['role'])) $profileContext .= "Hlutverk: " . $userProfile['role'] . "\n";
                if (!empty($userProfile['company'])) $profileContext .= "Fyrirtæki: " . $userProfile['company'] . "\n";
                if (!empty($userProfile['industry'])) $profileContext .= "Svið: " . $userProfile['industry'] . "\n";
                if (!empty($userProfile['responsibilities'])) $profileContext .= "Ábyrgðarsvið: " . $userProfile['responsibilities'] . "\n";
                if (!empty($userProfile['work_style'])) $profileContext .= "Vinnustíll: " . $userProfile['work_style'] . "\n";
                if (!empty($userProfile['context_notes'])) $profileContext .= "Sérstakar athugasemdir: " . $userProfile['context_notes'] . "\n";
            }
            
            $conversationContext = "FUNDARUPPLÝSINGAR:\n" . $_SESSION['summarizer_meeting_points'] . "\n" . $profileContext . "\n\nSamtal hingað til:\n";
            
            foreach ($conversation as $msg) {
                $role = $msg['type'] === 'user' ? 'Notandi' : 'Þú';
                $conversationContext .= $role . ": " . $msg['message'] . "\n";
            }
            
            // Determine if we should continue asking questions or suggest summary
            $questionCount = count(array_filter($conversation, fn($msg) => $msg['type'] === 'claude'));
            
            if ($questionCount < 6) {
                $prompt = $conversationContext . "\nÞú ert að hjálpa við að búa til ítarlega fundarsamantekt. Byggt á samtalinu hingað til, spyrðu næstu rökréttu spurningu til að fá betri samhengi um fundinn. 

Einbeittu þér að:
- Hvað var rætt
- Hvað var ákveðið
- Hvað voru næstu skref
- Vandamál eða hindranir
- Mikilvægar niðurstöður

Svaraðu AÐEINS með þinni næstu spurningu - ekkert annað.";
            } else {
                $prompt = $conversationContext . "\nÞú hefur fengið nægar upplýsingar. Spyrðu hvort notandinn vilji að þú búir til samantektina núna. Vertu stuttur og hnitmiðaður.";
                $_SESSION['summarizer_step'] = 'ready_for_summary';
            }
            
            $claudeResponse = $anthropicService->chat($prompt);
            
            $conversation[] = [
                'type' => 'claude',
                'message' => $claudeResponse,
                'timestamp' => time()
            ];
            
            $_SESSION['summarizer_conversation'] = $conversation;
            
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Villa við AI samtal: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        
    } elseif ($action === 'generate_summary') {
        // Generate summary and move to editing phase
        try {
            require_once __DIR__ . '/../../src/Services/AnthropicService.php';
            $anthropicService = new AnthropicService();
            
            // Build full context for summary generation with profile
            $userProfile = $_SESSION['user_profile'] ?? [];
            $profileContext = "";
            
            if (!empty($userProfile)) {
                $profileContext = "\n\nSAMHENGI UM NOTANDA:\n";
                if (!empty($userProfile['name'])) $profileContext .= "Nafn: " . $userProfile['name'] . "\n";
                if (!empty($userProfile['role'])) $profileContext .= "Hlutverk: " . $userProfile['role'] . "\n";
                if (!empty($userProfile['company'])) $profileContext .= "Fyrirtæki: " . $userProfile['company'] . "\n";
                if (!empty($userProfile['industry'])) $profileContext .= "Svið: " . $userProfile['industry'] . "\n";
                if (!empty($userProfile['responsibilities'])) $profileContext .= "Ábyrgðarsvið: " . $userProfile['responsibilities'] . "\n";
                if (!empty($userProfile['work_style'])) $profileContext .= "Vinnustíll: " . $userProfile['work_style'] . "\n";
                if (!empty($userProfile['context_notes'])) $profileContext .= "Sérstakar athugasemdir: " . $userProfile['context_notes'] . "\n";
            }
            
            $conversationHistory = "FUNDARUPPLÝSINGAR:\n" . $_SESSION['summarizer_meeting_points'] . "\n" . $profileContext . "\n\nSamtal við notanda:\n";
            
            foreach ($_SESSION['summarizer_conversation'] as $msg) {
                $role = $msg['type'] === 'user' ? 'Notandi' : 'AI';
                $conversationHistory .= $role . ": " . $msg['message'] . "\n";
            }
            
            $prompt = "Þú ert að búa til ítarlega fundarsamantekt fyrir þennan notanda. Notaðu bakgrunninn hans til að gera samantektina sem mest hagnýta.

$conversationHistory

Búðu til samantekt sem hentar hlutverki og ábyrgðarsviði notandans. Byrjaðu með # Fundarsamantekt sem fyrstu línu.

Notaðu þetta snið og fylltu út raunverulegt efni:

# Fundarsamantekt

**Dagsetning:** " . date('j. F Y') . "

## Yfirlit
Skrifaðu 2-3 setningar um tilgang fundarins og aðalniðurstöður með tilliti til hlutverks notandans.

## Helstu umræðuefni
Búðu til nákvæman lista af því sem var rætt, með áherslu á það sem skiptir notandann máli.

## Ákvarðanir
Listaðu allar ákvarðanir sem voru teknar og hvað þær þýða fyrir notandann.

## Næstu skref
Tilgreindu hvað þarf að gera næst - bæði almennt og sérstaklega fyrir notandann.

## Vandamál og hindranir
Ef einhver komu upp, lýstu þeim og hvernig þau gætu haft áhrif á notandann.

## Athugasemdir
Önnur mikilvæg atriði sem tengjast vinnuumhverfi eða ábyrgðarsviði notandans.

Vertu nákvæmur og notaðu upplýsingarnar úr samtalinu. Gerðu samantektina hagnýta fyrir þennan notanda. Svaraðu AÐEINS með samantektinni á íslensku.";

            $generatedSummary = $anthropicService->chat($prompt);
            $_SESSION['summarizer_summary'] = $generatedSummary;
            $_SESSION['summarizer_step'] = 'editing';
            
            // Redirect to show the editing view
            header('Location: <?php echo App::url()?>?page=meeting-summarizer');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Villa við að búa til samantekt: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            
            // Fallback to basic summary
            $generatedSummary = "# Fundarsamantekt\n\n**Dagsetning:** " . date('j. F Y') . "\n\n## Yfirlit\n\nVilla kom upp við að búa til samantekt með AI. Vinsamlegast breyttu handvirkt.\n\n## Upprunalegar fundarupplýsingar\n\n" . $_SESSION['summarizer_meeting_points'];
            $_SESSION['summarizer_summary'] = $generatedSummary;
        }
        
    } elseif ($action === 'save_summary') {
        // Save summary to database
        error_log("DEBUG: save_summary action called");
        try {
            require_once __DIR__ . '/../../src/Models/Meeting.php';
            $meetingModel = new Meeting();
            
            $summary = $_POST['summary'] ?? ''; // Don't sanitize HTML content
            $title = App::sanitize($_POST['title'] ?? 'Fundarsamantekt');
            
            error_log("DEBUG: Summary length: " . strlen($summary));
            error_log("DEBUG: Title: " . $title);
            error_log("DEBUG: First 200 chars of summary: " . substr($summary, 0, 200));
            error_log("DEBUG: Session conversation exists: " . (isset($_SESSION['summarizer_conversation']) ? 'YES' : 'NO'));
            error_log("DEBUG: Session meeting_points exists: " . (isset($_SESSION['summarizer_meeting_points']) ? 'YES' : 'NO'));
            
            if (empty($summary)) {
                throw new Exception('Samantekt er tóm');
            }
            
            // Create meeting record with summary
            $aiAnalysisData = [
                'summary' => $summary,
                'conversation' => $_SESSION['summarizer_conversation'] ?? [],
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            $meetingData = [
                'title' => $title,
                'original_input' => $_SESSION['summarizer_meeting_points'] ?? '',
                'input_type' => 'conversation',
                'ai_analysis' => json_encode($aiAnalysisData)
            ];
            
            error_log("DEBUG: AI analysis data before JSON encode: " . print_r($aiAnalysisData, true));
            error_log("DEBUG: JSON encoded ai_analysis: " . $meetingData['ai_analysis']);
            error_log("DEBUG: Meeting data to save: " . print_r($meetingData, true));
            
            $meetingId = $meetingModel->createMeeting($meetingData);
            error_log("DEBUG: Created meeting with ID: " . $meetingId);
            $meetingModel->setProcessingStatus($meetingId, Meeting::STATUS_COMPLETED);
            
            $_SESSION['flash_message'] = 'Samantekt vistuð með góðum árangri!';
            $_SESSION['flash_type'] = 'success';
            
            // Clear session data after successful save
            unset($_SESSION['summarizer_step']);
            unset($_SESSION['summarizer_conversation']);
            unset($_SESSION['summarizer_meeting_points']);
            unset($_SESSION['summarizer_summary']);
            
            // Return JSON response for AJAX
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Samantekt vistuð!',
                    'meeting_id' => $meetingId
                ]);
                exit;
            } else {
                // Redirect to meetings list
                App::redirect('<?php echo App::url()?>?page=meetings');
            }
            
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Villa við að vista samantekt: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                exit;
            }
        }
        
    } elseif ($action === 'clear_session') {
        // Clear all session data
        unset($_SESSION['summarizer_step']);
        unset($_SESSION['summarizer_conversation']);
        unset($_SESSION['summarizer_meeting_points']);
        unset($_SESSION['summarizer_summary']);
        echo json_encode(['status' => 'success']);
        exit;
    }
}

$summary = $_SESSION['summarizer_summary'] ?? '';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        
        <?php if ($conversationStep === 'start'): ?>
            
            <!-- Profile Reminder -->
            <?php if (empty($_SESSION['user_profile']['name'])): ?>
            <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #f59e0b; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="font-size: 2rem;">💡</div>
                    <div>
                        <h4 style="margin: 0 0 4px 0; color: #92400e; font-weight: 600;">Betri samantektir með persónulegu sniði</h4>
                        <p style="margin: 0; color: #b45309; font-size: 14px;">
                            Settu upp "Um mig" snið til að fá samantektir sem henta þér betur.
                            <a href="<?php echo App::url()?>?page=profile" style="color: #d97706; text-decoration: underline; font-weight: 500;">Setja upp núna</a>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Initial Meeting Points Input -->
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #f3f4f6; background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: white;">
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        Byrjaðu samtalssamantekt
                    </h3>
                </div>
                <div style="padding: 24px;">
                    <form method="post">
                        <input type="hidden" name="action" value="start_conversation">
                        
                        <div style="margin-bottom: 24px;">
                            <label for="meeting_points" style="display: block; font-weight: 500; color: #374151; margin-bottom: 8px;">
                                Lykilpunktar fundar
                            </label>
                            <textarea id="meeting_points" name="meeting_points" rows="8" required
                                      placeholder="Skrifaðu helstu punkta úr fundinum hér..."
                                      style="width: 100%; padding: 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; resize: vertical;"><?php echo App::sanitize($meetingPoints); ?></textarea>
                            <div style="font-size: 14px; color: #6b7280; margin-top: 8px;">
                                Skrifaðu helstu punkta, hugmyndir, eða athugasemdir frá fundinum. Claude mun spyrja spurninga til að fá betri samhengi.
                            </div>
                        </div>
                        
                        <button type="submit" 
                                style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(255, 107, 53, 0.4)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(255, 107, 53, 0.3)'">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            Byrja samtal með Claude
                        </button>
                    </form>
                </div>
            </div>
            
        <?php elseif ($conversationStep === 'conversation' || $conversationStep === 'ready_for_summary'): ?>
            <!-- Conversation Interface -->
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #f3f4f6; background: linear-gradient(135deg, #f7931e 0%, #ffcc02 100%); color: white;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                            </svg>
                            Samtal við Claude
                        </h3>
                        <button type="button" onclick="startOver()" 
                                style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.3s ease;"
                                onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
                                onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                            🔄 Byrja aftur
                        </button>
                    </div>
                </div>
                
                <!-- Chat Area -->
                <div id="chatArea" style="height: 400px; overflow-y: auto; padding: 20px; background: #f9fafb;">
                    <?php foreach ($conversation as $message): ?>
                        <div style="margin-bottom: 16px; display: flex; <?php echo $message['type'] === 'user' ? 'justify-content: flex-end;' : 'justify-content: flex-start;'; ?>">
                            <div style="max-width: 85%; padding: 12px 16px; border-radius: 12px; word-wrap: break-word; word-break: break-word; white-space: pre-wrap; <?php echo $message['type'] === 'user' ? 'background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; margin-left: auto;' : 'background: white; border: 1px solid #e5e7eb; color: #374151;'; ?>">
                                <?php echo nl2br(App::sanitize($message['message'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Message Input -->
                <?php if ($conversationStep === 'conversation'): ?>
                <div style="padding: 20px; border-top: 1px solid #e5e7eb; background: white;">
                    <form id="messageForm" style="display: flex; gap: 12px; align-items: flex-end;">
                        <textarea id="messageInput" name="message" placeholder="Svaraðu spurningu Claude..." required rows="1"
                               style="flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: none; min-height: 44px; max-height: 120px; overflow-y: auto; font-family: inherit; line-height: 1.4;"></textarea>
                        <button type="submit" id="sendButton"
                                style="background: linear-gradient(45deg, #f7931e, #ffcc02); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; height: 44px;"
                                onmouseover="this.style.transform='translateY(-2px)'"
                                onmouseout="this.style.transform='translateY(0)'">
                            Senda
                        </button>
                    </form>
                </div>
                <?php elseif ($conversationStep === 'ready_for_summary'): ?>
                <div style="padding: 20px; border-top: 1px solid #e5e7eb; background: white; text-align: center;">
                    <form method="post">
                        <input type="hidden" name="action" value="generate_summary">
                        <button type="submit" 
                                style="background: linear-gradient(45deg, #7bc043, #00a8cc); color: white; border: none; padding: 16px 32px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 16px;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(123, 192, 67, 0.4)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(123, 192, 67, 0.3)'">
                            🚀 Búa til samantekt
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <!-- Force Generate Button for Testing -->
                <div style="padding: 20px; border-top: 1px solid #e5e7eb; background: white; text-align: center;">
                    <p style="color: #6b7280; margin-bottom: 16px; font-size: 14px;">
                        Debug: Current step = <?php echo $conversationStep; ?>
                    </p>
                    <form method="post">
                        <input type="hidden" name="action" value="generate_summary">
                        <button type="submit" 
                                style="background: linear-gradient(45deg, #f59e0b, #d97706); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                            🧪 Force Generate Summary (Debug)
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
        <?php elseif ($conversationStep === 'editing'): ?>
            <!-- AI-Assisted Editor Interface -->
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);">
                <!-- Header with AI Controls -->
                <div style="background: linear-gradient(135deg, #7bc043 0%, #00a8cc 100%); color: white; padding: 24px 32px;">
                    <div style="display: flex; justify-content: between; align-items: center;">
                        <div style="flex: 1;">
                            <h3 style="font-size: 1.4rem; font-weight: 700; margin: 0 0 8px 0; display: flex; align-items: center; gap: 12px;">
                                <div style="background: rgba(255, 255, 255, 0.2); padding: 10px; border-radius: 12px;">
                                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </div>
                                AI Ritvinnsla
                            </h3>
                            <p style="margin: 0; opacity: 0.9; font-size: 0.95rem;">Breyttu samantektinni með hjálp gervigreindar</p>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <button type="button" onclick="toggleView()" id="viewToggle"
                                    style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 10px 16px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;"
                                    onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
                                    onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Forskoðun
                            </button>
                            <button type="button" onclick="askAI()" id="aiAssistBtn"
                                    style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 10px 16px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;"
                                    onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
                                    onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                                AI Aðstoð
                            </button>
                        </div>
                    </div>
                </div>
                
                
                <!-- Split Editor Interface -->
                <div id="editorContainer" style="display: flex; height: 600px;">
                    <!-- Editor Pane -->
                    <div style="flex: 1; display: flex; flex-direction: column; border-right: 1px solid #e5e7eb;">
                        <div style="padding: 16px 24px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Markdown Ritill
                        </div>
                        <textarea id="summaryEditor" 
                                  style="flex: 1; border: none; padding: 24px; font-size: 14px; font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace; line-height: 1.6; resize: none; outline: none; background: #fafafa;"
                                  oninput="updatePreview()"><?php echo App::sanitize($summary); ?></textarea>
                    </div>
                    
                    <!-- Preview Pane -->
                    <div id="previewPane" style="flex: 1; display: flex; flex-direction: column;">
                        <div style="padding: 16px 24px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Forskoðun
                            </div>
                            <div id="selectionTools" style="display: none; align-items: center; gap: 8px;">
                                <span id="selectedTextPreview" style="font-size: 12px; color: #6b7280; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                <button id="aiEditButton" onclick="editSelectedText()" 
                                        style="background: linear-gradient(45deg, #7bc043, #00a8cc); color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 4px;"
                                        onmouseover="this.style.transform='translateY(-1px)'"
                                        onmouseout="this.style.transform='translateY(0)'">
                                    <svg style="width: 12px; height: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                    AI Aðstoð
                                </button>
                            </div>
                        </div>
                        <div id="markdownPreview" style="flex: 1; padding: 24px; overflow-y: auto; background: white; line-height: 1.6; color: #374151; user-select: text; cursor: text;"></div>
                    </div>
                </div>
                
                <!-- Action Bar -->
                <div style="background: white; padding: 20px 32px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; gap: 12px;">
                        <button type="button" onclick="exportSummary()" 
                                style="background: linear-gradient(45deg, #00a8cc, #7bc043); color: white; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;"
                                onmouseover="this.style.transform='translateY(-2px)'"
                                onmouseout="this.style.transform='translateY(0)'">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Flytja út
                        </button>
                        <button type="button" onclick="transferToTasks()" 
                                style="background: linear-gradient(45deg, #ffcc02, #7bc043); color: white; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;"
                                onmouseover="this.style.transform='translateY(-2px)'"
                                onmouseout="this.style.transform='translateY(0)'">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            Búa til verkefni
                        </button>
                    </div>
                    
                    <div style="display: flex; gap: 12px;">
                        <button type="button" onclick="startOver()" 
                                style="background: #6b7280; color: white; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;"
                                onmouseover="this.style.background='#4b5563'"
                                onmouseout="this.style.background='#6b7280'">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Byrja aftur
                        </button>
                        <button type="button" onclick="saveSummaryV2()" 
                                style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;"
                                onmouseover="this.style.transform='translateY(-2px)'"
                                onmouseout="this.style.transform='translateY(0)'">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Vista samantekt
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- AI Assistant Modal -->
<div class="modal fade" id="aiAssistModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, #7bc043 0%, #00a8cc 100%); color: white; border: none; padding: 24px 32px;">
                <div style="display: flex; align-items: center; gap: 12px; width: 100%;">
                    <div style="background: rgba(255, 255, 255, 0.2); padding: 10px; border-radius: 12px;">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-weight: 700; font-size: 1.3rem;">Claude AI Aðstoð</h4>
                        <p style="margin: 0; opacity: 0.9; font-size: 0.9rem;">Fáðu hjálp við að breyta samantektinni</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" 
                            style="background: rgba(255, 255, 255, 0.2); border-radius: 10px; padding: 12px; opacity: 1;"></button>
                </div>
            </div>
            <div class="modal-body" style="padding: 32px; background: #f8fafc;">
                <div style="display: flex; gap: 24px; margin-bottom: 24px;">
                    <div style="flex: 1;">
                        <h5 style="color: #374151; font-weight: 600; margin-bottom: 12px;">Hvað viltu gera?</h5>
                        <textarea id="aiPromptModal" placeholder="t.d. 'Gerðu þetta styttra' eða 'Bættu við fleiri smáatriðum'"
                                  style="width: 100%; padding: 16px; border: 1px solid #d1d5db; border-radius: 12px; font-size: 14px; resize: vertical; min-height: 100px; background: white;"></textarea>
                    </div>
                    <div style="flex: 1;">
                        <h5 style="color: #374151; font-weight: 600; margin-bottom: 12px;">Flýtiaðgerðir</h5>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <button onclick="quickAIModal('Gerðu þetta styttra og hnitmiðaðra')" class="quick-ai-modal-btn">✂️ Stytta</button>
                            <button onclick="quickAIModal('Bættu við fleiri smáatriðum')" class="quick-ai-modal-btn">📝 Lengja</button>
                            <button onclick="quickAIModal('Gerðu þetta formlegan í tón')" class="quick-ai-modal-btn">👔 Formlegra</button>
                            <button onclick="quickAIModal('Leiðréttu málfræði og stafsetningu')" class="quick-ai-modal-btn">✅ Leiðrétta</button>
                            <button onclick="quickAIModal('Skipulagðu þetta betur')" class="quick-ai-modal-btn">📋 Skipuleggja</button>
                            <button onclick="quickAIModal('Endurskrifaðu á skýrari hátt')" class="quick-ai-modal-btn">💡 Skýra</button>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; margin-bottom: 24px;">
                    <button onclick="sendAIRequestModal()" id="sendAIBtnModal"
                            style="background: linear-gradient(45deg, #7bc043, #00a8cc); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;"
                            onmouseover="this.style.transform='translateY(-2px)'"
                            onmouseout="this.style.transform='translateY(0)'">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Senda til Claude
                    </button>
                </div>
                
                <!-- AI Response Area -->
                <div id="aiResponseModal" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Text Selection AI Edit Modal -->
<div class="modal fade" id="textSelectionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: white; border: none; padding: 24px 32px;">
                <div style="display: flex; align-items: center; gap: 12px; width: 100%;">
                    <div style="background: rgba(255, 255, 255, 0.2); padding: 10px; border-radius: 12px;">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-weight: 700; font-size: 1.3rem;">Breyta völdum texta</h4>
                        <p style="margin: 0; opacity: 0.9; font-size: 0.9rem;">Claude mun breyta aðeins þeim hluta sem þú valdir</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" 
                            style="background: rgba(255, 255, 255, 0.2); border-radius: 10px; padding: 12px; opacity: 1;"></button>
                </div>
            </div>
            <div class="modal-body" style="padding: 0; background: #f8fafc;">
                
                <!-- Selected text display -->
                <div style="background: white; border-bottom: 1px solid #e5e7eb; padding: 24px 32px;">
                    <h5 style="color: #374151; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                        </svg>
                        Valinn texti
                    </h5>
                    <div id="selectedTextDisplay" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; max-height: 150px; overflow-y: auto; font-family: inherit; line-height: 1.5; color: #374151; white-space: pre-wrap;"></div>
                </div>

                <!-- AI instruction area -->
                <div style="padding: 24px 32px;">
                    <div style="display: flex; gap: 24px; margin-bottom: 24px;">
                        <div style="flex: 1;">
                            <h5 style="color: #374151; font-weight: 600; margin-bottom: 12px;">Hvað á Claude að gera við þennan texta?</h5>
                            <textarea id="selectionAIPrompt" placeholder="t.d. 'Gerðu þetta skýrara', 'Bættu við fleiri upplýsingum', eða 'Styttu þetta'"
                                      style="width: 100%; padding: 16px; border: 1px solid #d1d5db; border-radius: 12px; font-size: 14px; resize: vertical; min-height: 100px; background: white;"></textarea>
                        </div>
                        <div style="flex: 1;">
                            <h5 style="color: #374151; font-weight: 600; margin-bottom: 12px;">Flýtiaðgerðir</h5>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <button onclick="quickSelectionAI('Gerðu þetta skýrara og auðveldara í skilningi')" class="quick-selection-btn">💡 Skýra</button>
                                <button onclick="quickSelectionAI('Styttu þetta og haltu aðeins mikilvægustu atriðunum')" class="quick-selection-btn">✂️ Stytta</button>
                                <button onclick="quickSelectionAI('Bættu við fleiri smáatriðum og útskýringum')" class="quick-selection-btn">📝 Útfæra</button>
                                <button onclick="quickSelectionAI('Gerðu þetta formlegan í tón og orðalagi')" class="quick-selection-btn">👔 Formlegra</button>
                                <button onclick="quickSelectionAI('Leiðréttu málfræði og stafsetningu')" class="quick-selection-btn">✅ Leiðrétta</button>
                                <button onclick="quickSelectionAI('Endurskrifaðu með öðrum orðum')" class="quick-selection-btn">🔄 Endurskrifa</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Send button -->
                    <div style="display: flex; gap: 12px; margin-bottom: 24px;">
                        <button onclick="sendSelectionAIRequest()" id="sendSelectionAIBtn"
                                style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;"
                                onmouseover="this.style.transform='translateY(-2px)'"
                                onmouseout="this.style.transform='translateY(0)'">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Senda til Claude
                        </button>
                    </div>
                    
                    <!-- AI Response Area -->
                    <div id="selectionAIResponse" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Force browser to reload by clearing any cached functions
if (typeof saveSummary !== 'undefined') {
    console.log('Clearing cached saveSummary function');
    delete window.saveSummary;
}

// Auto-scroll chat to bottom
function scrollChatToBottom() {
    const chatArea = document.getElementById('chatArea');
    if (chatArea) {
        chatArea.scrollTop = chatArea.scrollHeight;
    }
}

// Export summary
function exportSummary() {
    const summary = document.getElementById('summaryEditor').value;
    const blob = new Blob([summary], { type: 'text/markdown' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'fundarsamantekt-' + new Date().toISOString().split('T')[0] + '.md';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Transfer to tasks (placeholder)
function transferToTasks() {
    alert('Þessi virkni verður bætt við bráðlega! Samantektin verður flutt í verkefnakerfi.');
}

// Start over
function startOver() {
    if (confirm('Ertu viss um að þú viljir byrja aftur? Þetta mun eyða núverandi samtali og samantekt.')) {
        // Clear session data
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clear_session'
        }).then(response => {
            if (response.ok) {
                // Redirect to fresh page
                window.location.href = '<?php echo App::url()?>?page=meeting-summarizer';
            } else {
                // Fallback - just reload
                window.location.reload();
            }
        }).catch(() => {
            // Fallback - just reload
            window.location.reload();
        });
    }
}

// Save summary V2 - Fixed version
async function saveSummaryV2() {
    console.log('saveSummary function called');
    
    // Get the current content from the editor (this includes all AI edits)
    const markdownContent = document.getElementById('summaryEditor').value;
    
    // Convert markdown to formatted HTML like the preview pane
    const formattedSummary = convertMarkdownToFormattedHtml(markdownContent);
    
    const saveButton = event?.target || document.querySelector('[onclick="saveSummaryV2()"]');
    
    console.log('Markdown content to save:', markdownContent.substring(0, 200) + '...');
    console.log('Markdown length:', markdownContent.length);
    console.log('Formatted summary length:', formattedSummary.length);
    
    if (!markdownContent.trim()) {
        alert('Samantekt er tóm!');
        return;
    }
    
    // Show loading state
    const originalText = saveButton.textContent;
    saveButton.disabled = true;
    saveButton.textContent = 'Vistar...';
    
    try {
        console.log('Sending save request...');
        const response = await fetch('<?php echo App::url()?>?page=meeting-summarizer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_summary&summary=${encodeURIComponent(formattedSummary)}&title=${encodeURIComponent(extractTitleFromSummary(markdownContent))}&ajax=1`
        });
        
        console.log('Response received:', response.status);
        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success) {
            alert('Samantekt vistuð með góðum árangri!');
            // Redirect to meetings list
            window.location.href = '<?php echo App::url()?>?page=meetings';
        } else {
            alert('Villa við að vista samantekt: ' + data.error);
            saveButton.disabled = false;
            saveButton.textContent = originalText;
        }
        
    } catch (error) {
        console.error('Error saving summary:', error);
        alert('Villa við að vista samantekt: ' + error.message);
        saveButton.disabled = false;
        saveButton.textContent = originalText;
    }
}

// Convert markdown to formatted HTML (same as preview pane)
function convertMarkdownToFormattedHtml(markdown) {
    if (!markdown.trim()) return '';
    
    // Same conversion as updatePreview() function
    let html = markdown
        .replace(/^# (.+)/gm, '<h1 style="color: #111827; font-weight: 700; margin: 24px 0 16px 0; font-size: 2rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px;">$1</h1>')
        .replace(/^## (.+)/gm, '<h2 style="color: #374151; font-weight: 600; margin: 20px 0 12px 0; font-size: 1.5rem;">$1</h2>')
        .replace(/^### (.+)/gm, '<h3 style="color: #4b5563; font-weight: 600; margin: 16px 0 8px 0; font-size: 1.25rem;">$1</h3>')
        .replace(/\*\*(.+?)\*\*/g, '<strong style="color: #111827; font-weight: 600;">$1</strong>')
        .replace(/\*(.+?)\*/g, '<em style="color: #6b7280;">$1</em>')
        .replace(/^- (.+)/gm, '<div style="margin: 8px 0; padding-left: 20px; position: relative;"><span style="position: absolute; left: 0; color: #ff6b35; font-weight: bold;">•</span>$1</div>')
        .replace(/\n\n/g, '</p><p style="margin: 16px 0; line-height: 1.7; color: #374151;">')
        .replace(/\n/g, '<br>');
    
    // Wrap in container with proper styling
    return `<div style="line-height: 1.7; color: #374151;"><p style="margin: 16px 0; line-height: 1.7; color: #374151;">${html}</p></div>`;
}

// Extract title from markdown summary
function extractTitleFromSummary(summary) {
    const lines = summary.split('\n');
    for (const line of lines) {
        if (line.startsWith('# ')) {
            return line.substring(2).trim();
        }
    }
    return 'Fundarsamantekt';
}

// Auto-resize textarea function
function autoResizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    scrollChatToBottom();
    
    // Auto-focus message input if we're in conversation mode
    const messageInput = document.querySelector('textarea[name="message"]');
    if (messageInput) {
        messageInput.focus();
        
        // Add auto-resize functionality
        messageInput.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
        
        // Handle Enter key to submit (Shift+Enter for new line)
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('messageForm').dispatchEvent(new Event('submit'));
            }
        });
    }
    
    // Handle conversation form submission with AJAX
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            const chatArea = document.getElementById('chatArea');
            
            const userMessage = messageInput.value.trim();
            if (!userMessage) return;
            
            // Add user message to chat immediately
            addMessageToChat('user', userMessage);
            
            // Clear input and disable form
            messageInput.value = '';
            messageInput.style.height = '44px'; // Reset height
            messageInput.disabled = true;
            sendButton.disabled = true;
            sendButton.textContent = 'Sendir...';
            
            // Scroll to bottom
            scrollChatToBottom();
            
            try {
                // Send message to server
                const response = await fetch('<?php echo App::url()?>?page=meeting-summarizer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message_ajax&message=${encodeURIComponent(userMessage)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Stream Claude's response
                    await streamMessage('claude', data.response);
                    
                    // Check if ready for summary
                    if (data.ready_for_summary) {
                        showSummaryButton();
                    }
                } else {
                    addMessageToChat('claude', 'Villa kom upp: ' + data.error);
                }
                
            } catch (error) {
                console.error('Error:', error);
                addMessageToChat('claude', 'Villa kom upp við að senda skilaboð. Vinsamlegast reyndu aftur.');
            }
            
            // Re-enable form
            messageInput.disabled = false;
            sendButton.disabled = false;
            sendButton.textContent = 'Senda';
            messageInput.focus();
        });
    }
});

// Add message to chat area
function addMessageToChat(type, message) {
    const chatArea = document.getElementById('chatArea');
    const messageDiv = document.createElement('div');
    messageDiv.style.marginBottom = '16px';
    messageDiv.style.display = 'flex';
    messageDiv.style.justifyContent = type === 'user' ? 'flex-end' : 'flex-start';
    
    const bubbleDiv = document.createElement('div');
    bubbleDiv.style.maxWidth = '85%';
    bubbleDiv.style.padding = '12px 16px';
    bubbleDiv.style.borderRadius = '12px';
    bubbleDiv.style.wordWrap = 'break-word';
    bubbleDiv.style.wordBreak = 'break-word';
    bubbleDiv.style.whiteSpace = 'pre-wrap';
    
    if (type === 'user') {
        bubbleDiv.style.background = 'linear-gradient(45deg, #ff6b35, #f7931e)';
        bubbleDiv.style.color = 'white';
        bubbleDiv.style.marginLeft = 'auto';
    } else {
        bubbleDiv.style.background = 'white';
        bubbleDiv.style.border = '1px solid #e5e7eb';
        bubbleDiv.style.color = '#374151';
    }
    
    bubbleDiv.textContent = message;
    messageDiv.appendChild(bubbleDiv);
    chatArea.appendChild(messageDiv);
    
    return bubbleDiv;
}

// Stream message with typing effect
async function streamMessage(type, message) {
    const bubbleDiv = addMessageToChat(type, '');
    const words = message.split(' ');
    
    for (let i = 0; i < words.length; i++) {
        bubbleDiv.textContent = words.slice(0, i + 1).join(' ');
        scrollChatToBottom();
        await new Promise(resolve => setTimeout(resolve, 65)); // 65ms delay between words (30% slower)
    }
}

// Show summary generation button
function showSummaryButton() {
    const chatArea = document.getElementById('chatArea');
    const summaryDiv = document.createElement('div');
    summaryDiv.style.textAlign = 'center';
    summaryDiv.style.padding = '20px';
    summaryDiv.innerHTML = `
        <div style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border: 1px solid #16a34a; border-radius: 12px; padding: 20px; margin: 16px 0;">
            <h4 style="color: #15803d; margin-bottom: 12px;">🎉 Tilbúið!</h4>
            <p style="color: #166534; margin-bottom: 16px;">Claude hefur safnað nægilegum upplýsingum til að búa til samantekt.</p>
            <button onclick="generateSummary()" 
                    style="background: linear-gradient(45deg, #7bc043, #00a8cc); color: white; border: none; padding: 16px 32px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 16px;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(123, 192, 67, 0.4)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(123, 192, 67, 0.3)'">
                🚀 Búa til samantekt
            </button>
        </div>
    `;
    chatArea.appendChild(summaryDiv);
    scrollChatToBottom();
}

// Generate summary
function generateSummary() {
    // Show loading
    const button = event.target;
    button.disabled = true;
    button.textContent = 'Býr til samantekt...';
    
    // Submit form to generate summary
    const form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = '<input type="hidden" name="action" value="generate_summary">';
    document.body.appendChild(form);
    form.submit();
}

// === AI-ASSISTED EDITOR FUNCTIONS ===

// Global variables for text selection
let currentSelection = null;
let currentSelectedText = '';
let selectionRange = null;

// Quick AI button styles
const quickAiBtnStyle = `
    padding: 8px 12px; 
    border: 1px solid #e5e7eb; 
    background: white; 
    border-radius: 8px; 
    font-size: 12px; 
    cursor: pointer; 
    transition: all 0.3s ease; 
    display: flex; 
    align-items: center; 
    gap: 6px;
    font-weight: 500;
    color: #374151;
`;

// === TEXT SELECTION FUNCTIONALITY ===

// Initialize text selection listening
function initializeTextSelection() {
    const preview = document.getElementById('markdownPreview');
    if (!preview) return;
    
    // Listen for text selection in preview pane
    preview.addEventListener('mouseup', handleTextSelection);
    preview.addEventListener('touchend', handleTextSelection);
    
    // Hide selection tools when clicking elsewhere
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#previewPane') && !e.target.closest('#textSelectionModal')) {
            hideSelectionTools();
        }
    });
}

// Handle text selection
function handleTextSelection(e) {
    // Small delay to ensure selection is complete
    setTimeout(() => {
        const selection = window.getSelection();
        const selectedText = selection.toString().trim();
        
        if (selectedText.length > 10) { // Minimum selection length
            console.log('=== TEXT SELECTION DEBUG ===');
            console.log('Selected text from preview:', selectedText);
            
            // Store the plain text without HTML formatting
            currentSelectedText = selectedText;
            currentSelection = selection;
            
            // Try to get the range, but handle cases where it might not exist
            try {
                if (selection.rangeCount > 0) {
                    selectionRange = selection.getRangeAt(0);
                }
            } catch (error) {
                console.warn('Could not get selection range:', error);
                selectionRange = null;
            }
            
            // Immediately try to find this text in the editor for better matching
            const editor = document.getElementById('summaryEditor');
            if (editor) {
                const markdownContent = editor.value;
                console.log('Looking for selected text in markdown...');
                
                // Try to find exact match first
                if (markdownContent.includes(selectedText)) {
                    console.log('✅ Exact match found in markdown');
                } else {
                    // Try to find approximate match
                    const cleanSelected = selectedText.replace(/\s+/g, ' ').trim();
                    const lines = markdownContent.split('\n');
                    let bestMatch = '';
                    let bestMatchScore = 0;
                    
                    for (const line of lines) {
                        const cleanLine = line
                            .replace(/^#+\s*/, '') // Remove headers
                            .replace(/^\*+\s*/, '') // Remove list markers  
                            .replace(/\*\*(.*?)\*\*/g, '$1') // Remove bold
                            .replace(/\*(.*?)\*/g, '$1') // Remove italic
                            .replace(/\s+/g, ' ')
                            .trim();
                        
                        if (cleanLine.length > 5) {
                            // Calculate similarity score
                            const similarity = calculateSimilarity(cleanSelected, cleanLine);
                            if (similarity > bestMatchScore && similarity > 0.7) {
                                bestMatchScore = similarity;
                                bestMatch = line.trim();
                            }
                        }
                    }
                    
                    if (bestMatch) {
                        console.log('✅ Best markdown match found:', bestMatch);
                        console.log('Similarity score:', bestMatchScore);
                        // Store the markdown version for later use
                        window.markdownMatchForSelection = bestMatch;
                    } else {
                        console.log('⚠️ No good markdown match found');
                        window.markdownMatchForSelection = null;
                    }
                }
            }
            
            showSelectionTools(selectedText);
            console.log('Text selection handled, ready for AI editing');
        } else {
            hideSelectionTools();
        }
    }, 100);
}

// Calculate text similarity (simple word-based)
function calculateSimilarity(text1, text2) {
    const words1 = text1.toLowerCase().split(/\s+/);
    const words2 = text2.toLowerCase().split(/\s+/);
    
    const commonWords = words1.filter(word => words2.includes(word));
    const totalWords = Math.max(words1.length, words2.length);
    
    return commonWords.length / totalWords;
}

// Show selection tools
function showSelectionTools(selectedText) {
    const selectionTools = document.getElementById('selectionTools');
    const selectedTextPreview = document.getElementById('selectedTextPreview');
    
    if (selectionTools && selectedTextPreview) {
        selectedTextPreview.textContent = selectedText.length > 50 
            ? selectedText.substring(0, 50) + '...' 
            : selectedText;
        selectionTools.style.display = 'flex';
    }
}

// Hide selection tools
function hideSelectionTools() {
    const selectionTools = document.getElementById('selectionTools');
    if (selectionTools) {
        selectionTools.style.display = 'none';
    }
}

// Edit selected text function
function editSelectedText() {
    if (!currentSelectedText) {
        alert('Enginn texti valinn');
        return;
    }
    
    // Show the selected text in the modal
    const selectedTextDisplay = document.getElementById('selectedTextDisplay');
    const selectionAIPrompt = document.getElementById('selectionAIPrompt');
    
    if (selectedTextDisplay) {
        selectedTextDisplay.textContent = currentSelectedText;
    }
    
    // Clear previous prompt
    if (selectionAIPrompt) {
        selectionAIPrompt.value = '';
    }
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('textSelectionModal'));
    modal.show();
    
    // Focus on prompt after modal is shown
    setTimeout(() => {
        if (selectionAIPrompt) {
            selectionAIPrompt.focus();
        }
    }, 300);
}

// Quick selection AI actions
function quickSelectionAI(prompt) {
    const selectionAIPrompt = document.getElementById('selectionAIPrompt');
    if (selectionAIPrompt) {
        selectionAIPrompt.value = prompt;
        sendSelectionAIRequest();
    }
}

// Send AI request for selected text
async function sendSelectionAIRequest() {
    const prompt = document.getElementById('selectionAIPrompt').value.trim();
    const responseArea = document.getElementById('selectionAIResponse');
    const sendBtn = document.getElementById('sendSelectionAIBtn');
    
    if (!prompt) {
        alert('Vinsamlegast skrifaðu fyrirspurn');
        return;
    }
    
    if (!currentSelectedText) {
        alert('Enginn texti valinn');
        return;
    }
    
    // Show loading
    sendBtn.disabled = true;
    sendBtn.innerHTML = `
        <div style="width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
        Sendir...
    `;
    responseArea.style.display = 'block';
    responseArea.innerHTML = `
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; text-align: center; color: #6b7280;">
            <div style="width: 32px; height: 32px; border: 3px solid #e5e7eb; border-top: 3px solid #ff6b35; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
            Claude vinnur úr textanum þínum...
        </div>
    `;
    
    try {
        // Get full context (current summary for reference)
        const currentSummary = document.getElementById('summaryEditor').value;
        
        // Clean the selected text to find a better match in the markdown
        const cleanSelectedText = currentSelectedText
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/[""]/g, '"')
            .replace(/['']/g, "'");
        
        // Find the corresponding text in the markdown editor
        let markdownMatch = '';
        const summaryLines = currentSummary.split('\n');
        
        // Try to find the text in the markdown, accounting for formatting
        for (let i = 0; i < summaryLines.length; i++) {
            const line = summaryLines[i];
            const cleanLine = line
                .replace(/^#+\s*/, '') // Remove markdown headers
                .replace(/^\*+\s*/, '') // Remove list markers
                .replace(/\*\*(.*?)\*\*/g, '$1') // Remove bold formatting
                .replace(/\*(.*?)\*/g, '$1') // Remove italic formatting
                .replace(/\s+/g, ' ')
                .trim();
            
            if (cleanLine.includes(cleanSelectedText) || cleanSelectedText.includes(cleanLine)) {
                markdownMatch = line.trim();
                break;
            }
        }
        
        // If we found a match in markdown, use that; otherwise use the selected text
        const textToReplace = markdownMatch || currentSelectedText;
        
        console.log('Original selected text:', currentSelectedText);
        console.log('Markdown match found:', markdownMatch);
        console.log('Text to replace:', textToReplace);
        
        // Store both for replacement
        window.originalSelectedText = currentSelectedText;
        window.markdownTextToReplace = textToReplace;
        
        // Prepare context-aware prompt for selective editing
        const aiPrompt = `Þú ert að hjálpa notanda að breyta ákveðnum hluta úr fundarsamantekt. 

SAMHENGI - ÖNNUR SAMANTEKT:
${currentSummary}

VALINN TEXTI TIL BREYTINGA:
"${textToReplace}"

BEIÐNI NOTANDA:
${prompt}

Vinsamlegast endurskrifaðu AÐEINS völda textann út frá beiðni notandans. Svaraðu EINGÖNGU með nýja textanum - ekkert annað. Haltu sama stíl og tón og restin af samantektinni. Ef upprunalegi textinn var með markdown sniðmáti (eins og ** fyrir feitletrað eða # fyrir fyrirsagnir), haltu því sniðmáti.`;

        const response = await fetch('<?php echo App::url()?>?page=api&action=aiChat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                prompt: aiPrompt,
                context: {}
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            responseArea.innerHTML = `
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                    <div style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; padding: 16px;">
                        <h6 style="margin: 0; display: flex; align-items: center; gap: 8px; font-weight: 600;">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Claude Tillaga
                        </h6>
                    </div>
                    <div style="padding: 20px;">
                        <div style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; max-height: 300px; overflow-y: auto; font-family: inherit; font-size: 14px; line-height: 1.5; white-space: pre-wrap; margin-bottom: 16px;">${data.response}</div>
                        <div style="display: flex; gap: 12px;">
                            <button onclick="applySelectionEdit()" 
                                    style="background: linear-gradient(45deg, #ff6b35, #f7931e); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;"
                                    onmouseover="this.style.transform='translateY(-2px)'"
                                    onmouseout="this.style.transform='translateY(0)'">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Nota þessa útgáfu
                            </button>
                            <button onclick="dismissSelectionEdit()" 
                                    style="background: #6b7280; color: white; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='#4b5563'"
                                    onmouseout="this.style.background='#6b7280'">
                                Hafna
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Store the suggestion for later use
            window.currentSelectionEdit = data.response;
        } else {
            responseArea.innerHTML = `
                <div style="background: white; border: 1px solid #fecaca; border-radius: 12px; padding: 20px;">
                    <div style="color: #dc2626; text-align: center;">
                        <svg style="width: 32px; height: 32px; margin-bottom: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p style="margin: 0; font-weight: 600;">Villa kom upp</p>
                        <p style="margin: 8px 0 0 0; opacity: 0.7;">${data.message}</p>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Selection AI request error:', error);
        responseArea.innerHTML = `
            <div style="background: white; border: 1px solid #fecaca; border-radius: 12px; padding: 20px;">
                <div style="color: #dc2626; text-align: center;">
                    <svg style="width: 32px; height: 32px; margin-bottom: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p style="margin: 0; font-weight: 600;">Tenging mistókst</p>
                    <p style="margin: 8px 0 0 0; opacity: 0.7;">Villa við að tengja við Claude. Vinsamlegast reyndu aftur.</p>
                </div>
            </div>
        `;
    }
    
    // Restore button
    sendBtn.disabled = false;
    sendBtn.innerHTML = `
        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
        </svg>
        Senda til Claude
    `;
}

// Apply selection edit
function applySelectionEdit() {
    if (!window.currentSelectionEdit || !currentSelectedText) {
        alert('Engin tillaga að nota');
        return;
    }
    
    try {
        // Get the editor
        const editor = document.getElementById('summaryEditor');
        const currentContent = editor.value;
        
        console.log('Current content length:', currentContent.length);
        console.log('New text from AI:', window.currentSelectionEdit);
        
        // Determine which text to replace - use the best available match
        const textToReplace = window.markdownTextToReplace || window.markdownMatchForSelection || currentSelectedText;
        console.log('Text to replace in editor:', textToReplace);
        console.log('Available replacement options:');
        console.log('- markdownTextToReplace:', window.markdownTextToReplace);
        console.log('- markdownMatchForSelection:', window.markdownMatchForSelection);
        console.log('- currentSelectedText:', currentSelectedText);
        
        // Enhanced debugging
        console.log('=== TEXT REPLACEMENT DEBUG ===');
        console.log('Current content preview:', currentContent.substring(0, 500));
        console.log('Selected text:', currentSelectedText);
        console.log('Text to replace:', textToReplace);
        console.log('Current content contains selected text:', currentContent.includes(currentSelectedText));
        console.log('Current content contains text to replace:', currentContent.includes(textToReplace));
        
        // Try multiple replacement strategies with extensive debugging
        let newContent = currentContent;
        let replaced = false;
        
        // Strategy 1: Direct replacement with textToReplace
        if (textToReplace && currentContent.includes(textToReplace)) {
            newContent = currentContent.replace(textToReplace, window.currentSelectionEdit);
            console.log('✅ Direct replacement successful with textToReplace');
            replaced = true;
        }
        // Strategy 2: Direct replacement with original selected text
        else if (currentContent.includes(currentSelectedText)) {
            newContent = currentContent.replace(currentSelectedText, window.currentSelectionEdit);
            console.log('✅ Direct replacement successful with currentSelectedText');
            replaced = true;
        }
        // Strategy 3: Try fuzzy matching
        else {
            console.warn('⚠️ Direct match not found, trying fuzzy search strategies');
            
            // Create more search strategies
            const searchStrategies = [
                currentSelectedText.replace(/\s+/g, ' ').trim(), // Normalized whitespace
                currentSelectedText.replace(/[""'']/g, '"').replace(/['']/g, "'"), // Normalize quotes
                currentSelectedText.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim(), // Remove line breaks
                // Try without common markdown formatting
                currentSelectedText.replace(/\*\*(.*?)\*\*/g, '$1').replace(/\*(.*?)\*/g, '$1'),
                // Try to find partial matches (first 50 chars)
                currentSelectedText.substring(0, Math.min(50, currentSelectedText.length)).trim()
            ];
            
            console.log('Trying search strategies:', searchStrategies);
            
            for (let i = 0; i < searchStrategies.length; i++) {
                const searchText = searchStrategies[i];
                if (searchText && searchText.length > 5 && currentContent.includes(searchText)) {
                    newContent = currentContent.replace(searchText, window.currentSelectionEdit);
                    console.log(`✅ Replacement successful with strategy ${i + 1}:`, searchText.substring(0, 50));
                    replaced = true;
                    break;
                }
            }
            
            // Strategy 4: Word-by-word search for partial matches
            if (!replaced && currentSelectedText.length > 20) {
                console.log('⚠️ Trying word-by-word search...');
                const words = currentSelectedText.split(/\s+/);
                if (words.length >= 3) {
                    // Try to find a sequence of 3+ consecutive words
                    for (let i = 0; i <= words.length - 3; i++) {
                        const wordSequence = words.slice(i, i + 3).join(' ');
                        const contentIndex = currentContent.indexOf(wordSequence);
                        if (contentIndex !== -1) {
                            console.log('✅ Found word sequence:', wordSequence);
                            // Find the full sentence/paragraph containing this sequence
                            const beforeIndex = Math.max(0, contentIndex - 100);
                            const afterIndex = Math.min(currentContent.length, contentIndex + currentSelectedText.length + 100);
                            const contextText = currentContent.substring(beforeIndex, afterIndex);
                            
                            // Try to find the best match within this context
                            const lines = contextText.split('\n');
                            for (const line of lines) {
                                if (line.includes(wordSequence) && line.trim().length > 10) {
                                    console.log('✅ Found matching line:', line);
                                    newContent = currentContent.replace(line.trim(), window.currentSelectionEdit);
                                    replaced = true;
                                    break;
                                }
                            }
                            if (replaced) break;
                        }
                    }
                }
            }
            
            // If all strategies failed, show detailed error
            if (!replaced) {
                console.error('❌ All replacement strategies failed');
                console.log('Available content lines:');
                currentContent.split('\n').slice(0, 10).forEach((line, i) => {
                    console.log(`Line ${i + 1}:`, line.substring(0, 100));
                });
                
                // Offer manual replacement option
                const userChoice = confirm(
                    'Gat ekki fundið nákvæmlega þennan texta í ritvinnslinum.\n\n' +
                    'Viltu bæta Claude tillögunni við í lok skjalsins í staðinn?'
                );
                
                if (userChoice) {
                    newContent = currentContent + '\n\n' + window.currentSelectionEdit;
                    replaced = true;
                    console.log('✅ Appended to end of document');
                } else {
                    return;
                }
            }
        }
        
        // Update the editor with new content
        editor.value = newContent;
        console.log('Editor updated with new content, length:', newContent.length);
        
        // Update the preview immediately
        updatePreview();
        
        // Clear the browser selection so it doesn't interfere with future selections
        if (window.getSelection) {
            window.getSelection().removeAllRanges();
        }
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('textSelectionModal'));
        if (modal) {
            modal.hide();
        }
        
        // Reset selection variables so new selections can be made immediately
        currentSelection = null;
        currentSelectedText = '';
        selectionRange = null;
        window.currentSelectionEdit = null;
        window.originalSelectedText = null;
        window.markdownTextToReplace = null;
        
        // Hide selection tools
        hideSelectionTools();
        
        // Clear any modal inputs for next use
        const selectionAIPrompt = document.getElementById('selectionAIPrompt');
        const selectionAIResponse = document.getElementById('selectionAIResponse');
        if (selectionAIPrompt) selectionAIPrompt.value = '';
        if (selectionAIResponse) selectionAIResponse.style.display = 'none';
        
        // Show confirmation with save reminder
        showNotification('✅ Texti uppfærður! Mundu að vista breytingarnar.', 'success');
        
        // Re-initialize text selection for the updated content
        setTimeout(() => {
            initializeTextSelection();
        }, 100);
        
        console.log('Text replacement completed successfully, ready for next selection');
        
    } catch (error) {
        console.error('Error applying selection edit:', error);
        alert('Villa kom upp við að nota tillöguna: ' + error.message);
    }
}

// Dismiss selection edit
function dismissSelectionEdit() {
    const selectionAIPrompt = document.getElementById('selectionAIPrompt');
    const selectionAIResponse = document.getElementById('selectionAIResponse');
    
    if (selectionAIPrompt) selectionAIPrompt.value = '';
    if (selectionAIResponse) selectionAIResponse.style.display = 'none';
    
    showNotification('Tillaga hafnað', 'info');
}

// Clear text selection
function clearTextSelection() {
    if (window.getSelection) {
        window.getSelection().removeAllRanges();
    }
    currentSelection = null;
    currentSelectedText = '';
    selectionRange = null;
    hideSelectionTools();
}

// Apply styles to quick AI buttons
document.addEventListener('DOMContentLoaded', function() {
    // Initialize text selection functionality
    initializeTextSelection();
    
    // Style quick AI modal buttons
    const modalBtnStyle = `
        padding: 10px 12px; 
        border: 1px solid #e5e7eb; 
        background: white; 
        border-radius: 10px; 
        font-size: 13px; 
        cursor: pointer; 
        transition: all 0.3s ease; 
        display: flex; 
        align-items: center; 
        gap: 6px;
        font-weight: 500;
        color: #374151;
    `;
    
    document.querySelectorAll('.quick-ai-modal-btn').forEach(btn => {
        btn.style.cssText = modalBtnStyle;
        btn.onmouseover = function() {
            this.style.background = '#f3f4f6';
            this.style.borderColor = '#d1d5db';
            this.style.transform = 'translateY(-1px)';
        };
        btn.onmouseout = function() {
            this.style.background = 'white';
            this.style.borderColor = '#e5e7eb';
            this.style.transform = 'translateY(0)';
        };
    });

    // Style quick selection buttons
    const selectionBtnStyle = `
        padding: 8px 10px; 
        border: 1px solid #e5e7eb; 
        background: white; 
        border-radius: 8px; 
        font-size: 12px; 
        cursor: pointer; 
        transition: all 0.3s ease; 
        display: flex; 
        align-items: center; 
        gap: 4px;
        font-weight: 500;
        color: #374151;
        text-align: center;
        justify-content: center;
    `;
    
    document.querySelectorAll('.quick-selection-btn').forEach(btn => {
        btn.style.cssText = selectionBtnStyle;
        btn.onmouseover = function() {
            this.style.background = '#f9fafb';
            this.style.borderColor = '#d1d5db';
            this.style.transform = 'translateY(-1px)';
        };
        btn.onmouseout = function() {
            this.style.background = 'white';
            this.style.borderColor = '#e5e7eb';
            this.style.transform = 'translateY(0)';
        };
    });
    
    // Initialize preview on load (only if editor exists)
    if (document.getElementById('summaryEditor')) {
        updatePreview();
    }
});

// Open AI assistance modal
function askAI() {
    const modal = new bootstrap.Modal(document.getElementById('aiAssistModal'));
    modal.show();
    // Focus on the textarea after modal is shown
    setTimeout(() => {
        document.getElementById('aiPromptModal').focus();
    }, 300);
}

// Quick AI actions for modal
function quickAIModal(prompt) {
    document.getElementById('aiPromptModal').value = prompt;
    sendAIRequestModal();
}

// Send AI request via modal
async function sendAIRequestModal() {
    const prompt = document.getElementById('aiPromptModal').value.trim();
    const currentSummary = document.getElementById('summaryEditor').value;
    const responseArea = document.getElementById('aiResponseModal');
    const sendBtn = document.getElementById('sendAIBtnModal');
    
    if (!prompt) {
        alert('Vinsamlegast skrifaðu fyrirspurn');
        return;
    }
    
    // Show loading
    sendBtn.disabled = true;
    sendBtn.innerHTML = `
        <div style="width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
        Sendir...
    `;
    responseArea.style.display = 'block';
    responseArea.innerHTML = `
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; text-align: center; color: #6b7280;">
            <div style="width: 32px; height: 32px; border: 3px solid #e5e7eb; border-top: 3px solid #7bc043; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
            Claude vinnur úr beiðninni þinni...
        </div>
    `;
    
    try {
        // Prepare context-aware prompt
        const aiPrompt = `Þú ert að hjálpa notanda að breyta fundarsamantekt. 

NÚVERANDI SAMANTEKT:
${currentSummary}

BEIÐNI NOTANDA:
${prompt}

Vinsamlegast gefðu endurbætta útgáfu af samantektinni út frá beiðni notandans. Svaraðu AÐEINS með hinni nýju útgáfu af samantektinni - ekkert annað.`;

        const response = await fetch('<?php echo App::url()?>?page=api&action=aiChat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                prompt: aiPrompt,
                context: {}
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            responseArea.innerHTML = `
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                    <div style="background: linear-gradient(45deg, #7bc043, #00a8cc); color: white; padding: 16px;">
                        <h6 style="margin: 0; display: flex; align-items: center; gap: 8px; font-weight: 600;">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            Claude Tillaga
                        </h6>
                    </div>
                    <div style="padding: 20px;">
                        <div style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; max-height: 300px; overflow-y: auto; font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace; font-size: 13px; line-height: 1.5; white-space: pre-wrap; margin-bottom: 16px;">${data.response}</div>
                        <div style="display: flex; gap: 12px;">
                            <button onclick="applySuggestionModal()" 
                                    style="background: linear-gradient(45deg, #7bc043, #00a8cc); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;"
                                    onmouseover="this.style.transform='translateY(-2px)'"
                                    onmouseout="this.style.transform='translateY(0)'">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Nota þessa tillögu
                            </button>
                            <button onclick="dismissSuggestion()" 
                                    style="background: #6b7280; color: white; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='#4b5563'"
                                    onmouseout="this.style.background='#6b7280'">
                                Hafna
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Store the suggestion for later use
            window.currentAISuggestion = data.response;
        } else {
            responseArea.innerHTML = `
                <div style="background: white; border: 1px solid #fecaca; border-radius: 12px; padding: 20px;">
                    <div style="color: #dc2626; text-align: center;">
                        <svg style="width: 32px; height: 32px; margin-bottom: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p style="margin: 0; font-weight: 600;">Villa kom upp</p>
                        <p style="margin: 8px 0 0 0; opacity: 0.7;">${data.message}</p>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('AI request error:', error);
        responseArea.innerHTML = `
            <div style="background: white; border: 1px solid #fecaca; border-radius: 12px; padding: 20px;">
                <div style="color: #dc2626; text-align: center;">
                    <svg style="width: 32px; height: 32px; margin-bottom: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p style="margin: 0; font-weight: 600;">Tenging mistókst</p>
                    <p style="margin: 8px 0 0 0; opacity: 0.7;">Villa við að tengja við Claude. Vinsamlegast reyndu aftur.</p>
                </div>
            </div>
        `;
    }
    
    // Restore button
    sendBtn.disabled = false;
    sendBtn.innerHTML = `
        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
        </svg>
        Senda til Claude
    `;
}

// Apply AI suggestion from modal
function applySuggestionModal() {
    if (window.currentAISuggestion) {
        document.getElementById('summaryEditor').value = window.currentAISuggestion;
        updatePreview();
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('aiAssistModal'));
        modal.hide();
        
        // Clear the prompt for next use
        document.getElementById('aiPromptModal').value = '';
        document.getElementById('aiResponseModal').style.display = 'none';
        
        // Show confirmation
        showNotification('✅ Tillaga beitt! Samantekt uppfærð.', 'success');
    }
}

// Dismiss suggestion
function dismissSuggestion() {
    document.getElementById('aiPromptModal').value = '';
    document.getElementById('aiResponseModal').style.display = 'none';
    showNotification('Tillaga hafnað', 'info');
}

// Toggle between edit and preview modes
function toggleView() {
    const previewPane = document.getElementById('previewPane');
    const toggleBtn = document.getElementById('viewToggle');
    const isPreviewVisible = previewPane.style.display !== 'none';
    
    if (isPreviewVisible) {
        // Hide preview - full editor
        previewPane.style.display = 'none';
        toggleBtn.innerHTML = `
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            Sýna forskoðun
        `;
    } else {
        // Show preview - split view
        previewPane.style.display = 'flex';
        toggleBtn.innerHTML = `
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Aðeins ritill
        `;
        updatePreview();
    }
}

// Update markdown preview
function updatePreview() {
    const markdownEditor = document.getElementById('summaryEditor');
    const preview = document.getElementById('markdownPreview');
    
    // Only run if both elements exist (editing mode)
    if (!markdownEditor || !preview) return;
    
    const markdown = markdownEditor.value;
    
    // Simple markdown to HTML conversion
    let html = markdown
        .replace(/^# (.+)/gm, '<h1 style="color: #111827; font-weight: 700; margin: 24px 0 16px 0; font-size: 2rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px;">$1</h1>')
        .replace(/^## (.+)/gm, '<h2 style="color: #374151; font-weight: 600; margin: 20px 0 12px 0; font-size: 1.5rem;">$1</h2>')
        .replace(/^### (.+)/gm, '<h3 style="color: #4b5563; font-weight: 600; margin: 16px 0 8px 0; font-size: 1.25rem;">$1</h3>')
        .replace(/\*\*(.+?)\*\*/g, '<strong style="color: #111827; font-weight: 600;">$1</strong>')
        .replace(/\*(.+?)\*/g, '<em style="color: #6b7280;">$1</em>')
        .replace(/^- (.+)/gm, '<div style="margin: 8px 0; padding-left: 20px; position: relative;"><span style="position: absolute; left: 0; color: #ff6b35; font-weight: bold;">•</span>$1</div>')
        .replace(/\n\n/g, '</p><p style="margin: 16px 0; line-height: 1.7; color: #374151;">')
        .replace(/\n/g, '<br>');
    
    if (html.trim()) {
        preview.innerHTML = `<div style="line-height: 1.7; color: #374151;"><p style="margin: 16px 0; line-height: 1.7; color: #374151;">${html}</p></div>`;
    } else {
        preview.innerHTML = '<div style="text-align: center; color: #9ca3af; padding: 40px;">Byrjaðu að skrifa til að sjá forskoðun...</div>';
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? 'linear-gradient(45deg, #10b981, #059669)' : 'linear-gradient(45deg, #3b82f6, #1d4ed8)'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>