<?php

require_once('config.php');
global $DB;

for ($i = 0; $i < 30; $i++) {
    $record = new \stdClass();
    $record->question = $i;                
    $record->answer = "Answer for question " . $i;
    $record->answerformat = 1;                
    $record->fraction = 1.0;                
    $record->feedback = "<p>Feedback for question " . $i . "</p>";     
    $record->feedbackformat = 1;     

    try {
        // Insert the record into the test_question table
        $DB->insert_record('test_question_answers', $record);
    } catch (\Exception $e) {
        echo $e->getMessage();
    }
}


for ($i = 0; $i < 30; $i++) {
    $record = new \stdClass();
    $record->questionusageid = $i+time();                
    $record->slot = $i;
    $record->behaviour = 'deferredfeedback';                
    $record->questionid = 1;                
    $record->variant = 1;     
    $record->maxmark = 15.0000000;     
    $record->minfraction = 0;     
    $record->maxfraction = 1.0000000;     
    $record->flagged = 1;     
    $record->questionsummary = 'Bạn thấy BQNS có lợi ích gì?    : Tránh cho Nông sản không bị nấm, thối, hỏng    ; Duy trì được giống tốt cho mùa vụ sau    ; Bảo vệ sức khỏe cộng đồng    ; Tất cả các lựa chọn trên    ';     
    $record->rightanswer = 'Tất cả các lựa chọn trên';     
    $record->responsesummary = '2 lần trong năm ; 3 lần trong năm ';     
    $record->timemodified = 1695355647;     
    try {
        // Insert the record into the test_question table
        $DB->insert_record('test_question_attempts', $record);
    } catch (\Exception $e) {
        echo $e->getMessage();
    }
}

for ($i = 0; $i < 60; $i++) {
    $record = new \stdClass();
    $record->questionattemptid = $i+time();                
    $record->sequencenumber = $i;
    $record->state = 'complete';                
    $record->fraction = 1;                
    $record->timecreated = 1695355647;     
    $record->userid = 1234;         
    // Insert the record into the test_question table
    try {
        $DB->insert_record('test_question_attempt_steps', $record);
    } catch (\Exception $e) {
        echo $e->getMessage();
    }
}


   
echo 'Done3';die();

?>