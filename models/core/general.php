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
		
	public function getFeatured()
	{
		$moms = $this->db->run("SELECT * FROM mom WHERE featured = 1 ORDER BY RAND() LIMIT 3");
		
		foreach ($moms as $key => $r)
		{
			$res = $this->db->run("SELECT filename FROM mom_image WHERE profile = 1 AND mom_id = {$r['mom_id']}");
			$profile = (count($res) > 0) ? "uploads/mom/{$res[0]['filename']}" : 'assets/img/mom.na.jpg';
			
			$raised = $this->helper->getFunds($r['mom_id']);
			$remaining = FUND_TARGET - $raised;
			
			if ($raised > FUND_TARGET) $raised = FUND_TARGET;
			if ($remaining < 0) $remaining = 0;
			
			$moms[$key]['date'] = date('F d, Y', $r['baby_date']);
			$moms[$key]['raised'] = $raised;
			$moms[$key]['remaining'] = $remaining;
			$moms[$key]['percent'] = round($raised * 100 / FUND_TARGET, 0);
			$moms[$key]['profile'] = $profile;
		}
		
		$this->helper->respond(array(
			'error'		=>	0,
			'message'	=>	'success',
			'moms'		=>	$moms
		));
	}
	
	public function upload($ld)
	{
		$uploader = new Uploader(array('jpg', 'jpeg'), 64 * 1024 * 1024);
		$result = $uploader->handleUpload("uploads/temp/");

		header("Content-type: application/json; charset=utf-8");
		echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
		die();
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