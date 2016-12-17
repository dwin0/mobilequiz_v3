<?php 
	include_once 'errorCodeHandler.php';
	include_once 'config/executionDefaultValues.php';
	$maxCharactersExecution = 30;
	
	$code = '';
	$mode = "create";
	if(isset($_GET["mode"]))
	{
		$mode = $_GET["mode"];
	}
	
	$execId = -1;
	if(isset($_GET["execId"]))
	{
		$execId = $_GET["execId"];
	}
	
	$quizId = -1;
	if(isset($_GET["quizId"]))
	{
		$quizId = $_GET["quizId"];
	}
	
	$fromSite = '';
	if(isset($_GET["fromsite"]))
	{
		$fromSite = $_GET["fromsite"];
	}
	
	if($_SESSION["role"]["user"])
	{
		if(! $_SESSION["role"]["creator"])
		{
			$code = -3;
		}
	}
	else
	{
		$code = -4;
	}
	
	if($mode == 'edit' && $code == '') 
	{
		$stmt = $dbh->prepare("select * from execution where id = :execId");
		$stmt->bindParam(":execId", $execId);
		$stmt->execute();
		if($stmt->rowCount() != 1)
		{
			$code = -2;
		}
		$fetchExecution = $stmt->fetch(PDO::FETCH_ASSOC);
	} else if ($mode == 'create' && $code == '') {
		$stmt = $dbh->prepare("insert into execution (creation_date, last_modified) values (".time().", ".time().")");
		$stmt->execute();
		$newExecId = $dbh->lastInsertId();
		
		$stmt = $dbh->prepare("insert into qunaire_exec (questionnaire_id, execution_id) values (:quizId, :execId)");
		$stmt->bindParam(":quizId", $quizId);
		$stmt->bindParam(":execId", $newExecId);
		if(!$stmt->execute())
		{
			$code = -1;
		}
	}
	
	$errorCode = new mobileError("", "red");
	if($code != '')
	{
		$errorCode = handleCreateEditExecutionError($code);
	}
?>


