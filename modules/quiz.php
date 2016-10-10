<?php 
	if($_SESSION["role"]["user"] != 1)
	{
		header("Location: ?p=home&code=-20");
		exit;
	}
	include "modules/extraFunctions.php";

	//TODO: Duplicate Function handleCode & Extract to File 'HandleCode'
	
	$code = 0;
	$codeTxt = "";
	$color = "red";
	if(isset($_GET["code"]))
	{
		$code = $_GET["code"];
	}
	if($code > 0)
		$color = "green";
	
	switch ($code)
	{
		case -8:
		case -9:
		case -10:
		case -11:
		case -6:
			$codeTxt = $lang["quizUploadPicError"] . " (Code: " . htmlspecialchars($code) .")";
			break;
		case -2:
		case -7:
		case -12:
		case -13:
		case -14:
		case -16:
		case -17:
		case -21:
		case -22:
		case -23:
		case -24:
		case -25:
		case -27:
			$codeTxt = $lang["quizGeneralError"] . " (Code: " . htmlspecialchars($code) .")";
			break;
		case -15:
			$codeTxt = $lang["quizNotAvailable"] . ".";
			break;
		case -18:
			$codeTxt = $lang["quizAborted"] . ".";
			break;
		case -19:
			$codeTxt = $lang["quizNotFinished"] . ".";
			break;
		case -20:
			$codeTxt = $lang["quizNotStarted"] . ".";
			break;
		case -25:
			$codeTxt = $lang["quizNotPublic"] . ".";
			break;
		case -26:
			$codeTxt = $lang["quizNotInTimeWindow"] . ".";
			break;
		case -28:
			$codeTxt = $lang["errorWhileUploading"] . ".";
			break;
		case -29:
			$codeTxt = $lang["noCSVFile"] . ".";
			break;
		case -30:
			$codeTxt = $lang["uploadeCSVHandleError"] . ".";
			break;
		case -31:
		case -32:
		case -33:
			$codeTxt = $lang["csvInsertError"] . " (Code: " . htmlspecialchars($code) .")";
			break;
		case -34:
			$codeTxt = $lang["csvQunaireError"] . ".";
			break;
		case -35:
			$codeTxt = $lang["reachedMaximumOfParticipations"] . ".";
			break;
		case -36:
			$codeTxt = $lang["PDFCreationError"] . ".";
			if(isset($_GET["info"]) && $_GET["info"] == 'noAccess4')
				$codeTxt .= "<br />Lernkontrolle muss mindestens einmal durchgef&uuml;hrt werden um das Aufgabenblatt einsehen zu k&ouml;nnen.";
			break;
		case -37:
			$codeTxt = "End quiz db error.";
			break;
		case -38:
			$codeTxt = "Diese Lernkontrolle darf nur von bestimmten Gruppen durchgef&uuml;hrt werden.";
			break;
		case -3:
			$codeTxt = $lang["dateOrTimeFormatError"] . ".";
			break;
		case -4:
			$codeTxt = $lang["numericFormatError"] . ".";
			break;
		case -1:
			$codeTxt = $lang["noAccessError"] . ".";
			break;
		case 1:
			$codeTxt = $lang["successfullySavedQuiz"] . ".";
			if(isset($_GET["qwna"]) && $_GET["qwna"] != 0) //qwna(v) - question with no answer (value)
				$codeTxt .= "<br /><span style=\"color: red;\">".$_GET["qwna"]." Fragen ohne mindestens eine richtie Antwort vorhanden. Bitte &uuml;berpr&uuml;fen Sie Ihre Lernkontrolle sonst kann es zu Fehlern kommen.";
				$qwnav = explode(",", $_GET["qwnav"]);
				for($i = 0; $i < count($qwnav); $i++)
				{
					if($i == 0)
						$codeTxt .= "<ul>";
					$codeTxt .= "<li>".$qwnav[$i]."</li>";
					if($i == count($qwnav)-1)
						$codeTxt .= "</ul>";
				}
				$codeTxt .= "</span>";
			break;
		case 2:
			$codeTxt = $lang["successfullySavedQuizAsBlueprint"] . ".";
			break;
	}
	
	if($code < 0)
	{
		$file = "logs/errorLog.txt";
		$text = "Datum: " . date("d.m.Y H:i:s", time()) . "\nfromSite: ".$_SERVER['HTTP_REFERER']."\nCode: " . $code . "\nUsersession: " . $_SESSION["id"] . "\nQuizId: " . $_SESSION["quizSession"] . "\nSessionId: " . $_SESSION["idSession"] . "\nQuestionnumber: " . $_SESSION["questionNumber"] . "\n";
		$text .= "------------------------------\n";
		$fp = fopen($file, "a");
		fwrite($fp, $text);
		fclose($fp);
	}
