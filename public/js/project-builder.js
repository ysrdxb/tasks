/**
 * Enhanced Project Builder with Progressive Building, Smart Suggestions, and Validation
 */

// Global state
let currentStep = 1;
let projectData = {
    name: '',
    description: '',
    priority: 'medium',
    deadline: null,
    tags: [],
    tasks: [],
    ai_suggestions: null
};

// Enhanced Templates for Feature #2: Better Task Breakdown Assistant
const projectTemplates = {
    website: {
        name: 'Vefsíðuverkefni',
        description: 'Ný eða endurnýjuð vefsíða með nútímalegri hönnun og virkni',
        tasks: [
            { name: 'Skipuleggja innihald og skipulag', hours: 4, priority: 3, category: 'planning' },
            { name: 'Búa til wireframes og skissur', hours: 6, priority: 3, category: 'design' },
            { name: 'Hanna útlit og notendaviðmót', hours: 12, priority: 3, category: 'design' },
            { name: 'Þróa vefinn', hours: 20, priority: 3, category: 'development' },
            { name: 'Prófanir og villuleiðrétting', hours: 8, priority: 2, category: 'testing' },
            { name: 'Efnisinnsetning', hours: 4, priority: 2, category: 'content' },
            { name: 'Setja á loft og keyra', hours: 2, priority: 4, category: 'deployment' }
        ],
        estimated_weeks: 6,
        common_dependencies: {
            'design': ['planning'],
            'development': ['design'],
            'testing': ['development'],
            'deployment': ['testing']
        }
    },
    app: {
        name: 'Forritaverkefni',
        description: 'Þróun nýs forrits eða viðbót við núverandi kerfi',
        tasks: [
            { name: 'Kröfugreining og skipulagning', hours: 8, priority: 4 },
            { name: 'UI/UX hönnun', hours: 16, priority: 3 },
            { name: 'Bakendi þróun', hours: 24, priority: 4 },
            { name: 'Frontend þróun', hours: 20, priority: 3 },
            { name: 'API samþætting', hours: 12, priority: 3 },
            { name: 'Prófanir og quality assurance', hours: 16, priority: 3 },
            { name: 'Deployment og uppsetting', hours: 4, priority: 2 }
        ],
        estimated_weeks: 10
    },
    marketing: {
        name: 'Markaðsherferð',
        description: 'Skipulagning og framkvæmd markaðsherferðar',
        tasks: [
            { name: 'Markaðsrannsóknir', hours: 6, priority: 4 },
            { name: 'Stefnumótun og skipulagning', hours: 4, priority: 4 },
            { name: 'Efnisgerð og hönnun', hours: 12, priority: 3 },
            { name: 'Herferðaruppsetting', hours: 3, priority: 3 },
            { name: 'Keyrsla og eftirfylgni', hours: 8, priority: 2 },
            { name: 'Greining og skýrslugerð', hours: 4, priority: 2 }
        ],
        estimated_weeks: 4
    },
    research: {
        name: 'Rannsóknarverkefni',
        description: 'Upplýsingaöflun, greining og skýrslugerð',
        tasks: [
            { name: 'Skilgreina rannsóknarspurningar', hours: 3, priority: 4 },
            { name: 'Safna gögnum', hours: 12, priority: 3 },
            { name: 'Greina niðurstöður', hours: 8, priority: 3 },
            { name: 'Skrifa skýrslu', hours: 6, priority: 3 },
            { name: 'Kynna niðurstöður', hours: 2, priority: 2 }
        ],
        estimated_weeks: 3
    }
};

// Feature #2: Task Breakdown Assistant Patterns
const taskBreakdownPatterns = {
    // Common task categories with breakdown suggestions
    planning: {
        common_tasks: [
            'Kröfugreining og skipulagning',
            'Verkefnismótun og markmið',
            'Tímaáætlun og áfangar',
            'Teymissamstilling'
        ],
        typical_hours: [2, 4, 6],
        dependencies: []
    },
    design: {
        common_tasks: [
            'Wireframes og skissur',
            'UI/UX hönnun',
            'Útlitshönnun',
            'Notendaprófanir á hönnun'
        ],
        typical_hours: [4, 8, 12, 16],
        dependencies: ['planning']
    },
    development: {
        common_tasks: [
            'Grunnuppsetning verkefnis',
            'Bakendi þróun',
            'Frontend þróun',
            'API samþætting',
            'Gagnagrunnshönnun'
        ],
        typical_hours: [6, 12, 20, 24],
        dependencies: ['design']
    },
    testing: {
        common_tasks: [
            'Einingaprófanir',
            'Samþættingarprófanir',
            'Notendaprófanir',
            'Árasprófanir',
            'Villuleiðrétting'
        ],
        typical_hours: [4, 8, 12],
        dependencies: ['development']
    },
    content: {
        common_tasks: [
            'Efnisgerð',
            'Myndir og grafík',
            'Texti og afritun',
            'SEO bestun'
        ],
        typical_hours: [2, 4, 6, 8],
        dependencies: ['design']
    },
    deployment: {
        common_tasks: [
            'Vefhýsing uppsetning',
            'Domain uppsetning',
            'SSL vottorð',
            'Backup kerfi',
            'Monitoring'
        ],
        typical_hours: [1, 2, 4],
        dependencies: ['testing']
    }
};

// Smart task suggestions based on keywords
const taskSuggestionKeywords = {
    'vef': ['planning', 'design', 'development', 'testing', 'deployment'],
    'app': ['planning', 'design', 'development', 'testing', 'deployment'],
    'markaðs': ['planning', 'content', 'design'],
    'rannsókn': ['planning', 'content'],
    'hönnun': ['design'],
    'þróun': ['development'],
    'próf': ['testing']
};

// Feature #9: Enhanced Contextual Help During Card Creation
const contextualTips = {
    project_name: {
        title: 'Gott verkefnanafn:',
        tips: [
            'Skýrt og nákvæmt',
            'Nefnir helsta afrakstur',
            'Auðvelt að muna',
            'Forðast of almennar lýsingar'
        ],
        examples: [
            '✅ "Ný fyrirtækjavefsíða með netverslun"',
            '✅ "iOS forrit fyrir tímatak íþrótta"',
            '❌ "Nýtt verkefni"'
        ],
        dynamicTips: function(input) {
            const tips = [];
            if (input.length < 10) {
                tips.push('💡 Reyndu að vera nákvæmari');
            }
            if (!input.includes(' ')) {
                tips.push('💡 Bættu við fleiri orðum');
            }
            if (/^\w+\s+(app|forrit|vefsíða|kerfi)/i.test(input)) {
                tips.push('✅ Góð byrjun! Bættu við samhengi');
            }
            return tips;
        }
    },
    description: {
        title: 'Góð lýsing inniheldur:',
        tips: [
            'Markmið verkefnisins',
            'Helstu afrakstur',
            'Lykilhagsmunaaðila',
            'Sérstakar kröfur eða takmarkanir'
        ],
        examples: [
            '✅ "Búa til nútímalega vefsíðu fyrir viðskiptavini okkar með notendavænu viðmóti og greiðslukerfi. Markmiðið er að auka sölu um 30%."',
            '❌ "Búa til vefsíðu"'
        ],
        dynamicTips: function(input) {
            const tips = [];
            const hasGoal = /markmið|tilgang|markmiða/i.test(input);
            const hasDeliverable = /afhenda|afrakstur|deliver/i.test(input);
            const hasStakeholder = /viðskiptavinur|notandi|teymi/i.test(input);
            
            if (!hasGoal) tips.push('💡 Hvað er markmið verkefnisins?');
            if (!hasDeliverable) tips.push('💡 Hvað ætti að vera tilbúið í lokin?');
            if (!hasStakeholder) tips.push('💡 Hverjir eru notendurnir?');
            
            if (input.length > 100) {
                tips.push('✅ Góð nákvæmni!');
            }
            
            return tips;
        }
    },
    tasks: {
        title: 'Góðir verkþættir eru:',
        tips: [
            '2-8 klukkustundir að meðaltali',
            'Með skýrt markmið',
            'Með mælanlegan afrakstur',
            'Í rökréttri röðun'
        ],
        examples: [
            '✅ "Hanna notendaviðmót fyrir innskráningu"',
            '✅ "Prófa greiðslukerfi með þremur kortum"',
            '❌ "Gera allt"'
        ],
        dynamicTips: function(taskName) {
            const tips = [];
            if (taskName.length < 5) {
                tips.push('💡 Vertu nákvæmari');
            }
            if (/^(gera|búa til|þróa)\s*$/i.test(taskName)) {
                tips.push('💡 Hvað nákvæmlega á að gera?');
            }
            if (taskName.split(' ').length >= 4) {
                tips.push('✅ Góð lýsing!');
            }
            return tips;
        }
    },
    priority: {
        title: 'Forgangur verkefnis:',
        tips: [
            'Hátt - Lykilverkefni, brýn tímamörk',
            'Miðlungs - Venjulegur forgangur',
            'Lágt - Þegar tími gefst',
            'Brýnt - Þarf að vera tilbúið núna'
        ],
        examples: [
            'Spurnarorð: "Hvað gerist ef þetta er ekki tilbúið á réttum tíma?"'
        ],
        dynamicTips: function(priority, deadline) {
            const tips = [];
            if (priority === 'urgent' && !deadline) {
                tips.push('⚠️ Brýn verkefni þurfa yfirleitt skiladag');
            }
            if (priority === 'low' && deadline) {
                const deadlineDate = new Date(deadline);
                const now = new Date();
                const daysLeft = (deadlineDate - now) / (1000 * 60 * 60 * 24);
                if (daysLeft < 7) {
                    tips.push('⚠️ Stutt tímamörk fyrir "lágan" forgang');
                }
            }
            return tips;
        }
    },
    deadline: {
        title: 'Góður skiladagur:',
        tips: [
            'Raunhæfur miðað við umfang',
            'Tekur tillit til annarra verkefna',
            'Gefur svigrúm fyrir prófanir',
            'Samræmist hagsmunaaðilum'
        ],
        examples: [
            '💡 Almenna reglan: Áætlaðir tímar × 1.5 fyrir buffer'
        ],
        dynamicTips: function(deadline, estimatedHours) {
            const tips = [];
            const deadlineDate = new Date(deadline);
            const now = new Date();
            const daysLeft = Math.ceil((deadlineDate - now) / (1000 * 60 * 60 * 24));
            
            if (estimatedHours && daysLeft > 0) {
                const workDaysNeeded = Math.ceil(estimatedHours / 8);
                if (workDaysNeeded > daysLeft) {
                    tips.push('⚠️ Þetta gæti verið of þétt áætlun');
                } else if (workDaysNeeded < daysLeft / 2) {
                    tips.push('💡 Góður tími til að gera þetta vel');
                }
            }
            
            if (daysLeft < 0) {
                tips.push('❌ Þessi dagsetning er liðin');
            } else if (daysLeft < 7) {
                tips.push('⚠️ Mjög stuttur tími');
            }
            
            return tips;
        }
    }
};

