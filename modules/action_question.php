<?php

function insertQuestion()
{
	global $dbh;
	
	if($_SESSION["role"]["creator"])
	{
		if(isset($_POST["mode"]) && (isset($_POST["btnSave"]) || isset($_POST["btnSaveAndNext"])) &&
				isset($_POST["questionText"]) && isset($_POST["questionType"]) && isset($_POST["isPrivate"]) &&
				isset($_POST["answerText_0"]) && isset($_POST["answerText_1"]))
		{
			//check correct owner
			if($_POST["mode"] == 'edit')
			{
				//fetch owner of this question
				$stmt = $dbh->prepare("select owner_id, picture_link from question where id = :question_id");
				$stmt->bindParam(":question_id", $_POST["question_id"]);
				$stmt->execute();
				$fetchQuestionOwnerPic = $stmt->fetch(PDO::FETCH_ASSOC);
	
				//return if it is not the owner of this question
				if($fetchQuestionOwnerPic["owner_id"] != $_SESSION["id"] && $_SESSION["role"]["admin"] != 1)
				{
					header("Location: ?p=questions&code=-12");
					exit;
				}
			}
				
			//correct user input. min. two answers and min. one question checked
			$correctAnswers = false;
			$minimumTwoAnsers = false;
			$answerCounter = 0;
			for($i = 0; $i < 6; $i++)
			{
				if(isset($_POST["correctAnswer_" . $i]) && $_POST["correctAnswer_" . $i] == 1)
				{
					$answerCounter++;
				}
			}
			if($_POST["questionType"] == "singelchoise")
			{
				if($answerCounter == 1)
					$correctAnswers = true;
			} else if($_POST["questionType"] == "multiplechoise")
			{
				if($answerCounter >= 2)
					$correctAnswers = true;
			}
			if($_POST["answerText_0"] != "" && $_POST["answerText_1"] != "")
			{
				$minimumTwoAnsers = true;
			}
			if(!$correctAnswers || !$minimumTwoAnsers)
			{
				header("Location: ?p=questions&code=-5");
				exit;
			}
			//end
				
			//if the question and there answers is already existing when in creation mode
			//dont insert it, only use the question which is already existing
			$allIn = false;
			if($_POST["mode"] == "create")
			{
				$stmt = $dbh->prepare("select question.id as qId, question.text as qText, answer.id as aId, answer.text as aText, is_correct from question inner join answer_question on answer_question.question_id = question.id inner join answer on answer.id = answer_question.answer_id where question.text = :text");
				$stmt->bindParam(":text", $_POST["questionText"]);
				$stmt->execute();
				$fetchCheckQuestion = null;
				$questionCheckRowCount = $stmt->rowCount();
				if($questionCheckRowCount > 0)
				{
					$allInCount = 0;
					$fetchCheckQuestion = $stmt->fetchAll(PDO::FETCH_ASSOC);
					for($i = 0; $i < count($fetchCheckQuestion); $i++) //all answers- from quesy inc. questionstring
					{
						for($j = 0; $j < 6; $j++) //all answers in Excel
						{
							if(isset($_POST["answerText_" . $j]) && $_POST["answerText_" . $j] != "")
							{
								if($fetchCheckQuestion[$i]["aText"] == $_POST["answerText_" . $j] && ($_POST["correctAnswer_" . $j] != 1 || $fetchCheckQuestion[$i]["is_correct"] == $_POST["correctAnswer_" . $j]))
								{
									$allInCount++;
								}
							}
						}
					}
					if($questionCheckRowCount == $allInCount)
						$allIn = true;
				}
			}
				
			if(!$allIn)
			{
				//Upload question image
				if(isset($_FILES["questionLogo"]) && $_FILES["questionLogo"]["name"] != "")
				{
					$targetFile = uploadImage ();
				}
	
				//fetch questiontype id from name
				$stmt = $dbh->prepare("select id from question_type where type = :type");
				$stmt->bindParam(":type", $_POST["questionType"]);
				$stmt->execute();
				$fetchType = $stmt->fetch(PDO::FETCH_ASSOC);
	
				$subject_id = $_POST["topic"];
				if($subject_id == "null")
				{
					$subject_id = NULL;
				}
				//end
	
				$dbNull = NULL;
	
				//insert the question
				if($_POST["mode"] == "create")
				{
					$stmt = $dbh->prepare("insert into question	(text, owner_id, type_id, subject_id, language, creation_date, public, last_modified, picture_link)
							values (:text, ". $_SESSION["id"] .", :type_id, :subject_id, :language, ".time().", :public, ".time().", :picLink)");
	
					if(isset($_FILES["questionLogo"]) && $_FILES["questionLogo"]["name"] != "")
						$stmt->bindParam(":picLink", $targetFile);
						else
							$stmt->bindParam(":picLink", $dbNull);
								
				} else if($_POST["mode"] == "edit") //update the question
				{
						
					$stmt = $dbh->prepare("update question set text = :text, type_id = :type_id, subject_id = :subject_id, language = :language, public = :public, last_modified = ".time().", picture_link = :picLink where id = :question_id");
					$stmt->bindParam(":question_id", $_POST["question_id"]);
						
					if(isset($_FILES["questionLogo"]) && $_FILES["questionLogo"]["name"] != "")
					{
						$stmt->bindParam(":picLink", $targetFile);
					}
					else
					{
						$stmt->bindParam(":picLink", $fetchQuestionOwnerPic["picture_link"]);
					}
				} else {
					header("Location: ?p=questions&code=-3");
					exit;
				}
	
				$stmt->bindParam(":text", $_POST["questionText"]);
				$stmt->bindParam(":type_id", $fetchType["id"]);
				$stmt->bindParam(":subject_id", $subject_id);
				$stmt->bindParam(":language", $_POST["language"]);
				$stmt->bindParam(":public", $_POST["isPrivate"]);
	
				if($stmt->execute())
				{
					$insertedQuestionId = $dbh->lastInsertId();
					$orderCounter = 0;
						
					if($_POST["mode"] == "edit")
					{
						$stmt = $dbh->prepare("select answer_id, is_correct from answer_question where question_id = :question_id order by `order`");
						$stmt->bindParam(":question_id", $_POST["question_id"]);
						$stmt->execute();
						$fetchAnswerIdCount = $stmt->rowCount();
						$fetchAnswerId = $stmt->fetchAll(PDO::FETCH_ASSOC);
						$insertedQuestionId = $_POST["question_id"];
					}
						
					//insert / update answers + answer links
					for($i = 0; $i < 6; $i++) {
	
						if(isset($_POST["answerText_" . $i]) && $_POST["answerText_" . $i] != "")
						{
							if($_POST["mode"] == "create" || $i >= $fetchAnswerIdCount)
							{
								$stmt = $dbh->prepare("insert into answer (text) values (:text)");
							} else if($_POST["mode"] == "edit")
							{
								$stmt = $dbh->prepare("update answer set text = :text where id = :answer_id");
								$stmt->bindParam(":answer_id", $fetchAnswerId[$i]["answer_id"]);
							}
							$stmt->bindParam(":text", $_POST["answerText_" . $i]);
							if($stmt->execute())
							{
								if($_POST["mode"] == "create" || $i >= $fetchAnswerIdCount)
								{
									$lastAnswerId = $dbh->lastInsertId();
									$stmt = $dbh->prepare("insert into answer_question values (:answer_id, :question_id, :is_correct, :order)");
									$stmt->bindParam(":answer_id", $lastAnswerId);
									$stmt->bindParam(":question_id", $insertedQuestionId);
									$correctAnswer = 0;
									if(isset($_POST["correctAnswer_" . $i]))
									{
										$correctAnswer = 1;
									} else {
										if($fetchType["id"] == 2) //multiplechoise
										{
											$correctAnswer = -1;
										}
									}
									$stmt->bindParam(":is_correct", $correctAnswer);
									$stmt->bindParam(":order", $i);
									if($stmt->execute())
									{
										$orderCounter++;
									}
								} else if($_POST["mode"] == "edit")
								{
									$isCorrect = 0;
									if(isset($_POST["correctAnswer_" . $i]))
									{
										$isCorrect = 1;
									} else {
										if($fetchType["id"] == 2) //multiplechoise
										{
											$isCorrect = -1;
										}
									}
									$stmt = $dbh->prepare("update answer_question set is_correct = ".$isCorrect.", `order` = ".$i." where answer_id = " . $fetchAnswerId[$i]["answer_id"]);
									$stmt->execute();
								}
							}
							else {
								header("Location: ?p=questions&code=-13");
								exit;
							}
						}
						else { //delete answer if textfield is empty when it was filled before
							if($_POST["mode"] == "edit" && $i < $fetchAnswerIdCount)
							{
								$stmt = $dbh->prepare("delete from answer_question where answer_id = :answer_id");
								$stmt->bindParam(":answer_id", $fetchAnswerId[$i]["answer_id"]);
								$stmt->execute();
	
								$stmt = $dbh->prepare("delete from answer where id = :answer_id");
								$stmt->bindParam(":answer_id", $fetchAnswerId[$i]["answer_id"]);
								$stmt->execute();
							}
						}
					}
						
					//handle Keywords
					if(isset($_POST["keywords"]) && $_POST["keywords"] != "")
					{
	
						$keywordArray = explode(",", $_POST["keywords"]);
						$assocKeywordFetch = array();
	
						for($i = 0; $i < count($keywordArray); $i++)
						{
							$stmt = $dbh->prepare("select id from keyword where word = :keyword");
							$stmt->bindParam(":keyword", $keywordArray[$i]);
							$stmt->execute();
							$keywordFetch = $stmt->fetch(PDO::FETCH_ASSOC);
							if($stmt->rowCount() > 0)
							{
								$assocKeywordFetch[$keywordArray[$i]] = $keywordFetch["id"];
							} else {
								$stmt = $dbh->prepare("insert into keyword (word) values (:keyword)");
								$stmt->bindParam(":keyword", $keywordArray[$i]);
								if(!$stmt->execute())
								{
									header("Location: ?p=questions&code=-14");
									exit;
								}
								$assocKeywordFetch[$keywordArray[$i]] = $dbh->lastInsertId();
							}
								
						}
	
						if($_POST["mode"] == "edit")
						{
							$stmt = $dbh->prepare("delete from qu_keyword where qu_id = :qu_id");
							$stmt->bindParam(":qu_id", $_POST["question_id"]);
							$stmt->execute();
						}
						for($i = 0; $i < count($keywordArray); $i++)
						{
							$stmt = $dbh->prepare("insert into qu_keyword (qu_id, keyword_id) values (:qu_id, :keyword_id)");
							$stmt->bindParam(":keyword_id", $assocKeywordFetch[$keywordArray[$i]]);
							if($_POST["mode"] == "create")
								$stmt->bindParam(":qu_id", $insertedQuestionId);
								else if($_POST["mode"] == "edit")
									$stmt->bindParam(":qu_id", $_POST["question_id"]);
									if(!$stmt->execute())
									{
										header("Location: ?p=questions&code=-15");
										exit;
									}
						}
					}
				} else {
					header("Location: ?p=questions&code=-4");
					exit;
				}
			}
				
			$toSite = "questions";
			$fromQuizId = -1;
			if($_POST["fromsite"] != "")
			{
				$toSite = $_POST["fromsite"];
				if($toSite == "createEditQuiz")
				{
					$toSite .= "&mode=edit&id=" . $_POST["fromQuizId"];
				}
			}
				
			if(isset($_POST["btnSave"]) && $_POST["mode"] == "create")
			{
				header("Location: ?p=".$toSite."&code=1");
				exit;
			} else if(isset($_POST["btnSave"]) && $_POST["mode"] == "edit")
			{
				header("Location: ?p=".$toSite."&code=2");
				exit;
			} else if(isset($_POST["btnSaveAndNext"]))
			{
				header("Location: ?p=createEditQuestion");
				exit;
			}
		} else {
			header("Location: ?p=questions&code=-1&info=uio");
			exit;
		}
	}
}



/**
 * Uploads the question image
 */
function uploadImage() {
	
	$imageFileType = pathinfo($_FILES["questionLogo"]["name"], PATHINFO_EXTENSION);
	$targetDir = "uploadedImages/";
	$targetFile = $targetDir . "question_" . date("d_m_y_H_i_s", time()) . "__" . $_SESSION["id"] . "." . $imageFileType;
	$uploadOk = true;
	$subCode = 0;
		
	//check File is an image
	if(!getimagesize($_FILES["questionLogo"]["tmp_name"]))
	{
		$uploadOk = false;
		$subCode = -8;
	}
	
	//check if file already exists
	if(file_exists($targetFile))
	{
		$uploadOk = false;
		$subCode = -9;
	}
	
	//check file format | .jpeg,.jpg,.bmp,.png,.gif
	$imageFileType = strtolower($imageFileType);
	if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "bmp")
	{
		$uploadOk = false;
		$subCode = -11;
	}
	
	//check size
	$eightKB = 800000;
	if($_FILES["questionLogo"]["size"] > $eightKB)
	{
		shrinkQuestionLogo();
	}
	
	//check if all ok?
	if($uploadOk)
	{
		if(!move_uploaded_file($_FILES["questionLogo"]["tmp_name"], $targetFile))
		{
			header("Location: ?p=questions&code=-6");
			exit;
		}
	} else {
		header("Location: ?p=questions&code=" . $subCode);
		exit;
	}
	
	return $targetFile;
}

