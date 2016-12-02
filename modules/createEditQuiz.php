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
		if(! $_SESSION["role"]["creator"] && ($mode == 'edit' && !amIAssignedToThisQuiz($dbh, $_GET["id"])))
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
	}
	
	// can be merged with the upper role comparation
	if(! $_SESSION['role']['creator'] && $quizFetch["owner_id"] != $_SESSION["id"] && !amIAssignedToThisQuiz($dbh, $_GET["id"])) {
		header("Location: ?p=quiz&code=-1&info=lll");
		exit;
	}
	
	
	
	$errorCode = new mobileError("", "red");
	if(isset($_GET["code"]))
	{
		$errorCode = handleCreateEditQuizError($_GET["code"]);
	}
	
	
	$stmt = $dbh->prepare("select id, email from user");
	$stmt->execute();
	$fetchUserMails = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
	$fetchUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	function getIdToName($var)
	{
		for ($i = 0; $i < count($fetchUsers); $i++) {
			if($fetchUsers[$i]["email"] == $var)
				return $fetchUsers[$i]["id"];
		}
		return -1;
	}
?>
<script src="js/spin.min.js"></script>
<script type="text/javascript">

	var shouldConfirm = false;
	window.onbeforeunload = function() {
        if(shouldConfirm) {
            return "Seite wirklich verlassen?";
        }
    }

    function setConfirm(val, elem)
    {
    	shouldConfirm = val;
    	//console.log("e:" + elem);
    }

	function openExcelDialog()
    {
        $('#btnImportQuestionsFromExcel').click();
    }
    
	function openDirectoryDialog()
    {
        $('#btnImportQuestionsFromDirectory').click();
    }

	function setChecked(id)
	{
		$('#' + id).prop('checked', true);
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

	function addCreator()
	{
		var userEmail = $('#autocompleteUsers').val();
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=addAssignation&userEmail="+userEmail+"&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : -1;?>,
			success: function(output) 
			{
				if(output == "ok1")
				{
					$('#ajaxAnswer').html('<span style="color: green;">Berechtigung zugewiesen.</span>');
					//$('#assignTbl > tbody:last-child').append('<tr><td>'+userEmail+'</td><td></td></tr>');
					$('#assignTbl').DataTable().row.add([userEmail, '']).draw(false);
					$('#autocompleteUsers').val('');
					
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

	function delAssigned(userId)
	{
		console.log("del: " + userId);
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=delAssignation&userId="+userId+"&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : -1;?>,
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
			data: "action=delQuestionFromQuiz&questionId="+qId+"&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : -1;?>,
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
		console.log("ASdasd");
		$('#btnSave').prop('disabled', true);
		$('#btnSaveAsDraft').prop('disabled', true);
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
			data: "action=moveQuestion&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : -1;?>+"&qOrder="+JSON.stringify(qOrder),
			success: function(output) 
			{
				console.log(output);
			}
		});
	}

	$(function() {
		var tooltipElements = ['#singlechoiseMultHelp', '#assignationHelp', '.delAssignedImg', '.delQuestionImg',
			'.editQuestion', '.groupName', '.questionTypeInfo', '#showQuizTaskPaperHelp'];

		$.each(tooltipElements, function(i, string)
			{
				$(string).tipsy({gravity: 'n'});
			});

		setDatesEnabled();
		$("#groupAddSuccess").hide();
		$("#groupAddError").hide();
		
	    $( "#sortable" ).sortable({
		    stop: updateData}).disableSelection();

		
		document.getElementById("btnImportQuestionsFromExcel").addEventListener("change",function(){
		    document.getElementById("fileName").innerHTML = document.getElementById("btnImportQuestionsFromExcel").files[0].name;
		    shouldConfirm = true;
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
		    shouldConfirm = true;
		});

		$( "#additionalSettingsContent" ).hide();	
		var additionalSettingsContent = false;	
		$("#additionalSettingsHeading").click(function() {
			$( "#additionalSettingsContent" ).toggle("slow", false);
			additionalSettingsContent = !additionalSettingsContent;
			if(!additionalSettingsContent)
				$('#arrow1').html('&#9654;');
			else
				$('#arrow1').html('&#9660;');
		});

		var sourceData = <?php echo json_encode($fetchUserMails);?>;
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

        //TODO gehört in createEditExecution
		$( "#assignGroupToQuizSortable1, #assignGroupToQuizSortable2" ).sortable({
			connectWith: ".assignGroupToQuizCconnectedSortable"
		}).disableSelection();

		$( "#assignGroupToQuizSortable1, #assignGroupToQuizSortable2" ).sortable({
				stop: function( event, ui ) {
					console.log("changed");
					var assignedGroups = [];
					$( "#assignGroupToQuizSortable1 li").each(function(index, elem) {
						assignedGroups.push($(elem).attr('id'));
					});
					console.log("assignedGroups: " + JSON.stringify(assignedGroups));
					$.ajax({
						url: 'modules/actionHandler.php',
						type: "get",
						data: "action=changeAssignedGroups&questionaireId="+<?php echo isset($_GET["id"]) ? $_GET["id"] : -1;?>+"&groups="+JSON.stringify(assignedGroups),
						success: function(output) 
						{
							//alertify
							if(output == "ok") {
								
								console.log("success: " + output);
				                $("#groupAddSuccess").slideDown(1000);   
				                $("#groupAddSuccess").fadeTo(2000, 500).slideUp(1000);   
							} else {
								console.log("error: " + output);
				                $("#groupAddError").slideDown(1000);   
				                $("#groupAddError").fadeTo(2000, 500).slideUp(1000); 
							}
						}, error: function(output)
						{
							console.log("error: " + output);
			                $("#groupAddError").slideDown(1000);   
			                $("#groupAddError").fadeTo(2000, 500).slideUp(1000); 
						}
					});
				}
			});
		
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
			<div class="form-group">
				<label for="quizText" class="col-md-2 col-sm-3 control-label">
					<?php echo $lang["quizCreateName"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<input id="quizText" name="quizText" class="form-control" type="text"
						onchange="setConfirm(true, 'name')"
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
			<div class="form-group">
				<label for="description" class="col-md-2 col-sm-3 control-label">
					<?php echo $lang["description"];?>
				</label>
				<div class="col-md-10 col-sm-9">
					<textarea name="description" id="description" onchange="setConfirm(true, 'desc')"
						class="form-control text-input" wrap="soft" placeholder="<?php echo $lang["descriptionOfQuiz"] . " (" . $lang["maximum"] . " " . $maxCharactersQuizDesc . " " . $lang["characters"] . ")";?>"><?php 
						if($mode == "edit")
						{
							echo $quizFetch["description"];
						}
						?></textarea>
				</div>
			</div>
			<div class="form-group">
				<label for="language" class="col-md-2 col-sm-3 control-label"> 
					<?php echo $lang["quizLanguage"];?> *
				</label>
				<div class="col-md-10 col-sm-9">
					<select id="language" class="form-control" name="language" required="required" onchange="showNewLanguageInput()">
	                	<?php 
	                	$stmt = $dbh->prepare("select id from questionnaire");
	                	$stmt->execute();
	                	$allQuestionsCount = $stmt->rowCount();
	                	
	                    $stmt = $dbh->prepare("select language from questionnaire group by language");
	                    $stmt->execute();
	                    $result = $stmt->fetchAll();
	                    
	                    for($i = 0; $i < count($result); $i++){
							$stmt = $dbh->prepare("select id from questionnaire where language = '" . $result[$i]["language"] . "'");
							$stmt->execute();
							$selected = "";
							if($mode == "edit")
								$selected = ($quizFetch["language"] == $result[$i]["language"]) ? ' selected="selected"' : '';
							echo "<option value=\"" . $result[$i]["language"] . "\"" . $selected . ">" . $result[$i]["language"] . " (" . $stmt->rowCount() . " " . $lang["quizzes"] . ")</option>";
	                    } ?>
	                    <option value="newLanguage"><?php echo $lang["requestNewLanguage"];?></option>
	                </select> 
	                <input type="text" id="newLanguage"
						class="form-control" name="newLanguage"
						placeholder="<?php echo $lang["newLanguagePlaceholder"];?>"
						maxlength="30" style="display:none;"/>
				</div>
			</div>
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
		                    $result = $stmt->fetchAll();
		                    
		                    for($i = 0; $i < count($result); $i++){
								$stmt = $dbh->prepare("select id from questionnaire where subject_id = " . $result[$i]["id"]);
								$stmt->execute();
								$rowCount = $stmt->rowCount();
								
								$selected = "";
								if($mode == "edit")
									$selected = ($quizFetch["subject_id"] == $result[$i]["id"]) ? 'selected="selected"' : '';
								echo "<option value=\"" . $result[$i]["id"] . "\" " . $selected . ">" . $result[$i]["name"] . " (" . $rowCount . " " . $lang["quizzes"] . ")</option>";
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
			

			<div class="form-group">
				<div> 
					<label class="col-md-2 col-sm-3 control-label"><?php echo $lang["assignQuizToMember"];?><img id="assignationHelp" src="assets/icon_help.png" style="cursor: pointer; margin-left: 5px;" original-title="Hier k&ouml;nnen Benutzer eingetragen werden, welche die Berechtigungen bekommen dieses Quiz zu bearbeiten oder die Ergebnisse einzusehen" width="18" height="18"></label>
				</div>
				<div class="col-md-10 col-sm-9">
					<input type="email" id="autocompleteUsers"><img id="addUser" style="margin-left: 8px;" alt="add" src="assets/arrow-right.png" width="28" height="32">
				</div>
			</div>
			<div class="from-group">
				<div class="col-md-10 col-sm-9" id="ajaxAnswer"></div>
			</div>
			<div class="table-responsive">
				<?php 
				$quizId = -1;
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
			<input id="btnAddExistingQuestion" name="btnAddExistingQuestion" class="btn" onclick="setConfirm(false, 'btn3');" type="button" value="<?php echo $lang["addExistingQuestion"];?>" style="margin-top: 10px;"/><br />
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
		$('#addUser').on('click', function(){
			addCreator(<?php echo $quizFetch["id"];?>);
	    });
		
		$('#addUser').on('mouseover', function(){
			this.style.cursor='pointer';
	    });		
	    
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
		<?php if($mode == "edit"){?>
			<input type="hidden" name="quiz_id" value="<?php echo $quizFetch["id"];?>">
		<?php }?>
		<div style="float: right; padding-left: 10px; margin-top: 10px;">
			<input type="hidden" name="btnSave" value="<?php echo $lang["buttonSaveAndPublish"];?>" />
			<input type="submit" class="btn" id="btnSave" name="btnSave" onclick="setConfirm(false, 'btn1')" value="<?php echo $lang["buttonSaveAndPublish"];?>" />
		</div>
	</form>
</div>