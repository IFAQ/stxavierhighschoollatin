<?php

$passingScore = 8;
$standardMax = 10;
$standardPerPassing = 1;
$bonusMax = 10.5;
$bonusPerPassing = .1;

$fileToRead = "quizresults.csv";
$fileToWrite = "graderesults.csv";

$readHandle = fopen($fileToRead, 'r');
$writeHandle = fopen($fileToWrite, 'w');

$currentStudentFirstName = null;
$currentStudentLastName = null;

$headerRow = null;

$students = array();

while($row = fgetcsv($readHandle))
{
    if(!$headerRow)
    {
        $headerRow = array(
            'Last Name',
            'First Name',
            'Date',
            'Passing Scores',
            'Daily Points'
        );
        
        fputcsv($writeHandle, $headerRow);
        
        continue;
    }
    
    $studentKey = $row[1].':'.$row[0];
    
    if(!array_key_exists($studentKey, $students))
    {
        $newStudent = new stdClass();
        $newStudent->FirstName = $row[1];
        $newStudent->LastName = $row[0];
        $newStudent->QuizResultsByDate = array();
        
        $students[$studentKey] = $newStudent;
    }
    
    $currentStudent = $students[$studentKey];
    
    $currentDate = new DateTime($row[6]);
    $currentDateKey = $currentDate->format('Y-m-d');
    
    if(!array_key_exists($currentDateKey, $currentStudent->QuizResultsByDate))
    {
        $currentStudent->QuizResultsByDate[$currentDateKey] = 0;
    }
    
    $currentDatePassingScoreCount = $currentStudent->QuizResultsByDate[$currentDateKey];
    
    if($row[9] >= $passingScore)
    {
        $currentDatePassingScoreCount++;
    }
    
    $currentStudent->QuizResultsByDate[$currentDateKey] = $currentDatePassingScoreCount;
}

foreach($students as $student)
{
    foreach($student->QuizResultsByDate as $date => $passCounter)
    {
        echo $student->LastName."\n";
        $dailyPoints = 0;
        $numberOfPassingGrades = $passCounter;
        $resultDate = new DateTime($date);
        
        $dailyPoints = $passCounter;
        
        if($dailyPoints > $standardMax)
        {
            $bonusPoints = ($passCounter - $standardMax) * $bonusPerPassing;
            
            $dailyPoints = $standardMax + $bonusPoints;
            
            if($dailyPoints > $bonusMax)
            {
                $dailyPoints = $bonusMax;
            }
        }
        
        fputcsv($writeHandle,
            array(
                $student->LastName,
                $student->FirstName,
                $resultDate->format('m/d/Y'),
                $numberOfPassingGrades,
                $dailyPoints
            )
        );
    }
    
}


?>