/**
 * Reduces the size of the uploaded question logo
 */
function shrinkQuestionLogo()
{
	$image = $_FILES["questionLogo"]["tmp_name"];
	$ressource = imagecreatefromstring(file_get_contents($image));
	
	//Automatically compresses image	
	if(!imagejpeg($ressource, $image))
	{
		header("Location: ?p=questions&code=-16");
		exit;
	}
	
	return;
}


function deleteQuestion()
{
	global $dbh;
	
	if($_SESSION['role']['creator'])
	{
		$stmt = $dbh->prepare("select owner_id from question where id = :questionId");
		$stmt->bindParam(":questionId", $_GET["questionId"]);
		$stmt->execute();
		$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
			
		if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1)
		{

			$stmt = $dbh->prepare("delete from qunaire_qu where question_id = :questionId");
			$stmt->bindParam(":questionId", $_GET["questionId"]);
			$stmt->execute();

			$stmt = $dbh->prepare("select * from answer_question where question_id = :questionId");
			$stmt->bindParam(":questionId", $_GET["questionId"]);
			$stmt->execute();
			$fetchAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$stmt = $dbh->prepare("delete from answer_question where question_id = :questionId");
			$stmt->bindParam(":questionId", $_GET["questionId"]);
			$stmt->execute();

			for($i = 0; $i < count($fetchAnswers); $i++)
			{
				$stmt = $dbh->prepare("delete from answer where id = :answerId");
				$stmt->bindParam(":answerId", $fetchAnswers[$i]["answer_id"]);
				$stmt->execute();
			}

			$stmt = $dbh->prepare("delete from an_qu_user where question_id = :questionId");
			$stmt->bindParam(":questionId", $_GET["questionId"]);
			$stmt->execute();

			$stmt = $dbh->prepare("delete from qu_keyword where qu_id = :questionId");
			$stmt->bindParam(":questionId", $_GET["questionId"]);
			$stmt->execute();

			$stmt = $dbh->prepare("select * from qu_keyword where qu_id = :questionId");
			$stmt->bindParam(":questionId", $_GET["questionId"]);
			$stmt->execute();
			$fetchQuKeyword = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$stmt = $dbh->prepare("delete from qu_keyword where qu_id = :questionId");
			$stmt->bindParam(":questionId", $_GET["questionId"]);
			$stmt->execute();

			for($i = 0; $i < count($fetchQuKeyword); $i++)
			{
				$stmt = $dbh->prepare("delete from keyword where id = :keywordId");
				$stmt->bindParam(":keywordId", $fetchQuKeyword[$i]["keyword_id"]);
				$stmt->execute();
			}
			
			$stmt = $dbh->prepare("select picture_link from question where id = :questionId");
			$stmt->bindParam(":questionId", $_GET["questionId"]);
			$stmt->execute();
			$pictureLink = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$test = unlink("../" . $pictureLink[0]["picture_link"]);
			
			$stmt = $dbh->prepare("delete from question where id = :questionId");
			$stmt->bindParam(":questionId", $_GET["questionId"]);
			if($stmt->execute())
			{
				echo "deleteQuestionOk";
			} else {
				echo "deleteQuestionFail";
			}
		} else {
			header("Location: ?p=quiz&code=-1&info=bnm");
			exit;
		}
	}
}

