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

<!DOCTYPE html>
<html>
    <head>
        <title>Search</title>

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
                                        <li><a href="search.php?p_username=' . $dbProfilename . '">My posts</a></li>
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
                $sDropdown = isset($_GET['dd_title_post']) ? $_GET['dd_title_post'] : 'N/A';
                $sKeywords = isset($_GET['keywords']) ? $_GET['keywords'] : '';
                $sPUsername = isset($_GET['p_username']) ? $_GET['p_username'] : '';
                $sSearchUser = isset($_GET['username']) ? $_GET['username'] : '';
                $dtSearchFromDate = isset($_GET['fromdate']) ? $_GET['fromdate'] : '';
                $dtSearchToDate = isset($_GET['todate']) ? $_GET['todate'] : '';

                $arrPostId = array();
                $arrUserId = array();

                if(strlen($sDropdown) > 0 || strlen($sKeywords) > 0 || strlen($sPUsername) > 0 || strlen($sSearchUser) > 0)
                {
                    if($sDropdown == 0)
                    {
                        if(strlen($sKeywords) > 0)
                        {
                            $sqKeywords = '%' . $sKeywords . '%';

                            if(strlen($dtSearchToDate) > 0 && strlen($dtSearchFromDate) > 0)
                            {
                                $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE body LIKE ? OR date BETWEEN ? AND ? ORDER BY date, time DESC', array($sqKeywords, $dtSearchFromDate, $dtSearchToDate));
                            }
                            else
                            {
                                $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE body LIKE ?', array($sqKeywords));
                            }
                        }
                        else
                        {
                            if(strlen($dtSearchToDate) > 0 && strlen($dtSearchFromDate) > 0)
                            {
                                $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE date BETWEEN ? AND ? ORDER BY date, time DESC', array($dtSearchFromDate, $dtSearchToDate));
                            }
                            else
                                $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE postid = \'N/A\' ORDER BY date, time DESC');
                        }

                        foreach($qSearch -> results() as $oSearch)
                        {
                            array_push($arrPostId, $oSearch -> postid);
                        }
                    }
                    else if($sDropdown == 1)
                    {
                        if(strlen($sKeywords) > 0)
                        {
                            $sqKeywords = '%' . $sKeywords . '%';

                            if(strlen($dtSearchToDate) > 0 && strlen($dtSearchFromDate) > 0)
                            {
                                $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE title LIKE ? OR date BETWEEN ? AND ? ORDER BY date, time DESC', array($sqKeywords, $dtSearchFromDate, $dtSearchToDate));
                            }
                            else
                            {
                                $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE title LIKE ? ORDER BY date, time DESC', array($sqKeywords));
                            }
                        }
                        else
                        {
                            if(strlen($dtSearchToDate) > 0 && strlen($dtSearchFromDate) > 0)
                            {
                                $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE date BETWEEN ? AND ? ORDER BY date, time DESC', array($dtSearchFromDate, $dtSearchToDate));
                            }
                        }

                        foreach($qSearch -> results() as $oSearch)
                        {
                            array_push($arrPostId, $oSearch -> postid);
                        }
                    }
                    else if($sDropdown == 2)
                    {
                        if(strlen($sKeywords) > 0)
                        {
                            $sqKeywords = '%' . $sKeywords . '%';

                            if(strlen($dtSearchToDate) > 0 && strlen($dtSearchFromDate) > 0)
                            {
                                $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE title LIKE ? OR body LIKE ? OR date BETWEEN ? AND ? ORDER BY date, time DESC', array($sKeywords, $sKeywords, $dtSearchFromDate, $dtSearchToDate));
                            }
                            else
                            {
                                $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE title LIKE ? OR body LIKE ? ORDER BY date, time DESC', array($sqKeywords, $sqKeywords));
                            }
                        }
                        else
                        {
                            if(strlen($dtSearchToDate) > 0 && strlen($dtSearchFromDate) > 0)
                            {
                                $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE date BETWEEN ? AND ? ORDER BY date, time DESC', array($dtSearchFromDate, $dtSearchToDate));
                            }
                        }

                        foreach($qSearch -> results() as $oSearch)
                        {
                            array_push($arrPostId, $oSearch -> postid);
                        }
                    }

                    if(strlen($sPUsername) > 0)
                    {
                        $qUserid = DB::getInstance() -> query('SELECT userid FROM users WHERE profilename = ?', array($sPUsername));
                        if($qUserid -> count() > 0)
                        {
                            $iUserid = $qUserid -> first() -> userid;
                            $qSearch = DB::getInstance() -> query('SELECT postid FROM posts WHERE userid = ? ORDER BY date, time DESC', array($iUserid));

                            foreach($qSearch -> results() as $oSearch)
                            {
                                array_push($arrPostId, $oSearch -> postid);
                            }
                        }
                    }

                    if(strlen($sSearchUser) > 0)
                    {
                        $sqSearchUser = '%' . $sSearchUser . '%';
                        $qSearch = DB::getInstance() -> query('SELECT userid, profilename, permission FROM users WHERE profilename LIKE ?', array($sqSearchUser));

                        foreach($qSearch -> results() as $oSearch)
                        {
                            $arrTemp = array('userid' => $oSearch -> userid, 'profilename' => $oSearch -> profilename, 'permission' => $oSearch -> permission);
                            array_push($arrUserId, $arrTemp);
                        }
                    }

                    // no results were found
                    if((isset($_GET['fromdate']) && strlen($_GET['fromdate']) <= 0) || (isset($_GET['todate']) && strlen($_GET['todate']) <= 0) && (count($arrPostId) <= 0 && count($arrUserId) <= 0
                        && (isset($_GET['keywords']) || isset($_GET['dd_title_post'])
                        || isset($_GET['p_username']) || isset($_GET['username']))))
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
                                       <td class = "postinfo extra-padding" style = "font-size: 16px;">There were no results. Please try again</td>
                                    </tr>
                                </tbody>
                            </table> <div class = "row-buffer-3"></div>';
                    }
                }
            ?>

            <form action = "search.php" method = "get">
                <table cellpadding = "2" cellspacing = "1" width = "100%" class = "mobile">
                    <thead>
                        <tr class = "mobile">
                            <td class = "groupname mobile border" colspan = "2" class = "mobile">
                                <b class = "white-text">Search forum</b>
                            </td>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class = "mobile">
                            <td class = "postinfo mobile">
                                <div style = "padding: 13px;">
                                    Search by keywords: <br />

                                    <input type = "text" class = "form-control" name = "keywords" placeholder = "Keywords" value = "<?php echo $sKeywords; ?>" />

                                    <select name = "dd_title_post" class = "form-control" style = "width: 195px;">
                                        <option value = "0" <?php if($sDropdown == '0'){ echo "selected"; } ?>>Search entire post</option>
                                        <option value = "1" <?php if($sDropdown == '1'){ echo "selected"; } ?>>Search title only</option>
                                        <option value = "2" <?php if($sDropdown == '2'){ echo "selected"; } ?>>Search post and title</option>
                                    </select>
                                </div>
                            </td>

                            <td class = "postinfo mobile" valign = "top">
                                <div style = "padding: 13px;" height = "100%">
                                    Search by forum name: <br />

                                    <input type = "text" class = "form-control" name = "p_username" placeholder = "Forum name" value = "<?php echo $sPUsername; ?>" />
                                </div>
                            </td>
                        </tr>

                        <tr class = "mobile">
                            <td class = "postinfo mobile">
                                <div style = "padding: 13px">
                                    Search for user(s): <br />

                                    <input type = "text" class = "form-control" name = "username" placeholder = "Forum name" value = "<?php echo $sSearchUser; ?>" />
                                </div>
                            </td>

                            <td class = "postinfo mobile">
                                <div style = "padding: 13px">
                                    Search by start and end date and by time: <br />

                                    <table width = "100%" class = "mobile">
                                        <tr class = "mobile">
                                            <td class = "mobile">
                                                <input type = "date" class = "form-control" name = "fromdate" value = "<?php echo $dtSearchFromDate; ?>" placeholder = "from" />
                                            </td>

                                            <td class = "mobile">
                                                <input type = "date" class = "form-control" name = "todate" value = "<?php echo $dtSearchToDate; ?>" />
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>

                        <tr align = "center" class = "mobile">
                            <td class = "postinfo mobile" colspan = "2">
                                <div class = "row-buffer-5"></div>
                                <button type = "submit" class = "btn btn-primary">Search</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
            <div class = "row-buffer-10"></div>

            <?php
                if(count($arrUserId) > 0)
                {
                    echo '
                        <table cellpadding = "3" cellspacing = "1" width = "100%" align = "center">
                            <thead>
                                <tr class = "border">
                                    <td class = "groupname" colspan = "3">
                                        <b style = "color: white;">Usernames found with the keyword "' . $sSearchUser . '"</b>
                                    </td>
                                </tr>
                            </thead>

                            <thead>
                                <tr class = "tophead">
                                    <td class = "border remove_mobile" style = "padding: 4px"><span class = "white-text">Userid</span></td>
                                    <td class = "border" style = "padding: 4px"><span class = "white-text">Forum name</span></td>
                                    <td class = "border" style = "padding: 4px"><span class = "white-text">Permission</span></td>
                                </tr>
                            </thead>';

                    $arrPermission = array();
                    $qPermission = DB::getInstance() -> query('SELECT permission, permission_name FROM permissions');

                    foreach($qPermission -> results() as $permissions)
                    {
                        $arrPermission[$permissions -> permission] = $permissions -> permission_name;
                    }

                    foreach($arrUserId as $oUser)
                    {
                        $sCurUserPermission = $arrPermission[$oUser['permission']];

                        $sConfigString = 'config/color_codes/' . $oUser['permission'];
                        $iProfileNameColour = Config::get($sConfigString);

                        echo '
                            <tbody>
                                <tr>
                                    <td class = "postinfo border remove_mobile" align = "right"><b>' . $oUser['userid'] . '</b></td>
                                    <td class = "postinfo border" style = "border-color: black;"><b><a href = "member.php?u=' . $oUser['userid'] . '" style = "color: ' . $iProfileNameColour . ';">' . $oUser['profilename'] . '</b></td>
                                    <td class = "postinfo border" style = "color: ' . $iProfileNameColour . '; border-color: black;"><b>' . $sCurUserPermission . '</b></td>
                                </tr>
                            </tbody>';

                    }

                    echo '</table> <div class = "row-buffer-3"></div>';
                }

                if(count($arrPostId) > 0)
                {
                    echo '
                        <table cellpadding = "3" cellspacing = "1" width = "100%" align = "center">
                            <thead>
                                <tr class = "border">
                                    <td class = "groupname" colspan = "3">';
                                        if(strlen($dtSearchFromDate) > 0 && strlen($dtSearchToDate) > 0)
                                        {
                                            $sDtExtraText = "and between the following dates: " . $dtSearchFromDate . "|" . $dtSearchToDate;
                                        }
                                        else
                                            $sDtExtraText = "";

                                        if(strlen($sKeywords) > 0)
                                        {
                                            if(strlen($sPUsername) > 0)
                                            {
                                                echo '<b style = "color: white;">Results found with the keyword "' . $sKeywords . '" and with the forum name "' . $sPUsername . '" ' . $sDtExtraText . '</b>';
                                            }
                                            else
                                            {
                                                echo '<b style = "color: white;">Results found with the keyword "' . $sKeywords . '" ' . $sDtExtraText . '</b>';
                                            }
                                        }
                                        else
                                        {
                                            if(strlen($sPUsername) > 0)
                                            {
                                                echo '<b style = "color: white;">Results found with the forum name "' . $sPUsername . '" ' . $sDtExtraText . '</b>';
                                            }
                                            else if(strlen($dtSearchFromDate) > 0 && strlen($dtSearchToDate) > 0)
                                            {
                                                echo '<b style = "color: white;">Results found between the following dates: "' . $dtSearchFromDate . '|' . $dtSearchToDate . '"</b>';
                                            }
                                        }

                                    echo '</td>
                                </tr>
                            </thead>

                            <thead>
                                <tr class = "tophead">
                                    <td class = "border" style = "padding: 4px"><span class = "white-text">Post owner</span></td>
                                    <td class = "border" style = "padding: 4px"><span class = "white-text">Post title</span></td>
                                    <td class = "border" style = "padding: 4px"><span class = "white-text">Thread title</span></td>
                                </tr>
                            </thead>';

                        foreach($arrPostId as $postid)
                        {
                            if((isset($_GET['fromdate']) && strlen($_GET['fromdate']) > 0) && (isset($_GET['todate']) && strlen($_GET['todate']) > 0))
                            {
                                $qPostInfo = DB::getInstance() -> query('SELECT threadid, userid, title FROM posts WHERE postid = ? ORDER BY date, time DESC', array($postid));
                            }
                            else
                            {
                                $qPostInfo = DB::getInstance() -> query('SELECT threadid, userid, title FROM posts WHERE postid = ? ORDER BY date, time DESC', array($postid));
                            }

                            if($qPostInfo -> count() > 0)
                            {
                                $iThreadid      = $qPostInfo -> first() -> threadid;
                                $iUserid        = $qPostInfo -> first() -> userid;
                                $sPostTitle     = $qPostInfo -> first() -> title;

                                $qUserInfo = DB::getInstance() -> query('SELECT profilename, permission FROM users WHERE userid = ?', array($iUserid));
                                $sProfilename = $qUserInfo -> first() -> profilename;
                                $iPermission  = $qUserInfo -> first() -> permission;

                                $sConfigString = 'config/color_codes/' . $iPermission;
                                $iProfileNameColour = Config::get($sConfigString);

                                $qThreadInfo = DB::getInstance() -> query('SELECT title FROM threads WHERE threadid = ? ORDER BY lastpost DESC', array($iThreadid));
                                $sThreadTitle = $qThreadInfo -> first() -> title;

                                // with hl > need hl fix
                                // <td class = "postinfo border"><b><a href = "showthread.php?t=' . $iThreadid . '&p=' . $postid . '&hl=' . $sKeywords . '" class = "blue-text">' . $sPostTitle . '</a></b></td>

                                echo '
                                    <tbody>
                                        <tr>
                                            <td class = "postinfo border" style = "border-color: black;"><b><a href = "member.php?u=' . $iUserid . '" style = "color: ' . $iProfileNameColour . ';">' . $sProfilename . '</b></td>
                                            <td class = "postinfo border"><b><a href = "showthread.php?t=' . $iThreadid . '&p=' . $postid . '" class = "blue-text">' . $sPostTitle . '</a></b></td>
                                            <td class = "postinfo border"><b><a href = "showthread.php?t=' . $iThreadid . '" class = "blue-text">' . $sThreadTitle . '</a></b></td>

                                        </tr>
                                    </tbody>';
                            }
                        }

                    echo '</table>';
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
