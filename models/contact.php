<?php
/*
 * @Author: Slash Web Design
 */

class Contact extends Core
{
	public		 $template = 'template-cms.html';
	public   $loadExternal = array();
	public        $loadCSS = array();
	public         $loadJS = array('client.js');
	public		$hasAccess = false;
	protected $accessLevel = 4;
	
	function __construct()
	{
		parent::__construct();

		$this->hasAccess = $this->canAccess($this->accessLevel);
	}
	
	public function send(&$ld)
	{
		$body = 'Name: ' . $ld['name'] . '<br />Email: ' . $ld['email'] . '<br />---<br />Message: ' . $ld['message'];

		$ld['mail-result'] = $this->helper->sendMail($body, 'New contact from Conexe', array('name' => 'Admin', 'email' => ADMIN_EMAIL));

		$ld['error'] = $this->helper->buildMessageBox("success", "Your message has been sent. We will get back to you as soon as possible.", false);
		return true;
	}
	
	public function fetch()
	{
		$p = new Parser("contact.html");
		
		$p->parseValue(array(
			'ALERT'	=>	$this->helper->prefill('error')
		));

		return $p->fetch();
	}
}
?>