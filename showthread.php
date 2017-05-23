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

	if(isset($_GET['action']))
	{
		$gAction = ($_GET['action']) ? $_GET['action'] : '';

		if($iPermission >= 1)
		{
			if(!strcmp($gAction, 'lock'))
			{
				$qLock = DB::getInstance() -> query('SELECT locked FROM threads WHERE threadid = ?', array($_GET['t']));

				if($qLock -> count() > 0)
				{
					if($qLock -> first() -> locked == 0)
					{
						$qUpdateLock = DB::getInstance() -> update('threads', array(
																'locked' => '1'),
															array(
																'threadid', '=', $_GET['t']
																));
						Header('Location: showthread.php?t=' . $_GET['t']);
					}
				}
			}
			else if(!strcmp($gAction, 'unlock'))
			{
				$qLock = DB::getInstance() -> query('SELECT locked FROM threads WHERE threadid = ?', array($_GET['t']));

				if($qLock -> count() > 0)
				{
					if($qLock -> first() -> locked == 1)
					{
						$qUpdateLock = DB::getInstance() -> update('threads', array(
																'locked' => '0'),
															array(
																'threadid', '=', $_GET['t']
																));
						Header('Location: showthread.php?t=' . $_GET['t']);
					}
				}
			}
		}
	}
?>

