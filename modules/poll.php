<?php
	include_once 'errorCodeHandler.php';
?>
<script src="js/highcharts.js"></script>
<script type="text/javascript">

	function switchDisplay(elem)
	{
		disableAll();
		$('#' + elem).css('display', 'inline');
	}

	function disableAll()
	{
		$('#pollTabs .contentWrapper').each(function(){
			$(this).css('display', 'none');
		});
	}

	function showPoll()
	{
		var pollval = $('#goToPollToken').val();
		var location = '?p=poll';
		if(pollval != "")
			location = '?p=poll&token=' + pollval;
		window.location = location;
	}

	function delPoll(pId)
	{
		console.log("delPoll " + pId);
	}

	<?php if($_SESSION["role"]["creator"] == 1) {?>
		function switchPollState(pId)
		{
			$.ajax({
				url: 'modules/actionHandler.php',
				type: 'get',
				data: 'action=switchPollState&newActive=' + pId,
				dataType: "json",
				success: function(output) {
					if(output[0] == 'ok')
					{
						var color = "Green";
						var activated = "aktiviert";
						if(output[1] == 0)
						{
							var color = "Red";
							var activated = "deaktiviert";
						}
						
						$('#ajaxAnswer1').html("<span style=\"color: "+color+";\">Umfrage "+activated+"</span>");
						$('#openImg_' + pId).attr('src', 'assets/priority'+color+'.png');
						$('#openImg_' + pId).attr('original-title', 'Umfrage ist '+activated+'');
					} else {
						$('#ajaxAnswer1').html("<span style=\"color: red;\">Fehler 1</span>");
					}
				}, error: function()
				{
					$('#ajaxAnswer1').html("<span style=\"color: red;\">Fehler 2</span>");
				}
			});
		}
	<?php }?>

	$(function() {
		disableAll();
		$('#goToPollContent').css('display', 'inline');

    	$('.activatedImg').tipsy({gravity: 'n'});

		$('#pollTable').DataTable({
            'bSort': true,
            'bPaginate': true,
            'bLengthChange': true,
            'aoColumns': [
          		{'bSearchable': false},
				{'bSortable': false},
          		{'bSearchable': false},
          		{'bSearchable': false},
				{'bSortable': false},
				{'bSortable': false, 'bSearchable': false},
				{'bSortable': false, 'bSearchable': false}
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
        $('#pollTable_wrapper .dataTables_filter').prepend("<div><b>Suche:</b></div>");
        $('#pollTable_wrapper .dataTables_filter input').attr("placeholder", 'Suchbegriff eingeben');
        $('#pollTable_wrapper .dataTables_filter input').addClass("form-control");
        $('#pollTable_wrapper .dataTables_filter input').addClass("magnifyingGlass");
        $('#questionImg').resizable();
        switchType();

        <?php         
        $whereStatement = "where open = 1";
        if(isset($_GET["token"])) {
			$whereStatement = "where token = :token";
        }
        $stmt = $dbh->prepare("select poll.id, question, token, open, picture, question_type from poll " . $whereStatement);
        if(isset($_GET["token"])) {
        	$stmt->bindParam(":token", $_GET["token"]);
        }
        $stmt->execute();
        $questionResultFound = $stmt->rowCount();
        if($questionResultFound > 0)
        {
        	$pollId = $stmt->fetch(PDO::FETCH_ASSOC);
        	if(((isset($_COOKIE['pollId' . $pollId["id"]]) && $pollId["open"] == 1) || $pollId["open"] == 0) || $_SESSION["role"]["creator"] == 1){
	        	?>
	        	timerInterval = setInterval( function() {refreshPoll(<?php echo $pollId["id"];?>)}, 1000);
	        	<?php 
			}
        }
		?>

		$('#goToPollToken').keypress(function(e){
			if(e.keyCode==13)
				showPoll();
		});
		
	});
	
	<?php if(((isset($_COOKIE['pollId' . $pollId["id"]]) && $pollId["open"] == 1) || $pollId["open"] == 0) || $_SESSION["role"]["creator"] == 1){?>
		var pieData = [];
		var categories = [];
		var ok = [];
		var neutral = [];
		var notOk = [];
		var pointsCount = [];
		var pointData = [];
		var drawedFirsttime = false;
		var totalVotes = 0;
		function refreshPoll(pId)
		{
			$.ajax({
				url: 'modules/actionHandler.php',
				type: 'get',
				data: 'action=getPollVotes&pollToken=' + pId,
				dataType: 'json',
				success: function(output) {
					totalVotes = 0;
					pieData = [];
					categories = [];
					pointsCount = [];
					ok = [];
					neutral = [];
					notOk = [];
					pointData = [];
					if(output[2][0]["open"] == 0)
					{
						clearInterval(timerInterval);
						console.log("clear");
					}
					for(var i = 0; i < output[0].length; i++)
					{
						<?php  
						if($pollId["question_type"] == 0) {
						?>
							var allVotePossibilities = parseInt(output[0][i]["yesVotes"]);
							
							$('#answerId_' + output[0][i]["id"]).html(allVotePossibilities);
							totalVotes += parseInt(allVotePossibilities);
							pieData.push({name:output[0][i]["text"], y:parseFloat(allVotePossibilities)});
						<?php 
						} else if($pollId["question_type"] == 1) {
						?>
							var allVotePossibilities = parseInt(output[0][i]["yesVotes"]) + parseInt(output[0][i]["noVotes"]) + parseInt(output[0][i]["neutralVotes"]);
							
							$('#answerId_' + output[0][i]["id"]).html(allVotePossibilities);
							totalVotes += parseInt(allVotePossibilities);

							ok.push(parseInt(output[0][i]["yesVotes"]));
							neutral.push(parseInt(output[0][i]["neutralVotes"]));
							notOk.push(parseInt(output[0][i]["noVotes"]));
							
							categories.push(output[0][i]["text"]);

						<?php
						}
						?>
					}
					<?php
					if($pollId["question_type"] == 1){
					?>
						for(j = output[0].length*-1; j <= output[0].length; j++)
						{
							pointsCount.push(j);
							pointData.push(0);
						}
						pieData.push({name:"richtig", data:ok}, {name:"neutral", data:neutral},{name:"falsch", data:notOk});
						for(var i = 0; i < output[1].length; i++)
						{
							var arrayIdx = parseInt(output[1][i]["points"]) + parseInt(output[0].length);
							pointData[arrayIdx]++;
						}
					<?php 
					} 
					?>

					$('#totalVotes').html(totalVotes / output[0].length);

					if(!drawedFirsttime)
					{
			        	drawChart();
			        	drawedFirsttime = true;
					} else {
						<?php  
						if($pollId["question_type"] == 0) {
						?>
							$('#resultContainer').highcharts().series[0].setData(pieData);
						<?php  
						} else if($pollId["question_type"] == 1) {
						?>
							$('#resultContainer').highcharts().series[0].setData(pieData[0]["data"]);
							$('#resultContainer').highcharts().series[1].setData(pieData[1]["data"]);
							$('#resultContainer').highcharts().series[2].setData(pieData[2]["data"]);

							$('#resultContainer2').highcharts().series[0].setData(pointData);
						<?php  
						}
						?>
						$('#resultContainer').highcharts().redraw();
						$('#resultContainer2').highcharts().redraw();
					}
					/*if(output[0] == 'ok')
					{
						$('#answerId_0').html(0);
					} else {
						console.log("Process failed2");
					}*/
				}, error: function()
				{
					console.log("Process failed1");
				}
			});
		}

		<?php  
		if($pollId["question_type"] == 0) {
		?>
		function drawChart()
		{
			$('#resultContainer').highcharts({
		        chart: {
		            plotBackgroundColor: null,
		            plotBorderWidth: null,
		            plotShadow: false,
		            type: 'pie'
		        },
		        tooltip: {
		            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b> ({point.y})'
		        },
		        title:{
		            text:''
		        },
		        plotOptions: {
		            pie: {
		                allowPointSelect: true,
		                cursor: 'pointer',
		                dataLabels: {
		                    enabled: true,
		                    format: '<b>{point.name}</b>: {point.percentage:.1f}% ({point.y})',
		                    style: {
		                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
		                    }
		                }
		            }
		        },
		        series: [{
		            name: 'Result',
		            colorByPoint: true,
		            data: pieData
		        }]
		    });
		}
		<?php 
		} else if($pollId["question_type"] == 1) {
		?>
			// stacked column chart
			function drawChart()
			{
				//for vote results
				$('#resultContainer').highcharts({
			        chart: {
			            type: 'bar'
			        },
			        title: {
			            text: 'Ergebnisse Abstimmung'
			        },
			        "xAxis": {
			        	categories: categories,
			            "minorGridLineWidth": 0,
		                "minorTickLength": 0,
		                "tickLength": 0,			                
		                "labels": {
		                	"overflow": "justify",
		                    step: 1,
		                    "y": -10
			            }
			        },
			        yAxis: {
			            min: 0,
			            title: {
			                text: 'Anzahl votes'
			            },
			            stackLabels: {
			                enabled: true,
			                style: {
			                    fontWeight: 'bold',
			                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
			                }
			            }
			        },
			        legend: {
			            align: 'right',
			            verticalAlign: 'top',
			            floating: true,
			            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
			            borderColor: '#CCC',
			            borderWidth: 1,
			            shadow: false
			        },
			        tooltip: {
			            headerFormat: '<b>{point.x}</b><br/>',
			            pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
			        },
			        plotOptions: {
			            series: {
			                stacking: 'normal',
			                dataLabels: {
			                    enabled: true,
			                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
			                    style: {
			                        textShadow: '0 0 3px black'
			                    }
			                }
			            }
			        },
			        series: pieData
			    });

				var dataSum = 0;
				for (var i=0;i < pointData.length;i++) {
				    dataSum += pointData[i]
				}
				
				//for points
				$('#resultContainer2').highcharts({
					chart: {
			            type: 'column'
			        },
			        title: {
			            text: 'Punkteverteilung'
			        },
			        xAxis: {
			            categories: pointsCount
			        },
			        yAxis: {
			            min: 0,
			            title: {
			                text: 'Anzahl'
			            }
			        },
			        tooltip: {
			            headerFormat: '<span><b>{point.key} Punkte</b></span><table>',
			            pointFormat: '<td style="padding:0">{point.y} mal vorhanden</td></tr>',
			            footerFormat: '</table>',
			            shared: true,
			            useHTML: true
			        },
			        plotOptions: {
			            column: {
			                pointPadding: 0.2,
			                borderWidth: 0,
			                dataLabels: {
			                    enabled: true,
			                    formatter:function() {
			                        var pcnt = (this.y / dataSum) * 100;
			                        return Highcharts.numberFormat(pcnt) + '% (' + this.y + '/' + dataSum + ')';
			                    },
			                    color: 'black'
			                }
			            }
			        },
			        series: [{data: pointData}]
			    });
			}
		<?php 
		}
		?>
	<?php } // chart for question type 0 (singlechoise) if end ?>
	
		function showChart()
		{
			console.log("toggled");
			$('#resultContainer').toggle();
			$('#resultContainer2').toggle();
			$(window).resize();
			$('#resultContainer').highcharts().reflow();
			$('#resultContainer2').highcharts().reflow();
		}

		function showResults(pollId)
		{
			console.log("toggled R");
			$.ajax({
				url: 'modules/actionHandler.php',
				type: 'get',
				data: 'action=getCorrectAnswers&pollId=' + pollId,
				dataType: "json",
				success: function(output) {
					//console.log("j: " + JSON.stringify(output));
					if(output[0] == 'ok')
					{
						for(var i = 0; i < output[1].length; i++)
						{
							console.log("o: " + output[1][i]["id"]);
							if(output[1][i]["question_type"] == 0) //singlechoise
							{
								if(output[1][i]["correct"] == 1)
									$('#answer_' + output[1][i]["id"]).css('background-color', 'rgba(0, 255, 0, 0.39);');
							} else if(output[1][i]["question_type"] == 1) //multiplechoise
							{
								if(output[1][i]["correct"] == -1)
									$('#answer_'+output[1][i]["id"]+'_-1').css('background-color', 'rgba(0, 255, 0, 0.39);');
								if(output[1][i]["correct"] == 0)
									$('#answer_'+output[1][i]["id"]+'_0').css('background-color', 'rgba(0, 255, 0, 0.39);');
								if(output[1][i]["correct"] == 1)
									$('#answer_'+output[1][i]["id"]+'_1').css('background-color', 'rgba(0, 255, 0, 0.39);');
							}
						}
					} else {
						console.log("Fehler1");
					}
				}, error: function()
				{
					console.log("Fehler2");
				}
			});
		}

		function switchType()
		{
			var selectedType = $('input[name=questionType]:checked').val();
			console.log(selectedType);
			var type = 'radio';
			if(selectedType == 'multiple')
			{
				type = 'checkbox';
			}
			var answerDiv = $('#pollAnswers');
			answerDiv.html('');
			for(var i = 0; i < 5; i++)
			{
				//$('.correctAnswerClass').attr('type', type);
				if(selectedType == 'multiple')
				{
					answerDiv.append('<label for="answer_'+i+'">Antwort '+(i+1)+': </label>');
					answerDiv.append('<input style="margin-left: 5px;" class="correctAnswerClass" type="radio" name="correctAnswer_'+i+'" value="-1"><input style="margin-left: 5px;" class="correctAnswerClass" type="radio" name="correctAnswer_'+i+'" value="0"><input style="margin-left: 5px;" class="correctAnswerClass" type="radio" name="correctAnswer_'+i+'" value="1"> (falsch, neutral, richtig)');
					answerDiv.append('<input type="text" width="200" class="form-control text-input" name="answer_'+i+'">');
					//$('.correctAnswerClass').attr('name', 'correctAnswer_'+i);
				}
				else
				{
					answerDiv.append('<label for="answer_'+i+'">Antwort '+(i+1)+': </label>');
					answerDiv.append('<input style="margin-left: 5px;" class="correctAnswerClass" type="radio" name="correctAnswer" value="'+i+'"> Richtige Antwort');
					answerDiv.append('<input type="text" width="200" class="form-control text-input" name="answer_'+i+'">');
					//$('.correctAnswerClass').attr('name', 'correctAnswer');
				}
			}
			
		}
	
	<?php
	$errorCode = new mobileError("", "red");
	if(isset($_GET["code"]))
	{
		$errorCode = handlePollError($_GET["code"]);
	}
	?>
	
</script>
<div class="container theme-showcase">
	<div class="page-header">
		<h1><?php echo $lang["pollNav"];?></h1>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Do the poll!</h3>
		</div>
		<div class="panel-body">
			<p style="color: <?php echo $errorCode->getColor();?>;"><?php echo $errorCode->getText();?></p>
			<div class="col-md-2 col-sm-2">
				<input style="margin-bottom: 5px; width: 116px;" id="goToPoll" name="goToPoll" class="btn" onclick="switchDisplay('goToPollContent')" type="button" value="<?php echo $lang["goToPoll"];?>" />
				<?php if($_SESSION["role"]["creator"] == 1) {?><input style="margin-bottom: 5px; width: 116px;" id="yourPolls" name="yourPolls" class="btn" onclick="switchDisplay('yourPollsContent')" type="button" value="<?php echo $lang["yourPoll"];?>" />
				<input style="margin-bottom: 5px; width: 116px;" id="createPoll" name="createPoll" class="btn" onclick="switchDisplay('createPollContent')" type="button" value="<?php echo $lang["createPoll"];?>" /><?php }?>
				<?php 
				$showNA = false;
				if($questionResultFound > 0) {
					$stmt = $dbh->prepare("select poll_answers.id, yesVotes, noVotes, neutralVotes, poll_answers.text, open from poll_answers inner join poll on poll.id = poll_id where poll.id = :pollId");
					$stmt->bindParam(":pollId", $pollId["id"]);
					$stmt->execute();
					$selectedPollRowCount = $stmt->rowCount();
					$fetchAnswersForPrint = $stmt->fetchAll(PDO::FETCH_ASSOC);
				}
				?>
				<label for="pollIdWrapper">Umfragenummer:</label>
				<div id="pollIdWrapper">
					<input type="number" id="goToPollToken" name="goToPollToken" class="form-control" style="width: 116px; float: left;" />
					<img style="margin-left: 8px; cursor: pointer;" alt="add" src="assets/arrow-right.png" width="28" height="32" onclick="showPoll()">
				</div>
			</div>
			<div id="pollTabs" class="col-md-10 col-sm-10" style="border-left: 1px solid #E0E0E0;">
				<div class="contentWrapper" id="goToPollContent">
					<?php 
					if($questionResultFound > 0) {
						echo "<h3>Umfrage: ". $pollId["question"]." <span style=\"font-size: 10px;\">(".$pollId["token"].")</span><span style=\"float: right;\">Total: <span id=\"totalVotes\"></span></span></h3>";
						if($pollId["picture"] != NULL)
						{
							echo '<div>';
							echo "<img id=\"questionImg\" style=\"width: 550px; height: 300px;\" src=\"".$pollId["picture"]."\">";
							echo '</div>';
						}
						if($fetchAnswersForPrint[0]["open"] == 0)
							echo '<br /><span style="color: red; font-size: 12px;">(geschlossen)</span>';
						echo '<hr>';
					}
					
					if($selectedPollRowCount > 0)
					{
						$totalVotes = 0;
						if(!isset($_COOKIE['pollId' . $pollId["id"]])){
							echo '<form method="post" action="?p=actionHandler&action=sendVote">'; 
						}
						
						if(((isset($_COOKIE['pollId' . $pollId["id"]]) && $pollId["open"] == 1) || $pollId["open"] == 0) || $_SESSION["role"]["creator"] == 1){
							$displayMode = "";
							if($_SESSION["role"]["creator"] == 1)
							{
								$displayMode = "display: none;";
							}
							?>
							<div id="resultContainer" style="min-width: 310px; max-width: 800px; height: 400px; margin: 0 auto; <?php echo $displayMode;?>"></div>
							<?php 
							if($pollId["question_type"] == "1") //multiplechoise
							{
							?>
								<div id="resultContainer2" style="min-width: 310px; max-width: 800px; height: 400px; margin: 0 auto; <?php echo $displayMode;?>"></div>
							<?php 
							}
							?>
							<div style="margin-bottom: 5px;"><p><b>Antworten:</b></p>
							<?php 
						}
						
						if($pollId["question_type"] == "1") //multiplechoise
						{
							echo '<table cellspacing="10"><tr><td style="width: 50px;">falsch</td><td style="width: 50px;">keine Antwort</td><td style="width: 50px;">richtig</td><td></td></tr>';
						}
						
						for($i = 0; $i < count($fetchAnswersForPrint); $i++)
						{
							$voteResult = "";
							if(isset($_COOKIE['pollId' . $pollId["id"]]) || $_SESSION["role"]["creator"] == 1){
								//$voteResult = ' <span id="answerId_'.$fetchAnswersForPrint[$i]["id"].'">'.$fetchAnswersForPrint[$i]["votes"].'</span>';
							}
							
							$yourAnswer = "";
							$checked = "";
							$checked1 = "";
							$checked2 = "";
							$checked3 = "";
							$disabled = "";
							if(isset($_COOKIE['pollId' . $pollId["id"]]) || $fetchAnswersForPrint[0]["open"] == 0){
								$disabled = "disabled";
								if($pollId["question_type"] == "0") //multiplechoise
								{
									if($fetchAnswersForPrint[$i]["id"] == $_COOKIE['pollId' . $pollId["id"]])
									{
										$yourAnswer = "<i> - Ihre Antwort</i>";
										$checked = "checked";
									}
								} else if($pollId["question_type"] == "1") //multiplechoise
								{
									$cookieData = json_decode($_COOKIE['pollId' . $pollId["id"]],true);
									switch ($cookieData[$fetchAnswersForPrint[$i]["id"]])
									{
										case -1:
											$checked1 = "checked";
											break;
										case 0:
											$checked2 = "checked";
											break;
										case 1:
											$checked3 = "checked";
											break;
									}
								}
							} else {
								if($pollId["question_type"] == "1") //multiplechoise
								{
									$checked2 = "checked";	
								}
							}
							if($pollId["question_type"] == "1") //multiplechoise
							{
								echo '<tr>';
								
								echo '<td style="text-align: center;" id="answer_'.$fetchAnswersForPrint[$i]["id"].'_-1"><input type="radio" name="voteAnswers_'.$fetchAnswersForPrint[$i]["id"].'" value="-1" '.$disabled.' '.$checked1.'></td>';
								
								echo '<td style="text-align: center;" id="answer_'.$fetchAnswersForPrint[$i]["id"].'_0"><input type="radio" name="voteAnswers_'.$fetchAnswersForPrint[$i]["id"].'" value="0" '.$disabled.' '.$checked2.'></td>';
								
								echo '<td style="text-align: center;" id="answer_'.$fetchAnswersForPrint[$i]["id"].'_1"><input type="radio" name="voteAnswers_'.$fetchAnswersForPrint[$i]["id"].'" value="1" '.$disabled.' '.$checked3.'></td>';
								
								echo '<td style="border-left: 1px solid rgb(196, 196, 196); padding-left: 5px;">'.$fetchAnswersForPrint[$i]["text"] . $yourAnswer. ' ' . $voteResult . '</td>';
								
								echo '</tr>';
								
							} else if($pollId["question_type"] == "0"){ //singlechoise
								echo '<div id="answer_'.$fetchAnswersForPrint[$i]["id"].'">
        								<label class="radio-inline">
        									<input type="radio" style="float: left; margin-right: 5px;" name="voteAnswers" value="'.$fetchAnswersForPrint[$i]["id"].'" '.$disabled.' '.$checked.'>';
											echo '<p>'.$fetchAnswersForPrint[$i]["text"] . $yourAnswer. ' ' . $voteResult . '</p>
        								</label>
        						</div>';
							}
							$totalVotes += ($fetchAnswersForPrint[$i]["yesVotes"] + $fetchAnswersForPrint[$i]["noVotes"] + $fetchAnswersForPrint[$i]["neutralVotes"]);
						}
						
						if($pollId["question_type"] == "1") //multiplechoise
						{
							echo '</table>';
						}
						
						if(isset($_COOKIE['pollId' . $pollId["id"]]) || $_SESSION["role"]["creator"] == 1){
							echo "</div>";
						}
						
						if(!isset($_COOKIE['pollId' . $pollId["id"]]) && $fetchAnswersForPrint[0]["open"] != 0){
							//show votebutton
							echo '<input type="hidden" name="pollId" value="'.$pollId["id"].'">';
							echo '<input type="submit" name="vote" value="Vote" class="btn">';
							echo '</form>';
						}
						if(isset($_COOKIE['pollId' . $pollId["id"]]) || $_SESSION["role"]["creator"] == 1){
							echo '<input style="margin-top: 5px;" type="button" class="btn" value="zeige/verstecke Chart" onclick="showChart()"><br />';
							echo '<input style="margin-top: 5px;" type="button" class="btn" value="zeige/verstecke Ergebnisse" onclick="showResults('.$pollId["id"].')">';
						}
						
					} else {
						$showNA = true;
					}
					if($showNA) {echo "Keine Umfrage offen<br />";}?>
					<script type="text/javascript">
						$('#totalVotes').html(<?php echo $totalVotes / count($fetchAnswersForPrint);?>);
					</script>
					
				</div>
				<?php
					if($_SESSION["role"]["creator"] == 1)
					{ 
						$stmt = $dbh->prepare("select *, (select count(*) from poll_answers where poll_id = poll.id)answerCount, (select (sum(yesVotes)+sum(noVotes)+sum(neutralVotes)) from poll_answers where poll_id = poll.id)sumVotes from poll");
						$stmt->execute();
						$fetchPolls = $stmt->fetchAll(PDO::FETCH_ASSOC);
						
						?>
						<div class="contentWrapper" id="yourPollsContent">
							<p id="ajaxAnswer1"></p>
							<table class="pollTable" id="pollTable" style="margin: 0px;">
								<thead>
									<tr>
										<th>
					                    	<?php echo $lang["idCol"];?>
					                    </th>
										<th>
					                    	<?php echo $lang["question"];?>
					                    </th>
										<th>
					                    	<?php echo $lang["questionAmountAnswers"];?>
					                    </th>
										<th>
					                    	<?php echo $lang["amountParticipations"];?>
					                    </th>
										<th>
					                    	<?php echo $lang["pollToken"];?>
					                    </th>
										<th>
					                    	<?php echo $lang["pollCreationDate"];?>
					                    </th>
										<th>
					                    	<?php echo $lang["quizTableActions"];?>
					                    </th>
									</tr>
								</thead>
				                <tbody>
				                	<?php for($i = 0; $i < count($fetchPolls); $i++) {?>
				                	<tr>
										<td>
					                		<?php echo $fetchPolls[$i]["id"];?>
					                	</td>
										<td>
					                		<?php echo $fetchPolls[$i]["question"];?>
					                	</td>
										<td>
					                		<?php echo $fetchPolls[$i]["answerCount"];?>
					                	</td>
										<td>
					                		<?php echo $fetchPolls[$i]["question_type"]==0 ? $fetchPolls[$i]["sumVotes"] : $fetchPolls[$i]["sumVotes"] / $fetchPolls[$i]["answerCount"];?>
					                	</td>
										<td>
					                		<?php echo "<a href=\"?p=poll&token=" . $fetchPolls[$i]["token"] . "\">" . $fetchPolls[$i]["token"] . "</a>";?>
					                	</td>
										<td>
					                		<?php echo date("d.m.Y H:i:s", $fetchPolls[$i]["creation_date"]);?>
					                	</td>
					                	<td><?php 
						                    	$prio = "Green";
						                    	$hint = "Umfrage ist aktiviert";
						                    	if($fetchPolls[$i]["open"] == 0)
						                    	{
						                    		$prio = "Red";
						                    		$hint = "Umfrage ist deaktiviert";
						                    	}
						                    	
						                    ?>
						                    <img id="openImg_<?php echo $fetchPolls[$i]["id"];?>" class="activatedImg" src="<?php echo "assets/priority" . $prio . ".png";?>" original-title="<?php echo $hint;?>" style="margin-right: 5px; cursor: pointer;" onclick="switchPollState(<?php echo $fetchPolls[$i]["id"];?>)"/>&nbsp;
						                    <a href="?p=editPoll&id=<?php echo $fetchPolls[$i]["id"];?>" class="editPoll" "><img id="editPoll" src="assets/icon_edit.png" alt="" height="18px" width="18px"></a>&nbsp;
						                    <img id="delPollImg" style="cursor: pointer;" class="deletePoll delPollImg" src="assets/icon_delete.png" alt="" height="18px" width="18px" onclick="delPoll(<?php echo $fetchPolls[$i]["id"];?>)">&nbsp;
					                	</td>
									</tr>
									<?php }?>
				                </tbody>
							</table>
						</div>
						<?php 
					}
				
				if($_SESSION["role"]["creator"] == 1)
				{ 
				?>
				<div class="contentWrapper" id="createPollContent">
					<form action="?p=actionHandler&action=createPoll" method="post" enctype="multipart/form-data">
						<label for="question">Frage:</label>
						<textarea name=question id="question" class="form-control text-input" wrap="soft"></textarea>
						<p style="margin-top: 5px;">Bild zur Frage hinzuf&uuml;gen:</p>
						<input type="file" id="picture" name="picture" class="btn" accept=".jpeg,.jpg,.bmp,.png,.gif" />
						<br />
						<div id="pollAnswers">
							<?php 
								/*for($i = 0; $i < 5; $i++)
								{
									echo '<label for="answer_'.$i.'">Antwort '.($i+1).': </label>';
									echo '<input style="margin-left: 5px;" class="correctAnswerClass" type="checkbox" name="correctAnswer_'.$i.'" value="1"> Richtige Antwort';
									echo '<input type="text" width="200" class="form-control text-input" name="answer_'.$i.'">';
								}*/
							?>
						</div>
						<div style="margin-top: 5px;">
							<input type="radio" id="questionTypeSingle" name="questionType" value="single" onchange="switchType()"> Singlechoise
							<input type="radio" id="questionTypeMultiple" name="questionType" value="multiple" checked="checked" onchange="switchType()"> Multiplechoise
						</div>
						<input style="margin-top: 10px;" type="submit" class="btn" id="btnSave" name="btnSave" value="<?php echo $lang["publish"];?>" />
					</form>
				</div>
				<?php }?>
			</div>
		</div>
	</div>
</div>
</div>