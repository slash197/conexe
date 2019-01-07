<?php
/*
 * @Author: Slash Web Design
 */

class AccountEmail extends Core
{
	public		 $template = 'template-full.html';
	public   $loadExternal = array();
	public        $loadCSS = array();
	public         $loadJS = array('account.js');
	public		$hasAccess = false;
	protected $accessLevel = 3;
	
	function __construct()
	{
		parent::__construct();

		$this->hasAccess = $this->canAccess($this->accessLevel);
	}

	public function update(&$ld)
	{
		if ($ld['data']['password-old'] !== $this->user->password)
		{
			$ld['error'] = $this->helper->buildMessageBox("error", "Current password is invalid");
			return false;
		}
		if ($ld['data']['password-new'] === "" || strlen($ld['data']['password-new']) < 8)
		{
			$ld['error'] = $this->helper->buildMessageBox("error", "Please enter a valid password of at least 8 characters");
			return false;
		}
		if ($ld['data']['password-new'] !== $ld['data']['password-con'])
		{
			$ld['error'] = $this->helper->buildMessageBox("error", "Please confirm your new password");
			return false;
		}
		
		$ld['data']['password'] = $this->helper->encrypt($ld['data']['password-new']);
		
		$this->db->update("member", $ld['data'], "member_id = {$this->user->id}");	
		$ld['error'] = $this->helper->buildMessageBox("success", "Account details saved");
		
		$this->user = new User($this->user->id);		
		return true;
	}
	
	public function fetch()
	{
		$p = new Parser("accountemail.html");

		$p->parseValue(array(
			'ALERT'		=>	$this->helper->prefill('error'),
			'NAME'		=>	$this->user->fname,
			'EMAIL'		=>	$this->user->email,
			'IMAGE'		=>	$this->user->image
		));

		return $p->fetch();
	}
}
?>