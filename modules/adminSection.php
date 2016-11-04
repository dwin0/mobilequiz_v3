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

$roleNames = array(
		'admin' => 'roleAdmin',
		'manager' => 'roleManager',
		'creator' => 'roleCreator',
		'user' => 'roleUser'
);

?>
<script type="text/javascript">

	function roleHandler(decision,userId,trId)
	{
		$.ajax({
			url: "modules/profileSettings.php",
			type: "post",
			data: "action=roleDecision&userId="+userId+"&decision="+decision,
			success: function(output) 
			{
				if(output == "ok1")
				{
					$('#ajaxAnswer2').html('<span style="color: green;">Rolle angenommen.</span>');
					$('#role_'+trId).css('display', 'none');
				}
				if(output == "ok2")
				{
					$('#ajaxAnswer2').html('<span style="color: red;">Rolle abgelehnt.</span>');
					$('#role_'+trId).css('display', 'none');
				}
				if(output == "failed")
				{
					$('#ajaxAnswer3').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer2').html('<span style="color: red;">Fehler.</span>');
			}	      
		});
	}

	function languageHandler(decision,languageRequestId,trId)
	{
		$.ajax({
			url: "modules/profileSettings.php",
			type: "post",
			data: "action=languageDecision&languageRequestId="+languageRequestId+"&decision="+decision,
			success: function(output) 
			{
				if(output == "ok1")
				{
					$('#ajaxAnswer3').html('<span style="color: green;">Sprache angenommen.</span>');
					$('#lang_'+trId).css('display', 'none');
				}
				if(output == "ok2")
				{
					$('#ajaxAnswer3').html('<span style="color: red;">Sprache abgelehnt.</span>');
					$('#lang_'+trId).css('display', 'none');
				}
				if(output == "failed")
				{
					$('#ajaxAnswer3').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer3').html('<span style="color: red;">Fehler.</span>');
			}	      
		});
	}

	function topicHandler(decision,topicRequestId,trId)
	{
		$.ajax({
			url: "modules/profileSettings.php",
			type: "post",
			data: "action=topicDecision&topicRequestId="+topicRequestId+"&decision="+decision,
			success: function(output) 
			{
				console.log("success: " + output);
				if(output == "ok1")
				{
					$('#ajaxAnswer4').html('<span style="color: green;">Themenbereich angenommen.</span>');
					$('#topic_'+trId).css('display', 'none');
				}
				if(output == "ok2")
				{
					$('#ajaxAnswer4').html('<span style="color: red;">Themenbereich abgelehnt.</span>');
					$('#topic_'+trId).css('display', 'none');
				}
				if(output == "failed")
				{
					$('#ajaxAnswer4').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer4').html('<span style="color: red;">Fehler.</span>');
			}	      
		});
	}

	function delGroup(groupId)
	{
		console.log("del: " + groupId);
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=delGroup&groupId="+groupId,
			success: function(output) 
			{
				if(output == "ok")
				{
					$('#ajaxAnswer5').html('<span style="color: green;">Gruppe gel&ouml;scht.</span>');
					$('#groups').DataTable().row($('#group_'+groupId)).remove().draw();
					$('.tipsy').remove();
					$('.groupDetails').hide();
				}
				if(output == "failed")
				{
					$('#ajaxAnswer5').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer5').html('<span style="color: red;">Fehler.</span>');
			}	      
		});
	}

	function changeGroupTo(id)
	{
		$('.groupDetails').hide();
		$('#groupDetail_' + id).show();
	}

	function addGroup()
	{
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=addGroup&groupName="+$('#newGroupName').val(),
			success: function(output) 
			{
				if(output == "ok")
				{
					$('#ajaxAnswer5').html('<span style="color: green;">Gruppe erstellt.</span>');
					$('#groups').DataTable().row.add(['', $('#newGroupName').val(), '0', '-']).draw(false);
					location.reload();
				}
				if(output == "failed")
				{
					$('#ajaxAnswer5').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output, b, c) 
			{
				console.log(output);
				console.log(b);
				console.log(c);
				$('#ajaxAnswer5').html('<span style="color: red;">Fehler.</span>');
			}	      
		});
	}

	function delUserFromGroupFunc(userId, gId)
	{
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=delUserFromGroup&userId="+userId+"&groupId="+gId,
			success: function(output) 
			{
				if(output == "ok")
				{
					$('#ajaxAnswer5').html('<span style="color: green;">Benutzer aus Gruppe entfernt.</span>');
					$('#userInGroup_' + userId).css('display', 'none');
				}
				if(output == "failed")
				{
					$('#ajaxAnswer5').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer5').html('<span style="color: red;">Fehler.</span>');
			}	      
		});
	}

	//TODO: falls noch in anderen Files gebraucht wird, auslagern!
	function escapeHtml(text) {
		return text
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}

	function addUserToGroup(groupId)
	{
		var userEmail = $('#autocompleteUsers_' + groupId).val();
		console.log(userEmail + " " + groupId);
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=addUserToGroup&userEmail="+userEmail+"&groupId="+groupId,
			dataType: "json",
			success: function(output) 
			{
				if(output[0] == "ok" && output[1] > 0)
				{
					$('#ajaxAnswer5').html('<span style="color: green;">Benutzer hinzugef&uuml;gt.</span>');
					$('#listOfUsersAdded_' + groupId).append("<b>-</b> " + escapeHtml(userEmail) + "<br />");
					$('#autocompleteUsers_' + groupId).val('');
				}
				if(output[0] == "failed")
				{
					$('#ajaxAnswer5').html('<span style="color: red;">Fehler.</span>');
				}
			},
			error: function(output) 
			{
				$('#ajaxAnswer5').html('<span style="color: red;">Fehler.</span>');
			}	      
		});
	}

	function changeActive(uId)
	{
		$.ajax({
			url: 'modules/actionHandler.php',
			type: "get",
			data: "action=changeActive&userId="+uId,
			dataType: "json",
			success: function(output) 
			{
				if(output[0] == "ok")
				{
					var newSrc = "Green";
					if(output[1] == 0)
						newSrc = "Red";
					$('#prioImg_' + uId).attr('src', "assets/priority" + newSrc + ".png");
				}
				if(output[0] == "failed")
				{
					alert("error changin active status 2");
				}
			},
			error: function(output) 
			{
				alert("error changin active status 1");
			}	      
		});
	}

	$(function() {

		$('.activatedImg').tipsy({gravity: 'n'});
		$('.acceptImg').tipsy({gravity: 'n'});
		$('.refuseImg').tipsy({gravity: 'n'});
		$('.delGroupImg').tipsy({gravity: 'n'});
		$('.delUserFromGroupImg').tipsy({gravity: 'n'});
		
		$( "#adminSection1Content" ).hide();
		$( "#adminSection2Content" ).hide();
		$( "#adminSection3Content" ).hide();
		$( "#adminSection4Content" ).hide();
		$( "#adminSection5Content" ).hide();
		$( "#adminSection6Content" ).hide();

		var mainContent = false;
		var roleContent = false;
		var languageContent = false;
		var topicContent = false;
		var groupContent = false;
		var logentries = false;
		
		$("#adminSection1Heading").click(function() {
			$( "#adminSection1Content" ).toggle("slow", false);
			mainContent = !mainContent;
			if(!mainContent)
				$('#arrow1').html('&#9654;');
			else
				$('#arrow1').html('&#9660;');
		});
		
		$("#adminSection2Heading").click(function() {
			$( "#adminSection2Content" ).toggle("slow", false);
			roleContent = !roleContent;
			if(!roleContent)
				$('#arrow2').html('&#9654;');
			else
				$('#arrow2').html('&#9660;');
		});

		$("#adminSection3Heading").click(function() {
			$( "#adminSection3Content" ).toggle("slow", false);
			languageContent = !languageContent;
			if(!languageContent)
				$('#arrow3').html('&#9654;');
			else
				$('#arrow3').html('&#9660;');
		});

		$("#adminSection4Heading").click(function() {
			$( "#adminSection4Content" ).toggle("slow", false);
			topicContent = !topicContent;
			if(!topicContent)
				$('#arrow4').html('&#9654;');
			else
				$('#arrow4').html('&#9660;');
		});

		$("#adminSection5Heading").click(function() {
			$( "#adminSection5Content" ).toggle("slow", false);
			groupContent = !groupContent;
			if(!groupContent)
				$('#arrow5').html('&#9654;');
			else
				$('#arrow5').html('&#9660;');
		});

		$("#adminSection6Heading").click(function() {
			$( "#adminSection6Content" ).toggle("slow", false);
			logentries = !logentries;
			if(!logentries)
				$('#arrow6').html('&#9654;');
			else
				$('#arrow6').html('&#9660;');
		});

		<?php 
		$stmt = $dbh->prepare("select id, email from user where group_id is null");
		$stmt->execute();
		$fetchUserMails = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
		?>
		var sourceData = <?php echo json_encode($fetchUserMails);?>;
		$( ".autocompleteUsers" ).autocomplete({
		  source: sourceData
		});

		$('#users').DataTable({
            'bSort': true,
            'paging': true,
            'lengthChange': true,
            'aoColumns': [
				{'bSearchable': false},
				null,
				null,
				null,
				null,
				{'bSearchable': false},
				{'bSearchable': false},
				{'bSearchable': false, 'bSortable': false}
            ],
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Alle"]],
            "sDom": '<"toolbar">lfrtip',
            "oLanguage": {
                "sZeroRecords": "Es sind keine Benutzer dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Benutzer",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Benutzer",
                "sInfoFiltered": "(von insgesamt _MAX_ Benutzer)",
                "sSearch": ""
            }
        });
        $('#users_wrapper .dataTables_filter').prepend("<div><b>Suche:</b></div>");
        $('#users_wrapper .dataTables_filter input').attr("placeholder", 'Suchbegriff eingeben');
        $('#users_wrapper .dataTables_filter input').addClass("form-control");
        $('#users_wrapper .dataTables_filter input').addClass("magnifyingGlass");
        $('#users_wrapper div.toolbar').html(document.getElementById('hiddenFilter').innerHTML);


        $('#roles').dataTable({
            'bSort': true,
            'bFilter': false,
            'bPaginate': false,
            'bLengthChange': false,
            'aoColumns': [
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false}
            ],
            "oLanguage": {
                "sZeroRecords": "Es sind keine Anfragen dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Anfragen",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Anfragen",
                "sInfoFiltered": "(von insgesamt _MAX_ Anfragen)"
            }
        });

        $('#languages').dataTable({
            'bSort': true,
            'bFilter': false,
            'bPaginate': false,
            'bLengthChange': false,
            'aoColumns': [
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false}
            ],
            "oLanguage": {
                "sZeroRecords": "Es sind keine Anfragen dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Anfragen",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Anfragen",
                "sInfoFiltered": "(von insgesamt _MAX_ Anfragen)"
            }
        });

        $('#topics').dataTable({
            'bSort': true,
            'bFilter': false,
            'bPaginate': false,
            'bLengthChange': false,
            'aoColumns': [
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false}
            ],
            "oLanguage": {
                "sZeroRecords": "Es sind keine Anfragen dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Anfragen",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Anfragen",
                "sInfoFiltered": "(von insgesamt _MAX_ Anfragen)"
            }
        });

        $('#groups').dataTable({
            'bSort': true,
            'bFilter': false,
            'bPaginate': false,
            'bLengthChange': false,
            'aoColumns': [
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false}
            ],
            "oLanguage": {
                "sZeroRecords": "Es sind keine Gruppen dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Gruppen",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Gruppen",
                "sInfoFiltered": "(von insgesamt _MAX_ Gruppen)"
            }
        });
        
        $('#events').DataTable({
            'bSort': true,
            'bPaginate': true,
            'bLengthChange': true,
            'aoColumns': [
          		{'bSearchable': false, 'bSortable': false},
				{'bSearchable': false, 'bSortable': false},
				null,
				{'bSearchable': false, 'bSortable': false}
            ],
            "order": [[0, "desc"]],
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "sDom": '<"toolbar">lfrtip',
            "oLanguage": {
                "sZeroRecords": "Es sind keine Ereignisse dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Ereignisse",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Ereignisse",
                "sInfoFiltered": "(von insgesamt _MAX_ Ereignisse)",
                "sSearch": ""
            }
        });
        $('#events_wrapper .dataTables_filter').prepend("<div><b>Suche:</b></div>");
        $('#events_wrapper .dataTables_filter input').attr("placeholder", 'Suchbegriff eingeben');
        $('#events_wrapper .dataTables_filter input').addClass("form-control");
        $('#events_wrapper .dataTables_filter input').addClass("magnifyingGlass");
		
	});
