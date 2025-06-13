<?php

require_once __DIR__ . '/../config/database.php';

class Migrator {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function run() {
        echo "Starting database migration...\n";
        
        try {
            $this->createMigrationsTable();
            $this->runMigrations();
            echo "Migration completed successfully!\n";
        } catch (Exception $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_filename (filename)
        )";
        
        $this->db->query($sql);
    }
    
    private function runMigrations() {
        $migrationDir = __DIR__ . '/migrations';
        $files = glob($migrationDir . '/*.sql');
        sort($files);
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Check if migration already ran
            $existing = $this->db->selectOne(
                "SELECT id FROM migrations WHERE filename = ?", 
                [$filename]
            );
            
            if ($existing) {
                echo "Skipping $filename (already executed)\n";
                continue;
            }
            
            echo "Running migration: $filename\n";
            
            $sql = file_get_contents($file);
            $statements = $this->splitSqlStatements($sql);
            
            $this->db->beginTransaction();
            
            try {
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $this->db->query($statement);
                    }
                }
                
                // Record migration
                $this->db->insert(
                    "INSERT INTO migrations (filename) VALUES (?)",
                    [$filename]
                );
                
                $this->db->commit();
                echo "✓ $filename executed successfully\n";
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw new Exception("Failed to execute $filename: " . $e->getMessage());
            }
        }
    }
    
    private function splitSqlStatements($sql) {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        
        // Split by semicolon but not within quotes
        $statements = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inQuotes && ($char === '"' || $char === "'")) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($inQuotes && $char === $quoteChar && $sql[$i-1] !== '\\') {
                $inQuotes = false;
                $quoteChar = '';
            } elseif (!$inQuotes && $char === ';') {
                $statements[] = $current;
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $statements[] = $current;
        }
        
        return $statements;
    }
}

// Run if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $migrator = new Migrator();
    $migrator->run();
}