// Progressive help that adapts to user's experience level
const helpProgression = {
    beginner: {
        verbosity: 'high',
        showExamples: true,
        showWarnings: true
    },
    intermediate: {
        verbosity: 'medium',
        showExamples: false,
        showWarnings: true
    },
    expert: {
        verbosity: 'low',
        showExamples: false,
        showWarnings: false
    }
};

// Track user's help interaction to adapt help level
let userHelpLevel = 'beginner';
let helpInteractions = 0;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeProjectBuilder();
});

function initializeProjectBuilder() {
    // Set up form field listeners
    document.getElementById('project_name').addEventListener('input', validateStep1);
    document.getElementById('project_description').addEventListener('input', validateStep1);
    
    // Set up task time radio button listeners
    document.querySelectorAll('input[name="taskTime"]').forEach(radio => {
        radio.addEventListener('change', handleTaskTimeChange);
    });
    
    // Initialize validation
    validateStep1();
}

// #2: Better Task Breakdown Assistant & #3: Smart Project Information Extraction
function handleProjectNameInput(value) {
    projectData.name = value;
    
    // Show contextual tips
    showContextualTip('project_name');
    
    // Extract information and suggest project type
    if (value.length > 3) {
        extractProjectInfo(value, '');
    }
    
    validateStep1();
}

function handleDescriptionInput(value) {
    projectData.description = value;
    
    // Show contextual tips
    showContextualTip('description');
    
    // Extract project information from description
    if (value.length > 10) {
        extractProjectInfo(projectData.name, value);
    }
    
    validateStep1();
}

// Feature #3: Enhanced Smart Project Information Extraction
function extractProjectInfo(name, description) {
    const combinedText = (name + ' ' + description).toLowerCase();
    
    // Extract suggested deadline with better parsing
    extractAndSuggestDeadline(combinedText);
    
    // Extract estimated hours from scope description
    extractEstimatedHours(combinedText);
    
    // Extract team size mentions
    extractTeamSize(combinedText);
    
    // Extract priority with more sophisticated detection
    extractPriorityLevel(combinedText);
    
    // Extract dependencies
    extractDependencies(combinedText);
    
    // Detect project type and suggest template
    detectProjectTypeAndSuggestTemplate(combinedText);
    
    // Extract budget indicators if mentioned
    extractBudgetIndicators(combinedText);
    
    // Show extraction results
    showExtractionResults();
}

function extractAndSuggestDeadline(text) {
    const deadlinePatterns = [
        /(til|fyrir|þarf að vera tilbúið|má vera tilbúið|deadline)\s*(.*?)(?:\s|$|\.)/,
        /(í\s*(janúar|febrúar|mars|apríl|maí|júní|júlí|ágúst|september|október|nóvember|desember))/,
        /((\d{1,2})\.\s*(janúar|febrúar|mars|apríl|maí|júní|júlí|ágúst|september|október|nóvember|desember))/,
        /(næsta\s*(viku|mánuði|ári))/,
        /(í\s*(\d{1,2})\s*(vikur|mánuði|dögum))/,
        /(loka\s*(ársins|mánaðarins|vikunnar))/
    ];

    for (const pattern of deadlinePatterns) {
        const match = text.match(pattern);
        if (match) {
            const deadlineText = match[0];
            const suggestedDate = parseDeadlineText(deadlineText);
            if (suggestedDate) {
                document.getElementById('project_deadline').value = suggestedDate.toISOString().split('T')[0];
                showSmartSuggestion('deadline', `Skiladagur greindur: ${deadlineText}`);
                break;
            }
        }
    }
}

function extractEstimatedHours(text) {
    const timePatterns = [
        /(\d{1,3})\s*(klst|klukkustund|tím)/,
        /(\d{1,2})\s*(vikur|viku)\s*verkefni/,
        /(\d{1,2})\s*(mánuð|mánuði)\s*verkefni/,
        /(lítið|stutt|fljót)\s*verkefni/,
        /(stórt|langt|umfangsmikið)\s*verkefni/,
        /(miðlungs|hefðbundið)\s*verkefni/
    ];

    for (const pattern of timePatterns) {
        const match = text.match(pattern);
        if (match) {
            let estimatedHours = 0;
            
            if (match[0].includes('klst') || match[0].includes('tím')) {
                estimatedHours = parseInt(match[1]);
            } else if (match[0].includes('viku')) {
                estimatedHours = parseInt(match[1]) * 40; // Full work week
            } else if (match[0].includes('mánuð')) {
                estimatedHours = parseInt(match[1]) * 160; // Full work month
            } else if (match[0].includes('lítið') || match[0].includes('fljót')) {
                estimatedHours = 20;
            } else if (match[0].includes('stórt') || match[0].includes('umfangsmikið')) {
                estimatedHours = 200;
            } else if (match[0].includes('miðlungs')) {
                estimatedHours = 80;
            }
            
            if (estimatedHours > 0) {
                projectData.estimated_hours = estimatedHours;
                showSmartSuggestion('hours', `Áætlaður tími greindur: ${estimatedHours} klst`);
                break;
            }
        }
    }
}

function extractTeamSize(text) {
    const teamPatterns = [
        /(\d{1,2})\s*(mann|menn|manneskjur|þróunaraðil)/,
        /(ein|einn)\s*(manneskja|þróunaraðil)/,
        /(lítill|stór)\s*(hóp|teymi)/,
        /(ég|við|okkar\s*teymi)/
    ];

    for (const pattern of teamPatterns) {
        const match = text.match(pattern);
        if (match) {
            let teamSize = 1;
            
            if (match[1] && !isNaN(match[1])) {
                teamSize = parseInt(match[1]);
            } else if (match[0].includes('ein')) {
                teamSize = 1;
            } else if (match[0].includes('lítill')) {
                teamSize = 3;
            } else if (match[0].includes('stór')) {
                teamSize = 8;
            } else if (match[0].includes('við')) {
                teamSize = 3;
            }
            
            projectData.team_size = teamSize;
            showSmartSuggestion('team', `Teymistærð greind: ${teamSize} ${teamSize === 1 ? 'manneskja' : 'manneskjur'}`);
            break;
        }
    }
}

function extractPriorityLevel(text) {
    const priorityKeywords = {
        urgent: ['brýnt', 'strax', 'flýti', 'urgent', 'immediately', 'á morgun', 'núna'],
        high: ['mikilvægt', 'hátt', 'forgangs', 'important', 'lykilverkefni', 'forgangur'],
        medium: ['miðlungs', 'venjulegur', 'hefðbundinn', 'normal'],
        low: ['ekki flýti', 'seinna', 'lágt', 'later', 'þegar tími gefst']
    };
    
    for (const [level, keywords] of Object.entries(priorityKeywords)) {
        if (keywords.some(keyword => text.includes(keyword))) {
            document.getElementById('project_priority').value = level;
            showSmartSuggestion('priority', `Forgangur greindur: ${getPriorityLabel(level)}`);
            break;
        }
    }
}

function extractDependencies(text) {
    const dependencyPatterns = [
        /(þarf|krefst|byggir á|fer eftir)\s*(.*?)(?:\s|$|\.)/,
        /(á undan|fyrst|áður en)/,
        /(API|gagnagrunnur|hönnun|samþykki)/
    ];

    const dependencies = [];
    for (const pattern of dependencyPatterns) {
        const matches = text.match(pattern);
        if (matches) {
            dependencies.push(matches[0]);
        }
    }
    
    if (dependencies.length > 0) {
        projectData.dependencies = dependencies;
        showSmartSuggestion('dependencies', `Ósjálfstæði greint: ${dependencies.join(', ')}`);
    }
}

function detectProjectTypeAndSuggestTemplate(text) {
    const typeKeywords = {
        website: ['vefsíða', 'vefur', 'heimasíða', 'website', 'vefhönnun', 'netverslun'],
        app: ['app', 'forrit', 'application', 'kerfi', 'mobile', 'iOS', 'Android'],
        marketing: ['markaðs', 'herferð', 'auglýsing', 'campaign', 'kynning', 'vörumerkj'],
        research: ['rannsókn', 'greining', 'könnun', 'research', 'skýrsla', 'gögn']
    };
    
    let detectedType = null;
    let confidence = 0;
    
    for (const [type, keywords] of Object.entries(typeKeywords)) {
        const matches = keywords.filter(keyword => text.includes(keyword));
        if (matches.length > 0) {
            const typeConfidence = matches.length / keywords.length;
            if (typeConfidence > confidence) {
                confidence = typeConfidence;
                detectedType = type;
            }
        }
    }
    
    if (detectedType && confidence > 0.1) {
        highlightTemplate(detectedType);
        showSmartSuggestion('template', `Verkefnistegund greind: ${getProjectTypeName(detectedType)} (${Math.round(confidence * 100)}% öryggi)`);
    }
}

function extractBudgetIndicators(text) {
    const budgetPatterns = [
        /(\d{1,3}(?:\.\d{3})*)\s*(krón|isk|usd|eur)/,
        /(lágt|hátt|takmarkað|ótakmarkað)\s*(fjárhagsáætlun|budget)/,
        /(ókeypis|frítt|án kostnaðar)/
    ];

    for (const pattern of budgetPatterns) {
        const match = text.match(pattern);
        if (match) {
            projectData.budget_info = match[0];
            showSmartSuggestion('budget', `Fjárhagsupplýsingar greindar: ${match[0]}`);
            break;
        }
    }
}

function parseDeadlineText(deadlineText) {
    const now = new Date();
    const months = {
        'janúar': 0, 'febrúar': 1, 'mars': 2, 'apríl': 3, 'maí': 4, 'júní': 5,
        'júlí': 6, 'ágúst': 7, 'september': 8, 'október': 9, 'nóvember': 10, 'desember': 11
    };
    
    // Check for specific months
    for (const [monthName, monthIndex] of Object.entries(months)) {
        if (deadlineText.includes(monthName)) {
            const year = now.getFullYear();
            const suggestedDate = new Date(year, monthIndex, 15);
            if (suggestedDate <= now) {
                suggestedDate.setFullYear(year + 1);
            }
            return suggestedDate;
        }
    }
    
    // Check for relative dates
    if (deadlineText.includes('næsta viku')) {
        const nextWeek = new Date(now);
        nextWeek.setDate(now.getDate() + 7);
        return nextWeek;
    }
    
    if (deadlineText.includes('næsta mánuði')) {
        const nextMonth = new Date(now);
        nextMonth.setMonth(now.getMonth() + 1);
        return nextMonth;
    }
    
    // Check for X weeks/days patterns
    const weekMatch = deadlineText.match(/(\d{1,2})\s*vikur/);
    if (weekMatch) {
        const weeks = parseInt(weekMatch[1]);
        const targetDate = new Date(now);
        targetDate.setDate(now.getDate() + (weeks * 7));
        return targetDate;
    }
    
    const dayMatch = deadlineText.match(/(\d{1,2})\s*dögum/);
    if (dayMatch) {
        const days = parseInt(dayMatch[1]);
        const targetDate = new Date(now);
        targetDate.setDate(now.getDate() + days);
        return targetDate;
    }
    
    return null;
}

