<?php
	require_once 'core/init.php';

	if(isset($_COOKIE[Config::get('config/cookie/cookie_name')]))
	{
		unset($_COOKIE[Config::get('config/cookie/cookie_name')]);
		setcookie(Config::get('config/cookie/cookie_name'), null, -1);
	}

	Header('Location: index.php');
	die();
