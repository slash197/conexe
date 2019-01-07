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

		$this->name = trim($this->fname . " " . $this->lname);
		$this->image = file_exists("uploads/profile/{$this->id}.jpg") ? SITE_URL . "uploads/profile/{$this->id}.jpg?v=" . rand(111, 999) : SITE_URL . "assets/img/profile.na.png";
	}
}
?>