function getProjectTypeName(type) {
    const names = {
        website: 'Vefsíðuverkefni',
        app: 'Forritaverkefni', 
        marketing: 'Markaðsverkefni',
        research: 'Rannsóknarverkefni'
    };
    return names[type] || type;
}

function showSmartSuggestion(type, message) {
    // Create or update smart suggestions panel
    let panel = document.getElementById('smartSuggestions');
    if (!panel) {
        panel = document.createElement('div');
        panel.id = 'smartSuggestions';
        panel.className = 'alert alert-info mt-3';
        panel.innerHTML = '<h6><i class="bi bi-lightbulb"></i> Greindar upplýsingar:</h6><div id="suggestionsList"></div>';
        
        const step1Card = document.querySelector('[data-step="1"] .card-body');
        step1Card.appendChild(panel);
    }
    
    const suggestionsList = document.getElementById('suggestionsList');
    const suggestionItem = document.createElement('div');
    suggestionItem.className = 'small mb-1';
    suggestionItem.innerHTML = `✓ ${message}`;
    suggestionsList.appendChild(suggestionItem);
    
    // Auto-hide after 10 seconds
    setTimeout(() => {
        if (suggestionItem.parentNode) {
            suggestionItem.style.opacity = '0.5';
        }
    }, 10000);
}

function showExtractionResults() {
    // Update any calculated fields or show summary
    if (projectData.estimated_hours) {
        const estimatedWeeks = Math.ceil(projectData.estimated_hours / 40);
        showSmartSuggestion('calculation', `Áætlaður verkefnistími: ${estimatedWeeks} ${estimatedWeeks === 1 ? 'vika' : 'vikur'}`);
    }
}

function suggestDeadline(dateText) {
    // Simple date parsing - could be enhanced
    const months = {
        'janúar': 0, 'febrúar': 1, 'mars': 2, 'apríl': 3, 'maí': 4, 'júní': 5,
        'júlí': 6, 'ágúst': 7, 'september': 8, 'október': 9, 'nóvember': 10, 'desember': 11
    };
    
    // Look for month names
    for (const [monthName, monthIndex] of Object.entries(months)) {
        if (dateText.includes(monthName)) {
            const year = new Date().getFullYear();
            const suggestedDate = new Date(year, monthIndex, 15); // Mid-month
            if (suggestedDate > new Date()) {
                document.getElementById('project_deadline').value = suggestedDate.toISOString().split('T')[0];
                break;
            }
        }
    }
}

function highlightTemplate(type) {
    // Highlight the relevant template button
    document.querySelectorAll('.template-buttons .btn').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-primary');
    });
    
    const templateBtn = document.querySelector(`[onclick="useTemplate('${type}')"]`);
    if (templateBtn) {
        templateBtn.classList.remove('btn-outline-primary');
        templateBtn.classList.add('btn-primary');
        
        // Show a subtle suggestion
        showTemplateSuggestion(type);
    }
}

