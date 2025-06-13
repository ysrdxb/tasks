<?php

require_once __DIR__ . '/../../config/anthropic.php';

class AnthropicService {
    private $apiKey;
    private $model;
    private $baseUrl;
    
    public function __construct() {
        AnthropicConfig::init();
        $this->apiKey = AnthropicConfig::getApiKey();
        $this->model = AnthropicConfig::getModel();
        $this->baseUrl = AnthropicConfig::getBaseUrl();
        
        if (!AnthropicConfig::isConfigured()) {
            throw new Exception("Anthropic API is not properly configured. Please set ANTHROPIC_API_KEY in .env");
        }
    }
    
    public function analyzeNotes($input, $patterns = []) {
        $prompt = $this->buildAnalysisPrompt($input, $patterns);
        
        $response = $this->makeApiCall($prompt);
        
        if (!$response) {
            throw new Exception("Failed to get response from Anthropic API");
        }
        
        return $this->parseAnalysisResponse($response);
    }
    
    public function suggestProjectImprovements($project, $history = []) {
        $prompt = $this->buildImprovementPrompt($project, $history);
        
        $response = $this->makeApiCall($prompt);
        
        return $this->parseImprovementResponse($response);
    }
    
    public function generateTaskBreakdown($projectDescription, $patterns = []) {
        $prompt = $this->buildTaskBreakdownPrompt($projectDescription, $patterns);
        
        $response = $this->makeApiCall($prompt);
        
        return $this->parseTaskBreakdownResponse($response);
    }
    
    public function estimateTimeRequirements($tasks, $patterns = []) {
        $prompt = $this->buildTimeEstimationPrompt($tasks, $patterns);
        
        $response = $this->makeApiCall($prompt);
        
        return $this->parseTimeEstimationResponse($response);
    }
    
    public function identifyRisks($project) {
        $prompt = $this->buildRiskAnalysisPrompt($project);
        
        $response = $this->makeApiCall($prompt);
        
        return $this->parseRiskAnalysisResponse($response);
    }
    
