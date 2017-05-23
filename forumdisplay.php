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
?>

<html>
	<head>
		<title>Forum</title>

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
				// checking if forumid is set
				if(isset($_GET['f']))
				{
					$iGivenForum = $_GET['f'];

					// making sure forumid is correct
					$qSubForum = DB::getInstance() -> query('SELECT forumid, subforumid, subforumname FROM subforum WHERE subforumid = ?', array($iGivenForum));

					if($qSubForum -> count() > 0)
					{
						$iSubForumForumId = $qSubForum -> first() -> forumid;
						$iSubForumSubForumId = $qSubForum -> first() -> subforumid;
						$sSubForumSubForumName = $qSubForum -> first() -> subforumname;

						echo '<script>document.title = "' . $sSubForumSubForumName . '";</script>';

						$qPermissonCheck = DB::getInstance() -> query('SELECT permission_required FROM forum WHERE forumid = ?', array($iSubForumForumId));
						if($iPermission < $qPermissonCheck -> first() -> permission_required)
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
		                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">Your permission is not high enough to visit this forum.</td>
		                                </tr>
		                            </tbody>
		                        </table>';
						}
						else
						{
							if($iLoggedIn == 1)
							{
								echo '<a href = "newthread.php?do=newthread&f=' . $iGivenForum . '" class = "btn btn-info smaller-text">New thread</a> ';

								if($iPermission >= 2)
								{
									echo '<button type = "button" id = "toggle-delete" class = "btn btn-info smaller-text">Manage elements</button> <div class = "row-buffer-3"></div>';
								}
							}

							$iCurPage = isset($_GET['page']) ? $_GET['page'] : '1';

							$qTotalThreads = DB::getInstance() -> query('SELECT count(*) AS totalthreads FROM threads WHERE subforumid = ?', array($_GET['f']));
							$iTotalPosts = ceil($qTotalThreads -> first() -> totalthreads / 10);

							$iPrevPage = ($iCurPage - 1);
								if($iPrevPage <= 0) $iPrevPage = 1;

							$iNextPage = ($iCurPage + 1);
								if($iNextPage >= ($iTotalPosts + 1)) $iNextPage = $iTotalPosts;

							// pagination
							echo '
								<ol class = "breadcrumb">
									<li><a href = "index.php" style = "font-size: 16px;">' . Config::get('config/forum_name/name') . '</a></li>
									<li class = "active">' . $sSubForumSubForumName. '</a>
								</ol>';

							echo '
								<table cellpadding = "6" cellspacing = "1" width = "100%" align = "center">
									<thead>
										<tr class = "border">
											<td class = "groupname" colspan = "7">
												<b style = "color: white;">Threads found in forum: ' . $sSubForumSubForumName . '</b>
											</td>
										</tr>
									</thead>

									<thead>
										<tr class = "tophead">
											<td class = "border theadpic remove_mobile"></td>
											<td class = "border theadpic remove_mobile"></td>
											<td class = "border" width = "78%" style = "padding: 4px"><span class = "white-text">Thread/Thread starter</span></td>
											<!--<td class = "border theadsquare" style = "padding: 4px"><span class = "white-text">Rating</span></td>-->
											<td class = "border" style = "padding: 4px"><span class = "white-text">Last Post</span></td>
											<td class = "border theadsquare remove_mobile" style = "padding: 4px"><span class = "white-text">Replies</span></td>
											<td class = "border theadsquare remove_mobile" style = "padding: 4px"><span class = "white-text">Views</span></td>
										</tr>
									</thead>';


							//===========================================
							// STICKY THREADS
							//===========================================
							$qThreads = DB::getInstance() -> query('SELECT threadid, userid, title, sticky, views, likes, dislikes, icon FROM threads WHERE subforumid = ? AND sticky = 1 ORDER BY lastpost DESC', array($iGivenForum));

							foreach($qThreads -> results() as $oThreads)
							{
								// retrieving variables for the threads in this subforum
								$qThreadStarter = DB::getInstance() -> query('SELECT profilename FROM users WHERE userid = ?', array($oThreads -> userid));
								$sThreadStarterProfilename = $qThreadStarter -> first() -> profilename;

								$qPostInfo = DB::getInstance() -> query('SELECT date, time, userid FROM posts WHERE threadid = ? ORDER BY date DESC, time DESC', array($oThreads -> threadid));

								$dbLPDate = $qPostInfo -> first() -> date;
								$dbLPTime = $qPostInfo -> first() -> time;
								$dbLPUserid = $qPostInfo -> first() -> userid;

								$qTotalPosts = DB::getInstance() -> query('SELECT count(*) AS totalposts FROM posts WHERE threadid = ?', array($oThreads -> threadid));
								$dbLPTotalPosts = $qTotalPosts -> first() -> totalposts;

								$qUsername = DB::getInstance() -> query('SELECT profilename FROM users WHERE userid = ?', array($dbLPUserid));
								$dbLastPostProfilename = $qUsername -> first() -> profilename;

								echo '
									<tbody>
										<tr align = "center">
											<td class = "tdtest remove_mobile" style = "background-color: #EAEAEB !important;"><img src = "resources/images/thread-images/pin.png" /></td>
											<td class = "tdtest remove_mobile" style = "background-color: #EAEAEB !important;"><img src = "resources/images/thread-images/' . $oThreads -> icon . '" /></td>

											<td class = "tdtest" align = "left">
												<div>
													<a href = "showthread.php?t=' . $oThreads -> threadid . '" class = "big-text blue-text">' . $oThreads -> title . '</a>';

													if($iPermission >= 1)
													{
														echo '<a href = "delete_elements.php?delete=t&t=' . $oThreads -> threadid . '" class = "pull-right" style = "padding-right: 4px; padding-top: 4px;"><img src = "resources/images/delete.png" class = "delete-icon"/></a><a href = "editelement.php?t=' . $oThreads -> threadid . '" class = "pull-right deletebutton" style = "padding-right: 4px; padding-top: 4px;"><img src = "resources/images/edit.png" class = "delete-icon" /></a>';
													}

												echo '
												</div>

												<div><a href = "member.php?u=' . $oThreads -> userid . '" class = "small-text no-underline blue-text">' . $sThreadStarterProfilename . '</a></div>
											</td>

											<!--<td class = "tdtest">NEEDFIX</td>-->

											<td class = "tdtest" align = "left">
												<div>' . $dbLPDate . ',&nbsp<span style = "color: blue;">' . $dbLPTime . '</span></div>
												<div align = "right">by <a href = "member.php?u=' . $dbLPUserid . '" class = "blue-text">' . $dbLastPostProfilename . '</a></div>
											</td>

											<td class = "tdtest remove_mobile">' . $dbLPTotalPosts . '</td>
											<td class = "tdtest remove_mobile">' . $oThreads -> views . '</td>
										</tr>
									</tbody>';
							}

							if($qThreads -> count() > 0)
							{
								echo '
									<tbody>
										<tr class = "border">
											<td class = "groupname" colspan = "6">

											</td>
										</tr>
									</tbody>';
							}

							//===========================================
							// NON-STICKY THREADS
							//===========================================
							// getting thread information
							$qThreads = DB::getInstance() -> query('SELECT threadid, userid, title, sticky, views, likes, dislikes, locked, icon FROM threads WHERE subforumid = ? AND sticky = 0 ORDER BY lastpost DESC', array($iGivenForum));

							$iThreadCount = 1;
							foreach($qThreads -> results() as $oThreads)
							{
								if($iCurPage == 1)
								{
									if($iThreadCount >= $iCurPage && $iThreadCount <= ($iCurPage * 10))
									{
									}
									else
									{
										break;
									}
								}
								else if($iCurPage > 1)
								{
									if($iThreadCount <= ($iCurPage * 10) - 10)
									{
										$iThreadCount ++;
										continue;
									}

									if($iThreadCount >= ($iCurPage * 10) - 10 && $iThreadCount <= ($iCurPage * 10))
									{
									}
									else
									{
										break;
									}
								}

								// retrieving variables for the threads in this subforum
								$qThreadStarter = DB::getInstance() -> query('SELECT profilename FROM users WHERE userid = ?', array($oThreads -> userid));
								$sThreadStarterProfilename = $qThreadStarter -> first() -> profilename;

								$qPostInfo = DB::getInstance() -> query('SELECT date, time, userid FROM posts WHERE threadid = ? ORDER BY date DESC, time DESC', array($oThreads -> threadid));

								$dbLPDate = $qPostInfo -> first() -> date;
								$dbLPTime = $qPostInfo -> first() -> time;
								$dbLPUserid = $qPostInfo -> first() -> userid;

								$qTotalPosts = DB::getInstance() -> query('SELECT count(*) AS totalposts FROM posts WHERE threadid = ?', array($oThreads -> threadid));
								$dbLPTotalPosts = $qTotalPosts -> first() -> totalposts;

								$qUsername = DB::getInstance() -> query('SELECT profilename FROM users WHERE userid = ?', array($dbLPUserid));
								$dbLastPostProfilename = $qUsername -> first() -> profilename;

								echo '
									<tbody>
										<tr align = "center">';

											if($oThreads -> locked == 1)
											{
												echo '<td class = "tdtest remove_mobile" style = "background-color: #EAEAEB !important;"><i class="fa fa-lock fa-3x" ></i></td>';
											}
											else
											{
												echo '<td class = "tdtest remove_mobile" style = "background-color: #EAEAEB !important;"><img src = "resources/images/blank.png" /></td>';
											}

											echo '<td class = "tdtest remove_mobile" style = "background-color: #EAEAEB !important;"><img src = "resources/images/thread-images/' . $oThreads -> icon . '" /></td>

											<td class = "tdtest" align = "left">
												<div>
													<a href = "showthread.php?t=' . $oThreads -> threadid . '" class = "big-text blue-text">' . $oThreads -> title . '</a>';

													if($iPermission >= 1)
													{
														echo '<a href = "delete_elements.php?delete=t&t=' . $oThreads -> threadid . '" class = "pull-right" style = "padding-right: 4px; padding-top: 4px;"><img src = "resources/images/delete.png" class = "delete-icon"/></a><a href = "editelement.php?t=' . $oThreads -> threadid . '" class = "pull-right deletebutton" style = "padding-right: 4px; padding-top: 4px;"><img src = "resources/images/edit.png" class = "delete-icon" /></a>';
													}

												echo '
												</div>

												<div><a href = "member.php?u=' . $oThreads -> userid . '" class = "small-text no-underline blue-text">' . $sThreadStarterProfilename . '</a></div>
											</td>

											<!--<td class = "tdtest">NEEDFIX</td>-->

											<td class = "tdtest" align = "left">
												<div>' . $dbLPDate . ',&nbsp<span style = "color: blue;">' . $dbLPTime . '</span></div>
												<div align = "right">by <a href = "member.php?u=' . $dbLPUserid . '" class = "blue-text">' . $dbLastPostProfilename . '</a></div>
											</td>

											<td class = "tdtest remove_mobile">' . $dbLPTotalPosts . '</td>
											<td class = "tdtest remove_mobile">' . $oThreads -> views . '</td>
										</tr>
									</tbody>';

								$iThreadCount ++;
							}

							echo '</table>';
						}
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
                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">Invalid forum specified. Please try again</td>
                                </tr>
                            </tbody>
                        </table>';
					}
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
                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">Invalid forum specified. Please try again</td>
                                </tr>
                            </tbody>
                        </table>';
				}

				echo '<nav class = "pull-right">
					<ul class = "pagination">
                        <li>
                            <a href = "forumdisplay.php?f=' . $_GET['f'] . '&page=1">
                                <span aria-hidden = "true">&laquo;</span>
                            </a>
                        </li>

                        <li>
                            <a href = "forumdisplay.php?f=' . $_GET['f'] . '&page=' . $iPrevPage . '" aria-label = "Previous">
                                <span aria-hidden = "true">&lsaquo;</span>
                            </a>
                        </li>';

						if($iTotalPosts > 5)
                        {
                        	for($i = 1; $i <= 5; $i ++)
                            {
                                if($iCurPage == $i)
                                {
                                    echo '<li class = "active"><a href = "forumdisplay.php?page=' . $i . '">' . $i . '</a></li>';
                                }
                                else
                                {
                                    echo '<li><a href = "forumdisplay.php?page=' . $i . '">' . $i . '</a></li>';
                                }
                            }

                            if($iCurPage > 6)
                            {
                                echo '<li class = "active"><a href = "forumdisplay.php?page=' . $iCurPage . '">' . $iCurPage . '</a></li>';
                            }
                        }
                        else
                        {
                    		for($i = 1; $i <= $iTotalPosts; $i ++)
							{
								if($iCurPage == $i)
								{
									echo '<li class = "active"><a href = "forumdisplay.php?f=' . $_GET['f'] . '&page=' . $i . '">' . $i . '</a></li>';
								}
								else
								{
									echo '<li><a href = "forumdisplay.php?f=' . $_GET['f'] . '&page=' . $i . '">' . $i . '</a></li>';
								}
							}
                        }

						echo '<li>
                            <a href = "forumdisplay.php?f=' . $_GET['f'] . '&page=' . $iNextPage . '" aria-label = "Next">
                                <span aria-hidden = "true">&rsaquo;</span>
                            </a>
                        </li>

                        <li>
                            <a href = "forumdisplay.php?f=' . $_GET['f'] . '&page=' . $iTotalPosts . '">&raquo;</a>
                        </li>
					</ul>
				</nav>';
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

		<script type = "text/javascript">
			$(document).ready(function() {
				var iVisible = 0;
				$(".delete-icon").hide();

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