function showTemplateSuggestion(type) {
    const template = projectTemplates[type];
    if (template) {
        const suggestion = document.createElement('div');
        suggestion.className = 'alert alert-info alert-dismissible fade show mt-2';
        suggestion.innerHTML = `
            <small>
                <i class="bi bi-lightbulb"></i> <strong>Tillaga:</strong> 
                Þetta lítur út fyrir ${template.name.toLowerCase()}. 
                Viltu nota sniðmát?
                <button type="button" class="btn btn-sm btn-primary ms-2" onclick="useTemplate('${type}')">Já</button>
            </small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.quick-templates');
        container.appendChild(suggestion);
        
        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (suggestion.parentNode) {
                suggestion.remove();
            }
        }, 10000);
    }
}

// Feature #9: Enhanced Contextual Help During Card Creation
function showContextualTip(context, inputValue = '') {
    helpInteractions++;
    
    // Adapt help level based on user experience
    if (helpInteractions > 20) {
        userHelpLevel = 'expert';
    } else if (helpInteractions > 10) {
        userHelpLevel = 'intermediate';
    }
    
    // Hide all tips
    document.querySelectorAll('.contextual-help .tip').forEach(tip => {
        tip.classList.remove('active');
    });
    
    // Generate dynamic help content
    const helpContent = generateDynamicHelp(context, inputValue);
    updateHelpPanel(context, helpContent);
}

function generateDynamicHelp(context, inputValue) {
    const tipData = contextualTips[context];
    if (!tipData) return null;
    
    const helpLevel = helpProgression[userHelpLevel];
    const content = {
        title: tipData.title,
        staticTips: tipData.tips,
        dynamicTips: [],
        examples: helpLevel.showExamples ? tipData.examples : [],
        warnings: []
    };
    
    // Generate dynamic tips based on current input
    if (tipData.dynamicTips && inputValue) {
        content.dynamicTips = tipData.dynamicTips(inputValue);
    }
    
    // Add context-specific dynamic suggestions
    if (context === 'priority') {
        const deadline = document.getElementById('project_deadline')?.value;
        if (tipData.dynamicTips) {
            content.dynamicTips = tipData.dynamicTips(inputValue, deadline);
        }
    }
    
    if (context === 'deadline') {
        const estimatedHours = projectData.estimated_hours || 0;
        if (tipData.dynamicTips) {
            content.dynamicTips = tipData.dynamicTips(inputValue, estimatedHours);
        }
    }
    
    return content;
}

function updateHelpPanel(context, content) {
    let helpPanel = document.querySelector('.contextual-help');
    if (!helpPanel) {
        createHelpPanel();
        helpPanel = document.querySelector('.contextual-help');
    }
    
    const helpLevel = helpProgression[userHelpLevel];
    
    let html = `
        <div class="card border-info">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">💡 ${content.title}</h6>
                <small>${userHelpLevel === 'beginner' ? '🎓' : userHelpLevel === 'intermediate' ? '⚡' : '🚀'}</small>
            </div>
            <div class="card-body">
    `;
    
    // Static tips (reduced for experienced users)
    if (helpLevel.verbosity !== 'low') {
        html += `
            <div class="static-tips mb-3">
                <ul class="small mb-0">
                    ${content.staticTips.slice(0, helpLevel.verbosity === 'high' ? 4 : 2).map(tip => `<li>${tip}</li>`).join('')}
                </ul>
            </div>
        `;
    }
    
    // Dynamic tips (always shown)
    if (content.dynamicTips.length > 0) {
        html += `
            <div class="dynamic-tips mb-3">
                ${content.dynamicTips.map(tip => `<div class="alert alert-sm p-2 mb-1" style="font-size: 0.8rem;">${tip}</div>`).join('')}
            </div>
        `;
    }
    
    // Examples (for beginners only)
    if (content.examples && content.examples.length > 0 && helpLevel.showExamples) {
        html += `
            <div class="examples">
                <h6 class="small fw-bold">Dæmi:</h6>
                <div class="small">
                    ${content.examples.map(example => `<div class="mb-1">${example}</div>`).join('')}
                </div>
            </div>
        `;
    }
    
    html += `
            </div>
        </div>
    `;
    
    helpPanel.innerHTML = html;
    
    // Show help panel with animation
    helpPanel.classList.add('active');
    setTimeout(() => helpPanel.style.opacity = '1', 50);
}

function createHelpPanel() {
    const step1Card = document.querySelector('[data-step="1"] .col-md-4');
    if (!step1Card) return;
    
    const helpPanel = document.createElement('div');
    helpPanel.className = 'contextual-help mt-3';
    helpPanel.style.opacity = '0';
    helpPanel.style.transition = 'opacity 0.3s ease';
    
    step1Card.appendChild(helpPanel);
}

// Enhanced input handlers with contextual help
function handleProjectNameInputWithHelp(value) {
    projectData.name = value;
    showContextualTip('project_name', value);
    
    // Existing functionality
    if (value.length > 3) {
        extractProjectInfo(value, '');
    }
    
    validateStep1();
}

function handleDescriptionInputWithHelp(value) {
    projectData.description = value;
    showContextualTip('description', value);
    
    // Existing functionality
    if (value.length > 10) {
        extractProjectInfo(projectData.name, value);
    }
    
    validateStep1();
}

// Help for other form elements
function showPriorityHelp() {
    const priority = document.getElementById('project_priority').value;
    showContextualTip('priority', priority);
}

function showDeadlineHelp() {
    const deadline = document.getElementById('project_deadline').value;
    showContextualTip('deadline', deadline);
}

// Smart help suggestions based on common mistakes
function detectCommonMistakes(context, value) {
    const mistakes = [];
    
    if (context === 'project_name') {
        if (value.toLowerCase().startsWith('new ') || value.toLowerCase().startsWith('nýtt ')) {
            mistakes.push('⚠️ Forðastu að byrja á "nýtt" - vertu nákvæmari');
        }
        if (value.length > 50) {
            mistakes.push('⚠️ Verkefnisnafn er kannski of langt');
        }
    }
    
    if (context === 'description') {
        if (value.length < 20 && value.length > 0) {
            mistakes.push('💡 Reyndu að vera nákvæmari');
        }
        if (!value.includes('.') && value.length > 30) {
            mistakes.push('💡 Skiptu lýsingunni í setningar');
        }
    }
    
    return mistakes;
}

// Template usage
function useTemplate(type) {
    const template = projectTemplates[type];
    if (!template) return;
    
    // Confirm if there's already content
    if (projectData.name && !confirm('Þetta mun skrifa yfir núverandi innihald. Halda áfram?')) {
        return;
    }
    
    // Apply template
    document.getElementById('project_name').value = template.name;
    document.getElementById('project_description').value = template.description;
    
    // Store template tasks for later
    projectData.templateTasks = template.tasks;
    projectData.name = template.name;
    projectData.description = template.description;
    
    // Show confirmation
    showSuccessMessage(`Sniðmát "${template.name}" valið! Verkþættir verða bættir við á næsta skrefi.`);
    
    validateStep1();
}

// Feature #6: Enhanced Card Validation & Improvement
function validateStep1() {
    const name = document.getElementById('project_name').value.trim();
    const description = document.getElementById('project_description').value.trim();
    
    const validation = [];
    let canProceed = false;
    let qualityScore = 0;
    
    // Validate project name with detailed feedback
    const nameValidation = validateProjectName(name);
    validation.push(...nameValidation.feedback);
    qualityScore += nameValidation.score;
    if (nameValidation.canProceed) canProceed = true;
    
    // Validate description with detailed feedback
    const descValidation = validateProjectDescription(description);
    validation.push(...descValidation.feedback);
    qualityScore += descValidation.score;
    
    // Advanced content analysis
    if (canProceed && description) {
        const contentAnalysis = analyzeProjectContent(name, description);
        validation.push(...contentAnalysis.suggestions);
        qualityScore += contentAnalysis.score;
    }
    
    // Overall quality assessment
    const qualityFeedback = getQualityFeedback(qualityScore);
    if (qualityFeedback) {
        validation.push(qualityFeedback);
    }
    
    // Update validation display with score
    updateValidationDisplayWithScore('step1Validation', validation, qualityScore);
    
    // Enable/disable next button
    document.getElementById('step1Next').disabled = !canProceed;
    
    return canProceed;
}

function validateProjectName(name) {
    const feedback = [];
    let score = 0;
    let canProceed = false;
    
    if (!name) {
        feedback.push({
            type: 'error',
            message: '❌ Verkefnisnafn er nauðsynlegt',
            action: 'Sláðu inn verkefnisnafn'
        });
    } else if (name.length < 3) {
        feedback.push({
            type: 'warning',
            message: '⚠️ Verkefnisnafn of stutt',
            action: 'Bættu við frekari upplýsingum'
        });
        score = 1;
    } else if (name.length < 8) {
        feedback.push({
            type: 'info',
            message: '💡 Gott verkefnisnafn',
            action: 'Íhugaðu að gera það enn lýsandi'
        });
        score = 2;
        canProceed = true;
    } else {
        // Analyze name quality
        const hasAction = /^\w+\s+(vefsíða|forrit|kerfi|app|hönnun)/i.test(name);
        const hasContext = name.split(' ').length >= 3;
        const isSpecific = !/^(nýtt?|gamla?|betra?)\s/i.test(name);
        
        if (hasAction && hasContext && isSpecific) {
            feedback.push({
                type: 'success',
                message: '✅ Frábært verkefnisnafn',
                action: 'Vel gert! Skýrt og lýsandi'
            });
            score = 4;
        } else {
            feedback.push({
                type: 'success',
                message: '✅ Gott verkefnisnafn',
                action: hasAction ? '' : 'Íhugaðu að nefna hvað þú ert að gera'
            });
            score = 3;
        }
        canProceed = true;
    }
    
    return { feedback, score, canProceed };
}

function validateProjectDescription(description) {
    const feedback = [];
    let score = 0;
    
    if (!description) {
        feedback.push({
            type: 'warning',
            message: '💡 Lýsing hjálpar teyminu að skilja verkefnið betur',
            action: 'Bættu við stuttri lýsingu'
        });
    } else if (description.length < 20) {
        feedback.push({
            type: 'warning',
            message: '💡 Nákvæmari lýsing hjálpar við skipulagningu',
            action: 'Bættu við frekari smáatriðum'
        });
        score = 1;
    } else {
        // Analyze description quality
        const hasGoal = /markmið|tilgang|markmiða|goal/i.test(description);
        const hasDeliverable = /afhenda|afrakstur|deliver|útkoma/i.test(description);
        const hasStakeholder = /viðskiptavinur|notandi|teymi|client|user/i.test(description);
        const hasConstraints = /takmarkun|krafa|deadline|fjárhagsáætlun/i.test(description);
        
        let qualityCount = 0;
        if (hasGoal) qualityCount++;
        if (hasDeliverable) qualityCount++;
        if (hasStakeholder) qualityCount++;
        if (hasConstraints) qualityCount++;
        
        if (qualityCount >= 3) {
            feedback.push({
                type: 'success',
                message: '✅ Frábær verkefnislýsing',
                action: 'Inniheldur markmið, afrakstur og hagsmunaaðila'
            });
            score = 4;
        } else if (qualityCount >= 2) {
            feedback.push({
                type: 'success',
                message: '✅ Góð verkefnislýsing',
                action: 'Íhugaðu að bæta við ' + getMissingElements(hasGoal, hasDeliverable, hasStakeholder, hasConstraints)
            });
            score = 3;
        } else {
            feedback.push({
                type: 'info',
                message: '✅ Lýsing skráð',
                action: 'Íhugaðu að nefna markmið og afrakstur'
            });
            score = 2;
        }
    }
    
    return { feedback, score };
}

function analyzeProjectContent(name, description) {
    const suggestions = [];
    let score = 0;
    
    const combinedText = (name + ' ' + description).toLowerCase();
    
    // Check for missing key elements
    const missingElements = [];
    
    if (!combinedText.includes('notandi') && !combinedText.includes('viðskiptavinur')) {
        missingElements.push('marknotendur');
    }
    
    if (!combinedText.includes('próf') && !combinedText.includes('test')) {
        missingElements.push('prófunaráætlun');
    }
    
    if (!combinedText.includes('tími') && !combinedText.includes('deadline')) {
        missingElements.push('tímaáætlun');
    }
    
    if (missingElements.length > 0) {
        suggestions.push({
            type: 'info',
            message: `💡 Íhugaðu að bæta við: ${missingElements.join(', ')}`,
            action: 'Þetta hjálpar við betri skipulagningu'
        });
    } else {
        score = 2;
    }
    
    // Check for complexity indicators
    const complexityIndicators = ['samþætting', 'api', 'gagnagrunnur', 'notendastjórnun', 'greiðslur'];
    const foundComplexity = complexityIndicators.filter(indicator => combinedText.includes(indicator));
    
    if (foundComplexity.length > 2) {
        suggestions.push({
            type: 'warning',
            message: '⚠️ Þetta lítur út fyrir flókið verkefni',
            action: 'Íhugaðu að skipta í smærri áfanga'
        });
    } else if (foundComplexity.length > 0) {
        suggestions.push({
            type: 'info',
            message: `💡 Greindar flóknar aðgerðir: ${foundComplexity.join(', ')}`,
            action: 'Mundu að skipuleggja þessa vel'
        });
        score += 1;
    }
    
    return { suggestions, score };
}

function getMissingElements(hasGoal, hasDeliverable, hasStakeholder, hasConstraints) {
    const missing = [];
    if (!hasGoal) missing.push('markmiðum');
    if (!hasDeliverable) missing.push('afrakstur');
    if (!hasStakeholder) missing.push('hagsmunaaðilum');
    if (!hasConstraints) missing.push('takmörkunum');
    
    return missing.slice(0, 2).join(' og ');
}

function getQualityFeedback(score) {
    if (score >= 8) {
        return {
            type: 'success',
            message: '🏆 Framúrskarandi verkefnisupplýsingar!',
            action: 'Þetta verkefni er vel skilgreint'
        };
    } else if (score >= 6) {
        return {
            type: 'success',
            message: '🎯 Mjög gott verkefni',
            action: 'Smávægilegar endurbætur mögulegar'
        };
    } else if (score >= 4) {
        return {
            type: 'info',
            message: '👍 Gott verkefni',
            action: 'Nokkrar endurbætur mögulegar'
        };
    } else if (score >= 2) {
        return {
            type: 'warning',
            message: '⚠️ Verkefni þarf frekari skilgreiningu',
            action: 'Bættu við frekari smáatriðum'
        };
    } else {
        return {
            type: 'error',
            message: '❌ Verkefni þarf meiri vinnu',
            action: 'Bættu við grunnupplýsingum'
        };
    }
}

function updateValidationDisplayWithScore(containerId, validationItems, score) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const maxScore = 10;
    const percentage = Math.min(100, (score / maxScore) * 100);
    
    let html = `
        <div class="validation-score mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold">Gæðaskor verkefnis:</span>
                <span class="badge bg-${getScoreBadgeColor(percentage)}">${Math.round(percentage)}%</span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-${getScoreBadgeColor(percentage)}" 
                     style="width: ${percentage}%" role="progressbar"></div>
            </div>
        </div>
    `;
    
    html += validationItems.map(item => `
        <div class="validation-item ${item.type}">
            <div class="validation-message">${item.message}</div>
            ${item.action ? `<div class="validation-action small mt-1">${item.action}</div>` : ''}
        </div>
    `).join('');
    
    container.innerHTML = html;
}

function getScoreBadgeColor(percentage) {
    if (percentage >= 80) return 'success';
    if (percentage >= 60) return 'info';
    if (percentage >= 40) return 'warning';
    return 'danger';
}

function updateValidationDisplay(containerId, validationItems) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = validationItems.map(item => `
        <div class="validation-item ${item.type}">
            ${item.message}
        </div>
    `).join('');
}

// Step navigation
function nextStep() {
    if (currentStep === 1) {
        if (!validateStep1()) return;
        currentStep = 2;
        showStep(2);
        startAIAnalysis();
    } else if (currentStep === 2) {
        currentStep = 3;
        showStep(3);
        populateTasksFromAI();
    } else if (currentStep === 3) {
        if (!validateTasks()) return;
        currentStep = 4;
        showStep(4);
        updateCalculatedFields();
    } else if (currentStep === 4) {
        currentStep = 5;
        showStep(5);
        generateProjectPreview();
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function showStep(step) {
    // Update progress steps
    document.querySelectorAll('.step').forEach((stepEl, index) => {
        stepEl.classList.remove('active', 'completed');
        if (index + 1 === step) {
            stepEl.classList.add('active');
        } else if (index + 1 < step) {
            stepEl.classList.add('completed');
        }
    });
    
    // Show/hide step content
    document.querySelectorAll('.card-builder-step').forEach(stepEl => {
        stepEl.classList.remove('active');
    });
    
    document.querySelector(`[data-step="${step}"]`).classList.add('active');
}

// AI Analysis (Step 2)
function startAIAnalysis() {
    // Simulate AI analysis
    setTimeout(() => {
        const analysis = generateAIAnalysis();
        projectData.ai_suggestions = analysis;
        showAIResults(analysis);
    }, 2000);
}

function generateAIAnalysis() {
    const name = projectData.name;
    const description = projectData.description;
    
    // Simple analysis based on content
    const analysis = {
        confidence: 0.85,
        estimated_complexity: 'medium',
        suggested_duration_weeks: 6,
        suggested_tasks: [],
        risks: [],
        suggestions: []
    };
    
    // Use template tasks if available, otherwise generate generic ones
    if (projectData.templateTasks) {
        analysis.suggested_tasks = projectData.templateTasks;
    } else {
        // Generate basic tasks based on keywords
        analysis.suggested_tasks = [
            { name: 'Skipulagning og undirbúningur', hours: 4, priority: 4 },
            { name: 'Framkvæmd aðalhluta', hours: 12, priority: 3 },
            { name: 'Prófanir og gæðaeftirlit', hours: 6, priority: 3 },
            { name: 'Lokafrágangur og afhending', hours: 3, priority: 2 }
        ];
    }
    
    // Add suggestions based on analysis
    if (analysis.suggested_tasks.length > 8) {
        analysis.suggestions.push('Íhugaðu að sameina sambærilega verkþætti');
    }
    
    if (!description.includes('próf')) {
        analysis.suggestions.push('Bættu við prófunarverkþáttum');
        analysis.risks.push('Engar prófanir nefndar - gæti leitt til gæðavandamála');
    }
    
    return analysis;
}

function showAIResults(analysis) {
    // Hide loading
    document.getElementById('aiAnalysisStatus').style.display = 'none';
    
    // Show suggestions
    document.getElementById('aiSuggestions').classList.remove('d-none');
    document.getElementById('step2Navigation').classList.remove('d-none');
    
    // Populate suggested structure
    const container = document.getElementById('suggestedStructure');
    container.innerHTML = analysis.suggested_tasks.map((task, index) => `
        <div class="suggested-task border rounded p-2 mb-2" data-task-index="${index}">
            <div class="d-flex align-items-center">
                <div class="form-check me-2">
                    <input class="form-check-input" type="checkbox" checked 
                           id="suggested_task_${index}" onchange="toggleSuggestedTask(${index})">
                </div>
                <div class="flex-grow-1">
                    <strong>${task.name}</strong>
                    <div class="text-muted small">
                        ${task.hours} klst • P${task.priority} forgangur
                    </div>
                </div>
                <div class="confidence-badge">
                    <span class="badge bg-success">85%</span>
                </div>
            </div>
        </div>
    `).join('');
    
    // Show analysis info
    document.getElementById('aiAnalysisInfo').innerHTML = `
        <div class="mb-2">
            <strong>AI öryggi:</strong>
            <span class="badge bg-success">${Math.round(analysis.confidence * 100)}%</span>
        </div>
        <div class="mb-2">
            <strong>Áætlað flækjustig:</strong>
            <span class="text-capitalize">${analysis.estimated_complexity}</span>
        </div>
        <div class="mb-2">
            <strong>Áætlaður tími:</strong>
            ${analysis.suggested_duration_weeks} vikur
        </div>
        <hr>
        <div class="small">
            <strong>Tillögur:</strong>
            <ul class="mb-0">
                ${analysis.suggestions.map(s => `<li>${s}</li>`).join('')}
            </ul>
        </div>
    `;
}

function toggleSuggestedTask(index) {
    const checkbox = document.getElementById(`suggested_task_${index}`);
    const taskEl = document.querySelector(`[data-task-index="${index}"]`);
    
    if (checkbox.checked) {
        taskEl.style.opacity = '1';
    } else {
        taskEl.style.opacity = '0.5';
    }
}

// Tasks Management (Step 3)
function populateTasksFromAI() {
    if (!projectData.ai_suggestions) return;
    
    // Clear existing tasks
    projectData.tasks = [];
    
    // Add selected AI suggested tasks
    document.querySelectorAll('#suggestedStructure input[type="checkbox"]:checked').forEach(checkbox => {
        const index = parseInt(checkbox.id.replace('suggested_task_', ''));
        const task = projectData.ai_suggestions.suggested_tasks[index];
        if (task) {
            projectData.tasks.push({
                id: Date.now() + Math.random(),
                name: task.name,
                description: '',
                hours: task.hours,
                priority: task.priority
            });
        }
    });
    
    updateTasksList();
    validateTasks();
}

function updateTasksList() {
    const container = document.getElementById('tasksList');
    
    if (projectData.tasks.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-plus-circle" style="font-size: 2rem;"></i>
                <p class="mt-2">Engir verkþættir ennþá</p>
                <button type="button" class="btn btn-outline-primary" onclick="addNewTask()">
                    <i class="bi bi-plus"></i> Bæta við verkþætti
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = projectData.tasks.map((task, index) => `
        <div class="task-item draggable" data-task-index="${index}">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-grip-vertical text-muted me-2 drag-handle"></i>
                        <h6 class="mb-0">${task.name}</h6>
                        ${task.category ? `<span class="badge bg-light text-dark ms-2 small">${getCategoryName(task.category)}</span>` : ''}
                    </div>
                    ${task.description ? `<p class="text-muted small mb-1 ms-4">${task.description}</p>` : ''}
                    <div class="d-flex gap-3 small text-muted ms-4">
                        <span><i class="bi bi-clock"></i> ${task.hours} klst</span>
                        <span><i class="bi bi-flag"></i> P${task.priority}</span>
                        ${task.estimatedComplexity ? `<span><i class="bi bi-gear"></i> ${task.estimatedComplexity}</span>` : ''}
                    </div>
                </div>
                <div class="task-actions">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="duplicateTask(${index})" title="Afrita verkþátt">
                            <i class="bi bi-copy"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editTask(${index})" title="Breyta verkþætti">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTask(${index})" title="Eyða verkþætti">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    // Enable drag and drop after updating the list
    setTimeout(() => enableTaskDragAndDrop(), 100);
}

// Feature #8: Enhanced Task Management Within Cards
function addNewTask() {
    document.getElementById('taskBuilder').style.display = 'block';
    document.getElementById('newTaskName').focus();
    
    // Show contextual tips for tasks
    showContextualTip('tasks');
    
    // Enable smart task suggestions as user types
    enableSmartTaskSuggestions();
}

function enableSmartTaskSuggestions() {
    const taskNameInput = document.getElementById('newTaskName');
    const suggestionContainer = createTaskSuggestionContainer();
    
    taskNameInput.addEventListener('input', function(e) {
        const query = e.target.value.trim().toLowerCase();
        if (query.length >= 2) {
            showTaskNameSuggestions(query, suggestionContainer);
        } else {
            suggestionContainer.style.display = 'none';
        }
    });
    
    // Hide suggestions when clicking elsewhere
    document.addEventListener('click', function(e) {
        if (!taskNameInput.contains(e.target) && !suggestionContainer.contains(e.target)) {
            suggestionContainer.style.display = 'none';
        }
    });
}

function createTaskSuggestionContainer() {
    let container = document.getElementById('taskSuggestions');
    if (!container) {
        container = document.createElement('div');
        container.id = 'taskSuggestions';
        container.className = 'task-suggestions-dropdown';
        container.style.display = 'none';
        
        const taskNameInput = document.getElementById('newTaskName');
        taskNameInput.parentNode.appendChild(container);
    }
    return container;
}

function showTaskNameSuggestions(query, container) {
    const suggestions = getTaskNameSuggestions(query);
    
    if (suggestions.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.innerHTML = suggestions.map((suggestion, index) => `
        <div class="task-suggestion-item" onclick="selectTaskSuggestion('${suggestion.name}', ${suggestion.hours}, ${suggestion.priority})">
            <div class="suggestion-name">${suggestion.name}</div>
            <div class="suggestion-meta">
                <span class="badge bg-secondary">${suggestion.hours}h</span>
                <span class="badge bg-info">P${suggestion.priority}</span>
                ${suggestion.category ? `<span class="badge bg-light text-dark">${getCategoryName(suggestion.category)}</span>` : ''}
            </div>
        </div>
    `).join('');
    
    container.style.display = 'block';
}

function getTaskNameSuggestions(query) {
    const allSuggestions = [];
    
    // Get suggestions from task breakdown patterns
    Object.entries(taskBreakdownPatterns).forEach(([category, pattern]) => {
        pattern.common_tasks.forEach(taskName => {
            if (taskName.toLowerCase().includes(query)) {
                const typicalHour = pattern.typical_hours[Math.floor(Math.random() * pattern.typical_hours.length)];
                allSuggestions.push({
                    name: taskName,
                    hours: typicalHour,
                    priority: 3,
                    category: category,
                    relevance: calculateRelevance(query, taskName.toLowerCase())
                });
            }
        });
    });
    
    // Add common project management tasks
    const commonTasks = [
        { name: 'Fundarskipulagning með teymi', hours: 1, priority: 2 },
        { name: 'Stöðuskýrsla til verkefnisstjóra', hours: 1, priority: 2 },
        { name: 'Viðskiptavinafundur', hours: 2, priority: 3 },
        { name: 'Kóðayfirferð', hours: 2, priority: 3 },
        { name: 'Backup og öryggisafrit', hours: 1, priority: 2 },
        { name: 'Vinnuskjöl og skjölun', hours: 3, priority: 2 }
    ];
    
    commonTasks.forEach(task => {
        if (task.name.toLowerCase().includes(query)) {
            allSuggestions.push({
                ...task,
                relevance: calculateRelevance(query, task.name.toLowerCase())
            });
        }
    });
    
    // Sort by relevance and return top 5
    return allSuggestions
        .sort((a, b) => b.relevance - a.relevance)
        .slice(0, 5);
}

function calculateRelevance(query, taskName) {
    const queryWords = query.split(' ');
    let relevance = 0;
    
    queryWords.forEach(word => {
        if (taskName.includes(word)) {
            relevance += word.length / taskName.length;
        }
        if (taskName.startsWith(word)) {
            relevance += 0.5;
        }
    });
    
    return relevance;
}

function selectTaskSuggestion(name, hours, priority) {
    document.getElementById('newTaskName').value = name;
    
    // Auto-select appropriate time button
    const timeButtons = document.querySelectorAll('input[name="taskTime"]');
    timeButtons.forEach(btn => {
        if (parseInt(btn.value) === hours) {
            btn.checked = true;
        }
    });
    
    // Auto-select appropriate priority
    const priorityButtons = document.querySelectorAll('input[name="taskPriority"]');
    priorityButtons.forEach(btn => {
        if (parseInt(btn.value) === priority) {
            btn.checked = true;
        }
    });
    
    // Hide suggestions
    document.getElementById('taskSuggestions').style.display = 'none';
    
    // Focus on description
    document.getElementById('newTaskDescription').focus();
}

// Enhanced task editing with inline editing
function editTask(index) {
    const task = projectData.tasks[index];
    if (!task) return;
    
    const taskElement = document.querySelector(`[data-task-index="${index}"]`);
    if (!taskElement) return;
    
    // Create inline editing interface
    const originalContent = taskElement.innerHTML;
    
    taskElement.innerHTML = `
        <div class="task-edit-form">
            <div class="mb-2">
                <input type="text" class="form-control form-control-sm" 
                       value="${task.name}" id="edit_task_name_${index}" placeholder="Nafn verkþáttar">
            </div>
            <div class="mb-2">
                <textarea class="form-control form-control-sm" rows="2" 
                          id="edit_task_desc_${index}" placeholder="Lýsing">${task.description || ''}</textarea>
            </div>
            <div class="row mb-2">
                <div class="col-6">
                    <label class="form-label small">Klukkustundir:</label>
                    <input type="number" class="form-control form-control-sm" 
                           value="${task.hours}" id="edit_task_hours_${index}" min="0.5" step="0.5">
                </div>
                <div class="col-6">
                    <label class="form-label small">Forgangur:</label>
                    <select class="form-select form-select-sm" id="edit_task_priority_${index}">
                        <option value="1" ${task.priority === 1 ? 'selected' : ''}>Lágur</option>
                        <option value="2" ${task.priority === 2 ? 'selected' : ''}>Miðlungs-lágur</option>
                        <option value="3" ${task.priority === 3 ? 'selected' : ''}>Miðlungs</option>
                        <option value="4" ${task.priority === 4 ? 'selected' : ''}>Hár</option>
                        <option value="5" ${task.priority === 5 ? 'selected' : ''}>Mjög hár</option>
                    </select>
                </div>
            </div>
            <div class="task-edit-actions">
                <button type="button" class="btn btn-success btn-sm" onclick="saveTaskEdit(${index})">
                    <i class="bi bi-check"></i> Vista
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="cancelTaskEdit(${index}, \`${originalContent.replace(/`/g, '\\`')}\`)">
                    <i class="bi bi-x"></i> Hætta við
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeTask(${index})">
                    <i class="bi bi-trash"></i> Eyða
                </button>
            </div>
        </div>
    `;
    
    // Focus on name input
    document.getElementById(`edit_task_name_${index}`).focus();
}

