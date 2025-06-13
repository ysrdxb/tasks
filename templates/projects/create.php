<?php
/**
 * Enhanced Project Creation with Progressive Building
 */

$title = 'Búa til verkefni - AI Verkefnastjóri';
$currentPage = 'projects';
$pageHeader = [
    'title' => 'Búa til nýtt verkefni',
    'subtitle' => 'Byggjum upp verkefnið þitt skref fyrir skref'
];

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        
        <!-- Modern Progress Steps -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
            <div style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; position: relative; margin: 20px 0;">
                    <div style="position: absolute; top: 20px; left: 20px; right: 20px; height: 2px; background: #e5e7eb; z-index: 1;"></div>
                    
                    <div class="step active" data-step="1" style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #111827; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 8px; transition: all 0.3s ease;">1</div>
                        <div style="font-size: 0.875rem; color: #111827; text-align: center; font-weight: 600;">Grunnupplýsingar</div>
                    </div>
                    <div class="step" data-step="2" style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #e5e7eb; color: #6b7280; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 8px; transition: all 0.3s ease;">2</div>
                        <div style="font-size: 0.875rem; color: #6b7280; text-align: center;">AI tillögur</div>
                    </div>
                    <div class="step" data-step="3" style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #e5e7eb; color: #6b7280; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 8px; transition: all 0.3s ease;">3</div>
                        <div style="font-size: 0.875rem; color: #6b7280; text-align: center;">Verkþættir</div>
                    </div>
                    <div class="step" data-step="4" style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #e5e7eb; color: #6b7280; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 8px; transition: all 0.3s ease;">4</div>
                        <div style="font-size: 0.875rem; color: #6b7280; text-align: center;">Smáatriði</div>
                    </div>
                    <div class="step" data-step="5" style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #e5e7eb; color: #6b7280; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 8px; transition: all 0.3s ease;">5</div>
                        <div style="font-size: 0.875rem; color: #6b7280; text-align: center;">Forskoðun</div>
                    </div>
                </div>
            </div>
        </div>

        <form id="projectBuilderForm">
            
            <!-- Modern Step 1: Basic Info -->
            <div class="card-builder-step active" data-step="1">
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                    <div style="padding: 20px 24px; border-bottom: 1px solid #f3f4f6; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white;">
                        <h5 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Hvað er verkefnið?
                        </h5>
                    </div>
                    <div style="padding: 24px;">
                        <div class="row">
                            <div class="col-md-8">
                                <div style="margin-bottom: 24px;">
                                    <label for="project_name" style="display: block; font-weight: 500; color: #374151; margin-bottom: 8px;">Nafn verkefnis *</label>
                                    <input type="text" id="project_name" 
                                           placeholder="T.d., Ný fyrirtækjavefsíða" 
                                           oninput="handleProjectNameInputWithHelp(this.value)" 
                                           onfocus="showContextualTip('project_name', this.value)"
                                           style="width: 100%; padding: 16px 20px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; font-weight: 500; transition: all 0.15s ease;"
                                           onmouseover="this.style.borderColor='#9ca3af'"
                                           onmouseout="if(this !== document.activeElement) this.style.borderColor='#d1d5db'"
                                           onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)'"
                                           onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                                    <div style="font-size: 14px; color: #6b7280; margin-top: 8px;">Skýrt og lýsandi nafn hjálpar öllum að skilja verkefnið</div>
                                </div>
                                
                                <div style="margin-bottom: 24px;">
                                    <label for="project_description" style="display: block; font-weight: 500; color: #374151; margin-bottom: 8px;">Lýsing verkefnis</label>
                                    <textarea id="project_description" rows="4"
                                              placeholder="Lýstu verkefninu stuttlega - hver eru helstu markmið og afrakstur?"
                                              oninput="handleDescriptionInputWithHelp(this.value)"
                                              onfocus="showContextualTip('description', this.value)"
                                              style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; resize: vertical; transition: border-color 0.15s ease;"
                                              onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)'"
                                              onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"></textarea>
                                    <div style="font-size: 14px; color: #6b7280; margin-top: 8px;">Nefndu lykilafrakstur, hagsmunaaðila og kröfur</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <!-- Modern Contextual Help -->
                                <div class="contextual-help">
                                    <div style="background: white; border: 1px solid #bfdbfe; border-radius: 12px; overflow: hidden;">
                                        <div style="padding: 16px 20px; border-bottom: 1px solid #dbeafe; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
                                            <h6 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                                </svg>
                                                Ráð
                                            </h6>
                                        </div>
                                        <div style="padding: 20px;" id="contextualTips">
                                            <div class="tip active" data-context="project_name">
                                                <small>
                                                    <strong>Gott verkefnanafn:</strong><br>
                                                    • Skýrt og nákvæmt<br>
                                                    • Nefnir helsta afrakstur<br>
                                                    • Auðvelt að muna
                                                </small>
                                            </div>
                                            <div class="tip" data-context="description">
                                                <small>
                                                    <strong>Góð lýsing inniheldur:</strong><br>
                                                    • Markmið verkefnisins<br>
                                                    • Helstu afrakstur<br>
                                                    • Lykilhagsmunaaðila<br>
                                                    • Sérstaka kröfu
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modern Quick Templates -->
                                <div style="margin-top: 24px;">
                                    <h6 style="color: #374151; margin-bottom: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                        <svg style="width: 16px; height: 16px; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        Sniðmát
                                    </h6>
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <button type="button" onclick="useTemplate('website')"
                                                style="padding: 10px 16px; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 8px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.15s ease;"
                                                onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#9ca3af'"
                                                onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db'">
                                            🌐 Vefsíða
                                        </button>
                                        <button type="button" onclick="useTemplate('app')"
                                                style="padding: 10px 16px; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 8px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.15s ease;"
                                                onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#9ca3af'"
                                                onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db'">
                                            📱 Forrit
                                        </button>
                                        <button type="button" onclick="useTemplate('marketing')"
                                                style="padding: 10px 16px; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 8px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.15s ease;"
                                                onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#9ca3af'"
                                                onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db'">
                                            📢 Markaðsherferð
                                        </button>
                                        <button type="button" onclick="useTemplate('research')"
                                                style="padding: 10px 16px; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 8px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.15s ease;"
                                                onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#9ca3af'"
                                                onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db'">
                                            🔬 Rannsókn
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modern Validation & Progress -->
                        <div style="margin-top: 24px;" id="step1Validation">
                            <!-- Real-time validation will appear here -->
                        </div>
                        
                        <div style="margin-top: 32px;">
                            <button type="button" onclick="nextStep()" id="step1Next" disabled
                                    style="padding: 12px 24px; background: #9ca3af; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: not-allowed; display: flex; align-items: center; gap: 8px; transition: all 0.15s ease;">
                                Næsta skref 
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: AI Suggestions -->
            <div class="card-builder-step" data-step="2">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">🤖 Lítur þetta rétt út?</h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- AI Analysis Status -->
                        <div id="aiAnalysisStatus" class="text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Greinir...</span>
                            </div>
                            <h6>AI greinir verkefnið þitt...</h6>
                            <p class="text-muted">Þetta tekur nokkrar sekúndur</p>
                        </div>
                        
                        <!-- AI Suggestions (Hidden initially) -->
                        <div id="aiSuggestions" class="d-none">
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <h6>Tillögur að verkefnisuppbyggingu:</h6>
                                    <div id="suggestedStructure">
                                        <!-- AI suggested tasks will appear here -->
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0">📊 Greining</h6>
                                        </div>
                                        <div class="card-body" id="aiAnalysisInfo">
                                            <!-- AI analysis info -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="step-navigation mt-4 d-none" id="step2Navigation">
                            <button type="button" class="btn btn-outline-secondary" onclick="prevStep()">
                                <i class="bi bi-arrow-left"></i> Til baka
                            </button>
                            <button type="button" class="btn btn-primary" onclick="nextStep()">
                                Næsta skref <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Tasks -->
            <div class="card-builder-step" data-step="3">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">📋 Verkþættir</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6>Verkþættir verkefnisins</h6>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="suggestTaskBreakdown()">
                                            <i class="bi bi-lightbulb"></i> Tillögur að verkþáttum
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addNewTask()">
                                            <i class="bi bi-plus"></i> Bæta við verkþætti
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="tasksList">
                                    <!-- Tasks will be populated here -->
                                </div>
                                
                                <!-- Task Builder -->
                                <div class="task-builder mt-3" id="taskBuilder" style="display: none;">
                                    <div class="card border-success">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control mb-2" placeholder="Nafn verkþáttar" id="newTaskName">
                                                    <textarea class="form-control" placeholder="Lýsing (valfrjálst)" id="newTaskDescription" rows="2"></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="task-quick-options mb-2">
                                                        <label class="form-label small">Áætlaður tími:</label>
                                                        <div class="btn-group w-100" role="group">
                                                            <input type="radio" class="btn-check" name="taskTime" id="time2h" value="2">
                                                            <label class="btn btn-outline-primary btn-sm" for="time2h">🕐 2klst</label>
                                                            
                                                            <input type="radio" class="btn-check" name="taskTime" id="time4h" value="4">
                                                            <label class="btn btn-outline-primary btn-sm" for="time4h">🕐 4klst</label>
                                                            
                                                            <input type="radio" class="btn-check" name="taskTime" id="time1d" value="8">
                                                            <label class="btn btn-outline-primary btn-sm" for="time1d">🕐 1 dagur</label>
                                                            
                                                            <input type="radio" class="btn-check" name="taskTime" id="timeCustom" value="custom">
                                                            <label class="btn btn-outline-primary btn-sm" for="timeCustom">⚙️ Annað</label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="task-quick-priority">
                                                        <label class="form-label small">Forgangur:</label>
                                                        <div class="btn-group w-100" role="group">
                                                            <input type="radio" class="btn-check" name="taskPriority" id="prioHigh" value="5">
                                                            <label class="btn btn-outline-danger btn-sm" for="prioHigh">🔴 Hár</label>
                                                            
                                                            <input type="radio" class="btn-check" name="taskPriority" id="prioMedium" value="3" checked>
                                                            <label class="btn btn-outline-warning btn-sm" for="prioMedium">🟡 Miðlungs</label>
                                                            
                                                            <input type="radio" class="btn-check" name="taskPriority" id="prioLow" value="1">
                                                            <label class="btn btn-outline-success btn-sm" for="prioLow">🟢 Lágur</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="custom-time-input mt-2 d-none" id="customTimeInput">
                                                <label class="form-label small">Sérsniðinn tími (klukkustundir):</label>
                                                <input type="number" class="form-control form-control-sm" min="0.5" step="0.5" placeholder="T.d. 6.5">
                                            </div>
                                            
                                            <div class="task-builder-actions mt-3">
                                                <button type="button" class="btn btn-success btn-sm" onclick="saveNewTask()">
                                                    <i class="bi bi-check"></i> Vista verkþátt
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cancelNewTask()">
                                                    <i class="bi bi-x"></i> Hætta við
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <!-- Task Quick Actions -->
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">⚡ Flýtiaðgerðir</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addCommonTasks()">
                                                + Bæta við algengum verkþáttum
                                            </button>
                                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="splitLargeTasks()">
                                                ✂️ Skipta stórum verkþáttum
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="addRealisticTimes()">
                                                ⏰ Bæta við tímaáætlunum
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="suggestTaskOrder()">
                                                📋 Tillaga að röðun
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Task Validation -->
                                <div class="mt-3" id="taskValidation">
                                    <!-- Task validation feedback -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="step-navigation mt-4">
                            <button type="button" class="btn btn-outline-secondary" onclick="prevStep()">
                                <i class="bi bi-arrow-left"></i> Til baka
                            </button>
                            <button type="button" class="btn btn-primary" onclick="nextStep()">
                                Næsta skref <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Details -->
            <div class="card-builder-step" data-step="4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">⚡ Smáatriði</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_priority" class="form-label">Forgangur verkefnis</label>
                                    <select class="form-select" id="project_priority">
                                        <option value="low">🟢 Lágur - Ekki tímabundið</option>
                                        <option value="medium" selected>🟡 Miðlungs - Venjulegur forgangur</option>
                                        <option value="high">🟠 Hár - Mikilvægt verkefni</option>
                                        <option value="urgent">🔴 Brýnt - Þarf að ljúka fljótt</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="project_deadline" class="form-label">Skiladagur</label>
                                    <input type="date" class="form-control" id="project_deadline">
                                    <div class="form-text">Yfirlits- og áætlunarhjálp</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="project_tags" class="form-label">Merki (valfrjálst)</label>
                                    <input type="text" class="form-control" id="project_tags" 
                                           placeholder="T.d., vefþróun, hönnun, markaðssetning">
                                    <div class="form-text">Aðskildu merki með kommu</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-secondary">
                                    <div class="card-header">
                                        <h6 class="mb-0">📊 Sjálfvirkt reiknað</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <strong>Áætlaður heildartími:</strong>
                                            <span id="totalEstimatedTime" class="text-primary">0 klst</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Fjöldi verkþátta:</strong>
                                            <span id="totalTasks" class="text-info">0</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Áætlaður tími til loka:</strong>
                                            <span id="estimatedDuration" class="text-warning">-</span>
                                        </div>
                                        
                                        <hr>
                                        <small class="text-muted">
                                            Byggt á verkþáttum og föngum teymis
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="step-navigation mt-4">
                            <button type="button" class="btn btn-outline-secondary" onclick="prevStep()">
                                <i class="bi bi-arrow-left"></i> Til baka
                            </button>
                            <button type="button" class="btn btn-primary" onclick="nextStep()">
                                Forskoða <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 5: Preview -->
            <div class="card-builder-step" data-step="5">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">📄 Forskoðun verkefnis</h5>
                    </div>
                    <div class="card-body">
                        <div id="projectPreview">
                            <!-- Project preview will be generated here -->
                        </div>
                        
                        <!-- Validation & Suggestions -->
                        <div class="row mt-4">
                            <div class="col-md-8">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">✅ Staðfesting</h6>
                                    </div>
                                    <div class="card-body" id="finalValidation">
                                        <!-- Final validation results -->
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">💡 Tillögur</h6>
                                    </div>
                                    <div class="card-body" id="finalSuggestions">
                                        <!-- Final suggestions -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="step-navigation mt-4">
                            <button type="button" class="btn btn-outline-secondary" onclick="prevStep()">
                                <i class="bi bi-arrow-left"></i> Til baka
                            </button>
                            <button type="button" class="btn btn-outline-warning me-2" onclick="editProject()">
                                <i class="bi bi-pencil"></i> Breyta
                            </button>
                            <button type="submit" class="btn btn-success" id="createProjectBtn">
                                <i class="bi bi-check-circle"></i> Búa til verkefni
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
        </form>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="<?php echo App::asset('js/project-builder.js'); ?>"></script>

