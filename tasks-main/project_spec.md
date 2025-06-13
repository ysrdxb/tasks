# AI Meeting Notes & Project Management System

## Project Overview
Build an intelligent web application that converts meeting notes (handwritten or typed) into structured project management system using Claude AI. The system learns from user patterns and provides smart suggestions for project organization.

## Technology Stack
- **Backend**: PHP 8.0+, MySQL 8.0+
- **Frontend**: Bootstrap 5, jQuery, Chart.js
- **AI**: Anthropic Claude API
- **OCR**: Tesseract OCR
- **Environment**: MAMP (local development)

## Core Features
1. **Input Processing**: Upload handwritten notes (OCR), typed text, or voice notes
2. **AI Analysis**: Extract projects, tasks, deadlines, and priorities using Claude
3. **Learning System**: Adapt to user patterns and improve suggestions over time
4. **Project Management**: Full CRUD operations with smart insights
5. **Analytics**: Progress tracking and productivity insights

## Database Schema

```sql
-- Core Data Tables
CREATE TABLE meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    date DATETIME,
    original_input LONGTEXT,
    input_type ENUM('text', 'ocr', 'voice'),
    raw_file_path VARCHAR(500),
    ai_analysis JSON,
    processing_status ENUM('pending', 'processing', 'completed', 'error'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT,
    name VARCHAR(255),
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'urgent'),
    status ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled'),
    start_date DATE,
    deadline DATE,
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2) DEFAULT 0,
    completion_percentage INT DEFAULT 0,
    ai_confidence FLOAT,
    color_code VARCHAR(7) DEFAULT '#3498db',
    tags JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE
);

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    title VARCHAR(255),
    description TEXT,
    is_completed BOOLEAN DEFAULT FALSE,
    priority INT DEFAULT 1,
    deadline DATE,
    estimated_minutes INT,
    actual_minutes INT DEFAULT 0,
    dependencies JSON,
    assigned_to VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Learning & Intelligence Tables
CREATE TABLE user_patterns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pattern_type ENUM('priority_keywords', 'time_estimation', 'project_structure', 'naming_convention', 'deadline_patterns'),
    pattern_data JSON,
    confidence_score FLOAT,
    usage_count INT DEFAULT 1,
    success_rate FLOAT DEFAULT 0.5,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ai_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    context_type ENUM('project_extraction', 'task_creation', 'priority_assignment', 'time_estimation'),
    original_suggestion JSON,
    user_correction JSON,
    feedback_type ENUM('accepted', 'modified', 'rejected'),
    improvement_score FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    template_data JSON,
    usage_count INT DEFAULT 0,
    effectiveness_score FLOAT DEFAULT 0.5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type ENUM('meeting_upload', 'project_created', 'task_completed', 'deadline_missed', 'ai_suggestion_used'),
    entity_type ENUM('meeting', 'project', 'task'),
    entity_id INT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## File Structure
```
/ai-project-manager/
├── config/
│   ├── database.php
│   ├── anthropic.php
│   └── app.php
├── src/
│   ├── Models/
│   │   ├── Meeting.php
│   │   ├── Project.php
│   │   ├── Task.php
│   │   ├── UserPattern.php
│   │   └── Template.php
│   ├── Controllers/
│   │   ├── MeetingController.php
│   │   ├── ProjectController.php
│   │   ├── DashboardController.php
│   │   └── APIController.php
│   ├── Services/
│   │   ├── MeetingProcessor.php
│   │   ├── LearningEngine.php
│   │   ├── AnthropicService.php
│   │   ├── OCRService.php
│   │   └── AnalyticsService.php
│   └── Utils/
│       ├── Database.php
│       ├── FileUploader.php
│       └── Validator.php
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   ├── uploads/
│   └── api/
├── templates/
│   ├── layout/
│   ├── dashboard/
│   ├── projects/
│   ├── meetings/
│   └── components/
├── database/
│   ├── migrations/
│   └── seeds/
├── storage/
│   ├── logs/
│   └── cache/
├── .env.example
├── composer.json
└── README.md
```

## Core Classes & Methods

### MeetingProcessor Service
```php
class MeetingProcessor {
    private $anthropicService;
    private $learningEngine;
    private $ocrService;
    
