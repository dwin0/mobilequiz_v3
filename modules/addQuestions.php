<?php 
	if($_SESSION["role"]["creator"] != 1)
	{
		header("Location: ?p=home&code=-20");
		exit;
	}
	$quizId = -1;
	if(!isset($_GET["quizId"]) && !isset($_POST["quizId"]))
	{
		header("Location: ?p=quiz&code=-2");
		exit;
	} else
		$quizId = $_GET["quizId"];
	if(!isset($_GET["quizId"]) && isset($_POST["quizId"]))
	{
		$quizId = $_POST["quizId"];
	}
	
	//OWNER?
	$stmt = $dbh->prepare("select name, owner_id from questionnaire where id = :qId");
	$stmt->bindParam(":qId", $quizId);
	$stmt->execute();
	$fetchQnaireNameOwner = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if($fetchQnaireNameOwner["owner_id"] != $_SESSION["id"] && $_SESSION["role"]["admin"] != 1)
	{
		header("Location: ?p=quiz&code=-1&info=".$fetchQnaireNameOwner["owner_id"] . " a:" . $_SESSION["id"] . " b:" . $quizId);
		exit;
	}
	
?>
<script type="text/javascript">

	$(function() {
    	$('.questionTypeInfo').tipsy({gravity: 'n'});
        $('#questions').dataTable({
        	'sScrollY': "600px",
            'bScrollCollapse': true,
            'bSort': true,
            'bPaginate': false,
            'bLengthChange': false,
            'aoColumns': [
				{'bSearchable': false, 'bSortable': false},
				null,
				null,
				null,
				{'bSearchable': false},
				{'bSearchable': false},
				{'bSearchable': false}
            ],
            "sDom": '<"toolbar">frtip',
            "oLanguage": {
                "sZeroRecords": "Es sind keine Fragen dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Fragen",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Fragen",
                "sInfoFiltered": "(von insgesamt _MAX_ Fragen)",
                "sSearch": ""
            }
        });
        $('.dataTables_filter input').attr("placeholder", 'Suchbegriff in Spalte "Name (Keywords)" suchen');
        $('.dataTables_filter input').addClass("form-control");
        $('.dataTables_filter input').attr('id', 'addQuestionFilter');
        //$('div.toolbar').html(document.getElementById('hiddenFilter').innerHTML);
    });
    function sendData() {
        $('#quizFilter').submit();
    }

    function clearFilter()
    {
    	$('#questions').dataTable().fnFilter('');
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
		<h1><?php echo $lang["addQuestionsHeadline"] . " " . $fetchQnaireNameOwner["name"];?></h1>
	</div>
	<div class="panel panel-default">
		<div class="panel-body">
			<form id="quizFilter" class="form-horizontal" action="?p=addQuestions" method="POST">
			    <div id="hiddenFilter" style="width: 250px;">
			    		
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
			                    	} 
			                    ?>
			                </select>
			            </div>
			        </div>
			    </div>
			    <input type="hidden" name="quizId" value="<?php echo $quizId; ?>">
		    </form>
		    <form id="addQuestions" class="form-horizontal" action="?p=actionHandler&action=addQuestions" method="POST">
			    <div class="listOfQuizzes">
			        <table class="tblListOfQuizzes" id="questions" style="width: 100%">
			            <thead>
			                <tr>
			                	<th>
			                		<?php echo $lang["selection"]?>
			                	</th>
			                    <th>
			                        <?php echo $lang["questionQuestionText"]?>
			                    </th>
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
			                    <th>
			                        <?php echo $lang["questionCalcCorrectAnswer"]?>
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
			                $resultArray = $stmt->fetchAll();
			                
			                $stmt = $dbh->prepare("select question_id from qunaire_qu where questionnaire_id = :quizId");
			                $stmt->bindParam(":quizId", $quizId);
			                $stmt->execute();
			                $fetchAddedQuestionsForQuiz = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
			                
			                for($i = 0; $i < count($resultArray); $i++) {
							?>
			                    <tr class="entry" id="<?php echo "question_" . $resultArray[$i]["q_id"];?>">
			                        <?php 
			                        	$qType = "singlechoice";
				                        if($resultArray[$i]["type_id"] == 2)
				                        	$qType = "multiplechoice";
			                        ?>
			                        <td>
			                        	<input type="checkbox" name="questions[]" value="<?php echo $resultArray[$i]["q_id"];?>" <?php 
			                        	if(in_array($resultArray[$i]["q_id"], $fetchAddedQuestionsForQuiz))
			                        		echo " checked=\"checked\"";
			                        	?>>
			                        </td>
			                        <td title="<?php echo htmlspecialchars($resultArray[$i]["text"]);?>">
			                            <img width="15" height="15" class="questionTypeInfo" original-title="<?php echo $qType;?>" style="margin-right: 5px; margin-bottom: 3px;" src="assets/icon_<?php echo $qType;?>.png"><a href=""><?php echo substr($resultArray[$i]["text"], 0, 15);?></a>
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
			                            $stmt = $dbh->prepare("select questionnaire_id from qunaire_qu where question_id = " . $resultArray[$i]["q_id"]);
			                            $stmt->execute();
			                            echo $stmt->rowCount();
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
			                    </tr>
			                <?php }?>
			            </tbody>
			        </table>
			        <br />
			        <input type="hidden" name="quizId" value="<?php echo $quizId;?>">
			        <input type="submit" class="btn" name="submit" value="<?php echo $lang["addQuestions"];?>" onclick="clearFilter()">
			    </div>
			</form>
		</div>
	</div>
</div>