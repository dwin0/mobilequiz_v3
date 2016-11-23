<?php
session_start();
if($_SESSION["role"]["user"] != 1)
{
	header("Location: index.php?p=home&code=-20");
	exit;
}

//is the user participating (Session vars)
if(!isset($_SESSION["quizSession"]) || !isset($_SESSION["idSession"]) || $_SESSION["quizSession"] < 0) {
	header("Location: index.php?p=quiz&code=-20");
	exit;
}

//Quiz noch machbar? (Zeit)
if(isset($_GET["info"]) && $_GET["info"] == "fromStart")
{
	$_SESSION["questionNumber"] = 0;
}

$stmt = $dbh->prepare("select question_id from an_qu_user where session_id = :sessionId and question_order = :question_order");
$stmt->bindParam(":sessionId", $_SESSION["idSession"]);
$stmt->bindParam(":question_order", $_SESSION["questionNumber"]);
$stmt->execute();
$fetchSessionForQuestionTest = $stmt->fetch(PDO::FETCH_ASSOC);

$isNew = true;
if($stmt->rowCount() > 0)
{
	$isNew = false;
}


if($isNew)
{
	$stmt = $dbh->prepare("select question.id, question.text, question.type_id, question.picture_link, qunaire_qu.order, questionnaire.random_questions, questionnaire.random_answers
			from question 
				inner join qunaire_qu on qunaire_qu.question_id = question.id 
				inner join questionnaire on questionnaire.id = qunaire_qu.questionnaire_id 
				inner join user_qunaire_session on user_qunaire_session.questionnaire_id = questionnaire.id 
			where user_qunaire_session.questionnaire_id = :quizId 
				and user_qunaire_session.id = :sessionId 
				and question.id not in (select question_id from an_qu_user where session_id = :sessionId) 
			group by question.id");
	$stmt->bindParam(":quizId", $_SESSION["quizSession"]);
	$stmt->bindParam(":sessionId", $_SESSION["idSession"]);
	
	if($stmt->execute())
	{
		if($stmt->rowCount() == 0)
		{
			header("Location: ?p=participationConfirmEndQuiz");
			exit;
		} 
	} else {
		header("Location: index.php?p=quiz&code=-21");
		exit;
	}
	
	$fetchQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if($fetchQuestions[0]["random_questions"] == 1)
		$choosedQuestion = $fetchQuestions[mt_rand(0, count($fetchQuestions) - 1)];
	else 
	{
		$choosedQuestion = $fetchQuestions[0];
		for($i = 1; $i < count($fetchQuestions); $i++)
		{
			if($fetchQuestions[$i]["order"] < $choosedQuestion["order"])
				$choosedQuestion = $fetchQuestions[$i];
		}
	}
} else {
	if(isset($_GET["info"]) && $_GET["info"] == "unanswered")
	{
		$stmt = $dbh->prepare("select question.id, question.text, question.type_id, question.picture_link, selected, question_order from (select question.id, question.text, question.type_id, question.picture_link, selected, question_order from user_qunaire_session left outer join an_qu_user on user_qunaire_session.id = an_qu_user.session_id inner join question on an_qu_user.question_id = question.id where user_qunaire_session.id = :sessionId)question where selected is null or (type_id = 2 and selected = 0) group by id");
		$stmt->bindParam(":sessionId", $_SESSION["idSession"]);
		$stmt->execute();
		if($stmt->rowCount() == 0)
		{
			header("Location: ?p=participationConfirmEndQuiz");
			exit;
		}
		$fetchQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		//Used to go through all unanswered questions from beginning to end and after the last
		//go to checkoutsite
		//if i don't do this i will end in a endless loop until all questions answered
		//but there can be user who dont want to answer the question so i need a counter
		//to detect the last unanswered question
		if(!isset($_SESSION["unansweredNumber"]))
			$_SESSION["unansweredNumber"] = -1;
		
		if(isset($_SESSION["unansweredNumber"]))
		{
			$redirect = true;
			for($i = 0; $i < count($fetchQuestions); $i++)
			{
				if($fetchQuestions[$i]["question_order"] > $_SESSION["unansweredNumber"])
				{
					$redirect = false;
					$choosedQuestion = $fetchQuestions[$i];
					$_SESSION["unansweredNumber"] = $choosedQuestion["question_order"];
					break;
				}
			}
		}
		
		if($redirect)
		{
			header("Location: ?p=participationConfirmEndQuiz");
			exit;
		}
		
		//$choosedQuestion = $fetchQuestions[mt_rand(0, count($fetchQuestions) - 1)];
		$_SESSION["questionNumber"] = $choosedQuestion["question_order"];
		//questionnumber?
	} else {
		$stmt = $dbh->prepare("select question.id, question.text, question.type_id, question.picture_link from question where id = :qId");
		$stmt->bindParam(":qId", $fetchSessionForQuestionTest["question_id"]);
		$stmt->execute();
		$fetchQuestions = $stmt->fetch(PDO::FETCH_ASSOC);
		$choosedQuestion = $fetchQuestions;
	}
}

?>
<script type="text/javascript">

	function insertNextButtonWaitTime(time)
	{
		console.log("insertNextButton function " + time);
		$.ajax({
			url: 'Pindex.php?p=participation',
			type: 'get',
			data: 'action=insertNextButtonWaitTime&time=' + time,
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
		$('#buttons').css('display', 'inline');

		insertNextButtonWaitTime(Date.now());
		
		$('#answerForm').submit(function() {
			$('#startTimeNextButton').val(Date.now()); //millisecs
		});
	});
</script>

<noscript><p style="color: red;"><b>Du hast Javascript deaktiviert.</b> Um die Lernkontrolle durchf&uuml;hren zu k&ouml;nnen muss Javascript aktiviert sein.</p></noscript>
<form id="answerForm" name="answerForm" data-ajax="false" action="?p=participation" method="POST">
	<div class="question"><?php echo $choosedQuestion["text"];?></div>
	<input type="hidden" name="questionId" value="<?php echo $choosedQuestion["id"];?>">
	<input type="hidden" name="action" value="saveAndNextQuestion">
	
	<img id="questionImage" style="width:40vw; max-width:400px; display:block; margin: 0 auto; padding-top:5%" src="<?php echo $choosedQuestion["picture_link"]?>" />
		<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">
		    <div class="pswp__bg"></div>
		    <div class="pswp__scroll-wrap">
		
		        <div class="pswp__container">
		            <!-- don't modify these 3 pswp__item elements, data is added later on -->
		            <div class="pswp__item"></div>
		            <div class="pswp__item"></div>
		            <div class="pswp__item"></div>
		        </div>
		
		        <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
		        <div class="pswp__ui pswp__ui--hidden">
		
		            <div class="pswp__top-bar">
		            
		            <div class="pswp__counter"></div>
		
		                <button id="closePhoto">Schliessen</button>
		                
		                <div class="pswp__preloader">
		                    <div class="pswp__preloader__icn">
		                      <div class="pswp__preloader__cut">
		                        <div class="pswp__preloader__donut"></div>
		                      </div>
		                    </div>
		                </div>
		            </div>
		
		            <div class="pswp__caption">
		                <div class="pswp__caption__center"></div>
		            </div>
		
		          </div>
			</div>
		</div>
	
	
	
	<div class="answers">
		<?php 
		$dbSelected="";
		$dbJoin="";
		$dbWhere="";
		
		if(!$isNew)
		{
			$dbSelected=" ,selected";
			$dbJoin="inner join an_qu_user on an_qu_user.answer_id = answer.id ";
			$dbWhere="and an_qu_user.session_id = :session_id ";
		}
		
		$stmt = $dbh->prepare("select answer.id, answer.text" . $dbSelected . " from answer 
				inner join answer_question on answer.id = answer_question.answer_id " . $dbJoin . "
				where answer_question.question_id = :question_id " . $dbWhere . "order by answer_question.order asc");
		$stmt->bindParam(":question_id", $choosedQuestion["id"]);
		if(!$isNew)
		{
			$stmt->bindParam(":session_id", $_SESSION["idSession"]);
		}
		
		if($stmt->execute())
		{
			$fetchAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} else {
			header("Location: index.php?p=quiz&code=-22");
			exit;
		}
		?>
		
		<?php 
		if($choosedQuestion["random_answers"] == 1)
		{
			shuffle($fetchAnswers);
		}
		if($choosedQuestion["type_id"] == 1)
		{
		?>
		<div data-role="fieldcontain">
			<div data-role="controlgroup">
				<?php $i = 0;
				for(; $i < count($fetchAnswers); $i++) {
				?>
					<input type="radio" id="<?php echo "radio_" . $i;?>" name="answer" value="<?php echo $fetchAnswers[$i]["id"];?>" <?php  if(!$isNew) {if($fetchAnswers[$i]["selected"] == 1){echo " checked";}}?>/> 
					<label for="<?php echo "radio_" . $i;?>">
						<div style="font-weight:normal; white-space:normal;"><?php echo $fetchAnswers[$i]["text"];?></div>
					</label> 
				<?php 
				}
				?>
				<input type="radio" id="<?php echo "radio_" . $i;?>" name="answer" value="noAnswer" <?php  if($isNew) {echo " checked";} else {if($fetchAnswers[$i-1]["selected"] == NULL){echo " checked";}}?>/> 
				<label for="<?php echo "radio_" . $i;?>">
					<div style="font-weight:normal"><?php echo $lang["noAnswer"];?></div>
				</label>
			</div>
		</div>
		<?php 
		} else if($choosedQuestion["type_id"] == 2) { 
			$i = 0;
			echo "<ul data-role=\"listview\">";
			for(; $i < count($fetchAnswers); $i++) {
			?>
				<li>
					<div style="display: table;">
						<fieldset style="float: left;" id="<?php echo "radio_" . $i;?>" data-role="controlgroup" data-type="horizontal" data-role="fieldcontain">
							<input type="radio" name="<?php echo "answer_" . $fetchAnswers[$i]["id"];?>" id="<?php echo "radio-view-a" . $fetchAnswers[$i]["id"];?>" value="-1" <?php  if(!$isNew) {if($fetchAnswers[$i]["selected"] == -1){echo " checked";}}?> />
				         	<label for="<?php echo "radio-view-a" . $fetchAnswers[$i]["id"];?>">&#10007;</label>
				         	<input type="radio" name="<?php echo "answer_" . $fetchAnswers[$i]["id"];?>" id="<?php echo "radio-view-b" . $fetchAnswers[$i]["id"];?>" value="0" <?php  if(!$isNew) {if($fetchAnswers[$i]["selected"] == 0){echo " checked";}} else {echo 'checked';}?> />
				         	<label for="<?php echo "radio-view-b" . $fetchAnswers[$i]["id"];?>">-</label>
				         	<input type="radio" name="<?php echo "answer_" . $fetchAnswers[$i]["id"];?>" id="<?php echo "radio-view-c" . $fetchAnswers[$i]["id"];?>" value="1" <?php  if(!$isNew) {if($fetchAnswers[$i]["selected"] == 1){echo " checked";}}?> />
				         	<label for="<?php echo "radio-view-c" . $fetchAnswers[$i]["id"];?>">&#10003;</label>
						</fieldset>
						<div style="font-weight: normal; display: table-cell; vertical-align: middle; padding-left: 20px; white-space : normal;"><?php echo $fetchAnswers[$i]["text"];?></div>
					</div>
				</li>
		<?php 
			}
			echo "</ul>";
		}
		?>
	</div>
	<br />
	<br />
	<div id="buttons" data-role="controlgroup" data-type="horizontal">
		<input type="hidden" name="unanswered" value="<?php echo (isset($_GET["info"]) && $_GET["info"] == "unanswered") ? '1' : '0';?>">
		<input type="hidden" name="generationTime" value="<?php echo time();?>">
		<input id="startTimeNextButton" type="hidden" name="startTimeNextButton" value="-1">
		<?php if($_SESSION["questionNumber"] > 0) {?>
		<input type="submit" id="prevQuestion" name="prevQuestion" value="<?php echo $lang["btnBack"]; ?>" data-icon="arrow-l" data-iconpos="left" />
		<?php }?>
		<input type="submit" id="nextQuestion" name="nextQuestion" value="<?php echo $lang["nextQuestion"]; ?>" data-icon="arrow-r" data-iconpos="right" />
	</div>
</form>
<?php 
//echo "Debug<br />vars: <br />quizSession: " . $_SESSION["quizSession"] . "<br />idSession: " . $_SESSION["idSession"] . "<br />coosedQuestion: " . $choosedQuestion["id"] . "<br />questionAmount: " . count($fetchQuestions) ."<br />questionNumber: " . $_SESSION["questionNumber"] . "<br />UnansweredNumber: " . $_SESSION["unansweredNumber"];
?>


<script type="text/javascript">
var gallery;

var openPhotoSwipe = function() {
    var pswpElement = document.querySelectorAll('.pswp')[0];
    var image = document.getElementById('questionImage');
    
    var items = [
        {
            src: image.src,
            w: image.width * 3,
            h: image.height * 3
        }
    ];

    console.log(image.src);
    
    var options = {
        history: false,
        focus: true,

        showAnimationDuration: 0,
        hideAnimationDuration: 0
    };
    
    gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
    gallery.init();
};

var closePhotoSwipe = function(event) {
	gallery.close();
	event.preventDefault();
}

document.getElementById('questionImage').onclick = openPhotoSwipe;
document.getElementById('questionImage').ontouchstart = openPhotoSwipe;

document.getElementById('closePhoto').onclick = closePhotoSwipe;
document.getElementById('closePhoto').ontouchstart = closePhotoSwipe;

</script>
