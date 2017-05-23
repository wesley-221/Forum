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
            $iPermission = $dbPermission;;
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
        <title>Memberlist</title>

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

        <table cellpadding = "4" cellspacing = "1" width = "100%" align = "center">
                <thead>
                    <tr class = "border">
                        <td class = "groupname" colspan = "4"><b style = "color: white;"><?php echo Config::get('config/forum_name/name'); ?>: Members list</b></td>
                    </tr>

                    <tr class = "tophead" align = "center">
                        <td class = "border" width = "25%" style = "padding: 4px" align = "left"><b><span class = "white-text">Forum name</span></b></td>
                        <td class = "border" width = "25%" style = "padding: 4px"><b><span class = "white-text">Posts</span></b></td>
                        <td class = "border" width = "25%" style = "padding: 4px"><b><span class = "white-text">Reputation</span></b></td>
                        <td class = "border" width = "25%" style = "padding: 4px"><b><span class = "white-text">Profile Picture</span></b></td>
                    </tr>
                </thead>

                <?php
                    $qUsers = DB::getInstance() -> query('SELECT userid, username, profilename, userdescription, permission, reputation FROM users');

                    $iCurrentPost = 1; // counting posts, 10 per page
                    $iCurrentPage = isset($_GET['page']) ? $_GET['page'] : '1';

                    foreach($qUsers -> results() as $oUser)
                    {
                        if($iCurrentPage == 1)
                        {
                            if($iCurrentPost >= $iCurrentPage && $iCurrentPost <= ($iCurrentPage * 10) - 1)
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

                            if($iCurrentPost >= ($iCurrentPage * 10) - 10 && $iCurrentPost <= ($iCurrentPage * 10) -1)
                            {

                            }
                            else
                            {
                                break;
                            }
                        }

                        $qPosts = DB::getInstance() -> query('SELECT count(*) AS totalposts FROM posts WHERE userid = ?', array($oUser -> userid));

                        foreach(Config::get('config/allowed_extensions/') as $extension)
                        {
                            if(file_exists('resources/images/profile_pictures/' . $oUser -> username . '.' . $extension))
                            {
                                $finalImageUrl = 'resources/images/profile_pictures/' . $oUser -> username . '.' . $extension;
                                break;
                            }
                            else
                            {
                                $finalImageUrl = 'resources/images/profile_pictures/unknown.jpg';
                            }
                        }

                        if(file_exists($finalImageUrl))
                        {
                            $sProfilePicture = $finalImageUrl;
                        }

                        $sConfigString = 'config/color_codes/' . $oUser -> permission;
                        $iProfileNameColour = Config::get($sConfigString);

                        echo '
                            <tbody>
                                <tr align = "center">
                                    <td class = "tdtest" align = "left">
                                        <div>
                                            <a href = "member.php?u=' . $oUser -> userid . '" style = "color: ' . $iProfileNameColour . '" class = "big-text">' . $oUser -> profilename . '</a> <br />
                                            <span class = "small-text">' . $oUser -> userdescription . '</span>
                                        </div>
                                    </td>
                                    <td class = "tdtest">' . $qPosts -> first() -> totalposts . '</td>
                                    <td class = "tdtest">' . $oUser -> reputation . '</td>
                                    <td class = "tdtest" style = "padding: 4px;"><img src = "' . $sProfilePicture . '" height = "70" width = "70" /></td>
                                </tr>
                            </tbody>';

                        $iCurrentPost ++;
                    }
                ?>
            </table>

            <?php
                $qTotalUsers = DB::getInstance() -> query('SELECT count(*) AS totalusers FROM users');
                $iTotalUsers = $qTotalUsers -> first() -> totalusers;
                $iTotalUsers = ceil($iTotalUsers / 10);

                $iCurPage = isset($_GET['page']) ? $_GET['page'] : '1';

                $iPrevPage = ($iCurPage - 1);
                    if($iPrevPage <= 0) $iPrevPage = 1;

                $iNextPage = ($iCurPage + 1);
                    if($iNextPage >= ($iTotalUsers + 1)) $iNextPage = $iTotalUsers;

                echo '<nav class = "pull-right">
                    <ul class = "pagination">
                        <li>
                            <a href = "memberlist.php?page=1">
                                <span aria-hidden = "true">&laquo;</span>
                            </a>
                        </li>

                        <li>
                            <a href = "memberlist.php?page=' . $iPrevPage . '" aria-label = "Previous">
                                <span aria-hidden = "true">&lsaquo;</span>
                            </a>
                        </li>';

                        if($iTotalUsers >= 5)
                        {
                            for($i = 1; $i <= 5; $i ++)
                            {
                                if($iCurPage == $i)
                                {
                                    echo '<li class = "active"><a href = "memberlist.php?page=' . $i . '">' . $i . '</a></li>';
                                }
                                else
                                {
                                    echo '<li><a href = "memberlist.php?page=' . $i . '">' . $i . '</a></li>';
                                }
                            }

                            if($iCurPage >= 6)
                            {
                                echo '<li class = "active"><a href = "memberlist.php?page=' . $iCurPage . '">' . $iCurPage . '</a></li>';
                            }
                        }
                        else
                        {
                            for($i = 1; $i <= $iTotalUsers; $i ++)
                            {
                                if($iCurPage == $i)
                                {
                                    echo '<li class = "active"><a href = "memberlist.php?page=' . $i . '">' . $i . '</a></li>';
                                }
                                else
                                {
                                    echo '<li><a href = "memberlist.php?page=' . $i . '">' . $i . '</a></li>';
                                }
                            }
                        }

                        echo '<li>
                            <a href = "memberlist.php?page=' . $iNextPage . '" aria-label = "Next">
                                <span aria-hidden = "true">&rsaquo;</span>
                            </a>
                        </li>

                        <li>
                            <a href = "memberlist.php?page=' . $iTotalUsers . '">&raquo;</a>
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
    </body>
</html>