<style>
/* Progress Steps */
.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    margin: 20px 0;
}

.progress-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 20px;
    right: 20px;
    height: 2px;
    background: #e9ecef;
    z-index: 1;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.step.active .step-circle {
    background: #0d6efd;
    color: white;
}

.step.completed .step-circle {
    background: #198754;
    color: white;
}

.step-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-align: center;
}

.step.active .step-label {
    color: #0d6efd;
    font-weight: 600;
}

/* Card Builder Steps */
.card-builder-step {
    display: none;
}

.card-builder-step.active {
    display: block;
}

/* Contextual Help */
.contextual-help .tip {
    display: none;
}

.contextual-help .tip.active {
    display: block;
}

/* Template Buttons */
.template-buttons {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

/* Task Builder */
.task-builder {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced Task Items */
.task-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    transition: all 0.2s ease;
    position: relative;
}

.task-item:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.task-item.draggable {
    cursor: move;
}

.task-item .drag-handle {
    cursor: grab;
}

.task-item .drag-handle:active {
    cursor: grabbing;
}

/* Task suggestion dropdown */
.task-suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
}

.task-suggestion-item {
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
}

.task-suggestion-item:hover {
    background-color: #f8f9fa;
}

.task-suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-name {
    font-weight: 500;
    margin-bottom: 4px;
}

