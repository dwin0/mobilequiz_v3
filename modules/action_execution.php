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
			$response_array = updateExecutionNoPartPeriod($_POST["noParticipationPeriod"], $_POST["execId"], $_POST["priorityId"], $dbh);
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
		case "timeLimit":
			$response_array = updateExecutionTimeLimit($_POST["timeLimit"], $_POST["execId"], $_POST["priorityId"], $dbh);
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

function updateExecutionPriority($newPriority, $execId, $dbh)
{
	$response_array["status"] = "OK";
	
	$stmt = $dbh->prepare("update execution set priority_id = :prioId where id = :execId");
	$stmt->bindParam(":prioId", $newPriority);
	$stmt->bindParam(":execId", $execId);
	
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	
	$stmt = $dbh->prepare("select * from priority_settings where priority_id = :priorityId and user_id = :userId");
	$stmt->bindParam(":priorityId", $newPriority);
	$stmt->bindParam(":userId", $_SESSION["id"]);
	$stmt->execute();
	
	$userId = $_SESSION["id"];
	
	if($stmt->rowCount() == 0)
	{
		
		if($newPriority == 0)
		{
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
					values ($newPriority, $userId, $noParticipationPeriod, $limitedTime, $amountOfQuestions, $amountParticipations, $quizPassed,
					$randomQuestions, $randomAnswers, $singleChoiceMult, $public, $resultVisiblePoints, $resultVisible, $showTaskPaper)");
			$stmt->execute();
		} else if($newPriority == 1)
		{
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
					values ($newPriority, $userId, $noParticipationPeriod, $limitedTime, $amountOfQuestions, $amountParticipations, $quizPassed,
					$randomQuestions, $randomAnswers, $singleChoiceMult, $public, $resultVisiblePoints, $resultVisible, $showTaskPaper)");
			$stmt->execute();
		} else if($newPriority == 2)
		{
			$noParticipationPeriod = constant('noParticipationPeriod2');
			$limitedTime = null;
			$amountOfQuestions = constant('amount_of_questions2');
			$amountParticipations = constant('amount_participations2');
			$quizPassed = null;
			$randomQuestions = constant('random_questions2');
			$randomAnswers = constant('random_answers2');
			$singleChoiceMult = constant('singlechoice_multiplier2');
			$public = constant('public2');
			$resultVisiblePoints = constant('result_visible_points2');
			$resultVisible = constant('result_visible2');
			$showTaskPaper = constant('showTaskPaper2');
			$stmt = $dbh->prepare("insert into priority_settings (priority_id, user_id, noParticipationPeriod, amount_of_questions, amount_participations,
					random_questions, random_answers, singlechoice_multiplier, public, result_visible_points, result_visible, showTaskPaper)
					values ($newPriority, $userId, $noParticipationPeriod, $amountOfQuestions, $amountParticipations, $randomQuestions, $randomAnswers,
					$singleChoiceMult, $public, $resultVisiblePoints, $resultVisible, $showTaskPaper)");
			$stmt->execute();
		}
	} else 
	{
		$fetchUserPriority = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$noParticipationPeriod = $fetchUserPriority["noParticipationPeriod"];
		$limitedTime = $fetchUserPriority["limited_time"];
		$amountOfQuestions = $fetchUserPriority['amount_of_questions'];
		$amountParticipations = $fetchUserPriority['amount_participations'];
		$quizPassed = $fetchUserPriority['quiz_passed'];
		$randomQuestions = $fetchUserPriority['random_questions'];
		$randomAnswers = $fetchUserPriority['random_answers'];
		$singleChoiceMult = $fetchUserPriority['singlechoice_multiplier'];
		$public = $fetchUserPriority['public'];
		$resultVisiblePoints = $fetchUserPriority['result_visible_points'];
		$resultVisible = $fetchUserPriority['result_visible'];
		$showTaskPaper = $fetchUserPriority['showTaskPaper'];
	}
	
	$response_array["settings"] = array("noParticipationPeriod" => $noParticipationPeriod, "limited_time" => $limitedTime, "amount_of_questions" => $amountOfQuestions,
									"amount_participations" => $amountParticipations, "quiz_passed" => $quizPassed, "random_questions" => $randomQuestions,
									"random_answers" => $randomAnswers, "singlechoice_multiplier" => $singleChoiceMult, "public" => $public, "result_visible_points" => $resultVisiblePoints,
									"result_visible" => $resultVisible, "showTaskPaper" => $showTaskPaper);
	$response_array["functionName"] = "priority";
	
	return $response_array;
}

