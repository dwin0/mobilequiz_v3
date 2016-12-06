<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="<?php echo isset($_SESSION["nickname"]) ? '?p=quiz':'?p=home';?>">MobileQuiz.ch</a>
    </div>
	<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		<ul class="nav navbar-nav">
        	<?php if($_SESSION["role"]["user"] == 1) {?>
	        	<li><a href="?p=quiz" id="quizNavi"><?php echo $lang["quizzes"];?></a></li>
	        	<?php if($_SESSION["role"]["creator"] == 1) {?>
		            <li><a href="?p=questions" id="questionNavi"><?php echo $lang["navHeaderQuestions"];?></a></li>
		            <li><a href="?p=createEditExecution" id="executionNavi"><?php echo $lang["navHeaderExecution"];?></a></li>
		            <li><a href="?p=topics" id="applicationAreaNavi"><?php echo $lang["navHeaderTopics"];?></a></li>
	            <?php }?>
	            <li><a href="?p=keywords" id="keywordsNavi"><?php echo $lang["navHeaderKeywords"];?></a></li>
	            <?php if($_SESSION["role"]["creator"] == 1) {?>
	            	<li><a href="?p=adminSection" id="adminNavi"><?php echo $lang["navHeaderAdmin"];?></a></li>
	            <?php }?>
	    	<?php }?>
			<li><a href="?p=poll" id="pollNavi"><?php echo $lang["pollNav"];?></a></li>
	    </ul>
		<ul class="nav navbar-nav navbar-right" style="margin-right: 0px;">
            <li class="dropdown">
                <a href="" class="dropdown-toggle" data-toggle="dropdown">
	                <?php 
	                $ger = false;
	                $en = false;
	                if($_SESSION["language"] == "ger")
	                {
	                ?>
                		<img src="assets/german.png" alt="Deutsch" title="Deutsch" width="25px"> Deutsch <b class="caret"></b>
                	<?php 
                		$ger = true;
	                } else {
					?>
                		<img src="assets/english.png" alt="English" title="DeuEnglishtsch" width="25px"> English <b class="caret"></b>
                	<?php 
                		$en = true;
					}
					?>
                </a>
                <ul class="dropdown-menu">
                    <li><a id="languageGerman" href="?p=settings&action=lang&locale=ger&fromsite=<?php echo $_GET["p"];?>" ><img src="assets/german.png" alt="Deutsch" title="Deutsch" width="25px"><?php echo $ger ? '<strong>':'';?> Deutsch<?php echo $ger ? '</strong>':'';?></a></li>
                	<li><a id="languageEnglish" href="?p=settings&action=lang&locale=en&fromsite=<?php echo $_GET["p"];?>" ><img src="assets/english.png" alt="English" title="English" width="25px"><?php echo $en ? '<strong>':'';?> English<?php echo $en ? '</strong>':'';?></a></li>
            	</ul>
            </li>
                  
			<li class="dropdown">
				<?php
					
					$roleNames = array(
									'admin' => 'roleAdmin',
									'manager' => 'roleManager',
									'creator' => 'roleCreator',
									'user' => 'roleUser'
							);
					
					if($_SESSION["role"]["fakeUser"] == -1)
					{
						$role = $_SESSION["role"]["name"];
					} else if($_SESSION["role"]["fakeUser"] == 1)
					{
						$role = "user";
					}
					
					$navText = $lang["navHeaderHello"] . " " . $lang["guest"];
					if(isset($_SESSION["nickname"]))
					{
						$navText = $lang["loggedInAs"] . " " . $lang[$roleNames[$role]] . " - " . htmlspecialchars($_SESSION["email"]);
					}
				?>
				<a href="" class="dropdown-toggle" data-toggle="dropdown"><?php echo $navText;?> <b class="caret"></b></a>
				<ul class="dropdown-menu">
					<?php if(isset($_SESSION["id"])) {?><li><a href="?p=profile"><?php echo $lang["profile"]; ?></a></li><?php }?>
					<?php if(isset($_SESSION["id"]) && ($_SESSION["role"]["creator"] == 1 || $_SESSION['role']['fakeUser'] == 1)) {?><li><a href="?p=auth&action=switchRole"><?php echo $lang["switchRoleNav"]; ?></a></li><?php }?>
					<li><a href="<?php echo isset($_SESSION["id"]) ? "?p=auth&action=logout" : "?p=home"?>" id="login"><?php echo isset($_SESSION["id"]) ? $lang["logout"] : $lang["login"]?></a></li>
				</ul>
			</li>
		</ul>
	</div>
</nav>