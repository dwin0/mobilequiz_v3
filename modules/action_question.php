<?php

function updateQuestion()
{
	global $dbh;
	$response_array["status"] = "OK";
	
	//check if owner of admin
	$stmt = $dbh->prepare("select owner_id from question where id = :question_id");
	$stmt->bindParam(":question_id", $_POST["questionId"]);
	$stmt->execute();
	$fetchQuizOwnerPic = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if($fetchQuizOwnerPic["owner_id"] != $_SESSION["id"] && $_SESSION["role"]["admin"] != 1)
	{
		$response_array["status"] = "error";
		$response_array["text"] = "You are not allowed to update this question.";
	}
	
	$field = $_GET["field"];
	if(!isset($_POST["questionId"]) || !isset($field) || (!isset($_POST[$field]) && ($field != "addQuestionImage")))
	{
		$response_array["status"] = "error";
		$response_array["text"] = "Not all parameters received.";
	}
	
	if($response_array["status"] == "error")
	{
		echo json_encode($response_array);
		exit;
	}
	
	switch($field)
	{
		case "questionText":
			$response_array = updateQuestionText($_POST["questionText"], $_POST["questionId"], $dbh);
			break;
		case "keywords":
			$response_array = updateQuestionKeywords($_POST["keywords"], $_POST["questionId"], $dbh);
			break;
		case "language":
			$response_array = updateQuestionLanguage($_POST["language"], $_POST["questionId"], $dbh);
			break;
		case "topic":
			$response_array = updateQuestionTopic($_POST["topic"], $_POST["questionId"], $dbh);
			break;
		case "addQuestionImage":
			$response_array = addPicture($_POST["questionId"], $dbh);
			break;
		case "deleteQuestionImage":
			$response_array = deletePicture($_POST["questionId"], $dbh);
			break;
		case "isPrivate":
			$response_array = updateQuestionPublication($_POST["isPrivate"], $_POST["questionId"], $dbh);
			break;
		case "questionType":
			$response_array = updateQuestionType($_POST["questionType"], $_POST["questionId"], $dbh);
			break;
		case "answerText":
			$response_array = updateQuestionAnswers($_POST["answerId"], $_POST["answerNumber"], $_POST["answerText"], $_POST["isCorrect"], $_POST["questionId"], $dbh);
			break;
	}
	
	$stmt = $dbh->prepare("update question set last_modified = ".time()." where id = :question_id");
	$stmt->bindParam(":question_id", $_POST["questionId"]);
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = "Couldn't update database";
	}
	
	echo json_encode($response_array);
	exit;
}


function updateQuestionText($questionText, $questionId, $dbh)
{
	$response_array["status"] = "OK";
	
	$stmt = $dbh->prepare("update question set text = :text where id = :question_id");
	$stmt->bindParam(":text", $questionText);
	$stmt->bindParam(":question_id", $questionId);
		
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = "Couldn't update database";
	}
	
	return $response_array;
}

function updateQuestionKeywords($keywords, $questionId, $dbh)
{
	$response_array["status"] = "OK";
	
	$keywordArray = explode(",", $keywords);
	$assocKeywordFetch = array();
		
	for($i = 0; $i < count($keywordArray); $i++)
	{
		if($keywordArray[$i] == "") { continue; }
	
		$stmt = $dbh->prepare("select id from keyword where word = :keyword");
		$stmt->bindParam(":keyword", $keywordArray[$i]);
		$stmt->execute();
		$keywordFetch = $stmt->fetch(PDO::FETCH_ASSOC);
		if($stmt->rowCount() > 0) //keyword already exists
		{
			$assocKeywordFetch[$keywordArray[$i]] = $keywordFetch["id"];
		} else {
			//create new keyword
			$stmt = $dbh->prepare("insert into keyword (word) values (:keyword)");
			$stmt->bindParam(":keyword", $keywordArray[$i]);
			if(!$stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Database-Error";
				return $response_array;
			}
			$assocKeywordFetch[$keywordArray[$i]] = $dbh->lastInsertId();
		}
	}
	
	//delete all keywords from question an add new ones
	$stmt = $dbh->prepare("delete from qu_keyword where qu_id = :qu_id");
	$stmt->bindParam(":qu_id", $questionId);
	$stmt->execute();
		
	for($i = 0; $i < count($keywordArray); $i++)
	{
		$stmt = $dbh->prepare("insert into qu_keyword (qu_id, keyword_id) values (:question_id, :keyword_id)");
		$stmt->bindParam(":keyword_id", $assocKeywordFetch[$keywordArray[$i]]);
		$stmt->bindParam(":question_id", $questionId);
		if(!$stmt->execute())
		{
			$response_array["status"] = "error";
			$response_array["text"] = "Couldn't update database";
		}
	}
	
	return $response_array;
}


