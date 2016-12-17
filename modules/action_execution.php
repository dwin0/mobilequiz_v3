<?php
include_once 'config/executionDefaultValues.php';

function updateExecution() {
	global $dbh;
	$response_array["status"] = "OK";

	//check correct owner
	if($_POST["mode"] == 'edit')
	{
		//return if user is not allowed to update execution
		if($_SESSION["role"]["creator"] != 1)
		{
			$response_array["status"] = "error";
			$response_array["text"] = $lang["execution-authorization-error"];
		}
	}

	$field = $_GET["field"];
	if(!isset($_POST["execId"]) || !isset($field) || !isset($_POST[$field]))
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["parameterError"];
	}

	if($response_array["status"] == "error")
	{
		echo json_encode($response_array);
		exit;
	}

	switch($field)
	{
		case "executionName":
			$response_array = updateExecutionName($_POST["executionName"], $_POST["maxChar"], $_POST["execId"], $dbh);
			break;
		case "quizPriority":
			$response_array = updateExecutionPriority($_POST["quizPriority"], $_POST["execId"], $dbh);
			break;
		case "noParticipationPeriod":
			$response_array = updateExecutionNoPartPeriod($_POST["noParticipationPeriod"], $_POST["execId"], $dbh);
			break;
		case "startDate":
			$response_array = updateExecutionStartDate($_POST["startDate"], $_POST["startTime"], $_POST["execId"], $dbh);
			break;
		case "startTime":
			$response_array = updateExecutionStartTime($_POST["startTime"], $_POST["startDate"], $_POST["execId"], $dbh);
			break;
		case "endDate":
			$response_array = updateExecutionEndDate($_POST["endDate"], $_POST["endTime"], $_POST["execId"], $dbh);
			break;
		case "endTime":
			$response_array = updateExecutionEndTime($_POST["endTime"], $_POST["endDate"], $_POST["execId"], $dbh);
			break;
	}
	
	if($response_array["status"] == "OK")
	{
		$stmt = $dbh->prepare("update execution set last_modified = ".time()." where id = :execId");
		$stmt->bindParam(":execId", $_POST["execId"]);
		if(! $stmt->execute())
		{
			$response_array["status"] = "error";
			$response_array["text"] = $lang["DB-Update-Error"];
		}
	}
	
	echo json_encode($response_array);
	exit;
}


function updateExecutionName($name, $maxChar, $execId, $dbh)
{
	$response_array["status"] = "OK";
	
	if(strlen($name) > $maxChar) {
		$response_array["status"] = "error";
		$response_array["text"] = $lang["inputToLong"];
		return $response_array;
	}
	
	$stmt = $dbh->prepare("update execution set name = :name where id = :execId");
	$stmt->bindParam(":name", $name);
	$stmt->bindParam(":execId", $execId);
	
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	return $response_array;
}

