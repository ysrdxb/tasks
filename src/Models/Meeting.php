<?php

require_once __DIR__ . '/BaseModel.php';

class Meeting extends BaseModel {
    protected $table = 'meetings';
    protected $fillable = [
        'title', 'date', 'original_input', 'input_type', 
        'raw_file_path', 'ai_analysis', 'processing_status'
    ];
    
    const INPUT_TYPE_TEXT = 'text';
    const INPUT_TYPE_OCR = 'ocr';
    const INPUT_TYPE_VOICE = 'voice';
    const INPUT_TYPE_CONVERSATION = 'conversation';
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ERROR = 'error';
    
    public function createMeeting($data) {
        $this->validateRequired($data, ['title', 'original_input', 'input_type']);
        
        $meetingData = [
            'title' => $data['title'],
            'date' => $data['date'] ?? date('Y-m-d H:i:s'),
            'original_input' => $data['original_input'],
            'input_type' => $data['input_type'],
            'raw_file_path' => $data['raw_file_path'] ?? null,
            'ai_analysis' => $data['ai_analysis'] ?? null,
            'processing_status' => self::STATUS_PENDING
        ];
        
        return $this->create($meetingData);
    }
    
    public function updateAnalysis($id, $analysis) {
        $data = [
            'ai_analysis' => json_encode($analysis),
            'processing_status' => self::STATUS_COMPLETED
        ];
        
        return $this->update($id, $data);
    }
    
    public function setProcessingStatus($id, $status) {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_ERROR])) {
            throw new InvalidArgumentException("Invalid processing status: $status");
        }
        
        return $this->update($id, ['processing_status' => $status]);
    }
    
    public function getAnalysis($id) {
        $meeting = $this->find($id);
        if (!$meeting || empty($meeting['ai_analysis'])) {
            return null;
        }
        
        return json_decode($meeting['ai_analysis'], true);
    }
    
    public function getRecent($limit = 10) {
        return $this->findAll([], 'created_at DESC', $limit);
    }
    
    public function getByStatus($status) {
        return $this->findAll(['processing_status' => $status], 'created_at DESC');
    }
    
    public function getPending() {
        return $this->getByStatus(self::STATUS_PENDING);
    }
    
    public function getProcessing() {
        return $this->getByStatus(self::STATUS_PROCESSING);
    }
    
    public function getMeetingStats() {
        $sql = "SELECT 
                    COUNT(*) as total_meetings,
                    SUM(CASE WHEN processing_status = 'completed' THEN 1 ELSE 0 END) as completed_meetings,
                    SUM(CASE WHEN input_type = 'conversation' THEN 1 ELSE 0 END) as conversation_meetings,
                    SUM(CASE WHEN ai_analysis IS NOT NULL AND ai_analysis != '' THEN 1 ELSE 0 END) as meetings_with_analysis
                FROM {$this->table}";
        
        return $this->db->selectOne($sql) ?: [
            'total_meetings' => 0,
            'completed_meetings' => 0, 
            'conversation_meetings' => 0,
            'meetings_with_analysis' => 0
        ];
    }
    
    public function countWithAnalysis() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE ai_analysis IS NOT NULL AND ai_analysis != ''";
        $result = $this->db->selectOne($sql);
        return $result ? $result['count'] : 0;
    }
    
    public function getCompleted() {
        return $this->getByStatus(self::STATUS_COMPLETED);
    }
    
    public function getWithProjects($id) {
        $sql = "
            SELECT m.*, 
                   COUNT(p.id) as project_count,
                   COUNT(t.id) as task_count
            FROM meetings m
            LEFT JOIN projects p ON p.meeting_id = m.id
            LEFT JOIN tasks t ON t.project_id = p.id
            WHERE m.id = ?
            GROUP BY m.id
        ";
        
        return $this->selectOne($sql, [$id]);
    }
    
    public function search($query, $limit = 20) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE title LIKE ? OR original_input LIKE ?
            ORDER BY created_at DESC
            LIMIT ?
        ";
        
        $searchTerm = "%$query%";
        return $this->select($sql, [$searchTerm, $searchTerm, $limit]);
    }
    
    public function getStatistics() {
        $sql = "
            SELECT 
                COUNT(*) as total_meetings,
                COUNT(CASE WHEN processing_status = 'completed' THEN 1 END) as completed_meetings,
                COUNT(CASE WHEN processing_status = 'pending' THEN 1 END) as pending_meetings,
                COUNT(CASE WHEN processing_status = 'processing' THEN 1 END) as processing_meetings,
                COUNT(CASE WHEN processing_status = 'error' THEN 1 END) as error_meetings,
                COUNT(CASE WHEN input_type = 'text' THEN 1 END) as text_inputs,
                COUNT(CASE WHEN input_type = 'ocr' THEN 1 END) as ocr_inputs,
                COUNT(CASE WHEN input_type = 'voice' THEN 1 END) as voice_inputs
            FROM {$this->table}
        ";
        
        return $this->selectOne($sql);
    }
}