function updateQuestionLanguage($language, $questionId, $dbh)
{
	$response_array["status"] = "OK";
		
	$stmt = $dbh->prepare("select language from question group by language");
	$stmt->execute();
	$allLanguages = $stmt->fetchAll();
	
	for($i = 0; $i < count($allLanguages); $i++){
		if($allLanguages[$i]["language"] == $language)
		{
			$existingLanguage = true;
			
			//update question with existing language
			$stmt = $dbh->prepare("update question set language = :language where id = :question_id");
			$stmt->bindParam(":language", $language);
			$stmt->bindParam(":question_id", $questionId);
				
			if(! $stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Couldn't update database";
				return $response_array;
			}
		}
	}
		
	if(!$existingLanguage) //create new language-request
	{
		$stmt = $dbh->prepare("insert into language_request (user_id, language, timestamp, question_id) values (:user_id, :language, " . time() . ", :question_id)");
		$stmt->bindParam(":user_id", $_SESSION["id"]);
		$stmt->bindParam(":language", $language);
		$stmt->bindParam(":question_id", $questionId);
		if(! $stmt->execute())
		{
			$response_array["status"] = "error";
			$response_array["text"] = "Couldn't update database";
		}
	}
		
	return $response_array;
}


function updateQuestionTopic($topic, $questionId, $dbh)
{
	$response_array["status"] = "OK";
		
	$stmt = $dbh->prepare("select id from subjects where id = :id");
	$stmt->bindParam(":id", $topic);
	$stmt->execute();
	$fetchTopic = $stmt->fetch(PDO::FETCH_ASSOC);
		
	if(isset($fetchTopic["id"]))
	{
		//update question with existing topic
		$stmt = $dbh->prepare("update question set subject_id = :subject_id where id = :question_id");
		$stmt->bindParam(":subject_id", $fetchTopic["id"]);
		$stmt->bindParam(":question_id", $questionId);
	} else
	{ //create new topic-request
	$stmt = $dbh->prepare("insert into topic_request (user_id, topic, timestamp, question_id) values (:user_id, :topic, " . time() . ", :question_id)");
	$stmt->bindParam(":user_id", $_SESSION["id"]);
	$stmt->bindParam(":topic", $topic);
	$stmt->bindParam(":question_id", $questionId);
	}
		
	if(!$stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = "Database-Error";
	}
		
	return $response_array;
}


function addPicture($questionId, $dbh)
{
	$response_array["status"] = "ADDED";
		
	if(isset($_FILES["addQuestionImage"]) && $_FILES["addQuestionImage"]["name"] != "")
	{
		$image = $_FILES["addQuestionImage"];
		
		$imageFileType = pathinfo($image["name"], PATHINFO_EXTENSION);
		$targetFile = "uploadedImages/question_" . date("d_m_y_H_i_s", time()) . "__" . $_SESSION["id"] . "." . $imageFileType;
		
		//check File is an image
		if(!getimagesize($image["tmp_name"]))
		{
			$response_array["status"] = "error";
			$response_array["text"] = "File is not an image";
			return $response_array;
		}
		
		//check if file already exists
		if(file_exists($targetFile))
		{
			$response_array["status"] = "error";
			$response_array["text"] = "File already exists";
			return $response_array;
		}
		
		//check file format | .jpeg,.jpg,.bmp,.png,.gif
		$imageFileType = strtolower($imageFileType);
		if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "bmp")
		{
			$response_array["status"] = "error";
			$response_array["text"] = "File-Format is not supportet";
			return $response_array;
		}
		
		//check size
		$eightKB = 800000;
		$size = filesize($image["tmp_name"]);
		$sucessfullyResized = true;
		
		while($size > $eightKB)
		{
			$sucessfullyResized = shrinkQuestionImage($image);
			clearstatcache();
			$size = filesize($image["tmp_name"]);
		}
		if(!$sucessfullyResized)
		{
			$response_array["status"] = "error";
			$response_array["text"] = "Image resize failed";
			return $response_array;
		}
		
		if($response_array["status"] != "error")
		{
			//transfer tmp-file to uploadedImages-folder
			if(!move_uploaded_file($image["tmp_name"], $targetFile))
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Datatransfer failed";
			} else 
			{
				$stmt = $dbh->prepare("update question set picture_link = :picture_link where id = :question_id");
				$stmt->bindParam(":picture_link", $targetFile);
				$stmt->bindParam(":question_id", $questionId);
				
				$response_array["text"] = $targetFile;
				
				if(!$stmt->execute())
				{
					$response_array["status"] = "error";
					$response_array["text"] = "DB-Update error";
				}
			}
		}
		
		return $response_array;
	}
}

/**
 * Reduces the size of the uploaded question image
 */
