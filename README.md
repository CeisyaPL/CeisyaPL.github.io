# Local Quiz System with CSV Upload

This is a quiz system that runs on a local XAMPP server. It features a CSV upload system for quiz questions, a frontend interface for taking quizzes, and a PHP API endpoint for checking and scoring answers.

## Setup Instructions

1. Copy this entire folder to your XAMPP's htdocs directory (usually `C:\xampp\htdocs\API SCORE`)
2. Start your XAMPP Apache server
3. Access the upload interface at: `http://localhost/API SCORE/upload.html`
4. Upload your quiz CSV file
5. Take the quiz at: `http://localhost/API SCORE`

## CSV File Format

Your CSV file should follow this format:
```
question,option1,option2,option3,option4,correct_answer
What is 2+2?,1,2,3,4,4
Who is CEO of Tesla?,Bill Gates,Elon Musk,Jeff Bezos,Tim Cook,Elon Musk
```

- First row must be the header row exactly as shown above
- Each subsequent row contains:
  - A question
  - Four answer options
  - The correct answer (must match one of the options exactly)

## Features

- CSV file upload for quiz questions
- Multiple choice questions
- Instant scoring via API
- Score display with percentage
- Bootstrap-based responsive design
- Automatic loading of most recent quiz file

## Technical Details

- Frontend: HTML, JavaScript (jQuery), Bootstrap
- Backend: PHP API endpoints
- Data Format: CSV for questions, JSON for API communication
- File storage: Local file system (uploads folder)
