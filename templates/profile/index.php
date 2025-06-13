<?php
/**
 * About Me / Profile Page
 * User profile information for better AI context
 */

$title = 'Um mig - AI Verkefnastjóri';
$currentPage = 'profile';
$pageHeader = [
    'title' => 'Um mig',
    'subtitle' => 'Stilltu þinn bakgrunn svo Claude geti veitt betri aðstoð'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $profile = [
            'name' => App::sanitize($_POST['name'] ?? ''),
            'role' => App::sanitize($_POST['role'] ?? ''),
            'company' => App::sanitize($_POST['company'] ?? ''),
            'industry' => App::sanitize($_POST['industry'] ?? ''),
            'team_size' => App::sanitize($_POST['team_size'] ?? ''),
            'responsibilities' => App::sanitize($_POST['responsibilities'] ?? ''),
            'meeting_types' => App::sanitize($_POST['meeting_types'] ?? ''),
            'work_style' => App::sanitize($_POST['work_style'] ?? ''),
            'priorities' => App::sanitize($_POST['priorities'] ?? ''),
            'context_notes' => App::sanitize($_POST['context_notes'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Store in session for now (later can be saved to database)
        $_SESSION['user_profile'] = $profile;
        
        $_SESSION['flash_message'] = 'Snið þitt hefur verið vistað! Claude mun nú fá betri samhengi í samtölum.';
        $_SESSION['flash_type'] = 'success';
        
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Villa við að vista snið: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
}

// Load existing profile
$profile = $_SESSION['user_profile'] ?? [];

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <!-- Profile Information Card -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #f3f4f6; background: linear-gradient(135deg, #7bc043 0%, #00a8cc 100%); color: white;">
                <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Um þig
                </h3>
                <p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 14px;">
                    Þessar upplýsingar hjálpa Claude að skilja þinn bakgrunn og veita betri ráðgjöf
                </p>
            </div>
            <div style="padding: 24px;">
                
                <form method="post">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <h5 style="color: #374151; margin-bottom: 16px; font-weight: 600;">Grunnupplýsingar</h5>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="name" style="display: block; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    Nafn
                                </label>
                                <input type="text" id="name" name="name" 
                                       value="<?php echo App::sanitize($profile['name'] ?? ''); ?>"
                                       placeholder="Fullt nafn þitt"
                                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="role" style="display: block; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    Starfsheiti / Hlutverk
                                </label>
                                <input type="text" id="role" name="role" 
                                       value="<?php echo App::sanitize($profile['role'] ?? ''); ?>"
                                       placeholder="T.d., Verkefnastjóri, CEO, Þróunarstjóri"
                                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="company" style="display: block; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    Fyrirtæki / Stofnun
                                </label>
                                <input type="text" id="company" name="company" 
                                       value="<?php echo App::sanitize($profile['company'] ?? ''); ?>"
                                       placeholder="Nafn fyrirtækis þíns"
                                       style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="industry" style="display: block; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    Iðnaður / Svið
                                </label>
                                <select id="industry" name="industry" 
                                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="">Veldu svið</option>
                                    <option value="technology" <?php echo ($profile['industry'] ?? '') === 'technology' ? 'selected' : ''; ?>>Tækni / IT</option>
                                    <option value="finance" <?php echo ($profile['industry'] ?? '') === 'finance' ? 'selected' : ''; ?>>Fjármál</option>
                                    <option value="healthcare" <?php echo ($profile['industry'] ?? '') === 'healthcare' ? 'selected' : ''; ?>>Heilbrigðisþjónusta</option>
                                    <option value="education" <?php echo ($profile['industry'] ?? '') === 'education' ? 'selected' : ''; ?>>Menntun</option>
                                    <option value="retail" <?php echo ($profile['industry'] ?? '') === 'retail' ? 'selected' : ''; ?>>Verslun</option>
                                    <option value="manufacturing" <?php echo ($profile['industry'] ?? '') === 'manufacturing' ? 'selected' : ''; ?>>Framleiðsla</option>
                                    <option value="consulting" <?php echo ($profile['industry'] ?? '') === 'consulting' ? 'selected' : ''; ?>>Ráðgjöf</option>
                                    <option value="nonprofit" <?php echo ($profile['industry'] ?? '') === 'nonprofit' ? 'selected' : ''; ?>>Félagasamtök</option>
                                    <option value="government" <?php echo ($profile['industry'] ?? '') === 'government' ? 'selected' : ''; ?>>Stjórnvöld</option>
                                    <option value="other" <?php echo ($profile['industry'] ?? '') === 'other' ? 'selected' : ''; ?>>Annað</option>
                                </select>
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="team_size" style="display: block; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    Stærð teymis
                                </label>
                                <select id="team_size" name="team_size" 
                                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="">Veldu stærð</option>
                                    <option value="solo" <?php echo ($profile['team_size'] ?? '') === 'solo' ? 'selected' : ''; ?>>Vinn einn</option>
                                    <option value="small" <?php echo ($profile['team_size'] ?? '') === 'small' ? 'selected' : ''; ?>>Lítið teyma (2-5)</option>
                                    <option value="medium" <?php echo ($profile['team_size'] ?? '') === 'medium' ? 'selected' : ''; ?>>Miðlungs teyma (6-15)</option>
                                    <option value="large" <?php echo ($profile['team_size'] ?? '') === 'large' ? 'selected' : ''; ?>>Stórt teyma (16-50)</option>
                                    <option value="enterprise" <?php echo ($profile['team_size'] ?? '') === 'enterprise' ? 'selected' : ''; ?>>Fyrirtæki (50+)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Work Context -->
                        <div class="col-md-6">
                            <h5 style="color: #374151; margin-bottom: 16px; font-weight: 600;">Vinnusamhengi</h5>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="responsibilities" style="display: block; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    Helstu ábyrgðarsvið
                                </label>
                                <textarea id="responsibilities" name="responsibilities" rows="3"
                                          placeholder="Lýstu helstu ábyrgðarsviðum þínum..."
                                          style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: vertical;"><?php echo App::sanitize($profile['responsibilities'] ?? ''); ?></textarea>
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="meeting_types" style="display: block; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    Helstu tegundir funda
                                </label>
                                <textarea id="meeting_types" name="meeting_types" rows="3"
                                          placeholder="T.d., teymisfundir, viðskiptavinafundir, stefnumótun..."
                                          style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: vertical;"><?php echo App::sanitize($profile['meeting_types'] ?? ''); ?></textarea>
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="work_style" style="display: block; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    Vinnustíll
                                </label>
                                <select id="work_style" name="work_style" 
                                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="">Veldu vinnustíl</option>
                                    <option value="detail-oriented" <?php echo ($profile['work_style'] ?? '') === 'detail-oriented' ? 'selected' : ''; ?>>Nákvæmur og ítarlegur</option>
                                    <option value="big-picture" <?php echo ($profile['work_style'] ?? '') === 'big-picture' ? 'selected' : ''; ?>>Heildarsýn og stefnumótun</option>
                                    <option value="collaborative" <?php echo ($profile['work_style'] ?? '') === 'collaborative' ? 'selected' : ''; ?>>Samvinnulegur</option>
                                    <option value="results-driven" <?php echo ($profile['work_style'] ?? '') === 'results-driven' ? 'selected' : ''; ?>>Árangursdrifinn</option>
                                    <option value="analytical" <?php echo ($profile['work_style'] ?? '') === 'analytical' ? 'selected' : ''; ?>>Greiningarlaus</option>
                                    <option value="creative" <?php echo ($profile['work_style'] ?? '') === 'creative' ? 'selected' : ''; ?>>Skapandi</option>
                                </select>
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="priorities" style="display: block; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    Helstu forgangsröðun
                                </label>
                                <textarea id="priorities" name="priorities" rows="3"
                                          placeholder="Hvað er þér mikilvægast í verkefnum? T.d., gæði, tími, samvinna..."
                                          style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: vertical;"><?php echo App::sanitize($profile['priorities'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Context -->
                    <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                        <h5 style="color: #374151; margin-bottom: 16px; font-weight: 600;">Viðbótar samhengi</h5>
                        
                        <div style="margin-bottom: 20px;">
                            <label for="context_notes" style="display: block; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                Sérstakar athugasemdir fyrir Claude
                            </label>
                            <textarea id="context_notes" name="context_notes" rows="4"
                                      placeholder="Einhver sérstök atriði sem Claude ætti að vita um þig, vinnuumhverfi þitt, eða hvernig þú vilt fá aðstoð..."
                                      style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: vertical;"><?php echo App::sanitize($profile['context_notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div style="margin-top: 32px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 14px; color: #6b7280;">
                            <svg style="width: 16px; height: 16px; display: inline; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Þessar upplýsingar eru aðeins vistaðar staðbundið í vafra þínum
                        </div>
                        <button type="submit" 
                                style="background: linear-gradient(45deg, #7bc043, #00a8cc); color: white; border: none; padding: 12px 32px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(123, 192, 67, 0.4)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(123, 192, 67, 0.3)'">
                            💾 Vista snið
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Preview Card -->
        <?php if (!empty($profile['name'])): ?>
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #f3f4f6; background: #f9fafb;">
                <h3 style="font-size: 1.125rem; font-weight: 600; margin: 0; color: #374151;">
                    Snið fyrir Claude
                </h3>
                <p style="margin: 4px 0 0 0; font-size: 14px; color: #6b7280;">
                    Þetta er samhengið sem Claude mun fá um þig í samtölum
                </p>
            </div>
            <div style="padding: 24px; background: #f8fafc; font-family: monospace; font-size: 13px; line-height: 1.6; color: #374151;">
                <strong>Notandi:</strong> <?php echo App::sanitize($profile['name']); ?><br>
                <strong>Hlutverk:</strong> <?php echo App::sanitize($profile['role']); ?><?php if (!empty($profile['company'])): ?> hjá <?php echo App::sanitize($profile['company']); ?><?php endif; ?><br>
                <?php if (!empty($profile['industry'])): ?><strong>Svið:</strong> <?php echo App::sanitize($profile['industry']); ?><br><?php endif; ?>
                <?php if (!empty($profile['team_size'])): ?><strong>Teymisstærð:</strong> <?php echo App::sanitize($profile['team_size']); ?><br><?php endif; ?>
                <?php if (!empty($profile['responsibilities'])): ?><strong>Ábyrgðarsvið:</strong> <?php echo App::sanitize($profile['responsibilities']); ?><br><?php endif; ?>
                <?php if (!empty($profile['work_style'])): ?><strong>Vinnustíll:</strong> <?php echo App::sanitize($profile['work_style']); ?><br><?php endif; ?>
                <?php if (!empty($profile['context_notes'])): ?><strong>Sérstakar athugasemdir:</strong> <?php echo App::sanitize($profile['context_notes']); ?><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>