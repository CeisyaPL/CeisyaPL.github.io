<?php
header('Content-Type: application/json');

// Get the most recent CSV file
$upload_dir = '../uploads/';
$files = glob($upload_dir . 'quiz_*.csv');

if (empty($files)) {
    echo json_encode(['error' => 'No quiz file found']);
    exit;
}

// Get the most recent file
$latest_file = max($files);

// Read questions from CSV
$questions = [];
if (($handle = fopen($latest_file, "r")) !== FALSE) {
    // Skip header row
    fgetcsv($handle);
    
    // Read questions
    $question_number = 1;
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) >= 7) {
            $type = strtolower(trim($data[0]));
            $question = [
                'id' => $question_number,
                'type' => $type,
                'question' => $data[1]
            ];

            // Handle different question types
            switch ($type) {
                case 'multiple_choice':
                    $question['options'] = array_slice($data, 2, 4);
                    break;
                case 'fill_blank':
                    // For fill in the blank, we don't send any options
                    break;
                case 'open_ended':
                    // For open-ended questions, we don't send any options
                    break;
            }

            $questions[] = $question;
            $question_number++;
        }
    }
    fclose($handle);
}

echo json_encode(['questions' => $questions]);
?>