function saveTaskEdit(index) {
    const task = projectData.tasks[index];
    if (!task) return;
    
    // Get updated values
    const newName = document.getElementById(`edit_task_name_${index}`).value.trim();
    const newDesc = document.getElementById(`edit_task_desc_${index}`).value.trim();
    const newHours = parseFloat(document.getElementById(`edit_task_hours_${index}`).value) || task.hours;
    const newPriority = parseInt(document.getElementById(`edit_task_priority_${index}`).value) || task.priority;
    
    if (!newName) {
        alert('Verkþáttanafn er nauðsynlegt');
        return;
    }
    
    // Update task
    task.name = newName;
    task.description = newDesc;
    task.hours = newHours;
    task.priority = newPriority;
    
    // Refresh task list
    updateTasksList();
    validateTasks();
    
    showSuccessMessage('Verkþáttur uppfærður!');
}

function cancelTaskEdit(index, originalContent) {
    const taskElement = document.querySelector(`[data-task-index="${index}"]`);
    if (taskElement) {
        taskElement.innerHTML = originalContent;
    }
}

// Enhanced task duplication
function duplicateTask(index) {
    const task = projectData.tasks[index];
    if (!task) return;
    
    const duplicatedTask = {
        id: Date.now() + Math.random(),
        name: task.name + ' (afrit)',
        description: task.description,
        hours: task.hours,
        priority: task.priority,
        category: task.category
    };
    
    projectData.tasks.splice(index + 1, 0, duplicatedTask);
    updateTasksList();
    validateTasks();
    
    showSuccessMessage('Verkþáttur afritaður!');
}

