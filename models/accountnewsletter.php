<?php
/*
 * @Author: Slash Web Design
 */

class AccountNewsletter extends Core
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

	public function update(&$glob)
	{
		if (!isset($glob['data']['newsletter'])) $glob['data']['newsletter'] = 0;
		
		$this->db->update("member", $glob['data'], "member_id = {$this->user->id}");	
		$glob['error'] = $this->helper->buildMessageBox("success", "Account details saved");
		
		$this->user = new User($this->user->id);		
		return true;
	}
	
	public function fetch()
	{
		$p = new Parser("accountnewsletter.html");
		
		$p->parseValue(array(
			'ALERT'		=>	$this->helper->prefill('error'),
			'NAME'		=>	$this->user->fname,
			'IMAGE'		=>	$this->user->image,
					
			'NEWSLETTER'		=>	($this->user->newsletter === '1') ? 'checked="checked"' : ''
		));

		return $p->fetch();
	}
}
?>