function shrinkQuestionImage($originalImage)
{	
	$sucessful = true;
	
	$filename = $originalImage["tmp_name"];
	$percent = 0.5;

	// Get new dimensions
	list($width, $height) = getimagesize($filename);
	$new_width = $width * $percent;
	$new_height = $height * $percent;

	// Resample
	$image_p = imagecreatetruecolor($new_width, $new_height);
	$image = imagecreatefromstring(file_get_contents($filename));
	imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

	if(!imagejpeg($image_p, $filename))
	{
		$sucessful = false;
	}
	imagedestroy($image_p);

	return $sucessful;
}


function deletePicture($questionId, $dbh)
{
	$response_array["status"] = "DELETED";
	
	$stmt = $dbh->prepare("select picture_link from question where id = :question_id");
	$stmt->bindParam(":question_id", $questionId);
	$stmt->execute();
	$fetchQuestionImage = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$filename = $fetchQuestionImage["picture_link"];
	
	$stmt = $dbh->prepare("update question set picture_link = NULL where id = :question_id");
	$stmt->bindParam(":question_id", $questionId);
	$stmt->execute();
	if($stmt->execute())
	{
		unlink($filename); //delete image from server
	} else
	{
		$response_array["status"] = "error";
		$response_array["text"] = "Database-Error";
	}
	
	return $response_array;
}


function updateQuestionPublication($publication, $questionId, $dbh)
{
	$response_array["status"] = "OK";

	$stmt = $dbh->prepare("update question set public = :public where id = :question_id");
	$stmt->bindParam(":public", $publication);
	$stmt->bindParam(":question_id", $questionId);

	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = "Couldn't update database";
	}

	return $response_array;
}


function updateQuestionType($type, $questionId, $dbh)
{
	$response_array["status"] = "OK";

	//get question-type
	$stmt = $dbh->prepare("select id from question_type where type = :type");
	$stmt->bindParam(":type", $type);
	$stmt->execute();
	
	$fetchType = $stmt->fetch(PDO::FETCH_ASSOC);
	if($fetchType == false)
	{
		$response_array["status"] = "error";
		$response_array["text"] = "Couldn't find question-type";
		return $response_array;
	}
	
	//update type_id
	$stmt = $dbh->prepare("update question set type_id = :type_id where id = :question_id");
	$stmt->bindParam(":type_id", $fetchType["id"]);
	$stmt->bindParam(":question_id", $questionId);

	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = "Couldn't update database";
		return $response_array;
	}
	
	//update isCorrect -> Singlechoice (wrong == 0 points) / Multiplechoice (wrong == -1 point)
	if($type == "singelchoise")
	{
		$stmt = $dbh->prepare("select answer_id from answer_question where question_id = :questionId and is_correct = -1");
		$stmt->bindParam(":questionId", $questionId);
		$stmt->execute();
		$fetchAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		for($i = 0; $i < count($fetchAnswers); $i++)
		{
			$stmt = $dbh->prepare("update answer_question set is_correct = 0 where answer_id = :answerId");
			$stmt->bindParam(":answerId", $fetchAnswers[$i]["answer_id"]);
			if(! $stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Couldn't update database";
				return $response_array;
			}
		}

	} else if($type == "multiplechoise")
	{
		$stmt = $dbh->prepare("select answer_id from answer_question where question_id = :questionId and is_correct = 0");
		$stmt->bindParam(":questionId", $questionId);
		$stmt->execute();
		$fetchAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		for($i = 0; $i < count($fetchAnswers); $i++)
		{
			$stmt = $dbh->prepare("update answer_question set is_correct = -1 where answer_id = :answerId");
			$stmt->bindParam(":answerId", $fetchAnswers[$i]["answer_id"]);
			if(! $stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Couldn't update database";
				return $response_array;
			}
		}
	}
	
	return $response_array;
}


