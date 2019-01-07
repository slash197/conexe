<?php
/*
 * @Author: Slash Web Design
 */

class Account extends Core
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
		$ld['data']['password'] = $this->helper->encrypt($ld['data']['password']);
		
		$this->db->update("member", $ld['data'], "member_id = {$this->user->id}");	
		$ld['error'] = $this->helper->buildMessageBox("success", "Account details saved");
		
		if (isset($_FILES['image']) && ($_FILES['image']['tmp_name'] !== ''))
		{
			$image = new Resize($_FILES['image']['tmp_name']);
			
			$image->resizeImage(300, 300, 'crop');
			$image->saveImage("uploads/profile/{$this->user->id}.jpg", 100);
		}
		
		$this->user = new User($this->user->id);		
		return true;
	}
	
	public function delete(&$glob)
	{
		$this->db->update("member", array('deleted' => 1), "member_id = {$this->user->id}");
		
		$auth = new Auth();
		$auth->signOut();
	}
	
	public function fetch()
	{
		$p = new Parser("account.html");
		
		$p->parseValue(array(
			'ALERT'		=>	$this->helper->prefill('error'),
			'NAME'		=>	$this->user->fname,
			'EMAIL'		=>	$this->user->email,
			'PASSWORD'	=>	$this->user->password,
			'IMAGE'		=>	$this->user->image
		));

		return $p->fetch();
	}
}