.suggestion-meta {
    display: flex;
    gap: 4px;
}

.suggestion-meta .badge {
    font-size: 0.7rem;
}

/* Task editing form */
.task-edit-form {
    background-color: #f8f9fa;
    padding: 12px;
    border-radius: 6px;
    border: 2px solid #0d6efd;
}

.task-edit-actions {
    display: flex;
    gap: 6px;
    justify-content: flex-end;
}

/* Drag and drop states */
.task-ghost {
    opacity: 0.4;
}

.task-chosen {
    background-color: #e3f2fd !important;
}

.task-quick-options .btn-group {
    width: 100%;
}

.task-quick-options .btn {
    font-size: 0.75rem;
}

/* Enhanced Validation Feedback */
.validation-score {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 12px;
}

.validation-item {
    display: block;
    padding: 10px 12px;
    margin: 8px 0;
    border-radius: 8px;
    font-size: 0.875rem;
    border-left: 4px solid;
}

.validation-message {
    font-weight: 500;
}

.validation-action {
    color: #6c757d;
    font-style: italic;
}

.validation-item.success {
    background-color: #d1e7dd;
    color: #0f5132;
    border-left-color: #198754;
}

.validation-item.warning {
    background-color: #fff3cd;
    color: #664d03;
    border-left-color: #ffc107;
}

.validation-item.error {
    background-color: #f8d7da;
    color: #721c24;
    border-left-color: #dc3545;
}

.validation-item.info {
    background-color: #cff4fc;
    color: #055160;
    border-left-color: #0dcaf0;
}

/* Responsive */
@media (max-width: 768px) {
    .progress-steps {
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .step {
        flex: 1;
        min-width: 80px;
    }
    
    .step-label {
        font-size: 0.75rem;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>