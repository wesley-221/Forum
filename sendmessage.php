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

        $iPermission = 0;
        $dbUserid = "N/A";
        Header('Location: index.php');
    }

    //=================================
    // Log in the user on button press
    if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        // Login button has been pressed
        if(isset($_POST['btnSubmitModal']))
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
        <title>Send a message</title>

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
                if(isset($_GET['u']) && $iLoggedIn == 1)
                {
                    $qUser = DB::getInstance() -> query('SELECT userid, profilename FROM users WHERE userid = ?', array($_GET['u']));

                    if($qUser -> count() > 0)
                    {
                        $iSenderId          = $qUser -> first() -> userid;
                        $sSenderProfileName = $qUser -> first() -> profilename;

                        $sReceiverUsernames = isset($_POST['receivernames']) ? $_POST['receivernames'] : '';
                        $sTitle             = isset($_POST['title']) ? $_POST['title'] : '';
                        $sPicture           = isset($_POST['pic']) ? $_POST['pic'] : 'forum_old.png';
                        $sMessage           = isset($_POST['message_body']) ? $_POST['message_body'] : '';

                        if($_SERVER['REQUEST_METHOD'] == 'POST')
                        {
                            // variables to check if variables have been filled in correctly
                            $iReceiverUsernames = 0;
                            $iTitle = 0;
                            $iMessage = 0;

                            $sError_Message = '';

                            $arrReceiverUsernamesFinal = array();

                            // multiple profilename filled in
                            if(strpos($sReceiverUsernames, ';') !== false)
                            {
                                $arrReceiverUsernames = explode(';', $sReceiverUsernames);

                                foreach($arrReceiverUsernames as $sUsernames)
                                {
                                    if(strlen($sUsernames) > 0)
                                    {
                                        if(!strcmp($sUsernames[0], " ") && strlen($sUsernames) > 1)
                                        {
                                            $sUsernames = substr($sUsernames, 1);
                                            array_push($arrReceiverUsernamesFinal, $sUsernames);
                                        }
                                        else if(strcmp($sUsernames[0], " ") && strlen($sUsernames) > 1)
                                        {
                                            array_push($arrReceiverUsernamesFinal, $sUsernames);
                                        }
                                    }
                                }

                                if(count($arrReceiverUsernamesFinal) > 0)
                                {
                                    // users found
                                    $iReceiverUsernames = 1;
                                }
                            }
                            // single profilename filled in
                            else
                            {
                                if(strlen($sReceiverUsernames) > 0)
                                {
                                    array_push($arrReceiverUsernamesFinal, $sReceiverUsernames);
                                    $iReceiverUsernames = 1;
                                }
                            }

                            if(strlen($sTitle) >= 5 && strlen($sTitle) <= 50)
                            {
                                $iTitle = 1;
                            }

                            if(strlen($sMessage) >= 5)
                            {
                                $iMessage = 1;
                            }

                            if($iReceiverUsernames == 0)
                                $sError_Message .= '- There has to be atleast one receiver. <br />';

                            if($iTitle == 0)
                                $sError_Message .= '- You have to enter a title. Make sure it is atleast 5 characters and at most 50 characters. <br />';

                            if($iMessage == 0)
                                $sError_Message .= '- You have to enter a message. Make sure it is atleast 5 characters long. <br />';

                            if(strlen($sError_Message) > 0)
                            {
                                echo '
                                    <table cellpadding = "2" cellspacing = "1" width = "70%" align = "center" class = "textarea_mobile">
                                        <thead>
                                            <tr class = "border">
                                                <td class = "groupname" colspan = "2">
                                                    <b class = "white-text">System message</b>
                                                </td>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr align = "center">
                                                <td class = "postinfo extra-padding" style = "font-size: 16px;">
                                                    <b>You were unable to send this private message because of the following reason(s):</b> <br />
                                                    ' . $sError_Message . '
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table> <div class = "row-buffer-5"></div>';
                            }
                            else
                            {
                                if($iReceiverUsernames == 1)
                                {
                                    $arrNonValidUsers = array();
                                    $arrValidUsers = array();

                                    foreach($arrReceiverUsernamesFinal as $sUserFinal)
                                    {
                                        $qReceiverUser = DB::getInstance() -> query('SELECT userid FROM users WHERE profilename = ?', array($sUserFinal));

                                        if($qReceiverUser -> count() > 0)
                                        {
                                            array_push($arrValidUsers, $sUserFinal);
                                        }
                                        else
                                        {
                                            array_push($arrNonValidUsers, $sUserFinal);
                                        }
                                    }

                                    if(count($arrNonValidUsers) > 0)
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
                                                           <td class = "postinfo extra-padding" style = "font-size: 16px;">
                                                               <b>The following user(s) you entered are invalid:</b> <br />';

                                                                foreach($arrNonValidUsers as $sInvalidUser)
                                                                {
                                                                    echo '- ' . $sInvalidUser . '<br />';
                                                                }

                                                           echo '<br />Please try again.
                                                       </td>
                                                    </tr>
                                                </tbody>
                                            </table> <div class = "row-buffer-3"></div>';
                                    }
                                    else
                                    {
                                        $dtDate     = date("Y-m-d");
                                        $dtTime     = date("H:i:s");
                                        if(count($arrValidUsers) > 1)
                                        {
                                            foreach($arrValidUsers as $sValidUser)
                                            {
                                                $qReceiverUser = DB::getInstance() -> query('SELECT userid FROM users WHERE profilename = ?', array($sValidUser));
                                                $iReceiverUserid = $qReceiverUser -> first() -> userid;

                                                DB::getInstance() -> insert('messages', array(
                                                                            'senderid' => $dbUserid,
                                                                            'receiverid' => $iReceiverUserid,
                                                                            'title' => $sTitle,
                                                                            'message' => $sMessage,
                                                                            'icon' => $sPicture,
                                                                            'date' => $dtDate,
                                                                            'time' => $dtTime
                                                                       ));
                                            }
                                        }
                                        else
                                        {
                                            $qReceiverUser = DB::getInstance() -> query('SELECT userid FROM users WHERE profilename = ?', array($arrValidUsers[0]));
                                            $iReceiverUserid = $qReceiverUser -> first() -> userid;

                                            DB::getInstance() -> insert('messages', array(
                                                                            'senderid' => $dbUserid,
                                                                            'receiverid' => $iReceiverUserid,
                                                                            'title' => $sTitle,
                                                                            'message' => $sMessage,
                                                                            'icon' => $sPicture,
                                                                            'date' => $dtDate,
                                                                            'time' => $dtTime
                                                                       ));
                                        }

                                        Header('Location: messages.php');
                                    }
                                }
                            }
                        }

                        $sTextAreaUsernames = strlen($sReceiverUsernames) > 0 ? $sReceiverUsernames : $sSenderProfileName;

                        echo '<form action = "sendmessage.php?u=' . $_GET['u'] . '" method = "post">
                                <table cellpadding = "2" cellspacing = "1" width = "100%">
                                    <thead>
                                        <tr class = "border">
                                            <td class = "groupname" colspan = "2">
                                                <b class = "white-text">Send a private message</b>
                                            </td>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr align = "center">
                                            <td colspan = "2" class = "postinfo">
                                                <table>
                                                    <tr>
                                                        <td colspan = "2">Username(s) of receiver(s):</td>
                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <textarea rows = "2" cols = "50" autocomplete = "off" class = "textarea_mobile" name = "receivernames">' . htmlentities($sTextAreaUsernames) . '</textarea><br />

                                                            To send a private message to multiple people, type a ";" after each forum name: User1; User2; User3
                                                            <div class = "row-buffer-3"></div>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td colspan = "2">Title:</td>
                                                    </tr>

                                                    <tr>
                                                        <td><input type = "text" name = "title" class = "form-control" size = "40" tabindex = "1" autofocus value = "' . htmlentities($sTitle) . '" onFocus = "this.setSelectionRange(0, this.value.length)" /></td>
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
                                                        <td><textarea cols = "100%" rows = "10" name = "message_body" class = "textarea_mobile" tabindex = "2">' . htmlentities($sMessage) . '</textarea></td>
                                                    </tr>';

                                                echo '
                                                    <tr>
                                                        <td align = "center"><button type = "submit" class = "btn btn-info" tabindex = "4">Send private message</button></td>
                                                    </tr>
                                                </table>
                                            </td>
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
                                       <td class = "postinfo extra-padding" style = "font-size: 16px;">This is not a valid user. Please try again.</td>
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
                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">This is not a valid user. Please try again.</td>
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
                            <button type = "submit" class = "btn btn-primary" name = "btnSubmitModal">Log in</button>
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