// Drag and drop reordering
function enableTaskDragAndDrop() {
    const tasksList = document.getElementById('tasksList');
    
    // Make tasks sortable
    new Sortable(tasksList, {
        animation: 150,
        ghostClass: 'task-ghost',
        chosenClass: 'task-chosen',
        onEnd: function(evt) {
            // Reorder tasks in projectData
            const movedTask = projectData.tasks.splice(evt.oldIndex, 1)[0];
            projectData.tasks.splice(evt.newIndex, 0, movedTask);
            
            // Update display
            updateTasksList();
            showSuccessMessage('Verkþáttur endurraðaður!');
        }
    });
}

function handleTaskTimeChange() {
    const customOption = document.getElementById('timeCustom');
    const customInput = document.getElementById('customTimeInput');
    
    if (customOption.checked) {
        customInput.classList.remove('d-none');
        customInput.querySelector('input').focus();
    } else {
        customInput.classList.add('d-none');
    }
}

function saveNewTask() {
    const name = document.getElementById('newTaskName').value.trim();
    const description = document.getElementById('newTaskDescription').value.trim();
    
    if (!name) {
        alert('Verkþáttanafn er nauðsynlegt');
        return;
    }
    
    // Get selected time
    let hours = 4; // default
    const selectedTime = document.querySelector('input[name="taskTime"]:checked');
    if (selectedTime) {
        if (selectedTime.value === 'custom') {
            const customHours = document.querySelector('#customTimeInput input').value;
            hours = parseFloat(customHours) || 4;
        } else {
            hours = parseInt(selectedTime.value);
        }
    }
    
    // Get selected priority
    const selectedPriority = document.querySelector('input[name="taskPriority"]:checked');
    const priority = selectedPriority ? parseInt(selectedPriority.value) : 3;
    
    // Add task
    projectData.tasks.push({
        id: Date.now() + Math.random(),
        name: name,
        description: description,
        hours: hours,
        priority: priority
    });
    
    // Update display
    updateTasksList();
    validateTasks();
    
    // Clear form
    cancelNewTask();
    
    showSuccessMessage('Verkþáttur bætt við!');
}

function cancelNewTask() {
    document.getElementById('taskBuilder').style.display = 'none';
    document.getElementById('newTaskName').value = '';
    document.getElementById('newTaskDescription').value = '';
    
    // Reset radio buttons
    document.querySelector('#prioMedium').checked = true;
    document.querySelector('#time4h').checked = true;
    document.getElementById('customTimeInput').classList.add('d-none');
}

function editTask(index) {
    const task = projectData.tasks[index];
    if (!task) return;
    
    // For now, simple prompt-based editing
    const newName = prompt('Nafn verkþáttar:', task.name);
    if (newName && newName.trim()) {
        task.name = newName.trim();
        updateTasksList();
        validateTasks();
    }
}

function removeTask(index) {
    if (confirm('Ertu viss um að þú viljir fjarlægja þennan verkþátt?')) {
        projectData.tasks.splice(index, 1);
        updateTasksList();
        validateTasks();
    }
}

// Feature #2: Better Task Breakdown Assistant
function intelligentTaskBreakdown(projectName, description) {
    const combinedText = (projectName + ' ' + description).toLowerCase();
    const suggestedCategories = [];
    
    // Detect relevant categories based on keywords
    for (const [keyword, categories] of Object.entries(taskSuggestionKeywords)) {
        if (combinedText.includes(keyword)) {
            categories.forEach(cat => {
                if (!suggestedCategories.includes(cat)) {
                    suggestedCategories.push(cat);
                }
            });
        }
    }
    
    // Default categories if nothing detected
    if (suggestedCategories.length === 0) {
        suggestedCategories.push('planning', 'development', 'testing');
    }
    
    // Generate suggested tasks
    const suggestedTasks = [];
    suggestedCategories.forEach(category => {
        const pattern = taskBreakdownPatterns[category];
        if (pattern) {
            pattern.common_tasks.forEach(taskName => {
                const typicalHour = pattern.typical_hours[Math.floor(Math.random() * pattern.typical_hours.length)];
                suggestedTasks.push({
                    name: taskName,
                    category: category,
                    hours: typicalHour,
                    priority: 3,
                    dependencies: pattern.dependencies
                });
            });
        }
    });
    
    return {
        categories: suggestedCategories,
        tasks: suggestedTasks,
        confidence: 0.8
    };
}

function suggestTaskBreakdown() {
    if (!projectData.name) {
        showInfoMessage('Vinsamlegast sláðu inn verkefnisnafn fyrst');
        return;
    }
    
    const breakdown = intelligentTaskBreakdown(projectData.name, projectData.description);
    
    // Show breakdown suggestions modal
    showTaskBreakdownModal(breakdown);
}

function showTaskBreakdownModal(breakdown) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">🧠 Tillögur að verkþáttum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Byggt á verkefnislýsingu þinni, hér eru tillögur að verkþáttum:</p>
                    
                    <div class="suggested-breakdown">
                        ${breakdown.categories.map(category => `
                            <div class="category-section mb-4">
                                <h6>${getCategoryName(category)}</h6>
                                ${breakdown.tasks.filter(task => task.category === category).map((task, index) => `
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" checked 
                                               id="breakdown_task_${index}" data-task='${JSON.stringify(task)}'>
                                        <label class="form-check-label" for="breakdown_task_${index}">
                                            <strong>${task.name}</strong>
                                            <small class="text-muted ms-2">(${task.hours}h)</small>
                                        </label>
                                    </div>
                                `).join('')}
                            </div>
                        `).join('')}
                    </div>
                    
                    <div class="alert alert-info">
                        <small>💡 Þú getur breytt þessum tillögum á næsta skrefi</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hætta við</button>
                    <button type="button" class="btn btn-primary" onclick="applyTaskBreakdown()">
                        Nota valda verkþætti
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Clean up when modal is hidden
    modal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modal);
    });
}

function getCategoryName(category) {
    const names = {
        planning: '📋 Skipulagning',
        design: '🎨 Hönnun',
        development: '💻 Þróun',
        testing: '🧪 Prófanir',
        content: '📝 Efnisgerð',
        deployment: '🚀 Uppsetnig'
    };
    return names[category] || category;
}

function applyTaskBreakdown() {
    const selectedTasks = [];
    document.querySelectorAll('#suggestedBreakdown input[type="checkbox"]:checked').forEach(checkbox => {
        const taskData = JSON.parse(checkbox.dataset.task);
        selectedTasks.push({
            id: Date.now() + Math.random(),
            name: taskData.name,
            description: '',
            hours: taskData.hours,
            priority: taskData.priority,
            category: taskData.category
        });
    });
    
    // Add to project data
    projectData.tasks = [...projectData.tasks, ...selectedTasks];
    updateTasksList();
    validateTasks();
    
    // Close modal
    const modal = document.querySelector('.modal.show');
    if (modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        bsModal.hide();
    }
    
    showSuccessMessage(`${selectedTasks.length} verkþættir bættir við!`);
}

// #11: Quick Actions for Card Improvement
function addCommonTasks() {
    const commonTasks = [
        { name: 'Verkefnaskipulagning og undirbúningur', hours: 3, priority: 4 },
        { name: 'Gæðaeftirlit og prófanir', hours: 4, priority: 3 },
        { name: 'Skjölun og afhending', hours: 2, priority: 2 },
        { name: 'Endurskoðun og endurbætur', hours: 3, priority: 2 }
    ];
    
    // Add tasks that don't already exist
    commonTasks.forEach(newTask => {
        const exists = projectData.tasks.some(task => 
            task.name.toLowerCase().includes(newTask.name.toLowerCase().slice(0, 10))
        );
        
        if (!exists) {
            projectData.tasks.push({
                id: Date.now() + Math.random(),
                ...newTask
            });
        }
    });
    
    updateTasksList();
    validateTasks();
    showSuccessMessage('Algengir verkþættir bættir við!');
}

function splitLargeTasks() {
    const largeTasks = projectData.tasks.filter(task => task.hours > 8);
    
    if (largeTasks.length === 0) {
        showInfoMessage('Engir stórir verkþættir fundust (>8 klst)');
        return;
    }
    
    largeTasks.forEach(task => {
        if (confirm(`Skipta "${task.name}" (${task.hours} klst) í smærri verkþætti?`)) {
            const parts = Math.ceil(task.hours / 6); // Split into ~6 hour chunks
            const hoursPerPart = Math.round(task.hours / parts);
            
            // Remove original task
            const index = projectData.tasks.indexOf(task);
            projectData.tasks.splice(index, 1);
            
            // Add split tasks
            for (let i = 1; i <= parts; i++) {
                projectData.tasks.push({
                    id: Date.now() + Math.random() + i,
                    name: `${task.name} - Hluti ${i}`,
                    description: task.description,
                    hours: hoursPerPart,
                    priority: task.priority
                });
            }
        }
    });
    
    updateTasksList();
    validateTasks();
    showSuccessMessage('Stórir verkþættir skiptir upp!');
}

