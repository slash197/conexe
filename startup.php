<?php
/*
 * @Author: Slash Web Design
 */

ini_set("error_log", "logs/php-error.log");
ini_set("memory_limit", "128M");
error_reporting(E_ALL);
session_start();
$pageLoadTimeStart = microtime(true);

require 'models/core/autoloader.php';
require 'config.php';

$glob	= array();
$db		= new Database("mysql:host={$config['host']};dbname={$config['database']}", $config['username'], $config['password']);
$helper = new Helper();
$user	= isset($_SESSION['user_id']) ? new User() : null;
$page   = new Controller();

function __()
{
	global $helper;	
	return $helper->language->translate(func_get_args());
}

header("Content-type: text/html; charset=utf-8;");