<?php

$stdIn = fopen('php://stdin', 'r');

$test = new Test();

echo 'Do you want to use an existing test? [y/N] ';

if(strtolower(trim(fgets($stdIn))) == 'y')
{
    echo 'What is the file name of the test? ';
    
    $testFileName = trim(fgets($stdIn));
    
    if(substr($testFileName, -9) !== '.noratest')
    {
        $testFileName = $testFileName.'.noratest';
    }
    
    $testAsString = file_get_contents($testFileName);
    
    $test = unserialize($testAsString);
}
else
{
    echo 'Name: ';
    
    $test->setName(trim(fgets($stdIn)));
    
    $addAnother = true;
    
    $reportHandle = fopen('testanalysis.csv', 'w');
    
    $headers = array('Student');
    
    while($addAnother === true)
    {
        echo '(S)ection or (Q)uestion [S]: ';
        
        $typeSet = false;
        
        while($typeSet === false)
        {
            $typeSelector = strtoupper(trim(fgets($stdIn)));
            
            switch($typeSelector)
            {
                case 'Q':
                    $type = 'question';
                    $typeSet = true;
                    break;
                case 'S':
                case '':
                    $type = 'section';
                    $typeSet = true;
                    break;
                default:
                    echo 'ERROR: '.$typeSelector.' is not a valid value'."\n".'(S)ection or (Q)uestion [S]: ';
                    break;
            }
        }
        
        echo 'What is the name for this '.$type.'? ';
        
        $name = trim(fgets($stdIn));
        
        if($type === 'question')
        {
            $question = new Question();
            $question->setName($name);
            
            echo 'How many points is the question worth? ';
            
            $question->setPointsAvailable(trim(fgets($stdIn)));
            
            $test->addSection($question);
        }
        else
        {
            $section = new QuestionChoiceSection();
            $section->setName($name);
            
            echo 'What are the point values for the questions? ';
            
            $pointValues = explode(',', trim(fgets($stdIn)));
            
            foreach($pointValues as $pointValue)
            {
                $question = new Question();
                
                $question->setPointsAvailable($pointValue);
                
                $section->addQuestion($question);
            }
            
            echo 'How many questions must be answered? ';
            
            $countMinimumAnswersSet = false;
            
            while($countMinimumAnswersSet === false)
            {
                try
                {
                    $section->setCountMinimumAnswers(trim(fgets($stdIn)));
                }
                catch(Exception $e)
                {
                    echo 'ERROR: '.$e->getMessage()."\n";
                    echo 'How many questions must be answered? ';
                    continue;
                }
                
                $countMinimumAnswersSet = true;
            }
            
            $test->addSection($section);
        }
        
        echo 'Add more? [y/N] ';
        
        if(trim(fgets($stdIn)) !== 'y')
        {
            $addAnother = false;
        }
    }
    
    //write test to file
    $testHandle = fopen(str_replace(' ', '_', $test->getName()).'.noratest', 'w');
    
    fwrite($testHandle, serialize($test));
    
    fclose($testHandle);
}


//create CSV file for test results
$reportHandle = fopen('testanalysis.csv', 'w');

$headers = array('Student');

foreach($test->getSections() as $section)
{
    if($section instanceof Question)
    {
        $headers[] = $section->getName().' ('.$section->getPointsAvailable().')';
    }
    else
    {
        $prefix = $section->getName();
        
        $headers[] = $prefix;
        
        $questionCounter = 0;
        
        foreach($section->getQuestions() as $question)
        {
            $questionCounter++;
            $headers[] = $prefix.'/'.$questionCounter.' ('.$question->getPointsAvailable().')';
        }
    }
}

$headers[] = 'Bonus';
$headers[] = 'Total Gotten';
$headers[] = 'Total Attempted';
$headers[] = '/100';

fputcsv($reportHandle, $headers);


//ask for student information
echo 'Do you have a list of students? [Y/n] ';

