<?php 
	if($_SESSION["role"]["user"] == 1)
	{
		if($_SESSION["role"]["creator"] != 1)
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
	
	//TODO: Duplicate Function handleCode & Extract to File 'HandleCode'
	
	$code = 0;
	$codeTxt = "";
	$color = "red";
	if(isset($_GET["code"]))
	{
		$code = $_GET["code"];
	
		if($code > 0)
			$color = "green";
		switch ($code)
		{
			case -1:
			case -2:
			case -3:
			case -5:
			case -13:
			case -14:
			case -15:
				$codeTxt = "Fehler in der Bearbeitung des Vorgangs (Code: " . htmlspecialchars($code) .")";
				break;
			case -4:
				$codeTxt = "DB insert Fehler.";
				break;
			case -6:
				$codeTxt = "Datentransfer fehlgeschlagen.";
				break;
			case -7:
				$codeTxt = "Bild&uumlberpr&uumlfung fehlgeschlagen.";
				break;
			case -8:
				$codeTxt = "Keine Datei gefunden.";
				break;
			case -9:
				$codeTxt = "Datei schon vorhanden.";
				break;
			case -10:
				$codeTxt = "Datei zu gross.";
				break;
			case -11:
				$codeTxt = "Dateityp wird nicht unterst&uuml;tzt.";
				break;
			case -12:
				$codeTxt = "Unzureichende Berechtigungen.";
				break;
			case 1:
				$codeTxt = "Neue Frage erstellt";
				break;
			case 2:
				$codeTxt = "Frage wurde bearbeitet";
				break;
			default:
				$codeTxt = "Fehler (Code: " . htmlspecialchars($code) .")";
				break;
		}
	}
?>
<script type="text/javascript">

	function delQuestion(id)
	{
	    $.ajax({
		      url: 'modules/actionHandler.php',
		      type: 'get',
		      data: 'action=delQuestion&userId='+<?php echo $_SESSION["id"]; ?>+'&questionId=' + id,
		      success: function(output) {
			      if(output == 'deleteQuestionOk')
			      {
				      $('#question_' + id).hide();
				      $('#questionActionResult').html("Frage erfolgreich entfernt.");
			      }
		      }, error: function()
		      {
		          alert("Deleting failed");
		      }
		   });
	}

	function openDialog(qId)
	{
		$.ajax({
			url: 'modules/actionHandler.php',
			type: 'get',
			data: 'action=queryAnswers&questionId='+qId,
			dataType: 'json',
			success: function(output) {
				if(output[0] == 'getAnswersOk')
				{
					console.log(JSON.stringify(output));
					var dialogContent = "<div><b>"+output[1][0]+"</b><ol>";
					for(var i = 1; i < output[1].length; i++)
					{
						dialogContent += "<li>"+output[1][i]+"</li>";
					}
					dialogContent += "</ol></div>";
					
					
					$( "#dialog" ).html(dialogContent);
				} else {
					$( "#dialog" ).html("query failed 2");
				}
				$( "#dialog" ).dialog( "open" );
			}, error: function()
			{
				alert("query failed");
			}
		});
	}
	
    $(function() {
    	$('.deleteQuestion').tipsy({gravity: 'n'});
    	$('.editQuestion').tipsy({gravity: 'n'});
    	$('#answerQuality').tipsy({gravity: 'n'});
    	$('.amountUsed').tipsy({gravity: 'n', html: true});
    	$('.questionTypeInfo').tipsy({gravity: 'n'});

    	$( "#dialog" ).dialog({
    		autoOpen: false,
    		title: "Frage und Antworten",
			buttons: {
				"OK": function() {
					$( this ).dialog( "close" );
					$( "#dialog" ).html("");
				}
			}
		});
    	
    	
        $('#questions').DataTable({
        	'sScrollY': "600px",
            'bScrollCollapse': true,
            'bSort': true,
            'bPaginate': false,
            'bLengthChange': false,
            "sDom": '<"toolbar">frtip',
            "oLanguage": {
                "sZeroRecords": "Es sind keine Fragen dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Fragen",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Fragen",
                "sInfoFiltered": "(von insgesamt _MAX_ Fragen)",
                "sSearch": ""
            },
            "columnDefs": [
				{
					"visible": false,  "targets": [ 1 ]
				}
			],
        });
        $('.dataTables_filter').prepend("<div style=\"text-align:right; width:100px;\"><b>Suche:</b></div>");
        $('.dataTables_filter input').attr("placeholder", 'Suchbegriff in Spalte "Fragetext (Keywords)" suchen');
        $('.dataTables_filter input').addClass("form-control");
        $('.dataTables_filter input').addClass("magnifyingGlass");
        $('div.toolbar').html(document.getElementById('hiddenFilter').innerHTML);
    });
    function sendData() {
        $('#quizFilter').submit();
    }
</script>
<?php 
	$selectedLanguage = "all";
	$selectedTopic = "all";
	$selectedCreator = "all";
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
		<h1><?php echo $lang["questionsHeadline"];?></h1>
	</div>
	<p id="questionActionResult" style="color:<?php echo $code < 1 ? 'red':'green'?>;"><?php echo $codeTxt;?></p>
	<div class="panel panel-default">
		<div class="panel-body">
			<form id="quizFilter" class="form-horizontal" action="?p=questions" method="POST">
			    <div id="hiddenFilter" style="display: none;">
			    		
			        <div class="control-group">
			            <label class="control-label" for="language">
			                <?php echo $lang["quizLanguage"]?>
			            </label>
			            <div class="controls">
			                <select id="language" class="form-control" name="language" onchange="sendData()">
			                	<?php 
			                	$stmt = $dbh->prepare("select id from question");
			                	$stmt->execute();
			                	$allQuestionsCount = $stmt->rowCount();
			                	?>
			                    <option value="all" <?php echo ($selectedLanguage == "all") ? 'selected="selected"' : '';?>><?php echo $lang["all"] . " (".$allQuestionsCount." " . $lang["questions"] . ")";?></option>
			                    <?php 
			                    $stmt = $dbh->prepare("select language from question group by language");
			                    $stmt->execute();
			                    $result = $stmt->fetchAll();
			                    
			                    for($i = 0; $i < count($result); $i++){
									$stmt = $dbh->prepare("select id from question where language = '" . $result[$i]["language"] . "'");
									$stmt->execute();
									$selected = ($selectedLanguage == $result[$i]["language"]) ? 'selected="selected"' : '';
									echo "<option value=\"" . $result[$i]["language"] . "\"" . $selected . ">" . $result[$i]["language"] . " (" . $stmt->rowCount() . " " . $lang["questions"] . ")</option>";
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
			                    <option value="all" <?php echo ($selectedTopic == "all") ? 'selected="selected"' : '';?>><?php echo $lang["all"] . " (".$allQuestionsCount." " . $lang["questions"] . ")";?></option>
			                    <?php 
			                    $stmt = $dbh->prepare("select subject_id from question group by subject_id");
			                    $stmt->execute();
			                    $result = $stmt->fetchAll();
			                    
			                    for($i = 0; $i < count($result); $i++){
									if($result[$i]["subject_id"] == null)
									{
										$stmt = $dbh->prepare("select id from question where subject_id is null");
									}
									else 
										$stmt = $dbh->prepare("select id from question where subject_id = " . $result[$i]["subject_id"]);
									$stmt->execute();
									$rowCount = $stmt->rowCount();
									
									$stmt = $dbh->prepare("select name from subjects where id = " . $result[$i]["subject_id"]);
									$stmt->execute();
									$resultSubjectName = $stmt->fetchAll(PDO::FETCH_ASSOC);
									$selected = ($selectedTopic == $result[$i]["subject_id"]) ? 'selected="selected"' : '';
									$subjectName = ($resultSubjectName[0]["name"] == null) ? "Nicht zugeordnet" : $resultSubjectName[0]["name"];
									$subjectId = ($result[$i]["subject_id"] == null) ? 'null' : $result[$i]["subject_id"];
									echo "<option value=\"" . $subjectId . "\" " . $selected . ">" . $subjectName . " (" . $rowCount . " " . $lang["questions"] . ")</option>";
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
			                    <option value="all" <?php echo ($selectedTopic == "all") ? 'selected="selected"' : '';?>><?php echo $lang["all"] . " (". $allQuestionsCount ." " . $lang["questions"] . ")";?></option>
			                    <?php 

				                    $stmt = $dbh->prepare("select owner_id from question group by owner_id");
				                    $stmt->execute();
				                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                    	
			                    	for($i = 0; $i < count($result); $i++){
										
										$stmt = $dbh->prepare("select firstname, lastname from user_data inner join user on user.id = user_data.user_id where user.id = " . $result[$i]["owner_id"]);
										$stmt->execute();
				                    	$fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
				                    	
				                    	$stmt = $dbh->prepare("select id from question where owner_id = :owner_id");
				                    	$stmt->bindParam(":owner_id", $result[$i]["owner_id"]);
				                    	$stmt-> execute();
				                    	$ownerRowCount = $stmt->rowCount();
				                    	
				                    	$selected = $selectedCreator == $result[$i]["owner_id"] ? 'selected="selected"' : '';
										
										echo "<option value=\"" . $result[$i]["owner_id"] . "\" " . $selected . ">" . $fetchUser["firstname"] . " " . $fetchUser["lastname"] . " (" . $ownerRowCount . " " . $lang["questions"] . ")</option>";
			                    } ?>
			                </select>
			            </div>
			        </div>
			    </div>
			    <div class="listOfQuizzes">
			        <table class="tblListOfQuizzes" id="questions" style="width: 100%">
			            <thead>
			                <tr>
			                    <th>
			                        <?php echo $lang["questionQuestionText"]?>
			                    </th>
			                    <th></th>
			                    <th>
			                        <?php echo $lang["quizTableTopic"]?>
			                    </th>
			                    <th>
			                        <?php echo $lang["creator"]?>
			                    </th>
			                    <th>
			                        <?php echo $lang["questionAmountAnswers"]?>
			                    </th>
			                    <th>
			                        <?php echo $lang["questionAmountUsedAnswer"]?>
			                    </th>
			                    <th id="answerQuality" original-title="Qualit&auml;t der Antworten zu einzelnen Fragen (Durchschnittlich erreichte Punktezahl, -100% bis 100%)">
			                        <?php echo $lang["questionCalcCorrectAnswer"]?>
			                    </th>
			                    <th>
			                        <?php echo $lang["quizTableActions"]?>
			                    </th>
			                </tr>
			            </thead>
			            <tbody>
			                <?php
			                if($selectedLanguage != "all" || $selectedTopic != "all" || $selectedCreator != "all")
			                {
			                	$notFirst = false;
			                	$whereStatement = " where ";
			                	if($selectedLanguage != "all")
			                	{
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
			                
			                $stmt = $dbh->prepare("select question.id as q_id, question.*, subjects.id as s_id, subjects.*, user_data.firstname, user_data.lastname from question left outer join subjects on subjects.id = question.subject_id inner join user on user.id = question.owner_id inner join user_data on user_data.user_id = user.id" . $whereStatement);
			                if($selectedLanguage != "all"){$stmt->bindParam(":language", $selectedLanguage);}
			                if($selectedTopic != "all" && $selectedTopic != null){$stmt->bindParam(":subject_id", $selectedTopic);}
			                if($selectedCreator != "all"){$stmt->bindParam(":owner_id", $selectedCreator);}
			                $stmt->execute();
			                $resultArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                
			                for($i = 0; $i < count($resultArray); $i++) {
							?>
			                    <tr class="entry" id="<?php echo "question_" . $resultArray[$i]["q_id"];?>">
			                        <?php 
			                        	$qType = "singlechoice";
			                        if($resultArray[$i]["type_id"] == 2)
			                        	$qType = "multiplechoice";
			                        ?>
			                        <td title="<?php echo htmlspecialchars($resultArray[$i]["text"]);?>">
			                            <img width="15" height="15" class="questionTypeInfo" original-title="<?php echo $qType;?>" style="margin-right: 5px; margin-bottom: 3px;" src="assets/icon_<?php echo $qType;?>.png"><a href="javascript:void(0)" onclick="openDialog(<?php echo $resultArray[$i]["q_id"];?>)"><?php echo substr(htmlspecialchars($resultArray[$i]["text"]), 0, 20);?></a>
			                        </td>
			                        <td>
			                            <?php echo htmlspecialchars($resultArray[$i]["text"]);?>
			                        </td>
			                        <td>
			                        	<?php echo ($resultArray[$i]["name"]==NULL) ? "Nicht zugeordnet" : $resultArray[$i]["name"];?>
			                        </td>
			                        <td>
			                        	<?php echo $resultArray[$i]["firstname"] . " " . $resultArray[$i]["lastname"];?>
			                        </td>
			                        <td>
			                            <?php 
			                            $stmt = $dbh->prepare("select answer_id from answer_question where question_id = " . $resultArray[$i]["q_id"]);
			                            $stmt->execute();
			                            echo $stmt->rowCount();
			                            ?>
			                        </td>
			                        <td>
			                        	<?php 
			                            $stmt = $dbh->prepare("select questionnaire_id, name from qunaire_qu inner join questionnaire on questionnaire.id = qunaire_qu.questionnaire_id where question_id = " . $resultArray[$i]["q_id"]);
			                            $stmt->execute();
			                            $fetchQuestionnaireName = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                            $qunaireStr = "";
			                            for($j = 0; $j < count($fetchQuestionnaireName); $j++)
			                            {
			                            	if($j != 0)
			                            		$qunaireStr .= "<br />";
			                            	$qunaireStr .= $fetchQuestionnaireName[$j]["name"];
			                            }
			                            
			                            echo "<span class=\"amountUsed\" original-title=\"" . $qunaireStr . "\">" . $stmt->rowCount() . "</span>";
			                            ?>
			                        </td>
			                        <td>
			                        	<?php 
			                        	$stmt = $dbh->prepare("select * from an_qu_user inner join answer_question on answer_question.answer_id = an_qu_user.answer_id where an_qu_user.question_id = :qId");
			                        	$stmt->bindParam(":qId", $resultArray[$i]["q_id"]);
			                        	$stmt->execute();
			                        	$fetchAnQuUser = $stmt->fetchAll(PDO::FETCH_ASSOC);
			                        	$correctCounter = 0;
			                        	$totalCounter = 0;
			                        	for($j = 0; $j < count($fetchAnQuUser); $j++)
			                        	{
			                        		if($fetchAnQuUser[$j]["is_correct"] == 1 && $fetchAnQuUser[$j]["selected"] == 1)
			                        		{
			                        			$correctCounter++;
			                        		} else if($fetchAnQuUser[$j]["is_correct"] == -1 && $fetchAnQuUser[$j]["selected"] == -1)
			                        		{
			                        			$correctCounter++;
			                        		}
			                        		if($fetchAnQuUser[$j]["is_correct"] != 0)
			                        		{
			                        			$totalCounter++;
			                        		}
			                        	}
			                        	if($totalCounter != 0)
			                        		echo "(".$correctCounter."/".$totalCounter.") " . number_format(($correctCounter*100)/$totalCounter, 1) . "%";
			                        	else 
			                        		echo "-";
			                        	?>
			                        </td>
			                        <td>
			                        	<?php if($_SESSION['role']['admin'] == 1 || $resultArray[$i]["owner_id"] == $_SESSION["id"]) {?>
				                            <a href="<?php echo "?p=createEditQuestion&mode=edit&id=" . $resultArray[$i]["q_id"];?>"><img class="editQuestion" src="assets/icon_edit.png" alt="" original-title="Frage editieren" height="18px" width="18px"></a>
				                            <?php //nur wenn selber creator oder admin ?>
	                                		<img id="delQuestionImg" class="deleteQuestion" src="assets/icon_delete.png" alt="" original-title="Frage l&ouml;schen" height="18px" width="18px" onclick="delQuestion(<?php echo $resultArray[$i]["q_id"];?>)">
			                        	<?php }?>
			                        </td>
			                    </tr>
			                <?php }?>
			            </tbody>
			            <tfoot>
			                <tr>
			                    <th colspan="7" style="text-align: center">
			                        <input id="btnAddQuestion" class="btn" type="button" style="width: 100%" value="Neue Frage erstellen" onclick="window.location='?p=createEditQuestion';"/>
			                    </th>
			                </tr>
			            </tfoot>
			        </table>
			    </div>
			</form>
		</div>
	</div>
</div>
<div id="dialog" title="Basic dialog"></div>