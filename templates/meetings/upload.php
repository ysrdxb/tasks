<?php
/**
 * Upload Meeting Notes Page
 */

$title = 'Hlaða upp fundargerðum - AI verkefnastjóri';
$currentPage = 'upload';
$pageHeader = [
    'title' => 'Hlaða upp fundargerðum',
    'subtitle' => 'Hlaðið upp texta, myndum eða hljóðskrám til að draga út verkefni og verkhluta'
];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once __DIR__ . '/../../src/Services/MeetingProcessor.php';
        
        $processor = new MeetingProcessor();
        $title = App::sanitize($_POST['title'] ?? '');
        $notes = App::sanitize($_POST['notes'] ?? '');
        $testMode = isset($_POST['test_mode']);
        
        if (!empty($notes)) {
            if ($testMode) {
                // Test mode - create simple project without AI
                require_once __DIR__ . '/../../src/Models/Project.php';
                require_once __DIR__ . '/../../src/Models/Meeting.php';
                
                $meetingModel = new Meeting();
                $projectModel = new Project();
                
                // Create meeting record
                $meetingId = $meetingModel->createMeeting([
                    'title' => $title,
                    'original_input' => $notes,
                    'input_type' => 'text'
                ]);
                
                // Create simple test project
                $projectId = $projectModel->createProject([
                    'meeting_id' => $meetingId,
                    'name' => 'Prufuverkefni frá ' . $title,
                    'description' => 'Prufuverkefni búið til án AI vinnslu',
                    'priority' => 'medium'
                ]);
                
                $_SESSION['flash_message'] = 'Prufuverkefni búið til með góðum árangri! Verkefnisnúmer: ' . $projectId;
                $_SESSION['flash_type'] = 'success';
            } else {
                // Store the notes in session for AI analysis
                $_SESSION['pending_analysis'] = [
                    'title' => $title,
                    'notes' => $notes,
                    'timestamp' => time()
                ];
                
                // Redirect to suggestions review page
                App::redirect(App::url(). '?page=suggestions');
            }
        } else {
            throw new Exception('Vinsamlegast gefðu upp fundargerðir til vinnslu.');
        }
        
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Villa við vinnslu fundargerða: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
}

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <!-- Feature Selection -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #f3f4f6; background: #fafafa;">
                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0;">
                    Veldu aðferð
                </h3>
            </div>
            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                    <div style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;" 
                         onmouseover="this.style.borderColor='#ff6b35'; this.style.background='#fef7f0'"
                         onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='white'"
                         onclick="showQuickMode()">
                        <div style="font-size: 2rem; margin-bottom: 12px;">🚀</div>
                        <h4 style="color: #111827; margin-bottom: 8px;">Fljótleg verkefnalisti</h4>
                        <p style="color: #6b7280; font-size: 14px; margin: 0;">Dragðu út verkefni beint úr fundargerðum</p>
                    </div>
                    <div style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;" 
                         onmouseover="this.style.borderColor='#f7931e'; this.style.background='#fef9f0'"
                         onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='white'"
                         onclick="window.location.href='<?php echo App::url()?>?page=meeting-summarizer'">
                        <div style="font-size: 2rem; margin-bottom: 12px;">💬</div>
                        <h4 style="color: #111827; margin-bottom: 8px;">Samtalssamantekt</h4>
                        <p style="color: #6b7280; font-size: 14px; margin: 0;">Búðu til ítarlega samantekt í gegnum samtal</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Mode Upload Form -->
        <div id="quickModeForm" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px; display: none;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #f3f4f6; background: #fafafa;">
                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 20px; height: 20px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Hlaða upp fundargerðum
                </h3>
            </div>
            <div style="padding: 24px;">
                
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#text-tab" type="button">
                            <i class="bi bi-textarea-t"></i> Textainntak
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#file-tab" type="button">
                            <i class="bi bi-file-earmark-image"></i> Skráarinnhlaða
                        </button>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content">
                    
                    <!-- Text Input Tab -->
                    <div class="tab-pane fade show active" id="text-tab">
                        <form method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="title" class="form-label">Titill fundar</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="t.d., Vikulegur teymisfundur - 6. júní 2024" required>
                                <div class="invalid-feedback">Vinsamlegast gefðu upp titil fundar.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Fundargerðir</label>
                                <textarea class="form-control" id="notes" name="notes" rows="12" 
                                          placeholder="Límdu fundargerðir þínar hér..." required></textarea>
                                <div class="form-text">
                                    Límdu fundargerðir þínar og gervigreind okkar mun sjálfkrafa draga út verkefni, verkhluta, tímafresti og forgangsröðun.
                                </div>
                                <div class="invalid-feedback">Vinsamlegast gefðu upp fundargerðir til vinnslu.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="test_mode" name="test_mode">
                                    <label class="form-check-label" for="test_mode">
                                        <strong>Prufustilling</strong> - Búa til grunnverkefni án gervigreindar (til prófunar á gagnagrunni)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('notes').value = getSampleNotes()">
                                    <i class="bi bi-file-text"></i> Hlaða prufugerðum
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-robot"></i> Vinna með gervigreind
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- File Upload Tab -->
                    <div class="tab-pane fade" id="file-tab">
                        <div class="file-upload-area">
                            <div class="text-center">
                                <i class="bi bi-cloud-upload" style="font-size: 3rem; color: var(--bs-primary);"></i>
                                <h5 class="mt-3">Dragðu og slepptu skrám hér</h5>
                                <p class="text-muted">Eða smelltu til að velja skrár</p>
                                <p class="mb-0">
                                    <small class="text-muted">
                                        Stutt: Textaskrár, Myndir (JPG, PNG), Hljóðskrár (MP3, WAV)
                                        <br>Hámarksstærð skráar: 10MB
                                    </small>
                                </p>
                            </div>
                            <input type="file" class="d-none" accept=".txt,.jpg,.jpeg,.png,.gif,.mp3,.wav">
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i>
                            <strong>Athugasemd:</strong> Skráarvinnsla (OCR og hljóðuppskrift) er ekki enn útfærð. 
                            Vinsamlegast notaðu textainntaksfanann í bili.
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- Tips Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Ráð fyrir betri árangur</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bi bi-check-circle text-success"></i> Láttu fylgja með:</h6>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-dot"></i> Verkefnisnöfn og lýsingar</li>
                            <li><i class="bi bi-dot"></i> Ákveðna verkhluta og kröfur</li>
                            <li><i class="bi bi-dot"></i> Tímafresti og tímaramma</li>
                            <li><i class="bi bi-dot"></i> Forgangsröðun (brýnt, hátt o.s.frv.)</li>
                            <li><i class="bi bi-dot"></i> Úthlutaða teymismeðlimi</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-x-circle text-danger"></i> Forðastu:</h6>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-dot"></i> Of mikið smáspjall</li>
                            <li><i class="bi bi-dot"></i> Of tæknilega hugtök</li>
                            <li><i class="bi bi-dot"></i> Ófullnægjandi setningar</li>
                            <li><i class="bi bi-dot"></i> Blönduð tungumál í sömu setningu</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
