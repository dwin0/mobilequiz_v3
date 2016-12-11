<?php

	function getPoints($dbh, $quizId, $sessionId, $floatingPoint)
	{
		$totalPoints = 0;
		$userScore = 0;
		$stmt = $dbh->prepare("select question.id, question.type_id, execution.singlechoice_multiplier 
				from question inner join qunaire_qu on qunaire_qu.question_id = question.id 
				inner join qunaire_exec on qunaire_exec.questionnaire_id = " . $quizId . " inner join execution on qunaire_exec.execution_id = execution.id 
				where qunaire_qu.questionnaire_id = " . $quizId);
		$stmt->execute();
		$questionFetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
		for($i = 0; $i < count($questionFetch); $i++)
		{
			$stmt = $dbh->prepare("select answer_question.question_id, answer_question.answer_id, answer_question.is_correct, 
					(select selected from an_qu_user where answer_question.answer_id = an_qu_user.answer_id and session_id = :session_id) 
					as selected from answer_question where answer_question.question_id = :question_id");
			$stmt->bindParam(":session_id", $sessionId);
			$stmt->bindParam(":question_id", $questionFetch[$i]["id"]);
			$stmt->execute();
			
			$answerFetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$calc = array();
			if($questionFetch[$i]["type_id"] == 1) //0 und 1 werte betrachten (Singlechoice)
			{
				$calc = calcSinglechoice($answerFetch, $questionFetch[$i]["singlechoice_multiplier"]);
				
			} else if($questionFetch[$i]["type_id"] == 2) //0, 1 und -1 werte betrachten (multiplechoice)
			{
				$calc = calcMultipleChoice($answerFetch);
			}
			$userScore += $calc[0];
			$totalPoints += $calc[1];
		}
		$percent = ($userScore*100)/$totalPoints;
		return [$userScore, $totalPoints, number_format($percent, $floatingPoint)];
	}

	function calcSinglechoice($ar, $mult)
	{
		$score = 0;
		$total = 0;
		for($i = 0; $i < count($ar); $i++)
		{
			if($ar[$i]["is_correct"] == 1)
			{
				$total += 1*$mult;
				if($ar[$i]["selected"] == 1)
					$score += 1*$mult;
			}
			if($ar[$i]["selected"] == 1 && $ar[$i]["is_correct"] == 0)
				$score -= 1*$mult;
		}
		return [$score, $total];
	}
	
	function calcMultipleChoice($ar)
	{
		$score = 0;
		$total = 0;
		for($i = 0; $i < count($ar); $i++)
		{
			$total++;
			if($ar[$i]["is_correct"] == $ar[$i]["selected"])
			{
				$score++;
			} else if($ar[$i]["selected"] != 0)
			{
				$score--;
			}
		}
		return [$score, $total];
	}
	
	function amIAssignedToThisQuiz($dbh, $quizId)
	{
		$stmt = $dbh->prepare("select * from qunaire_assigned_to where questionnaire_id = :qId and user_id = :uId");
		$stmt->bindParam(":qId", $quizId);
		$stmt->bindParam(":uId", $_SESSION["id"]);
		$stmt->execute();
		if($stmt->rowCount() >= 1)
			return true;
		else 
			return false;
	}
	
	function doThisQuizHaveAGroupRestrictionAndAmIInThisGroup($dbh, $quizId)
	{
		// ist einer Gruppe zugewiesen:
		$stmt = $dbh->prepare("select user_id from user_group inner join qunaire_exec on qunaire_exec.questionnaire_id = " . $quizId .
					" inner join execution on qunaire_exec.execution_id = execution.id inner join group_exec on execution.id = group_exec.execution_id 
					 where questionnaire_id = " . $quizId . "AND user_group.group_id = group_exec.group_id");
		$stmt->execute();
		$groupExecFetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$rowCountGroupExecFetch = $stmt->rowCount();
		
		// ist separat dem Quiz zugewiesen:
		$stmt = $dbh->prepare("select user_id from user_exec inner join qunaire_exec on qunaire_exec.questionnaire_id = " . $quizId .
							" inner join execution on qunaire_exec.execution_id = execution.id where questionnaire_id = " .$quizId);
		$stmt->execute();
		$userExecFetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$rowCountUserExecFetch = $stmt->rowCount();
		
		if($rowCountGroupExecFetch > 0 || $rowCountUserExecFetch > 0)
		{
			for($i = 0; $i < count($groupExecFetch); $i++)
			{
				if($groupExecFetch[$i]["user_id"] == $_SESSION["id"])
				{
					return true;
				}
			}
			
			for($j = 0; $j < count($userExecFetch); $j++)
			{
				if($userExecFetch[$j]["user_id"] == $_SESSION["id"])
				{
					return true;
				}
			}
			
			return false;
		}
		else
			return true;
	}
	
	function addEvent($dbh, $type, $message)
	{
		$stmt = $dbh->prepare("insert into events (event_date, event_type, event) values (:date, :type, :msg)");
		$stmt->bindParam(":date", time());
		$stmt->bindParam(":type", $type);
		$stmt->bindParam(":msg", $message);
		return $stmt->execute();
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