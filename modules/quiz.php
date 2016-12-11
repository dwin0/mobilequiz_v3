<?php 
	if($_SESSION["role"]["user"] != 1)
	{
		header("Location: ?p=home&code=-20");
		exit;
	}
	include "modules/extraFunctions.php";
	include_once 'errorCodeHandler.php';
	
	$errorCode = new mobileError("", "red");
	if(isset($_GET["code"]))
	{
		$errorCode = handleQuizError($_GET["code"]);
	}
	
	if($_GET["code"] < 0)
	{
		$file = "logs/errorLog.txt";
		$text = "Datum: " . date("d.m.Y H:i:s", time()) . "\nfromSite: ".$_SERVER['HTTP_REFERER']."\nCode: " . $_GET["code"] . "\nUsersession: " . $_SESSION["id"] . "\nQuizId: " . $_SESSION["quizSession"] . "\nSessionId: " . $_SESSION["idSession"] . "\nQuestionnumber: " . $_SESSION["questionNumber"] . "\n";
		$text .= "------------------------------\n";
		$fp = fopen($file, "a");
		fwrite($fp, $text);
		fclose($fp);
	}

	
	$selectedState = "all";
	$selectedLanguage = "all";
	$selectedTopic = "all";
	$selectedCreator = "all";
	if(isset($_POST["state"]))
	{
		$selectedState = $_POST["state"];
	}
	if(isset($_POST["language"]))
	{
		$selectedLanguage = $_POST["language"];
	}
	
	if($_POST["alreadyThere"] != "1")
	{
		$stmt = $dbh->prepare("selects subject_id from group inner join user_group on group.id = user_group.group_id where user_group.user_id = :userId and group.subject_id is not null");
		$stmt->bindParam(":userId", $_SESSION["id"]);
		$stmt->execute();
		$fetchUserInterestGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$numberOfUserInterests = count($fetchUserInterestGroups);
		for($i = 0; $i < $numberOfUserInterests; $i++)
		{
			array_push($selectedTopic, $fetchUserInterestGroups[$i]["subject_id"]);
		}
	}
	
	if(isset($_POST["topic"]))
	{
		$selectedTopic = $_POST["topic"];
	}
	if(isset($_POST["owner"]))
	{
		$selectedCreator = $_POST["owner"];
	}