function addRealisticTimes() {
    let updated = 0;
    
    projectData.tasks.forEach(task => {
        if (task.hours === 0 || !task.hours) {
            // Estimate based on task name/complexity
            const name = task.name.toLowerCase();
            
            if (name.includes('skipulag') || name.includes('undirbún')) {
                task.hours = 3;
            } else if (name.includes('próf') || name.includes('gæða')) {
                task.hours = 4;
            } else if (name.includes('þróun') || name.includes('smíð')) {
                task.hours = 8;
            } else if (name.includes('hönnun') || name.includes('design')) {
                task.hours = 6;
            } else {
                task.hours = 4; // Default
            }
            
            updated++;
        }
    });
    
    if (updated > 0) {
        updateTasksList();
        validateTasks();
        showSuccessMessage(`Tímaáætlanir bættar við ${updated} verkþætti!`);
    } else {
        showInfoMessage('Allir verkþættir hafa nú þegar tímaáætlanir');
    }
}

function suggestTaskOrder() {
    // Simple ordering by priority then by logical flow
    const orderMap = {
        'skipulag': 1,
        'undirbún': 1,
        'hönnun': 2,
        'design': 2,
        'þróun': 3,
        'framkvæm': 3,
        'próf': 4,
        'gæða': 4,
        'skjölun': 5,
        'afhend': 5
    };
    
    projectData.tasks.sort((a, b) => {
        // First by logical order
        const aOrder = getTaskOrder(a.name, orderMap);
        const bOrder = getTaskOrder(b.name, orderMap);
        
        if (aOrder !== bOrder) {
            return aOrder - bOrder;
        }
        
        // Then by priority (higher priority first)
        return b.priority - a.priority;
    });
    
    updateTasksList();
    showSuccessMessage('Verkþættir raðaðir í rökrétta röð!');
}

function getTaskOrder(taskName, orderMap) {
    const name = taskName.toLowerCase();
    for (const [keyword, order] of Object.entries(orderMap)) {
        if (name.includes(keyword)) {
            return order;
        }
    }
    return 3; // Default middle order
}

// #6: Card Validation & Improvement (Tasks)
function validateTasks() {
    const validation = [];
    let canProceed = true;
    
    if (projectData.tasks.length === 0) {
        validation.push({
            type: 'error',
            message: '❌ Verkefni þarf að hafa að minnsta kosti einn verkþátt'
        });
        canProceed = false;
    } else {
        validation.push({
            type: 'success',
            message: `✅ ${projectData.tasks.length} verkþættir skilgreindir`
        });
        
        // Check for tasks without time estimates
        const noTimeTask = projectData.tasks.filter(task => !task.hours || task.hours === 0);
        if (noTimeTask.length > 0) {
            validation.push({
                type: 'warning',
                message: `⚠️ ${noTimeTask.length} verkþættir án tímaáætlunar`
            });
        }
        
        // Check for very large tasks
        const largeTasks = projectData.tasks.filter(task => task.hours > 12);
        if (largeTasks.length > 0) {
            validation.push({
                type: 'warning',
                message: `💡 ${largeTasks.length} verkþættir eru mjög stórir (>12 klst) - íhugaðu að skipta þeim upp`
            });
        }
        
        // Check task distribution
        const totalHours = projectData.tasks.reduce((sum, task) => sum + (task.hours || 0), 0);
        if (totalHours > 100) {
            validation.push({
                type: 'info',
                message: `📊 Heildarverkefni: ${totalHours} klst (${Math.round(totalHours/8)} vinnudagar)`
            });
        }
    }
    
    updateValidationDisplay('taskValidation', validation);
    return canProceed;
}

// Step 4: Update calculated fields
function updateCalculatedFields() {
    const totalHours = projectData.tasks.reduce((sum, task) => sum + (task.hours || 0), 0);
    const totalTasks = projectData.tasks.length;
    const estimatedDays = Math.ceil(totalHours / 8);
    const estimatedWeeks = Math.ceil(estimatedDays / 5);
    
    document.getElementById('totalEstimatedTime').textContent = `${totalHours} klst`;
    document.getElementById('totalTasks').textContent = totalTasks;
    document.getElementById('estimatedDuration').textContent = `${estimatedWeeks} ${estimatedWeeks === 1 ? 'vika' : 'vikur'}`;
    
    // Store in project data
    projectData.estimated_hours = totalHours;
    projectData.estimated_duration_weeks = estimatedWeeks;
}

