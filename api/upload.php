<?php
header('Content-Type: application/json');

// Create uploads directory if it doesn't exist
$uploadDir = '../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle file upload
if (isset($_FILES['quizFile'])) {
    $file = $_FILES['quizFile'];
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Upload failed']);
        exit;
    }

    // Verify file type
    $mimeType = mime_content_type($file['tmp_name']);
    if ($mimeType !== 'text/csv' && $mimeType !== 'text/plain') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Please upload a CSV file.']);
        exit;
    }

    // Generate unique filename
    $filename = 'quiz_' . date('Y-m-d_H-i-s') . '.csv';
    $destination = $uploadDir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Validate CSV format
        if (($handle = fopen($destination, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            if (count($header) !== 7 || 
                $header[0] !== 'type' ||
                $header[1] !== 'question' || 
                $header[6] !== 'correct_answer') {
                unlink($destination);
                fclose($handle);
                http_response_code(400);
                echo json_encode(['error' => 'Invalid CSV format. Required columns: type,question,option1,option2,option3,option4,correct_answer']);
                exit;
            }

            // Validate question types
            $line = 2;
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) !== 7) {
                    unlink($destination);
                    fclose($handle);
                    http_response_code(400);
                    echo json_encode(['error' => "Invalid number of columns at line $line"]);
                    exit;
                }

                $type = strtolower(trim($data[0]));
                if (!in_array($type, ['multiple_choice', 'fill_blank', 'open_ended'])) {
                    unlink($destination);
                    fclose($handle);
                    http_response_code(400);
                    echo json_encode(['error' => "Invalid question type '$type' at line $line. Must be: multiple_choice, fill_blank, or open_ended"]);
                    exit;
                }
                $line++;
            }
            fclose($handle);
        }

        // Redirect to quiz page
        header('Location: ../index.html');
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'No file uploaded']);
?>
