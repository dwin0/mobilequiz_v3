<?php 
	$execId = 0;
	if(isset($_GET["execId"]))
	{
		$execId = $_GET["execId"];
	} else {
		if(isset($_SESSION["quizSession"]) && isset($_SESSION["idSession"]))
			$execId = $_SESSION["quizSession"];
		else {
			header("Location: index.php?p=quiz&code=-2");
			exit;
		}
	}
	
	$stmt = $dbh->prepare("select questionnaire.name, limited_time from questionnaire inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id 
						inner join execution on qunaire_exec.execution_id = execution.id where execution.id = :id");
	$stmt->bindParam(":id", $execId);
	$stmt->execute();
	if($stmt->rowCount() <= 0)
	{
		header("Location: index.php?p=quiz&code=-15");
		exit;
	}
	$fetchQunaire = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$stmt = $dbh->prepare("select starttime from user_exec_session where id = :session_id");
	$stmt->bindParam(":session_id", $_SESSION["idSession"]);
	$stmt->execute();
	$fetchSession = $stmt->fetch(PDO::FETCH_ASSOC);
	
	$limited_time = $lang["participationTimeLimit"];
	if($fetchQunaire["limited_time"] != 0)
	{
		$additionalWaitTime = 0;
		if($_SESSION["additionalTime"]/1000 < 1)
			$additionalWaitTime = 1;
		else if($_SESSION["additionalTime"] < 0)
			$additionalWaitTime = 0;
		else 
			$additionalWaitTime = $_SESSION["additionalTime"]/1000;
		
		$limited_time = ($fetchSession["starttime"] + $fetchQunaire["limited_time"] - time() + $additionalWaitTime);
	}
?>
<script type="text/javascript">
	
	$(function() {

		<?php if($fetchQunaire["limited_time"] != 0 && isset($_SESSION["quizSession"]) && $_SESSION["quizSession"] >= 0) {?>
			var timer = <?php echo intval($limited_time);?>;
			var timerInterval = setInterval(countdown, 1000);
			var textRed = false;
			function countdown()
			{
				var min = Math.floor(timer/60);
				var sec = Math.floor(timer%60);
				if(min < 10)
					min = "0" + min;
				if(sec < 10)
					sec = "0" + sec;
				$('#time').html(min + ":" + sec);
				timer--;
				//console.log(timer);
				if(timer < 0)
				{
					clearInterval(timerInterval);
					window.location='?p=participation&action=endQuiz&state=timeExceeded';
				}
				if(timer < 30 && !textRed)
				{
					textRed = true;
					$('#time').css('color', '#D41C1C');
					animateTime();
				}
			}

			function animateTime()
			{
				$('#time').animate({
			        fontSize: $('#time').css('fontSize') == '22px' ? '20px' : '22px'
			    }, 500, animateTime);
			}		
		<?php }?>
	});
	
</script>
<div data-role="header" data-theme="a" data-position="fixed">
	<div style="width:100%">
		<?php if(isset($_SESSION["quizSession"]) && $_SESSION["quizSession"] >= 0) {
			$abortLink = "window.location='?p=participation&action=endQuiz&state=abort';"; 
		} else {
			$abortLink = "window.location='index.php?p=quiz';";
		}?>
		<div class="right">
			<input type="button" id="abortQuiz" name="abortQuiz" value="<?php echo $lang["buttonCancel"];?>" data-icon="arrow-l" data-iconpos="left" onclick="<?php echo $abortLink;?>"/>
		</div>
		<?php 
			if(isset($_SESSION["quizSession"]) && $_SESSION["quizSession"] >= 0)
			{
				$stmt = $dbh->prepare("select question.id from questionnaire inner join qunaire_qu on qunaire_qu.questionnaire_id = questionnaire.id inner join question on 
									question.id = qunaire_qu.question_id inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id where execution_id = :execId");
				$stmt->bindParam(":execId", $execId);
				$stmt->execute();
				$remainingQuestions = $stmt->rowCount();
				
				echo '<div class="left">';
				if($_SESSION["questionNumber"] +1 <= $remainingQuestions) {
					echo ($_SESSION["questionNumber"] +1) . ' / ' . $remainingQuestions ;
				}
				
				echo '</div>';
				echo '<div class="center">';
				$showLimitedTime = $limited_time;
				if($fetchQunaire["limited_time"] != 0)
					$showLimitedTime = gmdate("i:s", $limited_time);
				echo '<h3 id="time">' . $showLimitedTime . '</h3>';
				echo '</div>';
			} else {
				
				echo '<div class="center">';
				echo "<h3>" . $lang["quiz"] . " &laquo" . htmlspecialchars($fetchQunaire["name"]) . "&raquo</h3>";
				echo '</div>';
			}
		?>
	</div>
	<div style="clear: both"></div>
</div>
