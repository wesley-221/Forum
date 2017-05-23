<!DOCTYPE html>
<?php
    require_once 'core/init.php';
    $sUsername = isset($_POST['tbUsername']) ? $_POST['tbUsername'] : '';
    $sPassword = isset($_POST['tbPassword']) ? $_POST['tbPassword'] : '';

    $curDate = date('Y-m-d');

    /*for($i = 8; $i < 500; $i ++)
    {
        DB::getInstance() -> insert('friends', array('userid' => 1, 'friend_userid' => $i));
    }*/

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
        <title>My messages</title>

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
                if(isset($_GET['m']))
                {
                    $qGetMessage = DB::getInstance() -> query('SELECT senderid, receiverid, title, message, icon, message_read, date, time FROM messages WHERE messageid = ?', array($_GET['m']));

                    if($qGetMessage -> count() > 0)
                    {
                        if($qGetMessage -> first() -> receiverid == $dbUserid)
                        {
                            $iMSenderId     = $qGetMessage -> first() -> senderid;
                            $iMReceiverId   = $qGetMessage -> first() -> receiverid;
                            $sMTitle        = $qGetMessage -> first() -> title;
                            $sMMessage      = $qGetMessage -> first() -> message;
                            $sMIcon         = $qGetMessage -> first() -> icon;
                            $iMMessageRead  = $qGetMessage -> first() -> message_read;
                            $dtMDate        = $qGetMessage -> first() -> date;
                            $dtMTime        = $qGetMessage -> first() -> time;

                            $qUserdataSender = DB::getInstance() -> query('SELECT userid, userdescription, username, register_date, profilename, permission, signature FROM users WHERE userid = ?', array($iMSenderId));
                            $iSUserid           = $qUserdataSender -> first() -> userid;
                            $sSUserDescription  = $qUserdataSender -> first() -> userdescription;
                            $sSUsername         = $qUserdataSender -> first() -> username;
                            $dtSRegisterDate    = $qUserdataSender -> first() -> register_date;
                            $sSProfilename      = $qUserdataSender -> first() -> profilename;
                            $sSSignature        = $qUserdataSender -> first() -> signature;
                            $iSPermission       = $qUserdataSender -> first() -> permission;

                            $sConfigString = 'config/color_codes/' . $iSPermission;
                            $iProfileNameColour = Config::get($sConfigString);

                            $dtSendDate     = date("Y-m-d");
                            $dtSendTime     = date("H:i:s");

                            if($iMMessageRead == 0)
                            {
                                $iUpdateRead = 1;
                                DB::getInstance() -> update('messages', array('message_read' => $iUpdateRead), array('messageid', "=",  $_GET['m']));
                            }

                            echo '
                                <ol class = "breadcrumb">
                                    <li><a href = "index.php" style = "font-size: 16px;">' . Config::get('config/forum_name/name') . '</a></li>
                                    <li><a href = "messages.php">Messages</a></li>
                                    <li class = "active">' . $sMTitle . '</li>
                                </ol>';

                            if($_SERVER['REQUEST_METHOD'] == 'POST')
                            {
                                if(isset($_GET['do']) && !strcmp($_GET['do'], 'reply'))
                                {
                                    $sReplyPM = isset($_POST['reply_pm']) ? $_POST['reply_pm'] : '';

                                    if(strlen($sReplyPM) >= 4)
                                    {
                                        $dtSendDate     = date("Y-m-d");
                                        $dtSendTime     = date("H:i:s");

                                        DB::getInstance() -> insert('messages', array(
                                                                        'senderid' => $dbUserid,
                                                                        'receiverid' => $iMSenderId,
                                                                        'title' => 'RE: ' . $sMTitle,
                                                                        'message' => $sReplyPM,
                                                                        'icon' => $sMIcon,
                                                                        'date' => $dtSendDate,
                                                                        'time' => $dtSendTime
                                                                   ));
                                    }
                                    else
                                    {
                                        echo '<table cellpadding = "2" cellspacing = "1" width = "50%" align = "center" class = "textarea_mobile">
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
                                            </table> <div class = "row-buffer-5"></div>';
                                    }
                                }
                                else if(isset($_GET['do']) && !strcmp($_GET['do'], 'markunread'))
                                {
                                    DB::getInstance() -> update('messages', array('message_read' => 0), array('messageid', '=', $_GET['m']));
                                    Header('Location: messages.php');
                                }
                            }

                            if(isset($_GET['do']) && !strcmp($_GET['do'], 'delete'))
                            {
                                DB::getInstance() -> delete('messages', array('messageid', '=', $_GET['m']));
                                Header('Location: messages.php');
                            }

                            echo '
                                <table cellpadding = "2" cellspacing = "1" width = "100%" align = "center">
                                    <thead>
                                        <tr>
                                            <td>
                                                <form action = "messages.php?do=markunread&m=' . $_GET['m'] . '" method = "post">
                                                    <button name = "btnMarkUnread" class = "btn btn-primary">Mark as unread</button>
                                                </form>
                                                <div class = "row-buffer-3"></div>
                                            </td>
                                        </tr>
                                    </thead>

                                    <thead id = "' . $_GET['m'] . '">
                                        <tr class = "border">
                                            <td class = "groupname" colspan = "2">
                                                <b style = "color: white;">' . $dtMDate . ', ' . $dtMTime . '</b>
                                            </td>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr valign = "top">
                                            <td width = "12%" class = "border userinfo">
                                                <div class = "big-text"><a href = "member.php?u=' . $iSUserid . '" style = "color: ' . $iProfileNameColour . ';" class = "no-underline">' . $sSProfilename . '</a></div>
                                                <div><b><i>' . $sSUserDescription . '</i></b></div>';

                                                $finalImgUrl = 'resources/images/profile_pictures/unknown.jpg';

                                                foreach(Config::get('config/allowed_extensions/') as $extension)
                                                {
                                                    if(file_exists('resources/images/profile_pictures/' . $sSUsername . '.' . $extension))
                                                    {
                                                        $finalImgUrl = 'resources/images/profile_pictures/' . $sSUsername . '.' . $extension;
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
                                                <div>Join date: ' . $dtSRegisterDate . '</div>';
                                                $qAmountOfPosts = DB::getInstance() -> query('SELECT count(*) AS totalposts FROM posts WHERE userid = ?', array($iSUserid));

                                                echo '
                                                <div>Posts: ' . $qAmountOfPosts -> first() -> totalposts . '</div>
                                                <div>Reputation: N/A</div>
                                            </td>

                                            <td class = "border postinfo">
                                                <div class = "small-text">
                                                    <img src = "resources/images/thread-images/' . $sMIcon . '" width = "20px" height = "20px" /> <strong>' . $sMTitle . '</strong>
                                                    <hr size = "1" style = "color: #DFDFDF; background-color: #DFDFDF">';

                                                    echo Functions::bb_parse(nl2br(htmlentities($sMMessage)));

                                                    echo '<div class = "small-font">
                                                        <hr size = "1" style = "color: black;">
                                                            ' . $sSSignature . '
                                                        </hr>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class = "row-buffer-10"></div>

                                <form action = "messages.php?do=reply&m=' . $_GET['m'] . '" method = "post">
                                    <table cellpadding = "2" cellspacing = "1" width = "100%" align = "center">
                                        <thead>
                                            <tr class = "border">
                                                <td class = "groupname" colspan = "2">
                                                    <b class = "white-text">Quick reply</b>
                                                </td>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr align = "center">
                                                <td colspan = "2" class = "postinfo">
                                                    <div>
                                                        <textarea cols = "100%" rows = "10" name = "reply_pm"></textarea>

                                                        <div class = "row-buffer-1"></div>

                                                        <button type = "submit" class = "btn btn-success">Send message</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div class = "row-buffer-10"></div>
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
                    if(isset($_GET['do']) && !strcmp($_GET['do'], 'deleteall'))
                    {
                        echo '
                        <table cellpadding = "2" cellspacing = "1" width = "70%" align = "center"  class = "textarea_mobile">
                            <thead>
                                <tr class = "border">
                                    <td class = "groupname" colspan = "2">
                                        <b class = "white-text">Delete all messages</b>
                                    </td>
                                </tr>
                            </thead>

                            <tbody>
                                <tr align = "center">
                                    <td class = "postinfo border">
                                        Are you sure you want to delete <b>ALL</b> messages? <br />
                                        This includes both received AND sended messages.

                                        <div class = "row-buffer-3"></div>
                                        <button class = "btn btn-danger">Delete all messages</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class = "row-buffer-3"></div>';
                    }

                    echo '
                        <table cellpadding = "2" cellspacing = "1" width = "50%" align = "center" class = "textarea_mobile">
                            <thead>
                                <tr class = "border">
                                    <td class = "groupname" colspan = "2">
                                        <b class = "white-text">Private messages</b>
                                    </td>
                                </tr>
                            </thead>

                            <tbody>';
                                    $qMessages = DB::getInstance() -> query('SELECT count(messageid) AS totalmessages FROM messages WHERE receiverid = ?', array($dbUserid));

                                    echo '
                                    <tr>
                                        <td class = "postinfo">
                                            You have ' . $qMessages -> first() -> totalmessages . ' messages saved, of the allowed ' . Config::get('config/max_messages/') . ' messages. (<a href = "messages.php?do=deleteall" class = "blue-text">Delete all messages</a>)';

                                            $iPercentage = ceil($qMessages -> first() -> totalmessages / Config::get('config/max_messages/'));

                                            echo '<div class = "progress">';
                                            if($iPercentage <= 50)
                                            {
                                                echo '<div class = "progress-bar progress-bar-success" style = "width: ' . $iPercentage . '%"></div>';
                                            }
                                            else if($iPercentage >= 51 && $iPercentage <= 80)
                                            {
                                                echo '<div class = "progress-bar progress-bar-success" style = "width: 50%"></div>
                                                    <div class = "progress-bar progress-bar-warning progress-bar-striped" style = "width: ' . ($iPercentage - 50) . '%"></div>';
                                            }
                                            else if($iPercentage >= 81 && $iPercentage <= 100)
                                            {
                                                echo '<div class = "progress-bar progress-bar-success" style = "width: 50%"></div>
                                                    <div class = "progress-bar progress-bar-warning progress-bar-striped" style = "width: 30%"></div>
                                                    <div class = "progress-bar progress-bar-danger" style = "width: ' . ($iPercentage - 80) . '%"></div>';
                                            }

                                            echo '</div>
                                        </td>
                                    </tr>
                            </tbody>
                        </table>

                        <div class = "row-buffer-5"></div>';

                        $qOtherFriends = DB::getInstance() -> query('SELECT userid FROM friends WHERE friend_userid = ? AND denied = 0', array($dbUserid));

                        if($qOtherFriends -> count() > 0)
                        {
                            $iOtherFriend = $qOtherFriends -> first() -> userid;
                            $qMyFriend = DB::getInstance() -> query('SELECT friend_userid FROM friends WHERE userid = ? AND friend_userid = ? AND denied = 0', array($dbUserid, $iOtherFriend));

                            if($qMyFriend -> count() <= 0)
                            {
                                echo '
                                    <table cellpadding = "5" cellspacing = "1" width = "70%" align = "center" class = "textarea_mobile">
                                        <thead>
                                            <tr class = "border">
                                                <td class = "groupname" colspan = "5">
                                                    <b class = "white-text">Friend requests</b>
                                                </td>
                                            </tr>

                                            <tr class = "tophead">
                                                <td class = "border theadpic remove_mobile"></td>
                                                <td class = "border" width = "100%" style = "padding: 4px"><span class = "white-text">Subject/Sender</span></td>
                                                <td class = "border" style = "padding: 4px"></td>
                                                <td class = "border" style = "padding: 4px"></td>
                                                <td class = "border" style = "padding: 4px"></td>
                                            </tr>
                                        </thead>';

                                        $qOtherFriends = DB::getInstance() -> query('SELECT userid FROM friends WHERE friend_userid = ? AND denied = 0', array($dbUserid));

                                        foreach($qOtherFriends -> results() as $oOtherFriends)
                                        {
                                            $qMyFriend = DB::getInstance() -> query('SELECT friend_userid FROM friends WHERE userid = ? AND friend_userid = ? AND denied = 0', array($dbUserid, $oOtherFriends -> userid));

                                            if($qMyFriend -> count() <= 0)
                                            {
                                                // non mutual friends, show friend request
                                                $qOtherFriendData = DB::getInstance() -> query('SELECT profilename FROM users WHERE userid = ?', array($oOtherFriends -> userid));

                                                echo '
                                                    <tbody>
                                                        <tr align = "center">
                                                            <td class = "tdtest remove_mobile" style = "background-color: #EAEAEB !important;"><img src = "resources/images/thread-images/forum_old.png" /></td>

                                                            <td class = "tdtest" align = "left">
                                                                <div>
                                                                    <a href = "member.php?u=' . $oOtherFriends -> userid . '" class = "big-text blue-text">' . $qOtherFriendData -> first() -> profilename . '</a>
                                                                </div>
                                                            </td>

                                                            <td class = "tdtest">
                                                                <a href = "member.php?u=' . $oOtherFriends -> userid . '&a=addfriend">
                                                                    <span class = "fa-stack fa-lg">
                                                                        <i class = "fa fa-square fa-stack-2x" style = "color: green;"></i>
                                                                        <i class = "fa fa-check fa-stack-1x fa-inverse"></i>
                                                                    </span>
                                                                </a>
                                                            </td>

                                                            <td class = "tdtest">
                                                                <a href = "member.php?u=' . $oOtherFriends -> userid . '&a=denyfriend">
                                                                    <span class = "fa-stack fa-lg">
                                                                        <i class = "fa fa-square fa-stack-2x" style = "color: red;"></i>
                                                                        <i class = "fa fa-times fa-stack-1x fa-inverse"></i>
                                                                    </span>
                                                                </a>
                                                            </td>

                                                            <td class = "tdtest">
                                                                <input type = "checkbox" />
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                ';
                                            }
                                        }

                                        echo '
                                    </table>';
                            }

                        }

                        echo '<div class = "row-buffer-3"></div>

                        <table cellpadding = "4" cellspacing = "1" width = "70%" align = "center" class = "textarea_mobile">
                            <thead>
                                <tr class = "border">
                                    <td class = "groupname" colspan = "4">
                                        <b class = "white-text">Private messages</b>
                                    </td>
                                </tr>

                                <tr class = "tophead">
                                    <td class = "border theadpic remove_mobile"></td>
                                    <td class = "border theadpic remove_mobile"></td>
                                    <td class = "border" width = "85%" style = "padding: 4px"><span class = "white-text">Subject/Sender</span></td>
                                    <td class = "border" style = "padding: 4px;"></td>
                                    <!--<td class = "border" style = "padding: 4px;"></td>-->
                                </tr>
                            </thead>';


                            $qReceivedPM = DB::getInstance() -> query('SELECT messageid, senderid, receiverid, title, message, icon, message_read FROM messages WHERE receiverid = ? ORDER BY messageid DESC', array($dbUserid));

                            if($qReceivedPM -> count() > 0)
                            {
                                foreach($qReceivedPM -> results() as $oPM)
                                {
                                    $qSenderDetails = DB::getInstance() -> query('SELECT profilename, permission FROM users WHERE userid = ?', array($oPM -> senderid));

                                    $sConfigString = 'config/color_codes/' . $qSenderDetails -> first() -> permission;
                                    $iProfileNameColour = Config::get($sConfigString);

                                    if($oPM -> message_read == 0)
                                    {
                                        $iReadIcon = 'file_unread.png';
                                    }
                                    else
                                    {
                                        $iReadIcon = 'file_read.png';
                                    }

                                    echo '<tbody>
                                            <tr align = "center">
                                                <td class = "tdtest remove_mobile" style  "background-color: #EAEAEB !important;"><img src = "resources/images/thread-images/' . $iReadIcon . '" /></td>
                                                <td class = "tdtest remove_mobile" style = "background-color: #EAEAEB !important;"><img src = "resources/images/thread-images/' . $oPM -> icon . '" /></td>

                                                <td class = "tdtest" align = "left">
                                                    <div>
                                                        <a href = "messages.php?m=' . $oPM -> messageid . '" class = "big-text blue-text">' . htmlentities($oPM -> title) . '</a>
                                                    </div>

                                                    <a href = "member.php?u=' . $oPM -> senderid . '" class = "small-text" style = "color: ' . $iProfileNameColour . ';">' . htmlentities($qSenderDetails -> first() -> profilename) . '</a>
                                                </td>

                                                <td class = "tdtest">
                                                    <a href = "messages.php?do=delete&m=' . $oPM -> messageid . '">
                                                        <span class = "fa-stack fa-lg">
                                                            <i class = "fa fa-square fa-stack-2x" style = "color: red;"></i>
                                                            <i class = "fa fa-times fa-stack-1x fa-inverse"></i>
                                                        </span>
                                                    </a>
                                                </td>

                                                <!--<td class = "tdtest">
                                                    <input type = "checkbox" />
                                                </td>-->
                                            </tr>
                                        </tbody>';
                                }
                            }

                        echo '</table><div class = "row-buffer-10"></div>';
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
