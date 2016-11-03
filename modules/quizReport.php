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

//TODO: Duplicated SQL-Query

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
								<p class="form-control-static"><?php echo strftime("%d. %B %Y, %H:%M:%S", $fetchQuiz["starttime"]);?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizEndDate"];?>
							</label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php echo strftime("%d. %B %Y, %H:%M:%S", $fetchQuiz["endtime"]);?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["maxPoints"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select id, type_id from question inner join qunaire_qu on qunaire_qu.question_id = question.id where qunaire_qu.questionnaire_id = :quizId");
									$stmt->bindParam(":quizId", $_GET["id"]);
									$stmt->execute();
									$fetchQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
									$totalPoints = 0;
									for($i = 0; $i < count($fetchQuestions); $i++)
									{
										if($fetchQuestions[$i]["type_id"] == 1)
											$totalPoints++;
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
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizTableAmountQuestions"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select id from question inner join qunaire_qu on qunaire_qu.question_id = question.id where qunaire_qu.questionnaire_id = :quizId");
									$stmt->bindParam(":quizId", $_GET["id"]);
									$stmt->execute();
									echo $stmt->rowCount();
								?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["amountParticipants"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select questionnaire.id, user_qunaire_session.user_id from questionnaire inner join user_qunaire_session on user_qunaire_session.questionnaire_id = questionnaire.id where questionnaire.id = :quizId group by user_id");
									$stmt->bindParam(":quizId", $_GET["id"]);
									$stmt->execute();
									echo $stmt->rowCount();
								?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["amountParticipations"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select questionnaire.id, user_qunaire_session.user_id from questionnaire inner join user_qunaire_session on user_qunaire_session.questionnaire_id = questionnaire.id where questionnaire.id = :quizId");
									$stmt->bindParam(":quizId", $_GET["id"]);
									$stmt->execute();
									echo $stmt->rowCount();
								?></p>
							</div>
						</div>
					</div>
					<p>
						<?php 
						$quizLink = str_replace("/index.php", "", $_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF']);
						$showedLink = $quizLink . "/?quiz=" . $fetchQuiz["qnaire_token"];
						$text = $lang["showQuiz"];
						$text = str_replace("[0]", "<a href=\"?quiz=" . $fetchQuiz["qnaire_token"] . "\">" . $showedLink . "</a>", $text);
						$text = str_replace("[1]", strftime("%d. %B %Y, %H:%M:%S", $fetchQuiz["starttime"]), $text);
						$text = str_replace("[2]", strftime("%d. %B %Y, %H:%M:%S", $fetchQuiz["endtime"]), $text);
						$text = str_replace("[3]", $fetchQuiz["firstname"] . " " . $fetchQuiz["lastname"], $text);
						$text = str_replace("[4]", $fetchQuiz["email"], $text);
						echo $text;
						?>
					</p>
				</div>
				<div class="col-md-4 col-sm-4">
					<img
						src="http://chart.apis.google.com/chart?chs=300x300&cht=qr&chld=H|0&chl=<?php echo $showedLink;?>"
						alt="QR code" widthHeight="300" widthHeight="300"
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
			<?php 
			$stmt = $dbh->prepare("select starttime, endtime, end_state from user_qunaire_session where questionnaire_id = :quizId");
			$stmt->bindParam(":quizId", $_GET["id"]);
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
								$stmt = $dbh->prepare("select user_qunaire_session.id, nickname, starttime, endtime from user_qunaire_session inner join user on user.id = user_qunaire_session.user_id where questionnaire_id = :questionnaire_id");
								$stmt->bindParam(":questionnaire_id", $_GET["id"]);
								$stmt->execute();
								$fetchSession = $stmt->fetchAll(PDO::FETCH_ASSOC);
								
								for($i = 0; $i < count($fetchSession); $i++)
								{
									$fetchPoints = getPoints($dbh, $_GET["id"], $fetchSession[$i]["id"], 2);
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