<?php
/*
 * @Author: Slash Web Design
 */
class NVP
{
	public $id = 0;
	public $name = '';
}

class Address
{
	public $city = null;
	public $address = null;
	public $latitude = null;
	public $longitude = null;
}

class Location extends Address
{
	public $country = null;
	public $region = null;
	public $city = null;
	
	function __construct()
	{
		$this->country = new NVP();
		$this->region = new NVP();
	}
}

class User extends Core
{
	public $id;
	public $name;
	public $image;
	public $password;
	public $location = null;
		
	function __construct()
	{
		parent::__construct();
		
		$this->id = (int) $_SESSION['user_id'];
		$this->location = new Location();
		
		$res = $this->db->run("SELECT * FROM member WHERE member_id = {$this->id}");
		foreach ($res[0] as $key => $value)
		{
			switch ($key)
			{
				case 'password': 
					$this->$key = $this->helper->decrypt($value);
					break;
				
				case 'country_id':
			
					$res = $this->db->run("SELECT name FROM location_country WHERE id = {$value}");

					$this->location->country->id = $value;
					$this->location->country->name = count($res) ? $res[0]['name'] : '';
					
					break;
				
				case 'region_id':
			
					$res = $this->db->run("SELECT name FROM location_region WHERE id = {$value}");
										
					$this->location->region->id = $value;
					$this->location->region->name = count($res) ? $res[0]['name'] : '';
					
					break;
				
				default:
					$this->$key = $value;
			}
		}

		$this->name = trim($this->fname . " " . $this->lname);
		$this->image = file_exists("uploads/profile/{$this->id}.jpg") ? SITE_URL . "uploads/profile/{$this->id}.jpg?v=" . rand(111, 999) : SITE_URL . "assets/img/profile.na.png";
	}
}