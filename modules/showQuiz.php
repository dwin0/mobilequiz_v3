<?php

if($_SESSION["role"]["user"] != 1)
{
	header("Location: ?p=home&code=-20");
	exit;
}

if(!isset($_GET["quizId"]))
{
	header("Location: ?p=quiz&code=-15");
	exit;
}

$stmt = $dbh->prepare("select questionnaire.name, noParticipationPeriod, description, starttime, endtime, last_modified, qnaire_token, firstname, lastname, email from questionnaire inner join user on user.id = questionnaire.owner_id inner join user_data on user_data.user_id = user.id where questionnaire.id = :quizId");
$stmt->bindParam(":quizId", $_GET["quizId"]);
if(!$stmt->execute())
{
	header("Location: ?p=quiz&code=-25");
	exit;
}
$fetchQuiz = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["quiz"] . " &laquo;" . $fetchQuiz["name"] . "&raquo;"?></h1>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo "Publikationslink - Stand vom " . date("d.m.Y H:i:s", $fetchQuiz["last_modified"]); ?></h3>
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
							<label class="col-md-3 col-sm-4 control-label"><?php echo $lang["maxPoints"];?></label>
							<div class="col-md-9 col-sm-8">
								<p class="form-control-static"><?php 
									$stmt = $dbh->prepare("select question.id, type_id, questionnaire.singlechoise_multiplier from question inner join qunaire_qu on qunaire_qu.question_id = question.id inner join questionnaire on questionnaire.id = qunaire_qu.questionnaire_id where qunaire_qu.questionnaire_id = :quizId");
									$stmt->bindParam(":quizId", $_GET["quizId"]);
									$stmt->execute();
									$fetchQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
									$totalPoints = 0;
									for($i = 0; $i < count($fetchQuestions); $i++)
									{
										if($fetchQuestions[$i]["type_id"] == 1)
											$totalPoints+= (1*$fetchQuestions[0]["singlechoise_multiplier"]);
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
									$stmt->bindParam(":quizId", $_GET["quizId"]);
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
									$stmt->bindParam(":quizId", $_GET["quizId"]);
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
									$stmt->bindParam(":quizId", $_GET["quizId"]);
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
						$showedLink = $quizLink . "/?quiz=" . $fetchQuiz["qnaire_token"];
						
						$text;
						if($fetchQuiz["noParticipationPeriod"]) {
							$text = $lang["showQuizForever"];
						} else {
							$text = $lang["showQuiz"];
						}
						
						$text = str_replace("[0]", "<a href=\"?quiz=" . $fetchQuiz["qnaire_token"] . "\">" . $showedLink . "</a>", $text);
						$text = str_replace("[1]", utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchQuiz["starttime"])), $text);
						$text = str_replace("[2]", utf8_encode(strftime("%d. %B %Y, %H:%M:%S", $fetchQuiz["endtime"]), $text));
						$text = str_replace("[3]", $fetchQuiz["firstname"] . " " . $fetchQuiz["lastname"], $text);
						$text = str_replace("[4]", $fetchQuiz["email"], $text);
						echo $text;
						?>
					</p>
				</div>
				<div class="col-md-4 col-sm-4">
					<img
						src="https://chart.apis.google.com/chart?chs=300x300&cht=qr&chld=H|0&chl=<?php echo $showedLink;?>"
						alt="QR code" widthHeight="300" widthHeight="300"
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
				<a href="https://sinv-56082.edu.hsr.ch/index.php?p=generatePDF&action=getQuizTaskPaper&quizId=<?php echo $_GET["quizId"];?>" target="_blank"><?php echo $lang["showPrintTasksheet"];?></a>
			</p>
		</div>
	</div>
	<div style="height: 20px;"></div>
	<div class="left" style="float: left;">
		<input type="button" class="btn" id="btnBackToOverview" value="<?php echo $lang["buttonBackToOverview"];?>" onclick="window.location='?p=quiz';"/>
	</div>
</div>
</div>