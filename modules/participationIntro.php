<?php
	setlocale(LC_ALL, 'de_DE', 'deu_deu');

	//Query Quiz
	$execId = -1;
	if(isset($_GET["execId"]))
	{
		$execId = $_GET["execId"];
	} else {
		header("Location: index.php?p=quiz&code=-2");
		exit;
	}
	
	if($_SESSION["role"]["user"] != 1)
	{
		header("Location: index.php?p=home&code=-20&toQuiz=" . $execId);
		exit;
	}
	
	$stmt = $dbh->prepare("select execution.*, questionnaire.description, questionnaire.id as qId, count(qunaire_qu.questionnaire_id) as question_count, user_data.firstname, user_data.lastname, user.email 
			from questionnaire inner join qunaire_qu on qunaire_qu.questionnaire_id = questionnaire.id inner join user on user.id = questionnaire.owner_id 
			inner join user_data on user_data.user_id = user.id inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id 
			inner join execution on qunaire_exec.execution_id = execution.id  where execution.id = :id");
	$stmt->bindParam(":id", $execId);
	if(!$stmt->execute())
	{
		header("Location: index.php?p=quiz&code=-14");
		exit;
	}
	$fetchQunaire = $stmt->fetch(PDO::FETCH_ASSOC);
	
	include_once 'modules/authorizationCheck_participation.php';
	checkAuthorization($fetchQunaire["qId"], $fetchQunaire, false);
	
?>
<script type="text/javascript">

	function startNewQuiz(execId)
	{
		if(document.getElementById('checkReadAll').checked)
			window.location='?p=participation&action=startQuiz&execId=' + execId;
		else
		{
			$('#checkReadAll').tipsy("show");
		}
	}

	$(function() {
		$('#checkReadAll').tipsy({trigger:"manual", gravity:"sw"});

		$('#startButton').css('display', 'inline');
	});

</script>
<div class="logo"></div>
<noscript><p style="color: red;"><b>Du hast Javascript deaktiviert.</b> Um die Lernkontrolle durchf&uuml;hren zu k&ouml;nnen muss Javascript aktiviert sein.</p></noscript>
<div id="scriptWrapper">
	<div class="description">
		<div class="tr">
			<div class="td"><?php echo $lang["description"];?></div>
			<div class="td"><?php echo (($fetchQunaire["description"] != "") ? $fetchQunaire["description"] : '-');?></div>
		</div>
		<div class="tr">
			<div class="td"><?php echo $lang["quizStartDate"];?></div>
			<div class="td"><?php echo utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchQunaire["starttime"]) . " Uhr");?></div>
		</div>
		<div class="tr">
			<div class="td"><?php echo $lang["quizEndDate"];?></div>
			<div class="td"><?php
				if($fetchQunaire["noParticipationPeriod"]) {
					echo $lang["quizOpenForever"];
				} else {
					echo utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchQunaire["endtime"]) . " Uhr");
				}
			?></div>
		</div>
		<div class="tr">
			<div class="td"><?php echo $lang["quizTableAmountQuestions"];?></div>
			<div class="td"><?php echo $fetchQunaire["question_count"];?></div>
		</div>
		<div class="tr">
			<div class="td"><?php echo $lang["timeLimitation"];?></div>
			<div class="td"><?php 
				if($fetchQunaire["limited_time"] == 0)
					echo "keine";
				else 
				{
					echo gmdate("i:s", $fetchQunaire["limited_time"]) . " (mm:ss)";
				}
			?></div>
		</div>
		<div class="tr">
			<div class="td label"><?php echo $lang["amountParticipations"];?></div>
			<div class="td label"><?php
			$stmt = $dbh->prepare("select user_exec_session.id from user_exec_session inner join qunaire_exec on user_exec_session.execution_id = qunaire_exec.execution_id 
							where qunaire_exec.execution_id = :execId and user_exec_session.user_id = :user_id");
			$stmt->bindParam(":execId", $execId);
			$stmt->bindParam(":user_id", $_SESSION["id"]);
			$stmt->execute();
			$participations = $stmt->rowCount();
			echo $participations;
			?></div>
		</div>
		<div class="tr">
			<div class="td label"><?php echo $lang["maxParticipationLimit"];?></div>
			<div class="td label"><?php echo $fetchQunaire["amount_participations"] == 0 ? $lang["maxParticipations"] : $fetchQunaire["amount_participations"];?></div>
		</div>
		<div class="tr">
			<div class="td label"><?php echo $lang["createdBy"];?></div>
			<div class="td label"><?php echo $fetchQunaire["firstname"] . " " . $fetchQunaire["lastname"] . " (" . $fetchQunaire["email"] . ")";?></div>
		</div>
		<div class="tr">
			<div class="td"><?php echo $lang["participant"];?></div>
			<div class="td"><?php 
				$stmt = $dbh->prepare("select firstname, lastname, email from user inner join user_data on user.id = user_data.user_id where user.id = :userId");
				$stmt->bindParam(":userId", $_SESSION["id"]);
				$stmt->execute();
				$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
				echo $fetchUser["firstname"] . " " . $fetchUser["lastname"] . " (" . $fetchUser["email"] . ")";
			?></div>
		</div>
	</div>
	<div style="clear: both; height: 20px;"></div>
	<h1>Anmerkungen zur Teilnahme</h1>
	Eine Lernkontrolle besteht aus einer Reihe von Multiple oder Single Choice Fragen mit jeweils mehreren Antwortm&ouml;glichkeiten. Jede Antwortm&ouml;glichkeit wird mit einem Pluspunkt bewertet, wenn sie richtig ist und mit einem Minuspunkt wenn sie falsch ist. Neutral oder nicht gew&auml;hlte Antwortm&ouml;glichkeiten ergeben 0 Punkte.
	<br />
	<br />
	Bei Multiple Choice Fragen muss jede Antwortm&ouml;glichkeit als korrekt,  nicht korrekt oder "keine Antwort" gekennzeichnet werden. Man erh&auml;lt dann so viele Plus- und Minuspunkte, wie man Antwortm&ouml;glichkeiten richtig und falsch gew&auml;hlt hat. 
	<br />
	<br />
	Bei Single Choice Fragen kann nur eine Antwortm&ouml;glichkeit gew&auml;hlt werden. Man erh&auml;lt dann <i>(1 * Singlechoice Multiplizierer)</i> Plus- oder Minuspunkte. Der Singlechoice Multiplizierer ist dazu da, um Singlechoice Fragen gegen&uuml;ber den Multiplechoice Fragen nicht abzuwerten. Will man die Frage neutral beantworten, so ist die Antwortm&ouml;glichkeit "keine Antwort" zu w&auml;hlen. 
	<br />
	<br />
	<?php if(isset($_SESSION["quizSession"]) && $_SESSION["quizSession"] >= 0) {?>
		<b>Sie sind gerade noch in einer laufender Lernkontrolle eingetragen, bitte schliessen 
		Sie diese Lernokontrolle zuerst ab.</b>
	<?php }
	if($participations < $fetchQunaire["amount_participations"] || $fetchQunaire["amount_participations"] == 0 || $_SESSION["role"]["admin"] == 1) {
	?>
	<label>
		<input id="checkReadAll" original-title="Best&auml;tigung erforderlich!" type="checkbox" /><?php echo $lang["participationIntroAccept"]; ?>
	</label>
	<?php }?>
	<div id="startButton" style="display: none;" data-role="controlgroup" data-type="horizontal">
		<?php 
			$stmt = $dbh->prepare("select user_exec_session.id from user_exec_session inner join qunaire_exec on user_exec_session.execution_id = qunaire_exec.execution_id 
							where qunaire_exec.execution_id = :execId and user_exec_session.user_id = :user_id and endtime is null");
			$stmt->bindParam(":user_id", $_SESSION["id"]);
			$stmt->bindParam(":execId", $execId);
			$stmt->execute();
			$fetchEndtimeNull = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if($stmt->rowCount() > 0) {
				$_SESSION["quizSession"] = $execId;
				$_SESSION["idSession"] = $fetchEndtimeNull["id"];
			?>
			<input type="button" id="startQuiz" name="startQuiz" value="<?php echo $lang["toCurrentQuiz"]; ?>" data-icon="arrow-r" data-iconpos="right" onclick="<?php echo "window.location='?p=participate';";?>"/>
		<?php } else {
			if($participations < $fetchQunaire["amount_participations"] || $fetchQunaire["amount_participations"] == 0 || $_SESSION["role"]["admin"] == 1) {
			?>
			<input type="button" id="startQuiz" name="startQuiz" value="<?php echo $lang["begin"]; ?>" data-icon="arrow-r" data-iconpos="right" onclick="<?php echo "startNewQuiz(". $execId . ")";?>"/>
		<?php 
			} else {
				?>
				<p><b>Die Maximale Anzahl von Teilnahmen wurde bereits erreicht.</b></p>
				<?php 
			}
		}?>
	</div>
</div>
