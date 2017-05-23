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

    if(isset($_GET['a']))
    {
        if(!strcmp($_GET['a'], "removefriend"))
        {
            $qFriends = DB::getInstance() -> query('SELECT friend_userid FROM friends WHERE userid = ? AND friend_userid = ?', array($dbUserid, $_GET['u']));

            if($qFriends -> count() > 0)
            {
                DB::getInstance() -> query('DELETE FROM friends WHERE userid = ? AND friend_userid = ?', array($dbUserid, $_GET['u']));
            }
        }
        else if(!strcmp($_GET['a'], "addfriend"))
        {
            $qFriends = DB::getInstance() -> query('SELECT friend_userid FROM friends WHERE userid = ? AND friend_userid = ?', array($dbUserid, $_GET['u']));

            if($qFriends -> count() == 0)
            {
                DB::getInstance() -> insert('friends', array('userid' => $dbUserid, 'friend_userid' => $_GET['u']));
            }
        }
        else if(!strcmp($_GET['a'], "denyfriend"))
        {
            $qFriends = DB::getInstance() -> query('SELECT userid, friend_userid, denied FROM friends WHERE friend_userid = ? AND userid = ? AND denied = 0', array($dbUserid, $_GET['u']));

            if($qFriends -> count() > 0)
            {
                DB::getInstance() -> query('UPDATE friends SET denied = 1 WHERE userid = ? AND friend_userid = ?', array($_GET['u'], $dbUserid));
                Header('Location: messages.php');
            }
        }
    }
?>

