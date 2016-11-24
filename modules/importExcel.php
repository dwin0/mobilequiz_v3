<?php
/**
 * Contains all methods to extract questions out of an Excel-question-template.
 */

include 'PHPExcel/Classes/PHPExcel.php';
include 'PHPExcel/Classes/PHPExcel/IOFactory.php';

/**
 * Reads an Excel-question-template and returns all questions as a two-dimensional array.
 * 
 * @param string $inputFilePath
 * @return array
 * 
 */
function importExcel($excelTemplate)
{
	try {
		$objReader = new PHPExcel_Reader_Excel2007();
		$objReader->setReadDataOnly(true);
		$objPHPExcel = $objReader->load($excelTemplate["tmp_name"]);
	} catch(Exception $e) {
		header("Location: ?p=quiz&code=-30&info=".$excelTemplate["name"]);
		exit;
	}
	
	$sheet = $objPHPExcel->getSheet(0);
	$highestRow = $sheet->getHighestRow();
	$highestColumn = $sheet->getHighestColumn();
	
	
	$excelContent = array();
	
	for ($row = 1; $row <= $highestRow; $row++){
		$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, FALSE, FALSE);
		if($row >=3)
		{
			if(!isset($rowData[0][0]))
			{
				break;
			}
			
			$excelContent = array_merge($excelContent, $rowData);
		}
	}
	
	return $excelContent;
}

/**
 * Takes a two-dimensional array of questions and returns an array of question-objects.
 * 
 * @param array $excelContent
 * @return array
 */
function createQuestionArray($excelContent)
{
	$questions = array();
	
	//get all questions
	for($i = 0; $i < count($excelContent); $i++)
	{
		$type = $excelContent[$i][0]; //first column
		$questionText = $excelContent[$i][1]; //second column
		$questionImage = $excelContent[$i][2]; //third column
		$answers = array();
		
		$lastAnswerRow = 13;
		
		//get all answers
		for($j = 3; $j <= $lastAnswerRow - 1; $j = $j+2)
		{
			$answerText = $excelContent[$i][$j];
			if(!isset($answerText))
			{
				continue;
			}
			
			//If Excel-Cell contains 'FALSCH', PHPExcel converts value into false. 'WAHR' turns into true
			if($answerText === false)
			{
				$answerText = "FALSCH";
			}
			if($answerText === true)
			{
				$answerText = "WAHR";
			}
			
			$isCorrect = isset($excelContent[$i][$j+1]);
			
			$answer = new Answer($answerText, $isCorrect);
			array_push($answers, $answer);
		}
		
		$question = new Question($type, $questionText, $questionImage, $answers);
		array_push($questions, $question);
	}
	
	return $questions;
}


class Question
{
	private $type;
	private $questionText;
	private $questionImage;
	private $answers = array();

	public function __construct($type, $questionText, $questionImage, $answers)
	{
		$this->type = $type;
		$this->questionText = $questionText;
		$this->questionImage = $questionImage;
		$this->answers = $answers;
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	public function getTypeCode()
	{
		switch($this->type)
		{
			case "Singlechoice Text":
				return 1;
			case "Multiplechoice Text":
				return 2;
		}
	}

	public function getText()
	{
		return $this->questionText;
	}
	
	public function getImage()
	{
		return $this->questionImage;
	}

	public function getAnswers()
	{
		return $this->answers;
	}
	
	public function getNumberOfAnswers()
	{
		return count($this->answers);
	}
	
	public function getNumberOfCorrectAnswers()
	{
		$counter = 0;
		foreach($this->answers as $answer)
		{
			if($answer->isCorrect())
			{
				$counter++;
			}
		}
		return $counter;
	}
	
	public function isValid()
	{
		if($this->type == "Singlechoice Text" && $this->getNumberOfCorrectAnswers() != 1)
		{
			return false;
		}
		return true;
	}
}

class Answer
{
	private $answerText;
	private $isCorrect;
	
	public function __construct($answerText, $isCorrect)
	{
		$this->answerText = $answerText;
		$this->isCorrect = $isCorrect;
	}
	
	public function getText()
	{
		return $this->answerText;
	}
	
	public function isCorrect()
	{
		return $this->isCorrect;
	}
}

?>