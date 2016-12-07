<?php
include "modules/extraFunctions.php";

if(!isset($_GET["id"]))
{
	header("Location: ?p=quiz&code=-15");
	exit;
}

if($_SESSION["role"]["user"] == 1)
{
	if($_SESSION["role"]["creator"] != 1 && !amIAssignedToThisQuiz($dbh, $_GET["id"]))
	{
		header("Location: ?p=quiz&code=-1");
		exit;
	}
}
else
{
	header("Location: ?p=home&code=-20");
	exit;
}

$stmt = $dbh->prepare("select questionnaire.name, description, starttime, endtime, last_modified, qnaire_token, firstname, lastname, email, owner_id from questionnaire inner join user on user.id = questionnaire.owner_id inner join user_data on user_data.user_id = user.id where questionnaire.id = :quizId");
$stmt->bindParam(":quizId", $_GET["id"]);
if(!$stmt->execute())
{
	header("Location: ?p=quiz&code=-25");
	exit;
}
if($stmt->rowCount() != 1)
{
	header("Location: ?p=quiz&code=-15");
	exit;
}
$fetchQuiz = $stmt->fetch(PDO::FETCH_ASSOC);
if($fetchQuiz["owner_id"] != $_SESSION["id"] && $_SESSION['role']['admin'] != 1 && !amIAssignedToThisQuiz($dbh, $_GET["id"]))
{
	header("Location: ?p=quiz&code=-1");
	exit;
}

