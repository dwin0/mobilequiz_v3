<?php

include 'PHPExcel/Classes/PHPExcel.php';
include 'PHPExcel/Classes/PHPExcel/IOFactory.php';

function importExcel($inputFileName)
{
	try {
		$objReader = new PHPExcel_Reader_Excel2007();
		$objReader->setReadDataOnly(true);
		$objPHPExcel = $objReader->load($inputFileName);
	} catch(Exception $e) {
		$fileName = str_replace("excelTemplate/", "", $inputFileName);
		header("Location: ?p=quiz&code=-30&info=".$fileName);
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

function createQuestionArray($excelContent)
{
	$questions = array();
	
	for($i = 0; $i < count($excelContent); $i++)
	{
		$type = $excelContent[$i][0];
		$questionText = $excelContent[$i][1];
		$answers = array();
		
		$lastAnswerCell = 11;
		
		for($j = 2; $j <= $lastAnswerCell; $j = $j+2)
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
			
			$answer = new answer($answerText, $isCorrect);
			array_push($answers, $answer);
		}
		
		$question = new question($type, $questionText, $answers);
		array_push($questions, $question);
	}
	
	return $questions;
}


class question
{
	private $type;
	private $questionText;
	private $answers = array();

	public function __construct($type, $questionText, $answers)
	{
		$this->type = $type;
		$this->questionText = $questionText;
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

class answer
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