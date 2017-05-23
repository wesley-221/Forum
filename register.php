<?php
	require_once 'core/init.php';

	$sUsername = isset($_POST['tbUsername']) ? $_POST['tbUsername'] : '';
	$sPassword = isset($_POST['tbPassword']) ? $_POST['tbPassword'] : '';

	$curDate = date('Y-m-d');

	//============================
	// Check if user is logged in
	$iLoggedIn = 0;
	$iPermission = 0;
	$sCookie = isset($_COOKIE[Config::get('config/cookie/cookie_name')]) ? $_COOKIE[Config::get(' config/cookie/cookie_name')] : '';
	$qCookie = DB::getInstance() -> query('SELECT userid, permission, cookie, profilename FROM users WHERE cookie = ?', array($sCookie));

	// Cookies have the same values
	if($qCookie -> count() > 0)
	{
		$dbUserid 		= $qCookie -> first() -> userid;
		$dbCookie 		= $qCookie -> first() -> cookie;
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
	}

	//=================================
	// Log in the user on button press
	if($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		// Login button has been pressed
		if(isset($_POST['btnSubmit']))
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

	//====================================================================
	// Variables potentionally filled in by user (ie. failed registration)
	$sUsername			= isset($_POST['register_Username']) 			? $_POST['register_Username'] : '';
	$sProfileName 		= isset($_POST['register_ProfileName']) 		? $_POST['register_ProfileName'] : '';
	$sEmail 			= isset($_POST['register_Email']) 			? $_POST['register_Email'] : '';
	$sEmail_Second 		= isset($_POST['register_Email_Second']) 		? $_POST['register_Email_Second'] : '';
	$sPassword 			= isset($_POST['register_Password'])			? $_POST['register_Password'] : '';
	$sPassword_Second 	= isset($_POST['register_Password_Second'])	? $_POST['register_Password_Second'] : '';
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Register Account</title>

		<meta charset = "utf-8">
		<meta name = "viewport" content = "width=device-width, initial-scale = 1.0, maximum-scale = 1.0, user-scalable = 0" />
		<link href = "css/bootstrap.min.css" rel = "stylesheet" />
		<link href = "resources/font-awesome/css/font-awesome.min.css" rel = "stylesheet" />
		<link href = "css/style.css" rel = "stylesheet" />
		<link rel = "shortcut icon" href = "resources/favicon.png" />

		<script type="text/javascript">
			$(document).ready(function(){
				$('[data-toggle="tooltip"]').tooltip();
			});
		</script>
	</head>

	<body>
		<!--=====================================
			Navigatie balk
		=====================================-->
		<nav class = "navbar navbar-inverse navbar-static-top" role = "navigation">
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
										<li><a href="member.php?u=' . $dbUserid . '">Profile</a></li>
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

		<noscript>
			<div class = "no-js">
				Important parts of this website only work with JavaScript. Please enable JavaScript in your browser options.
			</div>
		</noscript>

		<!--=====================================
			Content
		=====================================-->
		<div class = "container extra-padding-left">
			<div class = "col-lg-12">
				<form class = "form-horizontal" id = "register-form" action = "register.php" method = "post" novalidate = "novalidate">
					<h2>Registration</h2>
					<div class = "panel panel-primary">
						<div class = "panel-heading clearfix">
							<h4 class="panel-title pull-left" style="padding-top: 7.5px;">Enter your information</h4>
						</div>

						<div class = "panel-body">
							<div class = "input-group margin-bottom-sm">
								<span class = "input-group-addon"><i class = "fa fa-user fa-fw"></i></span>
								<input type = "text" value = "<?php echo $sUsername; ?>" class = "form-control input-lg" id = "register_Username" name = "register_Username" placeholder = "Username" data-toggle = "tooltip" title = "Enter your username here. This is used for logging in on the website. This name is not visible to other people." autofocus>
							</div>

							<div class = "input-group margin-bottom-sm">
								<span class = "input-group-addon"><i class = "fa fa-user fa-fw"></i></span>
								<input type = "text" value = "<?php echo $sProfileName; ?>" class = "form-control input-lg" id = "register_ProfileName" name = "register_ProfileName" placeholder = "Forum name" data-toggle = "tooltip" title = "Enter your forum name here. This name is visible to other people.">
							</div>

							<div class = "row-buffer-10"></div>

							<div class = "input-group margin-bottom-sm">
								<span class = "input-group-addon"><i class = "fa fa-envelope fa-fw"></i></span>
								<input type = "email" value = "<?php echo $sEmail; ?>" class = "form-control input-lg" name = "register_Email" placeholder = "Email address" data-toggle = "tooltip" title = "Enter your email address here. By default this will not be visible to other people.">
							</div>

							<div class = "input-group margin-bottom-sm">
								<span class = "input-group-addon"><i class = "fa fa-envelope fa-fw"></i></span>
								<input type = "email" value = "<?php echo $sEmail_Second; ?>" class = "form-control input-lg" name = "register_Email_Second" placeholder = "Email address (Confirmation)" data-toggle = "tooltip" title = "Enter your email address as confirmation.">
							</div>

							<div class = "row-buffer-10"></div>

							<div class = "input-group margin-bottom-sm">
								<span class = "input-group-addon"><i class = "fa fa-key fa-fw"></i></span>
								<input type = "password" class = "form-control input-lg" name = "register_Password" placeholder = "Password" data-toggle = "tooltip" title = "Enter your password here. Your password will NOT be visible to anyone.">
							</div>

							<div class = "input-group margin-bottom-sm">
								<span class = "input-group-addon"><i class = "fa fa-key fa-fw"></i></span>
								<input type = "password" class = "form-control input-lg" name = "register_Password_Second" placeholder = "Password (confirmation)" data-toggle = "tooltip" title = "Enter your password here as confirmation.">
							</div>

							<div class = "row-buffer-10"></div>
							<span class = "pull-right"><button type = "submit" name = "btnRegister" class = "btn btn-primary">Register</button></span>
						</div>
					</div>
				</form>
			</div>
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
			$('input[type=text]').tooltip({
				placement: "top",
				trigger: "focus"
			});

			$('input[type=password]').tooltip({
				placement: "top",
				trigger: "focus"
			});

			$('input[type=email]').tooltip({
				placement: "top",
				trigger: "focus"
			});
		</script>
	</body>
</html>

<?php
	if($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		//=====================================
		// Registration button has been pressed
		if(isset($_POST['btnRegister']))
		{
			$sError_Message = '';

			if(!strcmp($sUsername, "") || strlen($sUsername) <= 3 || strlen($sUsername) >= 20)
				$sError_Message .= 'You have to enter an username. Make sure it is atleast 3 characters and at most 20 characters. <br />';

			if(!strcmp($sProfileName, "") || strlen($sProfileName) <= 3 || strlen($sUsername) >= 20)
				$sError_Message .= 'You have to enter a forum name. Make sure it is atleast 3 characters and at most 20 characters. <br />';

			if(!strcmp($sEmail, "") || !filter_var($sEmail, FILTER_VALIDATE_EMAIL))
			{
				$sError_Message .= 'You have to enter a valid email address  <br />';
			}
			else
			{
				if(strcmp($sEmail, $sEmail_Second))
				{
					$sError_Message .= 'The email addresses you entered do not match. <br />';
				}
			}

			if(!strcmp($sPassword, "") || strlen($sPassword) <= 3)
			{
				$sError_Message .= 'The password has to be atleast 3 characters. <br />';
			}
			else
			{
				if(strcmp($sPassword, $sPassword_Second))
				{
					$sError_Message .= 'The passwords you entered do not match. <br />';
				}
			}

			/*=====================================
				Laat foutmelding zien of
				registreer de gebruiker
			=====================================*/
			if(strlen($sError_Message) > 0)
			{
				echo '
				<div class="alert alert-danger fade in footer" style = "z-index: 1;" data-dismiss="alert">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
					<h4><b>You entered some wrong details. Please read the errors below. </b></h4>
					<i><span class = "pull-right">Click on this error to hide it.</span></i> <br />

					<p>
					' . $sError_Message . '
					</p>
				</div>
				';
			}
			else
			{
				$sError_Message = '';

				$qUsername = DB::getInstance() -> get('users', array('username', '=', $sUsername));
				if($qUsername -> count() > 0)
					$sError_Message .= 'The username "' . $sUsername . '" is already taken. Please try a different username. <br />';

				$qProfileName = DB::getInstance() -> get('users', array('profilename', '=', $sProfileName));
				if($qProfileName -> count() > 0)
					$sError_Message .= 'The forum name "' . $sProfileName . '" is already taken. Please try a different username. <br />';

				$qEmail = DB::getInstance() -> get('users', array('email', '=', $sEmail));
				if($qEmail -> count() > 0)
					$sError_Message .= 'The email "' . $sEmail . '" is already taken. Please try a different email. ';

				// Username/forum name/email has already been taken
				if(strlen($sError_Message) > 0)
				{
					echo '
						<div class="alert alert-danger fade in footer" style = "z-index: 1;" data-dismiss="alert">
							<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
							<h4><b>You entered some wrong details. Please read the errors below. </b></h4>
							<i><span class = "pull-right">Click on this error to hide it.</span></i> <br />

							<p>
							' . $sError_Message . '
							</p>
						</div>
						';
				}
				// Register the user
				else
				{
					$sRandomHash 		= hash("md5", Functions::generate_uniqueID(5));
					$sHashedPassword 	= hash("whirlpool", $sPassword . $sRandomHash);
					$dtRegisterDate		= date("Y-m-d");
					$dtRegisterTime		= date("H:i:s");

					$query = DB::getInstance() -> insert('users', array(
							"username" => $sUsername,
							"profilename" => $sProfileName,
							"last_profilename" => $sProfileName,
							"password" => $sHashedPassword,
							"salt" => $sRandomHash,
							"register_date" => $dtRegisterDate,
							"register_datetime" => $dtRegisterTime,
							"email" => $sEmail,
							"cookie" => "NOT_SET"
						));

					echo '<script type = "text/javascript">alert("Your account has been succesfully created. ");</script>';
					echo '<script type = "text/javascript">window.location.href = "index.php";</script>';
				}
			}
		}
	}
?>
