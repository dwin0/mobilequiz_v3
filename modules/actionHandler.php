<?php
	session_start();
	
	include_once 'action_topic.php';
	include_once 'action_question.php';
	include_once 'action_quiz.php';
	include_once 'action_quiz_groups.php';
	include_once 'action_poll.php';
	include_once 'action_user.php';
	include_once 'action_execution.php';
	
	include_once '../config/config.php';
	include_once "../modules/extraFunctions.php";

	$action = -1;
	if(isset($_GET["action"]))
	{
		$action = $_GET["action"];
	}
	
	switch($action) {
		case "insertTopic":
			insertTopic();
			break;
		case "delTopic":
			deleteTopic();
			break;
		case "delQuestion":
			deleteQuestion();
			break;
		case "addQuestionToQuiz":
			addQuestionToQuiz();
			break;
		case "updateQuestion":
			updateQuestion();
			break;
		case "updateQuiz":
			updateQuiz();
			break;
		case "delQuestionFromQuiz":
			deleteQuestionFromQuiz();
			break;
		case "delQuiz":
			deleteQuiz();
			break;
		case "delExec":
			deleteExecution();
		case "addAssignation":
			addAssignation();
			break;
		case "delAssignation":
			deleteAssignation();
			break;
		case "uploadExcel":
			uploadExcel();
		case "moveQuestion":
			moveQuestion();
			break;
		case "delGroup":
			deleteGroup();
			break;
		case "addGroup":
			addGroup();
			break;
		case "delUserFromGroup":
			deleteUserFromGroup();
			break;
		case "addUserToGroup":
			addUserToGroup();
			break;
		case "createPoll":
			createPoll();
			break;
		case "getPollVotes":
			getPollVotes();
			break;
		case "switchPollState":
			switchPollState();
			break;
		case "sendVote":
			sendVote();
			break;
		case "changeActive":
			changeActiveStateUser();
			break;
		case "getCorrectAnswers":
			getCorrectPollAnswers();
			break;
		case "changeAssignedGroups":
			changeAssignedGroups();
			break;
		case "revealUserName":
			revealUserName();
			break;
		case "queryAnswers":
			queryAnswers();
			break;
		case "updateExecution":
			updateExecution();
		default:
			header("Location: ?p=quiz&code=-1&info=ppp");
	}
	
?>