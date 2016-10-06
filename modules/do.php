<?php
	session_start();
	
	include_once '../config/config.php';
	include_once "../modules/extraFunctions.php";

	$action = -1;
	$fromSite = -1;
	if(isset($_GET["action"]))
	{
		$action = $_GET["action"];
	}
	if(isset($_GET["fromsite"]))
	{
		$fromSite = $_GET["fromsite"];
	}
	//----------
	
	if($action =="insertTopic")
	{
		if(isset($_POST["topicName"]) && strlen($_POST["topicName"]) > 1 && isset($_POST["submit"]))
		{
			$stmt = $dbh->prepare("insert into subjects (name) values (:name)");
			$stmt->bindParam(":name", $_POST["topicName"]);
			if($stmt->execute())
			{
				header("Location: ?p=topics&code=1");
			} else {
				header("Location: ?p=topics&code=-1&info=zzz");
			}
		}
	} else if($action =="delTopic")
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select id from subjects where name = 'undefined'");
			$stmt->execute();
			$fetchSubjectId = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$stmt = $dbh->prepare("update question set subject_id = " . $fetchSubjectId["id"] . " where subject_id = :topicId");
			$stmt->bindParam(":topicId", $_GET["topicId"]);
			$stmt->execute();
			
			$stmt = $dbh->prepare("update questionnaire set subject_id = " . $fetchSubjectId["id"] . " where subject_id = :topicId");
			$stmt->bindParam(":topicId", $_GET["topicId"]);
			$stmt->execute();
			
			$stmt = $dbh->prepare("delete from subjects where id = :topicId");
			$stmt->bindParam(":topicId", $_GET["topicId"]);
			if($stmt->execute())
			{
				echo "deleteTopicOk";
			} else {
				echo "deleteTopicFail";
			}
		}
	} else if($action =="insertQuestion" && ($_SESSION["role"]["creator"] == 1 || $_SESSION["role"]["manager"] == 1 || $_SESSION["role"]["admin"] == 1))
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
						for($j = 0; $j < 6; $j++) //all answers in csv
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
				//fileupload
				if(isset($_FILES["questionLogo"]) && $_FILES["questionLogo"]["name"] != "")
				{
					$subCode = 0;
					//upload picture
					$imageFileType = pathinfo($_FILES["questionLogo"]["name"], PATHINFO_EXTENSION);
					$targetDir = "uploadedImages/";
					$targetFile = $targetDir . "question_" . date("d_m_y_H_i_s", time()) . "__" . $_SESSION["id"] . "." . $imageFileType;
					$uploadOk = true;
					
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
					//check size
					if($_FILES["questionLogo"]["size"] > 20000000)
					{
						$uploadOk = false;
						$subCode = -10;
					}
					//check file format | .jpeg,.jpg,.bmp,.png,.gif
					$imageFileType = strtolower($imageFileType);
					if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "bmp")
					{
						$uploadOk = false;
						$subCode = -11;
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
				}
				//end
				
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
	} else if($action =="delQuestion")
	{
		if($_SESSION['role']['creator'] == 1)
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
	} else if($action =="delPicture")
	{
		$stmt = $dbh->prepare("select owner_id from question where id = :question_id");
		$stmt->bindParam(":question_id", $_GET["questionId"]);
		$stmt->execute();
		$fetchQuestionOwner = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(($_SESSION['role']['creator'] == 1 && $fetchQuestionOwner["owner_id"] == $_SESSION["id"]) || $_SESSION['role']['admin'] == 1)
		{
			$stmt = $dbh->prepare("update question set picture_link = NULL where id = :question_id");
			$stmt->bindParam(":question_id", $_GET["questionId"]);
			$stmt->execute();
			if($stmt->execute())
				echo "deletePictureOk";
			else
				echo "deletePictureFail";
		} else 
		{
			echo "deletePictureFail2";
		}
	} else if($action =="insertQuiz")
	{
		if(isset($_POST["mode"]) && (isset($_POST["btnSave"]) || isset($_POST["btnSaveAsDraft"]) || isset($_POST["btnAddQuestion"])) &&
				isset($_POST["quizText"]) && isset($_POST["topic"]) && isset($_POST["language"]) && 
				isset($_POST["endDate"]) && isset($_POST["endTime"]) &&
				isset($_POST["startDate"]) && isset($_POST["startTime"])
				&& isset($_POST["timeLimitMode"]) 
				&& isset($_POST["reportAfterQuizResults"])
				&& isset($_POST["reportAfterQuizPoints"])
				&& isset($_POST["quizPriority"])
				&& isset($_POST["amountQuestionMode"])
				&& isset($_POST["maxParticipationsMode"])
				&& isset($_POST["quizPassedMode"])
				&& isset($_POST["singlechoiseMult"])
				&& isset($_POST["noParticipationPeriod"]))
		{
			//check correct owner
			if($_POST["mode"] == 'edit')
			{
				//fetch owner of this quiz
				$stmt = $dbh->prepare("select owner_id, picture_link from questionnaire where id = :q_id");
				$stmt->bindParam(":q_id", $_POST["quiz_id"]);
				$stmt->execute();
				$fetchQuizOwnerPic = $stmt->fetch(PDO::FETCH_ASSOC);
		
				//return if it is not the owner of this quiz
				if($fetchQuizOwnerPic["owner_id"] != $_SESSION["id"] && $_SESSION["role"]["admin"] != 1 && !amIAssignedToThisQuiz($dbh, $_POST["quiz_id"]))
				{
					header("Location: ?p=quiz&code=-1&info=asd");
					exit;
				}
			}
			
			//check new Language is not empty
			if($_POST["language"] == "newLanguage")
			{
				if($_POST["newLanguage"] == "")
				{
					header("Location: ?p=createEditQuiz&code=-3&info=lang");
					exit;
				}
			}
			
			//check new Topic is not empty
			if($_POST["topic"] == "newTopic")
			{
				if($_POST["newTopic"] == "")
				{
					header("Location: ?p=createEditQuiz&code=-3&info=topic");
					exit;
				}
			}
			
			//insert quiz
			if($_POST["mode"] == "create")
			{
				//qnaire_token
				$qnaire_token = NULL;
				
				do {
					$qnaire_token = substr(md5(uniqid(rand(), true)), 0, 6);
					$stmt = $dbh->prepare("select id from questionnaire where qnaire_token = :qt");
					$stmt->bindParam(":qt", $qnaire_token);
					$stmt->execute();
				} while($stmt->rowCount()>0);
				
				$stmt = $dbh->prepare("insert into questionnaire (owner_id, subject_id, name, starttime, endtime, qnaire_token, random_questions, random_answers, limited_time, result_visible, result_visible_points, language, amount_of_questions, public, description, picture_link, creation_date, last_modified, priority, amount_participations, quiz_passed, singlechoise_multiplier, noParticipationPeriod, showTaskPaper) 
						values (" . $_SESSION["id"] . ", :subject_id, :name, :starttime, :endtime, :qnaire_token, :random_questions, :random_answers, :limited_time, :result_visible, :result_visible_points, :language, :amount_of_questions, :public, :description, :picLink, ".time().", ".time().", :priority, :amount_participations, :quiz_passed, :singlechoise_multiplier, :noParticipationPeriod, :showTaskPaper)");
				
				$stmt->bindParam(":qnaire_token", $qnaire_token);
				
			} else if($_POST["mode"] == "edit")
			{
				$stmt = $dbh->prepare("update questionnaire set subject_id = :subject_id, name = :name, starttime = :starttime, endtime = :endtime, random_questions = :random_questions, random_answers = :random_answers, limited_time = :limited_time, result_visible = :result_visible, result_visible_points = :result_visible_points, 
						language = :language, amount_of_questions = :amount_of_questions, public = :public, description = :description, picture_link = :picLink, last_modified = :last_modified, priority = :priority, amount_participations = :amount_participations, quiz_passed = :quiz_passed, singlechoise_multiplier = :singlechoise_multiplier, noParticipationPeriod = :noParticipationPeriod, showTaskPaper = :showTaskPaper where id = :quiz_id");
				$stmt->bindParam(":quiz_id", $_POST["quiz_id"]);
				$stmt->bindParam(":last_modified", time());
				
			}
			
			//parse/check start / enddate | start / endtime
			$startdate = time();
			$enddate = strtotime('+1 Week');
			
			if(substr_count($_POST["startDate"], '.') == 2 && substr_count($_POST["endDate"], '.') == 2 
					&& substr_count($_POST["startTime"], ':') == 1 && substr_count($_POST["endTime"], ':') == 1)
			{
				$arrDate = explode(".", $_POST["startDate"]);
				$arrTime = explode(":", $_POST["startTime"]);
				
				$startdate = mktime($arrTime[0], $arrTime[1],0,$arrDate[1], $arrDate[0], $arrDate[2]);
				
				$arrDate = explode(".", $_POST["endDate"]);
				$arrTime = explode(":", $_POST["endTime"]);
				
				$enddate = mktime($arrTime[0], $arrTime[1],0,$arrDate[1], $arrDate[0], $arrDate[2]);
			} else {
				header("Location: ?p=quiz&code=-3");
				exit;
			}
			
			//Task paper everytime available
			$showQuizTaskPaper = 0;
			if(isset($_POST["showQuizTaskPaper"]))
				$showQuizTaskPaper = 1;
			
			//random_questions
			$rndQuestions = 0;
			if(isset($_POST["randomizeQuestions"]))
				$rndQuestions = 1;
			
			//random_answers
			$rndAnswers = 0;
			if(isset($_POST["randomizeAnswers"]))
				$rndAnswers = 1;
			
			//limited_time
			$limited_time = 0;
			if($_POST["timeLimitMode"] == 1) //limit with $_POST["quizTimeLimit"]
			{
				if(substr_count($_POST["quizTimeLimit"], ':') == 1)
				{
					$arrTime = explode(":", $_POST["quizTimeLimit"]);
					$limited_time = ($arrTime[0]*60) + $arrTime[1];
				} else {
					header("Location: ?p=quiz&code=-3");
					exit;
				}
			}
			
			//result_visible
			$result_visible = 1;
			if($_POST["reportAfterQuizResults"] == 1 || $_POST["reportAfterQuizResults"] == 2 || $_POST["reportAfterQuizResults"] == 3)
				$result_visible = $_POST["reportAfterQuizResults"];
			
			//result_visible_points
			$result_visible_points = 1;
			if($_POST["reportAfterQuizPoints"] == 1 || $_POST["reportAfterQuizPoints"] == 2)
				$result_visible_points = $_POST["reportAfterQuizPoints"];
			
			//amount_of_questions
			$amountOfQuestions = 0;
			if($_POST["amountQuestionMode"] == 1)
			{
				if(!is_numeric($_POST["amountOfQuestions"]))
				{
					header("Location: ?p=quiz&code=-4");
					exit;
				}
				$amountOfQuestions = intval($_POST["amountOfQuestions"]);
			}
			
			//public 2 = entwurf, 1 = public, 0 = private
			$isQuizPublic = 2;
			if(isset($_POST["btnSave"]))
			{
				if(isset($_POST["isPublic"]))
					$isQuizPublic = 1;
				else 
					$isQuizPublic = 0;
			}
			
			//amount max participations
			$maxParticipations = 0;
			if($_POST["maxParticipationsMode"] == 1)
			{
				if(!is_numeric($_POST["maxParticipations"]))
				{
					header("Location: ?p=quiz&code=-4");
					exit;
				}
				$maxParticipations = intval($_POST["maxParticipations"]);
			}
			
			//quizPassed
			$quizPassed = 0;
			if($_POST["quizPassedMode"] == 1)
			{
				if(!is_numeric($_POST["quizPassed"]))
				{
					header("Location: ?p=quiz&code=-4");
					exit;
				}
				$quizPassed = intval($_POST["quizPassed"]);
			}
			
			//noParticipationPeriod
			$noParticipationPeriod = 0;
			if($_POST["noParticipationPeriod"] == 1)
				$noParticipationPeriod = 1;
			
			
			//pictureLink
			//fileupload
			if(isset($_FILES["quizLogo"]) && $_FILES["quizLogo"]["name"] != "")
			{
				$subCode = 0;
				//upload picture
				$imageFileType = pathinfo($_FILES["quizLogo"]["name"], PATHINFO_EXTENSION);
				$targetDir = "uploadedImages/";
				$targetFile = $targetDir . "quiz_" . date("d_m_y_H_i_s", time()) . "__" . $_SESSION["id"] . "." . $imageFileType;
				$uploadOk = true;
			
				//check File is an image
				if(!getimagesize($_FILES["quizLogo"]["tmp_name"]))
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
				//check size
				if($_FILES["quizLogo"]["size"] > 20000000)
				{
					$uploadOk = false;
					$subCode = -10;
				}
				//check file format | .jpeg,.jpg,.bmp,.png,.gif
				$imageFileType = strtolower($imageFileType);
				if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "bmp")
				{
					$uploadOk = false;
					$subCode = -11;
				}
				//check if all ok?
				if($uploadOk)
				{
					if(!move_uploaded_file($_FILES["quizLogo"]["tmp_name"], $targetFile))
					{
						header("Location: ?p=quiz&code=-6");
						exit;
					}
				} else {
					header("Location: ?p=quiz&code=" . $subCode);
					exit;
				}
			}
			
			$dbNull = NULL;
			
			if($_POST["mode"] == "create") 
			{
				if(isset($_FILES["quizLogo"]) && $_FILES["quizLogo"]["name"] != "")
					$stmt->bindParam(":picLink", $targetFile);
				else
					$stmt->bindParam(":picLink", $dbNull);
			} else if($_POST["mode"] == "edit")
			{			
				if(isset($_FILES["quizLogo"]) && $_FILES["quizLogo"]["name"] != "")
					$stmt->bindParam(":picLink", $targetFile);
				else
					$stmt->bindParam(":picLink", $fetchQuizOwnerPic["picture_link"]);
			}
			//end
			$dbSubject = $_POST["topic"];
			if($dbSubject == 'null' || $dbSubject == 'newTopic')
			{
				$dbSubject = null;
			}
			
			//checkNewLanguage
			$language = $_POST["language"];
			if($_POST["language"] == "newLanguage")
			{
				$language = "English";
			}
			
			$stmt->bindParam(":subject_id", $dbSubject);
			$stmt->bindParam(":name", $_POST["quizText"]);
			$stmt->bindParam(":starttime", $startdate);
			$stmt->bindParam(":endtime", $enddate);
			$stmt->bindParam(":random_questions", $rndQuestions);
			$stmt->bindParam(":random_answers", $rndAnswers);
			$stmt->bindParam(":limited_time", $limited_time);
			$stmt->bindParam(":result_visible", $result_visible);
			$stmt->bindParam(":result_visible_points", $result_visible_points);
			$stmt->bindParam(":language", $language);
			$stmt->bindParam(":amount_of_questions", $amountOfQuestions);
			$stmt->bindParam(":public", $isQuizPublic);
			$stmt->bindParam(":description", $_POST["description"]);
			$stmt->bindParam(":priority", $_POST["quizPriority"]);
			$stmt->bindParam(":amount_participations", $maxParticipations);
			$stmt->bindParam(":quiz_passed", $quizPassed);
			$stmt->bindParam(":singlechoise_multiplier", $_POST["singlechoiseMult"]);
			$stmt->bindParam(":noParticipationPeriod", $noParticipationPeriod);
			$stmt->bindParam(":showTaskPaper", $showQuizTaskPaper);
			
			
			if($stmt->execute())
			{
				$insertedQuizId = $dbh->lastInsertId();
				
				if($_POST["mode"] == "edit")
				{
					$insertedQuizId = $_POST["quiz_id"];
				}


				//questions with csv upload
				if(isset($_FILES["btnImportQuestionsFromCSV2"]) && $_FILES["btnImportQuestionsFromCSV2"]["name"] != "")
				{
					if ($_FILES["file"]["error"] > 0) {
						header("Location: ?p=quiz&code=-28");
						exit;
					}
					$imageFileType = pathinfo($_FILES["btnImportQuestionsFromCSV2"]["name"], PATHINFO_EXTENSION);
					$imageFileType = strtolower($imageFileType);
					if($imageFileType != "csv")
					{
						header("Location: ?p=quiz&code=-29");
						exit;
					}
				
					if($_POST["addOrReplaceQuestions"] == 0 && $_POST["mode"] == "edit") //replace questions
					{
						//unlink all existing questions
						$stmt = $dbh->prepare("delete from qunaire_qu where questionnaire_id = :qId");
						$stmt->bindParam(":qId", $_POST["quiz_id"]);
						$stmt->execute();
					}
					
					$questionUploadFileName = $_FILES["btnImportQuestionsFromCSV2"]["name"];
					
					move_uploaded_file($_FILES["btnImportQuestionsFromCSV2"]["tmp_name"], "uploadedImages/" . $questionUploadFileName);
					
					$questionUploadFileName = "uploadedImages/".$questionUploadFileName;
					
					if (($handle = fopen($questionUploadFileName, "r")) !== FALSE) {
						
						$orderCounter = 0;
						$amountQuestionsWithNoRightAnswer = 0;
						$amountQuestionsWithNoRightAnswerWhichOne = [];
						$firstline = true;
						$csvAnswerStart = false;
						$csvNumber = $csvKeyword = -1;
						$csvQuestion = 0;
						$csvAnswer = 1;
						while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
							
							for($j = 0; $j < count($data); $j++) //all answers in scv
							{
								$data[$j] = mb_convert_encoding($data[$j], "UTF-8");
								if($firstline && !$csvAnswerStart)
								{
									$headingData = checkStringIn(strtolower($data[$j]));
									if($headingData[0])
									{
										switch ($headingData[1])
										{
											case 0:
												$csvNumber = $j;
												break;
											case 1:
												$csvQuestion = $j;
												break;
											case 2:
												$csvAnswer = $j;
												$csvAnswerStart = true;
												break;
											case 3:
												$csvKeyword = $j;
												break;
										}
									}
								}
							}
							
							//check first line if its a header "question / Answer" line
							if($firstline && (strpos(strtolower($data[0]), "question") !== false || strpos(strtolower($data[0]), "frage") !== false || strpos(strtolower($data[1]), "answer") !== false || strpos(strtolower($data[1]), "antwort") !== false ))
							{
								$firstline = false;
								continue;
							}
							
							//check if questiontext AND all answers are already there
							//if yes use this id instead of insert the same question
							
							$stmt = $dbh->prepare("select question.id as qId, question.text as qText, answer.id as aId, answer.text as aText, is_correct from question inner join answer_question on answer_question.question_id = question.id inner join answer on answer.id = answer_question.answer_id where question.text = :text");
							$stmt->bindParam(":text", $data[$csvQuestion]);
							$stmt->execute();
							$allIn = false;
							$fetchCheckQuestion = null;
							$questionCheckRowCount = $stmt->rowCount();
							if($questionCheckRowCount > 0) //Question already exists
							{
								$allInCount = 0;
								$fetchCheckQuestion = $stmt->fetchAll(PDO::FETCH_ASSOC);
								for($i = 0; $i < count($fetchCheckQuestion); $i++) //all answers- from query inc. questionstring
								{
									for($j = $csvAnswer; $j < count($data); $j++) //all answers in scv
									{
										if($fetchCheckQuestion[$i]["aText"] == str_replace("*", "", $data[$j]))
										{
											$allInCount++;
										}
									}
								}
								if($questionCheckRowCount == $allInCount)
									$allIn = true;
							}
							
							if(!$allIn)
							{
								$amountofCorrectAnswers = 0;
								for($i = $csvAnswer; $i < count($data); $i++)
								{
									if(strpos($data[$i], "*") == true && (strlen($data[$i])-1 == strpos($data[$i], "*") || strlen($data[$i])-2 == strpos($data[$i], "*")))
									{
										$amountofCorrectAnswers++;
									}
								}
								
								if($amountofCorrectAnswers == 0)
								{
									$amountQuestionsWithNoRightAnswer++;
									array_push($amountQuestionsWithNoRightAnswerWhichOne, htmlspecialchars($data[$csvQuestion]));
								}
								
								$type_id = 1;
								if($amountofCorrectAnswers > 1)
									$type_id = 2;
								
								$stmt = $dbh->prepare("insert into question	(text, owner_id, type_id, subject_id, language, creation_date, public, last_modified, picture_link)
									values (:text, ". $_SESSION["id"] .", :type_id, :subject_id, :language, ".time().", :public, ".time().", :picLink)");
								$stmt->bindParam(":text", $data[$csvQuestion]);
								$stmt->bindParam(":type_id", $type_id);
								$stmt->bindValue(":subject_id", NULL);
								$stmt->bindValue(":language", "Deutsch");
								$stmt->bindValue(":public", 0);
								$stmt->bindValue(":picLink", NULL);
								if(!$stmt->execute())
								{
									header("Location: ?p=quiz&code=-31");
									exit;
								}
								$insertedQuestionId = $dbh->lastInsertId();
								
								for($i = $csvAnswer; $i < count($data); $i++)
								{
									if($data[$i] == "")
										continue;
									$isCorrect = false;
									$answerInsertText = $data[$i];
									$stmt = $dbh->prepare("insert into answer (text) values (:text)");
									if(strpos($data[$i], "*", strlen($data[$i]) - 2) !== false)
									{
										$isCorrect = true;
										$answerInsertText = substr($data[$i], 0, strpos($data[$i], "*", strlen($data[$i]) - 2));
									}
									$stmt->bindParam(":text", $answerInsertText);
									if(!$stmt->execute())
									{
										header("Location: ?p=quiz&code=-32");
										exit;
									}
									
									$insertedAnswerId = $dbh->lastInsertId();
									
									$stmt = $dbh->prepare("insert into answer_question values (:answer_id, :question_id, :is_correct, :order)");
									$stmt->bindParam(":answer_id", $insertedAnswerId);
									$stmt->bindParam(":question_id", $insertedQuestionId);
									if($type_id == 2 && $isCorrect == false)
										$isCorrect = -1;
									$stmt->bindParam(":is_correct", $isCorrect);
									$stmt->bindValue(":order", ($i-1));
									if(!$stmt->execute())
									{
										header("Location: ?p=quiz&code=-33");
										exit;
									}
								}
							} else {
								$insertedQuestionId = $fetchCheckQuestion[0]["qId"];
							}
							
							$stmt = $dbh->prepare("insert into qunaire_qu (questionnaire_id, question_id, `order`) values (:questionnaire_id, :question_id, :order)");
							$stmt->bindParam(":questionnaire_id", $insertedQuizId);
							$stmt->bindParam(":question_id", $insertedQuestionId);
							$stmt->bindParam(":order", $orderCounter);
							if(!$stmt->execute())
							{
								header("Location: ?p=quiz&code=-34");
								exit;
							}
							$orderCounter++;
						}
						fclose($handle);
						unlink($questionUploadFileName);
						
					} else {
						header("Location: ?p=quiz&code=-30&info=".$questionUploadFileName);
						exit;
					}
				
				}

				//requested language
				if($_POST["language"] == "newLanguage")
				{
					$stmt = $dbh->prepare("insert into language_request (user_id, language, timestamp, questionnaire_id) values (:user_id, :language, :timestamp, :questionnaire_id)");
					$stmt->bindParam(":user_id", $_SESSION["id"]);
					$stmt->bindParam(":language", $_POST["newLanguage"]);
					$stmt->bindParam(":timestamp", time());
					$stmt->bindParam(":questionnaire_id", $insertedQuizId);
					$stmt->execute();
				}
				
				//requested topic
				if($_POST["topic"] == "newTopic")
				{
					$stmt = $dbh->prepare("insert into topic_request (user_id, topic, timestamp, questionnaire_id) values (:user_id, :topic, :timestamp, :questionnaire_id)");
					$stmt->bindParam(":user_id", $_SESSION["id"]);
					$stmt->bindParam(":topic", $_POST["newTopic"]);
					$stmt->bindParam(":timestamp", time());
					$stmt->bindParam(":questionnaire_id", $insertedQuizId);
					$stmt->execute();
				}
				
				if(isset($_POST["btnAddQuestion"]))
				{
					header("Location: ?p=addQuestions&quizId=" . $insertedQuizId);
				} else if(isset($_POST["btnSave"]))
				{
					$qwnav = "";
					if($amountQuestionsWithNoRightAnswer > 0)
					{
						$qwnav = "&qwnav=" . implode(",", $amountQuestionsWithNoRightAnswerWhichOne);
					}
					header("Location: ?p=quiz&code=1&qwna=".$amountQuestionsWithNoRightAnswer . $qwnav."&info=");
				} else if(isset($_POST["btnSaveAsDraft"]))
				{
					header("Location: ?p=quiz&code=2");
				}
			} else {
				header("Location: ?p=quiz&code=-7&info=" . $case);
			}
			
		} else {
			$info = $_POST["mode"] . " " . (isset($_POST["btnSave"])  ||  isset($_POST["btnSaveAsDraft"])  || isset($_POST["btnAddQuestion"]))  . " " . 
				$_POST["quizText"] . " " . $_POST["topic"] . " " . $_POST["language"] . " " . 
				$_POST["endDate"] . " " . $_POST["endTime"]  . " " . 
				$_POST["startDate"] . " " . $_POST["startTime"] . " " . 
				$_POST["timeLimitMode"] . " " . 
				$_POST["reportAfterQuestion"] . " " .$_POST["quizPriority"] . " " . 
				$_POST["amountQuestionMode"] . " " . $_POST["maxParticipationsMode"];
			header("Location: ?p=quiz&code=-2&info=" .$info);
		}
	} else if($action =="addQuestions")
	{
		//addQuestions to Quiz
		//questions[]
		if(!isset($_POST["quizId"]))
		{
			header("Location: ?p=quiz&code=-2");
			exit;
		}
		
		//OWNER?
		$stmt = $dbh->prepare("select name, owner_id from questionnaire where id = :qId");
		$stmt->bindParam(":qId", $_POST["quizId"]);
		$stmt->execute();
		$fetchQnaireNameOwner = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if($fetchQnaireNameOwner["owner_id"] != $_SESSION["id"] && $_SESSION["role"]["admin"] != 1)
		{
			header("Location: ?p=quiz&code=-1&info=ert");
			exit;
		}
		
		if (!isset($_POST["questions"]))
		{
			header("Location: ?p=quiz&code=-12");
			exit;
		}
		
		$stmt = $dbh->prepare("delete from qunaire_qu where questionnaire_id = :qunaire_id");
		$stmt->bindParam(":qunaire_id", $_POST["quizId"]);
		$stmt->execute();
			
		foreach($_POST["questions"] as $value){
			
			$stmt = $dbh->prepare("insert into qunaire_qu (questionnaire_id, question_id) values (:qunaire_id, :q_id)");
			$stmt->bindParam(":qunaire_id", $_POST["quizId"]);
			$stmt->bindParam(":q_id", $value);
			
			if(!$stmt->execute())
			{
				header("Location: ?p=quiz&code=-13");
				exit;
			}
			
		}
		
		header("Location: ?p=createEditQuiz&mode=edit&id=" . $_POST["quizId"]);
	} else if($action == 'delQuestionFromQuiz')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select owner_id from questionnaire where id = :id");
			$stmt->bindParam(":id", $_GET["questionaireId"]);
			$stmt->execute();
			$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
		
			if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1 || amIAssignedToThisQuiz($dbh, $_GET["questionaireId"]))
			{
				$stmt = $dbh->prepare("delete from qunaire_qu where questionnaire_id = :questionnaireId and question_id = :questionId");
				$stmt->bindParam(":questionnaireId", $_GET["questionaireId"]);
				$stmt->bindParam(":questionId", $_GET["questionId"]);
				if($stmt->execute())
				{
					echo "ok";
				} else {echo "failed";}
			} else {echo "failed";}
		} else {echo "failed";}
	} else if($action == 'delQuiz')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select owner_id from questionnaire where id = :id");
			$stmt->bindParam(":id", $_GET["quizId"]);
			$stmt->execute();
			$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
				
			if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1)
			{
				$stmt = $dbh->prepare("delete from qunaire_qu where questionnaire_id = :qId");
				$stmt->bindParam(":qId", $_GET["quizId"]);
				$delQunaire_qu = $stmt->execute();
				
				$stmt = $dbh->prepare("select id from user_qunaire_session where questionnaire_id = :qId");
				$stmt->bindParam(":qId", $_GET["quizId"]);
				$stmt->execute();
				$fetchSessionId = $stmt->fetchAll(PDO::FETCH_ASSOC);
				
				$delAn_qu_user = true;
				for($i = 0; $i < count($fetchSessionId); $i++)
				{
					$stmt = $dbh->prepare("delete from an_qu_user where session_id = :sId");
					$stmt->bindParam(":sId", $fetchSessionId[$i]["id"]);
					if(!$stmt->execute())
						$delAn_qu_user = false;
				}
				
				$stmt = $dbh->prepare("delete from user_qunaire_session where questionnaire_id = :qId");
				$stmt->bindParam(":qId", $_GET["quizId"]);
				$delUser_qunaire_session = $stmt->execute();
				
				$stmt = $dbh->prepare("delete from qunaire_assigned_to where questionnaire_id = :qId");
				$stmt->bindParam(":qId", $_GET["quizId"]);
				$delQunaire_assigned_to = $stmt->execute();
				
				$stmt = $dbh->prepare("delete from questionnaire where id = :qId");
				$stmt->bindParam(":qId", $_GET["quizId"]);
				$delQuestionnaire = $stmt->execute();
				
				if($delQunaire_qu && $delUser_qunaire_session && $delQuestionnaire && $delAn_qu_user && $delQunaire_assigned_to)
				{
					echo "deleteQuizOk";
				} else {
					echo "failed";
				}
			}
		}
	} else if($action == 'addAssignation')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select owner_id from questionnaire where id = :id");
			$stmt->bindParam(":id", $_GET["questionaireId"]);
			$stmt->execute();
			$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
		
			if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1 || amIAssignedToThisQuiz($dbh, $_GET["questionaireId"]))
			{
				$stmt = $dbh->prepare("select id from questionnaire where id = :qId");
				$stmt->bindParam(":qId", $_GET["questionaireId"]);
				$stmt->execute();
				if($stmt->rowCount() != 1)
				{
					echo "failed";
				}
				
				$stmt = $dbh->prepare("select id from user where email = :email");
				$stmt->bindParam(":email", $_GET["userEmail"]);
				if(!$stmt->execute())
					echo "failed";
				$userId = $stmt->fetch(PDO::FETCH_ASSOC);
				
				$stmt = $dbh->prepare("insert into qunaire_assigned_to values (:questionnaireId, :user_id)");
				$stmt->bindParam(":questionnaireId", $_GET["questionaireId"]);
				$stmt->bindParam(":user_id", $userId["id"]);
				if($stmt->execute())
				{
					echo "ok1";
				} else {
					echo "failed";
				}
			} else {echo "failed";}
		} else {echo "failed";}
	} else if($action == 'delAssignation')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select owner_id from questionnaire where id = :id");
			$stmt->bindParam(":id", $_GET["questionaireId"]);
			$stmt->execute();
			$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
		
			if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1)
			{ 
				$stmt = $dbh->prepare("delete from qunaire_assigned_to where user_id = :uId and questionnaire_id = :qId");
				$stmt->bindParam(":qId", $_GET["questionaireId"]);
				$stmt->bindParam(":uId", $_GET["userId"]);
				if($stmt->execute())
				{
					echo "ok1";
				} else {echo "failed";}
			} else {echo "failed";}
		} else {echo "failed";}
	} else if($action == 'moveQuestion')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select owner_id from questionnaire where id = :id");
			$stmt->bindParam(":id", $_GET["questionaireId"]);
			$stmt->execute();
			$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
		
			if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1 || amIAssignedToThisQuiz($dbh, $_GET["questionaireId"]))
			{
				$qOrders=json_decode($_GET["qOrder"]);
				for ($i = 0; $i < count($qOrders); $i++) {
					$stmt = $dbh->prepare("update qunaire_qu set `order` = :order where questionnaire_id = :qunaireId and question_id = :qId");
					$stmt->bindParam(":order", $i);
					$stmt->bindParam(":qunaireId", $_GET["questionaireId"]);
					$stmt->bindParam(":qId", $qOrders[$i]);
					if(!$stmt->execute())
						echo "failed";
				}
				echo "ok";
			} else {echo "failed";}
		} else {echo "failed";}
	} else if($action == 'delGroup')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select owner_id, name from `group` where id = :id");
			$stmt->bindParam(":id", $_GET["groupId"]);
			$stmt->execute();
			$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
			
			//if owner_id == NULL -> means the creator of this group grant access to all manager to manage this group
			//in the future it can changed to assign additional owners
			if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1 || $fetchOwer["owner_id"] == NULL)
			{
				$stmt = $dbh->prepare("update user set group_id = :newGroupId where group_id = :oldGroupId");
				$stmt->bindValue(":newGroupId", NULL);
				$stmt->bindParam(":oldGroupId", $_GET["groupId"]);
				$executeUpdateUser = $stmt->execute();
				
				$stmt = $dbh->prepare("delete from `group` where id = :id");
				$stmt->bindParam(":id", $_GET["groupId"]);
				$executeDelGroup = $stmt->execute();
				
				if($executeUpdateUser && $executeDelGroup)
				{
				addEvent($dbh, "delGroup", $_SESSION["id"] . " deleted the group " . $fetchOwer["name"]);
					echo "ok";
				}
				else
					echo "failed";
			} else {echo "failed";}
		} else {echo "failed";}
	} else if($action == 'addGroup')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			do{
				$randomKey = substr(md5(uniqid(rand(), true)), 1, 6);
				$stmt = $dbh->prepare("select token from `group` where token = :token");
				$stmt->bindParam(":token", $randomkey);
				$stmt->execute();
			} while($stmt->rowCount() > 0);
			
			$stmt = $dbh->prepare("insert into `group` (name, owner_id, token) values (:groupName, :ownerId, :token)");
			$stmt->bindParam(":groupName", $_GET["groupName"]);
			$stmt->bindParam(":ownerId", $_SESSION["id"]);
			$stmt->bindParam(":token", $randomKey);
			if($stmt->execute())
			{
				addEvent($dbh, "addGroup", $_SESSION["id"] . " added the group " . $_GET["groupName"]);
				echo "ok";
			}
			else 
				echo $randomKey;
		} else {echo "failed";}
	} else if($action == 'delUserFromGroup')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select owner_id from `group` where id = :id");
			$stmt->bindParam(":id", $_GET["groupId"]);
			$stmt->execute();
			$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
				
			//if owner_id == NULL -> means the creator of this group grant access to all manager to manage this group
			//in the future it can changed to assign additional owners
			if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1 || $fetchOwer["owner_id"] == NULL)
			{
				$stmt = $dbh->prepare("update user set group_id = :newGroupId where id = :uId");
				$stmt->bindValue(":newGroupId", NULL);
				$stmt->bindParam(":uId", $_GET["userId"]);
				if($stmt->execute())
					echo "ok";
				else
					echo "failed";
			}
		} else {echo "failed";}
	} else if($action == 'addUserToGroup')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select owner_id from `group` where id = :id");
			$stmt->bindParam(":id", $_GET["groupId"]);
			$stmt->execute();
			$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
			
			//if owner_id == NULL -> means the creator of this group grant access to all manager to manage this group
			//in the future it can changed to assign additional owners
			if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1 || $fetchOwer["owner_id"] == NULL)
			{
				$stmt = $dbh->prepare("update user set group_id = :newGroupId where email = :uEmail");
				$stmt->bindValue(":newGroupId", $_GET["groupId"]);
				$stmt->bindParam(":uEmail", $_GET["userEmail"]);
				if($stmt->execute())
					echo json_encode(["ok", $stmt->rowCount()]);
				else
					echo  json_encode(["failed"]);
			}
		} else {echo json_encode(["failed"]);}
	} else if($action == 'createPoll')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			if(isset($_POST["question"]) && isset($_POST["answer_0"]) && isset($_POST["answer_1"]) && isset($_POST["questionType"]))
			{
				do{
					$six_digit_random_number = mt_rand(1000, 9999);
					$stmt = $dbh->prepare("select token from poll where token = :token");
					$stmt->bindParam(":token", $six_digit_random_number);
					$stmt->execute();
				} while($stmt->rowCount() > 0);
				
				$stmt = $dbh->prepare("insert into poll (owner_id, question, open, token, creation_date, question_type, picture) values (:owner_id, :question, :open, :token, :creation_date, :question_type, :picLink)");
				$stmt->bindParam(":owner_id", $_SESSION["id"]);
				$stmt->bindParam(":question", $_POST["question"]);
				$stmt->bindValue(":open", 1);
				$stmt->bindParam(":token", $six_digit_random_number);
				$stmt->bindParam(":creation_date", time());
				$questionType = 0;
				if($_POST["questionType"] == "multiple")
					$questionType = 1;
				$stmt->bindParam(":question_type", $questionType);
				
				//pictureLink
				//fileupload
				if(isset($_FILES["picture"]) && $_FILES["picture"]["name"] != "")
				{
					$subCode = 0;
					//upload picture
					$imageFileType = pathinfo($_FILES["picture"]["name"], PATHINFO_EXTENSION);
					$targetDir = "uploadedImages/";
					$targetFile = $targetDir . "poll_" . date("d_m_y_H_i_s", time()) . "." . $imageFileType;
						
					//check File is an image
					if(!getimagesize($_FILES["picture"]["tmp_name"]))
					{
						$subCode = -8;
					}
					//check if file already exists
					if(file_exists($targetFile))
					{
						$subCode = -9;
					}
					//check size
					if($_FILES["picture"]["size"] > 20000000)
					{
						$subCode = -10;
					}
					//check file format | .jpeg,.jpg,.bmp,.png,.gif
					$imageFileType = strtolower($imageFileType);
					if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "bmp")
					{
						$subCode = -11;
					}
					
					if(!move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFile))
					{
						$subCode = -12;
					}
				}
					
				//$dbNull = NULL;
				if(isset($_FILES["picture"]) && $_FILES["picture"]["name"] != "")
					$stmt->bindParam(":picLink", $targetFile);
				else
					$stmt->bindParam(":picLink", $dbNull);
				
				if($stmt->execute())
				{
					$insertedPollId = $dbh->lastInsertId();
					for ($i = 0; $i < 5; $i++)
					{
						if($_POST["answer_" . $i] != "")
						{
							$stmt = $dbh->prepare("insert into poll_answers (poll_id, text, correct) values (:poll_id, :text, :correct)");
							//correct answer check
							if($_POST["questionType"] == "multiple")
							{
								if(isset($_POST["correctAnswer_" . $i]))
									$stmt->bindValue(":correct", $_POST["correctAnswer_" . $i]);
							} else if($_POST["questionType"] == "single")
							{
								if(isset($_POST["correctAnswer"]) && $_POST["correctAnswer"] == $i)
									$stmt->bindValue(":correct", 1);	
								else 
									$stmt->bindValue(":correct", 0);								
							}
							$stmt->bindParam(":poll_id", $insertedPollId);
							$stmt->bindParam(":text", $_POST["answer_" . $i]);
							if(!$stmt->execute())
							{
								header("Location: ?p=poll&code=-4");
								exit;
							}
						}
					}
					
					header("Location: ?p=poll&token=" . $six_digit_random_number . "&info=" . $_FILES["picture"]["name"]);
					exit;
				} else {
					header("Location: ?p=poll&code=-3&subCode=" . $subCode);
					exit;
				}
			} else {
				header("Location: ?p=poll&code=-2");
				exit;
			}
		} else {
			header("Location: ?p=poll&code=-1");
			exit;
		}
	} else if($action == 'getPollVotes')
	{
		if(isset($_GET["pollToken"]))
		{
			$stmt = $dbh->prepare("select * from poll_answers where poll_id = :pollToken");
			$stmt->bindParam(":pollToken", $_GET["pollToken"]);
			$stmt->execute();
			$fetchPollAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			$stmt = $dbh->prepare("select * from poll_user_interactions where poll_id = :pollToken");
			$stmt->bindParam(":pollToken", $_GET["pollToken"]);
			$stmt->execute();
			$fetchPollPoints = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			$stmt = $dbh->prepare("select * from poll where id = :pollToken");
			$stmt->bindParam(":pollToken", $_GET["pollToken"]);
			$stmt->execute();
			$fetchPoll = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			//echo $_GET["pollToken"];
			echo json_encode([$fetchPollAnswers, $fetchPollPoints, $fetchPoll]);
			//json_encode(["ok", $stmt->rowCount()])
		} else {echo "failed";}
	} else if($action == 'switchPollState')
	{
		if(isset($_GET["newActive"]) && $_SESSION["role"]["creator"] == 1)
		{
			$stmt = $dbh->prepare("select open from poll where id = :pId");
			$stmt->bindParam(":pId", $_GET["newActive"]);
			$stmt->execute();
			$fetchOpen = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$stmt = $dbh->prepare("update poll set open = :open where id = :pId");
			$stmt->bindParam(":pId", $_GET["newActive"]);
			$newState = -1;
			if($fetchOpen["open"] == 0)
			{
				$stmt->bindValue(":open", 1);
				$newState = 1;
			} else { 
				$stmt->bindValue(":open", 0);
				$newState = 0;
			}
			if($stmt->execute())
			{
				echo json_encode(["ok", $newState]);
			} else {echo json_encode(["failed"]);}
		} else {echo json_encode(["failed"]);}
	} else if($action == 'sendVote')
	{
		//prüfen ob cookie schon vorhanden
		if(isset($_COOKIE['pollId' . $_POST["pollId"]])){
			header("Location: ?p=poll&code=-5");
			exit;
		}
		
		$stmt = $dbh->prepare("select *, poll.id as pId, poll_answers.id as aId from poll inner join poll_answers on poll.id = poll_id where poll.id = :pollId");
		$stmt->bindParam(":pollId", $_POST["pollId"]);
		$stmt->execute();
		$fetchPoll = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		//prüfen ob poll noch aktiv
		if($fetchPoll[0]["open"] == 0){
			header("Location: ?p=poll&code=-6&info=" . $_POST["pollId"]);
			exit;
		}
		
		//insert vote
		//check singlechoise or multiplechoise
		if($fetchPoll[0]["question_type"] == 0)
		{
			if(isset($_POST["voteAnswers"]))
			{
				$stmt = $dbh->prepare("update poll_answers set yesVotes = yesVotes + 1 where id = :aId");
				$stmt->bindParam(":aId", $_POST["voteAnswers"]);
				if($stmt->execute())
				{
					setcookie("pollId" . $_POST["pollId"], $_POST["voteAnswers"], time() + 86400);
					header("Location: ?p=poll&token=" . $fetchPoll[0]["token"]);
					exit;
				}
			} else {
				header("Location: ?p=poll&code=-7");
				exit;
			}
		} else if($fetchPoll[0]["question_type"] == 1){ //multiplechoise
			$cookieArray = array();
			$userPoints = 0;
			for($i = 0; $i < count($fetchPoll); $i++)
			{
				if(isset($_POST["voteAnswers_" . $fetchPoll[$i]["aId"]]) && $_POST["voteAnswers_" . $fetchPoll[$i]["aId"]] != "")
				{
					$voteStr = "";
					switch ($_POST["voteAnswers_" . $fetchPoll[$i]["aId"]]) {
						case -1:
							$voteStr = "noVotes";
							break;
						case 0:
							$voteStr = "neutralVotes";
							break;
						case 1:
							$voteStr = "yesVotes";
							break;
					}
					$stmt = $dbh->prepare("update poll_answers set ".$voteStr." = ".$voteStr." + 1 where id = :aId");
					$stmt->bindParam(":aId", $fetchPoll[$i]["aId"]);
					if(!$stmt->execute())
					{
						header("Location: ?p=poll&code=-8");
						exit;
					}
					$cookieArray[$fetchPoll[$i]["aId"]] = $_POST["voteAnswers_" . $fetchPoll[$i]["aId"]];
					//calc user points
					
					if($_POST["voteAnswers_" . $fetchPoll[$i]["aId"]] == $fetchPoll[$i]["correct"])
						$userPoints++;
					else if($_POST["voteAnswers_" . $fetchPoll[$i]["aId"]] != 0 && $_POST["voteAnswers_" . $fetchPoll[$i]["aId"]] != $fetchPoll[$i]["correct"])
						$userPoints--;
				}
			}
			//insert points
			$stmt = $dbh->prepare("insert into poll_user_interactions values (:pollId, :points)");
			$stmt->bindParam(":pollId", $fetchPoll[0]["poll_id"]);
			$stmt->bindParam(":points", $userPoints);
			$stmt->execute();
			
			setcookie("pollId" . $_POST["pollId"], json_encode($cookieArray), time() + 86400);
			header("Location: ?p=poll&token=" . $fetchPoll[0]["token"]);
			exit;
		}
	} else if($action == 'changeActive')
	{
		if($_SESSION["role"]["admin"] == 1 && isset($_GET["userId"]))
		{
			$stmt = $dbh->prepare("select isActivated from user where id = :uId");
			$stmt->bindParam(":uId", $_GET["userId"]);
			$stmt->execute();
			$userFetch = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$active = false;
			if(!$userFetch["isActivated"])
				$active = true;
				
			$stmt = $dbh->prepare("update user set isActivated = :iA where id = :uId");
			$stmt->bindParam(":uId", $_GET["userId"]);
			$stmt->bindParam(":iA", $active);
			if($stmt->execute())
			{
				{echo json_encode(["ok", $active]);}
			} else {echo json_encode(["failed"]);}
			
		} else {echo json_encode(["failed"]);}
	} else if($action == 'getCorrectAnswers')
	{
		if(isset($_GET["pollId"]) && $_GET["pollId"] != ""){
			
			$stmt = $dbh->prepare("select poll_answers.id, correct, question_type, open from poll_answers inner join poll on poll_id = poll.id where poll_id = :pollId");
			$stmt->bindParam(":pollId", $_GET["pollId"]);
			if($stmt->execute() && $stmt->rowCount() > 0)
			{
				$fetchPoll = $stmt->fetchAll(PDO::FETCH_ASSOC);
				if($_SESSION['role']['creator'] == 1  || $fetchPoll[0]["open"] == 0) //isset($_COOKIE['pollId' . $_POST["pollId"]]) || 
				{
					echo json_encode(["ok", $fetchPoll, $stmt->rowCount(), $_GET["pollId"]]);
				}
			} else {echo json_encode(["failed"]);}
		} else {echo json_encode(["failed"]);}
	}
	else if($action == 'changeAssignedGroups')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select owner_id from questionnaire where id = :id");
			$stmt->bindParam(":id", $_GET["questionaireId"]);
			$stmt->execute();
			$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
		
			if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1 || amIAssignedToThisQuiz($dbh, $_GET["questionaireId"]))
			{				
				$groups=json_decode($_GET["groups"]);

				$stmt = $dbh->prepare("delete from assign_group_qunaire where questionnaire_id = :qId");
				$stmt->bindParam(":qId", $_GET["questionaireId"]);
				$stmt->execute();
				
				for ($i = 0; $i < count($groups); $i++) {
					$stmt = $dbh->prepare("insert into assign_group_qunaire (group_id, questionnaire_id) values (:gId, :qId)");
					$stmt->bindParam(":gId", $groups[$i]);
					$stmt->bindParam(":qId", $_GET["questionaireId"]);
					if(!$stmt->execute())
						echo "fail";
				}
				echo "ok";
			}else {echo "fail";}
		}else {echo "fail";}
	}
	else if($action == 'revealUserName')
	{
		if($_SESSION['role']['creator'] == 1)
		{
			$stmt = $dbh->prepare("select owner_id from questionnaire where id = :id");
			$stmt->bindParam(":id", $_GET["questionaireId"]);
			$stmt->execute();
			$fetchOwer = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if($_SESSION["id"] == $fetchOwer["owner_id"] || $_SESSION['role']['admin'] == 1 || amIAssignedToThisQuiz($dbh, $_GET["questionnaireId"]))
			{
				$stmt = $dbh->prepare("select firstname, lastname from user_data where user_id = :uId");
				$stmt->bindParam(":uId", $_GET["userId"]);
				$stmt->execute();
				$fetchUserName = $stmt->fetch(PDO::FETCH_ASSOC);
				
				echo json_encode(["ok", $fetchUserName["lastname"] . " " . $fetchUserName["firstname"]]);
			} else {echo json_encode(["failed"]);}
		} else {echo json_encode(["failed"]);}
	}
	else if($action == 'queryAnswers')
	{
		if($_SESSION['role']['creator'] == 1 && isset($_GET["questionId"]))
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
	else 
	{
		header("Location: ?p=quiz&code=-1&info=ppp");
	}

	function checkStringIn($str)
	{
		$stringsToCheck = [["number", 0], ["nr", 0], ["nummer", 0], ["question", 1], ["frage", 1], ["answer", 2], ["antwort", 2], ["keyword", 3], ["schl", 3]];
		
		for ($i = 0; $i < count($stringsToCheck); $i++)
		{
			if(strpos(strtolower($str), $stringsToCheck[$i][0]) !== false)
				return [true, $stringsToCheck[$i][1]];
		}
		return [false];
	}
?>