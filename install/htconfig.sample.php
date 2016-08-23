<?php

// If automatic system installation fails: 

// Copy or rename this file to .htconfig.php in the top level 
// Hubzilla directory

// Why .htconfig.php? Because it contains sensitive information which could
// give somebody complete control of your database. Apache's default 
// configuration denies access to and refuses to serve any file beginning 
// with .ht

// Then set the following for your MySQL installation

$db_host = 'your.mysqlhost.com'; // Use 'localhost' if you aren't using a remote server
$db_port = 0;                    // leave 0 for default or set your port
$db_user = 'mysqlusername';
$db_pass = 'mysqlpassword';
$db_data = 'mysqldatabasename';
$db_type = 0; // use 1 for postgres, 0 for mysql

/*
 * Notice: Many of the following settings will be available in the admin panel 
 * after a successful site install. Once they are set in the admin panel, they
 * are stored in the DB - and the DB setting will over-ride any corresponding
 * setting in this file
 *
 * The command-line tool util/config is able to query and set the DB items 
 * directly if for some reason the admin panel is not available and a system
 * setting requires modification. 
 *
 */ 


// Choose a legal default timezone. If you are unsure, use "America/Los_Angeles".
// It can be changed later and only applies to timestamps for anonymous viewers.

App::$config['system']['timezone'] = 'America/Los_Angeles';

// What is your site name? DO NOT ADD A TRAILING SLASH!

App::$config['system']['baseurl'] = 'https://mysite.example';
App::$config['system']['sitename'] = "Hubzilla";
App::$config['system']['location_hash'] = 'if the auto install failed, put a unique random string here';


// Choices are 'basic', 'standard', and 'pro'.
// basic sets up the sevrer for basic social networking and removes "complicated" features
// standard provides most desired features except e-commerce
// pro gives you access to everything

App::$config['system']['server_role'] = 'pro';


// These lines set additional security headers to be sent with all responses
// You may wish to set transport_security_header to 0 if your server already sends
// this header. content_security_policy may need to be disabled if you wish to
// run the piwik analytics plugin or include other offsite resources on a page

App::$config['system']['transport_security_header'] = 1;
App::$config['system']['content_security_policy'] = 1;
App::$config['system']['ssl_cookie_protection'] = 1;


// Your choices are REGISTER_OPEN, REGISTER_APPROVE, or REGISTER_CLOSED.
// Be certain to create your own personal account before setting 
// REGISTER_CLOSED. 'register_text' (if set) will be displayed prominently on 
// the registration page. REGISTER_APPROVE requires you set 'admin_email'
// to the email address of an already registered person who can authorise
// and/or approve/deny the request. 

// In order to perform system administration via the admin panel, admin_email
// must precisely match the email address of the person logged in.

App::$config['system']['register_policy'] = REGISTER_OPEN;
App::$config['system']['register_text'] = '';
App::$config['system']['admin_email'] = '';

// Location of PHP command line processor

App::$config['system']['php_path'] = 'php';


// Configure how we communicate with directory servers.
// DIRECTORY_MODE_NORMAL     = directory client, we will find a directory (all of your member's queries will be directed elsewhere)
// DIRECTORY_MODE_SECONDARY  = caching directory or mirror (keeps in sync with realm primary [adds significant cron execution time])
// DIRECTORY_MODE_PRIMARY    = main directory server (you do not want this unless you are operating your own realm. one per realm.)
// DIRECTORY_MODE_STANDALONE = "off the grid" or private directory services (only local site members in directory)

App::$config['system']['directory_mode']  = DIRECTORY_MODE_NORMAL;


// PHP error logging setup
// Before doing this ensure that the webserver has permission
// to create and write to php.out in the top level Red directory,
// or change the name (below) to a file/path where this is allowed.

// Uncomment the following 4 lines to turn on PHP error logging.
//error_reporting(E_ERROR | E_WARNING | E_PARSE ); 
//ini_set('error_log','php.out'); 
//ini_set('log_errors','1'); 
//ini_set('display_errors', '0');
