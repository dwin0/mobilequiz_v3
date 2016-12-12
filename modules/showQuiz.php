<?php

if($_SESSION["role"]["user"] != 1)
{
	header("Location: ?p=home&code=-20");
	exit;
}

if(!isset($_GET["execId"]))
{
	header("Location: ?p=quiz&code=-15");
	exit;
}

$stmt = $dbh->prepare("select questionnaire.name, noParticipationPeriod, description, starttime, endtime, execution.last_modified, exec_token, firstname, lastname, email 
					from questionnaire inner join user on user.id = questionnaire.owner_id inner join user_data on user_data.user_id = user.id 
					inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id inner join execution on qunaire_exec.execution_id = execution.id 
					where execution.id = :execId");
$stmt->bindParam(":execId", $_GET["execId"]);
if(!$stmt->execute())
{
	header("Location: ?p=quiz&code=-25");
	exit;
}
$fetchExec = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["quiz"] . " &laquo;" . $fetchExec["name"] . "&raquo;"?></h1>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo "Publikationslink - Stand vom " . date("d.m.Y H:i:s", $fetchExec["last_modified"]); ?></h3>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-md-8 col-sm-8">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizCreateName"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php echo $fetchExec["name"];?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["description"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php echo $fetchExec["description"];?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizStartDate"];?>
							</label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php echo utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchExec["starttime"]));?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizEndDate"];?>
							</label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php
									if($fetchExec["noParticipationPeriod"]) {
										echo $lang["quizOpenForever"];
									} else {
										echo utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchExec["endtime"]));
									}
								?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["quizTableAmountQuestions"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select question.id from question inner join qunaire_qu on qunaire_qu.question_id = question.id inner join questionnaire on 
														questionnaire.id = qunaire_qu.questionnaire_id inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id 
														inner join execution on qunaire_exec.execution_id = execution.id where execution.id = :execId");
									$stmt->bindParam(":execId", $_GET["execId"]);
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
									$stmt = $dbh->prepare("select execution_id, user_exec_session.user_id from user_exec_session where execution_id = :execId group by user_id");
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
									$stmt = $dbh->prepare("select execution_id, user_exec_session.user_id from user_exec_session where execution_id = :execId");
									$stmt->bindParam(":execId", $_GET["execId"]);
									$stmt->execute();
									echo $stmt->rowCount();
								?></p>
							</div>
						</div>
					</div>
					<p>
						<?php 
						//$quizLink = str_replace("/index.php", "", $_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF']);
						$quizLink = "sinv-56082.edu.hsr.ch";
						$showedLink = $quizLink . "/?quiz=" . $fetchExec["exec_token"];
						
						$text;
						if($fetchExec["noParticipationPeriod"]) {
							$text = $lang["showQuizForever"];
						} else {
							$text = $lang["showQuiz"];
						}
						
						$text = str_replace("[0]", "<a href=\"?quiz=" . $fetchExec["exec_token"] . "\">" . $showedLink . "</a>", $text);
						$text = str_replace("[1]", utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchExec["starttime"])), $text);
						$text = str_replace("[2]", utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchExec["endtime"])), $text);
						$text = str_replace("[3]", $fetchExec["firstname"] . " " . $fetchExec["lastname"], $text);
						$text = str_replace("[4]", $fetchExec["email"], $text);
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

	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang["moreOptions"];?></h3>
		</div>
		<div class="panel-body">
			<p>
				<a href="?p=generatePDF&action=getQuizTaskPaper&execId=<?php echo $_GET["execId"];?>" target="_blank"><?php echo $lang["showPrintTasksheet"];?></a>
			</p>
		</div>
	</div>
	<div>
		<div style="float: left;">
			<input type="button" class="btn" id="btnBackToOverview" value="<?php echo $lang["buttonBackToOverview"];?>" onclick="window.location='?p=quiz';"/>
		</div>
		<div style="float: right;">
			<input type="button" class="btn" id="btnStartQuiz" value="<?php echo $lang["startQuiz"];?>" onclick="window.location='<?php echo "?quiz=" . $fetchExec["exec_token"];?>'"/>
		</div>
	</div>
</div>