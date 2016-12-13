<?php
	include_once "modules/extraFunctions.php";
	
	if(!isset($_GET["execId"]))
	{
		header("Location: ?p=quiz&code=-15");
		exit;
	}
	
	if($_SESSION["role"]["user"] == 1)
	{
		$stmt = $dbh->prepare("select questionnaire.id as qId, questionnaire.name, description, noParticipationPeriod, starttime, endtime, questionnaire.last_modified, exec_token,
									firstname, lastname, email, owner_id from questionnaire inner join user on user.id = questionnaire.owner_id inner join user_data on user_data.user_id = user.id
									inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id inner join execution on qunaire_exec.execution_id = execution.id where execution.id = :execId");
		$stmt->bindParam(":execId", $_GET["execId"]);
		if(!$stmt->execute())
		{
			header("Location: ?p=quiz&code=-25");
			exit;
		}
		if($stmt->rowCount() != 1)
		{
			header("Location: ?p=quiz&code=-15");
			exit;
		}
		$fetchQuiz = $stmt->fetch(PDO::FETCH_ASSOC);
		if($fetchQuiz["owner_id"] != $_SESSION["id"] && $_SESSION['role']['admin'] != 1 && $_SESSION["role"]["creator"] != 1 && !amIAssignedToThisQuiz($dbh, $fetchQuiz["qId"]))
		{
			header("Location: ?p=quiz&code=-1");
			exit;
		}
	}
	else
	{
		header("Location: ?p=home&code=-20");
		exit;
	}
	
	$execId = $_GET["execId"];
?>