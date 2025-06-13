-- Sample data for testing the AI Meeting Notes & Project Management System

-- Sample meetings
INSERT INTO meetings (title, date, original_input, input_type, processing_status) VALUES
('Weekly Team Standup', '2024-06-01 10:00:00', 'Team discussed progress on website redesign project. Need to finish homepage by Friday. John will handle the contact form. Sarah working on user authentication. Deadline moved to next Monday due to client feedback.', 'text', 'completed'),
('Project Planning Session', '2024-06-02 14:30:00', 'New mobile app project approved. High priority. Estimated 3 months development time. Features: user login, product catalog, shopping cart, payment integration. Launch target: September 2024.', 'text', 'completed'),
('Client Feedback Review', '2024-06-03 11:00:00', 'Client wants changes to dashboard design. Medium priority. Add dark mode toggle. Improve loading times. Fix responsive issues on tablet. Timeline: 2 weeks.', 'text', 'completed');

-- Sample projects
INSERT INTO projects (meeting_id, name, description, priority, status, start_date, deadline, estimated_hours, completion_percentage, ai_confidence, tags) VALUES
(1, 'Website Redesign', 'Complete redesign of company website with modern UI/UX', 'high', 'active', '2024-05-15', '2024-06-10', 120.00, 75, 0.9, '["web", "design", "frontend"]'),
(2, 'Mobile App Development', 'E-commerce mobile application for iOS and Android', 'urgent', 'planning', '2024-06-15', '2024-09-15', 480.00, 0, 0.95, '["mobile", "app", "ecommerce", "ios", "android"]'),
(3, 'Dashboard Improvements', 'UI/UX improvements and performance optimization', 'medium', 'active', '2024-06-01', '2024-06-15', 80.00, 25, 0.85, '["ui", "ux", "performance", "dashboard"]');

-- Sample tasks
INSERT INTO tasks (project_id, title, description, is_completed, priority, deadline, estimated_minutes, assigned_to) VALUES
(1, 'Design homepage layout', 'Create wireframes and mockups for new homepage', true, 1, '2024-06-05', 480, 'Design Team'),
(1, 'Implement contact form', 'Build responsive contact form with validation', false, 2, '2024-06-08', 240, 'John'),
(1, 'User authentication system', 'Implement login/register functionality', false, 1, '2024-06-10', 360, 'Sarah'),
(2, 'Setup project structure', 'Initialize React Native project and dependencies', false, 1, '2024-06-20', 120, 'Development Team'),
(2, 'Design user interface', 'Create app UI designs and user flow', false, 2, '2024-06-25', 600, 'Design Team'),
(2, 'Implement user login', 'Build authentication system for mobile app', false, 1, '2024-07-05', 480, 'Backend Team'),
(3, 'Add dark mode toggle', 'Implement dark/light theme switching', false, 3, '2024-06-10', 180, 'Frontend Team'),
(3, 'Optimize loading times', 'Improve page load performance and optimize assets', false, 1, '2024-06-12', 300, 'Development Team'),
(3, 'Fix responsive issues', 'Address tablet and mobile display problems', false, 2, '2024-06-15', 240, 'Frontend Team');

-- Sample user patterns
INSERT INTO user_patterns (pattern_type, pattern_data, confidence_score, usage_count, success_rate) VALUES
('priority_keywords', '{"high": ["urgent", "asap", "critical", "important"], "medium": ["should", "would be nice", "consider"], "low": ["eventually", "when time allows"]}', 0.8, 5, 0.85),
('time_estimation', '{"small_task": 120, "medium_task": 480, "large_task": 960, "keywords": {"quick": 60, "detailed": 480}}', 0.75, 3, 0.7),
('deadline_patterns', '{"urgent": 3, "high": 7, "medium": 14, "low": 30}', 0.9, 8, 0.9);

-- Sample templates
INSERT INTO templates (name, description, template_data, usage_count, effectiveness_score) VALUES
('Web Development Project', 'Standard template for web development projects', '{"phases": ["Planning", "Design", "Development", "Testing", "Deployment"], "common_tasks": ["Setup environment", "Create wireframes", "Implement features", "Write tests", "Deploy to production"]}', 3, 0.85),
('Mobile App Project', 'Template for mobile application development', '{"phases": ["Planning", "UI/UX Design", "Development", "Testing", "App Store Submission"], "platforms": ["iOS", "Android"], "common_features": ["User Authentication", "API Integration", "Push Notifications"]}', 2, 0.8);

-- Sample activity log
INSERT INTO activity_log (action_type, entity_type, entity_id, metadata) VALUES
('meeting_upload', 'meeting', 1, '{"file_size": 1024, "processing_time": 2.5}'),
('project_created', 'project', 1, '{"ai_confidence": 0.9, "tasks_generated": 3}'),
('task_completed', 'task', 1, '{"completion_time": 480, "estimated_time": 480}'),
('project_created', 'project', 2, '{"ai_confidence": 0.95, "tasks_generated": 3}'),
('ai_suggestion_used', 'project', 3, '{"suggestion_type": "time_estimation", "accuracy": 0.8}');