function showQuickMode() {
    document.getElementById('quickModeForm').style.display = 'block';
    document.getElementById('quickModeForm').scrollIntoView({ behavior: 'smooth' });
}

function getSampleNotes() {
    return `Vikulegur teymisfundur - 6. júní 2024

Teymið ræddi framvindu á endurgerð vefsíðu verkefnisins. Þetta er mikill forgangur og þarf að vera lokið fyrir föstudaginn 14. júní. 

Lykilverkhlutir sem eftir eru:
- Jón mun sjá um útfærslu tengiliðaforms (áætlaðar 4 klukkustundir)
- Sara vinnur að notendaauðkenningarkerfi (6-8 klukkustundir, á gjalddaga mánudaginn)
- Heimasíðuhönnun þarf lokaendurskoðun og samþykki

Nýtt farsímaforrit verkefni samþykkt! Þetta er brýnn forgangur. Markmiðsdagur útgáfu er september 2024. Áætluð 3 mánaða þróunartími.

Eiginleikar sem þarf:
- Notendainnskráning og skráning
- Vörulisti með leit
- Innkaupakörfu virkni  
- Greiðslusamþætting með Stripe
- Ýtiboð

Endurskoðunarfundur á viðbrögðum viðskiptavinar áætlaður á mánudag. Miðlungs forgangur verkhlutir:
- Bæta við dökku hami víxli á stjórnborð (2 klukkustundir)
- Bæta hleðslutíma síðna (frammistöðubestun, 5 klukkustundir)
- Laga sveigjanleika vandamál á spjaldtölvum (3 klukkustundir)

Tímalína: Allir endurskoðunarverkhlutir viðskiptavinar ættu að vera lokið innan 2 vikna.

Aðgerðaatriði:
- Skipuleggja hönnunarendurskoðunarfund fyrir heimasíðu
- Rannsaka greiðslusamþættingarmöguleika
- Búa til farsímaforrit vírramma
- Uppfæra verkefnatímalínuskjöl`;
}

// Modern tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Update button styles
            tabButtons.forEach(btn => {
                btn.style.color = '#6b7280';
                btn.style.borderBottomColor = 'transparent';
            });
            this.style.color = '#111827';
            this.style.borderBottomColor = '#111827';
            
            // Show/hide tab panes
            tabPanes.forEach(pane => {
                pane.style.display = 'none';
            });
            document.getElementById(targetTab).style.display = 'block';
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>