<html>
    <head>
        <title>Member</title>

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
                if(isset($_GET['u']))
                {
                    $qUserData = DB::getInstance() -> query('SELECT username, profilename, userdescription, register_date, register_datetime, born, email, signature, password, salt FROM users WHERE userid = ?', array($_GET['u']));

                    // user exists
                    if($qUserData -> count() > 0)
                    {
						$sUDUsername            = $qUserData -> first() -> username;
	                    $sUDProfilename         = $qUserData -> first() -> profilename;
	                    $sUDUserDescription     = $qUserData -> first() -> userdescription;
	                    $sUDRegisterDate        = $qUserData -> first() -> register_date;
	                    $sUDRegisterTime        = $qUserData -> first() -> register_datetime;
	                    $sUDBorn                = $qUserData -> first() -> born;
	                    $sUDEmail               = $qUserData -> first() -> email;
	                    $sUDSignature           = $qUserData -> first() -> signature;
	                    $sUDPassword            = $qUserData -> first() -> password;
	                    $sUDSalt                = $qUserData -> first() -> salt;

                        // delete avatar if needed
                        if(isset($_GET['d']))
                        {
                            if($iPermission >= 2 || $dbUserid == $_GET['u'])
                            {
                                foreach(Config::get('config/allowed_extensions/') as $extension)
                                {
                                    if(file_exists('resources/images/profile_pictures/' . $qUserData -> first() -> username . '.' . $extension))
                                    {
                                        unlink('resources/images/profile_pictures/' . $qUserData -> first() -> username . '.' . $extension);
                                    }
                                }
                            }
                        }

                        $arrBornSplit = explode('-', $qUserData -> first() -> born);
                        $dtBornYear =   $arrBornSplit[0];
                        $dtBornMonth =  $arrBornSplit[1];
                        $dtBornDay =    $arrBornSplit[2];

                        $arrMonths = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
                        $iMonthCount = 1;

                        if($_SERVER['REQUEST_METHOD'] == 'POST')
                        {
                            if(isset($_POST['btnSubmit']))
                            {
                                if($dbUserid == $_GET['u'] || $iPermission >= 2)
                                {
                                    $sProfileName       = isset($_POST['profilename']) ? $_POST['profilename'] : '';
                                    $sUserDescription   = isset($_POST['userdescription']) ? $_POST['userdescription'] : '';
                                    $sPassword          = isset($_POST['password']) ? $_POST['password'] : '';
                                    $sPasswordConfirm   = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
                                    $dtBornDayInput     = isset($_POST['born_day']) ? $_POST['born_day'] : '';
                                    $dtBornMonthInput   = isset($_POST['born_month']) ? $_POST['born_month'] : '';
                                    $dtBornYearInput    = isset($_POST['born_year']) ? $_POST['born_year'] : '';
                                    $sEmail             = isset($_POST['email']) ? $_POST['email'] : '';
                                    $sSignature         = isset($_POST['signature']) ? $_POST['signature'] : '';
                                    $iSubmitPermission  = isset($_POST['permission']) ? $_POST['permission'] : '';

                                    if($dtBornMonthInput < 10)
                                        $dtBornMonthInput = '0' . $dtBornMonthInput;

                                    if($dtBornDayInput < 10)
                                        $dtBornDayInput = '0' . $dtBornDayInput;

                                    $dtBorn =           $dtBornYearInput . '-' . $dtBornMonthInput . '-' . $dtBornDayInput;

                                    $iChangePassword = 0; $iChangeProfileName = 0; $iChangeUserDescription = 0;
                                    $iChangeEmail = 0; $iChangeSignature = 0; $iChangeBorn = 0; $iChangeImage = 0;
                                    $sError_Message = "";

                                    $arrAllowedExtensions = Config::get('config/allowed_extensions/');

                                    //echo basename($_FILES['picture']['name']);
                                    if(isset($_FILES['picture']['name']))
                                    {
                                        if(is_uploaded_file($_FILES['picture']['tmp_name']))
                                        {
                                            $fileImageType = pathinfo(basename($_FILES['picture']['name']), PATHINFO_EXTENSION);

                                            if($_FILES['picture']['size'] > 153600)
                                            {
                                                $sError_Message .= '- Invalid image size. The size must be between 0 and 150 KB. <br />';
                                            }
                                            else
                                            {
                                                $iChangeImage = 1;
                                            }

                                            if(!in_array($fileImageType, $arrAllowedExtensions))
                                            {
                                                $sError_Message .= '- Invalid image extension. Only allowed extension: png, jpg, gif, jpeg, bmp. <br />';
                                            }
                                        }
                                    }

                                    // new profilename
                                    $qProfileNameCheck = DB::getInstance() -> query('SELECT profilename FROM users WHERE profilename = ?', array($sProfileName));
                                    if($qProfileNameCheck -> count() == 0)
                                    {
                                        if(strcmp($sProfileName, $sUDProfilename))
                                        {
                                            if(strcmp($sProfileName, ""))
                                            {
                                                if(strlen($sProfileName) <= 3 || strlen($sProfileName) >= 20)
                                                {
                                                    $sError_Message .= '- You have to enter a forum name. Make sure it is atleast 3 characters and at most 20 characters. <br />';
                                                }
                                                else
                                                {
                                                    $iChangeProfileName = 1;
                                                }
                                            }
                                        }
                                    }
                                    else
                                    {
                                        $sError_Message .= '- The profilename "' . $sProfileName . '" is already in use. Please try a different one. <br />';
                                    }

                                    if(!strcmp($sPassword, $sPasswordConfirm))
                                    {
                                        if(!strcmp($sPassword, "password") || !strcmp($sPasswordConfirm, "password"))
                                        {
                                            // do nothing
                                        }
                                        else
                                        {
                                            $sHashedPassword = hash('whirlpool', $sPassword . $sUDSalt);

                                            if(strcmp($sHashedPassword, $sUDPassword))
                                            {
                                                if(strcmp($sPassword, ""))
                                                {
                                                    if(strlen($sPassword) <= 3)
                                                    {
                                                        $sError_Message .= '- The password has to be atleast 3 characters. <br />';
                                                    }
                                                    else
                                                    {
                                                        $iChangePassword = 1;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    else
                                    {
                                        $sError_Message .= '- The two passwords you entered do not match. Please try again. <br />';
                                    }

                                    // new userdescription
                                    if(strcmp($sUserDescription, $sUDUserDescription))
                                    {
                                        if(strlen($sUserDescription) <= 3 || strlen($sUserDescription) >= 50)
                                        {
                                            $sError_Message .= '- You have to enter a user description. Make sure it is atleast 3 characters and at most 50 characters. <br />';
                                        }
                                        else
                                        {
                                            $iChangeUserDescription = 1;
                                        }
                                    }

                                    // new email
                                    if(strcmp($sEmail, $sUDEmail))
                                    {
                                        if(strcmp($sEmail, ""))
                                        {
                                            if(!filter_var($sEmail, FILTER_VALIDATE_EMAIL))
                                            {
                                                $sError_Message .= '- You have to enter a valid email address  <br />';
                                            }
                                            else
                                            {
                                                $iChangeEmail = 1;
                                            }
                                        }
                                    }

                                    // new signature
                                    if(strcmp($sSignature, $sUDSignature))
                                    {
                                        if(strlen($sUserDescription) <= 3 || strlen($sUserDescription) >= 50)
                                        {
                                            $sError_Message .= '- You have to enter a user description. Make sure it is atleast 3 characters and at most 50 characters. <br />';
                                        }
                                        else
                                        {
                                            $iChangeSignature = 1;
                                        }
                                    }

                                    // new born
                                    if(strcmp($dtBorn, $sUDBorn))
                                        $iChangeBorn = 1;

                                    if(strlen($sError_Message) > 0)
                                    {
                                        echo '
                                            <table cellpadding = "2" cellspacing = "1" width = "50%" align = "center">
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
                                                            <b>You were unable change your information because of the following reason(s):</b> <br />
                                                            ' . $sError_Message . '
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table> <div class = "row-buffer-5"></div>';
                                    }
                                    else
                                    {
                                        $arrValues = array();

                                        if($iChangeProfileName == 1)
                                        {
                                            $arrValues['profilename'] = $sProfileName;
                                            $arrValues['last_profilename'] = $sUDProfilename;
                                        }

                                        if($iChangePassword == 1)
                                            $arrValues['password'] = $sHashedPassword;

                                        if($iChangeUserDescription == 1)
                                            $arrValues['userdescription'] = $sUserDescription;

                                        if($iChangeEmail == 1)
                                            $arrValues['email'] = $sEmail;

                                        if($iChangeSignature == 1)
                                            $arrValues['signature'] = $sSignature;

                                        if($iChangeBorn == 1)
                                            $arrValues['born'] = $dtBorn;

                                        $arrValues['permission'] = $iSubmitPermission;

                                        if($iChangeImage == 1)
                                        {
                                            foreach(Config::get('config/allowed_extensions/') as $extension)
                                            {
                                                if(file_exists('resources/images/profile_pictures/' . $sUDUsername . '.' . $extension))
                                                {
                                                    unlink('resources/images/profile_pictures/' . $sUDUsername . '.' . $extension);
                                                }
                                            }


                                            move_uploaded_file($_FILES['picture']['tmp_name'], 'resources/images/profile_pictures/' . $sUDUsername . '.' . $fileImageType);
                                        }

                                        $qSaveSettings = DB::getinstance() -> update('users', $arrValues,
                                                                array(
                                                                    'userid', '=', $_GET['u']
                                                               ));

                                        //echo '<script type = "text/javascript">window.location.href = "member.php?u=' . $_GET['u'] . '";</script>';
                                    }
                                }
                            }
                        }

                        $qAllRanks = DB::getInstance() -> query('SELECT permission, permission_name FROM permissions');
                        $arrAllRanks = array();

                        foreach($qAllRanks -> results() as $oRank)
                        {
                            $arrAllRanks[$oRank -> permission] = $oRank -> permission_name;
                        }

                        $qUserData = DB::getInstance() -> query('SELECT username, profilename, last_profilename, userdescription, register_date, register_datetime, born, email, signature, password, salt, permission FROM users WHERE userid = ?', array($_GET['u']));

                        $sLastKnown = (strlen($qUserData -> first() -> last_profilename) > 0) ? $qUserData -> first() -> last_profilename : 'N/A';

                        echo '
                        <form method = "post" action = "member.php?u=' . $_GET['u'] . '" enctype = "multipart/form-data">
                            <table cellpadding = "2" width = "100%" align = "center">
                                <tbody>
                                    <tr>
                                        <td><a href = "sendmessage.php?u=' . $_GET['u'] . '" class = "btn btn-primary"><i class="fa fa-envelope"></i> Send a private message</a></td>
                                    </tr>

                                    <tr>
                                        <td><div class = "row-buffer-3"></div></td>
                                    </tr>

                                    <tr valign = "top">
                                        <td width = "70%" class = "table-resize">
                                            <table cellpadding = "2" width = "100%" align = "center">
                                                <thead>
                                                    <tr class = "border">
                                                        <td class = "groupname border" colspan = "2">
                                                            <b class = "white-text">Userpage of ' . $qUserData -> first() -> profilename . ', last known as ' . $sLastKnown . '</b>
                                                        </td>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <tr>
                                                        <td class = "postinfo">Profile picture</td>';

                                                        $finalPictureUrl = '';
                                                        foreach(Config::get('config/allowed_extensions/') as $extension)
                                                        {
                                                            if(file_exists('resources/images/profile_pictures/' . $qUserData -> first() -> username . '.' . $extension))
                                                            {
                                                                $finalPictureUrl = 'resources/images/profile_pictures/' . $qUserData -> first() -> username . '.' . $extension;
                                                                break;
                                                            }
                                                            else
                                                            {
                                                                $finalPictureUrl = 'resources/images/profile_pictures/unknown.jpg';
                                                            }
                                                        }

                                                    if(file_exists($finalPictureUrl))
                                                    {
                                                        echo '<td class = "postinfo" colspan = "2"><img src = "' . $finalPictureUrl . '" /><div class = "row-buffer-3"></div>';

                                                        if($dbUserid == $_GET['u'])
                                                        {
                                                          echo '<input type = "file" name = "picture" />';
                                                        }

                                                        echo '</td>';
                                                    }

                                                    echo '</tr>';

                                                    if($dbUserid == $_GET['u'] || $iPermission >= 2)
                                                    {
                                                        echo '<tr>
                                                                <td class = "postinfo"></td><td class = "postinfo"><a href = "member.php?u=' . $_GET['u'] . '&d=ava" class = "blue-text">Remove this profile picture</a></td>
                                                            </tr>';
                                                    }

                                                    if($dbUserid == $_GET['u'] || $iPermission >= 2)
                                                    {
                                                        echo '<tr>
                                                            <td class = "postinfo">Username</td>
                                                            <td class = "postinfo"><input type = "text" name = "username" class = "form-control" value = "' . $qUserData -> first() -> username . '" disabled /></td>
                                                        </tr>';
                                                    }

                                                    if($dbUserid == $_GET['u'] || $iPermission >= 2)
                                                    {
                                                        echo '<tr>
                                                            <td class = "postinfo">Forum name</td>
                                                            <td class = "postinfo"><input type = "text" name = "profilename" class = "form-control" placeholder = "' . $qUserData -> first() -> profilename . '" /></td>
                                                        </tr>

                                                        <tr>
                                                            <td class = "postinfo">Userdescription</td>
                                                            <td class = "postinfo"><input type = "text" name = "userdescription" class = "form-control" value = "' . $qUserData -> first() -> userdescription . '" /></td>
                                                        </tr>';
                                                    }
                                                    else
                                                    {
                                                        echo '<tr>
                                                            <td class = "postinfo">Forum name</td>
                                                            <td class = "postinfo"><input type = "text" name = "profilename" class = "form-control" value = "' . $qUserData -> first() -> profilename . '" disabled /></td>
                                                        </tr>

                                                        <tr>
                                                            <td class = "postinfo">Userdescription</td>
                                                            <td class = "postinfo"><input type = "text" name = "userdescription" class = "form-control" value = "' . $qUserData -> first() -> userdescription . '" disabled /></td>
                                                        </tr>';
                                                    }

                                                    if($dbUserid == $_GET['u'] || $iPermission >= 3)
                                                    {
                                                        echo '
                                                            <tr>
                                                                <td class = "postinfo">Password</td>

                                                                <td class = "postinfo">
                                                                    <div class = "panel-header">
                                                                        <a class = "no-underline" data-toggle = "collapse" href = "#collapseOne">
                                                                            <div class = "panel-heading panel-border">
                                                                                <h4 class = "panel-title" align = "center">
                                                                                    <i>Password</i>
                                                                                </h4>
                                                                            </div>
                                                                        </a>

                                                                        <div id = "collapseOne" class = "panel-collapse collapse panel-text">
                                                                            <div class = "panel-body">
                                                                                <table cellpadding = "2" width = "100%" align = "center">
                                                                                    <tr>
                                                                                        <td>Password</td>
                                                                                        <td><input type = "password" name = "password" class = "form-control" placeholder = "password" /></td>
                                                                                    </tr>

                                                                                    <tr>
                                                                                        <td>Password confirmation</td>
                                                                                        <td><input type = "password" name = "password_confirm" class = "form-control" placeholder = "password confirmation" /></td>
                                                                                    </tr>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>';
                                                    }

                                                    if($iPermission >= 3)
                                                    {
                                                        echo '
                                                            <tr>
                                                                <td class = "postinfo">
                                                                    Permission
                                                                </td>

                                                                <td class = "postinfo">
                                                                    <select name = "permission" class = "form-control">';
                                                                        for($i = 0; $i < count($arrAllRanks); $i ++)
                                                                        {
                                                                            if($qUserData -> first() -> permission == $i)
                                                                            {
                                                                                echo '<option value = "' . $i . '" selected>' . $arrAllRanks[$i] . '</option>';
                                                                            }
                                                                            else
                                                                            {
                                                                                echo '<option value = "' . $i . '">' . $arrAllRanks[$i] . '</option>';
                                                                            }
                                                                        }

                                                                    echo '</select>
                                                                </td>
                                                            </tr>';
                                                    }
                                                    else
                                                    {
                                                        echo '
                                                            <tr>
                                                                <td class = "postinfo">Permission</td>
                                                                <td class = "postinfo"><input type = "text" class = "form-control" value = "' . $arrAllRanks[$qUserData -> first() -> permission] . '" disabled /></td>
                                                            </tr>';
                                                    }

                                                    echo '<tr>
                                                        <td class = "postinfo">Register date</td>
                                                        <td class = "postinfo"><input type = "text" class = "form-control" value = "' . $qUserData -> first() -> register_date . '" disabled /></td>
                                                    </tr>

                                                    <tr>
                                                        <td class = "postinfo">Register time</td>
                                                        <td class = "postinfo"><input type = "text" class = "form-control" value = "' . $qUserData -> first() -> register_datetime . '" disabled /></td>
                                                    </tr>

                                                    <tr>
                                                        <td class = "postinfo">Born</td>';

														if($dbUserid == $_GET['u'])
                                                        {
                                                            echo '<td class = "postinfo">
                                                                    <table>
                                                                        <tr>
                                                                            <td>
                                                                                <select name = "born_day" class = "form-control">';
                                                                                    for($i = 1; $i <= 31; $i ++)
                                                                                    {
                                                                                        if($i == $dtBornDay)
                                                                                        {
                                                                                            echo '<option value = "' . $i . '" selected>' . $i . '</option>';
                                                                                        }
                                                                                        else
                                                                                        {
                                                                                            echo '<option value = "' . $i . '">' . $i . '</option>';
                                                                                        }
                                                                                    }
                                                                                echo '</select>
                                                                            </td>

                                                                            <td>
                                                                                <select name = "born_month" class = "form-control">';

                                                                                    foreach($arrMonths as $month)
                                                                                    {
                                                                                        if($iMonthCount == $dtBornMonth)
                                                                                        {
                                                                                            echo '<option value = "' . $iMonthCount . '" selected>' . $month . '</option>';
                                                                                        }
                                                                                        else
                                                                                        {
                                                                                            echo '<option value = "' . $iMonthCount . '">' . $month . '</option>';
                                                                                        }

                                                                                        $iMonthCount ++;
                                                                                    }
                                                                                echo '</select>
                                                                            </td>

                                                                            <td>
                                                                                <select name = "born_year" class = "form-control">';
                                                                                    for($i = date('Y') - 100; $i <= date('Y') + 100; $i ++)
                                                                                    {
                                                                                        if($i == $dtBornYear)
                                                                                        {
																							echo '<option value = "' . $i . '" selected>' . $i . '</option>';
                                                                                        }
                                                                                        else
                                                                                        {
                                                                                            echo '<option value = "' . $i . '">' . $i . '</option>';
                                                                                        }
                                                                                    }
                                                                                echo '</select>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>';
                                                        }
                                                        else
                                                        {
                                                            echo '<td class = "postinfo"><input type = "text" class = "form-control" value = "' . $dtBornDay . '-' . $dtBornMonth . '-' . $dtBornYear . '" disabled /></td>';
                                                        }
                                                    echo '</tr>';

                                                    if($dbUserid == $_GET['u'] || $iPermission >= 3)
                                                    {
                                                        echo '<tr>
                                                            <td class = "postinfo">Email</td>
                                                            <td class = "postinfo"><input type = "text" name = "email" class = "form-control" placeholder = "' . $qUserData -> first() -> email . '" /></td>
                                                        </tr>';
                                                    }

                                                    if($dbUserid == $_GET['u'] || $iPermission >= 3)
                                                    {
                                                        echo '<tr>
                                                            <td class = "postinfo">Signature</td>
                                                            <td class = "postinfo"><textarea name = "signature" class = "form-control" rows = "6" >' . $qUserData -> first() -> signature . '</textarea></td>
                                                        </tr>';
                                                    }
                                                    else
                                                    {
                                                        echo '<tr>
                                                            <td class = "postinfo">Signature</td>
                                                            <td class = "postinfo"><textarea name = "signature" class = "form-control" rows = "6" style = "resize: vertical;" disabled>' . $qUserData -> first() -> signature . '</textarea></td>
                                                        </tr>';
                                                    }

                                                    if($dbUserid == $_GET['u'] || $iPermission >= 2)
                                                    {
                                                        echo '<tr>
                                                                <td class = "postinfo"></td><td class = "postinfo" align = "right"><div class = "row-buffer-3"></div><button type = "submit" class = "btn btn-primary" name = "btnSubmit">Save</button></td>
                                                            </tr>';
                                                    }
                                                echo '</tbody>
                                            </table>
                                        </td>

                                        <td valign = "left" class = "table-resize" style = "padding-left: 8px">
                                            <table cellpadding = "2" align = "center" width = "100%">
                                                <thead>
                                                    <tr class = "border">
                                                        <td class = "groupname border" colspan = "2">
                                                            <a href = "friends.php?u=' . $_GET['u'] . '"><b class = "white-text">Friends</b><span class = "pull-right" style = "padding-right: 4px;">Show all friends</span></a>
                                                        </td>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <tr>
                                                        <td class = "postinfo">';
                                                        if($dbUserid != $_GET['u'] && $iLoggedIn == 1)
                                                        {
                                                            $qFriends = DB::getInstance() -> query('SELECT friend_userid FROM friends WHERE userid = ? AND friend_userid = ?', array($dbUserid, $_GET['u']));

                                                            if($qFriends -> count() > 0)
                                                            {
                                                                $iCurrentUserFriends = 1;
                                                            }
                                                            else
                                                            {
                                                                $iCurrentUserFriends = 0;
                                                            }

                                                            $qFriends = DB::getInstance() -> query('SELECT friend_userid FROM friends WHERE userid = ? AND friend_userid = ?', array($_GET['u'], $dbUserid));

                                                            if($qFriends -> count() > 0)
                                                            {
                                                                $iTargetUserFriends = 1;
                                                            }
                                                            else
                                                            {
                                                                $iTargetUserFriends = 0;
                                                            }

                                                            if($iCurrentUserFriends == 1 && $iTargetUserFriends == 1)
                                                            {
                                                                echo '<a href = "member.php?u=' . $_GET['u'] . '&a=removefriend"><div class = "friend-mutual">
                                                                        <center><i class = "fa fa-heart"></i> <b>Mutual friend</b></center>
                                                                    </div></a>';
                                                            }
                                                            else if($iCurrentUserFriends == 0)
                                                            {
                                                                echo '<a href = "member.php?u=' . $_GET['u'] . '&a=addfriend"><div class = "no-friend">
                                                                    <center><i class = "fa fa-plus-square"></i> <b>Add friend</b></center>
                                                                </div></a>';
                                                            }
                                                            else
                                                            {
                                                                echo '<a href = "member.php?u=' . $_GET['u'] . '&a=removefriend"><div class = "friend-non-mutual">
                                                                        <center><i class = "fa fa-star"></i> <b>Friend</b></center>
                                                                    </div></a>';
                                                            }
                                                        }

                                                    echo '</td>
                                                    </tr>
                                                </tbody>

                                                <tbody>
                                                    <tr>
                                                        <td class = "postinfo">
                                                            <div>';
                                                            $qAllFriends = DB::getInstance() -> query('SELECT friend_userid FROM friends WHERE userid = ?', array($_GET['u']));

                                                            if($qAllFriends -> count() > 0)
                                                            {
                                                                $iCount = 0;
                                                                foreach($qAllFriends -> results() as $oFriend)
                                                                {
                                                                    if($iCount == 16)
                                                                        break;

                                                                    $iTargetUser = $oFriend -> friend_userid;
                                                                    $qTargetUsername = DB::getInstance() -> query('SELECT userid, username FROM users WHERE userid = ?', array ($iTargetUser));
                                                                    $sTargetUsername = $qTargetUsername -> first() -> username;

                                                                    $finalImgUrl = '';

                                                                    foreach(Config::get('config/allowed_extensions/') as $extension)
                                                                    {
                                                                        if(file_exists('resources/images/profile_pictures/' . $sTargetUsername . '.' . $extension))
                                                                        {
                                                                            $finalImgUrl = 'resources/images/profile_pictures/' . $sTargetUsername . '.' . $extension;
                                                                            break;
                                                                        }
                                                                        else
                                                                        {
                                                                            $finalImgUrl = 'resources/images/profile_pictures/unknown.jpg';
                                                                        }
                                                                    }


                                                                    echo '<div style = "max-width: 80px; float: left; padding-left: 5px; margin-right: 3px;">
                                                                            <a href = "member.php?u=' . $qTargetUsername -> first() -> userid . '" class = "blue-text no-underline">
                                                                                <img src = "' . $finalImgUrl . '" heigth = "80" width = "80" /> <br />
                                                                                <center><u>' . $sTargetUsername . '</u></center>
                                                                            </a>
                                                                        </div>';
                                                                    $iCount ++;
                                                                }
                                                            }
                                                            else
                                                            {
                                                                echo '<i>This user has no friends :(</i>';
                                                            }

                                                        echo '</table>
                                                        </td>
                                                    </tr>
                                                </tbody>
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
                                       <td class = "postinfo extra-padding" style = "font-size: 16px;">This user is not registered. No profile is available for this user to view.</td>
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
                                   <td class = "postinfo extra-padding" style = "font-size: 16px;">This user is not registered. No profile is available for this user to view.</td>
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
