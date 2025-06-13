<?php

require_once __DIR__ . '/../config/database.php';

class Seeder {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function run() {
        echo "Starting database seeding...\n";
        
        try {
            $this->runSeeds();
            echo "Seeding completed successfully!\n";
        } catch (Exception $e) {
            echo "Seeding failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function runSeeds() {
        $seedDir = __DIR__ . '/seeds';
        $files = glob($seedDir . '/*.sql');
        sort($files);
        
        foreach ($files as $file) {
            $filename = basename($file);
            echo "Running seed: $filename\n";
            
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
                
                $this->db->commit();
                echo "✓ $filename executed successfully\n";
                
            } catch (Exception $e) {
                $this->db->rollback();
                echo "⚠ Warning: Failed to execute $filename: " . $e->getMessage() . "\n";
                echo "This might be due to existing data. Continuing with other seeds...\n";
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
            } elseif ($inQuotes && $char === $quoteChar && ($i === 0 || $sql[$i-1] !== '\\')) {
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
    $seeder = new Seeder();
    $seeder->run();
}