// #10: Card Preview & Refinement (Step 5)
function generateProjectPreview() {
    collectFinalProjectData();
    
    const preview = document.getElementById('projectPreview');
    const totalHours = projectData.tasks.reduce((sum, task) => sum + (task.hours || 0), 0);
    
    preview.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">${projectData.name}</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">${projectData.description}</p>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <strong>Forgangur:</strong>
                                <span class="badge bg-${getPriorityColor(projectData.priority)} ms-2">
                                    ${getPriorityLabel(projectData.priority)}
                                </span>
                            </div>
                            <div class="col-sm-6">
                                <strong>Skiladagur:</strong>
                                <span class="ms-2">${projectData.deadline || 'Ekki skilgreindur'}</span>
                            </div>
                        </div>
                        
                        <h6>Verkþættir (${projectData.tasks.length}):</h6>
                        <div class="tasks-preview">
                            ${projectData.tasks.map((task, index) => `
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <strong>${task.name}</strong>
                                        ${task.description ? `<br><small class="text-muted">${task.description}</small>` : ''}
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary">P${task.priority}</span>
                                        <small class="text-muted ms-2">${task.hours}h</small>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        
                        ${projectData.tags.length > 0 ? `
                            <div class="mt-3">
                                <strong>Merki:</strong>
                                ${projectData.tags.map(tag => `<span class="badge bg-light text-dark ms-1">${tag}</span>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">📊 Yfirlit</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Heildarverkþættir:</strong>
                            <span class="text-primary">${projectData.tasks.length}</span>
                        </div>
                        <div class="mb-2">
                            <strong>Áætlaður tími:</strong>
                            <span class="text-primary">${totalHours} klst</span>
                        </div>
                        <div class="mb-2">
                            <strong>Áætluð tímalengd:</strong>
                            <span class="text-primary">${projectData.estimated_duration_weeks} vikur</span>
                        </div>
                        <div class="mb-2">
                            <strong>Meðaltími á verkþátt:</strong>
                            <span class="text-primary">${Math.round(totalHours / projectData.tasks.length)} klst</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    generateFinalValidation();
    generateFinalSuggestions();
}

function generateFinalValidation() {
    const validation = [];
    
    // Check all requirements
    if (projectData.name && projectData.tasks.length > 0) {
        validation.push({
            type: 'success',
            message: '✅ Verkefni hefur öll nauðsynleg gögn'
        });
    }
    
    if (projectData.description && projectData.description.length > 20) {
        validation.push({
            type: 'success',
            message: '✅ Góð verkefnislýsing'
        });
    }
    
    if (projectData.deadline) {
        validation.push({
            type: 'success',
            message: '✅ Skiladagur skilgreindur'
        });
    }
    
    const tasksWithTime = projectData.tasks.filter(task => task.hours > 0);
    if (tasksWithTime.length === projectData.tasks.length) {
        validation.push({
            type: 'success',
            message: '✅ Allir verkþættir hafa tímaáætlanir'
        });
    }
    
    updateValidationDisplay('finalValidation', validation);
}

// Feature #10: Enhanced Card Preview & Refinement
function generateFinalSuggestions() {
    const suggestions = [];
    const warnings = [];
    const improvements = [];
    
    // Comprehensive project analysis
    const analysis = performDetailedProjectAnalysis();
    
    // Critical issues (must be addressed)
    if (analysis.criticalIssues.length > 0) {
        warnings.push(...analysis.criticalIssues);
    }
    
    // Improvement opportunities
    if (analysis.improvements.length > 0) {
        improvements.push(...analysis.improvements);
    }
    
    // General suggestions
    if (analysis.suggestions.length > 0) {
        suggestions.push(...analysis.suggestions);
    }
    
    // Generate actionable recommendations
    const recommendations = generateActionableRecommendations(analysis);
    
    // Update suggestions display with categories
    updateSuggestionsDisplay(warnings, improvements, suggestions, recommendations);
}

function performDetailedProjectAnalysis() {
    const analysis = {
        criticalIssues: [],
        improvements: [],
        suggestions: [],
        score: 0,
        metrics: {}
    };
    
    const totalHours = projectData.tasks.reduce((sum, task) => sum + (task.hours || 0), 0);
    const totalTasks = projectData.tasks.length;
    
    // Calculate project metrics
    analysis.metrics = {
        totalHours: totalHours,
        totalTasks: totalTasks,
        averageTaskSize: totalTasks > 0 ? totalHours / totalTasks : 0,
        estimatedDays: Math.ceil(totalHours / 8),
        complexity: calculateProjectComplexity()
    };
    
    // Check for critical issues
    if (!projectData.name || projectData.name.length < 3) {
        analysis.criticalIssues.push('❌ Verkefnisnafn er of stutt eða vantar');
    }
    
    if (totalTasks === 0) {
        analysis.criticalIssues.push('❌ Verkefni þarf að hafa að minnsta kosti einn verkþátt');
    }
    
    if (projectData.deadline) {
        const deadlineDate = new Date(projectData.deadline);
        const now = new Date();
        const daysLeft = Math.ceil((deadlineDate - now) / (1000 * 60 * 60 * 24));
        const workDaysNeeded = Math.ceil(totalHours / 8);
        
        if (daysLeft < 0) {
            analysis.criticalIssues.push('❌ Skiladagur er liðinn');
        } else if (workDaysNeeded > daysLeft) {
            analysis.criticalIssues.push('⚠️ Ekki nægjanlegt tími til að klára verkefnið');
        }
    }
    
    // Check for improvements
    if (!projectData.deadline) {
        analysis.improvements.push('📅 Bættu við skiladegi fyrir betri skipulagningu');
    }
    
    if (!projectData.description || projectData.description.length < 50) {
        analysis.improvements.push('📝 Nákvæmari lýsing hjálpar teyminu');
    }
    
    if (projectData.tags.length === 0) {
        analysis.improvements.push('🏷️ Bættu við merkjum fyrir betri skipulagningu');
    }
    
    // Task-related improvements
    const tasksWithoutTime = projectData.tasks.filter(task => !task.hours || task.hours === 0);
    if (tasksWithoutTime.length > 0) {
        analysis.improvements.push(`⏰ ${tasksWithoutTime.length} verkþættir vantar tímaáætlun`);
    }
    
    const largeTasks = projectData.tasks.filter(task => task.hours > 12);
    if (largeTasks.length > 0) {
        analysis.improvements.push(`✂️ ${largeTasks.length} verkþættir eru mjög stórir (>12h) - íhugaðu að skipta þeim`);
    }
    
    // General suggestions
    if (totalHours > 160) {
        analysis.suggestions.push('💡 Þetta er mjög stórt verkefni - íhugaðu að skipta í áfanga');
    }
    
    const highPriorityTasks = projectData.tasks.filter(task => task.priority >= 4);
    if (highPriorityTasks.length > totalTasks * 0.6) {
        analysis.suggestions.push('💡 Margir verkþættir eru með háan forgang - endurskoðaðu forgangsröðun');
    }
    
    if (analysis.metrics.averageTaskSize < 2) {
        analysis.suggestions.push('💡 Verkþættir eru frekar smáir - íhugaðu að sameina suma');
    } else if (analysis.metrics.averageTaskSize > 10) {
        analysis.suggestions.push('💡 Verkþættir eru frekar stórir - íhugaðu að skipta þeim upp');
    }
    
    // Calculate overall score
    analysis.score = calculateProjectScore(analysis);
    
    return analysis;
}

function calculateProjectComplexity() {
    const description = (projectData.name + ' ' + projectData.description).toLowerCase();
    let complexity = 1;
    
    const complexityIndicators = [
        'api', 'gagnagrunnur', 'samþætting', 'greiðslur', 'notendastjórnun',
        'öryggis', 'skálun', 'móbíl', 'real-time', 'analytics'
    ];
    
    complexityIndicators.forEach(indicator => {
        if (description.includes(indicator)) {
            complexity += 0.3;
        }
    });
    
    // Factor in number of tasks
    if (projectData.tasks.length > 15) complexity += 0.5;
    if (projectData.tasks.length > 25) complexity += 0.5;
    
    return Math.min(3, complexity);
}

function calculateProjectScore(analysis) {
    let score = 5; // Start with base score
    
    // Deduct for critical issues
    score -= analysis.criticalIssues.length * 2;
    
    // Deduct for missing improvements
    score -= analysis.improvements.length * 0.5;
    
    // Add for good practices
    if (projectData.deadline) score += 1;
    if (projectData.description && projectData.description.length > 50) score += 1;
    if (projectData.tags.length > 0) score += 0.5;
    if (projectData.tasks.length >= 3 && projectData.tasks.length <= 15) score += 1;
    
    return Math.max(0, Math.min(10, score));
}

function generateActionableRecommendations(analysis) {
    const recommendations = [];
    
    if (analysis.score < 6) {
        recommendations.push({
            type: 'critical',
            title: 'Verkefni þarf frekari vinnu',
            actions: [
                'Farðu til baka og lagfærðu rauða atriði',
                'Bættu við tímaáætlunum fyrir alla verkþætti',
                'Vertu nákvæmari í verkefnislýsingu'
            ]
        });
    } else if (analysis.score < 8) {
        recommendations.push({
            type: 'improvement',
            title: 'Verkefni er nær að vera tilbúið',
            actions: [
                'Íhugaðu að bæta við skiladegi',
                'Bættu við merkjum',
                'Endurskoðaðu verkþættir fyrir stærð'
            ]
        });
    } else {
        recommendations.push({
            type: 'success',
            title: 'Frábært verkefni!',
            actions: [
                'Verkefnið er vel skilgreint',
                'Tilbúið til að hefja vinnu',
                'Mundu að uppfæra framvindu reglulega'
            ]
        });
    }
    
    return recommendations;
}

function updateSuggestionsDisplay(warnings, improvements, suggestions, recommendations) {
    let html = '';
    
    // Show overall score
    const analysis = performDetailedProjectAnalysis();
    const scorePercentage = (analysis.score / 10) * 100;
    
    html += `
        <div class="project-score mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Heildareinkunn verkefnis</h6>
                <span class="badge bg-${getScoreBadgeColor(scorePercentage)} fs-6">${Math.round(scorePercentage)}%</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-${getScoreBadgeColor(scorePercentage)}" 
                     style="width: ${scorePercentage}%" role="progressbar"></div>
            </div>
        </div>
    `;
    
    // Critical warnings
    if (warnings.length > 0) {
        html += `
            <div class="alert alert-danger">
                <h6 class="alert-heading">🚨 Þarf að laga</h6>
                ${warnings.map(w => `<div>${w}</div>`).join('')}
            </div>
        `;
    }
    
    // Improvements
    if (improvements.length > 0) {
        html += `
            <div class="alert alert-warning">
                <h6 class="alert-heading">⚡ Endurbætur</h6>
                ${improvements.map(i => `<div>${i}</div>`).join('')}
            </div>
        `;
    }
    
    // General suggestions
    if (suggestions.length > 0) {
        html += `
            <div class="alert alert-info">
                <h6 class="alert-heading">💡 Tillögur</h6>
                ${suggestions.map(s => `<div>${s}</div>`).join('')}
            </div>
        `;
    }
    
    // Actionable recommendations
    recommendations.forEach(rec => {
        const alertClass = rec.type === 'critical' ? 'alert-danger' : 
                          rec.type === 'improvement' ? 'alert-warning' : 'alert-success';
        
        html += `
            <div class="alert ${alertClass}">
                <h6 class="alert-heading">${rec.title}</h6>
                <ul class="mb-0">
                    ${rec.actions.map(action => `<li>${action}</li>`).join('')}
                </ul>
            </div>
        `;
    });
    
    // Quick fix buttons
    html += generateQuickFixButtons(warnings, improvements);
    
    document.getElementById('finalSuggestions').innerHTML = html;
}

function generateQuickFixButtons(warnings, improvements) {
    let html = '<div class="quick-fixes mt-3"><h6>Flýtilausnir:</h6><div class="d-flex flex-wrap gap-2">';
    
    if (!projectData.deadline) {
        html += '<button class="btn btn-sm btn-outline-primary" onclick="suggestRealisticDeadline()">📅 Leggja til skiladag</button>';
    }
    
    if (projectData.tags.length === 0) {
        html += '<button class="btn btn-sm btn-outline-primary" onclick="suggestProjectTags()">🏷️ Leggja til merki</button>';
    }
    
    const tasksWithoutTime = projectData.tasks.filter(task => !task.hours || task.hours === 0);
    if (tasksWithoutTime.length > 0) {
        html += '<button class="btn btn-sm btn-outline-warning" onclick="addRealisticTimes()">⏰ Bæta við tímaáætlunum</button>';
    }
    
    const largeTasks = projectData.tasks.filter(task => task.hours > 12);
    if (largeTasks.length > 0) {
        html += '<button class="btn btn-sm btn-outline-warning" onclick="splitLargeTasks()">✂️ Skipta stórum verkþáttum</button>';
    }
    
    html += '</div></div>';
    return html;
}

function suggestRealisticDeadline() {
    const totalHours = projectData.tasks.reduce((sum, task) => sum + (task.hours || 0), 0);
    const workDaysNeeded = Math.ceil(totalHours / 8);
    const bufferDays = Math.ceil(workDaysNeeded * 0.3); // 30% buffer
    const totalDays = workDaysNeeded + bufferDays;
    
    const suggestedDate = new Date();
    suggestedDate.setDate(suggestedDate.getDate() + totalDays);
    
    document.getElementById('project_deadline').value = suggestedDate.toISOString().split('T')[0];
    showSuccessMessage(`Skiladagur lagður til: ${suggestedDate.toLocaleDateString('is-IS')} (${workDaysNeeded} vinnudagar + ${bufferDays} dagar buffer)`);
    
    // Regenerate suggestions
    generateFinalSuggestions();
}

function suggestProjectTags() {
    const description = (projectData.name + ' ' + projectData.description).toLowerCase();
    const suggestedTags = [];
    
    const tagMapping = {
        'vef': 'vefþróun',
        'app': 'forritun',
        'design': 'hönnun',
        'markaðs': 'markaðssetning',
        'rannsókn': 'rannsóknir',
        'api': 'API',
        'mobile': 'farsímar',
        'greiðsl': 'greiðslur',
        'notend': 'notendaviðmót'
    };
    
    Object.entries(tagMapping).forEach(([keyword, tag]) => {
        if (description.includes(keyword) && !suggestedTags.includes(tag)) {
            suggestedTags.push(tag);
        }
    });
    
    if (suggestedTags.length === 0) {
        suggestedTags.push('almennt', 'verkefni');
    }
    
    document.getElementById('project_tags').value = suggestedTags.join(', ');
    showSuccessMessage(`Merki lögð til: ${suggestedTags.join(', ')}`);
    
    // Regenerate suggestions
    generateFinalSuggestions();
}

function collectFinalProjectData() {
    // Collect all form data
    projectData.priority = document.getElementById('project_priority').value;
    projectData.deadline = document.getElementById('project_deadline').value || null;
    
    const tagsInput = document.getElementById('project_tags').value;
    projectData.tags = tagsInput ? tagsInput.split(',').map(tag => tag.trim()).filter(tag => tag) : [];
}

// Utility functions
function getPriorityColor(priority) {
    const colors = {
        low: 'secondary',
        medium: 'info', 
        high: 'warning',
        urgent: 'danger'
    };
    return colors[priority] || 'info';
}

function getPriorityLabel(priority) {
    const labels = {
        low: 'Lágur',
        medium: 'Miðlungs',
        high: 'Hár', 
        urgent: 'Brýnn'
    };
    return labels[priority] || 'Miðlungs';
}

function showSuccessMessage(message) {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

function showInfoMessage(message) {
    showSuccessMessage(message); // For now, same implementation
}

// Final project creation
function editProject() {
    // Go back to step 1 for editing
    currentStep = 1;
    showStep(1);
}

// Form submission
document.getElementById('projectBuilderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    collectFinalProjectData();
    
    // Create the project
    const createData = {
        name: projectData.name,
        description: projectData.description,
        priority: projectData.priority,
        deadline: projectData.deadline,
        estimated_hours: projectData.estimated_hours,
        tags: JSON.stringify(projectData.tags),
        tasks: projectData.tasks
    };
    
    // Show loading
    document.getElementById('createProjectBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Býr til...';
    document.getElementById('createProjectBtn').disabled = true;
    
    // Submit to API
    fetch('/?page=api&action=createFullProject', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(createData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/?page=projects';
        } else {
            alert('Villa við að búa til verkefni: ' + data.message);
            document.getElementById('createProjectBtn').innerHTML = '<i class="bi bi-check-circle"></i> Búa til verkefni';
            document.getElementById('createProjectBtn').disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Villa við að búa til verkefni');
        document.getElementById('createProjectBtn').innerHTML = '<i class="bi bi-check-circle"></i> Búa til verkefni';
        document.getElementById('createProjectBtn').disabled = false;
    });
});