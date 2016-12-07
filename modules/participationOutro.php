<?php
	session_start();
	if($_SESSION["role"]["user"] != 1)
	{
		header("Location: index.php?p=home&code=-20");
		exit;
	}
	
	include "modules/extraFunctions.php";
	include_once 'errorCodeHandler.php';
	
	function getMultiplechoiceChar($val)
	{
		switch ($val)
		{
			case -1:
				return '&#10007;';
				break;
			case 0:
				return '-';
				break;
			case 1:
				return '&#10003;';
				break;
		}
	}
	
	//Query Quiz
	$quizId = 0;
	if(isset($_GET["quizId"]))
	{
		$quizId = $_GET["quizId"];
	} else {
		header("Location: index.php?p=quiz&code=-2");
		exit;
	}

	$stmt = $dbh->prepare("select result_visible, result_visible_points, singlechoice_multiplier, public from questionnaire where id = :questionnaire_id");
	$stmt->bindParam(":questionnaire_id", $quizId);
	$stmt->execute();
	$fetchQuestionnaire = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if(!isset($_GET["session"]))
		$stmt = $dbh->prepare("select * from user_qunaire_session where user_id = :user_id and questionnaire_id = :questionnaire_id and endtime is not null order by id desc limit 1");
	else
	{
		$stmt = $dbh->prepare("select * from user_qunaire_session where user_id = :user_id and questionnaire_id = :questionnaire_id and endtime is not null and id = :session_id");
		$stmt->bindParam(":session_id", $_GET["session"]);
	}
	$stmt->bindParam(":user_id", $_SESSION["id"]);
	$stmt->bindParam(":questionnaire_id", $quizId);
	if(!$stmt->execute() || $stmt->rowCount() < 1)
	{
		header("Location: index.php?p=quiz&code=-14&info=unknown " . $stmt->rowCount() . " " . $_GET["session"]);
		exit;
	}
	$fetchSession = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if($fetchSession["end_state"] == NULL)
	{
		header("Location: index.php?p=quiz&code=-19");
		exit;
	}
	
	include_once 'modules/authorizationCheck_participation.php';
	checkAuthorization($_GET["quizId"], $fetchQuestionnaire, true);
	
	$errorCode = new mobileError("", "red");
	if(isset($_GET["code"]))
	{
		$errorCode = handleParticipationOutroError($_GET["code"]);
	}
?>

