<?php

$passingScore = 8;
$onePointMax = 10;
$tenthPointMax = 10.5;

$fileToRead = "quizresults.csv";
$fileToWrite = "graderesults.csv";

$readHandle = fopen($fileToRead, 'r');
$writeHandle = fopen($fileToWrite, 'w');

$currentStudentFirstName = null;
$currentStudentLastName = null;

$headerRow = null;

while($row = fgetcsv($readHandle))
{
    if(!$headerRow)
    {
        $headerRow = array(
            'Last Name',
            'First Name',
            'Passing Scores',
            'Daily Points'
        );
        
        fputcsv($writeHandle, $headerRow);
        
        continue;
    }
    
    if($currentStudentFirstName !== $row[1] || $currentStudentLastName !== $row[0])
    {
        if($currentStudentFirstName)
        {
            $numberOfPassingGrades = $passCounter;
            
            while($dailyPoints < $onePointMax && $passCounter > 0)
            {
                $dailyPoints++;
                
                $passCounter--;
            }
            
            while($dailyPoints < $tenthPointMax && $passCounter > 0)
            {
                $dailyPoints = $dailyPoints + .1;
                
                $passCounter--;
            }
            
            fputcsv($writeHandle,
                array(
                    $currentStudentLastName,
                    $currentStudentFirstName,
                    $numberOfPassingGrades,
                    $dailyPoints
                )
            );
        }
        
        $currentStudentFirstName = $row[1];
        $currentStudentLastName = $row[0];
        $passCounter = 0;
        $dailyPoints = 0;
    }
    
    if($row[9] >= $passingScore)
    {
        $passCounter++;
    }
}


?>