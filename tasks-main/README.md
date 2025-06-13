# AI Meeting Notes & Project Management System

A professional web application that converts meeting notes into structured project management system using Claude AI. The system learns from user patterns and provides intelligent suggestions for project organization.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![Claude AI](https://img.shields.io/badge/Claude_AI-Anthropic-FF6B35?style=flat-square)

## ✨ Features

### 🤖 AI-Powered Analysis
- **Intelligent Meeting Processing**: Upload text, images, or voice notes
- **Automatic Project Extraction**: AI identifies projects, tasks, deadlines, and priorities
- **Pattern Learning**: System adapts to your work style and improves over time
- **Multi-language Support**: Optimized for Icelandic and English

### 📊 Project Management
- **Full CRUD Operations**: Create, read, update, and delete projects and tasks
- **Smart Insights**: AI-generated recommendations and risk analysis
- **Progress Tracking**: Visual progress indicators and completion analytics
- **Deadline Management**: Automatic overdue detection and notifications

### 📈 Analytics & Reporting
- **Productivity Metrics**: Track time estimates vs. actual time
- **Project Success Rates**: Monitor completion rates and patterns
- **Visual Dashboards**: Charts and graphs for better insights
- **Performance Optimization**: Identify bottlenecks and improvements

### 🎨 Modern Interface
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Bootstrap 5**: Modern, professional UI components
- **Interactive Elements**: Drag & drop, real-time updates
- **Dark Mode Ready**: Supports system preference detection

## 🚀 Quick Start

### Prerequisites
- **MAMP** (or similar PHP/MySQL environment)
- **PHP 8.0+**
- **MySQL 8.0+**
- **Anthropic API Key** (for AI features)

### Installation

1. **Clone or Download**
   ```bash
   # If using git
   git clone <repository-url> ai-project-manager
   
   # Or download and extract the ZIP file to your MAMP htdocs folder
   ```

2. **MAMP Setup**
   - Start MAMP servers
   - Open phpMyAdmin (usually `http://localhost:8888/phpMyAdmin`)
   - Create a new database called `tasks`

3. **Configure Environment**
   ```bash
   cd htdocs
   cp .env.example .env
   ```
   
   Edit `.env` with your settings:
   ```env
   # Database
   DB_HOST=localhost
   DB_NAME=tasks
   DB_USER=root
   DB_PASS=
   
   # Anthropic API (Get from https://console.anthropic.com/)
   ANTHROPIC_API_KEY=your_api_key_here
   ANTHROPIC_MODEL=claude-3-sonnet-20240229
   
   # Application
   APP_URL=http://localhost:8888
   ```

4. **Initialize Database**
   - Visit `http://localhost:8888/setup`
   - Click "Run Migration" to create database tables
   - Optionally click "Load Sample Data" for demo content

5. **Ready to Use!**
   - Visit `http://localhost:8888`
   - Start uploading meeting notes or create projects manually

## 📱 Usage Guide

### Getting Started
1. **Upload Meeting Notes**: Use the "Upload Notes" button to process meeting content
2. **Review AI Analysis**: Check extracted projects and tasks for accuracy
3. **Manage Projects**: Track progress, update statuses, and add tasks
4. **Monitor Analytics**: View productivity insights and performance metrics

### AI Analysis Tips
For best results when uploading meeting notes:

✅ **Include:**
- Project names and clear descriptions
- Specific tasks and requirements
- Deadlines and timeframes ("by Friday", "next week")
- Priority indicators ("urgent", "high priority", "when time allows")
- Assigned team members or responsibilities

❌ **Avoid:**
- Too much casual conversation
- Incomplete thoughts or fragments
- Mixed languages within sentences
- Overly technical jargon without context

### Example Meeting Notes
```
Weekly Team Standup - June 6, 2024

Website redesign project is high priority - needs to be finished by Friday June 14th.
- John will handle the contact form (4 hours estimated)
- Sarah working on authentication system (due Monday, 6-8 hours)
- Homepage design needs final review

New mobile app project approved! Urgent priority, launch target September 2024.
Features needed:
- User login and registration
- Product catalog with search
- Shopping cart and payment integration
- Push notifications

Client feedback items (medium priority, 2 week timeline):
- Add dark mode toggle (2 hours)
- Improve loading performance (5 hours) 
- Fix tablet responsive issues (3 hours).
```

## 🏗️ Architecture

### File Structure
```
htdocs/
├── config/                 # Configuration files
│   ├── app.php             # Main app configuration
│   ├── database.php        # Database connection
│   └── anthropic.php       # AI service configuration
├── src/
│   ├── Models/             # Data models
│   │   ├── Meeting.php     # Meeting data handling
│   │   ├── Project.php     # Project management
│   │   ├── Task.php        # Task operations
│   │   └── UserPattern.php # Learning patterns
│   └── Services/           # Business logic
│       ├── AnthropicService.php    # AI integration
│       ├── MeetingProcessor.php    # Meeting analysis
│       └── LearningEngine.php      # Pattern learning
├── public/
│   └── assets/             # Static files (CSS, JS, images)
├── templates/              # View templates
│   ├── layout/             # Base layouts
│   ├── dashboard/          # Dashboard views
│   ├── projects/           # Project management
│   └── meetings/           # Meeting management
├── database/
│   ├── migrations/         # Database schema
│   └── seeds/              # Sample data
└── index.php              # Main entry point
```

### Database Schema
The system uses 7 main tables:
- **meetings**: Raw meeting data and processing status
- **projects**: Project information and metadata
- **tasks**: Individual tasks and dependencies
- **user_patterns**: Learning data for AI improvement
- **ai_feedback**: User corrections for better accuracy
- **templates**: Reusable project templates
- **activity_log**: System activity tracking

## 🔧 Configuration

### Environment Variables
```env
# Database Configuration
DB_HOST=localhost           # Database host
DB_NAME=tasks              # Database name
DB_USER=root               # Database username
DB_PASS=                   # Database password

# Anthropic AI Configuration
ANTHROPIC_API_KEY=sk-...   # Your API key from Anthropic Console
ANTHROPIC_MODEL=claude-3-sonnet-20240229  # AI model to use

# File Upload Settings
MAX_FILE_SIZE=10M          # Maximum upload size
UPLOAD_PATH=public/uploads/ # Upload directory

# OCR Configuration (Future Feature)
TESSERACT_PATH=/usr/local/bin/tesseract
OCR_LANGUAGES=isl,eng      # Icelandic and English

# Application Settings
APP_ENV=development        # Environment (development/production)
APP_DEBUG=true            # Debug mode
APP_URL=http://localhost:8888  # Base application URL

# Security Settings
SESSION_LIFETIME=7200      # Session duration (2 hours)
CSRF_TOKEN_EXPIRE=3600     # CSRF token expiry (1 hour)
```

### Anthropic API Setup
1. Visit [Anthropic Console](https://console.anthropic.com/)
2. Create an account or sign in
3. Generate an API key
4. Add the key to your `.env` file
5. Test the connection using the setup page

## 🔒 Security Features

- **Input Sanitization**: All user input is properly sanitized
- **SQL Injection Prevention**: Uses prepared statements
- **CSRF Protection**: Token-based CSRF protection
- **XSS Protection**: Output encoding and validation
- **File Upload Validation**: Type and size restrictions
- **Session Security**: Secure session configuration

## 🎯 Development Status

### ✅ Completed Features
- [x] Database schema and migrations
- [x] Core model classes (Meeting, Project, Task)
- [x] Anthropic AI integration
- [x] Responsive Bootstrap UI
- [x] Dashboard with statistics
- [x] Meeting upload and processing
- [x] Project and task management
- [x] Analytics and reporting
- [x] Setup and configuration system

### 🚧 In Progress / Planned
- [ ] File upload (OCR and audio processing)
- [ ] Advanced controllers and API endpoints
- [ ] Real-time updates with WebSockets
- [ ] Advanced search and filtering
- [ ] Team collaboration features
- [ ] Export capabilities (PDF, Excel)
- [ ] Mobile app integration
- [ ] Advanced AI learning and templates

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Verify MAMP is running
- Check database credentials in `.env`
- Ensure `tasks` database exists

**AI Analysis Not Working**
- Verify Anthropic API key is correct
- Check internet connection
- Test API connection in setup page

**File Upload Issues**
- Check file size limits
- Verify upload directory permissions
- Note: OCR/audio processing not yet implemented

**Page Not Loading**
- Check MAMP server status
- Verify correct URL (usually `http://localhost:8888`)
- Check PHP error logs

### Getting Help
1. Check the setup page for configuration status
2. Enable debug mode in `.env` for detailed error messages
3. Check MAMP logs for server errors
4. Verify all prerequisites are installed

## 📄 License

This project is developed for educational and demonstration purposes. Please ensure you have proper licensing for any production use.

## 🤝 Contributing

This is a demonstration project. For suggestions or improvements:
1. Document any issues found
2. Propose enhancements
3. Share usage feedback
4. Report security concerns

---

**Built with ❤️ using PHP, MySQL, Bootstrap, and Claude AI**