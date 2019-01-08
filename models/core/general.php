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
	
	public function fetch()
	{
		
	}
}
?>