function updateExecutionNoPartPeriod($noPartPeriod, $execId, $priorityId, $dbh)
{
	$response_array["status"] = "OK";
	
	$stmt = $dbh->prepare("update execution set noParticipationPeriod = :noPartPeriod where id = :execId");
	$stmt->bindParam(":noPartPeriod", $noPartPeriod);
	$stmt->bindParam(":execId", $execId);
	if(!$stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	$stmt = $dbh->prepare("update priority_settings set noParticipationPeriod = :noPartPeriod where priority_id = :priorityId and user_id = :userId");
	$stmt->bindParam(":noPartPeriod", $noPartPeriod);
	$stmt->bindParam(":priorityId", $priorityId);
	$stmt->bindParam(":userId", $_SESSION["id"]);
	if(! $stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	$response_array["noPartPeriodNewValue"] = $noPartPeriod;
	$response_array["functionName"] = "noParticipationPeriod";
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

function updateExecutionTimeLimit($timeLimit, $execId, $priorityId, $dbh)
{
	$response_array["status"] = "OK";
	
	$time = 0;
	if($timeLimit != 0)
	{
		$time = convertMinutsAndSeconds($timeLimit);
	}
	
	$stmt = $dbh->prepare("update execution set limited_time = :timeLimit where id = :execId");
	$stmt->bindParam(":timeLimit", $time);
	$stmt->bindParam(":execId", $execId);
	if(!$stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	$stmt = $dbh->prepare("update priority_settings set limited_time = :timeLimit where priority_id = :priorityId and user_id = :userId");
	$stmt->bindParam(":timeLimit", $time);
	$stmt->bindParam(":priorityId", $priorityId);
	$stmt->bindParam(":userId", $_SESSION["id"]);
	if(!$stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
	}
	
	$response_array["timeLimitNewValue"] = $time;
	$response_array["functionName"] = "timeLimit";
	return $response_array;
}

function convertMinutsAndSeconds($time)
{
	$minute = substr($time, 0, 2);
	$second = substr($time, 3, 5);
	$returnTime = $minute * 60 + $second;
	return $returnTime;
}

function deleteGroupFromExecution()
{
	global $dbh;
	$response_array["status"] = "OK";
	
	//check correct owner
	if($_POST["mode"] == 'edit')
	{
		//return if user is not allowed to delete group from execution
		if($_SESSION["role"]["creator"] != 1)
		{
			$response_array["status"] = "error";
			$response_array["text"] = $lang["execution-authorization-error"];
		}
	}
	
	if(!isset($_POST["execId"]) || !isset($_POST["groupId"]))
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["parameterError"];
	}
	
	if($response_array["status"] == "error")
	{
		echo json_encode($response_array);
		exit;
	}
	
	$stmt = $dbh->prepare("delete from group_exec where group_id = :groupId and execution_id = :execId");
	$stmt->bindParam(":groupId", $_POST["groupId"]);
	$stmt->bindParam(":execId", $_POST["execId"]);
	if(!$stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
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

function addGroupAssignation()
{
	global $dbh;
	
	$response_array["status"] = "OK";
	
	//check correct owner
	if($_POST["mode"] == 'edit')
	{
		//return if user is not allowed to add group to execution
		if($_SESSION["role"]["creator"] != 1)
		{
			$response_array["status"] = "error";
			$response_array["text"] = $lang["execution-authorization-error"];
		}
	}
	
	if(!isset($_POST["execId"]) || !isset($_POST["groupName"]))
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["parameterError"];
	}
	
	if($response_array["status"] == "error")
	{
		echo json_encode($response_array);
		exit;
	}
	
	
	$stmt = $dbh->prepare("select id from `group` where name = :name");
	$stmt->bindParam(":name", $_POST["groupName"]);
	$stmt->execute();
	$groupId = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$stmt = $dbh->prepare("insert into group_exec (group_id, execution_id) values (:groupId, :execId)");
	$stmt->bindParam(":groupId", $groupId["id"]);
	$stmt->bindParam(":execId", $_POST["execId"]);
	if($stmt->execute())
	{
		$response_array["groupId"] = $groupId["id"];
	
	} else {
		$response_array["status"] = ["error"];
		$response_array["text"] = ["Couldn't update database."];
	}
	
	echo json_encode($response_array);
	exit;
	
}

function addUserAssignation()
{
	global $dbh;
	
	$response_array["status"] = "OK";
	
	//check correct owner
	if($_POST["mode"] == 'edit')
	{
		//return if user is not allowed to add group to execution
		if($_SESSION["role"]["creator"] != 1)
		{
			$response_array["status"] = "error";
			$response_array["text"] = $lang["execution-authorization-error"];
		}
	}
	
	if(!isset($_POST["execId"]) || !isset($_POST["userEmail"]))
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["parameterError"];
	}
	
	if($response_array["status"] == "error")
	{
		echo json_encode($response_array);
		exit;
	}
	
	
	$stmt = $dbh->prepare("select id from user where email = :email");
	$stmt->bindParam(":email", $_POST["userEmail"]);
	$stmt->execute();
	$userId = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$stmt = $dbh->prepare("insert into user_exec (user_id, execution_id) values (:userId, :execId)");
	$stmt->bindParam(":userId", $userId["id"]);
	$stmt->bindParam(":execId", $_POST["execId"]);
	if($stmt->execute())
	{
		$response_array["userId"] = $userId["id"];
	
	} else {
		$response_array["status"] = ["error"];
		$response_array["text"] = ["Couldn't update database."];
	}
	
	echo json_encode($response_array);
	exit;
	
}

function deleteUserFromExecution()
{
	global $dbh;
	$response_array["status"] = "OK";

	//check correct owner
	if($_POST["mode"] == 'edit')
	{
		//return if user is not allowed to delete group from execution
		if($_SESSION["role"]["creator"] != 1)
		{
			$response_array["status"] = "error";
			$response_array["text"] = $lang["execution-authorization-error"];
		}
	}

	if(!isset($_POST["execId"]) || !isset($_POST["userId"]))
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["parameterError"];
	}

	if($response_array["status"] == "error")
	{
		echo json_encode($response_array);
		exit;
	}

	$stmt = $dbh->prepare("delete from user_exec where user_id = :userId and execution_id = :execId");
	$stmt->bindParam(":userId", $_POST["userId"]);
	$stmt->bindParam(":execId", $_POST["execId"]);
	if(!$stmt->execute())
	{
		$response_array["status"] = "error";
		$response_array["text"] = $lang["DB-Update-Error"];
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


?>