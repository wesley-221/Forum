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
        $dbUserid       = $qCookie -> first() -> userid;
        $dbCookie       = $qCookie -> first() -> cookie;
        $dbProfilename  = $qCookie -> first() -> profilename;
        $dbPermission   = $qCookie -> first() -> permission;

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
                    $dbPassword     = $qPassword -> first() -> password;
                    $dbSalt         = $qPassword -> first() -> salt;
                    $userPassword   = hash("whirlpool", $sPassword . $dbSalt);

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
        <title>Reply to message</title>

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
                <a href = "index.php" class = "navbar-brand">TIM</a>

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
                // do options: reply > quick reply, postreply > new window
                if(isset($_GET['do']))
                {
                    // is topic set
                    if(isset($_GET['t']))
                    {
                        // make a query to check if thread exists
                        $qThread = DB::getInstance() -> query('SELECT threadid, subforumid, locked FROM threads WHERE threadid = ?', array($_GET['t']));

                        if($qThread -> first() -> locked == 1 && $iPermission < 1)
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
                            // >0 = thread exists
                            if($qThread -> count() > 0)
                            {
                                $iSubForumID = $qThread -> first() -> subforumid;


                                $qForumPermission = DB::getInstance() -> query('SELECT forumid FROM subforum WHERE subforumid = ?', array($iSubForumID));
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
                                    $sUserPostReply = isset($_POST['reply_message']) ? $_POST['reply_message'] : '';

                                    // quick reply
                                    if(!strcmp($_GET['do'], "postreply") || strlen($sUserPostReply) <= 4)
                                    {
                                        $qThreadTitle = DB::getInstance() -> query('SELECT title FROM threads WHERE threadid = ?', array($_GET['t']));
                                        $sReply_Title = $qThreadTitle -> first() -> title;
                                        $sReply_Message = "";

                                        if(strlen($sUserPostReply) <= 4 && $_SERVER['REQUEST_METHOD'] == 'POST')
                                        {
                                            echo '
                                            <table cellpadding = "2" cellspacing = "1" width = "50%" align = "center" class = "textarea_mobile">
                                                <thead>
                                                    <tr class = "border">
                                                        <td class = "groupname" colspan = "2">
                                                            <b class = "white-text">The following errors have been encountered while sending this post: </b>
                                                        </td>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <tr align = "center">
                                                       <td class = "postinfo extra-padding" style = "font-size: 16px;">The message is too short. Please extend the message to atleast 5 characters. </td>
                                                    </tr>
                                                </tbody>
                                            </table> <div class = "row-buffer-5"></div>';
                                        }
                                        else
                                        {
                                            $sReply_Message = $sUserPostReply;
                                        }

                                        echo '
                                            <form action = "newreply.php?do=reply&t=' . $_GET['t'] . '" method = "post">
                                                <table cellpadding = "2" cellspacing = "1" width = "100%" align = "center">
                                                    <thead>
                                                        <tr class = "border">
                                                            <td class = "groupname" colspan = "2">
                                                                <b class = "white-text">Reply</b>
                                                            </td>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <tr align = "center">
                                                            <td colspan = "2" class = "postinfo">
                                                                <table>
                                                                    <tr>
                                                                        <td colspan = "2">Title:</td>
                                                                    </tr>

                                                                    <tr>
                                                                        <td><input type = "text" name = "subject" size = "40" tabindex = "1" value = "' . $sReply_Title . '"/></td>
                                                                    </tr>

                                                                    <tr>
                                                                        <td><div class = "row-buffer-5"></div></td>
                                                                    </tr>

                                                                    <tr>
                                                                        <td>Message:</td><td><b>BB codes</b></td>
                                                                    </tr>

                                                                    <tr>
                                                                        <td><textarea cols = "100%" rows = "10" name = "reply_message">' . $sReply_Message . '</textarea></td>

                                                                        <td>
                                                                            <div style = "padding-left: 4px;">
                                                                                [b]bold[/b] <br />
                                                                                [i]italic[/i] <br />
                                                                                [u]underline[/u] <br />
                                                                                [size=#]size[/size] <br />
                                                                                [color=#]color[/color] <br />
                                                                                [url=#]url[/url] <br />
                                                                                [img]img[/img] <br />
                                                                                [video]video[/video] <br />
                                                                                [spoiler=headertext]body[/spoiler] <br />

                                                                            </div>
                                                                        </td>
                                                                    </tr>

                                                                    <tr>
                                                                        <td align = "center"><button type = "submit" class = "btn btn-info">Send post</button></td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </form>';

                                    }
                                    // quick reply
                                    else if(!strcmp($_GET['do'], "reply"))
                                    {
                                        if(strlen($sUserPostReply) > 4)
                                        {
                                            $qThread = DB::getInstance() -> query('SELECT title FROM threads WHERE threadid = ?', array($_GET['t']));
                                            $dtSendDate     = date("Y-m-d");
                                            $dtSendTime     = date("H:i:s");

                                            $qPost = DB::getInstance() -> insert('posts', array (
                                                    "threadid" => $_GET['t'],
                                                    "userid" => $dbUserid,
                                                    "date" => $dtSendDate,
                                                    "time" => $dtSendTime,
                                                    "title" => "RE: " . $qThread -> first() -> title,
                                                    "body" => $_POST['reply_message']
                                               ));

											DB::getInstance() -> update('threads', array("lastpost" => $dtSendDate . ' ' . $dtSendTime), array('threadid', '=', $_GET['t']));

                                            header('Location: showthread.php?t=' . $_GET['t']);
                                        }
                                        else
                                        {
                                            $qThreadTitle = DB::getInstance() -> query('SELECT title FROM threads WHERE threadid = ?', array($_GET['t']));
                                            $sReply_Title = $qThreadTitle -> first() -> title;

                                            echo '
                                                <table cellpadding = "2" cellspacing = "1" width = "50%" align = "center" class = "textarea_mobile">
                                                    <thead>
                                                        <tr class = "border">
                                                            <td class = "groupname" colspan = "2">
                                                                <b class = "white-text">The following errors have been encountered while sending this post: </b>
                                                            </td>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <tr align = "center">
                                                           <td class = "postinfo extra-padding" style = "font-size: 16px;">The message is too short. Please extend the message to atleast 4 characters. </td>
                                                        </tr>
                                                    </tbody>
                                                </table> <div class = "row-buffer-5"></div>

                                                <form action = "newreply.php?do=reply&t=' . $_GET['t'] . '" method = "post">
                                                    <table cellpadding = "2" cellspacing = "1" width = "100%">
                                                        <thead>
                                                            <tr class = "border">
                                                                <td class = "groupname" colspan = "2">
                                                                    <b class = "white-text">Reply</b>
                                                                </td>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                            <tr align = "center">
                                                                <td colspan = "2" class = "postinfo">
                                                                    <table>
                                                                        <tr>
                                                                            <td colspan = "2">Title:</td>
                                                                        </tr>

                                                                        <tr>
                                                                            <td><input type = "text" name = "subject" size = "40" tabindex = "1" value = "' . $sReply_Title . '"/></td>
                                                                        </tr>

                                                                        <tr>
                                                                            <td>Message:</td>
                                                                        </tr>

                                                                        <tr>
                                                                            <td><textarea cols = "100%" rows = "10" name = "reply_message"></textarea></td>
                                                                        </tr>

                                                                        <tr>
                                                                            <td align = "center"><button type = "submit" class = "btn btn-info">Send post</button></td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </form>';
                                        }
                                    }
                                    // new window
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
							// thread does not exist
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
					// topic is not set
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
				// invalid parameter given
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

        <!--=====================================
            Scripts
        =====================================-->
        <script src = "js/jquery-1.11.1.min.js"></script>
        <script src = "js/bootstrap.min.js"></script>
    </body>
</html>
