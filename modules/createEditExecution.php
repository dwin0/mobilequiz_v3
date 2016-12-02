<script type="text/javascript">

</script>


<script type="text/javascript" src="js/bootstrap-tabcollapse.js"></script>
<link rel="stylesheet" type="text/css" href="css/style.css" />
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $mode == "create" ? $lang["addNewExecution"] : str_replace("[1]", '&laquo;' . "Pseudoname TODO mit fetch ersetzen" . '&raquo;', $lang["editExecution"]);?></h1>
	</div>

	<p><?php echo $lang["requiredFields"];?></p>
	
	<ul id="myTab" class="nav nav-tabs">
        <li class="active"><a href="#generalInformation" data-toggle="tab">Allgemeine Informationen</a></li>
        <li><a href="#questions" data-toggle="tab">Fragen</a></li>
        <li><a href="#execution" data-toggle="tab">Durchf&uuml;hrungen</a></li>
    </ul>
    
    
    
    
    <div class="row">
					<div class="col-md-2 col-sm-2">
						<label><?php echo $lang["noParticipationPeriod2"];?> *</label>
					</div>
					<div class="col-md-2 col-sm-2 radio-inline">
						<?php 
						if($mode == "edit")
						{
							$noParticipationPeriod2 = $quizFetch["noParticipationPeriod"];
						}
						?>
						<label for="noParticipationPeriod1" class="radio-inline"> 
						<input type="radio" id="noParticipationPeriod1" name="noParticipationPeriod" onchange="setDatesEnabled()"
							value="1" <?php echo $noParticipationPeriod2 == 1 || $mode == "create" ? 'checked':'';?> /> <?php echo $lang["noParticipationPeriod3"];?>
						</label>
					</div>
					<div class="col-md-8 col-sm-8">
						<label for="noParticipationPeriod0" class="radio-inline"> 
						<input type="radio" id="noParticipationPeriod0" name="noParticipationPeriod" onchange="setDatesEnabled()"
							value="0" <?php echo $noParticipationPeriod2 == 0 ? 'checked':'';?> />
						<?php echo $lang["quizStartDate"];?>
						<input type="text" id="startDate" name="startDate" 
							style="width: 120px; display: inline;"
							value="<?php 
							$displayedTime = time();
							if($mode == "edit")
							{
								$displayedTime = $quizFetch["starttime"];
							}
							echo date("d.m.Y", $displayedTime);
							?>" class="form-control" required="required"/>
						<input type="time" id="startTime" name="startTime" onchange="setConfirm(true, 'starttime')"
							style="width: 90px; display: inline;"
							value="<?php echo date("H:i", $displayedTime); ?>" class="form-control" required="required"/> (h:min)
						<br />
						<?php echo $lang["quizEndDate"];?>
						<input type="text" id="endDate" name="endDate"
							style="width: 120px; display: inline;"
							value="<?php 
							$displayedTime = strtotime('+1 Week');
							if($mode == "edit")
							{
								$displayedTime = $quizFetch["endtime"];
							}
							echo date("d.m.Y", $displayedTime);
							?>"
							class="form-control" required="required"/> 
						<input type="time" id="endTime" onchange="setConfirm(true, 'endTime')"
							name="endTime" style="width: 90px; display: inline;"
							value="<?php echo date("H:i", $displayedTime);?>" class="form-control" required="required"/> (h:min)
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2">
						<label><?php echo $lang["quizTimeLimitation"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
							$timeLimit = "00:00";
							$noLimitChecked = true;
							if($mode == "edit")
							{
								if($quizFetch["limited_time"] != 0)
								{
									$timeLimit = gmdate("i:s", $quizFetch["limited_time"]);
									$noLimitChecked = false;
								}
							}
						?>
						<label for="radioNoneTimeLimit" class="radio-inline"> 
						<input type="radio" id="radioNoneTimeLimit" name="timeLimitMode" onchange="setConfirm(true, 'timelimit')"
							value="0" <?php echo $noLimitChecked ? 'checked':'';?> /> <?php echo $lang["noLimitation"];?>
		                </label>
						<label for="radioQuizTimeLimit" class="radio-inline" style="margin-left: 22px;">
							<input style="margin-top: 11px;" type="radio" id="radioQuizTimeLimit" onchange="setConfirm(true, 'timelimit2')" name="timeLimitMode" value="1" <?php echo $noLimitChecked ? '':'checked';?> /> 
							<input type="time" id="quizTimeLimit" class="form-control" onchange="setConfirm(true)" name="quizTimeLimit" style="width: 90px; display: inline;" 
								value="<?php echo $timeLimit;?>" onfocus="setChecked('radioQuizTimeLimit')"/> (min:s)
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2">
						<label><?php echo $lang["amountOfQuestions"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
							$questionAmount = "0";
							$questionAmountChecked = true;
							if($mode == "edit")
							{
								if($quizFetch["amount_of_questions"] != 0)
								{
									$questionAmount = $quizFetch["amount_of_questions"];
									$questionAmountChecked = false;
								}
							}
						?>
						<label for="radioNoneQuestionAmount" class="radio-inline"> 
							<input type="radio" id="radioNoneQuestionAmount" name="amountQuestionMode" onchange="setConfirm(true, 'questionamount')"
								value="0" <?php echo $questionAmountChecked ? 'checked':'';?> /> <?php echo $lang["noQuestionAmount"];?>
		                </label>
						<label for="radioNoneQuestionMode" class="radio-inline">
							<input style="margin-top: 11px;" type="radio" id="radioNoneQuestionMode" onchange="setConfirm(true, 'questionamount2')" name="amountQuestionMode" value="1" <?php echo $questionAmountChecked ? '':'checked';?> />
							<input type="number" id="amountOfQuestions" name="amountOfQuestions" onchange="setConfirm(true, 'questionamount3')" class="form-control" style="width: 90px; display: inline;" value="<?php 
								echo $questionAmount;
							?>" required="required"  onfocus="setChecked('radioNoneQuestionMode')"/>
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["amountMaxParticipations"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
							$maxParticipations = "1";
							$maxParticipationsChecked = true;
							if($mode == "edit")
							{
								if($quizFetch["amount_participations"] != 0)
								{
									$maxParticipations = $quizFetch["amount_participations"];
									$maxParticipationsChecked = false;
								}
							}
						?>
						<label for="radioNoneMaxParticipations" class="radio-inline"> 
						<input type="radio" id="radioNoneMaxParticipations" name="maxParticipationsMode" onchange="setConfirm(true, 'maxpart')"
							value="0" <?php echo $maxParticipationsChecked ? 'checked':'';?> /> <?php echo $lang["maxParticipations"];?>
		                </label>
						<label for="maxParticipationsMode" class="radio-inline">
							<input style="margin-top: 11px;" type="radio" id="maxParticipationsMode" onchange="setConfirm(true, 'maxpart')" name="maxParticipationsMode" value="1" <?php echo $maxParticipationsChecked ? '':'checked';?> />
							<input type="number" id="maxParticipations" name="maxParticipations" onchange="setConfirm(true, 'maxpart')" class="form-control" style="width: 90px; display: inline;" value="<?php 
								echo $maxParticipations;
							?>" min="1" required="required" onfocus="setChecked('maxParticipationsMode')"/>
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2">
						<label><?php echo $lang["quizPriority"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
						$resultChecked = 0;
						if($mode == "edit")
							$resultChecked = $quizFetch["priority"];
						?>
						<select name="quizPriority" class="form-control" style="width: 195px; display: inline;" onchange="setConfirm(true, 'priority')" required="required">
							<option value="0" <?php echo $resultChecked == 0 ? 'selected' : '';?>><?php echo $lang["prioLearningHelp"];?></option>
							<option value="1" <?php echo $resultChecked == 1 ? 'selected' : '';?>><?php echo $lang["prioExamRequirement"];?></option>
							<option value="2" <?php echo $resultChecked == 2 ? 'selected' : '';?>><?php echo $lang["prioExam"];?></option>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["quizPassed"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
							$quizPassed = "80";
							$quizPassedChecked = false;
							if($mode == "edit")
							{
								if($quizFetch["quiz_passed"] != 0)
								{
									$quizPassed = $quizFetch["quiz_passed"];
									$quizPassedChecked = false;
								} else {
									$quizPassed = 0;
									$quizPassedChecked = true;
								}
							}
						?>
						<label for="radioNoneQuizPassed" class="radio-inline"> 
						<input type="radio" id="radioNoneQuizPassed" name="quizPassedMode" onchange="setConfirm(true, 'passedmode')" 
							value="0" <?php echo $quizPassedChecked ? 'checked':'';?> /> <?php echo $lang["noPassing"];?>
		                </label>
						<label for="quizPassedMode" class="radio-inline">
							<input style="margin-top: 11px;" type="radio" id="quizPassedMode" onchange="setConfirm(true, 'passedmode2')" name="quizPassedMode" value="1" <?php echo $quizPassedChecked ? '':'checked';?> />
							<input type="number" id="quizPassed" name="quizPassed" onchange="setConfirm(true, 'passedmode3')" class="form-control" style="width: 90px; display: inline;" value="<?php 
								echo $quizPassed;
							?>" required="required"  onfocus="setChecked('quizPassedMode')"/>%
						</label>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["singlechoiseMult"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
						$singlechoiseMult = 2;
						if($mode == "edit")
						{
							$singlechoiseMult = $quizFetch["singlechoise_multiplier"];
						}
						?>
						<input type="number" id="singlechoiseMult" name="singlechoiseMult" onchange="setConfirm(true, 'singlechoise')" class="form-control" style="width: 90px; display: inline;" value="<?php 
							echo $singlechoiseMult; ?>" required="required" /><img id="singlechoiseMultHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 5px;" original-title="Um Singlechoisefragen gegen&uuml;ber Multiplechoisefragen nicht abzuwerten k&ouml;nnen diese mit einem Multiplizierer aufgewertet werden" width="18" height="18">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["showQuizTaskPaperSelection"];?>*</label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<?php 
						$showQuizTaskPaper = 0;
						if($mode == "edit")
						{
							$showQuizTaskPaper = $quizFetch["showTaskPaper"];
						}
						?>
						<label for="showQuizTaskPaper" style="float: left;">
							<input type="checkbox" id="showQuizTaskPaper" name="showQuizTaskPaper" onchange="setConfirm(true, 'showQuizTaskPaper')" <?php if($showQuizTaskPaper == 1) echo "checked";?>/>
						</label>
						<img id="showQuizTaskPaperHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 10px; margin-top: 11px;" original-title="Wenn eingeschalten, k&ouml;nnen die Aufgabenbl&auml;tter nur eingesehen werden, wenn mind. einmal dran teilgenommen wurde, ansonsten immer." width="18" height="18">
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["moreOptions"];?></label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
						<div>
							<div class="checkbox">
								<label> <input type="checkbox" name="randomizeQuestions"
									value="1" <?php 
									if($mode == "edit")
										echo $quizFetch["random_questions"]==1 ? 'checked':'';
									else 
										echo "checked";
									?> /><?php echo $lang["randomQuestions"];?>
			                    </label>
							</div>
						</div>
						<div>
							<div class="checkbox">
								<label> <input type="checkbox" name="randomizeAnswers" value="1"
									<?php
									if($mode == "edit")
										echo $quizFetch["random_answers"]==1 ? 'checked':'';
									else
										echo "checked";
									?> /><?php echo $lang["randomAnswers"];?>
			                    </label>
							</div>
						</div>
						<div>
							<div class="checkbox">
								<label> <input type="checkbox" name="isPublic" value="1" 
								<?php 
								if($mode == "edit")
								{
									if($quizFetch["public"] == 1)
										echo "checked";
								} else {
									echo "checked";
								}
								?> /><?php echo $lang["quizPublic"];?>
			                    </label>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-sm-2"> 
						<label><?php echo $lang["quizResultShow"];?></label>
					</div>
					<div class="col-md-10 col-sm-10 radio-inline">
                    	<?php 
                    	
                    	$resultChecked = 1;
                    	$pointsChecked = 1;
                    	if($mode == "edit")
                    	{
                    		$resultChecked = $quizFetch["result_visible"];
                    		$pointsChecked = $quizFetch["result_visible_points"];
                    	}
                    	?>
                    	
                    	<?php echo $lang["showPointsOption"] . ":";?><br />
                    	<label class="radio-inline">
                    		<input type="radio" name="reportAfterQuizPoints" value="1" id="reportAfterQuizPoints1" <?php echo $pointsChecked == 1 ? 'checked':'';?>/>
                    		 <?php echo $lang["showResultAtTheEndPointsYes"];?>
                    	</label>
                    	<label class="radio-inline">
                    		<input type="radio" name="reportAfterQuizPoints" value="2" id="reportAfterQuizPoints2" <?php echo $pointsChecked == 2 ? 'checked':'';?>/>
                    		<?php echo $lang["showResultAtTheEndPointsNo"];?>
                    	</label>
                    	
                    	<br /><br />
                    	<div>
	                    	<?php echo $lang["showDetailedInformationOption"] . ":";?><br />
	                    	<label class="radio" style="font-weight: normal">
	                    		<input type="radio" name="reportAfterQuizResults" value="3" id="reportAfterQuizResults3" <?php echo $resultChecked == 3 ? 'checked':'';?>/> <?php echo $lang["showResultNever"];?>
	                    	</label>
							<label class="radio" style="font-weight: normal">
								<input type="radio" name="reportAfterQuizResults" value="2" id="reportAfterQuizResults2" <?php echo $resultChecked == 2 ? 'checked':'';?>/> <?php echo $lang["showResultAtTheEnd"];?>
							</label>
							<label class="radio" style="font-weight: normal">
								<input type="radio" name="reportAfterQuizResults" value="1" id="reportAfterQuizResults1" <?php echo $resultChecked == 1 ? 'checked':'';?>/> <?php echo $lang["showResultAtTheEndDetailed"];?>
							</label>
						</div>
					</div>
				</div>
    
    
    
    
    
    
    
    
    
    
    
    <div class="panel-body" id="assignQuizToGroup" style="padding: 0px;">
				<div class="alert alert-success alert-dismissable" id="groupAddSuccess" style="border-radius: 0 0 4px 4px;">
				    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
				    <strong>Erfolgreich! </strong>
				    Gruppe zugeordnet.
				</div>
				<div class="alert alert-danger alert-dismissable" id="groupAddError" style="border-radius: 0 0 4px 4px;">
				    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
				    <strong>Fehler! </strong>
				    Es ist ein Fehler aufgetreten.
				</div>
				<div class="form-horizontal">
					<div class="col-md-3 col-sm-3" style="margin: 20px 0px 20px 0px;"> 
						<label><?php echo $lang["assignQuizToGroupInfo1"];?></label>
						<div style="min-height: 75px; max-height: 200px; overflow-y: scroll;">
							<ul id="assignGroupToQuizSortable1" class="assignGroupToQuizCconnectedSortable">
								<?php 
								$stmt = $dbh->prepare("select assign_group_qunaire.*, `group`.name, (select count(*) from user where group_id = `group`.id) as memberCount from assign_group_qunaire inner join `group` on `group`.id = assign_group_qunaire.group_id where questionnaire_id = :qId");
								$stmt->bindParam(":qId", $_GET["id"]);
								$stmt->execute();
								$fetchAssignedGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
								
								for($i = 0; $i < count($fetchAssignedGroups); $i++)
								{
								?>
									<li class="ui-state-default groupName" original-title="<?php echo $fetchAssignedGroups[$i]["name"];?>" id="<?php echo $fetchAssignedGroups[$i]["group_id"];?>"><?php echo strlen($fetchAssignedGroups[$i]["name"]) > 19 ? substr($fetchAssignedGroups[$i]["name"], 0, 19) . "..." : $fetchAssignedGroups[$i]["name"]; echo " (" . $fetchAssignedGroups[$i]["memberCount"] . ")";?></li>
								<?php }?>
							</ul>
						</div>
					</div>
					<div class="col-md-1 col-sm-1" style="margin-top: 25px; text-align: center;"> 
						<img style="margin-left: 8px;" alt="drag and drop" src="assets/arrow-leftRight.png" width="56" height="32">
						<span style="font-size: 10px;">Drag &amp; Drop</span>
					</div>
					<div class="col-md-3 col-sm-3" style="margin: 20px 0px 20px 0px;"> 
						<label><?php echo $lang["assignQuizToGroupInfo2"];?></label>
						<div style="min-height: 75px; max-height: 200px; overflow-y: scroll;">
							<ul id="assignGroupToQuizSortable2" class="assignGroupToQuizCconnectedSortable">
								<?php 
								$stmt = $dbh->prepare("select *, (select count(*) from user where group_id = `group`.id) as memberCount  from `group` where id not in (select group_id from assign_group_qunaire where questionnaire_id = :qId)");
								$stmt->bindParam(":qId", $_GET["id"]);
								$stmt->execute();
								$fetchGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
								
								for($i = 0; $i < count($fetchGroups); $i++)
								{
								?>
									<li class="ui-state-default groupName" original-title="<?php echo $fetchGroups[$i]["name"];?>" id="<?php echo $fetchGroups[$i]["id"];?>"><?php echo strlen($fetchGroups[$i]["name"]) > 19 ? substr($fetchGroups[$i]["name"], 0, 19) . "..." : $fetchGroups[$i]["name"]; echo " (" . $fetchGroups[$i]["memberCount"] . ")"?></li>
								<?php }?>
							</ul>
						</div>
					</div>
				</div>
			</div>
    
    
    
    
    
    
    
    
    
    
    
</div>
