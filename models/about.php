<?php
/*
 * @Author: Slash Web Design
 */

class About extends Core
{
	public		 $template = 'template-full.html';
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
	
	public function fetch()
	{
		$p = new Parser("about.html");
		$p->defineBlock('staff');
		$p->defineBlock('director');

		$staff = $this->db->run("SELECT * FROM staff ORDER BY sort_order ASC");
		foreach ($staff as $r)
		{
			$p->parseBlock(array(
				'NAME'		=>	$r['name'],
				'ROLE'		=>	$r['role'],
				'IMAGE'		=>	file_exists("uploads/staff/{$r['staff_id']}.jpg") ? "uploads/staff/{$r['staff_id']}.jpg" : "assets/img/profile.na.png",
			), ($r['is_director'] === '1') ? 'director' : 'staff');
		}

		return $p->fetch();
	}
}
?>