if(strtolower(trim(fgets($stdIn)) === 'n'))
{
    $studentNameArray = array();
}
else
{
    echo 'What is the filename with the student list? ';
    
    $studentListFileName = trim(fgets($stdIn)).'.txt';
    
    if($studentList = file_get_contents($studentListFileName))
    {
        $studentNameArray = explode("\n", $studentList);
    }
    else
    {
        echo 'No file matching the filename was found'."\n";
    }
}

//grading
$studentArray = array();
$lastStudent = false;
$useStudentList = false;
$studentListIndex = 0;

if(count($studentNameArray > 0))
{
    $useStudentList = true;
}

while($lastStudent === false)
{
    $student = new Student();
    
    if($useStudentList == true)
    {
        $studentName = $studentNameArray[$studentListIndex];
        
        echo $studentName."\n";
        
        $studentListIndex++;
    }
    else
    {
        echo 'Who is the student? ';
        
        $studentName = trim(fgets($stdIn));
    }
    
    $student->setName($studentName);
    
    $data = array($student->getName());
        
    $testClone = clone $test;
    
    $student->setTest($testClone);
    
    foreach($testClone->getSections() as $section)
    {
        if($section instanceof Question)
        {
            $possiblePoints = $section->getPointsAvailable();
            
            echo $section->getName().' ('.$possiblePoints.'): ';
            
            $pointsOff = trim(fgets($stdIn));
            
            if($pointsOff === '')
            {
                $points = 0;
            }
            else
            {
                $points = $possiblePoints - $pointsOff;
            }
            
            $section->setPointsGotten($points);
        }
        else
        {
            echo $section->getName().":\n";
            
            foreach($section->getQuestions() as $question)
            {
                $possiblePoints = $question->getPointsAvailable();
                
                echo "\t".' ('.$possiblePoints.'): ';
                
                $pointsOff = trim(fgets($stdIn));
                
                if($pointsOff === '')
                {
                    $points = 0;
                }
                else
                {
                    $points = $possiblePoints - $pointsOff;
                }
                
                $question->setPointsGotten($points);
            }
        }
    }
    
    echo 'Bonus (0): ';
    
    $bonusPoints = trim(fgets($stdIn));
    
    if($bonusPoints == '')
    {
        $bonusPoints = 0;
    }
    
    $testClone->setBonusPoints($bonusPoints);
    
    $testClone->analyze();
    
    foreach($testClone->getSections() as $testSection)
    {
        if($testSection instanceof Question)
        {
            $data[] = $testSection->getPointsGotten();
        }
        else
        {
            $data[] = $testSection->getPointsGotten();
            
            foreach($testSection->getQuestions() as $question)
            {
                $data[] = ($question->getPointsGotten() > 0) ? $question->getPointsGotten() : '';
            }
        }
    }
    
    $totalPointsGotten = $testClone->getTotalPointsGotten();
    $totalPointsAttempted = $testClone->getTotalPointsAttempted();
    $bonusPoints = $testClone->getBonusPoints();
    
    $percentage = round(100 * ($totalPointsGotten + $bonusPoints) / $totalPointsAttempted, 0);
    
    echo $totalPointsGotten.' + '.$bonusPoints.' / '.$totalPointsAttempted.' = '.$percentage."\n";
    
    $data[] = $bonusPoints;
    $data[] = $totalPointsGotten;
    $data[] = $totalPointsAttempted;
    $data[] = $percentage;
    
    fputcsv($reportHandle, $data);
    
    if($useStudentList == true)
    {
        if($studentListIndex == count($studentNameArray))
        {
            $lastStudent = true;
        }
    }
    else
    {    
        echo 'Score for another student? [Y/n] ';
        
        if(trim(fgets($stdIn)) === 'n')
        {
            $lastStudent = true;
        }
    }
}

fclose($reportHandle);

echo 'Have a good day!'."\n";


class Test {
    
    protected $bonusPoints = 0;
    protected $name = 'Test';
    protected $sections = array();
    protected $totalPointsAttempted;
    protected $totalPointsGotten;
    
    function __clone()
    {
        $newSections = array();
        
        foreach($this->sections as $section)
        {
            $newSections[] = clone $section;
        }
        
        $this->sections = $newSections;
        
    }
    
    public function addSection($section)
    {
        $this->sections[] = $section;
    }
    