$stmt = $dbh->prepare("select question.* from qunaire_qu inner join question on question.id = qunaire_qu.question_id where questionnaire_id = :quizId");
$stmt->bindParam(":quizId", $_GET["id"]);
$stmt->execute();
$fetchQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["quizReportHeading"] . " &laquo;" . $fetchQuiz["name"] . "&raquo;"?></h1>
	</div>
	<?php include 'modules/quizReportNav.php';?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["answerStat"] . " - Stand vom " . date("d.m.Y H:i:s", time()); ?></h3>
		</div>
	</div>
		<?php for($i = 0; $i < count($fetchQuestions); $i++) {?>
			<div id="<?php echo $fetchQuestions[$i]["id"];?>" class="panel panel-default">
				<div class="panel-heading">
					<div class="row">
						<div class="col-md-10 col-sm-9">
							<h3 class="panel-title">
								<?php echo $lang["question"] . " " . ($i+1) . ": " . $fetchQuestions[$i]["text"];?>
							</h3>
						</div>
						<div class="col-md-2 col-sm-3" style="text-align: right;">
							<?php 
							$stmt = $dbh->prepare("select * from answer_question inner join answer on answer.id = answer_question.answer_id where answer_question.question_id = :question_id");
							$stmt->bindParam(":question_id", $fetchQuestions[$i]["id"]);
							$stmt->execute();
							$fetchAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
							
							$answerCountArray = array();
							for($j = 0; $j < count($fetchAnswers); $j++)
							{
								$answerCountArray[$fetchAnswers[$j]["id"]] = [$fetchAnswers[$j]["is_correct"], 0, 0, 0, 0];
							}

							$stmt = $dbh->prepare("select * from user_qunaire_session where questionnaire_id = :quizId");
							$stmt->bindParam(":quizId", $_GET["id"]);
							$stmt->execute();
							$fetchSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
							
							$amountCorrect = 0;
							$amountAnswers = 0;
							$amountTotal = 0;
							$nullCounter = 0;
							$allMultiplesCorrect = true;
							for($j = 0; $j < count($fetchSessions); $j++)
							{
								$stmt = $dbh->prepare("select * from an_qu_user where session_id = :session_id and question_id = :question_id");
								$stmt->bindParam(":session_id", $fetchSessions[$j]["id"]);
								$stmt->bindParam(":question_id", $fetchQuestions[$i]["id"]);
								$stmt->execute();
								$fetchUserAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
								
								for($k = 0; $k < count($fetchUserAnswers); $k++)
								{
									if($fetchQuestions[$i]["type_id"] == 1) //singlechoice
									{
										if($fetchUserAnswers[$k]["selected"] == 1)
										{
											$answerCountArray[$fetchUserAnswers[$k]["answer_id"]][1]++;
											if($fetchUserAnswers[$k]["selected"] == $answerCountArray[$fetchUserAnswers[$k]["answer_id"]][0])
												$amountCorrect++;
										} else if($fetchUserAnswers[$k]["selected"] == NULL)
										{
											$nullCounter++;
											
											break;
										}
									}
									else if($fetchQuestions[$i]["type_id"] == 2) //multiplechoice
									{
										if($fetchUserAnswers[$k]["selected"] != NULL)
										{
											if($fetchUserAnswers[$k]["selected"] == 1)
											{
												$answerCountArray[$fetchUserAnswers[$k]["answer_id"]][1]++;
											} else if($fetchUserAnswers[$k]["selected"] == 0)
											{
												$answerCountArray[$fetchUserAnswers[$k]["answer_id"]][2]++;
											} else if($fetchUserAnswers[$k]["selected"] == -1)
											{
												$answerCountArray[$fetchUserAnswers[$k]["answer_id"]][3]++;
											}
											
											if($fetchUserAnswers[$k]["selected"] != $answerCountArray[$fetchUserAnswers[$k]["answer_id"]][0])
											{
												$allMultiplesCorrect = false;
											}
											
										} else
										{
											$allMultiplesCorrect = false;
											$nullCounter++;
											break;
										}
										$answerCountArray[$fetchUserAnswers[$k]["answer_id"]][4]++;

										if($allMultiplesCorrect)
											$amountCorrect++;
									}
								}
								
								$amountTotal++;
							}
							?>
							<p id="<?php echo "accuracy_" . $fetchQuestions[$i]["id"];?>">
								<?php echo "(" . $lang["accuracy"] . " " . number_format(($amountCorrect*100)/($amountTotal-$nullCounter), 0) ." %)";?>
							</p>
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
								<th>
									<?php echo $lang["answertext"]; ?>
								</th>
								<th style="width: 250px">
									<?php echo $lang["questionAmountAnswers"]; ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							$totalAmountOk = 0;
							$totalAmountNok = 0;
							$totalPossibleAnswers = 0;
							for($j = 0; $j < count($fetchAnswers); $j++)
							{
								?><tr><?php
								if($fetchQuestions[$i]["type_id"] == 1) //singlechoice
								{
									?>
									<td style="text-align: center;"><?php echo ($fetchAnswers[$j]["is_correct"] == 1) ? '&#9673;' : '&Omicron;';?></td>
									<td><?php echo $fetchAnswers[$j]["text"];?></td>
									<td><div class="bar" style="<?php echo $answerCountArray[$fetchAnswers[$j]["id"]][0] == 1 ? 'background-color: #CF9;' : 'background-color: #FCC;';?> width: <?php echo ($answerCountArray[$fetchAnswers[$j]["id"]][1]*100)/$amountTotal;?>%"><?php echo $answerCountArray[$fetchAnswers[$j]["id"]][1];?></div></td>
									<?php
								}
								else if($fetchQuestions[$i]["type_id"] == 2) //multiplechoice
								{
									$amountOk = 0;
									$amountNotOk = 0;
									if($answerCountArray[$fetchAnswers[$j]["id"]][0] == 1)
									{
										$amountOk = $answerCountArray[$fetchAnswers[$j]["id"]][1];
										$amountNotOk = $answerCountArray[$fetchAnswers[$j]["id"]][3];
										$totalAmountOk += $amountOk;
										$totalAmountNok += $amountNotOk;
									}
									else if($answerCountArray[$fetchAnswers[$j]["id"]][0] == -1)
									{
										$amountOk = $answerCountArray[$fetchAnswers[$j]["id"]][3];
										$amountNotOk = $answerCountArray[$fetchAnswers[$j]["id"]][1];
										$totalAmountOk += $amountOk;
										$totalAmountNok += $amountNotOk;
									}
									
									$totalAmountWithoutNull = $totalAmountNok + $totalAmountOk;
									?>
									<td style="text-align: center;"><?php echo ($fetchAnswers[$j]["is_correct"] == 1) ? '&#10003;' : '&#10007;';?></td>
									<td><?php echo $fetchAnswers[$j]["text"];?></td>
									<td>
									<div class="bar" style="<?php echo 'background-color: #CF9;';?> width: <?php echo ($amountOk*100)/$answerCountArray[$fetchAnswers[$j]["id"]][4];?>%"><?php echo $amountOk;?></div>
									<div class="bar" style="<?php echo 'background-color: #CCC;';?> width: <?php echo ($answerCountArray[$fetchAnswers[$j]["id"]][2]*100)/$answerCountArray[$fetchAnswers[$j]["id"]][4];?>%"><?php echo $answerCountArray[$fetchAnswers[$j]["id"]][2];?></div>
									<div class="bar" style="<?php echo 'background-color: #FCC;';?> width: <?php echo ($amountNotOk*100)/$answerCountArray[$fetchAnswers[$j]["id"]][4];?>%"><?php echo $amountNotOk;?></div>
									</td>
									<?php
								}
								?></tr><?php
							}
							if($fetchQuestions[$i]["type_id"] == 1) //singlechoice
							{
							?>
								<tr>
								<td style="text-align: center;">&Omicron;</td>
								<td><?php echo $lang["noAnswer"];?></td>
								<?php if($amountTotal == 0) {$amountTotal = $nullCounter;}?>
								<td><div class="bar" style="<?php echo 'background-color: #CCC;';?> width: <?php echo ($nullCounter*100)/$amountTotal;?>%"><?php echo $nullCounter;?></div></td>
								</tr>
							<?php }?>
						</tbody>
					</table>
					<?php 
					if($fetchQuestions[$i]["type_id"] == 2) //multiplechoice
					{?>
					<script type="text/javascript">
						document.getElementById('<?php echo "accuracy_" . $fetchQuestions[$i]["id"];?>').innerHTML = '<?php echo "(" . $lang["accuracy"] . " " . number_format(($totalAmountOk*100)/($totalAmountWithoutNull), 0) ." %)";?>';
					</script>
					<?php }?>
				</div>
			</div>
		<?php }?>
	</div>
</div>