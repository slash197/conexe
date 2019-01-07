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
		
		if (!isset($glob['data']['newsletter'])) $glob['data']['newsletter'] = 0;
		
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
		
		$res = $this->db->run("SELECT m.fname, m.baby_name, m.mom_id FROM transaction t, mom m WHERE t.member_id = {$this->user->id} AND t.mom_id = m.mom_id AND status = 'success' ORDER BY t.date DESC");
		$total = count($res);
		$last = ($total > 0) ? $res[$total - 1] : array('fname' => '', 'baby_name' => '', 'mom_id' => '');

		$p->parseValue(array(
			'ALERT'		=>	$this->helper->prefill('error'),
			'NAME'		=>	$this->user->fname,
			'EMAIL'		=>	$this->user->email,
			'PASSWORD'	=>	$this->user->password,
			'IMAGE'		=>	$this->user->image,
			
			'STAT_TOTAL'		=>	$total,
			'STAT_NAME'			=>	$last['fname'],
			'STAT_BABY_NAME'	=>	$last['baby_name'],
			'STAT_URL'			=>	"mom/{$last['mom_id']}/{$last['fname']}",
			'STAT_RECENT'		=>	($total > 0) ? '' : 'hide',
					
			'NEWSLETTER'		=>	($this->user->newsletter === '1') ? 'checked="checked"' : ''
		));

		return $p->fetch();
	}
}
?>