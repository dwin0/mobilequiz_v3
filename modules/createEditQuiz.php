<?php
	include_once 'modules/extraFunctions.php';
	include_once 'errorCodeHandler.php';
	

	$code = 0;
	if(!isset($_GET["id"]))
	{
		$mode = "create";
		$code = -20;
	}
	
	$maxCharactersQuiz = 30;
	$maxCharactersTopic = 30;
	$maxCharactersQuizDesc = 120;
	
	$codeText = "";
	$mode = "create";
	if(isset($_GET["mode"]))
	{
		$mode = $_GET["mode"];
	}
	
	if($mode == 'edit')
	{
		$stmt = $dbh->prepare("select questionnaire.*, user_data.firstname, user_data.lastname from questionnaire inner join user on user.id = questionnaire.owner_id inner join user_data on user_data.user_id = user.id where questionnaire.id = :id");
		$stmt->bindParam(":id", $_GET["id"]);
		$stmt->execute();
		if($stmt->rowCount() > 0)
			$quizFetch = $stmt->fetch(PDO::FETCH_ASSOC);
		else
		{
			$mode = "create";
			$code = -21;
		}
	} else if($mode == 'create') {
		
		$code = 0;
		if($_SESSION["language"] == "ger")
		{
			$language = "Deutsch";
		} else {
			$language = "English";
		}
		
		$stmt = $dbh->prepare("insert into questionnaire (owner_id, subject_id, name, language, description, creation_date, last_modified)
					values (" . $_SESSION["id"] . ", NULL, '', '" . $language . "', '', ".time().", ".time().")");
		
		$stmt->execute();
		
		$newQuizId = $dbh->lastInsertId();
	}
	
	if($_SESSION["role"]["user"])
	{
		if(! $_SESSION["role"]["creator"] && $quizFetch["owner_id"] != $_SESSION["id"] && ($mode == 'edit' && !amIAssignedToThisQuiz($dbh, $_GET["id"])))
		{
			header("Location: ?p=quiz&code=-1&info=qqq");
			exit;
		}
	}
	else
	{
		header("Location: ?p=home&code=-20");
		exit;
	}
	
	if($code < 0)
	{
		header("Location: ?p=home&code=" . $code);
		exit;
	}
	
	$errorCode = new mobileError("", "red");
	if(isset($_GET["code"]))
	{
		$errorCode = handleCreateEditQuizError($_GET["code"]);
	}
	
	
	$stmt = $dbh->prepare("select email, id from user");
	$stmt->execute();
	$fetchUserMails = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" type="text/css" href="css/style.css" />
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $mode == "create" ? $lang["createQuiz"] : str_replace("[1]", '&laquo;' . $quizFetch["name"] . '&raquo;', $lang["editQuiz"]);?></h1>
	</div>
	<?php if($_GET["code"] != 0) {?>
	<p id="createEditQuizActionResult" style="color:<?php echo $errorCode->getColor();?>;"><?php echo $errorCode->getText();?></p>
	<?php }?>
	<p><?php echo $lang["requiredFields"];?></p>
	
	<ul id="createEditQuizTab" class="nav nav-tabs">
        <li class="active"><a href="#generalInformation" data-toggle="tab">Allgemeine Informationen</a></li>
        <li><a href="#questions" data-toggle="tab">Fragen</a></li>
        <li><a href="#execution" data-toggle="tab">Durchf&uuml;hrungen</a></li>
    </ul>
	
	<div id="createEditQuizTabContent" class="tab-content" >
        <div class="tab-pane fade in active form-horizontal panel-body" id="generalInformation">
			
			<!-- Quiz Name -->
			<div class="form-group">
				<label for="quizText" class="col-md-2 col-sm-3 control-label">
					<?php echo $lang["quizCreateName"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<input id="quizText" name="quizText" class="form-control" type="text"
						required="required"
						placeholder="<?php echo $lang["quizCreateName"] . " (" . $lang["maximum"] . " " . $maxCharactersQuiz . " " . $lang["characters"] . ")";?>"
						maxlength="<?php echo $maxCharactersQuiz;?>" value="<?php 
						if($mode == "edit")
						{
							echo $quizFetch["name"];
						}
						?>"/>
				</div>
			</div>
			
			<!-- Quiz Description -->
			<div class="form-group">
				<label for="description" class="col-md-2 col-sm-3 control-label">
					<?php echo $lang["description"];?>
				</label>
				<div class="col-md-10 col-sm-9">
					<textarea name="description" id="description"
						class="form-control text-input" wrap="soft" placeholder="<?php echo $lang["descriptionOfQuiz"] . " (" . $lang["maximum"] . " " . $maxCharactersQuizDesc . " " . $lang["characters"] . ")";?>"><?php 
						if($mode == "edit")
						{
							echo $quizFetch["description"];
						}
						?></textarea>
				</div>
			</div>
			
			<!-- Quiz Language -->
			<div class="form-group">
				<label for="language" class="col-md-2 col-sm-3 control-label"> 
					<?php echo $lang["quizLanguage"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<select id="language" class="form-control" name="language" required="required" onchange="showNewLanguageInput()">
	                	<?php         	
	                    $stmt = $dbh->prepare("select language from questionnaire group by language");
	                    $stmt->execute();
	                    $allLanguages = $stmt->fetchAll();
	                    
	                    for($i = 0; $i < count($allLanguages); $i++){
							$stmt = $dbh->prepare("select id from questionnaire where language = '" . $allLanguages[$i]["language"] . "'");
							$stmt->execute();
							$selected = "";
							if($mode == "edit")
								$selected = ($quizFetch["language"] == $allLanguages[$i]["language"]) ? ' selected="selected"' : '';
							echo "<option value=\"" . $allLanguages[$i]["language"] . "\"" . $selected . ">" . $allLanguages[$i]["language"] . " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")</option>";
	                    } ?>
	                    <option value="newLanguage"><?php echo $lang["requestNewLanguage"];?></option>
	                </select> 
	                <input type="text" id="newLanguage"
						class="form-control" name="newLanguage"
						placeholder="<?php echo $lang["newLanguagePlaceholder"];?>"
						maxlength="30" style="display:none;"/>
				</div>
			</div>
			
			<!-- Quiz Topic -->
			<div class="form-group">
				<label for="applicationArea"
					class="col-md-2 col-sm-3 control-label"> 
					<?php echo $lang["quizTableTopic"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<select id="topic" class="form-control" name="topic" required="required" onchange="showNewTopicInput()">
		                    <?php 
		                    $stmt = $dbh->prepare("select * from subjects");
		                    $stmt->execute();
		                    $allLanguages = $stmt->fetchAll();
		                    
		                    for($i = 0; $i < count($allLanguages); $i++){
								$stmt = $dbh->prepare("select id from questionnaire where subject_id = " . $allLanguages[$i]["id"]);
								$stmt->execute();
								$rowCount = $stmt->rowCount();
								
								$selected = "";
								if($mode == "edit")
									$selected = ($quizFetch["subject_id"] == $allLanguages[$i]["id"]) ? 'selected="selected"' : '';
								echo "<option value=\"" . $allLanguages[$i]["id"] . "\" " . $selected . ">" . $allLanguages[$i]["name"] . " (" . $rowCount . " " . $lang["quizzes"] . ")</option>";
		                    } ?>
		                    <option value="null"
							<?php if($mode != "edit") {echo ' selected="selected"';} else {
		                    	if($quizFetch["subject_id"] == NULL) {echo ' selected="selected"';}
		                    }?>>Nicht zugeordnet <?php 
			                    $stmt = $dbh->prepare("select id from questionnaire where subject_id is null");
			                    $stmt->execute();
			                    echo " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")";
		                    ?></option>
		                    <option value="newTopic"><?php echo $lang["requestNewTopic"];?></option>
					</select> 
					<input type="text" id="newTopic" class="form-control"
						name="newTopic"
						placeholder="<?php echo $lang["newTopicPlaceholder"] . " (" . $lang["maximum"] . " " . $maxCharactersTopic . " " . $lang["characters"] . ")";?>"
						maxlength="<?php echo $maxCharactersTopic?>"  style="display:none;"/>
				</div>
			</div>
			
			
			<?php if($mode == 'edit') {?>
			<div class="form-group">
				<label class="col-md-2 col-sm-3 control-label"> 
		        	<?php echo $lang["createdBy"];?>
				</label>
				<div class="col-md-10 col-sm-9">
					<p class="form-control-static">
					<?php 
						if($mode == "edit")
						{
							echo $quizFetch["firstname"] . " " . $quizFetch["lastname"];
						}
					?>
					</p>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 col-sm-3 control-label"> 
		        	<?php echo $lang["createdAt"];?>
				</label>
				<div class="col-md-10 col-sm-9">
					<p class="form-control-static">
					<?php 
						if($mode == "edit")
						{
							echo date("d.m.Y H:i:s", $quizFetch["creation_date"]);
						}
					?>
					</p>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 col-sm-3 control-label"> 
		        	<?php echo $lang["lastModified"];?>
				</label>
				<div class="col-md-10 col-sm-9">
					<p class="form-control-static">
					<?php 
						if($mode == "edit")
						{
							echo date("d.m.Y H:i:s", $quizFetch["last_modified"]);
						}
					?>
					</p>
				</div>
			</div>
			<?php }?>
			
			<!-- Assign New Responsible Member -->
			<fieldset class="table-border">
				<legend class="table-border"><?php echo $lang["additionalSettings"];?></legend>
				<div class="form-group assignationMngmt">
					<div> 
						<label class="col-md-2 col-sm-3 control-label"><?php echo $lang["assignQuizToMember"];?><img id="assignationHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 5px;" original-title="Hier k&ouml;nnen Benutzer eingetragen werden, welche die Berechtigungen bekommen dieses Quiz zu bearbeiten oder die Ergebnisse einzusehen" width="18" height="18"></label>
					</div>
					<div class="col-md-10 col-sm-9">
						<input type="email" id="autocompleteUsers"><img id="addUser" style="margin-left: 8px; cursor: pointer" alt="add" src="assets/arrow-right.png" width="28" height="32" onclick="addCreator(<?php echo $quizFetch["id"];?>)">
					</div>
				</div>
				<div class="from-group">
					<div class="col-md-10 col-sm-9" id="ajaxAnswer"></div>
				</div>
				<div class="table-responsive">
					<?php 
					$quizId = $newQuizId;
					if($mode == "edit")
					{
						$quizId = $_GET["id"];
					}
					$stmt = $dbh->prepare("select user.email, user_id from qunaire_assigned_to inner join user on user.id = qunaire_assigned_to.user_id where questionnaire_id = :qId");
					$stmt->bindParam(":qId", $quizId);
					$stmt->execute();
					$fetchAssigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
					?>
					<table class="assignTbl" id="assignTbl">
			            <thead>
			                <tr>
			                    <th><?php echo $lang["email"];?></th>
			                    <th><?php echo $lang["quizTableActions"];?></th>
			                </tr>
			            </thead>
			            <tbody>
			            	<?php for ($i = 0; $i < count($fetchAssigns); $i++) {?>
			            	<tr id="<?php echo "assignation_" . $fetchAssigns[$i]["user_id"];?>">
			            		<td><?php echo $fetchAssigns[$i]["email"];?></td>
			            		<td><img id="delAssignedId" class="deleteAssigned delAssignedImg" src="assets/icon_delete.png" style="cursor: pointer;" alt="" original-title="Berechtigung entziehen" height="18px" width="18px" onclick="delAssigned(<?php echo $fetchAssigns[$i]["user_id"];?>)"></td>
			            	</tr>
			            	<?php }?>
			            </tbody>
		        	</table>
	        	</div>
        	</fieldset>

        </div>
        
        <div class="tab-pane fade from-horizontal panel-body" id="questions">
       		
       		<input id="btnAddNewQuestion" name="btnAddNewQuestion" class="btn" type="button" value="<?php echo $lang["addNewQuestion"];?>" /><br />
			<input id="btnAddExistingQuestion" name="btnAddExistingQuestion" class="btn" type="button" value="<?php echo $lang["addExistingQuestion"];?>" style="margin-top: 10px;"/><br />
			<input id="btnImportQuestion" name="btnImportQuestion" class="btn" type="button" value="<?php echo $lang["importQuestionsFromExcel"];?>" onclick="openExcelDialog()" style="margin-top: 10px;"/>&nbsp;<span id="fileName"><?php echo " <b>" . $lang["noFileSelected"] . "</b>";?></span><br />
			<input id="btnImportImageQuestion" name="btnImportQuestion" class="btn" type="button" value="<?php echo $lang["importQuestionsFromExcelWithImages"];?>" onclick="openDirectoryDialog()" style="margin-top: 10px; margin-bottom: 10px;"/>&nbsp;<span id="fileNames"><?php echo " <b>" . $lang["noFolderSelected"] . "</b>";?></span><br />
			
			<input type="file" style="display:none;" name="btnImportQuestionsFromExcel" id="btnImportQuestionsFromExcel" accept=".xlsx"/>
			<input type="file" style="display:none;" name="btnImportQuestionsFromDirectory[]" id="btnImportQuestionsFromDirectory" multiple directory="" webkitdirectory="" mozdirectory=""/>
           
           	<div class="table-responsive">
           	<table class="tblListOfQuestions" id="tblListOfQuestions">
	            <thead>
	                <tr>
	                	<th style="display: none;"></th>
	                    <th><?php echo $lang["positionShort"];?></th>
	                    <th></th>
	                    <th><?php echo $lang["questionQuestionText"];?></th>
	                    <th><?php echo $lang["questionAmountAnswers"];?></th>
	                    <th><?php echo $lang["results"];?></th>
	                    <th><?php echo $lang["quizTableActions"];?></th>
	                </tr>
	            </thead>
		            
				<?php 
				if($mode == "edit")
				{
					$stmt = $dbh->prepare("select questionnaire.owner_id, question_id, question.text, type_id from qunaire_qu inner join question on question.id = qunaire_qu.question_id inner join questionnaire on questionnaire.id = qunaire_qu.questionnaire_id where questionnaire_id = " . $quizFetch["id"] . " order by qunaire_qu.order");
					$stmt->execute();
					$fetchQnaireQu = $stmt->fetchAll(PDO::FETCH_ASSOC);
				
				?>
						
		            <tbody id="sortable">
		            	<?php for ($i=0; $i < count($fetchQnaireQu); $i++) {
                        	$qType = "singlechoice";
	                        if($fetchQnaireQu[$i]["type_id"] == 2)
	                        	$qType = "multiplechoice";
                        ?>
		            	<tr id="<?php echo "question_" . $fetchQnaireQu[$i]["question_id"];?>">
		            		<td class="qId" style="display: none;"><?php echo $fetchQnaireQu[$i]["question_id"];?></td>
		            		<td><?php echo ($i+1);?></td>
		            		<td>
		            		<img alt="up" src="assets/icon_downUp.png" width="18" height="18" style="cursor: move;">
		            		</td>
		            		<td><img width="15" height="15" class="questionTypeInfo" original-title="<?php echo $qType;?>" style="margin-right: 5px; margin-bottom: 3px;" src="assets/icon_<?php echo $qType;?>.png"><?php echo $fetchQnaireQu[$i]["text"];?></td>
		            		<td>
		            		<?php 
			            		$stmt = $dbh->prepare("select answer_id from answer_question where question_id = :qId");
			            		$stmt->bindParam(":qId", $fetchQnaireQu[$i]["question_id"]);
			            		$stmt->execute();
		            		
		            			echo $stmt->rowCount();
							?>
		            		</td>
		            		<td>
			            		<?php 
	                        	$stmt = $dbh->prepare("select * from an_qu_user inner join answer_question on answer_question.answer_id = an_qu_user.answer_id where an_qu_user.question_id = :qId");
	                        	$stmt->bindParam(":qId", $fetchQnaireQu[$i]["question_id"]);
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
		            		<?php if($_SESSION['role']['admin'] == 1 || $fetchQnaireQu[$i]["owner_id"] == $_SESSION["id"]) {?>
	                            <a href="?p=createEditQuestion&mode=edit&fromsite=createEditQuiz&quizId=<?php echo $_GET["id"];?>&id=<?php echo $fetchQnaireQu[$i]["question_id"];?>" class="editQuestion" original-title="Frage bearbeiten"><img id="editQuestion" src="assets/icon_edit.png" alt="" height="18px" width="18px"></a>&nbsp;
	                            <img id="delQuestionImg" style="cursor: pointer;" class="deleteQuestion delQuestionImg" src="assets/icon_delete.png" alt="" original-title="Frage aus diesem Quiz l&ouml;schen" height="18px" width="18px" onclick="delQuestion(<?php echo $fetchQnaireQu[$i]["question_id"];?>)"><br />
	                        <?php }?>
		            		</td>
		            	</tr>
		            	<?php }?>
		            </tbody>
		        
		        <?php }?>
		        </table>
		        </div>           
           
           
        </div>
        
        
        
        
        
        <div class="tab-pane fade form-horizontal panel-body" id="execution">
		
			<input id="btnAddNewExecution" name="btnAddNewExecution" class="btn" type="button" value="<?php echo $lang["addNewExecution"];?>"/><br />
			
			<div class="table-responsive">
           		<table class="tblListOfExecutions" id="tblListOfExecutions">
		            <thead>
		                <tr>
		                    <th><?php echo $lang["executionName"];?></th>
		                    <th><?php echo $lang["executionPeriod"];?></th>
		                    <th><?php echo $lang["quizTableActions"];?></th>
		                </tr>
		            </thead>
		            <tbody>
		            
		            <?php 
					if($mode == "edit")
					{
						$stmt = $dbh->prepare("select execution_id from qunaire_exec where questionnaire_id = :qId");
						$stmt->bindParam(":qId", $_GET["id"]);
						$stmt->execute();
						$fetchExecId = $stmt->fetchAll(PDO::FETCH_ASSOC);
						
						if(count($fetchExecId) >= 1) {
							for($i=0; $i < count($fetchExecId); $i++) {
								$stmt = $dbh->prepare("select name, starttime, endtime from execution where id = :execId");
								$stmt->bindParam(":execId", $fetchExecId[$i]["execution_id"]);
								$stmt->execute();
								$fetchExec = $stmt->fetchAll(PDO::FETCH_ASSOC);
					?>		            
		            
		            	<tr id="<?php echo "execution_" . $fetchExecId[$i]["execution_id"];?>">
							<td>
								<?php echo $fetchExec[0]["name"]?>
							</td>	
							<td>
								<?php echo date("d.m.Y H:i", $fetchExec[0]["starttime"]) . " bis " . date("d.m.Y H:i", $fetchExec[0]["endtime"])?>
							</td>
							<td>
							<?php if($_SESSION['role']['admin'] == 1 || $fetchQnaireQu[$i]["owner_id"] == $_SESSION["id"]) {?>
	                            <a href="?p=createEditExecution&mode=edit&fromsite=createEditQuiz&execId=<?php echo $fetchExecId[$i]["execution_id"];?>" class="editExecution" original-title="Durchf&uuml;hrung bearbeiten"><img id="editExecution" src="assets/icon_edit.png" alt="" height="18px" width="18px"></a>&nbsp;
	                            <img id="delExecutionImg" style="cursor: pointer;" class="deleteExecution delExecutionImg" src="assets/icon_delete.png" alt="" original-title="Durchf&uuml;hrung aus diesem Quiz l&ouml;schen" height="18px" width="18px" onclick="delExec(<?php echo $fetchExecId[$i]["execution_id"];?>)"><br />
	                        <?php }?>
							</td>
		            	</tr>
		            <?php }}}?>
		            </tbody>
		        </table>
	        </div>
	        
		</div>
    </div>
	
	<div style="float: left; margin-top: 10px;">
		<input type="button" class="btn" id="btnBackToOverview" value="<?php echo $lang["buttonBackToOverview"];?>" onclick="window.location='?p=quiz';"/>
	</div>
	<input type="hidden" name="mode" value="<?php echo $mode;?>">
	<input type="hidden" name="quiz_id" value="<?php echo ($mode == "edit") ? $quizFetch["id"] : $newQuizId;?>">
	<div style="float: right; padding-left: 10px; margin-top: 10px;">
		<input type="hidden" name="btnSave" value="<?php echo $lang["buttonSaveAndPublish"];?>" />
		<input type="submit" class="btn" id="btnSave" name="btnSave" value="<?php echo $lang["buttonSaveAndPublish"];?>" />
	</div>

</div>

<div id="snackbar">Some text some message..</div>

<script src="js/spin.min.js"></script>
<script type="text/javascript" src="js/bootstrap-tabcollapse.js"></script>
<script type="text/javascript">

	$(document).ready(function() {
		
		$(document).on("change", "#quizText, #description, #language, #newLanguage, #topic, #newTopic", updateQuizData);
		
	});

	function openExcelDialog()
    {
        $('#btnImportQuestionsFromExcel').click();
    }
    
	function openDirectoryDialog()
    {
        $('#btnImportQuestionsFromDirectory').click();
    }

	function showNewLanguageInput()
	{
		if($('#language').val() == "newLanguage")
		{
			$( "#newLanguage" ).show("slow", false);
		}else {
			$( "#newLanguage" ).hide("slow", false);
		}
	}

	function showNewTopicInput()
	{
		if($('#topic').val() == "newTopic")
		{
			$( "#newTopic" ).show("slow", false);
		} else {
			$( "#newTopic" ).hide("slow", false);
		}
	}

	function addCreator()
	{
		var userEmail = $('#autocompleteUsers').val();
		var form = new FormData();
		form.append("userEmail", userEmail);
		form.append("questionnaireId", <?php echo isset($_GET["id"]) ? $_GET["id"] : $newQuizId;?>);
		
		$.ajax({
			url: '?p=actionHandler&action=addAssignation',
			type: 'POST',
			data: form,
			dataType: 'json',
			contentType: false,
			processData: false,
			cache: false,
			success: function(response) 
			{
				if(response["status"] == "OK")
				{
					showSnackbar("<?php echo $lang["saved"]?>");
					$('#ajaxAnswer').html('<span style="color: green;">Berechtigung zugewiesen.</span>');
					var rowData = [userEmail, '<img id="delAssignedId" class="deleteAssigned delAssignedImg" src="assets/icon_delete.png" style="cursor: pointer;" alt="" original-title="Berechtigung entziehen" height="18px" width="18px" onclick="delAssigned(' + response["userId"] + ')">'];
					var rowIndex = $('#assignTbl').dataTable().fnAddData(rowData);
					var row = $('#assignTbl').dataTable().fnGetNodes(rowIndex);
					$(row).attr("id", "assignation_"+response["userId"]);
					$('#autocompleteUsers').val('');
				}
				else if(response["status"] == "error")
				{
					$('#ajaxAnswer').html('<span style="color: red;">' + response["text"] + '</span>');
				}
			},
			error: function() 
			{
				$('#ajaxAnswer').html("<span style='color: red;'>Ajax couldn't send data.</span>");
			}	      
		});
	}

	function delAssigned(userId)
	{
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=delAssignation&userId="+userId+"&questionnaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : $newQuizId;?>,
			success: function(output) 
			{
				if(output == "ok1")
				{
					showSnackbar("<?php echo $lang["saved"]?>");
					$('#ajaxAnswer').html('<span style="color: green;">Berechtigung aberkannt.</span>');
					$('#assignTbl').DataTable().row($('#assignation_'+userId)).remove().draw();
					$('.tipsy').remove();
				}
				if(output == "failed")
				{
					$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
			}	      
		});
	}

	function delQuestion(qId)
	{
		console.log("del: " + qId);
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=delQuestionFromQuiz&questionId="+qId+"&questionnaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : $newQuizId;?>,
			success: function(output) 
			{
				if(output == "ok")
				{
					showSnackbar("<?php echo $lang["saved"]?>");
					$('#tblListOfQuestions').DataTable().row($('#question_'+qId)).remove().draw();
					$('.tipsy').remove();
				}
				if(output == "failed")
				{
					$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
			}
		});
	}

	function delExec(id)
	{
		if(confirm("<?php echo $lang["deleteConfirmation"] ?>")) 
		{
			var data = new FormData();
			data.append("execId", id);
			
			$.ajax({
		        url: '?p=actionHandler&action=delExec',
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
							$('#tblListOfExecutions').DataTable().row($('#execution_'+data.execId)).remove().draw();
							$('#createEditQuizActionResult').html("<span style=\"color: green;\"><?php echo $lang["executionSuccessfullyDeleted"];?>.</span>");
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
	}

	function formCheck()
	{
		$('#btnSave').prop('disabled', true);
		$('#btnBackToOverview').prop('disabled', true);
		var opts = {position: 'fixed', color: '#fff', length: 56, radius: 70, width: 22};
		var spinner = new Spinner(opts).spin();
		var over = '<div id="overlay"></div>';
		$(over).appendTo('body');
		$('body').append(spinner.el);
		return true;
	}

	var updateData = function(e, ui)
	{
		var qOrder = [];
		$('#sortable').find('.qId').each (function(col, td) {
			qOrder.push($(td).html());
		});   
		console.log(JSON.stringify(qOrder));
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=moveQuestion&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : $newQuizId;?>+"&qOrder="+JSON.stringify(qOrder),
			success: function(output) 
			{
				showSnackbar("<?php echo $lang["saved"]?>");
				console.log(output);
			}
		});
	}


	function updateQuizData(event)
	{
		$maxCharactersQuiz = 30;
		$maxCharactersTopic = 30;
		$maxCharactersQuizDesc = 120;
		
		if(this.value == this.oldvalue) return;

		var target = event.target.id;
		if(target == "") {
			target = event.target.name;
		}

		var url = '?p=actionHandler&action=updateQuiz';
		var field;
		var data = new FormData();

		switch(target) {
			case "quizText":
				field = "quizText";
			    data.append("quizText", event.target.value);
			    data.append("maxChar", $maxCharactersQuiz);
				break;
			case "description":
				field = "description";
				data.append("description", event.target.value);
				data.append("maxChar", $maxCharactersQuizDesc);
				break;
			case "language":
			case "newLanguage":
				field = "language";
				data.append("language", event.target.value);
				break;
			case "topic":
			case "newTopic":
				field = "topic";
				data.append("topic", event.target.value);
				break;
		}

		uploadChange(url, data, field);
	}
	

	function uploadChange(url, data, field) 
	{
		data.append("quizId", $("[name='quiz_id']").val());
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


	function sendExcelData(uploadData)
	{
		$.ajax({
	        url: "?p=actionHandler&action=uploadExcel",
	        type: 'POST',
	        data: uploadData,
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

						if(data.text != null)
						{
							alert(data.text);
						}

						for(var i = 0; i < data.counter; i++)
						{
							var rowData = [
								data["questionInfo_" + i]["id"],
								data["questionInfo_" + i]["nextId"],
								data["questionInfo_" + i]["upDownIcon"],
								data["questionInfo_" + i]["questionText"],
								data["questionInfo_" + i]["totalAnswers"],
								data["questionInfo_" + i]["results"],
								data["questionInfo_" + i]["icons"]
								];
							var rowIndex = $('#tblListOfQuestions').dataTable().fnAddData(rowData);
							var row = $('#tblListOfQuestions').dataTable().fnGetNodes(rowIndex);
							$(row).attr("id", "question_" + data["questionInfo_" + i]["id"]);
						}

						$('#tblListOfQuestions > tbody > tr >td:first-child').attr("class", "qId").css("display", "none");

						$("#btnImportQuestionsFromExcel").val(null);
						$("#btnImportQuestionsFromDirectory").val(null);
						
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
	

	$(function() {
		var tooltipElements = ['#assignationHelp', '.delAssignedImg', '.delQuestionImg', '.delExecutionImg', '.editQuestion', '.editExecution', '.questionTypeInfo'];

		$.each(tooltipElements, function(i, string){
			$(string).tipsy({gravity: 'n'});
		});

		
	    $( "#sortable" ).sortable({
		    stop: updateData}).disableSelection();

		
		$("#btnImportQuestionsFromExcel").on("change",function(){

			var file = $("#btnImportQuestionsFromExcel")[0].files[0];				
		    $("#fileName").html(file.name);
		    
			var data = new FormData();
			data.append("quizId", $("[name='quiz_id']").val());
			data.append("excelFile", file, file.name);
			data.append("uploadType", "withoutImages");

			sendExcelData(data);
		});
		

		$("#btnImportQuestionsFromDirectory").on("change", function() {

			var data = new FormData();
			data.append("quizId", $("[name='quiz_id']").val());
			data.append("uploadType", "withImages");
			
			var files = $("#btnImportQuestionsFromDirectory")[0].files;

			var fileNames = "";
			for(var i = 0; i < files.length; i++) {

				data.append("file_" + i, files[i]);
				fileNames += files[i].name;
				if(i+1 < files.length) {
					fileNames += ", ";
				}
			}
			$("#fileNames").html(fileNames);
			sendExcelData(data);
		});


		$("#btnAddNewQuestion").on("click", function() {

			if(formCheck())
			{
				var nextSite = "createEditQuestion";
				var mode = "create";
				var fromsite = "createEditQuiz";
				var quizId = <?php echo isset($_GET["id"]) ? $_GET["id"] : $newQuizId;?>;
				window.location = "?p=" + nextSite + "&mode=" + mode + "&fromsite=" + fromsite + "&quizId=" + quizId;
			}
		});

		$("#btnAddExistingQuestion").on("click", function() {

			if(formCheck())
			{
				var nextSite = "addQuestions";
				var quizId = <?php echo isset($_GET["id"]) ? $_GET["id"] : $newQuizId;?>;
				window.location = "?p=" + nextSite + "&quizId=" + quizId;
			}
		});

		$("#btnAddNewExecution").on("click", function() {
			window.location = "?p=createEditExecution&mode=create&fromsite=createEditQuiz&quizId=" + <?php echo isset($_GET["id"]) ? $_GET["id"] : $newQuizId;?>;
		});
		
		var sourceData = <?php echo json_encode(array_column($fetchUserMails, "email"));?>;
		$( "#autocompleteUsers" ).autocomplete({
		  source: sourceData
		});

		$('#tblListOfQuestions').DataTable({
            'bSort': true,
            'bPaginate': false,
            'bInfo': false,
            'bLengthChange': true,
            'aaSorting': [[1, 'asc']],
            'aoColumns': [
				{'bSearchable': false, 'bSortable':false},
                {'bSearchable': false, 'bSortable':true},
                {'bSearchable': false, 'bSortable':false},
                {'bSortable':false},
                {'bSearchable': false, 'bSortable':false},
                {'bSearchable': false},
                {'bSearchable': false, 'bSortable':false}
            ],
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Alle"]],
            "sDom": 'lfrtip',
            "oLanguage": {
                "sZeroRecords": "Es sind keine Fragen dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Fragen",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Fragen",
                "sInfoFiltered": "(von insgesamt _MAX_ Fragen)",
                "sSearch": ""
            }
        });
        $('.dataTables_filter input').attr("placeholder", 'Suchbegriff in Spalte "Fragetext (Beschreibung)" suchen');
        $('.dataTables_filter input').addClass("form-control");
        $('.dataTables_filter input').addClass("magnifyingGlass");
        $('.dataTables_filter input').attr("style", "min-width: 350px;");


        $('#tblListOfExecutions').DataTable({
            'bSort': true,
            'bPaginate': false,
            'bInfo': false,
            'bLengthChange': true,
            'aoColumns': [
                {'bSearchable': true, 'bSortable':true},
                {'bSearchable': false, 'bSortable':true},
                {'bSearchable': false, 'bSortable':false}
            ],
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Alle"]],
            "sDom": 'lfrtip',
            "oLanguage": {
                "sZeroRecords": "Es sind keine Durchf&uuml;hrungen dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Durchf&uuml;hrungen",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Durchf&uuml;hrungen",
                "sInfoFiltered": "(von insgesamt _MAX_ Durchf&uuml;hrungen)",
                "sSearch": ""
            }
        });
        $('.dataTables_filter input').addClass("form-control");
        $('.dataTables_filter input').addClass("magnifyingGlass");
        $('.dataTables_filter input').attr("style", "min-width: 350px;");

        
        $('#assignTbl').DataTable({
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
                "sZeroRecords": "Es sind keine Berechtigungen vergeben worden",
                "sSearch": ""
            }
        });
        $('.dataTables_filter input').addClass("form-control");
        $('.dataTables_filter input').addClass("magnifyingGlass");
        $('.dataTables_filter').attr("style", "margin-top: 0");
        $('.dataTables_filter input').attr("style", "min-width: 350px;");

		$('#createEditQuizTab').tabCollapse();
	});

	function showSnackbar(text) {
		var snackbar = $("#snackbar");
		snackbar.text(text);
	    snackbar.addClass("show");
	    setTimeout(function(){ snackbar.removeClass("show"); }, 3000);
	}
</script>