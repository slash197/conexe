<?php
/*
 * @Author: Slash Web Design
 */

class General extends Core
{
	public		 $template = '';
	public   $loadExternal = array();
	public        $loadCSS = array();
	public         $loadJS = array();
	public		$hasAccess = false;
	protected $accessLevel = 4;
	
	function __construct()
	{
		parent::__construct();

		$this->hasAccess = $this->canAccess($this->accessLevel);
	}
	
	public function upload($ld)
	{
		$uploader = new Uploader(array('jpg', 'jpeg'), 64 * 1024 * 1024);
		$result = $uploader->handleUpload("uploads/temp/");

		header("Content-type: application/json; charset=utf-8");
		echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
		die();
	}
	
	public function getStates($ld)
	{
		$this->helper->respond(array(
			'status'	=>	true,
			'states'	=>	$this->helper->buildStateDD(0, $ld['country_id'])
		));
	}
	
	public function contact(&$ld)
	{
		$this->helper->p($ld, 1);
		
		$body = 'Name: ' . $ld['name'] . '<br />Email: ' . $ld['email'] . '<br />---<br />Message: ' . $ld['message'];

		$ld['mail-result'] = $this->helper->sendMail($body, 'New Contact from your website Kira', array('name' => 'Admin', 'email' => ADMIN_EMAIL));

		$ld['error'] = $this->helper->buildMessageBox("success", "Your message has been sent. We will get back to you as soon as possible.");
		return true;
	}
	
	public function fetch()
	{
		
	}
}
?>