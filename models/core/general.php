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
	
	public function upload()
	{
		$uploader = new Uploader(array('jpg', 'jpeg'), 64 * 1024 * 1024);
		$result = $uploader->handleUpload("uploads/temp/");

		header("Content-type: application/json; charset=utf-8");
		echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
		die();
	}
	
	public function getLocation($ld)
	{
		$ld['input'] = strtolower($ld['input']);
		
		$res = $this->db->run(
			"SELECT
				city.id,
				city.name,
				region.id AS region_id,
				region.name AS region_name,
				country.id AS country_id,
				country.name AS country_name
			FROM 
				location_city AS city,
				location_region AS region,
				location_country AS country
			WHERE 
				MATCH(city.name) AGAINST('{$ld['input']}' IN BOOLEAN MODE) AND
				city.name LIKE '{$ld['input']}%' AND
				city.region_id = region.id AND
				region.country_id = country.id
			LIMIT 20"
		);
		
		$this->helper->respond(array(
			'status'	=>	true,
			'results'	=>	$res
		));
	}
	
	public function fetch()
	{
		
	}
}