    public function analyze()
    {
        $this->totalPointsGotten = 0;
        $this->totalPointsAttempted = 0;
        
        foreach($this->sections as $section)
        {
            $this->totalPointsGotten += $section->getPointsGotten();
            $this->totalPointsAttempted += $section->getPointsAvailable();
        }
    }
    
    public function getBonusPoints()
    {
        return $this->bonusPoints;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getSections()
    {
        return $this->sections;
    }
    
    public function getTotalPointsAttempted()
    {
        return $this->totalPointsAttempted;
    }
    
    public function getTotalPointsGotten()
    {
        return $this->totalPointsGotten;
    }
    
    public function setBonusPoints($v)
    {
        $this->bonusPoints = $v;
    }
    
    public function setName($v)
    {
        $this->name = $v;
    }
    
}

class Student {

    protected $name;
    protected $test;
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getTest()
    {
        return $this->test;
    }
    
    public function setName($v)
    {
        $this->name = $v;
    }
    
    public function setTest(Test $v)
    {
        $this->test = $v;
    }

}

class QuestionChoiceSection {
    
    protected $countMinimumAnswers;
    protected $isSorted = false;
    protected $name;
    protected $questions = array();
    protected $sortedQuestions = array();
    
    public function addQuestion($question)
    {
        $this->questions[] = $question;
        
        $this->isSorted = false;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getPointsAvailable()
    {
        if($this->isSorted !== true)
        {
            $this->sortedQuestions = $this->questions;
            
            usort($this->sortedQuestions, array('QuestionChoiceSection', 'sortGottenPoints'));
            
            $this->isSorted = true;
        }
        
        $pointsAvailable = 0;
        $counter = 0;
        
        foreach($this->sortedQuestions as $question)
        {
            $counter++;
            
            if($counter > $this->countMinimumAnswers)
            {
                break;
            }
            
            $pointsAvailable += $question->getPointsAvailable();
        }
        
        return $pointsAvailable;
    }
    
    public function getPointsGotten()
    {
        if($this->isSorted !== true)
        {
            $this->sortedQuestions = $this->questions;
            
            usort($this->sortedQuestions, array('QuestionChoiceSection', 'sortGottenPoints'));
            
            $this->isSorted = true;
        }
        
        $pointsGotten = 0;
        $counter = 0;
        
        foreach($this->sortedQuestions as $question)
        {
            $counter++;
            
            if($counter > $this->countMinimumAnswers)
            {
                break;
            }
            
            $pointsGotten += $question->getPointsGotten();
        }
        
        return $pointsGotten;
    }
    
    public function getQuestions()
    {
        return $this->questions;
    }
    
    public function getSortedQuestions()
    {
        return $this->sortedQuestions;
    }
    
    public function setCountMinimumAnswers($v)
    {
        if($v > count($this->questions))
        {
            throw new Exception('The number of required answers ('.$v.') cannot exceed the number of questions in the section ('.count($this->questions).')');
        }
        
        $this->countMinimumAnswers = $v;
        
        $this->isSorted = false;
    }
    
    public function setName($v)
    {
        $this->name = $v;
    }
    
    function sortGottenPoints($a, $b)
    {
        if($a->getDifference() === $b->getDifference())
        {
            if($a->getPointsAvailable() === $b->getPointsAvailable())
            {
                return 0;
            }
            
            return ($a->getPointsAvailable() < $b->getPointsAvailable()) ? -1 : 1;
        }
        
        return ($a->getDifference() < $b->getDifference()) ? -1 : 1;
    }    
}

class Question {

    protected $name;
    protected $pointsAvailable;
    protected $pointsGotten;
    
    public function getDifference()
    {
        return $this->pointsAvailable - $this->pointsGotten;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getPointsAvailable()
    {
        return $this->pointsAvailable;
    }
    
    public function getPointsGotten()
    {
        return $this->pointsGotten;
    }
    
    public function setPointsAvailable($v)
    {
        $this->pointsAvailable = $v;
    }
    
    public function setPointsGotten($v)
    {
        $this->pointsGotten = $v;
    }
    
    public function setName($v)
    {
        $this->name = $v;
    }

}