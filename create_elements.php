<!DOCTYPE html>
<?php
	require_once 'core/init.php';
	$sUsername = isset($_POST['tbUsername']) ? $_POST['tbUsername'] : '';
	$sPassword = isset($_POST['tbPassword']) ? $_POST['tbPassword'] : '';

	$curDate = date('Y-m-d');

	//============================
	// Check if user is logged in
	$iLoggedIn = 0;
	$sCookie = isset($_COOKIE[Config::get('config/cookie/cookie_name')]) ? $_COOKIE[Config::get(' config/cookie/cookie_name')] : '';
	$qCookie = DB::getInstance() -> query('SELECT userid, permission, cookie, profilename FROM users WHERE cookie = ?', array($sCookie));

	// Cookies have the same values
	if($qCookie -> count() > 0)
	{
		$dbCookie 		= $qCookie -> first() -> cookie;
		$dbUserid 		= $qCookie -> first() -> userid;
		$dbProfilename  = $qCookie -> first() -> profilename;
		$dbPermission 	= $qCookie -> first() -> permission;

		// both variables aren't "NOT_SET"
		if(strcmp($dbCookie, "NOT_SET") && strcmp($sCookie, "NOT_SET"))
		{
			$iLoggedIn = 1;
			$iPermission = $dbPermission;
			unset($dbPermission);
		}
	}
	else
	{
		// The cookie is not in the database, removing cookies
		unset($_COOKIE[Config::get('config/cookie/cookie_name')]);
		setcookie(Config::get('config/cookie/cookie_name'), null, -1);
		Header('Location: index.php');
	}

	//=================================
	// Log in the user on button press
	if($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		// Login button has been pressed
		if(isset($_POST['btnSubmit']))
		{
			// Username and password contain information
			if(strlen($sUsername) > 3 && strlen($sPassword) > 3)
			{
				$qPassword = DB::getInstance() -> query('SELECT password, salt FROM users WHERE username = ? LIMIT 1', array($sUsername));

				// There is an user registered
				if($qPassword -> count() > 0)
				{
					$dbPassword 	= $qPassword -> first() -> password;
					$dbSalt 		= $qPassword -> first() -> salt;
					$userPassword 	= hash("whirlpool", $sPassword . $dbSalt);

					// The entered passwords are the same, create cookie and add it to the database
					if(!strcmp($userPassword, $dbPassword))
					{
						echo '<script type = "text/javascript">localStorage.setItem("login_message", "send");</script>';
						$sCookieValue = $dbSalt . Functions::generate_uniqueID(5) . $sUsername;
						$sCookieValue = hash("whirlpool", $sCookieValue);

						setcookie(Config::get('config/cookie/cookie_name'), $sCookieValue, time() + 60 * 60 * 24 * 30);
						$qCookie = DB::getinstance() -> update('users', array(
															'cookie' => $sCookieValue),
														array(
															'username', '=', $sUsername
														));

						Header("Location: index.php");
					}
					else
					{
						echo '<script type = "text/javascript">alert("The username or password you entered is incorrect. Please try again.");</script>';
					}
				}
				else
				{
					echo '<script type = "text/javascript">alert("The username or password you entered is incorrect. Please try again.");</script>';
				}
			}
		}
	}
?>

