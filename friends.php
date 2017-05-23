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

	$iPermission = 0;

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

	$qUserExist = DB::getInstance() -> query('SELECT userid FROM users WHERE userid = ?', array($_GET['u']));
	if($qUserExist -> count() > 0)
	{
		$qCurUser = DB::getInstance() -> query('SELECT profilename FROM users WHERE userid = ?', array($_GET['u']));
		$sCurUserProfile = $qCurUser -> first() -> profilename;
	}
	else
		$sCurUserProfile = "N/A";
?>

<html>
	<head>
		<title>Friends - <?php echo $sCurUserProfile; ?></title>

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

		<!--===============
			No javascript warning message
		================-->
		<noscript>
			<div class = "no-js">
				Important parts of this website only work with JavaScript. Please enable JavaScript in your browser options.
			</div>
		</noscript>

		<div class = "container extra-padding-top">
			<?php
				$qUserExist = DB::getInstance() -> query('SELECT userid FROM users WHERE userid = ?', array($_GET['u']));
				if($qUserExist -> count() > 0)
				{
					echo '<table cellpadding = "4" cellspacing = "1" width = "100%" align = "center">
		                <thead>
		                    <tr class = "border">
		                        <td class = "groupname" colspan = "4"><b style = "color: white;">All friends from "' . $sCurUserProfile . '"</b></td>
		                    </tr>

		                    <tr class = "tophead" align = "center">
		                        <td class = "border" width = "50%" style = "padding: 4px" align = "left"><b><span class = "white-text">Forum name</span></b></td>
		                        <td class = "border" width = "40" style = "padding: 4px"><b><span class = "white-text">Profile picture</span></b></td>
		                        <td class = "border" width = "15%" style = "padding: 4px" align = "left"><b><span class = "white-text">Status</span></b></td>
		                        <td class = "border" width = "16" style = "padding: 4px" align = "left"><b><span class = "white-text">Revoke friendship</span></b></td>
		                    </tr>
		                </thead>

		                <tbody>';
	                		$qFriends = DB::getInstance() -> query('SELECT friend_userid FROM friends WHERE userid = ?', array($_GET['u']));

	                		foreach($qFriends -> results() as $oFriend)
	                		{
	                			$qUserInfo = DB::getInstance() -> query('SELECT userid, username, profilename FROM users WHERE userid = ?', array($oFriend -> friend_userid));
	                			$iTargetID			= $qUserInfo -> first() -> userid;
	                			$sTargetUsername	= $qUserInfo -> first() -> username;
	                			$sTargetProfile		= $qUserInfo -> first() -> profilename;

	                			foreach(Config::get('config/allowed_extensions/') as $extension)
		                        {
		                            if(file_exists('resources/images/profile_pictures/' . $sTargetUsername . '.' . $extension))
		                            {
		                                $finalImageUrl = 'resources/images/profile_pictures/' . $sTargetUsername . '.' . $extension;
		                                break;
		                            }
		                            else
		                            {
		                                $finalImageUrl = 'resources/images/profile_pictures/unknown.jpg';
		                            }
		                        }

		                        if(file_exists($finalImageUrl))
		                        {
		                            $sProfilePicture = $finalImageUrl;
		                        }

		                        $qFriendCheck = DB::getInstance() -> query('SELECT friend_userid FROM friends WHERE userid = ? AND friend_userid = ?', array($_GET['u'], $iTargetID));
		                        $iCurrentUserFriends = $qFriendCheck -> count() > 0 ? 1 : 0;

		                        $qFriendCheck = DB::getInstance() -> query('SELECT friend_userid FROM friends WHERE userid = ? AND friend_userid = ?', array($iTargetID, $_GET['u']));
		                        $iTargetUserFriends = $qFriendCheck -> count() > 0 ? 1 : 0;

	                			echo '<tr>
	                				<td class = "postinfo border"><a href = "member.php?u=' . $iTargetID . '" class = "blue-text">' . $sTargetProfile . '</a></td>
	                				<td class = "postinfo border" align = "center"><img src = "' . $finalImageUrl  . '" width = "40" height = "40" /></td>
	                				<td class = "postinfo border" align = "center">';
	                					if($iCurrentUserFriends == 1 && $iTargetUserFriends == 1)
			                            {
			                                echo '<div class = "friend-mutual">
			                                        <center><i class = "fa fa-heart"></i> <b>Mutual friend</b></center>
			                                    </div>';
			                            }
			                            else if($iCurrentUserFriends == 1)
			                            {
			                                echo '<div class = "friend-non-mutual">
	                                                <center><i class = "fa fa-star"></i> <b>Friend</b></center>
	                                            </div>';
			                            }

	                				echo '</td>

	                				<td class = "postinfo border" align = "center">
	                					<a href = "member.php?u=' . $iTargetID . '&a=removefriend">
	                						<span class = "fa-stack fa-lg">
	                                            <i class = "fa fa-square fa-stack-2x" style = "color: red;"></i>
	                                            <i class = "fa fa-times fa-stack-1x fa-inverse"></i>
	                                        </span>
	                					</a>
	                				</td>
	                			</tr>';
	                		}
		                echo '</tbody>
		            </table>';
				}
				else
				{
					echo '
	                    <table cellpadding = "2" cellspacing = "1" width = "50%" align = "center" class = "textarea_mobile">
                                <thead>
                                    <tr class = "border">
                                        <td class = "groupname" colspan = "2">
                                            <b class = "white-text">System message</b>
                                        </td>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr align = "center">
                                       <td class = "postinfo extra-padding" style = "font-size: 16px;">This user was not found. Please try again</td>
                                    </tr>
                                </tbody>
                            </table> <div class = "row-buffer-3"></div>';
				}
			?>
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
	</body>
</html>