<link rel="stylesheet" type="text/css" href="css/style.css" />
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $mode == "create" ? $lang["addNewExecution"] : str_replace("[1]", '&laquo;' . $fetchExecution["name"] . '&raquo;', $lang["editExecution"]);?></h1>
	</div>
	
	<?php if($code != '') {?>
	<p style="color:<?php echo $errorCode->getColor();?>"><?php echo $errorCode->getText();?></p>
	<?php }?>
	<p><?php echo $lang["requiredFields"];?></p>
	
	<ul id="createEditExecutionTab" class="nav nav-tabs">
        <li class="active"><a href="#generalInformation" data-toggle="tab"><?php echo $lang["generalInformations"];?></a></li>
        <li><a href="#participation" data-toggle="tab"><?php echo $lang["participant"];?></a></li>
        <li><a href="#settings" data-toggle="tab"><?php echo $lang["settings"];?></a></li>
        <li><a href="#publication" data-toggle="tab"><?php echo $lang["publication"];?></a></li>
    </ul>
    
    
    
    <div id="createEditExecutionTabContent" class="tab-content" >
        <div class="tab-pane fade in active form-horizontal panel-body" id="generalInformation">
   	 		
   	 		<!-- Execution Name -->
   	 		<div class="form-group">
				<label for="executionName" class="col-md-2 col-sm-3 control-label">
					<?php echo $lang["executionName"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<input id="executionName" name="executionName" class="form-control" type="text"
						required="required"
						placeholder="<?php echo $lang["executionCreateName"] . " (" . $lang["maximum"] . " " . $maxCharactersExecution . " " . $lang["characters"] . ")";?>"
						maxlength="<?php echo $maxCharactersExecution;?>" value="<?php 
						if($mode == "edit")
						{
							echo $fetchExecution["name"];
						}
						?>"/>
				</div>
			</div>
   	 		
   	 		<!-- Execution Priority -->
   	 		<div class="form-group">
				<div class="col-md-2 col-sm-3 control-label">
					<label for="quizPriority"><?php echo $lang["quizPriority"];?>*</label>
				</div>
				<div class="col-md-10 col-sm-9">
					<?php 
					$resultChecked = 0;
					if($mode == "edit")
					{
						$resultChecked = $fetchExecution["priority_id"];
					}
					
					$fetchUserPriority = '';
					$stmt = $dbh->prepare("select * from priority_settings where priority_id = :priorityId and user_id = :userId");
					$stmt->bindParam(":priorityId", $resultChecked);
					$stmt->bindParam(":userId", $_SESSION["id"]);
					$stmt->execute();
					if($stmt->rowCount() == 1)
					{
						$fetchUserPriority = $stmt->fetch(PDO::FETCH_ASSOC);
					}
					?>
					<select id="quizPriority" name="quizPriority" class="form-control" style="width: 195px; display: inline;" required="required">
						<option value="0" <?php echo $resultChecked == 0 ? 'selected' : '';?>><?php echo $lang["prioLearningHelp"];?></option>
						<option value="1" <?php echo $resultChecked == 1 ? 'selected' : '';?>><?php echo $lang["prioExamRequirement"];?></option>
						<option value="2" <?php echo $resultChecked == 2 ? 'selected' : '';?>><?php echo $lang["prioExam"];?></option>
					</select>
				</div>
			</div>
			
			<!-- Execution Participation Period -->
   	 		<div class="form-group">
				<div class="col-md-2 col-sm-3 control-label">
					<label><?php echo $lang["executionPeriod"];?> *</label>
				</div>
				<div class="col-md-10 col-sm-6">
					<?php 
					if($mode == "edit")
					{
						$noParticipationPeriod2 = $fetchExecution["noParticipationPeriod"];
					} else if($mode == "create" && $fetchUserPriority != '')
					{
						$noParticipationPeriod2 = $fetchUserPriority["noParticipationPeriod"];
					}
					?>
					<label for="noParticipationPeriod1" class="radio-inline"> 
					<input type="radio" id="noParticipationPeriod1" name="noParticipationPeriod" onchange="setDatesEnabled()"
						value="1" <?php echo $noParticipationPeriod2 == 1 ? 'checked':'';?> /> <?php echo $lang["noParticipationPeriod3"];?>
					</label>
				</div>
				<div class="col-md-5 col-sm-7">
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
							$displayedTime = $fetchExecution["starttime"];
						}
						echo date("d.m.Y", $displayedTime);
						?>" class="form-control" required="required"/>
					<input type="time" id="startTime" name="startTime" style="width: 90px; display: inline;"
						value="<?php echo date("H:i", $displayedTime); ?>" class="form-control" required="required"/> (h:min)
					<br />
					<?php echo $lang["quizEndDate"];?>
					<input type="text" id="endDate" name="endDate"
						style="width: 120px; display: inline;"
						value="<?php 
						$displayedTime = strtotime('+1 Week');
						if($mode == "edit")
						{
							$displayedTime = $fetchExecution["endtime"];
						}
						echo date("d.m.Y", $displayedTime);
						?>"
						class="form-control" required="required"/> 
					<input type="time" id="endTime" name="endTime" style="width: 90px; display: inline;"
						value="<?php echo date("H:i", $displayedTime);?>" class="form-control" required="required"/> (h:min)
					</label>
				</div>
				<div class="col-md-2 col-sm-2">
					<!-- TODO: Logik -->
					<input type="button" class="btn" id="resetToStandardParticipationPeriod" value="<?php echo $lang["buttonSetBack"]; ?>" style="max-width: 185px; margin-top: 5px;" />
				</div>
			</div>
    	</div>
    	
    	
    	<div class="tab-pane form-horizontal panel-body" id="participation">
    		
    		<!-- Execution Group Management -->
			<fieldset class="table-border">
				<legend class="table-border"><?php echo $lang["profileJoinGroupHeading"];?></legend>
				<div class="form-group assignationMngmt">
					<div> 
						<label class="col-md-2 col-sm-3 control-label"><?php echo $lang["assignGroupToExecution"];?>
						<img id="assignGroupHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 5px;" original-title="Hier k&ouml;nnen Gruppen eingetragen werden, welche dieser 
						Durchf&uuml;hrung zugewiesen werden sollen. Nur wer zugewiesen ist, kann am Quiz teilnehmen." width="18" height="18"></label>
					</div>
					<div class="col-md-10 col-sm-9">
						<input type="text" id="autocompleteGroups"><img id="addGroup" style="margin-left: 8px; cursor: pointer" alt="add" src="assets/arrow-right.png" width="28" height="32" onclick="">
					</div>
				</div>
				<div class="from-group">
					<div class="col-md-10 col-sm-9" id="ajaxAnswerGroup"></div>
				</div>
				<div class="table-responsive">
					<!-- TODO: Logik -->
					<table class="assignGroupTbl" id="assignGroupTbl">
			            <thead>
			                <tr>
			                    <th><?php echo $lang["groupName"];?></th>
			                    <th><?php echo $lang["quizTableActions"];?></th>
			                </tr>
			            </thead>
			            <tbody>
			            	<!-- TODO: Logik -->
			            </tbody>
		        	</table>
	        	</div>
        	</fieldset>
        	
        	<!-- Execution Participant Management -->
        	<fieldset class="table-border">
				<legend class="table-border"><?php echo $lang["participantManagement"];?></legend>
	        	<div class="form-group assignationMngmt">
					<div> 
						<label class="col-md-2 col-sm-3 control-label"><?php echo $lang["assignParticipantToExecution"];?>
						<img id="assignParticipantHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 5px;" original-title="Hier k&ouml;nnen einzelne Teilnehmer eingetragen werden, 
						welche dieser Durchf&uuml;hrung zugewiesen werden sollen. Nur wer zugewiesen ist, kann am Quiz teilnehmen." width="18" height="18"></label>
					</div>
					<div class="col-md-10 col-sm-9">
						<input type="text" id="autocompleteUsers"><img id="addUser" style="margin-left: 8px; cursor: pointer" alt="add" src="assets/arrow-right.png" width="28" height="32" onclick="">
					</div>
				</div>
				<div class="from-group">
					<div class="col-md-10 col-sm-9" id="ajaxAnswerParticipant"></div>
				</div>
				<div class="table-responsive">
					<!-- TODO: Logik -->
					<table class="assignUserTbl" id="assignUserTbl">
			            <thead>
			                <tr>
			                    <th><?php echo $lang["participant"];?></th>
			                    <th><?php echo $lang["quizTableActions"];?></th>
			                </tr>
			            </thead>
			            <tbody>
			            	<!-- TODO: Logik -->
			            </tbody>
		        	</table>
	        	</div>
        	</fieldset>
			
			<!-- alter Code
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
			-->
    	</div>
    	
    	
    	<div class="tab-pane form-horizontal panel-body" id="settings">
    		<!-- Execution Time Limitation -->
			<div class="form-group" style="margin-bottom: 25px;">
				<div class="col-md-2 col-sm-3 control-label">
					<label><?php echo $lang["quizTimeLimitation"];?>*</label>
				</div>
				<div class="col-md-4 col-sm-6">
					<?php 
						$timeLimit = "00:00";
						$noLimitChecked = true;
						if($mode == "edit")
						{
							if($fetchExecution["limited_time"] != 0)
							{
								$timeLimit = gmdate("i:s", $fetchExecution["limited_time"]);
								$noLimitChecked = false;
							}
						}
					?>
					<label for="radioNoneTimeLimit" class="radio-inline"> 
					<input type="radio" id="radioNoneTimeLimit" name="timeLimitMode"
						value="0" <?php echo $noLimitChecked ? 'checked':'';?> /> <?php echo $lang["noLimitation"];?>
	                </label>
					<label for="radioQuizTimeLimit" class="radio-inline" style="margin-left: 22px;">
						<input style="margin-top: 11px;" type="radio" id="radioQuizTimeLimit" name="timeLimitMode" value="1" <?php echo $noLimitChecked ? '':'checked';?> /> 
						<input type="time" id="quizTimeLimit" class="form-control" name="quizTimeLimit" style="width: 90px; display: inline;" 
							value="<?php echo $timeLimit;?>" onfocus="setChecked('radioQuizTimeLimit')"/> (min:s)
					</label>
				</div>
				<div class="col-md-6 col-sm-2">
					<!-- TODO: Logik -->
					<input type="button" class="btn" id="resetToStandardTimeLimitation" value="<?php echo $lang["buttonSetBack"]; ?>" style="max-width: 185px; margin-top: 5px;" />
				</div>
			</div>
			
			<!-- Execution Amount Of Questions -->
			<div class="form-group" style="margin-bottom: 25px;">
				<div class="col-md-2 col-sm-3 control-label">
					<label><?php echo $lang["amountOfQuestions"];?>*</label>
				</div>
				<div class="col-md-4 col-sm-6">
					<?php 
						$questionAmount = "0";
						$questionAmountChecked = true;
						if($mode == "edit")
						{
							if($fetchExecution["amount_of_questions"] != 0)
							{
								$questionAmount = $fetchExecution["amount_of_questions"];
								$questionAmountChecked = false;
							}
						}
					?>
					<label for="radioNoneQuestionAmount" class="radio-inline"> 
						<input type="radio" id="radioNoneQuestionAmount" name="amountQuestionMode"
							value="0" <?php echo $questionAmountChecked ? 'checked':'';?> /> <?php echo $lang["noQuestionAmount"];?>
	                </label>
					<label for="radioNoneQuestionMode" class="radio-inline">
						<input style="margin-top: 11px;" type="radio" id="radioNoneQuestionMode" name="amountQuestionMode" value="1" <?php echo $questionAmountChecked ? '':'checked';?> />
						<input type="number" id="amountOfQuestions" name="amountOfQuestions" class="form-control" style="width: 90px; display: inline;" value="<?php 
							echo $questionAmount;
						?>" required="required"  onfocus="setChecked('radioNoneQuestionMode')"/>
					</label>
				</div>
				<div class="col-md-6 col-sm-2">
					<!-- TODO: Logik -->
					<input type="button" class="btn" id="resetToStandardAmountOfQuestions" value="<?php echo $lang["buttonSetBack"]; ?>" style="max-width: 185px; margin-top: 5px;" />
				</div>
			</div>
			
			<!-- Execution Amount of Participations -->
			<div class="form-group" style="margin-bottom: 25px;">
				<div class="col-md-2 col-sm-3 control-label"> 
					<label><?php echo $lang["amountMaxParticipations"];?>*</label>
				</div>
				<div class="col-md-4 col-sm-6">
					<?php 
						$maxParticipations = "1";
						$maxParticipationsChecked = true;
						if($mode == "edit")
						{
							if($fetchExecution["amount_participations"] != 0)
							{
								$maxParticipations = $fetchExecution["amount_participations"];
								$maxParticipationsChecked = false;
							}
						}
					?>
					<label for="radioNoneMaxParticipations" class="radio-inline"> 
					<input type="radio" id="radioNoneMaxParticipations" name="maxParticipationsMode"
						value="0" <?php echo $maxParticipationsChecked ? 'checked':'';?> /> <?php echo $lang["maxParticipations"];?>
	                </label>
					<label for="maxParticipationsMode" class="radio-inline">
						<input style="margin-top: 11px;" type="radio" id="maxParticipationsMode" name="maxParticipationsMode" value="1" <?php echo $maxParticipationsChecked ? '':'checked';?> />
						<input type="number" id="maxParticipations" name="maxParticipations" class="form-control" style="width: 90px; display: inline;" value="<?php 
							echo $maxParticipations;
						?>" min="1" required="required" onfocus="setChecked('maxParticipationsMode')"/>
					</label>
				</div>
				<div class="col-md-6 col-sm-2">
					<!-- TODO: Logik -->
					<input type="button" class="btn" id="resetToStandardAmountOfParticipations" value="<?php echo $lang["buttonSetBack"]; ?>" style="max-width: 185px; margin-top: 5px;" />
				</div>
			</div>
			
			<!-- Execution Percent Needed To Pass -->
			<div class="form-group" style="margin-bottom: 25px;">
				<div class="col-md-2 col-sm-3 control-label"> 
					<label><?php echo $lang["quizPassed"];?>*</label>
				</div>
				<div class="col-md-4 col-sm-6">
					<?php 
						$quizPassed = "80";
						$quizPassedChecked = false;
						if($mode == "edit")
						{
							if($fetchExecution["quiz_passed"] != 0)
							{
								$quizPassed = $fetchExecution["quiz_passed"];
								$quizPassedChecked = false;
							} else {
								$quizPassed = 0;
								$quizPassedChecked = true;
							}
						}
					?>
					<label for="radioNoneQuizPassed" class="radio-inline"> 
					<input type="radio" id="radioNoneQuizPassed" name="quizPassedMode"
						value="0" <?php echo $quizPassedChecked ? 'checked':'';?> /> <?php echo $lang["noPassing"];?>
	                </label>
					<label for="quizPassedMode" class="radio-inline">
						<input style="margin-top: 11px;" type="radio" id="quizPassedMode" name="quizPassedMode" value="1" <?php echo $quizPassedChecked ? '':'checked';?> />
						<input type="number" id="quizPassed" name="quizPassed" class="form-control" style="width: 90px; display: inline;" value="<?php 
							echo $quizPassed;
						?>" required="required"  onfocus="setChecked('quizPassedMode')"/>%
					</label>
				</div>
				<div class="col-md-6 col-sm-2">
					<!-- TODO: Logik -->
					<input type="button" class="btn" id="resetToStandardPercentToPass" value="<?php echo $lang["buttonSetBack"]; ?>" style="max-width: 185px; margin-top: 5px;" />
				</div>
			</div>
			
			<!-- Execution Random Order -->
			<div class="form-group" style="margin-bottom: 25px;">
				<div class="col-md-2 col-sm-3 control-label"> 
					<label><?php echo $lang["randomTitle"];?></label>
				</div>
				<div class="col-md-4 col-sm-6">
					<div>
						<div class="checkbox" style="margin-top: 0">
							<label> <input type="checkbox" name="randomizeQuestions"
								value="1" <?php 
								if($mode == "edit")
									{ echo $fetchExecution["random_questions"]==1 ? 'checked':''; }
								else 
									{ echo "checked"; }
								?> /><?php echo $lang["randomQuestions"];?>
		                    </label>
						</div>
					</div>
					<div>
						<div class="checkbox">
							<label> <input type="checkbox" name="randomizeAnswers" value="1"
								<?php
								if($mode == "edit")
									{ echo $fetchExecution["random_answers"]==1 ? 'checked':''; }
								else
									{ echo "checked"; }
								?> /><?php echo $lang["randomAnswers"];?>
		                    </label>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-sm-2">
					<!-- TODO: Logik -->
					<input type="button" class="btn" id="resetToStandardRandomOrder" value="<?php echo $lang["buttonSetBack"]; ?>" style="max-width: 185px; margin-top: 5px;" />
				</div>
			</div>
			
			<!-- Single Choice Multiplier -->
			<div class="form-group">
				<div class="col-md-2 col-sm-3 control-label"> 

					<label><?php echo $lang["singlechoiceMult"];?>*
						<img id="singlechoiceMultHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 5px;" original-title="Um Singlechoicefragen gegen&uuml;ber Multiplechoicefragen nicht abzuwerten k&ouml;nnen diese mit einem Multiplizierer aufgewertet werden" width="18" height="18">
					</label>

				</div>
				<div class="col-md-4 col-sm-6">
					<?php 
					$singlechoiceMultiplier = 2;
					if($mode == "edit")
					{
						$singlechoiceMultiplier = $fetchExecution["singlechoice_multiplier"];
					}
					?>
					<input type="number" id="singlechoiceMult" name="singlechoiceMult" class="form-control" style="width: 90px; display: inline;"
						value="<?php echo $singlechoiceMultiplier; ?>" required="required" />
				</div>
				<div class="col-md-6 col-sm-2">
					<!-- TODO: Logik -->
					<input type="button" class="btn" id="resetToStandardSingleChoiceMultiplier" value="<?php echo $lang["buttonSetBack"]; ?>" style="max-width: 185px; margin-top: 5px;" />
				</div>
			</div>	
    	</div>
    	

    	<div class="tab-pane form-horizontal panel-body" id="publication">
    		
    		<!-- Publication -->
			<div class="form-group">
				<div class="col-md-2 col-sm-3 control-label">
					<label> 
						<?php echo $lang["publication"];?> *
					</label>
				</div>
				<div class="col-md-4 col-sm-6">
					<label class="radio-inline"> <input type="radio" name="isPrivate" value="0" required 
						<?php if($mode == "create") { echo "checked"; }
							  else if($mode == "edit") {
								  if($fetchExecution["public"] == 0) { echo " checked"; }
						}?>/> <?php echo $lang["public"];?>
					</label> 
					<label class="radio-inline" style="white-space: pre;"> <input type="radio" name="isPrivate" value="1"
						<?php if($mode == "edit") {
								  if($fetchExecution["public"] == 1) { echo " checked"; }
						}?>/><?php echo $lang["privateMoreInfo"];?>
					</label>
				</div>
				<div class="col-md-6 col-sm-2">
					<!-- TODO: Logik -->
					<input type="button" class="btn" id="resetToStandardPublication" value="<?php echo $lang["buttonSetBack"]; ?>" style="max-width: 185px; margin-top: 5px;" />
				</div>
			</div>
			
			<!-- Execution Show Points / Result In Report -->		
			<div class="form-group">
				<div class="col-md-2 col-sm-3 control-label"> 
					<label><?php echo $lang["quizResultShow"];?></label>
				</div>
				<div class="col-md-4 col-sm-6">
                	<?php 
                    	
                    $resultChecked = 1;
                    $pointsChecked = 1;
                    if($mode == "edit")
                    {
                    	$resultChecked = $fetchExecution["result_visible"];
                    	$pointsChecked = $fetchExecution["result_visible_points"];
                    }
                    ?>
                    
                    <?php echo $lang["showPointsOption"] . ":";?><br />
                    <label class="radio-inline">
                    	<input type="radio" name="reportAfterQuizPoints" value="1" id="reportAfterQuizPoints1" <?php echo $pointsChecked == 1 ? 'checked':'';?>/>
                    	 <?php echo $lang["yes"];?>
                    </label>
                    <label class="radio-inline">
                    	<input type="radio" name="reportAfterQuizPoints" value="2" id="reportAfterQuizPoints2" <?php echo $pointsChecked == 2 ? 'checked':'';?>/>
                    	<?php echo $lang["no"];?>
                    </label>
                    
                    <br /><br />
                    <div>
	                   	<?php echo $lang["showDetailedInformationOption"] . ":";?>
	                   	<br />
	                   	
                    	<label class="radio" style="font-weight: normal">
                    		<input type="radio" name="reportAfterQuizResults" value="3" id="reportAfterQuizResults3" <?php echo $resultChecked == 3 ? 'checked':'';?>/>
                    		<?php echo $lang["showResultNever"];?>
                    	</label>
                    	
						<label class="radio" style="font-weight: normal">
							<input type="radio" name="reportAfterQuizResults" value="2" id="reportAfterQuizResults2" <?php echo $resultChecked == 2 ? 'checked':'';?>/>
							<?php echo $lang["showResultAtTheEnd"];?>
						</label>
						
						<label class="radio" style="font-weight: normal">
							<input type="radio" name="reportAfterQuizResults" value="1" id="reportAfterQuizResults1" <?php echo $resultChecked == 1 ? 'checked':'';?>/>
							<?php echo $lang["showResultAtTheEndDetailed"];?>
						</label>
					</div>
    			</div>
    			<div class="col-md-6 col-sm-2">
					<!-- TODO: Logik -->
					<input type="button" class="btn" id="resetToStandardShowResults" value="<?php echo $lang["buttonSetBack"]; ?>" style="max-width: 185px; margin-top: 5px;" />
				</div>
    		</div>
    		
    		<!-- Execution Show Taskpaper -->
    		<div class="form-group">
				<div class="col-md-2 col-sm-3 control-label"> 
					<label><?php echo $lang["showQuizTaskPaperSelection"];?>*
						<img id="showQuizTaskPaperHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 5px;" original-title="Wenn eingeschalten, k&ouml;nnen die Aufgabenbl&auml;tter nur eingesehen werden, wenn mind. einmal dran teilgenommen wurde, ansonsten immer." width="18" height="18">
					</label>
				</div>
				<div class="col-md-4 col-sm-6">
					<?php 
					$showQuizTaskPaper = 0;
					if($mode == "edit")
					{
						$showQuizTaskPaper = $fetchExecution["showTaskPaper"];
					}
					?>
					<label class="radio-inline">
                    	<input type="radio" name="showQuizTaskPaper" value="1" id="showQuizTaskPaper1" <?php echo $showQuizTaskPaper == 1 ? 'checked':'';?>/>
                    	 <?php echo $lang["yes"];?>
                    </label>
                    <label class="radio-inline">
                    	<input type="radio" name="showQuizTaskPaper" value="2" id="showQuizTaskPaper2" <?php echo $showQuizTaskPaper == 0 ? 'checked':'';?>/>
                    	<?php echo $lang["no"];?>
                    </label>	
				</div>
				<div class="col-md-6 col-sm-2">
					<!-- TODO: Logik -->
					<input type="button" class="btn" id="resetToStandardShowTaskpaper" value="<?php echo $lang["buttonSetBack"]; ?>" style="max-width: 185px; margin-top: 5px;" />
				</div>
			</div>
    	</div>
    </div>   
	
	<div style="float: left; margin-top: 10px;">
		<input type="button" class="btn" id="btnBackToCreateEditQuiz" value="<?php echo $lang["btnBack"];?>" onclick="window.location='?p=quiz';"/> <!-- TODO: richtige location setzen -->
	</div>
	<div style="float: right; padding-left: 10px; margin-top: 10px;">
		<input type="button" class="btn" id="btnSaveAndCreateNewExecution" value="<?php echo $lang["createNextExecution"];?>" /> <!-- TODO: Logik für den klick hinzufügen -->
	</div>
	