function updateExecutionPriority($resultChecked, $execId, $dbh)
{
	$response_array["status"] = "OK";
	
	if($resultChecked == 0)
	{
		$userId = $_SESSION["id"];
		$noParticipationPeriod = constant('noParticipationPeriod0');
		$limitedTime = constant('limited_time0');
		$amountOfQuestions = constant('amount_of_questions0');
		$amountParticipations = constant('amount_participations0');
		$quizPassed = constant('quiz_passed0');
		$randomQuestions = constant('random_questions0');
		$randomAnswers = constant('random_answers0');
		$singleChoiceMult = constant('singlechoice_multiplier0');
		$public = constant('public0');
		$resultVisiblePoints = constant('result_visible_points0');
		$resultVisible = constant('result_visible0');
		$showTaskPaper = constant('showTaskPaper0');
		$stmt = $dbh->prepare("insert into priority_settings (priority_id, user_id, noParticipationPeriod, limited_time, amount_of_questions, amount_participations,
				quiz_passed, random_questions, random_answers, singlechoice_multiplier, public, result_visible_points, result_visible, showTaskPaper)
				values ($resultChecked, $userId, $noParticipationPeriod, $limitedTime, $amountOfQuestions, $amountParticipations, $quizPassed,
				$randomQuestions, $randomAnswers, $singleChoiceMult, $public, $resultVisiblePoints, $resultVisible, $showTaskPaper)");
		$stmt->execute();
	} else if($resultChecked == 1)
	{
		$userId = $_SESSION["id"];
		$noParticipationPeriod = constant('noParticipationPeriod1');
		$limitedTime = constant('limited_time1');
		$amountOfQuestions = constant('amount_of_questions1');
		$amountParticipations = constant('amount_participations1');
		$quizPassed = constant('quiz_passed1');
		$randomQuestions = constant('random_questions1');
		$randomAnswers = constant('random_answers1');
		$singleChoiceMult = constant('singlechoice_multiplier1');
		$public = constant('public1');
		$resultVisiblePoints = constant('result_visible_points1');
		$resultVisible = constant('result_visible1');
		$showTaskPaper = constant('showTaskPaper1');
		$stmt = $dbh->prepare("insert into priority_settings (priority_id, user_id, noParticipationPeriod, limited_time, amount_of_questions, amount_participations,
				quiz_passed, random_questions, random_answers, singlechoice_multiplier, public, result_visible_points, result_visible, showTaskPaper)
				values ($resultChecked, $userId, $noParticipationPeriod, $limitedTime, $amountOfQuestions, $amountParticipations, $quizPassed,
				$randomQuestions, $randomAnswers, $singleChoiceMult, $public, $resultVisiblePoints, $resultVisible, $showTaskPaper)");
		$stmt->execute();
	} else if($resultChecked == 2)
	{
		$userId = $_SESSION["id"];
		$noParticipationPeriod = constant('noParticipationPeriod2');
		$amountOfQuestions = constant('amount_of_questions2');
		$amountParticipations = constant('amount_participations2');
		$randomQuestions = constant('random_questions2');
		$randomAnswers = constant('random_answers2');
		$singleChoiceMult = constant('singlechoice_multiplier2');
		$public = constant('public2');
		$resultVisiblePoints = constant('result_visible_points2');
		$resultVisible = constant('result_visible2');
		$showTaskPaper = constant('showTaskPaper2');
		$stmt = $dbh->prepare("insert into priority_settings (priority_id, user_id, noParticipationPeriod, amount_of_questions, amount_participations,
				random_questions, random_answers, singlechoice_multiplier, public, result_visible_points, result_visible, showTaskPaper)
				values ($resultChecked, $userId, $noParticipationPeriod, $amountOfQuestions, $amountParticipations, $randomQuestions, $randomAnswers,
				$singleChoiceMult, $public, $resultVisiblePoints, $resultVisible, $showTaskPaper)");
		$stmt->execute();
	}
	
	$stmt = $dbh->prepare("update execution set priority_id = :prioId where id = :execId");
	$stmt->bindParam(":prioId", $resultChecked);
	$stmt->bindParam(":execId", $execId);
	
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	return $response_array;
}

function updateExecutionNoPartPeriod($noPartPeriod, $execId, $dbh)
{
	$response_array["status"] = "OK";
	
	$stmt = $dbh->prepare("update execution set noParticipationPeriod = :noPartPeriod where id = :execId");
	$stmt->bindParam(":noPartPeriod", $noPartPeriod);
	$stmt->bindParam(":execId", $execId);
	
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	return $response_array;
}


function updateExecutionStartDate($startDate, $startTime, $execId, $dbh)
{
	$time = unixTime($startDate, $startTime);
	$response_array["status"] = "OK";
	
	$stmt = $dbh->prepare("update execution set starttime = :starttime where id = :execId");
	$stmt->bindParam(":starttime", $time);
	$stmt->bindParam(":execId", $execId);
	
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	return $response_array;
}

function updateExecutionStartTime($startTime, $startDate, $execId, $dbh)
{
	$time = unixTime($startDate, $startTime);
	$response_array["status"] = "OK";
	
	$stmt = $dbh->prepare("update execution set starttime = :starttime where id = :execId");
	$stmt->bindParam(":starttime", $time);
	$stmt->bindParam(":execId", $execId);
	
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	return $response_array;
}

function updateExecutionEndDate($endDate, $endTime, $execId, $dbh)
{
	$time = unixTime($endDate, $endTime);
	$response_array["status"] = "OK";
	
	$stmt = $dbh->prepare("update execution set endtime = :endtime where id = :execId");
	$stmt->bindParam(":endtime", $time);
	$stmt->bindParam(":execId", $execId);
	
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	return $response_array;
}

function updateExecutionEndTime($endTime, $endDate, $execId, $dbh)
{
	$time = unixTime($endDate, $endTime);
	$response_array["status"] = "OK";
	
	$stmt = $dbh->prepare("update execution set endtime = :endtime where id = :execId");
	$stmt->bindParam(":endtime", $time);
	$stmt->bindParam(":execId", $execId);
	
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	return $response_array;
}

function unixTime($date, $time)
{
	$hour = substr($time, 0, 2);
	$minute = substr($time, 3, 5);
	$second = 0;
	$month = substr($date, 3, 5);
	$day = substr($date, 0, 2);
	$year = substr($date, 6);
	$time = mktime($hour, $minute, $second, $month, $day, $year);
	return $time;
}


?>