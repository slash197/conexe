<?php
/*
 * @Author Slash Web Design
 */

require 'startup.php';

if (LIVE === 0)
{
	die('The site is currently under maintenance, please check back later');
}

$og = array(
	'url'	=>	'',
	'title'	=>	'',
	'description'	=>	''
);

$page->run();

$p = new Parser($page->template);

$p->parseValue(array(
	'CONTENT'			=>	$page->content,
	'MAIN_MENU'			=>	$helper->buildMainMenu(0, true),
	'MAIN_MENU_SIMPLE'	=>	$helper->buildMainMenu(0, false),
	'RANDOM'			=>	rand(111, 999),
	'META_TITLE'		=>	META_TITLE,
	'META_KEYWORDS'		=>	META_KEYWORDS,
	'META_DESCRIPTION'	=>	META_DESCRIPTION,
	'SITE_URL'			=>	SITE_URL,

	'META_OG_URL'			=>	$og['url'],
	'META_OG_TITLE'			=>	$og['title'],
	'META_OG_DESCRIPTION'	=>	$og['description'],
	'META_OG_IMAGE'			=>	SITE_URL . 'assets/img/logo.png',

	'GLOBAL'			=>	json_encode(array(
								'siteName'	=>	SITE_NAME,
								'siteURL'	=>	SITE_URL,
								'isHome'	=>	(isset($glob['title']) && ($glob['title'] == "home")) ? 1 : 0,
								'glob'		=>	$glob,
								'user'		=>	$user,
								'assets'	=>	$page->assets
							)),
	'USER_IMAGE'		=>	($user !== null) ? $user->image : '',
	'USER_NAME'			=>	($user !== null) ? $user->name : '',
	'DEBUG'				=>	(DEBUG === 1) ? $helper->debug() : '',
	'CYEAR'				=>	date('Y', time())
));
echo $p->fetch();

$helper->cleanUp();
unset($helper);
?>