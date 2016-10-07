<?php
session_start();

include_once '../config/config.php';

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
if($_SESSION["role"]["user"] != 1)
{
	header("Location: index.php?p=home&code=-20");
	exit;
}

if($action == "startQuiz")
{
	
	$id = 0;
	if(isset($_GET["quizId"]))
	{
		$id = $_GET["quizId"];
	} else {
		header("Location: index.php?p=quiz&code=-2");
		exit;
	}
	
	$stmt = $dbh->prepare("select id, amount_participations, public, starttime, endtime, noParticipationPeriod from questionnaire where id = :id");
	$stmt->bindParam(":id", $id);
	$stmt->execute();
	$fetchQuiz = $stmt->fetch(PDO::FETCH_ASSOC);
	if($stmt->rowCount() <= 0)
	{
		header("Location: index.php?p=quiz&code=-15");
		exit;
	}

	include_once 'modules/extraFunctions.php';
	//Quiz enabled? (time, special access)
	//maybe assigned Quiz?
	if($_SESSION['role']['admin'] != 1)
	{
		if($fetchQuiz["public"] != 1)
		{
			header("Location: index.php?p=quiz&code=-25");
			exit;
		}
		if(($fetchQuiz["starttime"] > time() || $fetchQuiz["endtime"] < time()) && $fetchQuiz["noParticipationPeriod"] == 0)
		{
			header("Location: index.php?p=quiz&code=-26");
			exit;
		}
		if(!doThisQuizHaveAGroupRestrictionAndAmIInThisGroup($dbh, $id))
		{
			header("Location: index.php?p=quiz&code=-38");
			exit;
		}
	}
	
	//max participations
	$stmt = $dbh->prepare("select id from user_qunaire_session where questionnaire_id = :questionnaire_id and user_id = :user_id");
	$stmt->bindParam(":questionnaire_id", $id);
	$stmt->bindParam(":user_id", $_SESSION["id"]);
	$stmt->execute();
	$participations = $stmt->rowCount();
	if($fetchQuiz["amount_participations"] != 0 && $fetchQuiz["amount_participations"] <= $participations && $_SESSION["role"]["admin"] != 1)
	{
		header("Location: index.php?p=quiz&code=-35");
		exit;
	}
	
	$stmt = $dbh->prepare("insert into user_qunaire_session (user_id, questionnaire_id, starttime) 
			values (:user_id, :questionnaire_id, :starttime)");
	$stmt->bindParam(":user_id", $_SESSION["id"]);
	$stmt->bindParam(":questionnaire_id", $id);
	$stmt->bindParam(":starttime", time());
	if($stmt->execute())
	{
		$_SESSION["quizSession"] = $id;
		$_SESSION["idSession"] = $dbh->lastInsertId();
		$_SESSION["questionNumber"] = 0;
		$_SESSION["additionalTime"] = 0;
		header("Location: ?p=participate");
		exit;
	} else {
		header("Location: index.php?p=quiz&code=-16");
		exit;
	}
	
} else if($action == "endQuiz")
{
	$stmt = $dbh->prepare("update user_qunaire_session set endtime = :endtime, end_state = :end_state where id = :idSession"); //incomplete statement (points, finished in time)
	$stmt->bindParam(":idSession", $_SESSION["idSession"]);
	$stmt->bindParam(":endtime", time());
	$code = 0;
	switch ($_GET["state"])
	{
		case 'correct':
			$code = 1;
			break;
		case 'timeExceeded':
			$code = 2;
			break;
		case 'abort':
			$code = 3;
			break;
		default:
			$code = 3;
			break;
	}
	$stmt->bindParam(":end_state", $code);
		
	if($stmt->execute())
	{
		$quizId = $_SESSION["quizSession"];
		$_SESSION["quizSession"] = -1;
		$_SESSION["idSession"] = -1;
		$_SESSION["questionNumber"] = -1;
		$_SESSION["unansweredNumber"] = -1;
		$_SESSION["next_button_time"] = -1;
		$_SESSION["additionalTime"] = -1;
		header("Location: ?p=participationOutro&quizId=" . $quizId);
		exit;
	} else {
		header("Location: index.php?p=quiz&code=-37");
		exit;
	}
} else if(isset($_POST["action"]) && isset($_POST["action"]) == 'saveAndNextQuestion')
{
	if(isset($_POST["questionId"]) && (isset($_POST["prevQuestion"]) || isset($_POST["nextQuestion"]))) 
	{
		
		$stmt = $dbh->prepare("select answer.id, question.type_id from answer inner join answer_question on answer_question.answer_id = answer.id inner join question on question.id = answer_question.question_id where answer_question.question_id = :question_id");
		$stmt->bindParam(":question_id", $_POST["questionId"]);
		$stmt->execute();
		$fetchAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		for($i = 0; $i < count($fetchAnswers); $i++)
		{
			$stmt = $dbh->prepare("select time_needed from an_qu_user where session_id = :session_id and answer_id = :answer_id and question_id = :question_id");
			$stmt->bindParam(":session_id", $_SESSION["idSession"]);
			$stmt->bindParam(":answer_id", $fetchAnswers[$i]["id"]);
			$stmt->bindParam(":question_id", $_POST["questionId"]);
			$stmt->execute();
			$fetchTimeNeededRowCount = $stmt->rowCount();
			$fetchTimeNeeded = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$stmt = $dbh->prepare("replace into an_qu_user (session_id, answer_id, question_id, selected, time_needed, question_order) 
					values (:session_id, :answer_id, :question_id, :selected, :time_needed, :question_order)");
			$stmt->bindParam(":session_id", $_SESSION["idSession"]);
			$stmt->bindParam(":answer_id", $fetchAnswers[$i]["id"]);
			$stmt->bindParam(":question_id", $_POST["questionId"]);
			$stmt->bindParam(":question_order", $_SESSION["questionNumber"]);
			
			if($fetchAnswers[$i]["type_id"] == 1) //question_type == 1 Singlechoise
			{
				if($_POST["answer"] != "noAnswer")
				{
					if($_POST["answer"] == $fetchAnswers[$i]["id"])
					{ $isSelected = 1; } else { $isSelected = 0; }
					//$isSelected = $_POST["answer"] == $fetchAnswers[$i]["id"];
				} else {
					$isSelected = NULL;
				}
			} else if($fetchAnswers[$i]["type_id"] == 2) //question_type == 2 Multiplechoise
			{
				if(isset($_POST["answer_" . $fetchAnswers[$i]["id"]]))
					$isSelected = $_POST["answer_" . $fetchAnswers[$i]["id"]];
				else 
					$isSelected = 0;
			}
			
			$stmt->bindParam(":selected", $isSelected);
			//$stmt->bindValue(":selected", NULL);
			if($fetchTimeNeededRowCount == 0)
			{
				$timeNeeded = time() - $_POST["generationTime"];
			}
			else
			{
				$timeNeeded = $fetchTimeNeeded["time_needed"];
			}
			$stmt->bindParam(":time_needed", $timeNeeded);
			//$stmt->bindValue(":time_needed", 0);
			if(!$stmt->execute())
			{
				header("Location: index.php?p=quiz&code=-23&info=participationTime" . "selected:" . $isSelected . "timeNeeded: " . $timeNeeded . "Error: " . $stmt->errorInfo()[0] . " POSTANSWER: " . $_POST["answer"] . " FETCHANSWER: " . $fetchAnswers[$i]["id"] . " equal: " . ($_POST["answer"] == $fetchAnswers[$i]["id"]) );
				exit;
			}
		} 
		
		if(isset($_POST["prevQuestion"]))
		{
			$_SESSION["questionNumber"]--;
			header("Location: ?p=participate");
			exit;
		} else if(isset($_POST["nextQuestion"]))
		{
			if(isset($_POST["unanswered"]) && $_POST["unanswered"] == "1")
			{
				header("Location: ?p=participate&info=unanswered");
				exit;
			} else {
				$_SESSION["questionNumber"]++;
				$_SESSION["next_button_time"] = $_POST["startTimeNextButton"];
				header("Location: ?p=participate");
				exit;
			}
		}
	} else if(isset($_POST["prevQuestion"]))
	{
		$_SESSION["questionNumber"]--;
		header("Location: ?p=participate");
		exit;
	} else {
		header("Location: index.php?p=quiz&code=-24");
		exit;
	}
} else if(isset($_GET["action"]) && isset($_GET["action"]) == 'insertNextButtonWaitTime')
{ 
	if($_SESSION["questionNumber"] > 0 && $_SESSION["next_button_time"] != -1)
	{
		$stmt = $dbh->prepare("select next_button_time from an_qu_user where session_id = :session_id and question_order = :qO");
		$stmt->bindParam(":session_id", $_SESSION["idSession"]);
		if(!isset($_GET["customQOrder"]))
			$qO = $_SESSION["questionNumber"]-1;
		else 
			$qO = $_GET["customQOrder"]-1;
		$stmt->bindParam(":qO", $qO);
		$stmt->execute();
		$fetchTimeNeededRowCount = $stmt->rowCount();
		$fetchTimeNeeded = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt = $dbh->prepare("update an_qu_user set next_button_time = :next_button_time where session_id = :session_id and question_order = :qO");
		$stmt->bindParam(":session_id", $_SESSION["idSession"]);
		$stmt->bindParam(":qO", $qO);
		
		if($fetchTimeNeeded["next_button_time"] == 0)
		{
			//$nextButtonTimeNeeded = round(microtime(true) * 1000) - $_SESSION["next_button_time"];
			$nextButtonTimeNeeded = $_GET["time"] - $_SESSION["next_button_time"];
			$_SESSION["additionalTime"] += $nextButtonTimeNeeded;
		}
		else
		{
			$nextButtonTimeNeeded = $fetchTimeNeeded["next_button_time"];
		}
		$stmt->bindParam(":next_button_time", $nextButtonTimeNeeded);
		if(!$stmt->execute())
		{
			echo json_encode(["failed", $nextButtonTimeNeeded]);
			exit;
		} else {
			echo json_encode(["ok", $nextButtonTimeNeeded, $_GET["time"], $_SESSION["next_button_time"], $_SESSION["idSession"], $_GET["questionId"], $qO]);
			exit;
		}
	}
} else {
	header("Location: index.php?p=home&code=-20");
	exit;
}
?>
