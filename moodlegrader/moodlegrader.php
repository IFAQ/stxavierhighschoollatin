<?php

$passingScore = 8;
$passesNeeded = 5;

/*
$fileToRead = "~/Users/jimmie/Documents/Scripts/quizresults.csv";

$handle = fopen($fileToRead, 'r');

$data = fgetcsv($handle, 1000);
*/

$students = array(
    'Student 1' => array(5,8,9,10,10,8,7,9),
    'Student 2' => array(2,3,10,7,8,7,9),
    'Student 3' => array(4,6,10,7,8,9,9,9,9)
);

foreach($students as $name => $scores)
{
    $passCounter = 0;
    
    foreach($scores as $score)
    {
        if($score >= $passingScore)
        {
            $passCounter++;
        }
    }
    
    echo $name.': '.$passCounter.'/n';
}

?>