    public function processInput($input, $type, $filePath = null)
    public function extractTextFromImage($imagePath)
    public function analyzeWithAI($text, $userPatterns = [])
    public function createProjectsFromAnalysis($analysis, $meetingId)
    public function validateExtraction($extraction)
}
```

### LearningEngine Service
```php
class LearningEngine {
    public function getUserPatterns($userId = null)
    public function updatePatterns($feedback)
    public function analyzeUserBehavior($actions)
    public function suggestImprovements($context)
    public function generateTemplates($projectHistory)
    public function calculateConfidenceScore($pattern)
}
```

### AnthropicService
```php
class AnthropicService {
    private $apiKey;
    private $baseUrl = 'https://api.anthropic.com/v1/messages';
    
    public function analyzeNotes($input, $patterns = [])
    public function suggestProjectImprovements($project, $history = [])
    public function generateTaskBreakdown($projectDescription)
    public function estimateTimeRequirements($tasks)
    public function identifyRisks($project)
}
```

## AI Prompts (Icelandic)

### Main Analysis Prompt
```php
$analysisPrompt = "Þú ert sérfræðingur í verkefnastjórnun fyrir íslenskan notanda.

Greindu þessar fundaglósur og dragðu út:

1. VERKEFNI (Projects):
   - Nafn og skýra lýsingu
   - Forgangur (low/medium/high/urgent)
   - Áætlaður tími í klukkustundum
   - Deadline ef það er getið
   - Ábyrgðaraðili eða tengiliður

2. VERKÞÆTTI (Tasks):
   - Hvaða verkefni þeir tilheyra
   - Nákvæm lýsing og kröfur
   - Dependencies á aðra verkþætti
   - Tímamat í mínútum
   - Forgangur (1-5)

3. LYKILUPPLÝSINGAR:
   - Mikilvægar dagsetningar
   - Áhættuþættir
   - Vöntun upplýsingar

4. LÆRDÓMUR:
   - Greina stíl og orðaval notanda
   - Forgangsröðun patterns
   - Tímamat patterns

Notenda patterns: {user_patterns}

Fundaglósur:
{meeting_notes}

Svaraðu í JSON format með confidence scores (0-1) fyrir hvern hlut.

Format:
{
  \"projects\": [
    {
      \"name\": \"string\",
      \"description\": \"string\",
      \"priority\": \"low|medium|high|urgent\",
      \"estimated_hours\": number,
      \"deadline\": \"YYYY-MM-DD or null\",
      \"responsible_party\": \"string or null\",
      \"confidence\": number,
      \"tags\": [\"array of strings\"]
    }
  ],
  \"tasks\": [
    {
      \"project_index\": number,
      \"title\": \"string\",
      \"description\": \"string\",
      \"priority\": number,
      \"estimated_minutes\": number,
      \"deadline\": \"YYYY-MM-DD or null\",
      \"dependencies\": [array of task indices],
      \"confidence\": number
    }
  ],
  \"key_dates\": [
    {
      \"date\": \"YYYY-MM-DD\",
      \"description\": \"string\",
      \"importance\": \"low|medium|high\"
    }
  ],
  \"risks\": [\"array of potential risks\"],
  \"missing_info\": [\"array of missing information\"],
  \"user_patterns_detected\": {
    \"priority_keywords\": [\"array\"],
    \"time_indicators\": [\"array\"],
    \"naming_style\": \"string\"
  }
}";
```

## API Endpoints

### REST API Structure
```
POST /api/meetings/upload - Upload and process meeting notes
GET /api/meetings - List all meetings
GET /api/meetings/{id} - Get specific meeting
PUT /api/meetings/{id} - Update meeting

