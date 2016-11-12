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
		exit('Error loading file "'	.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
	}
	
	$sheet = $objPHPExcel->getSheet(0);
	$highestRow = $sheet->getHighestRow();
	$highestColumn = $sheet->getHighestColumn();
	
	
	$excelContent = array();
	
	for ($row = 1; $row <= $highestRow; $row++){
		$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
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

	public function getText()
	{
		return $this->questionText;
	}

	public function getColor()
	{
		return $this->answers;
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
		return $this->correct;
	}
}

?>