function queryAnswers()
{
	global $dbh;
	
	if($_SESSION['role']['creator'] && isset($_GET["questionId"]))
	{
		$stmt = $dbh->prepare("select question.id, question.text qText, answer.* from question inner join answer_question on question.id = answer_question.question_id inner join answer on answer.id = answer_question.answer_id where question.id = :id");
		$stmt->bindParam(":id", $_GET["questionId"]);
		if($stmt->execute()) {
			$fetchQuestionAndAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
			$resultArray = array();
			array_push($resultArray, $fetchQuestionAndAnswers[0]["qText"]);
	
			for ($i = 0; $i < count($fetchQuestionAndAnswers); $i++)
			{
				array_push($resultArray, $fetchQuestionAndAnswers[$i]["text"]);
			}
			echo json_encode(["getAnswersOk", $resultArray]);
		} else {echo json_encode(["failed"]);}
	} else {echo json_encode(["failed"]);}
}

/**
 * Deletes the database-entry for question logo and the image-file from the server
 */
function deletePicture()
{
	global $dbh;

	$stmt = $dbh->prepare("select owner_id, picture_link from question where id = :question_id");
	$stmt->bindParam(":question_id", $_GET["questionId"]);
	$stmt->execute();
	$fetchQuestionOwner = $stmt->fetch(PDO::FETCH_ASSOC);

	if(($_SESSION['role']['creator'] && $fetchQuestionOwner["owner_id"] == $_SESSION["id"]) || $_SESSION['role']['admin'])
	{
		$filename = $fetchQuestionOwner["picture_link"];
		
		$stmt = $dbh->prepare("update question set picture_link = NULL where id = :question_id");
		$stmt->bindParam(":question_id", $_GET["questionId"]);
		$stmt->execute();
		if($stmt->execute())
		{
			unlink("../" . $filename);
			echo "deletePictureOk";
		} else
		{
			echo "deletePictureFail";
		}
	} else
	{
		echo "deletePictureFail2";
	}
}

?>