GET /api/projects - List all projects
POST /api/projects - Create new project
GET /api/projects/{id} - Get specific project
PUT /api/projects/{id} - Update project
DELETE /api/projects/{id} - Delete project

GET /api/tasks - List tasks (with filters)
POST /api/tasks - Create new task
PUT /api/tasks/{id} - Update task
DELETE /api/tasks/{id} - Delete task

GET /api/analytics/dashboard - Dashboard data
GET /api/analytics/productivity - Productivity metrics
GET /api/learning/patterns - Get user patterns
POST /api/learning/feedback - Submit feedback for learning

POST /api/ai/suggest-improvements - Get AI suggestions for project
POST /api/ai/estimate-time - Get time estimates
POST /api/ai/generate-template - Generate project template
```

## Frontend Components

### Main Views
1. **Dashboard** (`/`)
   - Project overview cards
   - Recent activity feed
   - Upcoming deadlines
   - Progress charts

2. **Upload Meeting** (`/upload`)
   - File drop zone
   - Text input area
   - Processing status
   - AI analysis review

3. **Project Management** (`/projects`)
   - Kanban board view
   - List view with filters
   - Calendar timeline
   - Project details modal

4. **Analytics** (`/analytics`)
   - Productivity metrics
   - Time tracking
   - Project success rates
   - AI learning progress

### Key JavaScript Features
```javascript
// Real-time AI processing updates
// Drag & drop project status changes
// Smart form auto-completion
// Keyboard shortcuts
// Offline capability for note-taking
// Progressive Web App features
```

## Configuration Files

### .env Template
```
# Database
DB_HOST=localhost
DB_NAME=ai_project_manager
DB_USER=root
DB_PASS=

# Anthropic API
ANTHROPIC_API_KEY=your_api_key_here
ANTHROPIC_MODEL=claude-3-sonnet-20240229

# File Upload
MAX_FILE_SIZE=10M
UPLOAD_PATH=public/uploads/

# OCR
TESSERACT_PATH=/usr/local/bin/tesseract
OCR_LANGUAGES=isl,eng

# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8888

# Security
SESSION_LIFETIME=7200
CSRF_TOKEN_EXPIRE=3600
```

## Installation Instructions

1. **Setup MAMP Environment**
   ```bash
   # Start MAMP
   # Create new database: ai_project_manager
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

4. **Database Setup**
   ```bash
   php database/migrate.php
   php database/seed.php
   ```

5. **Install Tesseract OCR**
   ```bash
   # macOS
   brew install tesseract tesseract-lang
   
   # Install Icelandic language pack
   brew install tesseract-lang
   ```

## Development Priorities

### Phase 1: Core Functionality
- [x] Database schema
- [ ] Basic file upload
- [ ] Anthropic API integration
- [ ] Simple project creation
- [ ] Basic dashboard

### Phase 2: AI Intelligence
- [ ] OCR implementation
- [ ] Learning engine
- [ ] Pattern recognition
- [ ] Smart suggestions

### Phase 3: Advanced Features
- [ ] Analytics dashboard
- [ ] Template system
- [ ] Advanced project management
- [ ] Mobile responsiveness

### Phase 4: Polish & Optimization
- [ ] Performance optimization
- [ ] Advanced AI features
- [ ] Export capabilities
- [ ] Backup system

## Testing Strategy
- Unit tests for AI processing
- Integration tests for API endpoints
- User acceptance testing for workflows
- Performance testing for file uploads
- AI accuracy testing with sample data

## Security Considerations
- File upload validation
- SQL injection prevention
- XSS protection
- API rate limiting
- Secure file storage
- Input sanitization

## Performance Optimization
- Database indexing
- File caching
- API response caching
- Image optimization
- Lazy loading
- CDN for static assets

---

**Next Steps**: Start with database setup and basic file upload functionality, then integrate Anthropic API for text analysis.