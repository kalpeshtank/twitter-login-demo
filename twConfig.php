<?php

if (!session_id()) {
    session_start();
}

//Include Twitter client library 
include_once 'src/twitteroauth.php';

/*
 * Configuration and setup Twitter API
 */
$consumerKey = 'zbXkj1fzewIEyMKM9XnzSsQSp';
$consumerSecret = 'k9Co61h4cDPamCHkMG5fQSY9o2SYYMIcBqXWw86Cdl2M5z1TlG';
$redirectURL = 'http://localhost/twitter-login-demo-git/';
