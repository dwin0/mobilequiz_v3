<?php 
	include_once 'errorCodeHandler.php';

	if($_SESSION["role"]["user"])
	{
		if(! $_SESSION["role"]["creator"])
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
	
	$errorCode = new mobileError("", "red");
	if(isset($_GET["code"]))
	{
		$errorCode = handleTopicsError($_GET["code"]);
	}
?>
<script type="text/javascript">

	function delTopic(id)
	{
	    console.log("delTopic " + id);
		$.ajax({
		      url: 'modules/actionHandler.php',
		      type: 'get',
		      data: 'action=delTopic&userId='+<?php echo $_SESSION["id"]; ?>+'&topicId=' + id,
		      success: function(output) {
			      if(output == 'deleteTopicOk')
			      {
				      //$('#topic_' + id).hide();
				      $('#topics').DataTable().row($('#topic_' + id)).remove().draw();
				      $('#topicActionResult').html("Themengebiet erfolgreich entfernt.");
			      }
		      }, error: function()
		      {
		          alert("Deleting failed");
		      }
		   });
	}
	
    $(function() {
    	$('.deleteTopic').tipsy({gravity: 'n'});
        $('#topics').dataTable({
            'bSort': true,
            'bPaginate': false,
            'bLengthChange': false,
            'aoColumnDefs':[{
                "bSortables":false,
                "aTargets":["sorting_disabled"]
            }],
            "sDom": 'frtip',
            "oLanguage": {
                "sZeroRecords": "Es sind keine Themenbereiche dieser Art vorhanden",
                "sInfo": "Zeige von _START_ bis _END_ von insgesamt _TOTAL_ Themenbereiche",
                "sInfoEmpty": "Zeige von 0 bis 0 von insgesamt 0 Themenbereiche",
                "sInfoFiltered": "(von insgesamt _MAX_ Themenbereiche)",
                "sSearch": ""
            }
        });
        $('.dataTables_filter input').attr("placeholder", 'Suchbegriff in Spalte "Name (Keywords)" suchen');
        $('.dataTables_filter input').addClass("form-control");
    });
</script>
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["topicsHeadline"];?></h1>
	</div>
	<p></p>
	<div class="panel panel-default">
		<div class="panel-body">
		    <div class="listOfTopics">
		        <table class="tblListOfTopics" id="topics" style="width: 100%">
		            <thead>
		                <tr>
		                    <th>
		                        <?php echo $lang["topicTopic"]?>
		                    </th>
		                    <th>
		                        <?php echo $lang["topicAmountQuizzes"]?>
		                    </th>
		                    <th>
		                        <?php echo $lang["topicAmountQuestions"]?>
		                    </th>
		                    <th>
		                        <?php echo $lang["quizTableActions"]?>
		                    </th>
		                </tr>
		            </thead>
		            <tbody>
		                <?php 
		                $stmt = $dbh->prepare("select * from subjects");
		                $stmt->execute();
		                $resultArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
		                
		                for($i = 0; $i < count($resultArray); $i++) {
						?>
		                    <tr class="entry" id="<?php echo "topic_" . $resultArray[$i]["id"];?>">
		                        
		                        <td>
		                            <?php echo $resultArray[$i]["name"];?>
		                        </td>
		                        <td>
		                            <?php 
		                            $stmt = $dbh->prepare("select * from questionnaire where subject_id = " . $resultArray[$i]["id"]);
		                            $stmt->execute();
		                            echo $stmt->rowCount();
		                            ?>
		                        </td>
		                        <td>
		                            <?php 
		                            $stmt = $dbh->prepare("select * from question where subject_id = " . $resultArray[$i]["id"]);
		                            $stmt->execute();
		                            echo $stmt->rowCount();
		                            ?>
		                        </td>
		                        <td>
		                        	<?php if($_SESSION['role']['creator']) {?>
			                            <img style="cursor:pointer" id="deleteTopic" class="deleteTopic" src="assets/icon_delete.png" alt="" original-title="Themengebiet l&ouml;schen" height="18px" width="18px" <?php echo "onclick=\"delTopic(" . $resultArray[$i]["id"] . ")\"";?>>
		                        	<?php }?>
		                        </td>
		                    </tr>
		                <?php }?>
	            	</tbody>
	            	<tfoot>
		                <tr class="entry" id="<?php echo "topic_undefined";?>">
		                        
	                        <td>
	                            <?php echo $lang["undefined"];?>
	                        </td>
	                        <td>
	                            <?php 
	                            $stmt = $dbh->prepare("select id from questionnaire where subject_id is null");
	                            $stmt->execute();
	                            echo $stmt->rowCount();
	                            ?>
	                        </td>
	                        <td>
	                            <?php 
	                            $stmt = $dbh->prepare("select id from question where subject_id is null");
	                            $stmt->execute();
	                            echo $stmt->rowCount();
	                            ?>
	                        </td>
	                        <td>
	                        </td>
	                    </tr>
	                    <tr class="entry" id="<?php echo "topic_all";?>">
		                        
	                        <td>
	                            <?php echo $lang["all"];?>
	                        </td>
	                        <td>
	                            <?php 
	                            $stmt = $dbh->prepare("select id from questionnaire");
	                            $stmt->execute();
	                            echo $stmt->rowCount();
	                            ?>
	                        </td>
	                        <td>
	                            <?php 
	                            $stmt = $dbh->prepare("select id from question");
	                            $stmt->execute();
	                            echo $stmt->rowCount();
	                            ?>
	                        </td>
	                        <td>
	                        </td>
	                    </tr>
		            </tfoot>
		        </table>
		        <br />
		        <br />
		        <p id="topicActionResult" style="color:<?php echo $errorCode->getColor();?>;"><?php echo $errorCode->getText();?></p>
		        <form action="?p=actionHandler&action=insertTopic" method="post">
		        	<input style="float:left; width: 300px;" type="text" name="topicName" required="required" value="" class="form-control" placeholder="<?php echo $lang["topicName"]; ?>" />
		        	<input style="margin-left:20px;" type="submit" name="submit" class="btn" value="<?php echo $lang["create"]; ?>">
		        </form>
		    </div>
		</div>
	</div>
</div>