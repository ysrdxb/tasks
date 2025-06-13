<?php
/**
 * Meetings List Page
 */

$title = 'Fundir - AI Verkefnastjóri';
$currentPage = 'meetings';
$pageHeader = [
    'title' => 'Fundir',
    'subtitle' => 'Stjórnaðu fundargerðum og AI greiningu',
    'actions' => [
        [
            'label' => 'Hlaða upp minnismiðum',
            'url' => '<?php echo App::url()?>?page=upload',
            'type' => 'primary',
            'icon' => 'cloud-upload'
        ]
    ]
];

try {
    require_once __DIR__ . '/../../src/Models/Meeting.php';
    $meetingModel = new Meeting();
    $meetings = $meetingModel->findAll([], 'created_at DESC');
} catch (Exception $e) {
    $meetings = [];
    $error = $e->getMessage();
}

ob_start();
?>

<?php if (isset($error)): ?>
    <div style="background: #fffbeb; border: 1px solid #fed7aa; border-radius: 12px; padding: 24px; margin-bottom: 32px;">
        <h4 style="color: #92400e; margin-bottom: 8px; font-size: 1.125rem; font-weight: 600;">Ekki tókst að hlaða fundum</h4>
        <p style="color: #b45309; margin-bottom: 16px;"><?php echo App::sanitize($error); ?></p>
        <a href="<?php echo App::url()?>?page=setup" style="background: #d97706; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500;">
            Keyra uppsetningu
        </a>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        
        <?php if (empty($meetings)): ?>
            <!-- Modern Empty State -->
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                <div style="text-align: center; padding: 80px 24px;">
                    <div style="background: #f8fafc; padding: 20px; border-radius: 16px; display: inline-block; margin-bottom: 24px;">
                        <svg style="width: 48px; height: 48px; color: #94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 style="color: #374151; margin-bottom: 16px; font-size: 1.5rem; font-weight: 600;">Engir fundir ennþá</h3>
                    <p style="color: #6b7280; margin-bottom: 32px; max-width: 400px; margin-left: auto; margin-right: auto;">Hladdu upp þínum fyrstu fundargerðum til að byrja með AI-knúna verkefnastjórnun.</p>
                    <a href="<?php echo App::url()?>?page=upload" style="background: #111827; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px;">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Hlaða upp fundargerðum
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Modern Meetings List -->
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #f3f4f6; background: #fafafa;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <svg style="width: 20px; height: 20px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Allir fundir
                    </h3>
                </div>
                <div style="padding: 0;">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f9fafb;">
                                <tr>
                                    <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: #374151; border-bottom: 1px solid #e5e7eb;">Fundur</th>
                                    <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: #374151; border-bottom: 1px solid #e5e7eb;">Dagsetning</th>
                                    <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: #374151; border-bottom: 1px solid #e5e7eb;">Tegund</th>
                                    <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: #374151; border-bottom: 1px solid #e5e7eb;">Staða</th>
                                    <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: #374151; border-bottom: 1px solid #e5e7eb;">Verkefni</th>
                                    <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: #374151; border-bottom: 1px solid #e5e7eb;">Aðgerðir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meetings as $meeting): ?>
                                    <tr style="border-bottom: 1px solid #f3f4f6; transition: background-color 0.15s ease;" onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor=''">
                                        <td style="padding: 20px 24px;">
                                            <div style="font-weight: 500; color: #111827; margin-bottom: 4px;"><?php echo App::sanitize($meeting['title']); ?></div>
                                            <?php if (!empty($meeting['original_input'])): ?>
                                                <div style="font-size: 0.875rem; color: #6b7280; line-height: 1.4;">
                                                    <?php echo App::sanitize(substr($meeting['original_input'], 0, 100)) . '...'; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 20px 24px;">
                                            <div style="font-size: 0.875rem; color: #6b7280;">
                                                <?php echo date('j. M Y', strtotime($meeting['date'])); ?>
                                            </div>
                                        </td>
                                        <td style="padding: 20px 24px;">
                                            <span style="font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; font-weight: 500; background: #f3f4f6; color: #374151;">
                                                <?php echo ucfirst($meeting['input_type']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 20px 24px;">
                                            <?php
                                            $statusStyles = [
                                                'pending' => 'background: #fef3c7; color: #92400e;',
                                                'processing' => 'background: #dbeafe; color: #1e40af;',
                                                'completed' => 'background: #dcfce7; color: #166534;',
                                                'error' => 'background: #fef2f2; color: #991b1b;'
                                            ];
                                            $statusStyle = $statusStyles[$meeting['processing_status']] ?? 'background: #f3f4f6; color: #374151;';
                                            ?>
                                            <span style="font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; font-weight: 500; <?php echo $statusStyle; ?>">
                                                <?php echo ucfirst($meeting['processing_status']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 20px 24px;">
                                            <div style="font-weight: 500; color: #111827;">
                                                <?php
                                                // Count projects created from this meeting
                                                try {
                                                    require_once __DIR__ . '/../../src/Models/Project.php';
                                                    $projectModel = new Project();
                                                    $projectCount = $projectModel->count(['meeting_id' => $meeting['id']]);
                                                    echo $projectCount;
                                                } catch (Exception $e) {
                                                    echo '0';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td style="padding: 20px 24px;">
                                            <div style="display: flex; gap: 8px;">
                                                <button style="border: 1px solid #d1d5db; background: white; color: #374151; padding: 6px 8px; border-radius: 6px; cursor: pointer; transition: all 0.15s ease;" 
                                                        onclick="viewMeeting(<?php echo $meeting['id']; ?>)"
                                                        onmouseover="this.style.background='#f9fafb'"
                                                        onmouseout="this.style.background='white'">
                                                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button>
                                                <?php if ($meeting['processing_status'] === 'error'): ?>
                                                    <button style="border: 1px solid #f59e0b; background: #fef3c7; color: #92400e; padding: 6px 8px; border-radius: 6px; cursor: pointer; transition: all 0.15s ease;"
                                                            onclick="reprocessMeeting(<?php echo $meeting['id']; ?>)"
                                                            onmouseover="this.style.background='#fde68a'"
                                                            onmouseout="this.style.background='#fef3c7'">
                                                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                    </button>
                                                <?php endif; ?>
                                                <button style="border: 1px solid #ef4444; background: #fef2f2; color: #dc2626; padding: 6px 8px; border-radius: 6px; cursor: pointer; transition: all 0.15s ease;"
                                                        onclick="deleteMeeting(<?php echo $meeting['id']; ?>)"
                                                        onmouseover="this.style.background='#fee2e2'"
                                                        onmouseout="this.style.background='#fef2f2'">
                                                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- Modern Meeting Details Modal -->
<div class="modal fade" id="meetingModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-xxl-down modal-xl">
        <div class="modal-content" style="border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: white; border: none; padding: 30px 40px;">
                <div style="display: flex; align-items: center; gap: 15px; width: 100%;">
                    <div style="background: rgba(255, 255, 255, 0.2); padding: 12px; border-radius: 12px;">
                        <svg style="width: 24px; height: 24px;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                        </svg>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-weight: 700; font-size: 1.5rem;">Fundargögn</h4>
                        <p style="margin: 0; opacity: 0.9; font-size: 0.95rem;">Ítarleg yfirlit og greining</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" 
                            style="background: rgba(255, 255, 255, 0.2); border-radius: 10px; padding: 12px; opacity: 1;"
                            onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
                            onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'"></button>
                </div>
            </div>
            <div class="modal-body" style="padding: 0; background: #f8fafc;">
                <div id="meetingContent" style="min-height: 500px;">
                    <div style="display: flex; justify-content: center; align-items: center; height: 400px;">
                        <div style="text-align: center;">
                            <div style="background: linear-gradient(45deg, #ff6b35, #f7931e); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; animation: pulse 2s infinite;">
                                <svg style="width: 28px; height: 28px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <h5 style="color: #374151; margin-bottom: 8px;">Hleður fundargögnum...</h5>
                            <p style="color: #6b7280; margin: 0;">Vinsamlegast bíðið á meðan gögn eru sótt</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>

<script>
function viewMeeting(meetingId) {
    const modal = new bootstrap.Modal(document.getElementById('meetingModal'));
    const modalContent = document.getElementById('meetingContent');
    
    // Show loading
    modalContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Hleður...</span>
            </div>
            <p class="mt-3">Hleður fundargögnum...</p>
        </div>
    `;
    modal.show();
    
    // Load meeting details via AJAX
    fetch(`<?php echo App::url()?>?page=api&action=getMeeting&id=${meetingId}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Raw API response:', data);
            if (data.success) {
                const meeting = data.meeting;
                const analysis = data.analysis;
                console.log('Meeting data received:', meeting);
                console.log('Analysis data received:', analysis);
                console.log('DEBUG INFO:', data.debug);
                
                modalContent.innerHTML = `
                    <!-- Header Section -->
                    <div style="background: white; padding: 40px; border-bottom: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: between; align-items: flex-start; gap: 30px;">
                            <div style="flex: 1;">
                                <h2 style="color: #111827; font-weight: 700; font-size: 1.8rem; margin: 0 0 12px 0;">${meeting.title}</h2>
                                <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                                    <div style="display: flex; align-items: center; gap: 8px; color: #6b7280;">
                                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span>${new Date(meeting.date).toLocaleDateString('is-IS', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px; color: #6b7280;">
                                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        <span style="text-transform: capitalize;">${meeting.input_type}</span>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: end; gap: 15px;">
                                <span style="background: ${getStatusGradient(meeting.processing_status)}; color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; text-transform: capitalize;">${meeting.processing_status}</span>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick="exportMeeting(${meetingId}, 'pdf')" 
                                            style="background: linear-gradient(45deg, #dc2626, #ef4444); color: white; border: none; padding: 12px 20px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;"
                                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(220, 38, 38, 0.4)'"
                                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        PDF
                                    </button>
                                    <button onclick="exportMeeting(${meetingId}, 'md')" 
                                            style="background: linear-gradient(45deg, #7bc043, #00a8cc); color: white; border: none; padding: 12px 20px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;"
                                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(123, 192, 67, 0.4)'"
                                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Markdown
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Tabs -->
                    <div style="background: white; padding: 0 40px;">
                        <div style="display: flex; border-bottom: 1px solid #e5e7eb;">
                            <button class="modal-tab active" data-tab="overview" onclick="switchTab(event, 'overview')"
                                    style="padding: 20px 30px; border: none; background: none; color: #ff6b35; font-weight: 600; border-bottom: 3px solid #ff6b35; cursor: pointer;">
                                📋 Yfirlit
                            </button>
                            <button class="modal-tab" data-tab="analysis" onclick="switchTab(event, 'analysis')"
                                    style="padding: 20px 30px; border: none; background: none; color: #6b7280; font-weight: 600; border-bottom: 3px solid transparent; cursor: pointer;">
                                🤖 AI Greining
                            </button>
                            ${meeting.input_type === 'conversation' && analysis && analysis.conversation ? `
                            <button class="modal-tab" data-tab="conversation" onclick="switchTab(event, 'conversation')"
                                    style="padding: 20px 30px; border: none; background: none; color: #6b7280; font-weight: 600; border-bottom: 3px solid transparent; cursor: pointer;">
                                💬 Samtal
                            </button>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Tab Content -->
                    <div style="padding: 40px; max-height: 60vh; overflow-y: auto;">
                        <!-- Overview Tab -->
                        <div id="tab-overview" class="modal-tab-content">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                <div>
                                    <h5 style="color: #111827; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                                        <div style="background: linear-gradient(45deg, #ff6b35, #f7931e); padding: 8px; border-radius: 8px;">
                                            <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        Upprunalegt inntak
                                    </h5>
                                    <div style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; max-height: 400px; overflow-y: auto;">
                                        <pre style="white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 0.9em; line-height: 1.6; margin: 0; color: #374151;">${meeting.original_input}</pre>
                                    </div>
                                </div>
                                <div>
                                    <h5 style="color: #111827; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                                        <div style="background: linear-gradient(45deg, #7bc043, #00a8cc); padding: 8px; border-radius: 8px;">
                                            <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                        Tölfræði
                                    </h5>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 2rem; font-weight: 700; color: #ff6b35; margin-bottom: 8px;">${data.project_count || 0}</div>
                                            <div style="color: #6b7280; font-size: 0.9rem;">Verkefni</div>
                                        </div>
                                        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 2rem; font-weight: 700; color: #7bc043; margin-bottom: 8px;">${data.task_count || 0}</div>
                                            <div style="color: #6b7280; font-size: 0.9rem;">Verkþættir</div>
                                        </div>
                                    </div>
                                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-top: 20px;">
                                        <div style="color: #6b7280; font-size: 0.85rem; margin-bottom: 8px;">Búið til</div>
                                        <div style="color: #111827; font-weight: 600;">${new Date(meeting.created_at).toLocaleString('is-IS')}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Analysis Tab -->
                        <div id="tab-analysis" class="modal-tab-content" style="display: none;">
                            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 30px;">
                                <h5 style="color: #111827; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                                    <div style="background: linear-gradient(45deg, #f7931e, #ffcc02); padding: 8px; border-radius: 8px;">
                                        <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                        </svg>
                                    </div>
                                    AI Greining og Samantekt
                                </h5>
                                <div id="analysisContent" style="max-height: 500px; overflow-y: auto;">
                                    <!-- Content will be inserted here -->
                                </div>
                            </div>
                        </div>
                        
                        ${meeting.input_type === 'conversation' && analysis && analysis.conversation ? `
                        <!-- Conversation Tab -->
                        <div id="tab-conversation" class="modal-tab-content" style="display: none;">
                            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 30px;">
                                <h5 style="color: #111827; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                                    <div style="background: linear-gradient(45deg, #00a8cc, #7bc043); padding: 8px; border-radius: 8px;">
                                        <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                    </div>
                                    Samtal við Claude
                                </h5>
                                <div style="max-height: 500px; overflow-y: auto;">
                                    ${formatConversationModern(analysis.conversation)}
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                // Insert analysis content properly using innerHTML
                const analysisContentDiv = document.getElementById('analysisContent');
                if (analysis) {
                    const formattedAnalysis = formatAnalysisModern(analysis);
                    analysisContentDiv.innerHTML = formattedAnalysis;
                } else {
                    analysisContentDiv.innerHTML = `
                        <div style="text-align: center; padding: 60px; color: #6b7280;">
                            <svg style="width: 48px; height: 48px; margin-bottom: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p style="margin: 0; font-size: 1.1rem; margin-bottom: 12px;">Engin AI greining vistuð</p>
                            <p style="margin: 0; font-size: 0.9rem; color: #9ca3af;">
                                ${meeting.input_type === 'conversation' ? 
                                  'Þetta er samtals-fundur. AI greiningin gæti ekki hafa verið vistuð rétt.' : 
                                  'Enginn AI greining framkvæmd fyrir þennan fund.'}
                            </p>
                            <button onclick="regenerateAnalysis(${meeting.id})" 
                                    style="background: linear-gradient(45deg, #f7931e, #ffcc02); color: white; border: none; padding: 8px 16px; border-radius: 8px; margin-top: 16px; cursor: pointer; font-size: 0.9rem;">
                                🔄 Endurgreina fund
                            </button>
                        </div>
                    `;
                }
            } else {
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Villa við að hlaða fundargögnum: ${data.error}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading meeting data:', error);
            modalContent.innerHTML = `
                <div class="alert alert-danger" style="margin: 40px;">
                    <i class="bi bi-exclamation-triangle"></i>
                    Villa við að hlaða fundargögnum: ${error.message}
                </div>
            `;
        });
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'processing': 'info', 
        'completed': 'success',
        'error': 'danger'
    };
    return colors[status] || 'secondary';
}

function getStatusGradient(status) {
    const gradients = {
        'pending': 'linear-gradient(45deg, #f59e0b, #d97706)',
        'processing': 'linear-gradient(45deg, #3b82f6, #1d4ed8)',
        'completed': 'linear-gradient(45deg, #10b981, #059669)',
        'error': 'linear-gradient(45deg, #ef4444, #dc2626)'
    };
    return gradients[status] || 'linear-gradient(45deg, #6b7280, #4b5563)';
}

function formatAnalysis(analysis) {
    if (analysis.summary) {
        // Markdown summary
        return `<div class="markdown-content">${analysis.summary.replace(/\n/g, '<br>')}</div>`;
    } else {
        // JSON analysis
        return `<pre>${JSON.stringify(analysis, null, 2)}</pre>`;
    }
}

function formatAnalysisModern(analysis) {
    console.log('formatAnalysisModern called with:', analysis);
    
    // Check for summary in analysis.summary (conversation meetings)
    if (analysis && analysis.summary) {
        console.log('Found analysis.summary:', analysis.summary);
        // Decode HTML entities that were escaped during save
        let decodedHTML = analysis.summary
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'")
            .replace(/&amp;/g, '&');
        return decodedHTML;
    } 
    // Check if the entire analysis object is a summary (some meetings store it differently)
    else if (typeof analysis === 'string' && analysis.includes('<h1') || analysis.includes('<h2')) {
        console.log('Found HTML string analysis');
        return `<div style="line-height: 1.6; color: #374151; padding: 20px;">${analysis}</div>`;
    }
    // Handle markdown format
    else if (typeof analysis === 'string') {
        console.log('Found markdown string analysis');
        let formattedSummary = analysis
            .replace(/^# (.+)/gm, '<h3 style="color: #111827; font-weight: 700; margin: 24px 0 16px 0; font-size: 1.4rem;">$1</h3>')
            .replace(/^## (.+)/gm, '<h4 style="color: #374151; font-weight: 600; margin: 20px 0 12px 0; font-size: 1.2rem;">$1</h4>')
            .replace(/^### (.+)/gm, '<h5 style="color: #4b5563; font-weight: 600; margin: 16px 0 8px 0; font-size: 1.1rem;">$1</h5>')
            .replace(/\*\*(.+?)\*\*/g, '<strong style="color: #111827;">$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/^- (.+)/gm, '<div style="margin: 8px 0; padding-left: 20px; position: relative;"><span style="position: absolute; left: 0; color: #ff6b35;">•</span>$1</div>')
            .replace(/\n\n/g, '</p><p style="margin: 16px 0; line-height: 1.6; color: #374151;">')
            .replace(/\n/g, '<br>');
        
        return `<div style="line-height: 1.6; color: #374151; padding: 20px;"><p style="margin: 16px 0; line-height: 1.6; color: #374151;">${formattedSummary}</p></div>`;
    }
    // Fallback for JSON/object data
    else if (analysis && typeof analysis === 'object') {
        console.log('Found object analysis, stringifying');
        return `<div style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; overflow-x: auto;"><pre style="margin: 0; font-size: 0.9em; color: #374151;">${JSON.stringify(analysis, null, 2)}</pre></div>`;
    }
    else {
        console.log('No analysis found');
        return '<div style="text-align: center; padding: 60px; color: #6b7280;"><svg style="width: 48px; height: 48px; margin-bottom: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><p style="margin: 0; font-size: 1.1rem;">Engin AI greining tiltæk</p></div>';
    }
}

function formatConversation(conversation) {
    if (!Array.isArray(conversation)) return '<p class="text-muted">Engin samtalgögn</p>';
    
    return conversation.map(msg => `
        <div class="mb-2">
            <strong>${msg.type === 'user' ? 'Þú' : 'Claude'}:</strong>
            <div class="ms-3">${msg.message}</div>
        </div>
    `).join('');
}

function formatConversationModern(conversation) {
    if (!Array.isArray(conversation)) return '<div style="text-align: center; padding: 40px; color: #6b7280;">Engin samtalgögn tiltæk</div>';
    
    return conversation.map((msg, index) => `
        <div style="margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px;">
            <div style="background: ${msg.type === 'user' ? 'linear-gradient(45deg, #ff6b35, #f7931e)' : 'linear-gradient(45deg, #7bc043, #00a8cc)'}; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                ${msg.type === 'user' ? 
                    '<svg style="width: 20px; height: 20px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>' : 
                    '<svg style="width: 20px; height: 20px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>'
                }
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #111827; margin-bottom: 8px; font-size: 0.95rem;">
                    ${msg.type === 'user' ? 'Þú' : 'Claude'}
                </div>
                <div style="background: ${msg.type === 'user' ? '#fef7f0' : '#f0f9ff'}; border: 1px solid ${msg.type === 'user' ? '#fed7aa' : '#bae6fd'}; border-radius: 12px; padding: 16px; color: #374151; line-height: 1.6;">
                    ${msg.message.replace(/\n/g, '<br>')}
                </div>
            </div>
        </div>
    `).join('');
}

function switchTab(event, tabId) {
    // Remove active class from all tabs
    document.querySelectorAll('.modal-tab').forEach(tab => {
        tab.style.color = '#6b7280';
        tab.style.borderBottomColor = 'transparent';
    });
    
    // Hide all tab content
    document.querySelectorAll('.modal-tab-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Activate clicked tab
    event.target.style.color = '#ff6b35';
    event.target.style.borderBottomColor = '#ff6b35';
    
    // Show selected tab content
    document.getElementById(`tab-${tabId}`).style.display = 'block';
}

function exportMeeting(meetingId, format = 'md') {
    window.open(`<?php echo App::url()?>?page=api&action=exportMeeting&id=${meetingId}&format=${format}`, '_blank');
}

function reprocessMeeting(meetingId) {
    if (confirm('Are you sure you want to reprocess this meeting? This will delete existing projects and tasks created from this meeting.')) {
        // This would trigger reprocessing via AJAX
        App.showAlert('Meeting reprocessing is not yet implemented.', 'info');
    }
}

function deleteMeeting(meetingId) {
    if (confirm('Ertu viss um að þú viljir eyða þessum fundi? Þetta mun einnig eyða öllum verkefnum og verkþáttum sem tengdir eru fundinum.')) {
        // Show loading state
        const deleteBtn = document.querySelector(`button[onclick="deleteMeeting(${meetingId})"]`);
        const originalHtml = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        deleteBtn.disabled = true;
        
        // Send delete request
        fetch('<?php echo App::url()?>?page=api&action=deleteMeeting', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                meetingId: meetingId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the row from table with animation
                const row = deleteBtn.closest('tr');
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                
                setTimeout(() => {
                    row.remove();
                    
                    // Check if table is now empty
                    const tbody = document.querySelector('table tbody');
                    if (tbody.children.length === 0) {
                        // Reload page to show empty state
                        window.location.reload();
                    }
                }, 300);
                
                // Show success message
                showAlert('Fundur hefur verið eyddur', 'success');
            } else {
                // Restore button state
                deleteBtn.innerHTML = originalHtml;
                deleteBtn.disabled = false;
                showAlert('Villa við að eyða fundi: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Restore button state
            deleteBtn.innerHTML = originalHtml;
            deleteBtn.disabled = false;
            showAlert('Villa við að eyða fundi', 'danger');
        });
    }
}

function showAlert(message, type) {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.classList.remove('show');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 150);
        }
    }, 5000);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>