</script>
<?php 

	//TODO: Duplicate Function handleCode & Extract to File 'HandleCode'
	
	$code = 0;
	$codeTxt = "";
	$color = "red";
	if(isset($_GET["code"]))
	{
		$code = $_GET["code"];
	}
	if($code > 0)
	{
		$color = "green";
	}
	
	switch ($code)
	{
		case 1:
			$codeTxt = "Benutzer erfolgreich erstellt.";
			break;
	}

	$stmt = $dbh->prepare("select user.*, user_data.*, role.name as rName from user inner join user_data on id = user_id inner join role on role.id = user.role_id");
	$stmt->execute();
	$fetchUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div id="hiddenFilter" style="display: none;"></div>
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["adminSectionHeadline"];?></h1>
	</div>
	<p id="result" style="color:<?php echo $color;?>;"><?php echo $codeTxt;?></p>
	<?php if($_SESSION["role"]["admin"] == 1) {?>
	<div class="panel panel-default">
		<div class="panel-heading" id="adminSection1Heading">
			<span id="arrow1" style="float: left; margin-right: 7px;">&#9654;</span>
			<h3 class="panel-title"><?php echo $lang["users"] . " (" . $stmt->rowCount() . ")" ;?></h3>
		</div>
		<div class="panel-body" id="adminSection1Content">
			<div class="control-group">
				<table class="tblListOfUsers" id="users" style="width: 100%">
					<thead>
						<tr>
							<th>id</th>
							<th>
		                        <?php echo $lang["firstname"] . " " . $lang["lastname"];?>
		                    </th>
							<th>
		                        <?php echo $lang["nickname"];?>
		                    </th>
							<th>
		                        <?php echo $lang["email"];?>
		                    </th>
							<th>
		                        <?php echo $lang["role"];?>
		                    </th>
							<th>
		                        <?php echo $lang["creationDate"];?>
		                    </th>
							<th>
		                        <?php echo $lang["state"];?>
		                    </th>
							<th>
		                        <?php echo $lang["quizTableActions"];?>
		                    </th>
						</tr>
					</thead>
		            <tbody>
		            	<?php for($i = 0; $i < count($fetchUsers); $i++) {?>
			            	<tr>
							<td>
			                        <?php echo $fetchUsers[$i]["id"];?>
			                    </td>
							<td>
			                        <?php echo htmlspecialchars($fetchUsers[$i]["firstname"]) . " " . htmlspecialchars($fetchUsers[$i]["lastname"]);?>
			                    </td>
							<td>
			                        <?php echo htmlspecialchars($fetchUsers[$i]["nickname"]);?>
			                    </td>
							<td>
			                        <?php echo htmlspecialchars($fetchUsers[$i]["email"]);?>
			                    </td>
							<td><?php 
			                    	echo $lang[$roleNames[$fetchUsers[$i]["rName"]]];
			                    ?></td>
							<td>
			                        <?php echo date("d.m.Y H:i:s", $fetchUsers[$i]["create_date"]);?>
			                    </td>
							<td><?php 
			                    	$prio = "Green";
			                    	$hint = "Benutzer ist aktiviert";
			                    	if($fetchUsers[$i]["isActivated"] == 0)
			                    	{
			                    		$prio = "Red";
			                    		$hint = "Benutzer ist deaktiviert";
			                    	}
			                    	
			                    ?>
			                    <img class="activatedImg" id="prioImg_<?php echo $fetchUsers[$i]["id"];?>" src="<?php echo "assets/priority" . $prio . ".png";?>" original-title="<?php echo $hint;?>" style="margin-right: 5px; cursor: pointer;" onclick="changeActive(<?php echo $fetchUsers[$i]["id"];?>)"/>
							</td>
							<td></td>
						</tr>
		                <?php }?>
		            </tbody>
				</table>
			</div>
			<div style="margin-top: 55px;">
				<label for="createNewUser">Einen neuen Benutzer erstellen.</label>
				<div id="createNewUser" class="createNewUser">
					<form action="?p=auth&action=createNewUser" method="POST">
						<input style="width: 200px; float: left; margin-right: 10px;" type="text" placeholder="Vorname" width="200" class="form-control" name="firstname">
						<input style="width: 200px; float: left; margin-right: 10px;" type="text" placeholder="Nachname" width="200" class="form-control" name="lastname">
						<input style="width: 200px; float: left; margin-right: 10px;" type="password" placeholder="Passwort" width="200" class="form-control" name="password">
						<input style="width: 200px; float: left; margin-right: 10px;" type="email" placeholder="Email" width="200" class="form-control" name="email">
						<input style="width: 200px;" type="text" placeholder="Pseudonym" width="200" class="form-control" name="nickname">
						<input style="width: 200px; margin-top: 10px;" type="submit" name="submit" class="btn" value="Erstellen">
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php }
		$stmt = $dbh->prepare("select `group`.*, firstname, lastname from `group` inner join user_data on user_id = owner_id");
		$stmt->execute();
		$fetchGroup = $stmt->fetchAll(PDO::FETCH_ASSOC);
	?>
	<div class="panel panel-default">
		<div class="panel-heading" id="adminSection5Heading">
			<span id="arrow5" style="float: left; margin-right: 7px;">&#9654;</span>
			<h3 class="panel-title"><?php echo $lang["administrateGroups"];?></h3>
		</div>
		<div class="panel-body" id="adminSection5Content">
			<div class="control-group" style="width: 40%; float: left;">
		        <p id="ajaxAnswer5"></p>
				<table class="tblListOfGroups" id="groups" style="margin: 0px;">
					<thead>
						<tr>
							<th>
		                    	<?php echo $lang["idCol"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["groupName"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["amountParticipants"];?>
		                    </th>
							<th>
		                    	<?php echo "Token";?>
		                    </th>
						</tr>
					</thead>
	                <tbody>
	                	<?php for($i = 0; $i < count($fetchGroup); $i++) {?>
	                	<tr id="<?php echo "group_" . $fetchGroup[$i]["id"];?>">
							<td>
		                		<?php echo $fetchGroup[$i]["id"];?>
		                	</td>
							<td>
		                		<a onclick="changeGroupTo(<?php echo $fetchGroup[$i]["id"];?>)" style="cursor: pointer;"><?php echo htmlspecialchars($fetchGroup[$i]["name"]);?></a>
		                	</td>
		                	<td>
		                		<?php 
		                		$stmt = $dbh->prepare("select id from user where group_id = :gId");
		                		$stmt->bindParam(":gId", $fetchGroup[$i]["id"]);
		                		$stmt->execute();
		                		echo $stmt->rowCount();
		                		?>
		                	</td>
				 			<td>
								<?php
								if($fetchGroup[$i]["owner_id"] == $_SESSION["id"] || $_SESSION["role"]["admin"] == 1) 
									echo $fetchGroup[$i]["token"];
								?>
		                	</td>
						</tr>
						<?php }?>
	                </tbody>
	                <tfoot>
	                	<td><?php echo $lang["addGroup"];?>:</td>
	                	<td colspan="2"><input id="newGroupName" style="width: 200px; float: left; margin-right: 10px;" type="text" placeholder="Gruppenname" width="200" class="form-control" name="groupName" maxlength="30"><img style="margin-left: 8px;" alt="add" src="assets/arrow-right.png" width="28" height="32" onclick="addGroup()"></td>
	                </tfoot>
				</table>
			</div>
			<?php 
			
			for($i = 0; $i < count($fetchGroup); $i++) {
				
				$stmt = $dbh->prepare("select email, user.id from user inner join `group` on group.id = user.group_id where group.id = :gId order by email");
				$stmt->bindParam(":gId", $fetchGroup[$i]["id"]);
				$stmt->execute();
				$fetchUserFromGroup = $stmt->fetchAll(PDO::FETCH_ASSOC);
				
				$groupToken = "";
				if($_SESSION["role"]["manager"] || ($fetchGroup[$i]["owner_id"] == $_SESSION["id"]))
				{
					$groupToken = " (" . $fetchGroup[$i]["token"] . ")";
					$delImg = '<img id="delGroup_'. $fetchGroup[$i]["id"].'" class="deleteGroup delGroupImg" src="assets/icon_delete.png" style="cursor: pointer; margin: -4px 0px 0px 5px;" alt="" original-title="Gruppe l&ouml;schen" height="18px" width="18px" onclick="delGroup('.$fetchGroup[$i]["id"].')">';				
				}
			?>
			<div id="<?php echo "groupDetail_" . $fetchGroup[$i]["id"];?>" class="control-group groupDetails" style="display: none; padding: 0px 5px 0px 5px; width: 55%; float: right; border: 1px solid #000; margin-top: 35px;">
		        <h4 style="float: left;"><?php echo "Name: " . htmlspecialchars($fetchGroup[$i]["name"]) . $groupToken . $delImg;?></h4><span style="float: right; margin: 9px 5px 0px 0px; font-size: 10px;"><?php echo $lang["groupOwner"] . ": " . htmlspecialchars($fetchGroup[$i]["firstname"]) . " " . htmlspecialchars($fetchGroup[$i]["lastname"]);?></span>
		        <div style="clear: both;"></div>
		        <div id="<?php echo "groupContent_" . $i;?>">
		        	<div id="<?php echo "listOfUsersAdded_" . $fetchGroup[$i]["id"];?>"><?php echo $lang["groupAddedUsers"];?>:<br />
		        	<?php 
		        	for ($j = 0; $j < count($fetchUserFromGroup); $j++) {
						if($_SESSION["role"]["manager"] || ($fetchGroup[$i]["owner_id"] == $_SESSION["id"]))
						{
							$delUserFromGroup = '<img id="delUserFromGroup_'. $fetchUserFromGroup[$j]["id"].'" class="delUserFromGroup delUserFromGroupImg" src="assets/icon_delete.png" style="cursor: pointer; margin: -4px 0px 0px 5px;" alt="" original-title="Benutzer aus Gruppe l&ouml;schen" height="18px" width="18px" onclick="delUserFromGroupFunc('.$fetchUserFromGroup[$j]["id"].', '.$fetchGroup[$i]["id"].')">';
						}
		        		echo "<span id=\"userInGroup_" . $fetchUserFromGroup[$j]["id"] . "\"> <b>-</b> " . htmlspecialchars($fetchUserFromGroup[$j]["email"]);
		        		echo $delUserFromGroup;
		        		echo "<br /></span>";	        				        		
		        	}?>
		        	</div>
		        	<div style="margin-top: 5px;">
		        		<input id="<?php echo "autocompleteUsers_" . $fetchGroup[$i]["id"];?>" class="autocompleteUsers form-control" placeholder="Benutzer Email" style="width: 200px; float: left; margin: 0px 10px 5px 0px;" ><img style="margin-left: 8px;" alt="add" src="assets/arrow-right.png" width="28" height="32" onclick="addUserToGroup(<?php echo $fetchGroup[$i]["id"];?>)">
		        	</div>
		        </div>
		    </div>
		    <?php }?>
		</div>
	</div>
	<?php 
	if($_SESSION["role"]["admin"] == 1) {
		$stmt = $dbh->prepare("select user.id as uId, role_request.*, cRole.name as cRole, user_data.firstname, user_data.lastname, rRole.name as rRole, email from role_request inner join user on user.id = role_request.user_id inner join role as rRole on rRole.id = role_request.role_id inner join user_data on user_data.user_id = user.id inner join role as cRole on cRole.id = user.role_id");
		$stmt->execute();
		$fetchRequestedRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
	?>
	<div class="panel panel-default">
		<div class="panel-heading" id="adminSection2Heading">
			<span id="arrow2" style="float: left; margin-right: 7px;">&#9654;</span>
			<h3 class="panel-title"><?php echo $lang["askForRole"] . " (" . $stmt->rowCount() . ")" ;?></h3>
		</div>
		<div class="panel-body" id="adminSection2Content">
			<div class="control-group">
				<label class="control-label" for="askForRole">
		            <?php echo $lang["adminSectionAskForRoleText"];?>
		        </label>
		        <p id="ajaxAnswer2"></p>
				<table class="tblListOfRoles" id="roles" style="width: 100%">
					<thead>
						<tr>
							<th>
		                    	<?php echo $lang["firstname"] . " " . $lang["lastname"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["email"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["adminSectionCurrentRole"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["adminSectionRequestetRole"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["adminSectionRequestedAt"];?>
		                    </th>
							<th>
		                        <?php echo $lang["quizTableActions"];?>
		                    </th>
						</tr>
					</thead>
	                <tbody>
	                	<?php for($i = 0; $i < count($fetchRequestedRoles); $i++) {?>
	                	<tr id="<?php echo "role_" . $i;?>">
							<td>
		                		<?php echo htmlspecialchars($fetchRequestedRoles[$i]["firstname"]) . " " . htmlspecialchars($fetchRequestedRoles[$i]["lastname"]);?>
		                	</td>
							<td>
		                		<?php echo htmlspecialchars($fetchRequestedRoles[$i]["email"]);?>
		                	</td>
							<td>
		                		<?php echo $lang[$roleNames[$fetchRequestedRoles[$i]["cRole"]]];?>
							</td>
							<td>
		                		<?php echo $lang[$roleNames[$fetchRequestedRoles[$i]["rRole"]]];?>
		                	</td>
							<td>
		                		<?php echo date("d.m.Y H:i:s", $fetchRequestedRoles[$i]["timestamp"]);?>
		                	</td>
							<td>
								<img style="cursor: pointer;" class="acceptImg" src="<?php echo "assets/icon_correct.png";?>" original-title="annehmen" <?php echo "onclick=\"roleHandler(1,".$fetchRequestedRoles[$i]["uId"].",".$i.")\""?>/> 
		                		<img style="cursor: pointer;" class="refuseImg" src="<?php echo "assets/icon_incorrect.png";?>" original-title="ablehnen" <?php echo "onclick=\"roleHandler(0,".$fetchRequestedRoles[$i]["uId"].",".$i.")\""?>/>
							</td>
						</tr>
	                	<?php }?>
	                </tbody>
				</table>
			</div>
		</div>
	</div>
	<?php 
	}
	if($_SESSION["role"]["manager"] == 1) {
		//language_request query
		$stmt = $dbh->prepare("select language_request.*, user.email, user_data.firstname, user_data.lastname, questionnaire.name from language_request inner join user on user.id = language_request.user_id inner join user_data on user.id = user_data.user_id inner join questionnaire on questionnaire.id = language_request.questionnaire_id");
		$stmt->execute();
		$fetchLanguageRequest = $stmt->fetchAll(PDO::FETCH_ASSOC);
	?>
	<div class="panel panel-default">
		<div class="panel-heading" id="adminSection3Heading">
			<span id="arrow3" style="float: left; margin-right: 7px;">&#9654;</span>
			<h3 class="panel-title"><?php echo $lang["requestNewLanguage"] . " (" . $stmt->rowCount() . ")" ;?></h3>
		</div>
		<div class="panel-body" id="adminSection3Content">
			<div class="control-group">
		        <p id="ajaxAnswer3"></p>
				<table class="tblListOfLanguages" id="languages" style="width: 100%">
					<thead>
						<tr>
							<th>
		                    	<?php echo $lang["firstname"] . " " . $lang["lastname"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["email"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["quizCreateName"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["requestedLanguage"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["adminSectionRequestedAt"];?>
		                    </th>
							<th>
		                        <?php echo $lang["quizTableActions"];?>
		                    </th>
						</tr>
					</thead>
	                <tbody>
	                	<?php for($i = 0; $i < count($fetchLanguageRequest); $i++) {?>
	                	<tr id="<?php echo "lang_" . $i;?>">
							<td>
		                		<?php echo htmlspecialchars($fetchLanguageRequest[$i]["firstname"]) . " " . htmlspecialchars($fetchLanguageRequest[$i]["lastname"]);?>
		                	</td>
							<td>
		                		<?php echo htmlspecialchars($fetchLanguageRequest[$i]["email"]);?>
		                	</td>
							<td>
		                		<?php echo htmlspecialchars($fetchLanguageRequest[$i]["name"]);?>
							</td>
							<td>
		                		<?php echo htmlspecialchars($fetchLanguageRequest[$i]["language"]);?>
		                	</td>
							<td>
		                		<?php echo date("d.m.Y H:i:s", $fetchLanguageRequest[$i]["timestamp"]);?>
		                	</td>
							<td>
								<img style="cursor: pointer;" class="acceptImg" src="<?php echo "assets/icon_correct.png";?>" original-title="annehmen" <?php echo "onclick=\"languageHandler(1,".$fetchLanguageRequest[$i]["id"].",".$i.")\""?>/> 
		                		<img style="cursor: pointer;" class="refuseImg" src="<?php echo "assets/icon_incorrect.png";?>" original-title="ablehnen" <?php echo "onclick=\"languageHandler(0,".$fetchLanguageRequest[$i]["id"].",".$i.")\""?>/>
							</td>
						</tr>
	                	<?php }?>
	                </tbody>
				</table>
			</div>
		</div>
	</div>
	<?php 
	}
	if($_SESSION["role"]["manager"] == 1) { 
		//topic_request query
		$stmt = $dbh->prepare("select topic_request.*, user.email, user_data.firstname, user_data.lastname, questionnaire.name from topic_request inner join user on user.id = topic_request.user_id inner join user_data on user.id = user_data.user_id inner join questionnaire on questionnaire.id = topic_request.questionnaire_id");
		$stmt->execute();
		$fetchTopicRequest = $stmt->fetchAll(PDO::FETCH_ASSOC);
	?>
	<div class="panel panel-default">
		<div class="panel-heading" id="adminSection4Heading">
			<span id="arrow4" style="float: left; margin-right: 7px;">&#9654;</span>
			<h3 class="panel-title"><?php echo $lang["requestNewTopic"] . " (" . $stmt->rowCount() . ")" ;?></h3>
		</div>
		<div class="panel-body" id="adminSection4Content">
			<div class="control-group">
		        <p id="ajaxAnswer4"></p>
				<table class="tblListOfTopics" id="topics" style="width: 100%">
					<thead>
						<tr>
							<th>
		                    	<?php echo $lang["firstname"] . " " . $lang["lastname"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["email"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["quizCreateName"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["requestedTopic"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["adminSectionRequestedAt"];?>
		                    </th>
							<th>
		                        <?php echo $lang["quizTableActions"];?>
		                    </th>
						</tr>
					</thead>
	                <tbody>
	                	<?php for($i = 0; $i < count($fetchTopicRequest); $i++) {?>
	                	<tr id="<?php echo "topic_" . $i;?>">
							<td>
		                		<?php echo htmlspecialchars($fetchTopicRequest[$i]["firstname"]) . " " . htmlspecialchars($fetchTopicRequest[$i]["lastname"]);?>
		                	</td>
							<td>
		                		<?php echo htmlspecialchars($fetchTopicRequest[$i]["email"]);?>
		                	</td>
							<td>
		                		<?php echo htmlspecialchars($fetchTopicRequest[$i]["name"]);?>
							</td>
							<td>
		                		<?php echo htmlspecialchars($fetchTopicRequest[$i]["topic"]);?>
		                	</td>
							<td>
		                		<?php echo date("d.m.Y H:i:s", $fetchTopicRequest[$i]["timestamp"]);?>
		                	</td>
							<td>
								<img style="cursor: pointer;" class="acceptImg" src="<?php echo "assets/icon_correct.png";?>" original-title="annehmen" <?php echo "onclick=\"topicHandler(1,".$fetchTopicRequest[$i]["id"].",".$i.")\""?>/> 
		                		<img style="cursor: pointer;" class="refuseImg" src="<?php echo "assets/icon_incorrect.png";?>" original-title="ablehnen" <?php echo "onclick=\"topicHandler(0,".$fetchTopicRequest[$i]["id"].",".$i.")\""?>/>
							</td>
						</tr>
	                	<?php }?>
	                </tbody>
				</table>
			</div>
		</div>
	</div>
	<?php 
	}
	if($_SESSION["role"]["admin"] == 1) { 
		$stmt = $dbh->prepare("select * from events order by id asc");
		$stmt->execute();
		$fetchEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
	?>
	<div class="panel panel-default">
		<div class="panel-heading" id="adminSection6Heading">
			<span id="arrow6" style="float: left; margin-right: 7px;">&#9654;</span>
			<h3 class="panel-title"><?php echo $lang["logentries"];?></h3>
		</div>
		<div class="panel-body" id="adminSection6Content">
			<div class="control-group">
				<p id="ajaxAnswer6"></p>
				<table id="events" style="width: 100%">
					<thead>
						<tr>
							<th>
		                    	<?php echo $lang["idCol"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["dateCol"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["typeCol"];?>
		                    </th>
							<th>
		                    	<?php echo $lang["eventCol"];?>
		            		</th>
		            	</tr>
					</thead>
					<tbody>
						<?php for($i = 0; $i < count($fetchEvents); $i++) {?>
						<tr>
							<td><?php echo $fetchEvents[$i]["id"];?></td>
							<td><?php echo date("d.m.Y H:i:s", $fetchEvents[$i]["event_date"]);?></td>
							<td><?php echo $fetchEvents[$i]["event_type"];?></td>
							<td><?php echo htmlspecialchars($fetchEvents[$i]["event"]);?></td>
		           		</tr>
		           		<?php }?>
					</tbody>
				</table>
		    </div>
		</div>
	</div>
	<?php }?>
</div>