<div data-role="tabs" id="tabs">
	<div data-role="navbar">
		<ul>
			<li><a href="#participationStatistics"
				class="ui-btn-active ui-state-persist" data-ajax="false"><?php echo $lang["statisticAfterQuizHeadline"]; ?></a></li>
			<li><a href="#participationResults" data-ajax="false"><?php echo $lang["participationResultsHeading"]; ?></a></li>
		</ul>
	</div>
	<div id="participationStatistics">
		<p><?php echo $lang["thanksForParticipate"]; ?></p>
		<p id="codeResult" style="color:<?php echo $errorCode->getColor();?>;"><?php echo $errorCode->getText();?></p>
		<div>
			<?php 
			$stmt = $dbh->prepare("select id from user_qunaire_session where user_id = :user_id and questionnaire_id = :questionnaire_id and endtime is not null order by id desc");
			$stmt->bindParam(":user_id", $_SESSION["id"]);
			$stmt->bindParam(":questionnaire_id", $quizId);
			$stmt->execute();
			$fetchIdForSelect = $stmt->fetchAll(PDO::FETCH_ASSOC);
			?>
			<select onchange="refreshOnSelect(this.value)">
				<?php for($i = 0; $i < count($fetchIdForSelect); $i++) {?>
				<option value="<?php echo $fetchIdForSelect[$i]["id"];?>" <?php if(isset($_GET["session"]) && $fetchIdForSelect[$i]["id"]==$_GET["session"]) {echo 'selected';}?> ><?php echo (count($fetchIdForSelect)-$i) . ". Versuch"?></option>
				<?php }?>
			</select>
		</div>
		<h2><?php echo $lang["statisticAfterQuizHeadline"]; ?></h2>
		<table>
			<tr>
				<td><?php echo $lang["participant"]; ?>:</td>
				<td><?php 
					$stmt = $dbh->prepare("select firstname, lastname from user inner join user_data on user.id = user_data.user_id where user.id = :userId");
					$stmt->bindParam(":userId", $_SESSION["id"]);
					$stmt->execute();
					$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
					echo $fetchUser["firstname"] . " " .$fetchUser["lastname"];
				?></td>
			</tr>
			<tr>
				<td><?php echo $lang["amountParticipated"]; ?>:</td>
				<td><?php 
					$stmt = $dbh->prepare("select count(*) as count from user_qunaire_session where user_id = :user_id and questionnaire_id = :questionnaire_id");
					$stmt->bindParam(":user_id", $_SESSION["id"]);
					$stmt->bindParam(":questionnaire_id", $quizId);
					$stmt->execute();
					$fetchCount = $stmt->fetch(PDO::FETCH_ASSOC);
					echo $fetchCount["count"];
				?></td>
			</tr>
			<tr>
				<td><?php echo $lang["timeNeeded"]; ?>:</td>
				<td><?php echo gmdate("H:i:s", ($fetchSession["endtime"]-$fetchSession["starttime"])) . " (hh:mm:ss)";?></td>
			</tr>
			<?php if($fetchQuestionnaire["result_visible"] != 3) {?>
				<tr>
					<td><?php echo $lang["completedQuestions"]; ?>:</td>
					<td><?php 
						$stmt = $dbh->prepare("select question_id from an_qu_user inner join answer on answer.id = an_qu_user.answer_id where session_id = :session_id group by question_id");
						$stmt->bindParam(":session_id", $fetchSession["id"]);
						$stmt->execute();
						echo $stmt->rowCount();
					?></td>
				</tr>
			<?php } ?>
				<tr>
					<td><?php echo $lang["totalPoints"]; ?>:</td>
					<td><?php 
						if($fetchQuestionnaire["result_visible_points"] == 1) {
						$fetchPoints = getPoints($dbh, $quizId, $fetchSession["id"], 2);
						echo $fetchPoints[0] . "/" . $fetchPoints[1] . " (" . $fetchPoints[2] . "%)";
						} else {
							echo "Anzeigen der Punkte deaktiviert.";
						}
					?></td>
				</tr>
			<tr>
				<td><?php echo $lang["endState"]; ?>:</td>
				<td><?php 
				switch ($fetchSession["end_state"])
				{
					case 1:
						echo "OK";
						break;
					case 2:
						echo "Zeit abgelaufen";
						break;
					case 3:
						echo "Abgebrochen";
						break;
				}
				?></td>
			</tr>
		</table>
	</div>
	<div id="participationResults">
		<h2><?php echo $lang["participationResultsHeading"]; ?></h2>
		<p><?php 
		
			if($fetchQuestionnaire["result_visible"] == 1)
			{
				echo $lang["resultVisible1"];
			} else if($fetchQuestionnaire["result_visible"] == 2)
			{
				echo $lang["resultVisible2"];
			} else if($fetchQuestionnaire["result_visible"] == 3)
			{
				echo $lang["resultVisible3"];
			}
		?></p>
		<?php 
		if($fetchQuestionnaire["result_visible"] != 3) 
		{
		
			$stmt = $dbh->prepare("select question.id as questionId, question.text as questionText, question.type_id, question.picture_link, an_qu_user.question_order from question inner join qunaire_qu on qunaire_qu.question_id = question.id left outer join an_qu_user on an_qu_user.question_id = question.id and session_id = :session_id where qunaire_qu.questionnaire_id = :questionnaire_id group by question.id order by an_qu_user.question_order");
			$stmt->bindParam(":questionnaire_id", $quizId);
			$stmt->bindParam(":session_id", $fetchSession["id"]);
			if(!$stmt->execute())
			{
				header("Location: index.php?p=quiz&code=-26&quizId=" . $quizId . "&session=" . $fetchSession["id"] . "&dbhError=". $dbh->errorInfo()[0] . "&stmtError=" . $stmt->errorInfo()[2]);
				exit;
			}
			$fetchQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			for($i = 0; $i < count($fetchQuestions); $i++)
			{
			?>
				<div id="<?php echo "q-" . $fetchQuestions[$i]["questionId"];?>" class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title" style="wihte-space: pre-wrap; word-wrap: break-word;"><?php echo $lang["question"] . " " . ($i+1) . ": " . $fetchQuestions[$i]["questionText"];?></h3>
					</div>
					<?php if(isset($fetchQuestions[$i]["picture_link"])) { ?>
					<img id="questionImage" style="margin: 1em;" src="<?php echo $fetchQuestions[$i]["picture_link"]?>" />
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
					<?php }?>
					<div class="panel-body">
						<table style="width: 100%">
							<thead>
								<tr>
									<th style="text-align: left"><?php echo $lang["answertext"];?></th>
									<th style="width: 100px; text-align: center"><?php echo $lang["correctAnswer"];?></th>
									<th style="width: 100px; text-align: center"><?php echo $lang["selectedAnswer"];?></th>
									<th style="width: 100px; text-align: center"><?php echo $lang["totalPoints"];?></th>
								</tr>
							</thead>
							<tbody>
								<?php 
								$stmt = $dbh->prepare("select answer_question.answer_id, answer.text, answer_question.is_correct, (select selected from an_qu_user where answer_question.answer_id = an_qu_user.answer_id and session_id = :session_id) as selected
									from answer_question 
									inner join answer on answer.id = answer_question.answer_id 
									where answer_question.question_id = :question_id");
								$stmt->bindParam(":question_id", $fetchQuestions[$i]["questionId"]);
								$stmt->bindParam(":session_id", $fetchSession["id"]);
								if(!$stmt->execute())
								{
									header("Location: index.php?p=quiz&code=-26");
									exit;
								}
								$fetchAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
								
								$answerColor = "";
								$answeredCorrect = false;
								$punkte = 0;
								if($fetchQuestions[$i]["type_id"] == 1) //singlechoice
								{
									for($j = 0; $j < count($fetchAnswers); $j++)
									{
										if($fetchAnswers[$j]["selected"] == 1)
										{
											$punkte = -1*$fetchQuestionnaire["singlechoice_multiplier"];
										}
										if($fetchAnswers[$j]["is_correct"] == 1 && $fetchAnswers[$j]["selected"] == 1)
										{
											$answerColor = "#CCFF99";
											$answeredCorrect = true;
											$punkte = 1*$fetchQuestionnaire["singlechoice_multiplier"];
											break;
										}
										$answerColor = "#FFCCCC";
									}
								}
								for($j = 0; $j < count($fetchAnswers); $j++)
								{
								?>
									<tr style="background-color: <?php if($answerColor != ""){ echo $answerColor;} else { echo $fetchAnswers[$j]["is_correct"] == $fetchAnswers[$j]["selected"] ? '#CCFF99' : '#FFCCCC';}?>">
										<td style="word-wrap: break-word;"><?php echo $fetchAnswers[$j]["text"];?></td>
										<td style="text-align: center; font-size: 1.2em"><?php
										if($fetchQuestions[$i]["type_id"] == 1) //singlechoice
										{
											if($fetchQuestionnaire["result_visible"] == 1)
											{
												echo $fetchAnswers[$j]["is_correct"] == 1 ? '&#9673;' : '&Omicron;';
											} else if($fetchQuestionnaire["result_visible"] == 2)
											{
												if($answeredCorrect)
													echo $fetchAnswers[$j]["is_correct"] == 1 ? '&#9673;' : '&Omicron;';
												else 
													echo "?";
											}
										} else if($fetchQuestions[$i]["type_id"] == 2) //multiplechoice
										{
											if($fetchQuestionnaire["result_visible"] == 1)
											{
												echo getMultiplechoiceChar($fetchAnswers[$j]["is_correct"]);
											} else if($fetchQuestionnaire["result_visible"] == 2)
											{
												if($fetchAnswers[$j]["is_correct"] == $fetchAnswers[$j]["selected"])
												{
													echo getMultiplechoiceChar($fetchAnswers[$j]["is_correct"]);
												} else {
													echo "?";
												}
											}
										}
										?></td>
										<td style="text-align: center; font-size: 1.2em"><?php 
										if($fetchQuestions[$i]["type_id"] == 1) //singlechoice
										{
											echo $fetchAnswers[$j]["selected"] == 1 && $fetchAnswers[$j]["selected"] != NULL ? '&#9673;' : '&Omicron;';
										} else if($fetchQuestions[$i]["type_id"] == 2) //multiplechoice
										{
											if($fetchAnswers[$j]["selected"] != NULL)
												echo getMultiplechoiceChar($fetchAnswers[$j]["selected"]);
											else 
												echo '&#8408;';
										}
										?></td>
										<td style="text-align: center"><?php 
										if($fetchQuestions[$i]["type_id"] == 1) //singlechoice
										{
											if($fetchAnswers[$j]["selected"] == 1)
												echo $punkte;
											else
												echo "0";
										} else if($fetchQuestions[$i]["type_id"] == 2) //multiplechoice
										{
											if($fetchAnswers[$j]["selected"] == 0)
												echo "0";
											else {
												if($fetchAnswers[$j]["is_correct"] == $fetchAnswers[$j]["selected"])
												{
													echo "1";
												} else {
													echo "-1";
												}
											}
										}
										?></td>
									</tr>
								<?php }?>
							</tbody>
						</table>
					</div>
				</div>
			<?php
			}
		}
		?>
	</div>
</div>
<div data-role="controlgroup" data-type="horizontal" style="margin-top: 25px;">
	<a href="?p=participationIntro&quizId=<?php echo $quizId;?>" data-theme="a" data-ajax="false" data-iconshadow="true" data-role="button" data-icon="arrow-l" data-iconpos="left"><?php echo $lang["rejoinQuiz"]; ?></a>
	<a href="index.php?p=quiz" data-theme="a" data-iconshadow="true" data-ajax="false" data-role="button" data-icon="arrow-r" data-iconpos="right"><?php echo $lang["nextQuestion"]; ?></a>
</div>


<script type="text/javascript">

	$(function() {
		
	});

	function refreshOnSelect(value)
	{
		window.location = "Pindex.php?p=participationOutro&quizId="+<?php echo $quizId;?>+"&session=" + value;
	}

</script>
<script type="text/javascript" src="js/photoSwipeController.js"></script>
