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
        <title>Edit forum elements</title>

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
                if(isset($_GET['p']))
                {
                    // does post exist
                    $qPostDetails = DB::getInstance() -> query('SELECT userid, title, body, threadid FROM posts WHERE postid = ?', array($_GET['p']));

                    if($qPostDetails -> count())
                    {
                        $iPostDetailsUserId = $qPostDetails -> first() -> userid;
                        $sPostDetailsTitle = $qPostDetails -> first() -> title;
                        $sPostDetailsBody = $qPostDetails -> first() -> body;
                        $iPostDetailsThreadID = $qPostDetails -> first() -> threadid;
                        // is current user owner of the post

                        if($dbUserid == $qPostDetails -> first() -> userid || $iPermission >= 1)
                        {
                            if($_SERVER['REQUEST_METHOD'] == 'POST')
                            {
                                $sSubject = isset($_POST['subject']) ? $_POST['subject'] : '';
                                $sMessage = isset($_POST['reply_message']) ? $_POST['reply_message'] : '';
                                $sEditorMessage = isset($_POST['editor_message']) ? $_POST['editor_message'] : '';

                                if(strlen($sSubject) >= 5 && strlen($sMessage) >= 5)
                                {
                                    $dtDate     = date("Y-m-d");
                                    $dtTime     = date("H:i:s");

                                    $qUpdatePost = DB::getInstance() -> update("posts", array(
                                                            "title"             => $sSubject,
                                                            "body"              => $sMessage,
                                                            "lastedit_userid"   => $dbUserid,
                                                            "lastedit_date"     => $dtDate,
                                                            "lastedit_time" => $dtTime,
                                                            "lastedit_message"  => $sEditorMessage
                                                       ), array (
                                                            "postid", "=", $_GET['p']
                                                       ));

                                    Header('location: showthread.php?t=' . $iPostDetailsThreadID . '&p=' . $_GET['p']);
                                }
                                else
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
                                }
                            }

                            echo '
                                <form action = "editelement.php?p=' . $_GET['p'] . '" method = "post">
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
                                                            <td>Title:</td>
                                                        </tr>

                                                        <tr>
                                                            <td><input type = "text" name = "subject" class = "form-control" size = "40" tabindex = "1" value = "' . htmlentities($sPostDetailsTitle) . '"/> <div class = "row-buffer-2"></div></td>
                                                        </tr>

                                                        <tr>
                                                            <td>Editors note:</td>
                                                        </tr>

                                                        <tr>
                                                            <td><input type = "text" name = "editor_message" class = "form-control" size = "40" tabindex = "2" autofocus /> <div class = "row-buffer-2"></div></td>
                                                        </tr>

                                                        <tr>
                                                            <td>Message:</td><td><b>BB codes</b></td>
                                                        </tr>

                                                        <tr>
                                                            <td><textarea cols = "100%" rows = "10" name = "reply_message" class = "form-control" tabindex = "3">' . htmlentities($sPostDetailsBody) . '</textarea></td>

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
                                                            <td align = "center"><button type = "submit" class = "btn btn-info" tabindex = "4">Edit message</button></td>
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
                else if(isset($_GET['f']))
                {
                    $qSubForumDetails = DB::getInstance() -> query('SELECT subforumname, subforumdescription FROM subforum WHERE subforumid = ?', array($_GET['f']));

                    if($qSubForumDetails -> count() > 0)
                    {
                        if($iPermission >= 1)
                        {
                            $sSubForumName = $qSubForumDetails -> first() -> subforumname;
                            $sSubForumDescription = $qSubForumDetails -> first() -> subforumdescription;

                            if($_SERVER['REQUEST_METHOD'] == 'POST')
                            {
                                $sEditSubForumName          = isset($_POST['subforumname']) ? $_POST['subforumname'] : '';
                                $sEditSubForumDescription   = isset($_POST['subforumdescription']) ? $_POST['subforumdescription'] : '';

                                if(strlen($sEditSubForumName) >= 4 && strlen($sEditSubForumDescription) >= 4)
                                {
                                    $qUpdatePost = DB::getInstance() -> update("subforum", array(
                                                            "subforumname"             => $sEditSubForumName,
                                                            "subforumdescription"      => $sEditSubForumDescription,
                                                       ), array (
                                                            "subforumid", "=", $_GET['f']
                                                       ));

                                    Header('location: index.php');
                                }
                                else
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
                                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">The title and/or message is too short. Please extend the title and/or message to atleast 4 characters. </td>
                                                </tr>
                                            </tbody>
                                        </table> <div class = "row-buffer-5"></div>';
                                }
                            }

                            echo '
                                <form action = "editelement.php?f=' . $_GET['f'] . '" method = "post">
                                    <table cellpadding = "2" cellspacing = "1" width = "100%" align = "center">
                                        <thead>
                                            <tr class = "border">
                                                <td class = "groupname" colspan = "2">
                                                    <b class = "white-text">Edit a Sub forum</b>
                                                </td>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr align = "center">
                                                <td colspan = "2" class = "postinfo">
                                                    <table>
                                                        <tr>
                                                            <td>Sub forum name</td>
                                                        </tr>

                                                        <tr>
                                                            <td><input type = "text" name = "subforumname" class = "form-control" size = "40" tabindex = "1" value = "' . htmlentities($sSubForumName) . '" autofocus /> <div class = "row-buffer-2"></div></td>
                                                        </tr>

                                                        <tr>
                                                            <td><div class = "row-buffer-3"></div>Sub forum description</td>
                                                        </tr>

                                                        <tr>
                                                            <td><input type = "text" name = "subforumdescription" class = "form-control" size = "40" tabindex = "2" autofocus value = "' . htmlentities($sSubForumDescription) . '" /> <div class = "row-buffer-2"></div></td>
                                                        </tr>

                                                        <tr>
                                                            <td align = "center"><button type = "submit" class = "btn btn-info" tabindex = "4">Edit Sub forum</button></td>
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
                else if(isset($_GET['t']))
                {
                    $qThreadDetails = DB::getInstance() -> query('SELECT userid, title, sticky, locked, icon FROM threads WHERE threadid = ?', array($_GET['t']));

                    if($qThreadDetails -> count())
                    {
                        if($iPermission >= 1 ||  $qThreadDetails -> first() -> userid == $dbUserid)
                        {
                            $sDBThreadTitle = $qThreadDetails -> first() -> title;
                            $iDBUserId      = $qThreadDetails -> first() -> userid;
                            $iDBSticky      = $qThreadDetails -> first() -> sticky;
                            $iDBLocked      = $qThreadDetails -> first() -> locked;
                            $sDBIcon        = $qThreadDetails -> first() -> icon;

                            $iDBSticky = ($iDBSticky == 1) ? 'checked' : '';
                            $iDBLocked = ($iDBLocked == 1) ? 'checked' : '';

                            if($_SERVER['REQUEST_METHOD'] == 'POST')
                            {
                                $sThreadTitle           = isset($_POST['title']) ? $_POST['title'] : '';
                                $iLocked                = isset($_POST['locked']) ? $_POST['locked'] : '';
                                $iSticky                = isset($_POST['sticky']) ? $_POST['sticky'] : '';
                                $sIcon                  = isset($_POST['pic']) ? $_POST['pic'] : '';

                                if(strlen($sThreadTitle) >= 4)
                                {
                                    $iLocked = (!strcmp($iLocked, 'on')) ? '1' : '0';
                                    $iSticky = (!strcmp($iSticky, 'on')) ? '1' : '0';

                                    $qUpdateThread = DB::getInstance() -> update("threads", array(
                                                            "title"             => $sThreadTitle,
                                                            "sticky"            => $iSticky,
                                                            "locked"            => $iLocked,
                                                            "icon"              => $sIcon
                                                       ), array (
                                                            "threadid", "=", $_GET['t']
                                                       ));

                                    Header('location: showthread.php?t=' . $_GET['t']);
                                }
                                else
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
                                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">The title and/or message is too short. Please extend the title and/or message to atleast 4 characters. </td>
                                                </tr>
                                            </tbody>
                                        </table> <div class = "row-buffer-5"></div>';
                                }
                            }

                            echo '
                                <form action = "editelement.php?t=' . $_GET['t'] . '" method = "post">
                                    <table cellpadding = "2" cellspacing = "1" width = "100%" align = "center" >
                                        <thead>
                                            <tr class = "border">
                                                <td class = "groupname" colspan = "2">
                                                    <b class = "white-text">Edit a Thread</b>
                                                </td>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr align = "center">
                                                <td colspan = "2" class = "postinfo">
                                                    <table>
                                                        <tr>
                                                            <td>Thread Title</td>
                                                        </tr>

                                                        <tr>
                                                            <td><input type = "text" name = "title" class = "form-control" size = "40" tabindex = "1" value = "' . htmlentities($sDBThreadTitle) . '" onFocus = "this.setSelectionRange(0, this.value.length)" autofocus /> <div class = "row-buffer-2"></div></td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <table>
                                                                    <tr>
                                                                        <td>Sticky</td> <td><div class = "row-buffer-5"></div></td> <td>Locked</td>
                                                                    </tr>

                                                                    <tr>
                                                                        <td>
                                                                            <input type = "checkbox" ' . $iDBSticky . ' name = "sticky" />
                                                                        </td>

                                                                        <td></td>

                                                                        <td>
                                                                            <input type = "checkbox" ' . $iDBLocked . ' name = "locked" />
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <div class = "row-buffer-3"></div>';
                                                                    foreach(Config::get('config/thread_icons/') as $sIcon)
                                                                    {
                                                                        if(!strcmp($sIcon, $sDBIcon))
                                                                        {
                                                                            echo '<img src = "resources/images/thread-images/' . $sIcon . '" /> <input type = "radio" name = "pic" value = "' . $sIcon . '" checked />';
                                                                        }
                                                                        else
                                                                        {
                                                                            echo '<img src = "resources/images/thread-images/' . $sIcon . '" /> <input type = "radio" name = "pic" value = "' . $sIcon . '" />';
                                                                        }
                                                                    }

                                                                echo '<div class = "row-buffer-3"></div>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td align = "center"><button type = "submit" class = "btn btn-info" tabindex = "4">Edit Sub forum</button></td>
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
                else if(isset($_GET['c']))
                {
                    $qForumDetails = DB::getInstance() -> query('SELECT forumname, permission_required FROM forum WHERE forumid = ?', array($_GET['c']));

                    if($qForumDetails -> count() > 0)
                    {
                        if($iPermission >= $qForumDetails -> first() -> permission_required)
                        {
                            $sForumName = $qForumDetails -> first() -> forumname;
                            $sForumPermission = $qForumDetails -> first() -> permission_required;

                            if($_SERVER['REQUEST_METHOD'] == 'POST')
                            {
                                $sEditForumName = isset($_POST['categoryname']) ? $_POST['categoryname'] : '';
                                $sEditForumPermission = isset($_POST['categorypermission']) ? $_POST['categorypermission'] : '';

                                if(strlen($sForumName) >= 4)
                                {
                                    $qUpdateCategory = DB::getInstance() -> update("forum", array(
                                                            "forumname"             => $sEditForumName,
                                                            "permission_required"   => $sEditForumPermission
                                                       ), array (
                                                            "forumid", "=", $_GET['c']
                                                       ));

                                    Header('location: index.php');
                                }
                                else
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
                                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">The title is too short. Please extend the title to atleast 4 characters. </td>
                                                </tr>
                                            </tbody>
                                        </table> <div class = "row-buffer-5"></div>';
                                }
                            }

                            $qAllPermissions = DB::getInstance() -> query('SELECT permission, permission_name FROM permissions');

                            echo '
                                <form action = "editelement.php?c=' . $_GET['c'] . '" method = "post">
                                    <table cellpadding = "2" cellspacing = "1" width = "100%" align = "center">
                                        <thead>
                                            <tr class = "border">
                                                <td class = "groupname" colspan = "2">
                                                    <b class = "white-text">Edit a forum category</b>
                                                </td>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr align = "center">
                                                <td colspan = "2" class = "postinfo">
                                                    <table>
                                                        <tr>
                                                            <td>Forum category name</td>
                                                        </tr>

                                                        <tr>
                                                            <td><input type = "text" name = "categoryname" class = "form-control" onfocus = "this.select();" size = "40" tabindex = "1" value = "' . htmlentities($sForumName) . '" autofocus /> <div class = "row-buffer-2"></div></td>
                                                        </tr>

                                                        <tr>
                                                            <td>Forum category permission</td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <select name = "categorypermission" class = "form-control">';
                                                            foreach($qAllPermissions -> results() as $oPermission)
                                                            {
                                                                if($oPermission -> permission == $sForumPermission)
                                                                {
                                                                    echo '<option value = "' . $oPermission -> permission . '" selected>' . $oPermission -> permission_name . '</option>';
                                                                }
                                                                else
                                                                {
                                                                    echo '<option value = "' . $oPermission -> permission . '">' . $oPermission -> permission_name . '</option>';
                                                                }
                                                            }

                                                        echo '</select>
                                                                <div class = "row-buffer-3"></div>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td align = "center"><button type = "submit" class = "btn btn-info" tabindex = "4">Edit forum</button></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </form>';
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
