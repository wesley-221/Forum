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
			$iLoggedIn = 1;			$iPermission = $dbPermission;
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
		<title>Delete forum elements</title>

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
				$gDelete = isset($_GET['delete']) ? $_GET['delete'] : '';

				// look if the correct things have been filled in
				if(strlen($gDelete) == 1 && (!strcmp($gDelete, 'c') || !strcmp($gDelete, 't') || !strcmp($gDelete, 'p') || !strcmp($gDelete, 's')))
				{
					$gDeleteElement = isset($_GET[$gDelete]) ? $_GET[$gDelete] : '';

					// if any of the ids is filled in
					if(strlen($gDeleteElement) > 0)
					{
						if($_SERVER['REQUEST_METHOD'] == 'POST')
						{
							if(!strcmp($gDelete, 'p'))
							{
								$qPostThreadId = DB::getInstance() -> query('SELECT threadid FROM posts WHERE postid = ?', array($gDeleteElement));
								$iThreadId = $qPostThreadId -> first() -> threadid;
								$qDeletePost = DB::getInstance() -> query('DELETE FROM posts WHERE postid = ?', array($gDeleteElement));

								header('location: showthread.php?t=' . $iThreadId);
							}
							else if(!strcmp($gDelete, 't'))
							{
								$qDeleteAllPosts = DB::getInstance() -> query('DELETE FROM posts WHERE threadid = ?', array($gDeleteElement));
								$qSubForumId = DB::getInstance() -> query('SELECT subforumid FROM threads WHERE threadid = ?', array($gDeleteElement));
								$iSubForumId = $qSubForumId -> first() -> subforumid;
								$qDeleteThread = DB::getInstance() -> query('DELETE FROM threads WHERE threadid = ?', array($gDeleteElement));

								header('location: forumdisplay.php?f=' . $iSubForumId);
							}
							else if(!strcmp($gDelete, 's'))
							{
								$qAllThreads = DB::getInstance() -> query('SELECT threadid FROM threads WHERE subforumid = ?', array($gDeleteElement));

								foreach($qAllThreads -> results() as $qThread)
								{
									$qDeleteAllPosts = DB::getInstance() -> query('DELETE FROM posts WHERE threadid = ?', array($qThread -> threadid));
									$qDeleteThisThread = DB::getInstance() -> query('DELETE FROM threads WHERE threadid = ?', array($qThread -> threadid));
								}

								$qDeleteSubForum = DB::getInstance() -> query('DELETE FROM subforum WHERE subforumid = ?', array($gDeleteElement));

								header('Location: index.php');
							}
							else if(!strcmp($gDelete, 'c'))
							{
								$qAllSubForum = DB::getInstance() -> query('SELECT subforumid FROM subforum WHERE forumid = ?', array($gDeleteElement));

								foreach($qAllSubForum -> results() as $qSubForum)
								{
									$qAllThreads = DB::getInstance() -> query('SELECT threadid FROM threads WHERE subforumid = ?', array($qSubForum -> subforumid));

									foreach($qAllThreads -> results() as $qThread)
									{
										$qDeleteAllPosts = DB::getInstance() -> query('DELETE FROM posts WHERE threadid = ?', array($qThread -> threadid));
										$qDeleteThisThread = DB::getInstance() -> query('DELETE FROM threads WHERE threadid = ?', array($qThread -> threadid));
									}

									$qDeleteSubForum = DB::getInstance() -> query('DELETE FROM subforum WHERE subforumid = ?', array($qSubForum -> subforumid));
								}

								$qDeleteCategory = DB::getInstance() -> query('DELETE FROM forum WHERE forumid = ?', array($gDeleteElement));

								header('Location: index.php');
							}
						}

						if(!strcmp($gDelete, 'p'))
						{
							$qPost = DB::getInstance() -> query('SELECT userid, title, body FROM posts WHERE postid = ?', array($gDeleteElement));

							if($qPost -> count() > 0)
							{
								echo '
									<form action = "delete_elements.php?delete=' . $gDelete . '&' . $gDelete . '=' . $gDeleteElement . '" method = "post">
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
													<td class = "postinfo">
														<table>
															<tr>
																<td class = "postinfo" style = "width: 80px;">Profile name</td><td>:&nbsp;</td><td>' . $qPost -> first() -> userid . '</td>
															</tr>

															<tr>
																<td class = "postinfo">Title</td><td>:&nbsp;</td><td>' . $qPost -> first() -> title . '</td>
															</tr>

															<tr>
																<td class = "postinfo">Message</td><td>:&nbsp;</td><td>' . $qPost -> first() -> body . '</td>
															</tr>
														</table>
													</td>
												</tr>

												<tr align = "center">
													<td class = "postinfo"><button type = "submit" class = "btn btn-info">Delete Post</button></td>
												</tr>
											</tbody>
										</table>
									</form>';
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
			                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">Invalid forum specified. Please try again1</td>
			                                </tr>
			                            </tbody>
			                        </table>';
							}
						}
						else if(!strcmp($gDelete, 't'))
						{
							$qThread = DB::getInstance() -> query('SELECT threadid, title FROM threads WHERE threadid = ?', array($gDeleteElement));

							if($qThread -> count() > 0)
							{
								echo '
									<form action = "delete_elements.php?delete=' . $gDelete . '&' . $gDelete . '=' . $gDeleteElement . '" method = "post">
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
													<td class = "postinfo">
														<table>
															<tr>
																<td class = "postinfo big-text">
																	Are you sure you want to delete this thread? <br />
																	Note: This will delete ALL posts that are in this thread
																</td>
															</tr>

															<tr>
																<td class = "postinfo">Title: <b>' . $qThread -> first() -> title . '</b></td>
															</tr>
														</table>
													</td>
												</tr>


												<tr align = "center">
													<td class = "postinfo"><button type = "submit" class = "btn btn-info">Delete Thread</button></td>
												</tr>
											</tbody>
										</table>
									</form>';
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
			                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">Invalid forum specified. Please try again1</td>
			                                </tr>
			                            </tbody>
			                        </table>';
							}
						}
						else if(!strcmp($gDelete, 's'))
						{
							$qSubForum = DB::getInstance() -> query('SELECT subforumname, subforumdescription FROM subforum WHERE subforumid = ?', array($gDeleteElement));

							if($qSubForum -> count() > 0)
							{
								echo '
									<form action = "delete_elements.php?delete=' . $gDelete . '&' . $gDelete . '=' . $gDeleteElement . '" method = "post">
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
													<td class = "postinfo">
														<table>
															<tr>
																<td class = "postinfo big-text">
																	Are you sure you want to delete this sub forum? <br />
																	Note: This will delete all threads AND posts that are in this sub forum
																</td>
															</tr>

															<tr>
																<td class = "postinfo">Title: <b>' . $qSubForum -> first() -> subforumname . '</b></td>
															</tr>

															<tr>
																<td class = "postinfo">Description: <b>' . $qSubForum -> first() -> subforumdescription . '</b></td>
															</tr>
														</table>
													</td>
												</tr>


												<tr align = "center">
													<td class = "postinfo"><button type = "submit" class = "btn btn-info">Delete Sub Forum</button></td>
												</tr>
											</tbody>
										</table>
									</form>';
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
			                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">Invalid forum specified. Please try again2</td>
			                                </tr>
			                            </tbody>
			                        </table>';
							}
						}
						else if(!strcmp($gDelete, 'c'))
						{
							$qForum = DB::getInstance() -> query('SELECT forumid, forumname FROM forum WHERE forumid = ?', array($gDeleteElement));

							if($qForum -> count() > 0)
							{
								echo '
									<form action = "delete_elements.php?delete=' . $gDelete . '&' . $gDelete . '=' . $gDeleteElement . '" method = "post">
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
													<td class = "postinfo">
														<table>
															<tr>
																<td class = "postinfo big-text">
																	Are you sure you want to delete this forum? <br />
																	Note: This will delete all sub forums, threads AND posts that are in this category
																</td>
															</tr>

															<tr>
																<td class = "postinfo">Category name: ' . $qForum -> first() -> forumname . '<b></b></td>
															</tr>
														</table>
													</td>
												</tr>


												<tr align = "center">
													<td class = "postinfo"><button type = "submit" class = "btn btn-info">Delete Forum Category</button></td>
												</tr>
											</tbody>
										</table>
									</form>';
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
	                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">Invalid forum specified. Please try again2</td>
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
                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">Invalid forum specified. Please try again3</td>
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

		<!--=====================================
			Scripts
		=====================================-->
		<script src = "js/jquery-1.11.1.min.js"></script>
		<script src = "js/bootstrap.min.js"></script>
	</body>
</html>
