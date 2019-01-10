<?php
/*
 * @Author: Slash Web Design
 */

class Account extends Core
{
	public		 $template = 'template-full.html';
	public   $loadExternal = array();
	public        $loadCSS = array();
	public         $loadJS = array('fileuploader.js', 'account.js');
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
		$ld['error'] = $this->helper->buildMessageBox("success", "Account details saved", false);
		
		$this->user = new User($this->user->id);		
		return true;
	}
	
	public function saveProfileImage(&$ld)
	{
		if (isset($ld['image']))
		{
			$image = new Resize($ld['image']);
			
			$image->resizeImage(300, 300, 'crop');
			$image->saveImage("uploads/profile/{$this->user->id}.jpg", 100);
			
			@unlink($ld['image']);
		}
		
		$this->user = new User($this->user->id);
		
		$this->helper->respond(array(
			'status'	=>	true,
			'url'		=>	"uploads/profile/{$this->user->id}.jpg?v=" . rand(111, 999)
		));
	}
	
	public function delete()
	{
		$this->db->update("member", array('deleted' => 1), "member_id = {$this->user->id}");
		
		$auth = new Auth();
		$auth->signOut();
	}
	
	public function fetch()
	{
		global $helper;
		
		$p = new Parser("account.html");
		
		$p->parseValue(array(
			'ALERT'		=>	$this->helper->prefill('error'),
			'FNAME'		=>	$this->user->fname,
			'LNAME'		=>	$this->user->lname,
			'EMAIL'		=>	$this->user->email,
			'PASSWORD'	=>	$this->user->password,
			'IMAGE'		=>	$this->user->image,
			'PHONE'		=>	$this->user->phone,
			'COUNTRY'	=>	$helper->buildCountryDD($this->user->location->country->id),
			'STATE'		=>	$helper->buildStateDD($this->user->location->region->id, $this->user->location->country->id),
			'CITY'		=>	$this->user->city,
		));

		return $p->fetch();
	}
}