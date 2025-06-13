-- Add 'conversation' to input_type enum
ALTER TABLE meetings MODIFY COLUMN input_type ENUM('text', 'ocr', 'voice', 'conversation');