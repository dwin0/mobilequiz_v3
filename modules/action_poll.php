<?php

function createPoll($dbh)
{
	if($_SESSION['role']['creator'])
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
}


function getPollVotes($dbh)
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
}


function switchPollState($dbh)
{
	if(isset($_GET["newActive"]) && $_SESSION["role"]["creator"])
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
}


function sendVote($dbh)
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
}


function getCorrectPollAnswers($dbh)
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

?>