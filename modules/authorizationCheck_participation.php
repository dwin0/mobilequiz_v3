<?php
	include_once 'modules/extraFunctions.php';
	
	function checkAuthorization($quizId, $fetchQuiz, $participationOutro)
	{
		global $dbh;
		
		//Quiz enabled? (time, special access)
		//maybe assigned Quiz?
		if(! $_SESSION['role']['admin'])
		{
			if(! $fetchQuiz["public"])
			{
				if(!amIQuizCreator($quizId)) {
					header("Location: index.php?p=quiz&code=-25");
					exit;
				}
			}
			if(!$participationOutro) 
			{
				if(($fetchQuiz["starttime"] > time() || $fetchQuiz["endtime"] < time()) && $fetchQuiz["noParticipationPeriod"] == 0)
				{
					header("Location: index.php?p=quiz&code=-26");
					exit;
				}
			}
			if(!doThisQuizHaveAGroupRestrictionAndAmIInThisGroup($dbh, $id))
			{
				header("Location: index.php?p=quiz&code=-38");
				exit;
			}
		}
	}
	
	
	function amIQuizCreator($quizId)
	{
		global $dbh;
	
		if($_SESSION['role']['creator'])
		{
			$stmt = $dbh->prepare("select owner_id from questionnaire where id = :id");
			$stmt->bindParam(":id", $quizId);
			$stmt->execute();
			$fetchOwner = $stmt->fetch(PDO::FETCH_ASSOC);
	
			return $_SESSION["id"] == $fetchOwner['owner_id'];
		} else {
			return false;
		}
	}
	
?>