<?php
	include "modules/authorizationCheck_quizReport.php";
?>

<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["quizReportHeading"] . " &laquo;" . $fetchQuiz["name"] . "&raquo;"?></h1>
	</div>
	<?php include 'modules/quizReportNav.php';?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["statisticAfterQuizHeadline"] . " - Stand vom " . date("d.m.Y H:i:s", time()); ?></h3>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-md-8 col-sm-8">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizCreateName"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php echo $fetchQuiz["name"];?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["description"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php echo $fetchQuiz["description"];?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizStartDate"];?>
							</label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php echo utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchQuiz["starttime"]));?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizEndDate"];?>
							</label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php
									if($fetchQuiz["noParticipationPeriod"]) {
										echo $lang["quizOpenForever"];
									} else {
										echo utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchQuiz["endtime"]));
									}
								?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizTableAmountQuestions"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select id from question inner join qunaire_qu on qunaire_qu.question_id = question.id where qunaire_qu.questionnaire_id = :quizId");
									$stmt->bindParam(":quizId", $fetchQuiz["qId"]);
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
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["amountParticipants"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select execution_id, user_exec_session.user_id from execution inner join user_exec_session 
														on user_exec_session.execution_id = execution.id where execution.id = :execId group by user_id");
									$stmt->bindParam(":execId", $_GET["execId"]);
									$stmt->execute();
									echo $stmt->rowCount();
								?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["amountParticipations"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select execution.id, user_exec_session.user_id from execution inner join user_exec_session 
														on user_exec_session.execution_id = execution.id where execution.id = :execId");
									$stmt->bindParam(":execId", $_GET["execId"]);
									$stmt->execute();
									echo $stmt->rowCount();
								?></p>
							</div>
						</div>
					</div>
					<p>
						<?php 
						$quizLink = str_replace("/index.php", "", $_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF']);
						$showedLink = $quizLink . "/?quiz=" . $fetchQuiz["exec_token"];
						
						$text;
						if($fetchQuiz["noParticipationPeriod"]) {
							$text = $lang["showQuizForever"];
						} else {
							$text = $lang["showQuiz"];
						}
						
						$text = str_replace("[0]", "<a href=\"?quiz=" . $fetchQuiz["exec_token"] . "\">" . $showedLink . "</a>", $text);
						$text = str_replace("[1]", utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchQuiz["starttime"])), $text);
						$text = str_replace("[2]", utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchQuiz["endtime"])), $text);
						$text = str_replace("[3]", $fetchQuiz["firstname"] . " " . $fetchQuiz["lastname"], $text);
						$text = str_replace("[4]", $fetchQuiz["email"], $text);
						echo $text;
						?>
					</p>
				</div>
				<div class="col-md-4 col-sm-4">
					<img
						src="https://chart.apis.google.com/chart?chs=300x300&cht=qr&chld=H|0&chl=<?php echo $showedLink;?>"
						alt="QR code" width="300px"
						style="max-width: 100%" />
				</div>
			</div>
		</div>
	</div>
	<div class="panel panel-default col-md-4 col-sm-4" style="padding-right: 0px; padding-left: 0px; float: left; margin-right: 20px;">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["quizReportTime"]; ?></h3>
		</div>
		<div class="panel-body">
			<?php //TODO: nicht sicher ob diese Berechnung stimmt --> in Sitzung anschauen?
			$stmt = $dbh->prepare("select starttime, endtime, end_state from user_exec_session where execution_id = :execId and endtime is not null");
			$stmt->bindParam(":execId", $_GET["execId"]);
			$stmt->execute();
			$fetchUserQunaireSession = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			$totalSeconds = 0;
			$shortestSeconds = 999999;
			$longestSeconds = 0;
			for($i = 0; $i < count($fetchUserQunaireSession); $i++)
			{
				$timeNeeded = ($fetchUserQunaireSession[$i]["endtime"] - $fetchUserQunaireSession[$i]["starttime"]);
				$totalSeconds += $timeNeeded;
				if($timeNeeded < $shortestSeconds)
					$shortestSeconds = $timeNeeded;
				if($timeNeeded > $longestSeconds)
					$longestSeconds = $timeNeeded;
				
			}
			
			$averageSecondsNeeded = $totalSeconds/count($fetchUserQunaireSession);
			
			?>
			<div class="row">
				<div class="col-md-12 col-sm-12">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-6 col-sm-6 control-label"><?php echo $lang["avgTimeNeededForQuiz"];?></label>
							<div class="col-md-6 col-sm-6">
								<p class="form-control-static"><?php echo gmdate("H:i:s", $averageSecondsNeeded);?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-6 col-sm-6 control-label"><?php echo $lang["bestTimeNeededForQuiz"];?></label>
							<div class="col-md-6 col-sm6">
								<p class="form-control-static"><?php echo gmdate("H:i:s", $shortestSeconds);?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-6 col-sm-6 control-label"><?php echo $lang["worstTimeNeededForQuiz"];?></label>
							<div class="col-md-6 col-sm-6">
								<p class="form-control-static"><?php echo gmdate("H:i:s", $longestSeconds);?></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="panel panel-default col-md-4 col-sm-4" style="padding-right: 0px; padding-left: 0px; float: left;">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["participationPointDetails"]; ?></h3>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-md-12 col-sm-12">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-6 col-sm-6 control-label"><?php echo $lang["bestParticipation"]; ?></label>
							<div class="col-md-6 col-sm-6">
								<?php 
								$bestPoints = [0,0,0];
								$avgPoints = 0;
								$stmt = $dbh->prepare("select user_exec_session.id, nickname, starttime, endtime from user_exec_session inner join user 
													on user.id = user_exec_session.user_id where execution_id = :execId");
								$stmt->bindParam(":execId", $_GET["execId"]);
								$stmt->execute();
								$fetchSession = $stmt->fetchAll(PDO::FETCH_ASSOC);
								
								for($i = 0; $i < count($fetchSession); $i++)
								{
									$fetchPoints = getPoints($dbh, $fetchQuiz["qId"], $fetchSession[$i]["id"], 2);
									$sessionKey = $fetchSession[$i]["nickname"];
									$timeNeeded = $fetchSession[$i]["endtime"] - $fetchSession[$i]["starttime"];
									if($fetchPoints[0] >= $bestPoints[1][0])
										if($fetchPoints[0] == $bestPoints[1][0])
										{
											if($timeNeeded < $bestPoints[2])
												$bestPoints = [$sessionKey, $fetchPoints, $timeNeeded];
										} else {
											$bestPoints = [$sessionKey, $fetchPoints, $timeNeeded];
										}
									$avgPoints += $fetchPoints[0];
								}
								$avgPoints /= count($fetchSession);
								?>
								<p class="form-control-static"><?php echo $bestPoints[0] . " mit " . $bestPoints[1][0] . " (" . $bestPoints[1][2] . "%) Punkten"?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-6 col-sm-6 control-label"><?php echo $lang["participationAvgPoints"]; ?></label>
							<div class="col-md-6 col-sm6">
								<p class="form-control-static"><?php echo number_format($avgPoints, 2) . " Punkte";?></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div style="clear:both;"></div>
</div>