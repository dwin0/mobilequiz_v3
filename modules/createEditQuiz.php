<?php
	include_once 'modules/extraFunctions.php';
	include_once 'errorCodeHandler.php';
	

	if(!isset($_GET["id"]))
	{
		$mode = "create";
		$code = -1;
	}
	
	$maxCharactersQuiz = 30;
	$maxCharactersTopic = 30;
	$maxCharactersQuizDesc = 120;
	
	$code = 0;
	$codeText = "";
	$mode = "create";
	if(isset($_GET["mode"]))
	{
		$mode = $_GET["mode"];
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
			$code = -2;
		}
	} else if($mode == 'create') {
		
		if($_SESSION["language"] == "ger")
		{
			$language = "Deutsch";
		} else {
			$language = "English";
		}
		
		$stmt = $dbh->prepare("insert into questionnaire (owner_id, subject_id, name, starttime, endtime, qnaire_token, random_questions, random_answers, limited_time, result_visible, result_visible_points, language, amount_of_questions, public, description, creation_date, last_modified, priority, amount_participations, quiz_passed, singlechoise_multiplier, noParticipationPeriod, showTaskPaper)
					values (" . $_SESSION["id"] . ", NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, '" . $language . "', NULL, NULL, '', ".time().", ".time().", NULL, NULL, NULL, NULL, NULL, NULL)");
		
		$stmt->execute();
		
		$newQuizId = $dbh->lastInsertId();
	}
	
	$errorCode = new mobileError("", "red");
	if(isset($_GET["code"]))
	{
		$errorCode = handleCreateEditQuizError($_GET["code"]);
	}
	
	
	$stmt = $dbh->prepare("select email, id from user");
	$stmt->execute();
	$fetchUserMails = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$dummy = $fetchUserMails[0]["email"];
	
	// Wird nirgends benötigt? Kann das gelöscht werden?
	function getIdToEMail($var)
	{
		for ($i = 0; $i < count($fetchUserMails); $i++) {
			if($fetchUserMails[$i]["email"] == $var) {
				return $fetchUserMails[$i]["id"];
			}
		}
		return -1;
	}
?>
<script src="js/spin.min.js"></script>
<script type="text/javascript">

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
		console.log(userEmail);
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=addAssignation&userEmail="+userEmail+"&questionnaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : $newQuizId;?>,
			success: function(output) 
			{
				if(output == "ok1")
				{
					$('#ajaxAnswer').html('<span style="color: green;">Berechtigung zugewiesen.</span>');
					//$('#assignTbl > tbody:last-child').append('<tr><td>'+userEmail+'</td><td></td></tr>');
					$('#assignTbl').DataTable().row.add([userEmail, '']).draw(false);
					$('#autocompleteUsers').val('');
					console.log("ok");
				}
				if(output == "failed")
				{
					$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
					console.log("Fehler1");
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer').html('<span style="color: red;">Fehler.</span>');
				console.log("Fehler2");
			}	      
		});
	}

	function delAssigned(userId)
	{
		console.log("del: " + userId);

		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=delAssignation&userId="+userId+"&questionnaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : $newQuizId;?>,
			success: function(output) 
			{
				if(output == "ok1")
				{
					$('#ajaxAnswer').html('<span style="color: green;">Berechtigung aberkannt.</span>');
					//$('#assignation_'+userId).css('display', 'none');
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
				console.log(output);
			}
		});
	}

	$(function() {
		var tooltipElements = ['#assignationHelp', '.delAssignedImg', '.delQuestionImg', '.editQuestion', '.questionTypeInfo'];

		$.each(tooltipElements, function(i, string){
			$(string).tipsy({gravity: 'n'});
		});

		
	    $( "#sortable" ).sortable({
		    stop: updateData}).disableSelection();

		
		document.getElementById("btnImportQuestionsFromExcel").addEventListener("change",function(){
		    document.getElementById("fileName").innerHTML = document.getElementById("btnImportQuestionsFromExcel").files[0].name;
		});

		document.getElementById("btnImportQuestionsFromDirectory").addEventListener("change",function(){			
			var files = document.getElementById("btnImportQuestionsFromDirectory").files;

			var fileNames = "";
			for(var i = 0; i < files.length; i++) {
				fileNames += files[i].name;
				if(i+1 < files.length) {
					fileNames += ", ";
				}
			}
			document.getElementById("fileNames").innerHTML = fileNames;
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
				{'bSearchable': false, 'bSortable': false},
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
        $('.dataTables_filter input').attr("style", "min-width: 350px;");
	
	});
    
</script>

<script type="text/javascript" src="js/bootstrap-tabcollapse.js"></script>
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
			<div class="form-group">
				<div> 
					<label class="col-md-2 col-sm-3 control-label"><?php echo $lang["assignQuizToMember"];?><img id="assignationHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 5px;" original-title="Hier k&ouml;nnen Benutzer eingetragen werden, welche die Berechtigungen bekommen dieses Quiz zu bearbeiten oder die Ergebnisse einzusehen" width="18" height="18"></label>
				</div>
				<div class="col-md-10 col-sm-9">
					<input type="email" id="autocompleteUsers"><img id="addUser" style="margin-left: 8px;" alt="add" src="assets/arrow-right.png" width="28" height="32" onclick="addCreator(<?php echo $quizFetch["id"];?>)">
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

				
				
				

        </div>
        
        <div class="tab-pane fade from-horizontal panel-body" id="questions">
       		
       		<input id="btnAddNewQuestion" name="btnAddNewQuestion" class="btn" onclick="" type="button" value="<?php echo $lang["addNewQuestion"];?>" /><br />
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
	                            <img id="delQuestionImg" style="cursor: pointer;" class="deleteQuestion delQuestionImg" src="assets/icon_delete.png" alt="" original-title="Frage aus dieser Lernkontrolle l&ouml;schen" height="18px" width="18px" onclick="delQuestion(<?php echo $fetchQnaireQu[$i]["question_id"];?>)"><br />
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
		
			<input id="btnAddNewExecution" name="btnAddNewExecution" class="btn" onclick="" type="button" value="<?php echo $lang["addNewExecution"];?>" /><br />
			
			<div class="table-responsive">
           		<table class="tblListOfExecutions" id="tblListOfExecutions">
		            <thead>
		                <tr>
		                    <th><?php echo $lang["executionName"];?></th>
		                    <th><?php echo $lang["executionPeriod"];?></th>
		                    <th><?php echo $lang["quizTableActions"];?></th>
		                </tr>
		            </thead>
		        </table>
	        </div>
	        
		</div>

    </div>
		
	<script type="text/javascript">
		$(document).ready(function() {
			
			$(document).on("change", "#quizText, #description, #language, #newLanguage, #topic, #newTopic", updateQuizData);

			$('#addUser').on('mouseover', function(){
				this.style.cursor='pointer';
		    });
			
		});



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
							console.log("OK");
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
		
	    
	    $('#createEditQuizTab').tabCollapse();
	</script>
	
	
	
	
	<form id="createQuiz"
		action="<?php echo "?p=actionHandler&action=insertQuiz&mode=" . $mode;?>"
		class="form-horizontal" method="POST" enctype="multipart/form-data"
		onsubmit="return formCheck()">
		

		
			
		
		<div style="float: left; margin-top: 10px;">
			<input type="button" class="btn" id="btnBackToOverview" value="<?php echo $lang["buttonBackToOverview"];?>" onclick="window.location='?p=quiz';"/>
		</div>
		<input type="hidden" name="mode" value="<?php echo $mode;?>">
		<input type="hidden" name="quiz_id" value="<?php echo ($mode == "edit") ? $quizFetch["id"] : $newQuizId;;?>">
		<div style="float: right; padding-left: 10px; margin-top: 10px;">
			<input type="hidden" name="btnSave" value="<?php echo $lang["buttonSaveAndPublish"];?>" />
			<input type="submit" class="btn" id="btnSave" name="btnSave" value="<?php echo $lang["buttonSaveAndPublish"];?>" />
		</div>
	</form>
</div>