<html>
	<head>

		<title>Thread</title>

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
				// check if thread is set
				if(isset($_GET['t']))
				{
					$iGivenThread = $_GET['t'];

					$qThreadPosts = DB::getInstance() -> query('SELECT title FROM posts WHERE threadid = ?', array($iGivenThread));

					// checking if threadid is valid
					if($qThreadPosts -> count() > 0)
					{
						$sThreadPostsTitle = $qThreadPosts -> first() -> title;

						echo '<script>document.title = "' . $sThreadPostsTitle . '";</script>';

						$qThreadID = DB::getInstance() -> query('SELECT subforumid FROM threads WHERE threadid = ?', array($iGivenThread));
						$iSubforumID = $qThreadID -> first() -> subforumid;
						$qSubForumName = DB::getInstance() -> query('SELECT subforumname FROM subforum WHERE subforumid = ?', array($iSubforumID));
						$sSubForumName = $qSubForumName -> first() -> subforumname;

						$qForumPermission = DB::getInstance() -> query('SELECT forumid FROM subforum WHERE subforumid = ?', array($iSubforumID));
						$iForumID = $qForumPermission -> first() -> forumid;
						$qForumPermission = DB::getInstance() -> query('SELECT permission_required FROM forum WHERE forumid = ?', array($iForumID));

						if($iPermission < $qForumPermission -> first() -> permission_required)
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
							// breadcrumbs
							echo '
								<ol class = "breadcrumb">
									<li><a href = "index.php" style = "font-size: 16px;">' . Config::get('config/forum_name/name') . '</a></li>
									<li><a href = "forumdisplay.php?f=' . $iSubforumID . '">' . $sSubForumName . '</a></li>
									<li class = "active">' . $sThreadPostsTitle . '</li>
								</ol>';

							// get views and adding one
							$qCurrentThread = DB::getInstance() -> query('SELECT views, icon FROM threads WHERE threadid = ?', array($iGivenThread));
							$iCurrentViews = $qCurrentThread -> first() -> views += 1;
							$sThreadIcon = $qCurrentThread -> first() -> icon;
							$qUpdateViews = DB::getInstance() -> update('threads', array(
														'views' => $iCurrentViews
													), array(
														'threadid', '=', $iGivenThread
													));

							$qTotalPosts = DB::getInstance() -> query('SELECT count(*) AS totalposts FROM posts WHERE threadid = ?', array($_GET['t']));
							$iTotalPosts = ceil($qTotalPosts -> first() -> totalposts / 10);

							if($iPermission >= 1)
							{
								$qLock = DB::getInstance() -> query('SELECT locked FROM threads WHERE threadid = ?', array($_GET['t']));

								if($qLock -> first() -> locked == 0)
								{
									echo '<a href = "showthread.php?t=' . $_GET['t'] . '&action=lock" class = "btn btn-primary">Lock this thread <i class = "fa fa-lock"></i></a> <div class = "row-buffer-3"></div>';
								}
								else
								{
									echo '<a href = "showthread.php?t=' . $_GET['t'] . '&action=unlock" class = "btn btn-primary">Unlock this thread <i class="fa fa-unlock"></i></a> <div class = "row-buffer-3"></div>';
								}
							}

							echo '<table cellpadding = "2" cellspacing = "1" width = "100%" align = "center">';

							// NEED BETTER SOLUTION FOR THIS
							$qThreadPosts = DB::getInstance() -> query('SELECT postid, userid, opening_post, date, time, title, body, lastedit_userid, lastedit_date, lastedit_time, lastedit_message FROM posts WHERE threadid = ?', array($iGivenThread));

							// looping through all thread posts

							$iCurrentPost = 1; // counting posts, 10 per page
							$iCurrentPage = isset($_GET['page']) ? $_GET['page'] : '1';

							foreach($qThreadPosts -> results() as $oPosts)
							{
								if($iCurrentPage == 1)
								{
									if($iCurrentPost >= $iCurrentPage && $iCurrentPost <= ($iCurrentPage * 10))
									{

									}
									else
									{
										break;
									}
								}
								else if($iCurrentPage > 1)
								{
									if($iCurrentPost <= ($iCurrentPage * 10) - 10)
									{
										$iCurrentPost ++;
										continue;
									}

									if($iCurrentPost >= ($iCurrentPage * 10) - 10 && $iCurrentPost <= ($iCurrentPage * 10))
									{

									}
									else
									{
										break;
									}
								}

								$qUserdata 		= DB::getInstance() -> query('SELECT userid, username, signature, profilename, userdescription, register_date, permission FROM users WHERE userid = ?', array($oPosts -> userid));

								$sSignature = $qUserdata -> first() -> signature;

								$sConfigString = 'config/color_codes/' . $qUserdata -> first() -> permission;
								$iProfileNameColour = Config::get($sConfigString);

								echo '
								<thead id = "' . $oPosts -> postid . '">
									<tr class = "border">
										<td class = "groupname" colspan = "2">
											<b style = "color: white;">' . $oPosts -> date . ', ' . $oPosts -> time . '</b>
										</td>
									</tr>
								</thead>

								<tbody>
									<tr valign = "top">
										<td width = "12%" class = "border userinfo">
											<div class = "big-text"><a href = "member.php?u=' . $qUserdata -> first() -> userid . '" style = "color: ' . $iProfileNameColour . ';" class = "no-underline">' . $qUserdata -> first() -> profilename . '</a></div>
											<div><b><i>' . $qUserdata -> first() -> userdescription . '</i></b></div>';

											$finalImgUrl = 'resources/images/profile_pictures/unknown.jpg';

											foreach(Config::get('config/allowed_extensions/') as $extension)
											{
												if(file_exists('resources/images/profile_pictures/' . $qUserdata -> first() -> username . '.' . $extension))
												{
													$finalImgUrl = 'resources/images/profile_pictures/' . $qUserdata -> first() -> username . '.' . $extension;
													break;
												}
												else
												{
													$finalImgUrl = 'resources/images/profile_pictures/unknown.jpg';
												}
											}

											if(file_exists($finalImgUrl))
											{
												echo '<img src = "' . $finalImgUrl . '" width = "128px" height = "128px" />';
											}

											echo '<div class = "row-buffer-3"></div>
											<div>Join date: ' . $qUserdata -> first() -> register_date . '</div>';

											$qAmountOfPosts = DB::getInstance() -> query('SELECT count(*) AS totalposts FROM posts WHERE userid = ?', array($oPosts -> userid));

											echo '
											<div>Posts: ' . $qAmountOfPosts -> first() -> totalposts . '</div>
											<div>Reputation: N/A</div>
										</td>

										<td class = "border postinfo">
											<div class = "small-text">
												<img src = "resources/images/thread-images/' . $sThreadIcon . '" width = "20px" height = "20px" /> <strong>' . $oPosts -> title . '</strong>';

												if($iLoggedIn == 1)
												{
													if($oPosts -> userid == $dbUserid || $iPermission >= 1)
													{
														echo '
														<div class = "pull-right">
															<a href = "editelement.php?p=' . $oPosts -> postid . '"><img src = "resources/images/edit.png" /></a>';

															if($iPermission >= 1 && $oPosts -> opening_post == 0)
															{
																echo '&nbsp;<a href = "delete_elements.php?delete=p&p=' . $oPosts -> postid . '"><img src = "resources/images/delete.png" /></a>';
															}

														echo '
														</div>';
													}
												}

												echo '
												<hr size = "1" style = "color: #DFDFDF; background-color: #DFDFDF">';

												$sPostBody = $oPosts -> body;

												if(isset($_GET['hl']))
												{
													$sPostBody = str_replace($_GET['hl'], '[hl]' . $_GET['hl'] . '[/hl]', $sPostBody);
												}

												echo Functions::bb_parse(nl2br(htmlentities($sPostBody)));

												echo '<div class = "small-font">
													<hr size = "1" style = "color: black;">
														' . $sSignature . '
													</hr>
												</div>';

												if(strcmp($oPosts -> lastedit_userid, "0") && strcmp($oPosts -> lastedit_date, "0000-00-00") && strcmp($oPosts -> lastedit_time, "00:00:00"))
												{
													$qLastEditUsername = DB::getInstance() -> query('SELECT profilename, permission FROM users WHERE userid = ?', array($oPosts -> lastedit_userid));

													$sConfigString = 'config/color_codes/' . $qLastEditUsername -> first() -> permission;
													$iEditColour = Config::get($sConfigString);

													echo '
													<div class = "small-font">
														<hr size = "1" style = "color: #DFDFDF; background-color: #DFDFDF">
														Last edited by <b style = "color: ' . $iEditColour . ';">' . $qLastEditUsername -> first() -> profilename . '</b>. ' . $oPosts -> lastedit_date . ' at ' . $oPosts -> lastedit_time . ' with the following message: <br />
														<u>' . $oPosts -> lastedit_message . '</u>
													</div>';
												}
											echo '
											</div>
										</td>
									</tr>
								</tbody>';

								$iCurrentPost ++;
							}

							echo '</table><div class = "row-buffer-2"></div>';

							$iCurPage = isset($_GET['page']) ? $_GET['page'] : '1';

							$iPrevPage = ($iCurPage - 1);
								if($iPrevPage <= 0) $iPrevPage = 1;

							$iNextPage = ($iCurPage + 1);
								if($iNextPage >= ($iTotalPosts + 1)) $iNextPage = $iTotalPosts;

							echo '<nav class = "pull-right">
								<ul class = "pagination">
									<li>
										<a href = "showthread.php?t=' . $_GET['t'] . '&page=' . $iPrevPage . '" aria-label = "Previous">
											<span aria-hidden = "true">&laquo;</span>
										</a>
									</li>';

									for($i = 1; $i <= $iTotalPosts; $i ++)
									{
										if($iCurPage == $i)
										{
											echo '<li class = "active"><a href = "showthread.php?t=' . $_GET['t'] . '&page=' . $i . '">' . $i . '</a></li>';
										}
										else
										{
											echo '<li><a href = "showthread.php?t=' . $_GET['t'] . '&page=' . $i . '">' . $i . '</a></li>';
										}
									}

									echo '<li>
										<a href = "showthread.php?t=' . $_GET['t'] . '&page=' . $iNextPage . '" aria-label = "Next">
											<span aria-hidden = "true">&raquo;</span>
										</a>
									</li>
								</ul>
							</nav>';

							// if user is logged in show reply part
							$qLock = DB::getInstance() -> query('SELECT locked FROM threads WHERE threadid = ?', array($_GET['t']));

							if($qLock -> first() -> locked == 1 && $iPermission < 1)
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
	                                           <td class = "postinfo extra-padding" style = "font-size: 16px;">This thread is locked.</td>
	                                        </tr>
	                                    </tbody>
	                                </table>';
							}
							else
							{
								if($iLoggedIn == 1)
								{
									echo '<form action = "newreply.php?do=reply&t=' . $_GET['t'] . '" method = "post">
										<table cellpadding = "2" cellspacing = "1" width = "100%">
											<thead>
												<tr class = "border">
													<td class = "groupname" colspan = "2">
														<b class = "white-text">Quick reply</b>
													</td>
												</tr>
											</thead>

											<tbody>';
												if($qLock -> first() -> locked == 1)
												{
													echo '
														<tr align = "center">
															<td class = "postinfo"><b>Note: This thread has been locked.</b></td>
														</tr>';
												}

												echo '<tr align = "center">
													<td colspan = "2" class = "postinfo">
														<div>
															<textarea cols = "100%" rows = "10" name = "reply_message"></textarea>

															<div class = "row-buffer-1"></div>

															<button type = "submit" class = "btn btn-success">Quick reply</button> <a href = "newreply.php?do=postreply&t=' . $_GET['t'] . '" class = "btn btn-info">More options</a>
														</div>
													</td>
												</tr>
											</tbody>
										</table>
										<div class = "row-buffer-10"></div>
									</form>';
								}
							}
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

		<?php
		if(isset($_GET['t']) && isset($_GET['p']))
			{
				echo '<script type = "text/javascript">document.getElementById("' . $_GET['p'] . '").scrollIntoView();</script>';
			}
		?>
		<!--=====================================
			Scripts
		=====================================-->
		<script src = "js/jquery-1.11.1.min.js"></script>
		<script src = "js/bootstrap.min.js"></script>
	</body>
</html>