?>
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["quizzes"];?></h1>
	</div>
	<p id="topicActionResult" style="color:<?php echo $errorCode->getColor();?>;"><?php echo $errorCode->getText();?></p>
	<div class="panel panel-default">
		<div class="panel-body">
		
			<?php if($_SESSION['role']['creator'] == 1) {?>
            <button id="btnAddQuiz" class="btn btn-success" type="button" style="width: 260px; height: 3em; float: left; margin-bottom: 1em; margin-top: 8px" onclick="window.location='?p=createEditQuiz';"><?php echo $lang["createQuiz"];?> <span class="glyphicon glyphicon-plus"></span></button>
	        <?php }?>
			
			<div style="width: 100%; margin-bottom: 1em;">
				<div id="searchBoxDiv" class="control-group" style="width: 260px; margin-left:auto; margin-right:0;">
					<label class="control-label" for="searchbox">
						<b><?php echo $lang["search"]; ?></b>
						<input type="search" id="searchbox" class="form-control input-sm magnifyingGlassstyle" 
						style="width: 260px" placeholder="<?php echo $lang["enterSearchTerm"];?>">
					</label>
				</div>
			</div>
		
			<form id="quizFilter" class="form-horizontal" action="?p=quiz" method="POST" style="clear: both">
				<input type="hidden" name="alreadyThere" value="1" />
			
				<fieldset class="table-border">
					<legend class="table-border" style="margin-bottom: -1em"><?php echo $lang["filterOptions"];?></legend>
					
					<!-- State FILTER -->
			        <div class="control-group">
			            <label class="control-label" for="state">
			                <?php echo $lang["state"]?>
			            </label>
			            <div class="controls">
			                <select id="state" multiple="multiple" class="form-control" name="state[]" onchange="sendData()">
			                    <option value="participated" <?php echo (in_array("participated", $selectedState)) ? 'selected="selected"' : '';?>><?php echo $lang["participated"];?></option>
			                    <option value="current" <?php echo (in_array("current", $selectedState)) ? 'selected="selected"' : '';?>><?php echo $lang["currend"];?></option>
			                    <option value="finished" <?php echo (in_array("finished", $selectedState)) ? 'selected="selected"' : '';?>><?php echo $lang["finished"];?></option>
			                </select>
			            </div>
			        </div>
			
					<!-- Language FILTER -->
			        <div class="control-group">
			            <label class="control-label" for="language">
			                <?php echo $lang["quizLanguage"]?>
			            </label>
			            <div class="controls">
			                <select id="language" multiple class="form-control" name="language[]" onchange="sendData()">
			                    <?php 
			                    $filterWhere = "";
			                    $filterWhereAnd = "";
			                    if($_SESSION["role"]["creator"] != 1)
			                    {
			                    	$filterWhere = " where execution.public = 1";
			                    	$filterWhereAnd = " and execution.public = 1";
			                    }
			                    		                	
			                    $stmt = $dbh->prepare("select language from questionnaire inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id inner join execution on qunaire_exec.execution_id = execution.id ". $filterWhere ." group by language");
			                    $stmt->execute();
			                    $result = $stmt->fetchAll();
			                    
			                    for($i = 0; $i < count($result); $i++){
									$stmt = $dbh->prepare("select questionnaire.id from questionnaire inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id inner join execution on qunaire_exec.execution_id = execution.id where questionnaire.language = '" . $result[$i]["language"] . "'" . $filterWhereAnd);
									$stmt->execute();
									$selected = (in_array($result[$i]["language"], $selectedLanguage)) ? 'selected="selected"' : '';
									echo "<option value=\"" . $result[$i]["language"] . "\"" . $selected . ">" . $result[$i]["language"] . " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")</option>";
			                    } ?>
			                </select>
			            </div>
			        </div>
			        
			        <!-- Topic FILTER -->
			        <div class="control-group">
			            <label class="control-label" for="topic">
			                <?php echo $lang["quizTopics"]?>
			            </label>
			            <div class="controls">
			                <select id="topic" multiple class="form-control" name="topic[]" onchange="sendData()">
			                    <?php 
			                    
			                    $stmt = $dbh->prepare("select subject_id from questionnaire inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id inner join execution on qunaire_exec.execution_id = execution.id".$filterWhere." group by subject_id");
			                    $stmt->execute();
			                    $result = $stmt->fetchAll();
			                    
			                    for($i = 0; $i < count($result); $i++){
									if($result[$i]["subject_id"] == null)
									{
										$stmt = $dbh->prepare("select questionnaire.id from questionnaire inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id inner join execution on qunaire_exec.execution_id = execution.id where subject_id is null" . $filterWhereAnd);
									}
									else 
									{
										$stmt = $dbh->prepare("select questionnaire.id from questionnaire inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id inner join execution on qunaire_exec.execution_id = execution.id where subject_id = " . $result[$i]["subject_id"] . $filterWhereAnd);
									}
									$stmt->execute();
									$rowCount = $stmt->rowCount();
									
									$stmt = $dbh->prepare("select name from subjects where id = " . $result[$i]["subject_id"]);
									$stmt->execute();
									$resultSubjectName = $stmt->fetchAll(PDO::FETCH_ASSOC);
									$selected = (in_array($result[$i]["subject_id"], $selectedTopic)) ? 'selected="selected"' : '';
									if($resultSubjectName[0]["name"] == null && $selectedTopic[0] == "null") {$selected = 'selected="selected"'; };
									$subjectName = ($resultSubjectName[0]["name"] == null) ? $lang["undefined"] : $resultSubjectName[0]["name"];
									$subjectId = ($result[$i]["subject_id"] == null) ? 'null' : $result[$i]["subject_id"];
									echo "<option value=\"" . $subjectId . "\" " . $selected . ">" . $subjectName . " (" . $rowCount . " " . $lang["quizzes"] . ")</option>";
			                    } ?>
			                </select>
			            </div>
			        </div>
			        
			        <!-- Owner FILTER -->
			        <div class="control-group">
			            <label class="control-label" for="owner">
			                <?php echo $lang["quizOwner"]?>
			            </label>
			            <div class="controls">
			                <select id="owner" multiple class="form-control" name="owner[]" onchange="sendData()">
			                    <?php 
			                    	
				                    $stmt = $dbh->prepare("select owner_id from questionnaire inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id inner join execution on qunaire_exec.execution_id = execution.id ".$filterWhere." group by owner_id");
				                    $stmt->execute();
				                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                    	
			                    	for($i = 0; $i < count($result); $i++){
										
										$stmt = $dbh->prepare("select firstname, lastname from user_data inner join user on user.id = user_data.user_id where user.id = " . $result[$i]["owner_id"]);
										$stmt->execute();
				                    	$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
				                    	
				                    	$stmt = $dbh->prepare("select questionnaire.id from questionnaire inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id inner join execution on qunaire_exec.execution_id = execution.id where owner_id = :owner_id" . $filterWhereAnd);
				                    	$stmt->bindParam(":owner_id", $result[$i]["owner_id"]);
				                    	$stmt-> execute();
				                    	$ownerRowCount = $stmt->rowCount();
				                    	
				                    	$selected = (in_array($result[$i]["owner_id"], $selectedCreator)) ? 'selected="selected"' : '';
										
										echo "<option value=\"" . $result[$i]["owner_id"] . "\" " . $selected . ">" . $fetchUser["firstname"] . " " . $fetchUser["lastname"] . " (" . $ownerRowCount . " " . $lang["quizzes"] . ")</option>";
			                    } ?>
			                </select>
			            </div>
			        </div>
			    </fieldset>
		        
		        
			    <div class="listOfQuizzes">
			        <table class="tblListOfQuizzes" id="quizzes" style="width: 100%">
			            <thead>
			                <tr>
			                    <th>
			                        <?php echo $lang["quizTableName"]?>
			                    </th>
			                    <th>
			                        <?php echo $lang["quizTableTopic"]?>
			                    </th>
			                    <th>
			                        <?php echo $lang["executionType"]?>
			                    </th>
			                    <th>
				                    <?php
				                    if($_SESSION['role']['creator'] == 1) {
				                    	echo $lang["percentParticipations"];
							        } else 
							        {
							        	echo $lang["userQuizState"];
							        }?>
			                    </th>
			                    <th>
			                        <?php echo $lang["quizTableState"]?>
			                    </th>
			                    <th>
			                        <?php echo $lang["quizTableActions"]?>
			                    </th>
			                </tr>
			            </thead>
			            <tbody>
			                <?php 
			                $whereStatement ="";
			                if($selectedState != "all" || $selectedLanguage != "all" || $selectedTopic != "all" || $selectedCreator != "all")
			                {
			                	$notFirst = false;
			                	$whereStatement = " where ";
			                	$noParticipationTime = false;
			                	if($selectedState != "all")
			                	{
			                		$numberOfSelected = count($selectedState);
			                		$time = time();
			                		$whereStatement .= "(";
			                		
			                		if(in_array('participated', $selectedState))
			                		{
			                			$whereStatement .= "(starttime > $time) ";
			                			if($numberOfSelected > 1)
			                			{
			                				$numberOfSelected--;
			                				$whereStatement .= "or ";
			                			}
			                		}
			                		if(in_array('current', $selectedState))
			                		{
			                			$whereStatement .= "((starttime < $time and endtime > $time) or noParticipationPeriod = 1) ";
			                			$noParticipationTime = true;
			                			if($numberOfSelected > 1)
			                			{
			                				$numberOfSelected--;
			                				$whereStatement .= "or ";
			                			}
			                		}
			                		if(in_array('finished', $selectedState))
			                		{
			                			$whereStatement .= "(endtime < $time and noParticipationPeriod <> 1) ";
			                			$noParticipationTime = true;
			                		}
			                		$notFirst = true;
			                		
			                		$whereStatement .= ") ";
			                	}
			                	if($selectedLanguage != "all")
			                	{
			                		if($notFirst)
			                		{
			                			$whereStatement .= " and ";
			                		}
			                		
			                		$whereStatement .= "(language = '$selectedLanguage[0]' ";
			                		
			                		$numberOfSelectedLanguages = count($selectedLanguage);
			                		for($i = 1; $i < $numberOfSelectedLanguages; $i++)
			                		{
			                			$whereStatement .= "or language = '$selectedLanguage[$i]' ";
			                		}
			                		
			                		$whereStatement .= ") ";
			                		
			                		$notFirst = true;
			                	}
			                	if($selectedTopic != "all")
			                	{
			                		if($notFirst)
			                		{
			                			$whereStatement .= " and ";
			                		}
			                		
			                		if($selectedTopic[0] == "null")
			                		{
			                			$whereStatement .= "(subject_id is null ";
			                		} else 
			                		{
			                			$whereStatement .= "(subject_id = $selectedTopic[0] ";
			                		}
			                		
			                		
			                		$numberOfSelectetTopics = count($selectedTopic);
			                		for($i = 1; $i < $numberOfSelectetTopics; $i++)
			                		{
			                			if($selectedTopic[$i] == "null")
			                			{
			                				$whereStatement .= "or subject_id is null ";
			                			} else
			                			{
			                				$whereStatement .= "or subject_id = $selectedTopic[$i] ";
			                			}
			                		}
			                		
			                		$whereStatement .= ") ";
			                		
			                		$notFirst = true;
			                	}
			                	if($selectedCreator != "all")
			                	{
			                		if($notFirst)
			                		{
			                			$whereStatement .= " and ";
			                		}
			                			
			                		$whereStatement .= "(owner_id = $selectedCreator[0] ";
			                		
			                		$numberOfSelectetCreators = count($selectedCreator);
			                		for($i = 1; $i < $numberOfSelectetCreators; $i++)
			                		{
			                			$whereStatement .= "or owner_id = $selectedCreator[$i] ";
			                		}
			                		
			                		$whereStatement .= ") ";
			                	}
			                }
			                
			                $queryStr = "select questionnaire.id as qId, questionnaire.language, questionnaire.name as qName, questionnaire.description, subjects.name as sName, 
			                    		execution.quiz_passed, starttime, endtime, owner_id, execution.priority_id, execution.public, execution.noParticipationPeriod, 
			                    		execution.result_visible from questionnaire left outer join subjects on questionnaire.subject_id = subjects.id 
			                    		inner join user on user.id = questionnaire.owner_id inner join qunaire_exec on qunaire_exec.questionnaire_id = questionnaire.id 
			                    		inner join execution on qunaire_exec.execution_id = execution.id" . $whereStatement;
			                $stmt = $dbh->prepare($queryStr);			                
			                $stmt->execute();
			                $fetchQuestionnaire = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                
			                for($i = 0; $i < count($fetchQuestionnaire); $i++) {
								if($fetchQuestionnaire[$i]["public"] != 1 && $fetchQuestionnaire[$i]["owner_id"] != $_SESSION["id"] && $_SESSION['role']['admin'] != 1 && !amIAssignedToThisQuiz($dbh, $fetchQuestionnaire[$i]["qId"]))
									continue;
							?>
			                    <tr class="entry" style="height: 75px" id="<?php echo "quiz_" . $fetchQuestionnaire[$i]["qId"];?>">
			                        <td title="<?php echo htmlspecialchars($fetchQuestionnaire[$i]["description"]);?>">
			                        	<?php if($_SESSION['role']['admin'] == 1 || $fetchQuestionnaire[$i]["owner_id"] == $_SESSION["id"] || amIAssignedToThisQuiz($dbh, $fetchQuestionnaire[$i]["qId"])) {
			                        		$eyePic = $fetchQuestionnaire[$i]["public"] != 1 ? 'closed' : 'open';
			                        		echo '<img alt="'.$eyePic.'" src="assets/icon_eye_' . $eyePic . '.png" width="13" height="10" class="eye_'.$eyePic.'" original-title="'.$lang["quiz_" . $eyePic].'">';}?>
			                            <p style="display: inline-block; width: 120px; word-wrap: break-word;"><?php echo " " . substr(htmlspecialchars($fetchQuestionnaire[$i]["qName"]), 0, 30);?></p>
			                            <p id="arrowDown" style="float: right; margin-right: 1em; display: none">&#9660;</p>
			                        </td>
			                        <td>
			                            <?php echo ($fetchQuestionnaire[$i]["sName"]==NULL) ? $lang["undefined"] : $fetchQuestionnaire[$i]["sName"];?>
			                        </td>
			                        <td>
			                        	<?php 
			                        	switch($fetchQuestionnaire[$i]["priority_id"])
			                        	{
			                        		case "0":
			                        			echo $lang["prioLearningHelp"];
			                        			break;
			                        		case "1":
			                        			echo $lang["prioExamRequirement"];
			                        			break;
			                        		case "2":
			                        			echo $lang["prioExam"];
			                        			break;
			                        	}
			                            ?>
			                        </td>
			                        <td style="white-space: nowrap;">
			                            <?php 
			                            
			                            if($_SESSION['role']['creator'] == 1) {
			                            	
			                            	//TODO: Anzahl Teilnahmen
			                            	
			                            	
			                            } else
			                            {
			                            	//TODO: Stimmt Berechnung? Muss nochmals kurz nachvollzogen werden!
			                            	$qId = $fetchQuestionnaire[$i]["qId"];
			                            	$stmt = $dbh->prepare("select execution_id from qunaire_exec where questionnaire_id = " . $qId);
			                            	$stmt->execute();
			                            	$execution_id = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                            	
			                            	$sessionId = $_SESSION["id"];
			                            	$execId = $execution_id[0]["execution_id"];
			                            	$stmt = $dbh->prepare("select * from user_exec_session where user_id = " . $sessionId . " and execution_id = " . $execId);
			                            	$test = $stmt->queryString;
			                            	if(!$stmt->execute())
			                            	{
			                            		header("Location: index.php?p=quiz&code=-14");
			                            		exit;
			                            	}
			                            	$ownParticipationAmount = $stmt->rowCount();
			                            	$fetchSession = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                            	                          	
			                            	$tmpPoints = null;
			                            	$fetchPoints = [0,0,0];
			                            	for ($j = 0; $j < count($fetchSession); $j++)
			                            	{
			                            		$tmpPoints = getPoints($dbh, $fetchQuestionnaire[$i]["qId"], $fetchSession[$j]["id"], 0);
			                            		if($j == 0 || $tmpPoints[0] >= $fetchPoints[0])
			                            		{
			                            			$fetchPoints = $tmpPoints;
			                            		}
			                            	}
			                            	 
			                            	if($fetchPoints[2] >= $fetchQuestionnaire[$i]["quiz_passed"] && $ownParticipationAmount != 0)
			                            	{
			                            		$hint = $lang["quizFinished"];
			                            	}
			                            	else
			                            	{
			                            		$hint = $lang["quizNotFinished"];
			                            	}
			                            	 
			                            	echo ($fetchQuestionnaire[$i]["result_visible"] != 3) ? $hint . " (" . $fetchPoints[2] . "%)" : $lang["notPublic"];
			                            }
			                            
			                            ?>
			                        </td>
			                        <td>
			                            <?php 
			                            $stmt = $dbh->prepare("select count(*) as count from qunaire_qu where questionnaire_id = :questionnaireId");
			                            $stmt->bindParam(":questionnaireId", $fetchQuestionnaire[$i]["qId"]);
			                            $stmt->execute();
			                            $fetchQuestionCount = $stmt->fetch(PDO::FETCH_COLUMN, 0);
			                            
			                            $starttime = $fetchQuestionnaire[$i]["starttime"];
			                            $endtime = $fetchQuestionnaire[$i]["endtime"];
			                            $str = "";
			                            $canParticipate = false;
			                            $taskPaperAvailable = true;
			                            
			                            if(time() < $starttime)
			                            	$str = $lang["quizStartsAt"] . date("d.m.Y H:i", $starttime);
			                            if(time() >= $starttime && time() <= $endtime)
			                            {
			                            	$str = $lang["quizEndsAt"] . date("d.m.Y H:i", $endtime);
			                            	$canParticipate = true;
			                            }
			                            if(time() > $endtime)
			                            	$str = $lang["quizClosed"] . date("d.m.Y H:i", $endtime);
			                            if($fetchQuestionnaire[$i]["noParticipationPeriod"] == 1)
			                            	$str = $lang["quizOpenForever"];
			                            echo $str;
			                            if($fetchQuestionnaire[$i]["noParticipationPeriod"] == 1)
			                            {
			                            	$canParticipate = true;
			                            }
			                            if($_SESSION["role"]["manager"])
			                            {
			                            	$canParticipate = true;
			                            }
			                            if($fetchQuestionCount < 1)
			                            {
			                            	$canParticipate = false;
			                            	$taskPaperAvailable = false;
			                            }
			                            if($fetchQuestionnaire[$i]["result_visible"] == 3 && ($fetchQuestionnaire[$i]["showTaskPaper"] == 0 && $ownParticipationAmount <= 0) && $_SESSION['role']['admin'] == 0 || (time() < $starttime && $fetchQuestionnaire[$i]["noParticipationPeriod"] == 0))
			                            {
			                            	$taskPaperAvailable = false;
			                            }
			                            ?>
			                            
			                        </td>
			                        <td style="width: 130px;">
			                        
			                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
										style="color: #373a3c; background-color: #fff; border-color: #ccc;"><?php echo $lang["action"];?></button>
										<div class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="position: relative; margin-left: -120px">
			                        
			                        <?php if($_SESSION['role']['admin'] == 1 || $fetchQuestionnaire[$i]["owner_id"] == $_SESSION["id"] || amIAssignedToThisQuiz($dbh, $fetchQuestionnaire[$i]["qId"])) {?>
											
										<a class="dropdown-item" href="?p=createEditQuiz&mode=edit&id=<?php echo $fetchQuestionnaire[$i]["qId"];?>"><span class="glyphicon glyphicon-pencil"></span> <?php echo $lang["editQuizAction"];?></a>
										<a class="dropdown-item" onclick="delQuiz(<?php echo $fetchQuestionnaire[$i]["qId"];?>)"><span class="glyphicon glyphicon-remove"></span> <?php echo $lang["delQuiz"];?></a>
										<a class="dropdown-item" href="?p=quizReport&id=<?php echo $fetchQuestionnaire[$i]["qId"];?>"><span class="glyphicon glyphicon-file"></span> <?php echo $lang["showQuizReport"];?></a>
				                        								
			                        <?php }?>
		                            	<?php if($canParticipate) {?>
			                            	<a class="dropdown-item" href="Pindex.php?p=participationIntro&quizId=<?php echo $fetchQuestionnaire[$i]["qId"];?>"><span class="glyphicon glyphicon-play-circle"></span> <?php echo $lang["participateQuiz"];?></a>
			                            <?php } ?>
			                            	<a class="dropdown-item" href="?p=showQuiz&quizId=<?php echo $fetchQuestionnaire[$i]["qId"];?>"><span class="glyphicon glyphicon-info-sign"></span> <?php echo $lang["showQuizInfo"];?></a>
			                            <?php
			                            if(($taskPaperAvailable && $ownParticipationAmount > 0) || $_SESSION['role']['admin'] == 1 || $fetchQuestionnaire[$i]["owner_id"] == $_SESSION["id"] || amIAssignedToThisQuiz($dbh, $fetchQuestionnaire[$i]["qId"]))
			                            {
			                            ?>
		                                	<a class="dropdown-item" href="?p=generatePDF&action=getQuizTaskPaper&quizId=<?php echo $fetchQuestionnaire[$i]["qId"];?>" target='_blank'><span class="glyphicon glyphicon-file"></span> <?php echo $lang["showTaskpaper"];?></a>
		                                <?php }
		                                if(((time() > $endtime || $fetchQuestionnaire[$i]["result_visible"] == 1) && $fetchQuestionnaire[$i]["result_visible"] != 3) && $ownParticipationAmount > 0) {?>
		                                	<a class="dropdown-item" href="?p=generatePDF&action=getQuizTaskPaperWithMyAnswers&quizId=<?php echo $fetchQuestionnaire[$i]["qId"];?>" target="_blank"><span class="glyphicon glyphicon-file"></span> <?php echo $lang["showTaskPaperWithSolution"];?></a>
		                                <?php }
		                                if($ownParticipationAmount > 0) {?>
		                                	<a class="dropdown-item" href="<?php echo "Pindex.php?p=participationOutro&quizId=" . $fetchQuestionnaire[$i]["qId"];?>"><span class="glyphicon glyphicon-file"></span> <?php echo $lang["showOwnParticipations"];?></a>
		                                <?php }?>
		                                </div>
			                        </td>
			                    </tr>
			                <?php }?>
			            </tbody>
			        </table>
			    </div>
			</form>
		</div>
	</div>
