-- AI Meeting Notes & Project Management System Database Schema

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

-- Indexes for performance
CREATE INDEX idx_meetings_date ON meetings(date);
CREATE INDEX idx_meetings_status ON meetings(processing_status);
CREATE INDEX idx_projects_status ON projects(status);
CREATE INDEX idx_projects_priority ON projects(priority);
CREATE INDEX idx_projects_deadline ON projects(deadline);
CREATE INDEX idx_tasks_completed ON tasks(is_completed);
CREATE INDEX idx_tasks_deadline ON tasks(deadline);
CREATE INDEX idx_tasks_priority ON tasks(priority);
CREATE INDEX idx_activity_log_date ON activity_log(created_at);
CREATE INDEX idx_user_patterns_type ON user_patterns(pattern_type);