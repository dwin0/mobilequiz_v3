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