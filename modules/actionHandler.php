<?php
	session_start();
	
	include_once 'action_topic.php';
	include_once 'action_question.php';
	include_once 'action_quiz.php';
	include_once 'action_quiz_groups.php';
	include_once 'action_poll.php';
	include_once 'action_user.php';
	
	include_once '../config/config.php';
	include_once "../modules/extraFunctions.php";

	$action = -1;
	if(isset($_GET["action"]))
	{
		$action = $_GET["action"];
	}
	
	switch($action) {
		case "insertTopic":
			insertTopic($dbh);
			break;
		case "delTopic":
			deleteTopic($dbh);
			break;
		case "insertQuestion":
			insertQuestion($dbh);
			break;
		case "delQuestion":
			deleteQuestion($dbh);
			break;
		case "delPicture":
			deletePicture($dbh);
			break;
		case "insertQuiz":
			insertQuiz($dbh);
			break;
		case "addQuestions":
			addQuestions($dbh);
			break;
		case "delQuestionFromQuiz":
			deleteQuestionFromQuiz($dbh);
			break;
		case "delQuiz":
			deleteQuiz($dbh);
			break;
		case "addAssignation":
			addAssignation($dbh);
			break;
		case "delAssignation":
			deleteAssignation($dbh);
			break;
		case "moveQuestion":
			moveQuestion($dbh);
			break;
		case "delGroup":
			deleteGroup($dbh);
			break;
		case "addGroup":
			addGroup($dbh);
			break;
		case "delUserFromGroup":
			deleteUserFromGroup($dbh);
			break;
		case "addUserToGroup":
			addUserToGroup($dbh);
			break;
		case "createPoll":
			createPoll($dbh);
			break;
		case "getPollVotes":
			getPollVotes($dbh);
			break;
		case "switchPollState":
			switchPollState($dbh);
			break;
		case "sendVote":
			sendVote($dbh);
			break;
		case "changeActive":
			changeActiveStateUser($dbh);
			break;
		case "getCorrectAnswers":
			getCorrectPollAnswers($dbh);
			break;
		case "changeAssignedGroups":
			changeAssignedGroups($dbh);
			break;
		case "revealUserName":
			revealUserName($dbh);
			break;
		case "queryAnswers":
			queryAnswers($dbh);
			break;
		default:
			header("Location: ?p=quiz&code=-1&info=ppp");
	}
	
?>