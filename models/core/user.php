<?php
/*
 * @Author: Slash Web Design
 */

class User extends Core
{
	public $id;
	public $name;
	public $image;
	public $password;
	public $location = array(
		'country'	=>	array(
			'id'	=>	0,
			'name'	=>	''
		),
		'region'	=>	array(
			'id'	=>	0,
			'name'	=>	''
		),
		'city'	=>	''
	);
		
	function __construct()
	{
		parent::__construct();
		
		$this->id = (int) $_SESSION['user_id'];
		
		$res = $this->db->run("SELECT * FROM member WHERE member_id = {$this->id}");
		foreach ($res[0] as $key => $value)
		{
			switch ($key)
			{
				case 'password': $this->encrypted = $value; $value = $this->helper->decrypt($value); break;
			}
			
			$this->$key = $value;
		}
		
		$country = $this->db->run("SELECT name FROM location_country WHERE id = {$this->location['country']['id']}");
		$region = $this->db->run("SELECT name FROM location_region WHERE country_id = {$this->country_id} AND id = {$this->location['region']['id']}");
		
		$this->location['country']['name'] = count($country) ? $country[0]['name'] : '';
		$this->location['region']['name'] = count($region) ? $region[0]['name'] : '';

		$this->name = trim($this->fname . " " . $this->lname);
		$this->image = file_exists("uploads/profile/{$this->id}.jpg") ? SITE_URL . "uploads/profile/{$this->id}.jpg?v=" . rand(111, 999) : SITE_URL . "assets/img/profile.na.png";
	}
}
?>