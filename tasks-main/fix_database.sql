-- Fix missing updated_at columns in tasks database
USE tasks;

-- Add updated_at column to tasks table
ALTER TABLE tasks 
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
AFTER created_at;

-- Add updated_at column to templates table  
ALTER TABLE templates 
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
AFTER created_at;

-- Add updated_at column to user_patterns table
ALTER TABLE user_patterns 
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
AFTER created_at;

-- Verify the changes
SHOW COLUMNS FROM tasks;
SHOW COLUMNS FROM templates;
SHOW COLUMNS FROM user_patterns;