-- Add banking_score column to users table
ALTER TABLE users ADD COLUMN banking_score DECIMAL(3,1) DEFAULT 5.0;
