<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get the raw POST data
    $rawData = file_get_contents('php://input');
    
    // Log the raw data for debugging
    error_log("Received data: " . $rawData);
    
    $data = json_decode($rawData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Extract student info and answers
    $answers = isset($data['answers']) ? $data['answers'] : null;
    $studentName = isset($data['studentName']) ? trim($data['studentName']) : '';
    $className = isset($data['className']) ? trim($data['className']) : '';
    $subject = isset($data['subject']) ? trim($data['subject']) : '';

    if (!$answers) {
        throw new Exception('No answers received');
    }

    if (empty($studentName) || empty($className) || empty($subject)) {
        throw new Exception('Missing student information');
    }

    // Load questions from CSV
    $csvFile = '../question_templates.csv';
    
    if (!file_exists($csvFile)) {
        throw new Exception('Question file not found');
    }

    $questions = [];
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        // Skip header row
        fgetcsv($handle);
        
        $id = 1;
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 7) { // Make sure we have enough columns
                $questions[] = [
                    'id' => $id,
                    'type' => $data[0],
                    'question' => $data[1],
                    'options' => array_filter(array_slice($data, 2, 4)), // Remove empty options
                    'correct_answer' => $data[6]
                ];
                $id++;
            }
        }
        fclose($handle);
    }

    if (empty($questions)) {
        throw new Exception('No questions found in the template');
    }

    // Check answers
    $score = 0;
    $total = count($questions);
    $feedback = [];

    foreach ($questions as $q) {
        $userAnswer = isset($answers[$q['id']]) ? trim($answers[$q['id']]) : '';
        
        if ($userAnswer === '') {
            continue;
        }

        switch ($q['type']) {
            case 'multiple_choice':
            case 'fill_blank':
                if (strcasecmp($userAnswer, trim($q['correct_answer'])) === 0) {
                    $score++;
                }
                break;

            case 'open_ended':
                // For open-ended questions, we'll do basic keyword matching
                $keywords = explode(' ', strtolower(trim($q['correct_answer'])));
                $answerText = strtolower($userAnswer);
                $matches = 0;
                
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) > 3 && strpos($answerText, $keyword) !== false) {
                        $matches++;
                    }
                }
                
                $score += ($matches / count($keywords));
                break;
        }
    }

    $percentage = round(($score / $total) * 100);

    // Save results
    $resultsDir = "../results/{$className}/{$subject}";
    if (!file_exists($resultsDir)) {
        mkdir($resultsDir, 0777, true);
    }

    $timestamp = date('Y-m-d_H-i-s');
    $resultsFile = "{$resultsDir}/{$studentName}_{$timestamp}.json";
    
    $resultData = [
        'timestamp' => $timestamp,
        'studentName' => $studentName,
        'className' => $className,
        'subject' => $subject,
        'score' => $score,
        'total' => $total,
        'percentage' => $percentage,
        'answers' => $answers
    ];

    file_put_contents($resultsFile, json_encode($resultData, JSON_PRETTY_PRINT));

    echo json_encode([
        'success' => true,
        'score' => round($score, 1),
        'total' => $total,
        'percentage' => $percentage
    ]);

} catch (Exception $e) {
    error_log("Quiz submission error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
