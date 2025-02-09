$(document).ready(function() {
    let questions = [];
    let isSubmitting = false;
    let touchStartY = 0;
    let touchEndY = 0;

    // Handle mobile input focus
    function handleMobileInputs() {
        // Handle radio button clicks with scroll detection
        $(document).on('touchstart', '.form-check', function(e) {
            touchStartY = e.originalEvent.touches[0].clientY;
        });

        $(document).on('touchend', '.form-check', function(e) {
            touchEndY = e.changedTouches[0].clientY;
            
            // If user scrolled more than 10px, don't select the option
            if (Math.abs(touchStartY - touchEndY) < 10) {
                const radio = $(this).find('input[type="radio"]');
                if (radio.length) {
                    radio.prop('checked', true);
                }
            }
        });

        // Enable text selection for inputs and textareas
        $('input[type="text"], textarea').on('touchstart', function(e) {
            e.stopPropagation();
        });

        // Fix mobile keyboard issues
        $('input[type="text"]').on('focus', function() {
            $(this).attr('inputmode', 'text');
        });
    }

    // Load questions from the latest CSV file
    $.ajax({
        url: 'api/get_questions.php',
        method: 'GET',
        success: function(response) {
            if (response.error) {
                $('#questions').html(`
                    <div class="alert alert-warning">
                        ${response.error}. Please upload a quiz file first.
                        <br>
                        <a href="upload.html" class="btn btn-primary mt-2">Upload Quiz File</a>
                    </div>
                `);
                return;
            }
            
            questions = response.questions;
            displayQuestions();
            handleMobileInputs();
        },
        error: function() {
            $('#questions').html(`
                <div class="alert alert-danger">
                    Error loading questions. Please try again.
                    <br>
                    <a href="upload.html" class="btn btn-primary mt-2">Upload Quiz File</a>
                </div>
            `);
        }
    });

    // Display questions based on their type
    function displayQuestions() {
        const questionsContainer = $('#questions');
        questions.forEach((q, index) => {
            let questionHtml = `
                <div class="question">
                    <h5>Question ${index + 1}</h5>
                    <p class="mb-3">${q.question}</p>
            `;

            switch (q.type) {
                case 'multiple_choice':
                    questionHtml += q.options.map((option, i) => `
                        <div class="form-check" role="button" tabindex="0">
                            <input class="form-check-input" type="radio" name="q${q.id}" value="${option}" id="q${q.id}o${i}" required>
                            <label class="form-check-label w-100" for="q${q.id}o${i}">
                                ${option}
                            </label>
                        </div>
                    `).join('');
                    break;

                case 'fill_blank':
                    questionHtml += `
                        <div class="mb-3">
                            <input type="text" class="form-control" name="q${q.id}" required
                                inputmode="text"
                                autocomplete="off"
                                autocorrect="off"
                                autocapitalize="off">
                        </div>
                    `;
                    break;

                case 'open_ended':
                    questionHtml += `
                        <div class="mb-3">
                            <textarea class="form-control" name="q${q.id}" rows="4" required 
                                placeholder="Type your answer here..."
                                autocomplete="off"></textarea>
                        </div>
                    `;
                    break;
            }

            questionHtml += '</div>';
            questionsContainer.append(questionHtml);
        });
    }

    // Handle form submission
    $('#quizForm').on('submit', function(e) {
        e.preventDefault();
        
        if (isSubmitting) return;
        isSubmitting = true;

        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Submitting...');

        // Validate student information
        const studentName = $('#studentName').val().trim();
        const className = $('#className').val().trim();
        const subject = $('#subject').val().trim();

        if (!studentName || !className || !subject) {
            alert('Please fill in all student information fields');
            submitBtn.prop('disabled', false).html(originalBtnText);
            isSubmitting = false;
            return;
        }
        
        // Collect all answers
        const answers = {};
        let allAnswered = true;

        questions.forEach((q) => {
            const input = $(`[name="q${q.id}"]`);
            const answer = q.type === 'multiple_choice' ? 
                input.filter(':checked').val() : 
                input.val();
            
            if (!answer || answer.trim() === '') {
                allAnswered = false;
                return false; // break the loop
            }
            answers[q.id] = answer;
        });

        if (!allAnswered) {
            alert('Please answer all questions before submitting.');
            submitBtn.prop('disabled', false).html(originalBtnText);
            isSubmitting = false;
            return;
        }

        // Prepare submission data
        const submissionData = {
            studentName: studentName,
            className: className,
            subject: subject,
            answers: answers
        };

        // Send answers to API
        $.ajax({
            url: 'api/check_answers.php',
            method: 'POST',
            data: JSON.stringify(submissionData),
            contentType: 'application/json',
            success: function(response) {
                if (!response.success) {
                    $('#result').removeClass()
                        .addClass('result alert alert-warning')
                        .html(response.error || 'An error occurred while submitting the quiz.')
                        .show();
                    return;
                }

                // Show the score
                const result = $('#result');
                result.html(`
                    <div class="text-center">
                        <h4>Quiz Results for ${studentName}</h4>
                        <h5>Score: ${response.score}/${response.total}</h5>
                        <p>Percentage: ${response.percentage}%</p>
                    </div>
                `);
                result.removeClass().addClass('result alert ' + 
                    (response.percentage >= 70 ? 'alert-success' : 'alert-warning')).show();

                // Disable all inputs after submission
                $('input, textarea, select, button[type="submit"]').prop('disabled', true);

                // Scroll to results
                $('html, body').animate({
                    scrollTop: result.offset().top - 20
                }, 500);
            },
            error: function(xhr) {
                let errorMessage = 'Error submitting quiz. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
                
                $('#result').removeClass()
                    .addClass('result alert alert-danger')
                    .html(errorMessage)
                    .show();
            },
            complete: function() {
                isSubmitting = false;
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
});
