<?php
include "modules/authorizationCheck_quizReport.php";

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

if(!isset($_GET["qId"]) || !isset($_GET["uId"]))
{
	header("Location: ?p=quiz&code=-15&info=first");
	exit;
}

$stmt = $dbh->prepare("select * from user inner join user_data on user_id = id where id = :uId");
$stmt->bindParam(":uId", $_GET["uId"]);
if(!$stmt->execute())
{
	header("Location: ?p=quiz&code=-25");
	exit;
}
$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $dbh->prepare("select question.* from qunaire_qu inner join question on question.id = qunaire_qu.question_id where questionnaire_id = :quizId");
$stmt->bindParam(":quizId", $_GET["qId"]);
$stmt->execute();
$fetchQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $fetchUser["lastname"] . " " . $fetchUser["firstname"] . " &laquo;" . $fetchQuiz["name"] . "&raquo; Auswertung "?></h1>
	</div>
	<input type="button" value="<?php echo $lang["btnBack"];?>" class="btn" style="margin-bottom: 5px;" onclick="backToOverview(<?php echo $execId;?>)">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["answerStat"] . " - Stand vom " . date("d.m.Y H:i:s", time()); ?></h3>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-md-8 col-sm-8">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizTableAmountQuestions"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select id from question inner join qunaire_qu on qunaire_qu.question_id = question.id where qunaire_qu.questionnaire_id = :quizId");
									$stmt->bindParam(":quizId", $_GET["qId"]);
									$stmt->execute();
									echo $stmt->rowCount();
								?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["maxPoints"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select question.id, type_id, execution.singlechoice_multiplier from question inner join qunaire_qu
																on qunaire_qu.question_id = question.id inner join questionnaire on questionnaire.id = qunaire_qu.questionnaire_id
																inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id inner join execution on qunaire_exec.execution_id = execution.id
																where execution.id = :execId");
									$stmt->bindParam(":execId", $_GET["execId"]);
									$stmt->execute();
									$fetchQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
									$totalPoints = 0;
									for($i = 0; $i < count($fetchQuestions); $i++)
									{
										if($fetchQuestions[$i]["type_id"] == 1)
											$totalPoints+= (1*$fetchQuestions[0]["singlechoice_multiplier"]);
											else if($fetchQuestions[$i]["type_id"] == 2)
											{
												$stmt = $dbh->prepare("select answer_id as count from answer_question where question_id = :question_id");
												$stmt->bindParam(":question_id", $fetchQuestions[$i]["id"]);
												$stmt->execute();
												$totalPoints += $stmt->rowCount();
											}
									}
									echo $totalPoints;
								?></p>
							</div>
						</div>
						<?php 
						$bestSession = -1;
						$percentageArray= array();
						$choosedSession = -1;
						if(isset($_GET["sId"]))
							$choosedSession = $_GET["sId"];
						$stmt = $dbh->prepare("select * from user_exec_session where user_id = :user_id and execution_id = :execId and endtime is not null ");
						$stmt->bindParam(":user_id", $_GET["uId"]);
						$stmt->bindParam(":execId", $execId);
						if(!$stmt->execute())
						{
							header("Location: index.php?p=quiz&code=-14");
							exit;
						}
						$fetchSessionRowCount = $stmt->rowCount();
						$fetchSession = $stmt->fetchAll(PDO::FETCH_ASSOC);
						?>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["amountParticipations"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									echo $fetchSessionRowCount;
								?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo "Beste Teilnahme";?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
		                            $tmpPoints = null;
		                            $fetchPoints = [0,0,0];
									$timeNeededComplete = -1;
									$starttime = 0;
		                            for ($j = 0; $j < count($fetchSession); $j++)
		                            {
		                            	$tmpPoints = getPoints($dbh, $_GET["qId"], $fetchSession[$j]["id"], 0);
		                            	$percentageArray[$fetchSession[$j]["id"]] = $tmpPoints;
		                            	if($j == 0 || $tmpPoints[0] >= $fetchPoints[0])
		                            	{
			                            	$fetchPoints = $tmpPoints;
			                            	$bestSession = $fetchSession[$j]["id"];
			                            	if(!isset($_GET["sId"]))
			                            	{
			                            		$choosedSession = $fetchSession[$j]["id"];
		                            			$timeNeededComplete = $fetchSession[$j]["endtime"] - $fetchSession[$j]["starttime"];
		                            			$starttime = $fetchSession[$j]["starttime"];
			                            	}
		                            	}
		                            	if(isset($_GET["sId"]) && $fetchSession[$j]["id"] == $_GET["sId"])
		                            	{
		                            		$timeNeededComplete = $fetchSession[$j]["endtime"] - $fetchSession[$j]["starttime"];
		                            		$starttime = $fetchSession[$j]["starttime"];
		                            	}
		                            }
		                            
		                            echo $fetchPoints[0]."/".$fetchPoints[1]. " (" . $fetchPoints[2] . "%)";
								?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["timeNeeded"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									echo gmdate("H:i:s", $timeNeededComplete) . " (HH:mm:ss)";
								?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["participatedAt"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									echo date("d.m.Y - H:i:s", $starttime);
								?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo "Teilnahme anzeigen";?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static">
									<select style="width: 180px;" id="owner" class="form-control" name="owner" onchange="changeSession(this)">
										<?php for($i = count($fetchSession)-1; $i >= 0; $i--) {?>
											<option value="<?php echo $fetchSession[$i]["id"];?>" <?php echo ($fetchSession[$i]["id"] == $choosedSession) ? 'selected' : '';?>><?php echo ($i + 1) . ". Versuch (" . $percentageArray[$fetchSession[$i]["id"]][2] . "%)";?></option>
										<?php }?>
									</select>
								</p>
							</div>
						</div>
					</div>
				</div>			
			</div>
		</div>
	</div>
		<?php for($i = 0; $i < count($fetchQuestions); $i++) {

			$stmt = $dbh->prepare("select answer_question.answer_id, answer.text, answer_question.is_correct, (select selected from an_qu_user where answer_question.answer_id = an_qu_user.answer_id and session_id = :session_id) as selected,
									(select next_button_time from an_qu_user where answer_question.answer_id = an_qu_user.answer_id and session_id = :session_id) as next_button_time,
									(select time_needed from an_qu_user where answer_question.answer_id = an_qu_user.answer_id and session_id = :session_id) as time_needed
									from answer_question
									inner join answer on answer.id = answer_question.answer_id
									where answer_question.question_id = :question_id");
			$stmt->bindParam(":question_id", $fetchQuestions[$i]["id"]);
			$stmt->bindParam(":session_id", $choosedSession);
			$stmt->execute();
			$fetchAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			?>
			<div id="<?php echo $fetchQuestions[$i]["id"];?>" class="panel panel-default">
				<div class="panel-heading">
					<div class="row">
						<div class="col-md-10 col-sm-9">
							<h3 class="panel-title">
								<?php echo $lang["question"] . " " . ($i+1) . ": " . $fetchQuestions[$i]["text"];?>
							</h3>
						</div>
						<div class="col-md-2 col-sm-3" style="text-align: right;">
							Ben&ouml;tigte Zeit f&uuml;r die Frage: <span id="questionTimeNeeded_<?php echo $fetchQuestions[$i]["id"];?>"></span> s
						</div>
					</div>
				</div>
				<div class="panel-body">
					<table style="width: 100%">
						<thead>
							<tr>
								<th style="width: 100px; text-align: center">
									<?php echo $lang["correctAnswer"]; ?>
								</th>
								<th style="width: 100px; text-align: center">
									<?php echo $lang["selectedAnswer"]; ?>
								</th>
								<th>
									<?php echo $lang["answertext"]; ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							
							$totalAmountOk = 0;
							$totalPossibleAnswers = 0;
							$nextButtonTime = -1;
							$questionTime = -1;
							for($j = 0; $j < count($fetchAnswers); $j++)
							{
								?><tr style="background-color: <?php echo ($fetchAnswers[$j]["is_correct"] == $fetchAnswers[$j]["selected"]) ? "rgba(0, 255, 0, 0.39)" : "rgba(255, 0, 0, 0.36);" ;?>;"><?php
								if($fetchQuestions[$i]["type_id"] == 1) //singlechoice
								{
									?>
									<td style="text-align: center;"><?php echo ($fetchAnswers[$j]["is_correct"] == 1) ? '&#9673;' : '&Omicron;';?></td>
									<td style="text-align: center;"><?php echo ($fetchAnswers[$j]["selected"] == 1) ? '&#9673;' : '&Omicron;';?></td>
									<td><?php echo $fetchAnswers[$j]["text"];?></td>
									<?php
								}
								else if($fetchQuestions[$i]["type_id"] == 2) //multiplechoice
								{
									?>
									<td style="text-align: center;"><?php echo ($fetchAnswers[$j]["is_correct"] == 1) ? '&#10003;' : '&#10007;';?></td>
									<td style="text-align: center;"><?php echo getMultiplechoiceChar($fetchAnswers[$j]["selected"]);?></td>
									<td><?php echo $fetchAnswers[$j]["text"];?></td>
									<?php
								}
								?></tr><?php
								$nextButtonTime = $fetchAnswers[$j]["next_button_time"];
								$questionTime = $fetchAnswers[$j]["time_needed"];
								?>
								<script>
									$('#questionTimeNeeded_<?php echo $fetchQuestions[$i]["id"];?>').html(<?php echo $questionTime;?>);
								</script>
								<?php 
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
			<div style="text-align: center; margin-bottom: 20px;"><?php echo "Ladezeit zwischen Fragen: " . $nextButtonTime . " ms";?></div>
		<?php }?>
	<input type="button" value="<?php echo $lang["btnBack"];?>" class="btn" style="margin-bottom: 5px;" onclick="backToOverview(<?php echo $execId;?>)">
</div>

<script type="text/javascript">
	function backToOverview(execId)
	{
		window.location = '?p=quizReportLadder&execId=' + execId;
	}

	function changeSession(val)
	{
		window.location = '?p=quizReportAnswerPersonalized&uId=<?php echo $_GET["uId"];?>&qId=<?php echo $_GET["qId"];?>&sId='+$(val).val()+'&execId='+<?php echo $_GET["execId"] ?>;
	}
</script>