function updateQuestionAnswers($answerId, $answerNumber, $answerText, $isCorrect, $questionId, $dbh)
{
	$response_array["status"] = "OK";
	
	$stmt = $dbh->prepare("select type from question_type inner join question on question_type.id = question.type_id where question.id = :questionId");
	$stmt->bindParam(":questionId", $questionId);
	$stmt->execute();
	$fetchQuestionType = $stmt->fetch(PDO::FETCH_ASSOC);
	$questionType = $fetchQuestionType["type"];
	
	//calculate isCorrect-points
	if($questionType == "singelchoise")
	{
		if($isCorrect == "true")
		{
			$isCorrect = 1;
		} else 
		{
			$isCorrect = 0;
		}
	} else if($questionType == "multiplechoise")
	{
		if($isCorrect == "true")
		{
			$isCorrect = 1;
		} else
		{
			$isCorrect = -1;
		}
	}
	
	$stmt = $dbh->prepare("select id from answer where id = :answerId");
	$stmt->bindParam(":answerId", $answerId);
	$stmt->execute();
	$fetchAnswerId = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if(!isset($fetchAnswerId["id"]) && $answerText != "") //new answer
	{	
		//create new answer
		$stmt = $dbh->prepare("insert into answer (text) values (:answerText)");
		$stmt->bindParam(":answerText", $answerText);
		if(! $stmt->execute())
		{
			$response_array["status"] = "error";
			$response_array["text"] = "Couldn't insert new answer";
			return $response_array;
		}
		
		//return-values for ajax-success
		$answerId = $dbh->lastInsertId();
		$response_array["status"] = "ANSWER_INSERTED";
		$response_array["answerId"] = $answerId;
		$response_array["answerNumber"] = $answerNumber;
		
		
		//calculate order-attribute
		$stmt = $dbh->prepare("select count(answer_id) as total from answer_question where question_id = :questionId");
		$stmt->bindParam(":questionId", $questionId);
		$stmt->execute();
		$fetchAmountOfAnswers = $stmt->fetch(PDO::FETCH_ASSOC);
		$nextOrder = $fetchAmountOfAnswers["total"]; //starts with 0
		
		
		//create new answer_question-entry
		$stmt = $dbh->prepare("insert into answer_question values (:answerId, :questionId, :isCorrect, :order)");
		$stmt->bindParam(":answerId", $answerId);
		$stmt->bindParam(":questionId", $questionId);
		$stmt->bindParam(":isCorrect", $isCorrect);
		$stmt->bindParam(":order", $nextOrder);
		
		if(! $stmt->execute())
		{
			$response_array["status"] = "error";
			$response_array["text"] = "Couldn't insert new answer_question";
			return $response_array;
		}
		
	} else //existing answer
	{
		if($answerText == "") //empty -> delete answer and answer_question
		{
			//return-values for ajax-success
			$response_array["status"] = "ANSWER_DELETED";
			$response_array["answerNumber"] = $answerNumber;
			
			//get current order from current question
			$stmt = $dbh->prepare("select `order` from answer_question where answer_id = :answerId");
			$stmt->bindParam(":answerId", $answerId);
			$stmt->execute();
			$fetchQuestionOrder = $stmt->fetch(PDO::FETCH_ASSOC);
			$deletedQuestionOrder = $fetchQuestionOrder["order"];
			
			//delete answer_question-entry
			$stmt = $dbh->prepare("delete from answer_question where answer_id = :answerId");
			$stmt->bindParam(":answerId", $answerId);
			
			if(! $stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Couldn't delete empty answer_question";
				return $response_array;
			}
			
			//delete answer-entry
			$stmt = $dbh->prepare("delete from answer where id = :answerId");
			$stmt->bindParam(":answerId", $answerId);
			
			if(! $stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Couldn't delete empty answer";
				return $response_array;
			}
			
			//update order from other question-answers
			$stmt = $dbh->prepare("select answer_id, `order` from answer_question where question_id = :questionId");
			$stmt->bindParam(":questionId", $questionId);
			$stmt->execute();
			$fetchQuestionAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			for($i = 0; $i < count($fetchQuestionAnswers); $i++)
			{
				$stmt = $dbh->prepare("update answer_question set `order` = :order where answer_id = :answerId");
				
				if($deletedQuestionOrder > $fetchQuestionAnswers[$i]["order"])
				{
					continue;
				}
				$newOrder = $fetchQuestionAnswers[$i]["order"] - 1;
				$stmt->bindParam(":order", $newOrder);
				$stmt->bindParam(":answerId", $fetchQuestionAnswers[$i]["answer_id"]);
				
				if(! $stmt->execute())
				{
					$response_array["status"] = "error";
					$response_array["text"] = "Couldn't update order";
					return $response_array;
				}
			}
			
			return $response_array;
		} else //update existing answer
		{
			$stmt = $dbh->prepare("update answer set text = :text where id = :answerId");
			$stmt->bindParam(":text", $answerText);
			$stmt->bindParam(":answerId", $answerId);
			
			if(! $stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Couldn't update answer-table";
				return $response_array;
			}
			
			$stmt = $dbh->prepare("update answer_question set is_correct = :isCorrect where answer_id = :answerId");
			$stmt->bindParam(":isCorrect", $isCorrect);
			$stmt->bindParam(":answerId", $answerId);
				
			if(! $stmt->execute())
			{
				$response_array["status"] = "error";
				$response_array["text"] = "Couldn't update answer_question-table";
				return $response_array;
			}
		}
	}
	
	return $response_array;
}



















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
			for($i = 0; $i < 5; $i++)
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
				if($answerCounter >= 1)
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
						for($j = 0; $j < 5; $j++)
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
					for($i = 0; $i < 5; $i++) {
	
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



?>
