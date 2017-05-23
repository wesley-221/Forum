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
        <title>Post a thread</title>

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
                // look if subforum is set
                if(isset($_GET['f']))
                {
                    $iGivenForum = $_GET['f'];

                    $qThread = DB::getInstance() -> query('SELECT forumid FROM subforum WHERE subforumid = ?', array($iGivenForum));

                    // checking if subforum is valid
                    if($qThread -> count() > 0)
                    {
                        if(isset($_POST['subject']) && isset($_POST['reply_message']))
                        {
                            if(strlen($_POST['subject']) >= 5 && strlen($_POST['reply_message']) >= 5)
                            {
                                $sTempID = Functions::generate_uniqueID(20);
                                $dtSendDate     = date("Y-m-d");
                                $dtSendTime     = date("H:i:s");

                                $iStickied = isset($_POST['sticky']) ? $_POST['sticky'] : '';

                                if(!strcmp($iStickied, ''))
                                    $iStickied = 0;
                                else
                                    $iStickied = ($iStickied == true) ? "1" : "0";

                                $validIcons = Config::get('config/thread_icons/');

                                $sIconName = isset($_POST['pic']) ? $_POST['pic'] : "forum_old.png";

                                if(!in_array($sIconName, $validIcons))
                                {
                                    $sIconName = 'forum_old.png';
                                }

                                $qSaveThread = DB::getInstance() -> insert('threads', array(
                                        "subforumid" => $iGivenForum,
                                        "userid" => $dbUserid,
                                        "title" => $_POST['subject'],
										"lastpost" => $dtSendDate . ' ' . $dtSendTime,
                                        "sticky" => $iStickied,
                                        "temp_id" => $sTempID,
                                        "icon" => $sIconName
                                   ));

                                $qThreadID = DB::getInstance() -> query('SELECT threadid FROM threads WHERE temp_id = ?', array($sTempID));

                                $iThreadID = $qThreadID -> first() -> threadid;
                                $iOpening_Thread = "1";

                                $qSavePost = DB::getInstance() -> insert('posts', array (
                                                "threadid" => $iThreadID,
                                                "userid" => $dbUserid,
                                                "opening_post" => $iOpening_Thread,
                                                "date" => $dtSendDate,
                                                "time" => $dtSendTime,
                                                "title" => $_POST['subject'],
                                                "body" => $_POST['reply_message'],
                                           ));

                                $qRemoveThreadID = DB::getInstance() -> update('threads', array(
                                                                "temp_id" => "NOT_SET"
                                                           ), array(
                                                                'temp_id', '=', $sTempID
                                                           ));

                                header('location: showthread.php?t=' . $iThreadID);
                            }
                            else if(strlen($_POST['subject'] <= 4 || strlen($_POST['reply_message']) <= 4))
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
                                               <td class = "postinfo extra-padding" style = "font-size: 16px;">The title and/or message is too short. Please extend the title and/or message to atleast 5 characters. </td>
                                            </tr>
                                        </tbody>
                                    </table> <div class = "row-buffer-5"></div>';

                                echo '
                                    <form action = "postthread.php?f=' . $_GET['f'] . '" method = "post">
                                        <table cellpadding = "2" cellspacing = "1" width = "100%">
                                            <thead>
                                                <tr class = "border">
                                                    <td class = "groupname" colspan = "2">
                                                        <b class = "white-text">Start a new thread</b>
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
                                                                <td><input type = "text" name = "subject" size = "40" tabindex = "1" value = "' . $_POST['subject'] . '" autofocus /></td>
                                                            </tr>

                                                            <tr>
                                                                <td>
                                                                    <div class = "row-buffer-3"></div>';

                                                                    $iFirst = 1;
                                                                    foreach(Config::get('config/thread_icons/') as $sIcon)
                                                                    {
                                                                        if($iFirst == 1)
                                                                        {
                                                                            echo '<img src = "resources/images/thread-images/' . $sIcon . '" /> <input type = "radio" name = "pic" value = "' . $sIcon . '" checked>';
                                                                            $iFirst = 0;
                                                                        }
                                                                        else
                                                                        {
                                                                            echo '<img src = "resources/images/thread-images/' . $sIcon . '" /> <input type = "radio" name = "pic" value = "' . $sIcon . '">';
                                                                        }
                                                                    }

                                                                echo '</td>
                                                            </tr>

                                                            <tr>
                                                                <td>Message:</td>
                                                            </tr>

                                                            <tr>
                                                                <td><textarea cols = "100%" rows = "10" name = "reply_message" tabindex = "2">' . $_POST['reply_message'] . '</textarea></td>
                                                            </tr>';

                                                            if($iPermission >= 1)
                                                            {
                                                                echo '
                                                                    <tr>
                                                                        <td><input type = "checkbox" name = "sticky" tabindex = "3" /> <span>Make this thread sticky</span></td>
                                                                    </tr>';
                                                            }

                                                        echo '
                                                            <tr>
                                                                <td align = "center"><button type = "submit" class = "btn btn-info" tabindex = "4">Create thread</button></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </form>';
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
                                           <td class = "postinfo extra-padding" style = "font-size: 16px;">Something went wrong. Please try again</td>
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

        <!--=====================================
            Scripts
        =====================================-->
        <script src = "js/jquery-1.11.1.min.js"></script>
        <script src = "js/bootstrap.min.js"></script>
    </body>
</html>
