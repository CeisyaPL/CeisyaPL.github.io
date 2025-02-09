<?php
session_start();
require_once 'vendor/autoload.php';

class GoogleSheetsExport {
    private $client;
    private $service;
    private $spreadsheetId;
    
    public function __construct() {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(__DIR__ . '/credentials.json');
        $this->client->setRedirectUri('http://192.168.140.141/API SCORE/api/oauth2callback.php');
        $this->client->addScope(Google_Service_Sheets::SPREADSHEETS);
        
        // Check if we have a valid access token
        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $this->client->setAccessToken($_SESSION['access_token']);
            if ($this->client->isAccessTokenExpired()) {
                unset($_SESSION['access_token']);
                header('Location: ' . $this->client->createAuthUrl());
                exit;
            }
        } else {
            header('Location: ' . $this->client->createAuthUrl());
            exit;
        }
        
        $this->service = new Google_Service_Sheets($this->client);
    }
    
    public function createSpreadsheet($title) {
        $spreadsheet = new Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => $title
            ]
        ]);
        
        $spreadsheet = $this->service->spreadsheets->create($spreadsheet);
        $this->spreadsheetId = $spreadsheet->spreadsheetId;
        return $this->spreadsheetId;
    }
    
    public function appendData($range, $values) {
        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);
        
        $params = [
            'valueInputOption' => 'RAW'
        ];
        
        $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }
    
    public function exportResults($className, $subject) {
        // Create new spreadsheet for this class and subject
        $title = "Quiz Results - $className - $subject - " . date('Y-m-d H:i:s');
        $this->createSpreadsheet($title);
        
        // Add headers
        $headers = [
            ['Timestamp', 'Student Name', 'Class', 'Subject', 'Score', 'Total', 'Percentage', 'Answers']
        ];
        $this->appendData('Sheet1!A1:H1', $headers);
        
        // Get all result files for this class and subject
        $resultsDir = __DIR__ . "/../results/$className/$subject";
        if (!is_dir($resultsDir)) {
            throw new Exception("No results found for $className - $subject");
        }
        
        $results = [];
        $files = glob("$resultsDir/*.json");
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            $results[] = [
                $data['timestamp'],
                $data['studentName'],
                $data['className'],
                $data['subject'],
                $data['score'],
                $data['total'],
                $data['percentage'],
                json_encode($data['answers'])
            ];
        }
        
        // Add data
        if (!empty($results)) {
            $this->appendData('Sheet1!A2:H' . (count($results) + 1), $results);
        }
        
        return [
            'spreadsheetId' => $this->spreadsheetId,
            'url' => "https://docs.google.com/spreadsheets/d/" . $this->spreadsheetId
        ];
    }
}

// Handle API request
header('Content-Type: application/json');

try {
    // Validate request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['className']) || !isset($data['subject'])) {
        throw new Exception('Missing class name or subject');
    }
    
    $className = $data['className'];
    $subject = $data['subject'];
    
    // Initialize Google Sheets export
    $exporter = new GoogleSheetsExport();
    $result = $exporter->exportResults($className, $subject);
    
    echo json_encode([
        'success' => true,
        'message' => 'Results exported successfully',
        'spreadsheetUrl' => $result['url']
    ]);
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