    private function buildAnalysisPrompt($input, $patterns) {
        return "Þú ert sérfræðingur í verkefnastjórnun fyrir íslenskan notanda. Greindu þessar fundargerðir og dragðu út verkefni og verkþætti.

MIKILVÆGT: Svaraðu aðeins á íslensku og notaðu eingöngu íslensk orð og setningagerð. Allt sem þú skrifar verður að vera á íslensku.

Fundargerðir:
{$input}

Greiningarkröfur:
- Verkefni með nafni, lýsingu, forgangi og tímamati
- Verkþætti með titli, lýsingu, forgangi og tímamati
- Allt á íslensku

Dragðu út:
1. VERKEFNI (Projects):
   - Nafn og skýra lýsingu á íslensku
   - Forgangur (low/medium/high/urgent)
   - Áætlaður tími í klukkustundum
   - Skiladagur ef það er getið
   - Ábyrgðaraðili eða tengiliður

2. VERKÞÆTTI (Tasks):
   - Hvaða verkefni þeir tilheyra
   - Nákvæm lýsing og kröfur á íslensku
   - Forgangur (1-5)
   - Tímamat í mínútum

Svaraðu í JSON formati en allt textainnihald verður að vera á íslensku:
{
  \"projects\": [
    {
      \"name\": \"íslenskt verkefnisnafn\",
      \"description\": \"íslensk lýsing á verkefninu\", 
      \"priority\": \"medium\",
      \"estimated_hours\": 8,
      \"deadline\": null,
      \"confidence\": 0.8,
      \"tags\": []
    }
  ],
  \"tasks\": [
    {
      \"project_index\": 0,
      \"title\": \"íslenskur verkþáttatitill\",
      \"description\": \"íslensk lýsing á verkþættinum\",
      \"priority\": 2,
      \"estimated_minutes\": 120,
      \"confidence\": 0.7
    }
  ],
  \"key_dates\": [],
  \"risks\": [],
  \"missing_info\": []
}";
    }
    
    private function buildImprovementPrompt($project, $history) {
        $projectInfo = json_encode($project, JSON_PRETTY_PRINT);
        $historyInfo = json_encode($history, JSON_PRETTY_PRINT);
        
        return "Þú ert verkefnastjórnunarráðgjafi. Greina þetta verkefni og stinga upp á umbótum.

Verkefni:
{$projectInfo}

Saga og gögn:
{$historyInfo}

Gefðu ráðleggingar um:
1. Tímaáætlun og skilvirkni
2. Áhættustjórnun
3. Verkþáttaskiptingu
4. Forgangsröðun
5. Auðlindaúthlutun

Svaraðu á JSON formati:
{
  \"suggestions\": [
    {
      \"category\": \"string\",
      \"title\": \"string\",
      \"description\": \"string\",
      \"priority\": \"low|medium|high\",
      \"estimated_impact\": \"string\",
      \"implementation_difficulty\": \"easy|medium|hard\"
    }
  ],
  \"risks_identified\": [\"array\"],
  \"optimization_opportunities\": [\"array\"]
}";
    }
    
    private function buildTaskBreakdownPrompt($projectDescription, $patterns) {
        $patternsInfo = json_encode($patterns, JSON_PRETTY_PRINT);
        
        return "Þú ert verkefnaskiptingarfræðingur. Taktu þessa verkefnalýsingu og skiptu henni í nákvæma verkþætti.

Verkefni:
{$projectDescription}

Notenda patterns:
{$patternsInfo}

Búðu til lista af verkþáttum með:
1. Rökréttri röðun og tengslum
2. Raunhæfu tímamati
3. Skýrum kröfum
4. Viðeigandi forgangi

JSON format:
{
  \"tasks\": [
    {
      \"title\": \"string\",
      \"description\": \"string\",
      \"priority\": number,
      \"estimated_minutes\": number,
      \"prerequisites\": [\"array of task titles\"],
      \"deliverables\": [\"array\"],
      \"skills_required\": [\"array\"]
    }
  ],
  \"phases\": [
    {
      \"name\": \"string\",
      \"duration_days\": number,
      \"tasks_included\": [\"array of task titles\"]
    }
  ]
}";
    }
    
    private function buildTimeEstimationPrompt($tasks, $patterns) {
        $tasksInfo = json_encode($tasks, JSON_PRETTY_PRINT);
        $patternsInfo = json_encode($patterns, JSON_PRETTY_PRINT);
        
        return "Þú ert tímamatsfræðingur fyrir verkefni. Mettu tíma fyrir þessa verkþætti.

Verkþættir:
{$tasksInfo}

Tímamat patterns:
{$patternsInfo}

Gefðu nákvæmt tímamat með rökstuðningi.

JSON format:
{
  \"time_estimates\": [
    {
      \"task_id\": \"string\",
      \"estimated_minutes\": number,
      \"confidence\": number,
      \"factors_considered\": [\"array\"],
      \"risk_buffer_percentage\": number
    }
  ],
  \"total_project_time\": {
    \"optimistic_hours\": number,
    \"realistic_hours\": number,
    \"pessimistic_hours\": number
  }
}";
    }
    
    private function buildRiskAnalysisPrompt($project) {
        $projectInfo = json_encode($project, JSON_PRETTY_PRINT);
        
        return "Þú ert áhættugreiningarfræðingur. Greindu þetta verkefni og finndu hugsanlega áhættuþætti.

Verkefni:
{$projectInfo}

Finndu og flokkaðu áhættuþætti með:
1. Líkur
2. Áhrif
3. Mótvægisaðgerðir

JSON format:
{
  \"risks\": [
    {
      \"category\": \"string\",
      \"description\": \"string\",
      \"probability\": \"low|medium|high\",
      \"impact\": \"low|medium|high\",
      \"mitigation_strategies\": [\"array\"],
      \"early_warning_signs\": [\"array\"]
    }
  ],
  \"overall_risk_level\": \"low|medium|high\",
  \"recommendations\": [\"array\"]
}";
    }
    
    private function makeApiCall($prompt) {
        // Increase PHP execution time for this operation
        set_time_limit(300); // 5 minutes
        
        $data = [
            'model' => $this->model,
            'max_tokens' => 4000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => AnthropicConfig::getHeaders(),
            CURLOPT_TIMEOUT => 180, // 3 minutes for cURL timeout
            CURLOPT_CONNECTTIMEOUT => 60, // 1 minute for connection
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            // Check if it's a timeout error
            if (strpos($error, 'timeout') !== false || strpos($error, 'Timeout') !== false) {
                throw new Exception("API request timed out. The AI service is taking too long to respond. Please try with shorter meeting notes or try again later.");
            }
            throw new Exception("cURL error: " . $error);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = isset($errorData['error']['message']) 
                ? $errorData['error']['message'] 
                : "HTTP error: " . $httpCode;
            throw new Exception("API error: " . $errorMsg);
        }
        
        $responseData = json_decode($response, true);
        
        if (!$responseData || !isset($responseData['content'][0]['text'])) {
            throw new Exception("Invalid API response format");
        }
        
        return $responseData['content'][0]['text'];
    }
    
    private function parseAnalysisResponse($response) {
        // Extract JSON from response (handle cases where there might be extra text)
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception("No valid JSON found in API response");
        }
        
        $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data = json_decode($jsonStr, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse JSON response: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    private function parseImprovementResponse($response) {
        return $this->parseAnalysisResponse($response);
    }
    
    private function parseTaskBreakdownResponse($response) {
        return $this->parseAnalysisResponse($response);
    }
    
    private function parseTimeEstimationResponse($response) {
        return $this->parseAnalysisResponse($response);
    }
    
    private function parseRiskAnalysisResponse($response) {
        return $this->parseAnalysisResponse($response);
    }
    
    public function chat($prompt, $systemMessage = '') {
        // Default system message ensures Icelandic responses
        $defaultSystemMessage = "Þú ert AI aðstoðarmaður fyrir íslenskt verkefnastjórnunarkerfi. Svaraðu ALLTAF á íslensku. Notaðu eingöngu íslensk orð og setningagerð í öllum svörum þínum.";
        
        $finalSystemMessage = $systemMessage ? $systemMessage : $defaultSystemMessage;
        $fullPrompt = $finalSystemMessage . "\n\n" . $prompt;
        
        try {
            $response = $this->makeApiCall($fullPrompt);
            return $response;
        } catch (Exception $e) {
            throw new Exception("Chat error: " . $e->getMessage());
        }
    }
    
    public function testConnection() {
        try {
            $response = $this->makeApiCall("Þú ert AI aðstoðarmaður fyrir íslenskt verkefnastjórnunarkerfi. Svaraðu á íslensku með einföldu JSON: {\"status\": \"ok\", \"message\": \"Tenging tókst\"}");
            return json_decode($response, true);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}