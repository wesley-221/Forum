<?php
	session_start();

	$GLOBALS['config'] = array(
		'mysql' => array(
			'host' => '127.0.0.1',
			'username' => 'root',
			'password' => '',
			'db' => 'forum'
		),
		'session' => array(
			'session_name' => 'user'
		),
		'cookie' => array(
			'cookie_name' => 'login_token'
		),
		'forum_name' => 'Forum',
		'color_codes' => array(
			'0' => 'black',
			'1' => '#0022b3',
			'2' => '#b40000',
			'3' => '#078A07'
		),
		'allowed_extensions' => array(
			"jpg",
			"jpeg",
			"bmp",
			"png",
			"gif"
		),
		'thread_icons' => array(
			"forum_old.png",
			"forum_explenationmark.png",
			"forum_question.png",
			"osu.png"
		),
		'max_messages' => 5000
	);

	spl_autoload_register( function( $class ) {
		require_once 'classes/' . $class . '.php';
	});
