<!DOCTYPE html>
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
?>

<html>
	<head>
		<title>Homepage</title>

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
				<a href = "index.php" class = "navbar-brand"><?php echo Config::get('config/forum_name/'); ?></a>

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
				// Checking if user has permissions to create new boards and is logged in
				if($iPermission >= 2 && $iLoggedIn == 1)
				{
					echo '<a href = "create_elements.php" class = "btn btn-info smaller-text">Create a new board</a> <button type = "button" id = "toggle-delete" class = "btn btn-info smaller-text">Manage elements</button> <div class = "row-buffer-3"></div>';
				}
			?>

			<table cellpadding = "7" cellspacing = "1" width = "100%" align = "center">
				<thead>
					<tr class = "tophead">
						<td class = "border theadpic remove_mobile"></td>
						<td class = "border" width = "78%" style = "padding: 4px"><span class = "white-text">Forum</span></td>
						<td class = "border" style = "padding: 4px"><span class = "white-text">Last Post</span></td>
						<td class = "border theadsquare remove_mobile" style = "padding: 4px"><span class = "white-text">Threads</span></td>
						<td class = "border theadsquare remove_mobile" style = "padding: 4px"><span class = "white-text">Posts</span></td>
					</tr>
				</thead>

				<?php
					$qForum = DB::getInstance() -> query('SELECT * FROM forum');

					// Looping through all forums, showing it in a table
					foreach($qForum -> results() as $sForum)
					{
						if($iPermission < $sForum -> permission_required)
							continue;

						echo '
							<tbody>
								<tr class = "border">
									<td class = "groupname" colspan = "6">
										<b style = "color: white;">' . $sForum -> forumname . '</b>';

										if($iPermission >= 2)
										{
											echo '<a href = "delete_elements.php?delete=c&c=' . $sForum -> forumid . '" class = "pull-right" style = "padding-right: 4px;"><img src = "resources/images/delete.png" class = "delete-icon"/></a><a href = "editelement.php?c=' . $sForum -> forumid . '" class = "pull-right deletebutton" style = "padding-right: 4px;"><img src = "resources/images/edit.png" class = "delete-icon" /></a>';
										}
									echo '
									</td>
								</tr>
							</tbody>';

						// query for all subforums
						$qSubForum = DB::getInstance() -> query('SELECT * FROM subforum WHERE forumid = ?', array($sForum -> forumid));

						// looping through the query
						foreach($qSubForum -> results() as $sSubForum)
						{
							// getting the total amount of threads created in this subforum
							$qTotalThreads = DB::getInstance() -> query('SELECT count(*) AS totalthreads FROM threads WHERE subforumid = ?', array($sSubForum -> subforumid));
							$iTotalThreads = $qTotalThreads -> first() -> totalthreads;

							// looping through all threads from this subforum, counting all posts
							$qAllThreads = DB::getInstance() -> query('SELECT threadid FROM threads WHERE subforumid = ?', array($sSubForum -> subforumid));

							$iTotalPosts = 0;
							foreach($qAllThreads -> results() as $iThreadIDS)
							{
								$qPostsPerThread = DB::getInstance() -> query('SELECT count(*) AS postperthread FROM posts WHERE threadid = ?', array($iThreadIDS -> threadid));
								$iTotalPosts += $qPostsPerThread -> first() -> postperthread;
							}

							// looping through all threads from this subforum
							$qAllThreads = DB::getInstance() -> query('SELECT threadid FROM threads WHERE subforumid = ?', array($sSubForum -> subforumid));

							// (re)setting variables
							$dtRecentDate = "0000-00-00";
							$dtRecentTime = "00:00:00";
							$dtRecentUserid = 0;
							$dtRecentTitle = "";
							$dtRecentProfileName = "N/A";
							$dtRecentThreadID = "N/A";

							// see if there is a thread
							if($qAllThreads -> count() > 0)
							{
								// loop through all available threads
								foreach($qAllThreads -> results() as $iThreadIDS)
								{
									// look for last posted thread
									$qLastPostInThread = DB::getInstance() -> query('SELECT postid, userid, date, time, title FROM `posts` WHERE threadid = ? ORDER BY `date` DESC,`time` DESC', array($iThreadIDS -> threadid));

									if($qLastPostInThread -> count() > 0)
									{
										// setting the variables if conditions have been met
										if($qLastPostInThread -> first() -> date > $dtRecentDate)
										{
											$iPostid 			= $qLastPostInThread -> first() -> postid;
											$dtRecentDate 		= $qLastPostInThread -> first() -> date;
											$dtRecentTime 		= $qLastPostInThread -> first() -> time;
											$dtRecentUserid 	= $qLastPostInThread -> first() -> userid;
											$dtRecentTitle 		= $qLastPostInThread -> first() -> title;
											$dtRecentThreadID 	= $iThreadIDS -> threadid;
											continue;
										}

										if(!strcmp($qLastPostInThread -> first() -> date, $dtRecentDate))
										{
											if(strtotime($qLastPostInThread -> first() -> time) > strtotime($dtRecentTime))
											{
												$dtRecentDate 		= $qLastPostInThread -> first() -> date;
												$dtRecentTime 		= $qLastPostInThread -> first() -> time;
												$dtRecentUserid 	= $qLastPostInThread -> first() -> userid;
												$dtRecentTitle 		= $qLastPostInThread -> first() -> title;
												$dtRecentThreadID 	= $iThreadIDS -> threadid;
											}
										}
									}
									else
									{
										$iPostid = "N/A";
										$dtRecentDate = "N/A";
										$dtRecentTime = "";
										$dtRecentUserid = "N/A";
										$dtRecentTitle = "N/A";
										$dtRecentThreadID = "N/A";
									}
								}
							}
							else
							{
								$iPostid = "N/A";
								$dtRecentDate = "N/A";
								$dtRecentTime = "";
								$dtRecentUserid = "N/A";
								$dtRecentTitle = "N/A";
								$dtRecentThreadID = "N/A";
							}

							// if recentid isn't n/a get the profile name
							if(strcmp($dtRecentUserid, "N/A"))
							{
								$qProfilename = DB::getInstance() -> query('SELECT profilename FROM users WHERE userid = ?', array($dtRecentUserid));
								$dtRecentProfileName = $qProfilename -> first() -> profilename;
							}

							echo '
								<tbody>
									<tr align = "center">
										<td class = "tdtest remove_mobile" style = "background-color: #EAEAEB;"><img src = "resources/images/thread-images/forum_old.png" /></td>

										<td class = "tdtest" align = "left">
											<div>
												<a href = "forumdisplay.php?f=' . $sSubForum -> subforumid . '" class = "big-text blue-text">' . $sSubForum -> subforumname . '</a>';
												if($iPermission >= 2)
												{
													echo '<a href = "delete_elements.php?delete=s&s=' . $sSubForum -> subforumid . '" class = "pull-right deletebutton" style = "padding-right: 4px;"><img src = "resources/images/delete.png" class = "delete-icon" /></a><a href = "editelement.php?f=' . $sSubForum -> subforumid . '" class = "pull-right deletebutton" style = "padding-right: 4px;"><img src = "resources/images/edit.png" class = "delete-icon" /></a>';
												}
											echo '
											</div>

											<div class = "small-text">' . $sSubForum -> subforumdescription . '</div>
										</td>

										<td class = "tdtest" align = "left">
											<div><a href = "showthread.php?t=' . $dtRecentThreadID . '&p=' . $iPostid . '" class = "blue-text">' . $dtRecentTitle . '</a></div>
											<div>by <a href = "member.php?u=' . $dtRecentUserid .'" class = "blue-text">' . $dtRecentProfileName . '</a></div>
											<div align = "right">' . $dtRecentDate . '&nbsp;' . $dtRecentTime . '</div>
										</td>

										<td class = "tdtest remove_mobile">' . $iTotalThreads . '</td>
										<td class = "tdtest remove_mobile">' . $iTotalPosts . '</td>
									</tr>
								</tbody>';
						}
					}
				?>
			</table>

			<div class = "row-buffer-10"></div>
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
				var iVisible = 0;

				$("#toggle-delete").click(function(){
					if(iVisible == 0)
					{
						$(".delete-icon").show();
						iVisible = 1;
					}
					else
					{
						$(".delete-icon").hide();
						iVisible = 0;
					}
				});
			});
		</script>
	</body>
</html>
