<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['studentName']) || empty($data['className']) || empty($data['subject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Prepare the CSV data
$csvData = [
    $data['timestamp'],
    $data['studentName'],
    $data['score'],
    $data['total'],
    $data['percentage']
];

// Create results directory structure
$baseDir = '../results/';
$classDir = $baseDir . $data['className'] . '/';
$subjectDir = $classDir . $data['subject'] . '/';

// Create directories if they don't exist
foreach ([$baseDir, $classDir, $subjectDir] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Get the current date for the filename
$date = date('Y-m');
$filename = $subjectDir . "quiz_results_{$date}.csv";

// Create header if file doesn't exist
$fileExists = file_exists($filename);
if (!$fileExists) {
    $header = ['Timestamp', 'Student Name', 'Score', 'Total Questions', 'Percentage'];
    $fp = fopen($filename, 'w');
    fputcsv($fp, $header);
    fclose($fp);
}

// Append the result
$fp = fopen($filename, 'a');
fputcsv($fp, $csvData);
fclose($fp);

// Create index.html files to prevent directory listing
$indexContent = '<html><head><title>Access Denied</title></head><body><h1>Access Denied</h1></body></html>';
file_put_contents($baseDir . 'index.html', $indexContent);
file_put_contents($classDir . 'index.html', $indexContent);
file_put_contents($subjectDir . 'index.html', $indexContent);

echo json_encode([
    'success' => true,
    'message' => 'Result saved successfully',
    'path' => $filename
]);
?>
