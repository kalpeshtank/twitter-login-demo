<?php
//start session
session_start();

//Include Twitter config file && User class
include_once 'twConfig.php';
include_once 'User.php';

//If OAuth token not matched
if (isset($_REQUEST['oauth_token']) && $_SESSION['token'] !== $_REQUEST['oauth_token']) {
//Remove token from session
    unset($_SESSION['token']);
    unset($_SESSION['token_secret']);
}

//If user already verified 
$error = '';
$login_logout = '';
$output = '';
$output_user_detail = '';
if (isset($_SESSION['status']) && $_SESSION['status'] == 'verified' && !empty($_SESSION['request_vars'])) {
//Retrive variables from session
    $username = $_SESSION['request_vars']['screen_name'];
    $twitterId = $_SESSION['request_vars']['user_id'];
    $oauthToken = $_SESSION['request_vars']['oauth_token'];
    $oauthTokenSecret = $_SESSION['request_vars']['oauth_token_secret'];
    $profilePicture = $_SESSION['userData']['picture'];

    /*
     * Prepare output to show to the user
     */
    $twClient = new TwitterOAuth($consumerKey, $consumerSecret, $oauthToken, $oauthTokenSecret);

//If user submits a tweet to post to twitter
    if (isset($_POST["updateme"])) {
        $my_update = $twClient->post('statuses/update', array('status' => $_POST["updateme"]));
    }
    $twitter_user_data = $twClient->get('users/lookup', array('screen_name' => $username));
    $userData = $twitter_user_data[0];
//    echo '<pre>';
//    print_r($userData);
//    exit;
    //Get latest tweets
    $myTweets = $twClient->get('statuses/user_timeline', array('screen_name' => $username, 'count' => 5));
//Display username and logout link
    $output_user_detail .= '<div class="col-md-7 user-details">';
    $output_user_detail .= '<div class="row coralbg white">';
    $output_user_detail .= '<div class="col-md-2 no-pad"><div class="user-image"><img src="' . $userData['profile_image_url'] . '" class="img-responsive thumbnail"></div></div>';
    $output_user_detail .= '<div class="col-md-10 no-pad"><div class="user-pad"><h3 class="welcome_txt">Welcome back, <strong>' . $userData['name'] . '</strong></h3>';
    $output_user_detail .= '<h4 class="white"><i class="fa fa-twitter"></i>(Twitter ID : ' . $twitterId . ').</div></div>';
    $output_user_detail .= '</div><div class="row overview">';
    $output_user_detail .= '<div class="col-md-4 user-pad text-center">';
    $output_user_detail .= '<h3>FOLLOWERS</h3>';
    $output_user_detail .= '<h4>' . $userData['followers_count'] . '</h4></div>';
    $output_user_detail .= '<div class="col-md-3 user-pad text-center">';
    $output_user_detail .= ' <h3>FOLLOWING</h3>';
    $output_user_detail .= '<h4>' . $userData['friends_count'] . '</h4></div>';
    $output_user_detail .= '<div class="col-md-5 user-pad text-center">';
    $output_user_detail .= '<h3>TOTAL TWEETS</h3>';
    $output_user_detail .= ' <h4>' . $userData['statuses_count'] . '</h4>';
    $output_user_detail .= '</div></div></div>';
    $output .= '<div class="welcome_txt">Welcome <strong>' . $username . '</strong> (Twitter ID : ' . $twitterId . '). <a href="logout.php">Logout</a>!</div>';
    $login_logout = '<a href="logout.php" class="btn btn-xl btn-twitter">Logut(' . $username . ')</a>';
//Display profile iamge and tweet form
    $output .= '<div class="tweet_box">';
    $output .= '<img src="' . $profilePicture . '" width="120" height="110"/>';
    $output .= '<form method="post" action=""><table width="200" border="0" cellpadding="3">';
    $output .= '<tr>';
    $output .= '<td><textarea name="updateme" cols="60" rows="4"></textarea></td>';
    $output .= '</tr>';
    $output .= '<tr>';
    $output .= '<td><input type="submit" value="Tweet" /></td>';
    $output .= '</tr></table></form>';
    $output .= '</div>';
//Display the latest tweets
    $output .= '<div class="tweet_list"><strong>Latest Tweets : </strong>';
    $output .= '<ul>';
    foreach ($myTweets as $tweet) {
        $output .= '<li>' . $tweet['text'] . ' <br />-<i>' . $tweet['created_at'] . '</i></li>';
    }
    $output .= '</ul></div>';
} elseif (isset($_REQUEST['oauth_token']) && $_SESSION['token'] == $_REQUEST['oauth_token']) {
//Call Twitter API
    $twClient = new TwitterOAuth($consumerKey, $consumerSecret, $_SESSION['token'], $_SESSION['token_secret']);

//Get OAuth token
    $access_token = $twClient->getAccessToken($_REQUEST['oauth_verifier']);

//If returns success
    if ($twClient->http_code == '200') {
//Storing access token data into session
        $_SESSION['status'] = 'verified';
        $_SESSION['request_vars'] = $access_token;

//Get user profile data from twitter
        $userInfo = $twClient->get('account/verify_credentials');

//Initialize User class
        $user = new User();

//Insert or update user data to the database
        $name = explode(" ", $userInfo->name);
        $fname = isset($name[0]) ? $name[0] : '';
        $lname = isset($name[1]) ? $name[1] : '';
        $profileLink = 'https://twitter.com/' . $userInfo->screen_name;
        $twUserData = array(
            'oauth_provider' => 'twitter',
            'oauth_uid' => $userInfo->id,
            'first_name' => $fname,
            'last_name' => $lname,
            'email' => '',
            'gender' => '',
            'locale' => $userInfo->lang,
            'picture' => $userInfo->profile_image_url,
            'link' => $profileLink,
            'username' => $userInfo->screen_name
        );

        $userData = $user->checkUser($twUserData);

//Storing user data into session
        $_SESSION['userData'] = $userData;

//Remove oauth token and secret from session
        unset($_SESSION['token']);
        unset($_SESSION['token_secret']);

//Redirect the user back to the same page
        header('Location: ./');
    } else {
        $error = '<h3 style="color:red">Some problem occurred, please try again.</h3>';
    }
} else {
//Fresh authentication
    $twClient = new TwitterOAuth($consumerKey, $consumerSecret);
    $request_token = $twClient->getRequestToken($redirectURL);

//Received token info from twitter
    $_SESSION['token'] = $request_token['oauth_token'];
    $_SESSION['token_secret'] = $request_token['oauth_token_secret'];

//If authentication returns success
    if ($twClient->http_code == '200') {
//Get twitter oauth url
        $authUrl = $twClient->getAuthorizeURL($request_token['oauth_token']);

//Display twitter login button
//        $output = '<a href="' . filter_var($authUrl, FILTER_SANITIZE_URL) . '"><img src="images/sign-in-with-twitter.png" width="151" height="24" border="0" /></a>';
        $login_logout = '<a href="' . filter_var($authUrl, FILTER_SANITIZE_URL) . '" class="btn btn-xl btn-twitter"><span class="fa fa-twitter"></span> Login in with Twitter</a>';
    } else {
        $error = '<h3 style="color:red">Error connecting to twitter! try again later!</h3>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">
        <title>tweeter-login</title>
        <!-- Bootstrap core CSS -->
        <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <!-- Custom styles for this template -->
        <link href="css/scrolling-nav.css" rel="stylesheet">
        <link href="css/style.css" rel="stylesheet">
        <!--Custom fonts for this template--> 
        <link href="css/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
        <link href='https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
        <link href='https://fonts.googleapis.com/css?family=Merriweather:400,300,300italic,400italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>
    </head>
    <body id="page-top">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" id="mainNav">
            <div class="container">
                <a class="navbar-brand js-scroll-trigger" href="#page-top">Tweeter-login</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <?php echo $login_logout; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <section>
            <div class="container">
                <div class="row">
                    <div class="col-md-12 mx-auto">
                        <?php if ($error != '') { ?>
                            <div class="alert alert-danger">
                                <strong><?php echo $error; ?></strong>
                            </div>
                            <?php
                        }
                        ?>

                    </div>
                </div>
            </div>
            <!--            <div class="container">
                            <div class="row">
                                <div class="col-lg-8 mx-auto">
            <?php
//                        echo $output;
            ?>
                                </div>
                            </div>
                        </div>-->
            <div class="container">
                <div class="row user-menu-container square">
                    <?php echo $output_user_detail ?>
                    <!--                    <div class="col-md-4 user-menu user-pad">
                                            <div class="user-menu-content active">
                                                <h3>
                                                    Latest Tweets 
                                                </h3>
                                            </div>
                                        </div>-->
                </div>
            </div>
        </section>
        <!-- Footer -->
        <footer class="py-5 bg-dark">
            <div class="container">
                <p class="m-0 text-center text-white">Copyright &copy; Your Website 2017</p>
            </div>
            <!-- /.container -->
        </footer>
        <!-- Bootstrap core JavaScript -->
        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="vendor/popper/popper.min.js"></script>
        <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
        <!-- Plugin JavaScript -->
        <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
        <!-- Custom JavaScript for this theme -->
        <script src="js/scrolling-nav.js"></script>
    </body>
</html>