</div>


<script type="text/javascript">

	function delQuiz(id)
	{
		if(confirm("<?php echo $lang["deleteConfirmation"];?>"))
		{
		    $.ajax({
				url: 'modules/actionHandler.php',
				type: 'get',
				data: 'action=delQuiz&quizId=' + id,
				success: function(output) {
					if(output == 'deleteQuizOk')
					{
						$('#quizzes').DataTable().row($('#quiz_'+id)).remove().draw();
						$('#topicActionResult').html("<span style=\"color: green;\"><?php echo $lang["quizSuccessfullyDeleted"];?>.</span>");
					}
				}, error: function()
				{
					alert(<?php echo "\"" . $lang["deletingFailed"] . "\"";?>);
				}
			});
		}
	}

	function askForQuestionnaire(qId)
	{
		$.ajax({
			url: 'modules/generatePDF.php',
			type: 'get',
			data: 'action=getQuizTaskPaper&quizId=' + qId,
			dataType: 'json',
			success: function(output) {
				if(output[0] == 'ok')
				{
					window.open(output[1], 'Download');
				} else {
					alert("Process failed");
				}
			}, error: function()
			{
				alert("Process failed");
			}
		});
	}

	function animateTime()
	{
		$('.startQuizButton').animate({
	        width: $('.startQuizButton').css('width') == '20px' ? '18px' : '20px',
	    	height: $('.startQuizButton').css('height') == '20px' ? '18px' : '20px'
	    }, 500);
	}

    $(function() {
    	$('.editQunnaire').tipsy({gravity: 'n'});
    	$('.delQuizImg').tipsy({gravity: 'n'});
    	$('.participate').tipsy({gravity: 'n'});
    	$('.showSolutionPaperWithOwnSolutions').tipsy({gravity: 'n'});
    	$('.showTaskPaper').tipsy({gravity: 'n'});
    	$('.prioImg').tipsy({gravity: 'n'});
    	$('.quizCompleteImg').tipsy({gravity: 'n'});
    	$('.qunnaireReport').tipsy({gravity: 'n'});
    	$('.showOwnParticipations').tipsy({gravity: 'n'});
    	$('.eye_open').tipsy({gravity: 'n'});
    	$('.eye_closed').tipsy({gravity: 'n'});
    	$('.nameCol').tipsy({gravity: 'n'});
    	var startButtonInterval = setInterval(animateTime, 1000);
    	//animateTime();
    	
    	$('#quizzes').DataTable({
            sort: true,
            paginate: true,
            lengthChange: false,
            responsive: true,
            columns: [
                {responsivePriority: 1},
                {responsivePriority: 6},
                {searchable: false, responsivePriority: 5},
                {searchable: false, responsivePriority: 4},
                {searchable: false, responsivePriority: 3},
				{searchable: false, sortable: false, responsivePriority: 2},
            ],
            dom: '<"toolbar">frtip',
            language: {
                zeroRecords: "<?php echo str_replace("[1]", $lang["quizzes"], $lang["dataTbaleZeroRecords"]);?>",
                info: "<?php echo str_replace("[1]", $lang["quizzes"], $lang["dataTableInfo"]);?>",
                infoEmpty: "<?php echo str_replace("[1]", $lang["quizzes"], $lang["dataTableEmpty"]);?>",
                infoFiltered: "<?php echo str_replace("[1]", $lang["quizzes"], $lang["dataTableInfoFiltered"]);?>",
                search: ""
            }
        });
        $('.dataTables_filter').css("display", "none");


        $("#searchbox").on("keyup search input paste cut", function() {
        	$('#quizzes').dataTable().fnFilter(this.value);
        });
    	
    });
    function sendData() {
        $('#quizFilter').submit();
    }


    $(document).ready(function() {
        $('#state, #language, #topic, #owner').multiselect({

        	buttonText: function(options, select) {
                if (options.length === 0) {
                    return '<?php echo $lang["all"]?>';
                }
                 else {
                     var labels = [];
                     options.each(function() {
                         if ($(this).attr('label') !== undefined) {
                             labels.push($(this).attr('label'));
                         }
                         else {
                             labels.push($(this).html());
                         }
                     });
                     return labels.join(', ') + '';
                 }
            }
        });
    });
    
</script>
