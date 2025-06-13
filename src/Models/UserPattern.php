<?php

require_once __DIR__ . '/BaseModel.php';

class UserPattern extends BaseModel {
    protected $table = 'user_patterns';
    protected $fillable = [
        'pattern_type', 'pattern_data', 'confidence_score', 
        'usage_count', 'success_rate'
    ];
    
    const TYPE_PRIORITY_KEYWORDS = 'priority_keywords';
    const TYPE_TIME_ESTIMATION = 'time_estimation';
    const TYPE_PROJECT_STRUCTURE = 'project_structure';
    const TYPE_NAMING_CONVENTION = 'naming_convention';
    const TYPE_DEADLINE_PATTERNS = 'deadline_patterns';
    
    public function createPattern($type, $data, $confidenceScore = 0.5) {
        $validTypes = [
            self::TYPE_PRIORITY_KEYWORDS,
            self::TYPE_TIME_ESTIMATION,
            self::TYPE_PROJECT_STRUCTURE,
            self::TYPE_NAMING_CONVENTION,
            self::TYPE_DEADLINE_PATTERNS
        ];
        
        if (!in_array($type, $validTypes)) {
            throw new InvalidArgumentException("Invalid pattern type: $type");
        }
        
        $patternData = [
            'pattern_type' => $type,
            'pattern_data' => json_encode($data),
            'confidence_score' => $confidenceScore,
            'last_used' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($patternData);
    }
    
    public function updatePattern($id, $data, $isSuccessful = true) {
        $pattern = $this->find($id);
        if (!$pattern) {
            throw new InvalidArgumentException("Pattern not found");
        }
        
        $newUsageCount = $pattern['usage_count'] + 1;
        $newSuccessRate = $isSuccessful 
            ? (($pattern['success_rate'] * $pattern['usage_count']) + 1) / $newUsageCount
            : (($pattern['success_rate'] * $pattern['usage_count'])) / $newUsageCount;
        
        $updateData = [
            'pattern_data' => json_encode($data),
            'usage_count' => $newUsageCount,
            'success_rate' => $newSuccessRate,
            'last_used' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($id, $updateData);
    }
    
    public function incrementUsage($id, $isSuccessful = true) {
        $pattern = $this->find($id);
        if (!$pattern) {
            throw new InvalidArgumentException("Pattern not found");
        }
        
        $newUsageCount = $pattern['usage_count'] + 1;
        $newSuccessRate = $isSuccessful 
            ? (($pattern['success_rate'] * $pattern['usage_count']) + 1) / $newUsageCount
            : (($pattern['success_rate'] * $pattern['usage_count'])) / $newUsageCount;
        
        $updateData = [
            'usage_count' => $newUsageCount,
            'success_rate' => $newSuccessRate,
            'last_used' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($id, $updateData);
    }
    
    public function getByType($type) {
        $patterns = $this->findAll(['pattern_type' => $type], 'confidence_score DESC, success_rate DESC');
        
        foreach ($patterns as &$pattern) {
            $pattern['pattern_data'] = json_decode($pattern['pattern_data'], true);
        }
        
        return $patterns;
    }
    
    public function getBestPattern($type) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE pattern_type = ?
            ORDER BY (confidence_score * success_rate) DESC
            LIMIT 1
        ";
        
        $pattern = $this->selectOne($sql, [$type]);
        
        if ($pattern) {
            $pattern['pattern_data'] = json_decode($pattern['pattern_data'], true);
        }
        
        return $pattern;
    }
    
    public function getAllPatterns() {
        $patterns = $this->findAll([], 'pattern_type ASC, confidence_score DESC');
        
        $groupedPatterns = [];
        foreach ($patterns as $pattern) {
            $pattern['pattern_data'] = json_decode($pattern['pattern_data'], true);
            $groupedPatterns[$pattern['pattern_type']][] = $pattern;
        }
        
        return $groupedPatterns;
    }
    
    public function getPriorityKeywords() {
        $pattern = $this->getBestPattern(self::TYPE_PRIORITY_KEYWORDS);
        return $pattern ? $pattern['pattern_data'] : [
            'urgent' => ['urgent', 'asap', 'critical', 'emergency'],
            'high' => ['important', 'priority', 'soon', 'deadline'],
            'medium' => ['should', 'consider', 'would be nice'],
            'low' => ['eventually', 'when time allows', 'backlog']
        ];
    }
    
    public function getTimeEstimationPatterns() {
        $pattern = $this->getBestPattern(self::TYPE_TIME_ESTIMATION);
        return $pattern ? $pattern['pattern_data'] : [
            'quick' => 30,
            'simple' => 60,
            'medium' => 240,
            'complex' => 480,
            'large' => 960
        ];
    }
    
    public function getDeadlinePatterns() {
        $pattern = $this->getBestPattern(self::TYPE_DEADLINE_PATTERNS);
        return $pattern ? $pattern['pattern_data'] : [
            'urgent' => 1,
            'high' => 3,
            'medium' => 7,
            'low' => 14
        ];
    }
    
    public function learnFromFeedback($type, $originalData, $correctedData, $feedbackType) {
        // Find existing pattern or create new one
        $existingPattern = $this->getBestPattern($type);
        
        if ($existingPattern) {
            // Update existing pattern based on feedback
            $mergedData = $this->mergePatternData($existingPattern['pattern_data'], $correctedData, $feedbackType);
            $isSuccessful = $feedbackType === 'accepted';
            
            $this->updatePattern($existingPattern['id'], $mergedData, $isSuccessful);
        } else {
            // Create new pattern
            $confidenceScore = $feedbackType === 'accepted' ? 0.8 : 0.4;
            $this->createPattern($type, $correctedData, $confidenceScore);
        }
    }
    
    private function mergePatternData($existingData, $newData, $feedbackType) {
        switch ($feedbackType) {
            case 'accepted':
                // Reinforce existing patterns
                return $existingData;
                
            case 'modified':
                // Merge and weight toward corrections
                return array_merge($existingData, $newData);
                
            case 'rejected':
                // Reduce confidence in existing patterns
                return $existingData;
                
            default:
                return $existingData;
        }
    }
    
    public function getPatternStatistics() {
        $sql = "
            SELECT 
                pattern_type,
                COUNT(*) as pattern_count,
                AVG(confidence_score) as avg_confidence,
                AVG(success_rate) as avg_success_rate,
                SUM(usage_count) as total_usage
            FROM {$this->table}
            GROUP BY pattern_type
        ";
        
        return $this->select($sql);
    }
    
    public function cleanupOldPatterns($threshold = 0.3, $maxAge = 90) {
        $sql = "
            DELETE FROM {$this->table}
            WHERE (confidence_score < ? OR success_rate < ?)
            AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        return $this->query($sql, [$threshold, $threshold, $maxAge]);
    }
}