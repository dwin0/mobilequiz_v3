<?php

function insertTopic()
{
	global $dbh;
	
	if(isset($_POST["topicName"]) && strlen($_POST["topicName"]) > 1 && isset($_POST["submit"]))
	{
		$stmt = $dbh->prepare("insert into subjects (name) values (:name)");
		$stmt->bindParam(":name", $_POST["topicName"]);
		if($stmt->execute())
		{
			$lastInsertedId = $dbh->lastInsertId();
			
			//create interest-group
			do{
				$randomKey = substr(md5(uniqid(rand(), true)), 1, 6);
				$stmt = $dbh->prepare("select token from `group` where token = :token");
				$stmt->bindParam(":token", $randomkey);
				$stmt->execute();
			} while($stmt->rowCount() > 0);
				
			$groupName = "interest_group_" . $_POST["topicName"];
			$stmt = $dbh->prepare("insert into `group` (name, owner_id, token, subject_id) values (:groupName, :ownerId, :token, :subjectId)");
			$stmt->bindParam(":groupName", $groupName);
			$stmt->bindParam(":ownerId", $_SESSION["id"]);
			$stmt->bindParam(":token", $randomKey);
			$stmt->bindParam(":subjectId", $lastInsertedId);
			if(!$stmt->execute())
			{
				header("Location: ?p=topics&code=-1&info=zzz");
			}
			
			header("Location: ?p=topics&code=1");
		} else {
			header("Location: ?p=topics&code=-1&info=zzz");
		}
	}
}

function deleteTopic()
{
	global $dbh;
	
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
		
		$stmt = $dbh->prepare("select id from group where subject_id = :subjectId");
		$stmt->bindParam(":subjectId", $_GET["topicId"]);
		$stmt->execute();
		$fetchGroupId = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt = $dbh->prepare("delete from user_group where group_id = :groupId");
		$stmt->bindParam(":groupId", $fetchGroupId["id"]);
		$stmt->execute();
		
		$stmt = $dbh->prepare("delete from group where id = :groupId");
		$stmt->bindParam(":groupId", $fetchGroupId["id"]);
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
}

?>