<html>
	<head>
		<title>Create forum elements</title>

		<meta charset = "utf-8">
		<meta name = "viewport" content = "width = device-width, initial-scale = 1.0, maximum-scale = 1.0, user-scalable = 0" />
		<link href = "css/bootstrap.min.css" rel = "stylesheet" />
		<link href = "resources/font-awesome/css/font-awesome.min.css" rel = "stylesheet" />
		<link href = "css/style.css" rel = "stylesheet" />
		<link rel = "shortcut icon" href = "resources/favicon.png" />
	</head>

	<body>
		<nav class = "navbar navbar-inverse navbar-override navbar-static-top" role = "navigation">
			<div class = "container navbar-inner">
				<a href = "index.php" class = "navbar-brand"><?php echo Config::get('config/forum_name/name'); ?></a>

				<button class = "navbar-toggle" data-toggle = "collapse" data-target = ".navHeaderCollapse">
					<span class = "icon-bar"></span>
					<span class = "icon-bar"></span>
					<span class = "icon-bar"></span>
				</button>

				<div class = "collapse navbar-collapse navHeaderCollapse">
					<ul class = "nav navbar-nav navbar-right">
						<li><a href = "memberlist.php">Community members</a></li>
						<li><a href = <?php echo '"search.php?fromdate=' . $curDate . '&todate=' . $curDate . '">Today\'s posts'; ?></a></li>
						<li><a href = "search.php">Search</a></li>

						<?php
							if($iLoggedIn)
							{
								$qMessagesUnread = DB::getInstance() -> query('SELECT count(messageid) AS totalmessages FROM messages WHERE receiverid = ? AND message_read = 0', array($dbUserid));
                            	$iMessageUnread = $qMessagesUnread -> first() -> totalmessages;
							}
							else
							{
								$iMessageUnread = 0;
							}

							if($iLoggedIn == 0)
							{
								echo '<li><a href = "#" data-toggle = "modal" data-target = "#loginModal">Log in</a></li>';
								echo '<li><a href = "register.php">Sign up</a></li>';
							}
							else
							{
								echo '<li class = "dropdown">
									<a href = "#" class = "dropdown-toggle" data-toggle = "dropdown" role = "button">' . $dbProfilename . ' <span class = "caret"></span></a>

									<ul class="dropdown-menu">
										<li><a href = "member.php?u=' . $dbUserid . '">Profile</a></li>
										<li><a href = "search.php?p_username=' . $dbProfilename . '">My posts</a></li>
										<li><a href = "messages.php">Messages&nbsp;<span class = "badge">' . $iMessageUnread . '</span></a></li>
										<li role="separator" class="divider"></li>
										<!--<li><a href = "#">Settings</a></li>-->
										<li><a href = "logout.php">Sign out</a></li>
									</ul>
								</li>';
							}
						?>
					</ul>
				</div>
			</div>
		</nav>

		<!--===============
			No javascript warning message
		================-->
		<noscript>
			<div class = "no-js">
				Important parts of this website only work with JavaScript. Please enable JavaScript in your browser options.
			</div>
		</noscript>

		<!--
			=================================
				Error message table
			=================================
        -->
		<div class = "container extra-padding-top">
            <table id = "error-table" cellpadding = "2" cellspacing = "1" width = "50%" align = "center" class = "textarea_mobile" style="display: none;">
	            <thead>
	                <tr class = "border">
	                    <td class = "groupname" colspan = "2">
	                        <b class = "white-text"><span id = "change-error-title"></span></b>
	                    </td>
	                </tr>
	            </thead>

	            <tbody>
	                <tr align = "center">
	                   <td class = "postinfo extra-padding" style = "font-size: 16px;"><span id = "change-error-text"></span></td>
	                </tr>
	            </tbody>
	        </table>

	        <div class = "row-buffer-2"></div>

	        <!--
				=================================
					Base table with all options
				=================================
            -->
			<table id = "base-step" cellpadding = "2" cellspacing = "1" width = "50%" align = "center" class = "textarea_mobile">
                <thead>
                    <tr class = "border">
                        <td class = "groupname" colspan = "2">
                            <b class = "white-text">Create elements</b>
                        </td>
                    </tr>
                </thead>

                <tbody>
                    <tr align = "center">
                       <td class = "postinfo"><a id = "trigger-step-1" href = "#" class = "btn btn-info form-control">Create a new forum category</a></td>
                    </tr>

                    <tr align = "center">
                    	<td class = "postinfo"><a id = "trigger-step-2" href = "#" class = "btn btn-info form-control">Create a new sub forum</a></td>
                    </tr>
                </tbody>
            </table>

            <!--
				=================================
					Create a new forum category
				=================================
            -->
            <form action = "create_elements.php?step=1" method = "post">
	            <table id = "step-1"  width = "50%" align = "center" class = "textarea_mobile" style="display: none;">
	                <thead>
	                    <tr class = "border">
	                        <td class = "groupname" colspan = "2">
	                            <b class = "white-text">Create a new forum category</b> <a href = "#" class = "go-back pull-right" style = "padding-right: 4px;">Go back</a>
	                        </td>
	                    </tr>
	                </thead>

	                <tbody>
	                	<tr>
	                		<td class = "postinfo">Forum name: <input type = "text" name = "step1-forumname" class = "form-control" /></td>
	                	</tr>

	                	<tr>
	                		<td class = "postinfo">
	                			Permission required to access this forum category: <br />

	                			<select name = "step1-permission" class = "form-control">
	                				<?php
	                					$qPermission = DB::getInstance() -> query('SELECT * FROM permissions');
	                					foreach($qPermission -> results() as $oPermission)
	                					{
	                						echo '<option value = "' . $oPermission -> permission . '">' . $oPermission -> permission_name . '</option>';
	                					}
	                				?>
	                			</select>
	                		</td>
	                	</tr>

	                    <tr align = "center">
	                       <td class = "postinfo" colspan = "2"><button type = "submit" class = "btn btn-info">Create</button></td>
	                    </tr>
	                </tbody>
	            </table>
            </form>

            <!--
				=================================
					Create a new sub forum
				=================================
            -->
            <form action = "create_elements.php?step=2" method = "post">
	            <table id = "step-2" cellpadding = "2" cellspacing = "1" width = "50%" align = "center" class = "textarea_mobile" style="display: none;">
	                <thead>
	                    <tr class = "border">
	                        <td class = "groupname" colspan = "2">
	                            <b class = "white-text">Create a new sub forum</b> <a href = "#" class = "pull-right go-back" style = "padding-right: 3px;">Go back</a>
	                        </td>
	                    </tr>
	                </thead>

	                <tbody>
	                    <tr>
	                		<td class = "postinfo">
	                			Forum name: <br />
	                			<select name = "step2-forumid" class = "form-control">
	                				<?php
	                					$qGetForum = DB::getInstance() -> query('SELECT forumid, forumname FROM forum WHERE permission_required <= ?', array($iPermission));
	                					foreach($qGetForum -> results() as $sForum)
	                					{
	                						echo '<option value = "' . $sForum -> forumid . '">' . $sForum -> forumname . '</option>';
	                					}
	                				?>
	                			</select>
	                		</td>
	                	</tr>

	                	<tr>
		            		<td class = "postinfo">Sub forum name: <input type = "text" name = "step2-subforumname" class = "form-control" /></td>
		            	</tr>

		            	<tr>
		            		<td class = "postinfo">Sub forum description: <input type = "text" name = "step2-subforumdescription" class = "form-control" /></td>
		            	</tr>

						<tr align = "center">
	                       <td class = "postinfo" colspan = "2"><button type = "submit" class = "btn btn-info">Create</button></td>
	                    </tr>
	                </tbody>
	            </table>
            </form>
		</div>

		<!--===============
			Login Modal
		================-->
		<div class = "modal fade" id = "loginModal" tabindex = "-1" role = "dialog" aria-labelledby = "myModalLabel">
			<div class = "modal-dialog" role = "document">
				<form class = "form-horizontal form-width" method = "post" action = "index.php">
					<div class = "modal-content">
						<div class = "modal-header">
							Log in
						</div>

						<div class = "modal-body">
							<div class = "input-group margin-bottom-sm">
								<span class = "input-group-addon"><i class = "fa fa-user fa-fw"></i></span>
								<input type = "text" class = "form-control input-lg" value = "<?php echo $sUsername; ?>" id = "tbUsername" name = "tbUsername" placeholder = "Username" />
							</div>

							<div class = "input-group margin-bottom-sm">
								<span class = "input-group-addon"><i class = "fa fa-lock fa-fw"></i></span>
								<input type = "password" class = "form-control input-lg" id = "tbPassword" name = "tbPassword" placeholder = "Password" />
							</div>
						</div>

						<div class = "modal-footer">
							<button type = "submit" class = "btn btn-primary" name = "btnSubmit">Log in</button>
						</div>
					</div>
				</form>
			</div>
		</div>

		<!--=====================================
			Scripts
		=====================================-->
		<script src = "js/jquery-1.11.1.min.js"></script>
		<script src = "js/bootstrap.min.js"></script>

		<script type = "text/javascript">
			$(document).ready(function() {
				$("#step-1").hide();
				$("#step-2").hide();
				$("#step-3").hide();
				$("#error-table").hide();

				$(".go-back").click(function() {
					$("#step-1").hide();
					$("#step-2").hide();
					$("#step-3").hide();
					$("#error-table").hide();
					$("#base-step").fadeIn(400);
				});

				$("#trigger-step-1").click(function() {
					$("#base-step").hide();
					$("#step-1").fadeIn(400);
				});

				$("#trigger-step-2").click(function() {
					$("#base-step").hide();
					$("#step-2").fadeIn(400);
				});

				$("#trigger-step-3").click(function() {
					$("#base-step").hide();
					$("#step-3").fadeIn(400);
				});
			});
		</script>
	</body>