?>
<script type="text/javascript">

	function delQuiz(id)
	{
		if(confirm("<?php echo $lang["deleteConfirmation"];?>"))
		{
		    $.ajax({
				url: 'modules/do.php',
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
            paginate: false,
            lengthChange: false,
            responsive: true,
            columns: [
                {responsivePriority: 1},
                {responsivePriority: 7},
                {responsivePriority: 8},
                {searchable: false, responsivePriority: 6},
                {searchable: false, responsivePriority: 5},
                <?php if($_SESSION["role"]["creator"] == 1) {
                	echo '{searchable: false, responsivePriority: 5},';
				}?>
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
    	$('.dataTables_filter').prepend("<div style=\"text-align:right; width:100px;\"><b><?php echo $lang["search"];?>:</b></div>");
        $('.dataTables_filter input').attr("placeholder", '<?php echo $lang["enterSearchTerm"];?>');
        $('.dataTables_filter input').addClass("form-control");
        $('.dataTables_filter input').addClass("magnifyingGlass");
        $('div.toolbar').html(document.getElementById('hiddenFilter').innerHTML);
    });
    function sendData() {
        $('#quizFilter').submit();
    }
</script>
<?php 
	$selectedState = "current";
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
	if(isset($_POST["topic"]))
	{
		$selectedTopic = $_POST["topic"];
		if($selectedTopic == "null")
			$selectedTopic = null;
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
	<p id="topicActionResult" style="color:<?php echo $color;?>;"><?php echo $codeTxt;?></p>
	<?php //echo "a: " . $selectedTopic;?>
	<div class="panel panel-default">
		<div class="panel-body">
			<form id="quizFilter" class="form-horizontal" action="?p=quiz" method="POST">
			    <div id="hiddenFilter" style="display: none;">
			        <div class="control-group">
			            <label class="control-label" for="state">
			                <?php echo $lang["state"]?>
			            </label>
			            <div class="controls">
			                <select id="state" class="form-control" name="state" onchange="sendData()">
			                    <option value="all" <?php echo ($selectedState == "all") ? 'selected="selected"' : '';?>><?php echo $lang["all"];?></option>
			                    <option value="participated" <?php echo ($selectedState == "participated") ? 'selected="selected"' : '';?>><?php echo $lang["participated"];?></option>
			                    <option value="current" <?php echo ($selectedState == "current") ? 'selected="selected"' : '';?>><?php echo $lang["currend"];?></option>
			                    <option value="finished" <?php echo ($selectedState == "finished") ? 'selected="selected"' : '';?>><?php echo $lang["finished"];?></option>
			                </select>
			            </div>
			        </div>
			
			        <div class="control-group">
			            <label class="control-label" for="language">
			                <?php echo $lang["quizLanguage"]?>
			            </label>
			            <div class="controls">
			                <select id="language" class="form-control" name="language" onchange="sendData()">
			                    <?php 
			                    $filterWhere = "";
			                    $filterWhereAnd = "";
			                    if($_SESSION["role"]["creator"] != 1)
			                    {
			                    	$filterWhere = " where public = 1";
			                    	$filterWhereAnd = " and public = 1";
			                    }
			                    
			                	$stmt = $dbh->prepare("select id from questionnaire" . $filterWhere);
			                	$stmt->execute();
			                	$allQuestionnairessCount = $stmt->rowCount();
			                	?>
			                    <option value="all" <?php echo ($selectedLanguage == "all") ? 'selected="selected"' : '';?>><?php echo $lang["all"] . " (".$allQuestionnairessCount." " . $lang["quizzes"] . ")";?></option>
			                    <?php 
			                    $stmt = $dbh->prepare("select language from questionnaire ". $filterWhere ." group by language");
			                    $stmt->execute();
			                    $result = $stmt->fetchAll();
			                    
			                    for($i = 0; $i < count($result); $i++){
									$stmt = $dbh->prepare("select id from questionnaire where language = '" . $result[$i]["language"] . "'" . $filterWhereAnd);
									$stmt->execute();
									$selected = ($selectedLanguage == $result[$i]["language"]) ? 'selected="selected"' : '';
									echo "<option value=\"" . $result[$i]["language"] . "\"" . $selected . ">" . $result[$i]["language"] . " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")</option>";
			                    } ?>
			                </select>
			            </div>
			        </div>
			        <div class="control-group">
			            <label class="control-label" for="topic">
			                <?php echo $lang["quizTopics"]?>
			            </label>
			            <div class="controls">
			                <select id="topic" class="form-control" name="topic" onchange="sendData()">
			                    <option value="all" <?php echo ($selectedTopic == "all") ? 'selected="selected"' : '';?>><?php echo $lang["all"] . " (".$allQuestionnairessCount." " . $lang["quizzes"] . ")";?></option>
			                    <?php 
			                    
			                    $stmt = $dbh->prepare("select subject_id from questionnaire ".$filterWhere." group by subject_id");
			                    $stmt->execute();
			                    $result = $stmt->fetchAll();
			                    
			                    for($i = 0; $i < count($result); $i++){
									if($result[$i]["subject_id"] == null)
									{
										$stmt = $dbh->prepare("select id from questionnaire where subject_id is null" . $filterWhereAnd );
									}
									else 
										$stmt = $dbh->prepare("select id from questionnaire where subject_id = " . $result[$i]["subject_id"] . $filterWhereAnd);
									$stmt->execute();
									$rowCount = $stmt->rowCount();
									
									$stmt = $dbh->prepare("select name from subjects where id = " . $result[$i]["subject_id"]);
									$stmt->execute();
									$resultSubjectName = $stmt->fetchAll(PDO::FETCH_ASSOC);
									$selected = ($selectedTopic == $result[$i]["subject_id"]) ? 'selected="selected"' : '';
									$subjectName = ($resultSubjectName[0]["name"] == null) ? "Nicht zugeordnet" : $resultSubjectName[0]["name"];
									$subjectId = ($result[$i]["subject_id"] == null) ? 'null' : $result[$i]["subject_id"];
									echo "<option value=\"" . $subjectId . "\" " . $selected . ">" . $subjectName . " (" . $rowCount . " " . $lang["quizzes"] . ")</option>";
			                    } ?>
			                </select>
			            </div>
			        </div>
			        <div class="control-group">
			            <label class="control-label" for="owner">
			                <?php echo $lang["quizOwner"]?>
			            </label>
			            <div class="controls">
			                <select id="owner" class="form-control" name="owner" onchange="sendData()">
			                    <option value="all" <?php echo ($selectedTopic == "all") ? 'selected="selected"' : '';?>><?php echo $lang["all"] . " (". $allQuestionnairessCount ." " . $lang["quizzes"] . ")";?></option>
			                    <?php 
			                    	
				                    $stmt = $dbh->prepare("select owner_id from questionnaire ".$filterWhere." group by owner_id");
				                    $stmt->execute();
				                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                    	
			                    	for($i = 0; $i < count($result); $i++){
										
										$stmt = $dbh->prepare("select firstname, lastname from user_data inner join user on user.id = user_data.user_id where user.id = " . $result[$i]["owner_id"]);
										$stmt->execute();
				                    	$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
				                    	
				                    	$stmt = $dbh->prepare("select id from questionnaire where owner_id = :owner_id" . $filterWhereAnd);
				                    	$stmt->bindParam(":owner_id", $result[$i]["owner_id"]);
				                    	$stmt-> execute();
				                    	$ownerRowCount = $stmt->rowCount();
				                    	
				                    	$selected = $selectedCreator == $result[$i]["owner_id"] ? 'selected="selected"' : '';
										
										echo "<option value=\"" . $result[$i]["owner_id"] . "\" " . $selected . ">" . $fetchUser["firstname"] . " " . $fetchUser["lastname"] . " (" . $ownerRowCount . " " . $lang["quizzes"] . ")</option>";
			                    } ?>
			                </select>
			            </div>
			        </div>
			    </div>
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
			                        <?php echo $lang["quizTableCreator"]?>
			                    </th>
			                    <th>
			                        <?php echo $lang["quizTableAmountQuestions"]?>
			                    </th>
			                    <th>
			                        <?php echo $lang["quizTableParticipiations"]?>
			                    </th>
			                    <?php 
			                    if($_SESSION["role"]["creator"] == 1) {
			                    ?>
			                    <th>
			                        <?php echo $lang["amountParticipations"]?>
			                    </th>
			                    <?php }?>
			                    <th>
			                        <?php echo $lang["userQuizState"]?>
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
			                		if($selectedState == 'participated')
			                		{
			                			$whereStatement .= "starttime > :time ";
			                		} else if($selectedState == 'current')
			                		{
			                			$whereStatement .= "starttime < :time and endtime > :time or noParticipationPeriod = :noParticipationPeriod ";
			                			$noParticipationTime = true;
			                		} else if($selectedState == 'finished')
			                		{
			                			$whereStatement .= "endtime < :time and noParticipationPeriod <> :noParticipationPeriod ";
			                			$noParticipationTime = true;
			                		}
			                		$notFirst = true;
			                	}
			                	if($selectedLanguage != "all")
			                	{
			                		if($notFirst)
			                			$whereStatement .= " and ";
			                		$whereStatement .= "language = :language ";
			                		$notFirst = true;
			                	}
			                	if($selectedTopic != "all")
			                	{
			                		if($notFirst)
			                			$whereStatement .= " and ";
			                		if($selectedTopic == null)
			                			$whereStatement .= "subject_id is null ";
			                		else 
			                			$whereStatement .= "subject_id = :subject_id ";
			                		$notFirst = true;
			                	}
			                	if($selectedCreator != "all")
			                	{
			                		if($notFirst)
			                			$whereStatement .= " and ";
			                		$whereStatement .= "owner_id = :owner_id";
			                	}
			                }
			                
			                $queryStr = "select questionnaire.id as qId, questionnaire.name as qName, questionnaire.description, subjects.name as sName, questionnaire.quiz_passed, user_data.firstname, user_data.lastname, starttime, endtime, user.email as uEmail, owner_id, questionnaire.priority, questionnaire.public, questionnaire.noParticipationPeriod, questionnaire.result_visible from questionnaire left outer join subjects on questionnaire.subject_id = subjects.id inner join user on user.id = questionnaire.owner_id inner join user_data on user_data.user_id = user.id" . $whereStatement;
			                $stmt = $dbh->prepare($queryStr);
			                if($selectedState != "all"){$stmt->bindParam(":time", time());}
			                if($noParticipationTime) {$stmt->bindValue(":noParticipationPeriod", 1);}
			                if($selectedLanguage != "all"){$stmt->bindParam(":language", $selectedLanguage);}
			                if($selectedTopic != "all" && $selectedTopic != null){$stmt->bindParam(":subject_id", $selectedTopic);}
			                if($selectedCreator != "all"){$stmt->bindParam(":owner_id", $selectedCreator);}
			                $stmt->execute();
			                $fetchQuestionnaire = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                
			                for($i = 0; $i < count($fetchQuestionnaire); $i++) {
								if($fetchQuestionnaire[$i]["public"] != 1 && $fetchQuestionnaire[$i]["owner_id"] != $_SESSION["id"] && $_SESSION['role']['admin'] != 1 && !amIAssignedToThisQuiz($dbh, $fetchQuestionnaire[$i]["qId"]))
									continue;
							?>
								<?php //echo "b: " . $queryStr . "<br />";?>
			                    <tr class="entry" id="<?php echo "quiz_" . $fetchQuestionnaire[$i]["qId"];?>">
			                        <td title="<?php echo htmlspecialchars($fetchQuestionnaire[$i]["description"]);?>">
			                            <a href="?p=showQuiz&quizId=<?php echo $fetchQuestionnaire[$i]["qId"];?>"><?php echo substr(htmlspecialchars($fetchQuestionnaire[$i]["qName"]), 0, 30);?></a>
			                        </td>
			                        <td>
			                            <?php echo ($fetchQuestionnaire[$i]["sName"]==NULL) ? "Nicht zugeordnet" : $fetchQuestionnaire[$i]["sName"];?>
			                        </td>
			                        <td class="nameCol" original-title="<?php echo $fetchQuestionnaire[$i]["uEmail"];?>">
			                            <?php echo htmlspecialchars($fetchQuestionnaire[$i]["firstname"]) . " " . htmlspecialchars($fetchQuestionnaire[$i]["lastname"]);?>
			                        </td>
			                        <td>
			                        	<?php 
			                        	$stmt = $dbh->prepare("select question_id from qunaire_qu where questionnaire_id = " . $fetchQuestionnaire[$i]["qId"]);
			                        	$stmt->execute();
			                        	echo $stmt->rowCount();
			                        	?>
			                        </td>
			                        <td>
			                            <?php 
			                            $stmt = $dbh->prepare("select id from user_qunaire_session where user_id = :user_id and questionnaire_id = :qunaire_id");
			                            $stmt->bindParam(":user_id", $_SESSION["id"]);
			                            $stmt->bindParam(":qunaire_id", $fetchQuestionnaire[$i]["qId"]);
			                            $stmt->execute();
			                            $ownParticipationAmount = $stmt->rowCount();
			                            echo $ownParticipationAmount;
			                            ?>
			                        </td>
			                        <?php if($_SESSION["role"]["creator"] == 1){?>
				                        <td>
				                        	<?php 
				                        	$allParticipations = -1;
				                            $stmt = $dbh->prepare("select distinct user_id from user_qunaire_session where questionnaire_id = :qunaire_id");
			                            	$stmt->bindParam(":qunaire_id", $fetchQuestionnaire[$i]["qId"]);
				                            $stmt->execute();
				                            $allParticipations = $stmt->rowCount();
				                            echo $allParticipations;
				                            ?>
				                        </td>
			                        <?php }?>
			                        
			                        <td style="white-space: nowrap;">
			                            <?php 
			                            $prio = "";
			                            $hint = "";
			                            switch ($fetchQuestionnaire[$i]["priority"])
			                            {
			                            	case 0: $prio = "Green";
			                            		$hint = $lang["lowPrio"];
			                            		break;
			                            	case 1: $prio = "Yellow";
			                            		$hint = $lang["middlePrio"];
			                            		break;
			                            	case 2: $prio = "Red";
			                            		$hint = $lang["highPrio"];
			                            		break;
			                            }
			                            ?>
			                            <img class="prioImg" src="<?php echo "assets/priority" . $prio . ".png";?>" original-title="<?php echo $hint;?>" style="margin-right: 5px;"/>
			                            <?php 
			                            $stmt = $dbh->prepare("select * from user_qunaire_session where user_id = :user_id and questionnaire_id = :questionnaire_id");
			                            $stmt->bindParam(":user_id", $_SESSION["id"]);
			                            $stmt->bindParam(":questionnaire_id", $fetchQuestionnaire[$i]["qId"]);
			                            if(!$stmt->execute())
			                            {
			                            	header("Location: index.php?p=quiz&code=-14");
			                            	exit;
			                            }
			                            $fetchSession = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                            
			                            $tmpPoints = null;
			                            $fetchPoints = [0,0,0];
			                            for ($j = 0; $j < count($fetchSession); $j++)
			                            {
			                            	$tmpPoints = getPoints($dbh, $fetchQuestionnaire[$i]["qId"], $fetchSession[$j]["id"], 0);
			                            	if($j == 0 || $tmpPoints[0] >= $fetchPoints[0])
				                            	$fetchPoints = $tmpPoints;
			                            }
			                            
			                            if($fetchPoints[2] >= $fetchQuestionnaire[$i]["quiz_passed"])
			                            {
			                            	$completeImg = "icon_correct";
			                            	$hint = $lang["quizFinished"];
			                            }
			                            else
			                            { 
			                            	$completeImg = "icon_incorrect";
			                            	$hint = $lang["quizNotFinished"];
			                            }
			                            
			                            if($fetchQuestionnaire[$i]["quiz_passed"] == 0)
			                            {
			                            	$completeImg = "icon_noPassing";
			                            	$hint = $lang["noPassing"];
		                            	}
			                            ?>
			                            <img class="quizCompleteImg" src="<?php echo "assets/" . $completeImg . ".png";?>" original-title="<?php echo $hint;?>" />
			                            <?php echo ($fetchQuestionnaire[$i]["result_visible"] != 3) ? "(" . $fetchPoints[2] . "%)" : "( ? %)";?>
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
			                        <?php if($_SESSION['role']['admin'] == 1 || $fetchQuestionnaire[$i]["owner_id"] == $_SESSION["id"] || amIAssignedToThisQuiz($dbh, $fetchQuestionnaire[$i]["qId"])) {?>
			                            <div style="white-space: nowrap;">
				                            <a href="?p=createEditQuiz&mode=edit&id=<?php echo $fetchQuestionnaire[$i]["qId"];?>" class="editQunnaire" original-title="<?php echo str_replace("[1]", '&laquo;' . substr(htmlspecialchars($fetchQuestionnaire[$i]["qName"]), 0, 25) . '&raquo;', $lang["editQuiz"]);?>"><img id="editQunnaire" src="assets/icon_edit.png" alt="" height="18px" width="18px"></a>&nbsp;
				                            <img id="delQuizImg" style="cursor: pointer;" class="deleteQuiz delQuizImg" src="assets/icon_delete.png" alt="" original-title="<?php echo $lang["delQuiz"];?>" height="18px" width="18px" onclick="delQuiz(<?php echo $fetchQuestionnaire[$i]["qId"];?>)">&nbsp;
				                            <a href="?p=quizReport&id=<?php echo $fetchQuestionnaire[$i]["qId"];?>" class="qunnaireReport" original-title="<?php echo $lang["showQuizReport"];?>"><img id="qunnaireReport" src="assets/icon_report.png" alt="" height="18px" width="18px"></a>&nbsp;
				                            <?php $eyePic = $fetchQuestionnaire[$i]["public"] != 1 ? 'closed' : 'open';
											echo '<img alt="'.$eyePic.'" src="assets/icon_eye_' . $eyePic . '.png" width="13" height="10" class="eye_'.$eyePic.'" original-title="'.$lang["quiz_" . $eyePic].'">';?><br />
										</div>
			                        <?php }?>
			                            <div style="white-space: nowrap;">
			                            	<?php if($canParticipate) {?>
			                            		<div style="height: 20px; width: 20px; float: left; margin-right: 4px;">
				                            		<a href="Pindex.php?p=participationIntro&quizId=<?php echo $fetchQuestionnaire[$i]["qId"];?>" class="participate" original-title="<?php echo $lang["participateQuiz"];?>"><img class="startQuizButton" src="assets/green-start-button.png" alt="<?php echo $lang["participateQuiz"];?>" height="18px" width="18px"></a>&nbsp;
				                            	</div>
				                            <?php }
				                            if(($taskPaperAvailable && $ownParticipationAmount > 0) || $_SESSION['role']['admin'] == 1 || $fetchQuestionnaire[$i]["owner_id"] == $_SESSION["id"] || amIAssignedToThisQuiz($dbh, $fetchQuestionnaire[$i]["qId"]))
				                            {
				                            ?>
			                                	<a href="?p=generatePDF&action=getQuizTaskPaper&quizId=<?php echo $fetchQuestionnaire[$i]["qId"];?>" target='_blank' class="showTaskPaper" original-title="<?php echo $lang["showTaskpaper"];?>"><img src="assets/icon_quiz.png" alt="" height="18px" width="18px"></a>&nbsp;
			                                <?php 
				                            }
			                                if(((time() > $endtime || $fetchQuestionnaire[$i]["result_visible"] == 1) && $fetchQuestionnaire[$i]["result_visible"] != 3) && $ownParticipationAmount > 0) {?>
			                                	<a href="?p=generatePDF&action=getQuizTaskPaperWithMyAnswers&quizId=<?php echo $fetchQuestionnaire[$i]["qId"];?>" target="_blank" class="showSolutionPaperWithOwnSolutions" original-title="<?php echo $lang["showTaskPaperWithSolution"];?>"><img src="assets/pdf_icon.png" alt="" height="18px" width="18px"></a>&nbsp;
			                                <?php }
			                                if($ownParticipationAmount > 0) {?>
			                                	<a href="<?php echo "Pindex.php?p=participationOutro&quizId=" . $fetchQuestionnaire[$i]["qId"];?>" class="showOwnParticipations" original-title="<?php echo $lang["showOwnParticipations"];?>"><img id="ownQunnaireReport" src="assets/icon_report.png" alt="" height="18px" width="18px"></a>
			                                <?php }?>
		                                </div>
			                        </td>
			                    </tr>
			                <?php }?>
			            </tbody>
			            <tfoot>
			            	<?php if($_SESSION['role']['creator'] == 1) {?>
			                <tr>
			                    <th colspan="9" style="text-align: center">
			                        <input id="btnAddQuiz" class="btn" type="button" style="width: 100%" value="<?php echo $lang["createQuiz"];?>" onclick="window.location='?p=createEditQuiz';"/></th>
			                </tr>
				            <?php }?>
			            </tfoot>
			        </table>
			    </div>
			</form>
		</div>
	</div>
</div>
