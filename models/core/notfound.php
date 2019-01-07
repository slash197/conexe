<?php
/*
 * @Author: Slash Web Design
 */

class NotFound extends Core
{
	public		 $template = 'template-cms.html';
	public   $loadExternal = array();
	public        $loadCSS = array();
	public         $loadJS = array('client.js');
	public		$hasAccess = true;
	
	protected $accessLevel = 4;
	protected $accessDenied;
	
	function __construct($accessDenied = false)
	{
		parent::__construct();
		
		$this->accessDenied = $accessDenied;
	}

	public function fetch()
	{
		global $glob;
		
		$p = new Parser("404.html");
		
		if (!isset($glob['error'])) $glob['error'] = 'You have to sign in to access this page';
		
		$p->parseValue(array(
			'ACCESS_DENIED'		=>	$this->accessDenied ? $this->helper->buildMessageBox('error', $glob['error']) : ''
		));
		
		return $p->fetch();
	}
}
?>