</div>

<div id="snackbar">Some text some message..</div>


<script type="text/javascript" src="js/bootstrap-tabcollapse.js"></script>
<script type="text/javascript">

	function setChecked(id)
	{
		$('#' + id).prop('checked', true);
	}

	function setDatesEnabled()
	{
		var val = false;
		if($("#noParticipationPeriod1").prop('checked'))
		{
			val = true;
		}
	
		var timeBoxes = ["startDate", "startTime", "endDate", "endTime"];
	
		$.each(timeBoxes, function()
		{
			$("#" + this).prop('readonly', val);
		});
	}

	
	$(function() {
		var tooltipElements = ['#singlechoiceMultHelp', '.groupName', '#showQuizTaskPaperHelp', '#assignParticipantHelp', '#assignGroupHelp'];

		$.each(tooltipElements, function(i, string){
			$(string).tipsy({gravity: 'n'});
		});

		$(document).ready(function() {
			
			$(document).on("change", "#executionName, #quizPriority, [name='noParticipationPeriod'], #startDate, #startTime, #endDate, #endTime", updateExecutionData);
			
		});
		
		setDatesEnabled();
		$("#groupAddSuccess").hide();
		$("#groupAddError").hide();

		$('#startDate').datepicker({
            format: "dd.mm.yyyy",
            startDate: "+0d",
            language: "de",
            orientation: "top left",
            autoclose: true,
            todayHighlight: true
        });
        
        $('#endDate').datepicker({
            format: "dd.mm.yyyy",
            startDate: "+0d",
            language: "de",
            orientation: "top left",
            autoclose: true,
            todayHighlight: true
        });

		// alter Code
// 		$( "#assignGroupToQuizSortable1, #assignGroupToQuizSortable2" ).sortable({
// 			connectWith: ".assignGroupToQuizCconnectedSortable"
// 		}).disableSelection();

// 		$( "#assignGroupToQuizSortable1, #assignGroupToQuizSortable2" ).sortable({
// 			stop: function( event, ui ) {
// 				console.log("changed");
// 				var assignedGroups = [];
// 				$( "#assignGroupToQuizSortable1 li").each(function(index, elem) {
// 					assignedGroups.push($(elem).attr('id'));
// 				});
// 				console.log("assignedGroups: " + JSON.stringify(assignedGroups));
// 				$.ajax({
// 					url: 'modules/actionHandler.php',
// 					type: "get",
//					data: "action=changeAssignedGroups&questionaireId="+<?php // echo isset($_GET["id"]) ? $_GET["id"] : -1;?>+"&groups="+JSON.stringify(assignedGroups),
// 					success: function(output) 
// 					{
// 						//alertify
// 						if(output == "ok") {
							
// 							console.log("success: " + output);
// 			                $("#groupAddSuccess").slideDown(1000);   
// 			                $("#groupAddSuccess").fadeTo(2000, 500).slideUp(1000);   
// 						} else {
// 							console.log("error: " + output);
// 			                $("#groupAddError").slideDown(1000);   
// 			                $("#groupAddError").fadeTo(2000, 500).slideUp(1000); 
// 						}
// 					}, error: function(output)
// 					{
// 						console.log("error: " + output);
// 		                $("#groupAddError").slideDown(1000);   
// 		                $("#groupAddError").fadeTo(2000, 500).slideUp(1000); 
// 					}
// 				});
// 			}
// 		});
		


		$('#assignGroupTbl').DataTable({
            'bSort': true,
            'bPaginate': false,
            'bLengthChange': false,
            'bInfo': false,
            'aoColumns': [
				{'bSearchable': true, 'bSortable': true},
				{'bSearchable': false, 'bSortable': false}
            ],
            "sDom": 'lfrtip',
            "oLanguage": {
                "sZeroRecords": "Es sind keine Gruppen zugewiesen worden",
                "sSearch": ""
            }
        });
        $('.dataTables_filter input').addClass("form-control");
        $('.dataTables_filter input').addClass("magnifyingGlass");
        $('.dataTables_filter input').attr("style", "min-width: 300px;");
        $('.dataTables_filter').attr("style", "margin-top: 0");
        $('.dataTables_wrapper').attr("style", "margin-bottom: 25px;");

        
        $('#assignUserTbl').DataTable({
            'bSort': true,
            'bPaginate': false,
            'bLengthChange': false,
            'bInfo': false,
            'aoColumns': [
				{'bSearchable': true, 'bSortable': true},
				{'bSearchable': false, 'bSortable': false}
            ],
            "sDom": 'lfrtip',
            "oLanguage": {
                "sZeroRecords": "Es sind keine Teilnehmer zugewiesen worden",
                "sSearch": ""
            }
        });
        $('.dataTables_filter input').addClass("form-control");
        $('.dataTables_filter input').addClass("magnifyingGlass");
        $('.dataTables_filter input').attr("style", "min-width: 300px;");
        $('.dataTables_filter').attr("style", "margin-top: 0");
        $('.dataTables_wrapper').attr("style", "margin-bottom: 25px;");

		
	});


	function updateExecutionData(event)
	{
		var maxCharactersExecName = 30;
		
		if(this.value == this.oldvalue) return;

		var target = event.target.id;
		if(target == "") {
			target = event.target.name;
		}

		var url = '?p=actionHandler&action=updateExecution';
		var field;
		var data = new FormData();

		switch(target) {
			case "executionName":
				field = "executionName";
			    data.append("executionName", event.target.value);
			    data.append("maxChar", maxCharactersExecName);
				break;
			case "quizPriority":
				field = "quizPriority";
				data.append("quizPriority", event.target.value);
				break;
			case "noParticipationPeriod0":
			case "noParticipationPeriod1":
				field = "noParticipationPeriod";
				data.append("noParticipationPeriod", event.target.value);
				break;
			case "startDate":
				field = "startDate";
				data.append("startDate", event.target.value);
				data.append("startTime", $('#startTime').val());
				break;
			case "startTime":
				field = "startTime";
				data.append("startTime", event.target.value);
				data.append("startDate", $('#startDate').val());
				break;
			case "endDate":
				field = "endDate";
				data.append("endDate", event.target.value);
				data.append("endTime", $('#endTime').val());
				break;
			case "endTime":
				field = "endTime";
				data.append("endTime", event.target.value);
				data.append("endDate", $('#endDate').val());
				break;
		}

		uploadChange(url, data, field);
	}

	function uploadChange(url, data, field) 
	{
		data.append("execId", "<?php echo ($mode == "edit") ? $execId : $newExecId;?>");
		data.append("mode", "<?php echo $mode;?>");
		
		$.ajax({
	        url: url + '&field=' + field,
	        type: 'POST',
	        data: data,
	        cache: false,
	        dataType: 'json',
	        processData: false,
	        contentType: false,
	        success: function(data)
	        {
				switch(data.status)
				{
					case "OK":
						showSnackbar("<?php echo $lang["saved"]?>");
						break;
					case "error":
						alert("Error: " + data.text);
						break;
				}
	        },
	        error: function()
	        {
	            console.log("Ajax couldn't send data");
	            alert("Ajax couldn't send data");
	        }
	    });
	}

	function showSnackbar(text) {
		var snackbar = $("#snackbar");
		snackbar.text(text);
	    snackbar.addClass("show");
	    setTimeout(function(){ snackbar.removeClass("show"); }, 3000);
	}
	
	$('#createEditExecutionTab').tabCollapse();
</script>