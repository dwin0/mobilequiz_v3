<?php
session_start();
if($_SESSION["role"]["user"] != 1)
{
	header("Location: index.php?p=home&code=-20");
	exit;
}

include "modules/extraFunctions.php";

//Query Quiz
if(!isset($_SESSION["quizSession"]) || !isset($_SESSION["idSession"]) || $_SESSION["quizSession"] < 0) {
	header("Location: index.php?p=quiz&code=-20");
	exit;
}

$_SESSION["unansweredNumber"] = -1;

$stmt = $dbh->prepare("select question_order from an_qu_user where session_id = :session_id group by question_id order by question_order desc limit 1");
$stmt->bindParam(":session_id", $_SESSION["idSession"]);
$stmt->execute();
$fetchQuestionOrder = $stmt->fetch(PDO::FETCH_ASSOC);
$customQOrder = $_SESSION["questionNumber"];
$_SESSION["questionNumber"] = $fetchQuestionOrder["question_order"];
?>

<script type="text/javascript">

	function insertNextButtonWaitTime(time)
	{
		console.log("insertNextButton function " + time);
		$.ajax({
			url: 'Pindex.php?p=participation',
			type: 'get',
			data: 'action=insertNextButtonWaitTime&customQOrder=<?php echo $customQOrder;?>&time=' + time,
			dataType: 'json',
			success: function(output) {
				if(output[0] == 'ok')
				{
					console.log("inserted time: " + JSON.stringify(output));
				} else {
					console.log("error 1");
				}
			}, error: function()
			{
				console.log("error 2");
			}
		});
	}

	$(function () {

		insertNextButtonWaitTime(Date.now());
	});
</script>

<div class="question" style="margin: 20px 0px 70px;"><?php echo $lang["participateFinishHeading"];?></div>
<div data-role="controlgroup" data-type="vertical" style="width: 100%; margin: auto;">
	<?php if($_SESSION["questionNumber"] > 0) {?>
	<input type="button" id="prevQuestion" name="prevQuestion" value="<?php echo $lang["btnBack"]; ?>" data-ajax="false" data-icon="arrow-l" data-iconpos="left" onclick="window.location='?p=participate';" />
	<?php 
		$stmt = $dbh->prepare("select question.id, question.text, question.type_id, selected from (select question.id, question.text, question.type_id, selected from user_qunaire_session left outer join an_qu_user on user_qunaire_session.id = an_qu_user.session_id inner join question on an_qu_user.question_id = question.id where user_qunaire_session.id = :sessionId)question where selected is null or (type_id = 2 and selected = 0) group by id");
		$stmt->bindParam(":sessionId", $_SESSION["idSession"]);
		$stmt->execute();
		if($stmt->rowCount() != 0)
		{
			?>
			<input type="button" id="unansweredQuestion" name="unansweredQuestion" value="<?php echo $lang["btnUnansweredQuestions"] . " (" . $stmt->rowCount() . ")"; ?>" data-ajax="false" data-icon="arrow-l" data-iconpos="left" onclick="window.location='?p=participate&info=unanswered';" />
			<?php 
		}
	}?>
	<input type="button" id="beginFromStart" name="beginFromStart" value="<?php echo $lang["btnParticipationFromStart"]; ?>" data-ajax="false" data-icon="arrow-l" data-iconpos="left" onclick="window.location='?p=participate&info=fromStart';" />
	<input type="button" id="endQuiz" name="endQuiz" value="<?php echo $lang["participateFinish"]; ?>" data-ajax="false" data-icon="arrow-r" data-iconpos="right" onclick="window.location='?p=participation&action=endQuiz&state=correct';" />
</div>
<?php 
//echo "Debug<br />vars: <br />quizSession: " . $_SESSION["quizSession"] . "<br />idSession: " . $_SESSION["idSession"] ."<br />questionNumber: " . $_SESSION["questionNumber"];
?>