</html>

<?php
	$iCurrentStep = isset($_GET['step']) ? $_GET['step'] : '';

	if(strlen($iCurrentStep) > 0)
	{
		if($iCurrentStep == 1)
		{
			echo '
				<script type = "text/javascript">
					$(document).ready(function() {
						$("#base-step").hide();
						$("#step-1").show();
					});
				</script>';

			if($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				$sForumName = isset($_POST['step1-forumname']) ? $_POST['step1-forumname'] : '';
				$sPermission = isset($_POST['step1-permission']) ? $_POST['step1-permission'] : '';

				if(strlen($sForumName) >= 4)
				{
					$qCreateNewForum = DB::getInstance() -> insert('forum', array(
													"forumname" => $sForumName,
													"permission_required" => $sPermission
												));
					echo '
						<script type = "text/javascript">
							window.location = "index.php";
						</script>';
				}
				else
				{
					echo '
						<script type = "text/javascript">
							$(document).ready(function() {
								$("#change-error-title").text("The following errors have been encountered tryint to create the new forum category: ");
								$("#change-error-text").text("The forum name is too short. Please extend the forum name to atleast 4 characters. ");
								$("#error-table").show();
							});
						</script>';
				}
			}
		}
		else if($iCurrentStep == 2)
		{
			echo '
				<script type = "text/javascript">
					$(document).ready(function() {
						$("#base-step").hide();
						$("#step-2").show();
					});
				</script>';

			if($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				$iForumID = isset($_POST['step2-forumid']) ? $_POST['step2-forumid'] : '';
				$sSubForumName = isset($_POST['step2-subforumname']) ? $_POST['step2-subforumname'] : '';
				$sSubForumDescription = isset($_POST['step2-subforumdescription']) ? $_POST['step2-subforumdescription'] : '';

				if(strlen($sSubForumName) >= 4 && strlen($sSubForumDescription) >= 4)
				{
					$qCreateSubForum = DB::getInstance() -> insert('subforum', array(
													"forumid" => $iForumID,
													"subforumname" => $sSubForumName,
													"subforumdescription" => $sSubForumDescription
												));
					echo '
						<script type = "text/javascript">
							window.location = "index.php";
						</script>';
				}
				else
				{
					echo '
						<script type = "text/javascript">
							$(document).ready(function() {
								$("#change-error-title").text("The following errors have been encountered tryint to create the new sub forum: ");
								$("#change-error-text").text("The sub forum name and/or title is too short. Please extend the sub forum name and/or title to atleast 4 characters. ");
								$("#error-table").show();
							});
						</script>';
				}
			}
		}
	}
?>
