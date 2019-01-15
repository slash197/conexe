<?php
/*
 *	@Author: Slash Web Design
 */

class auth
{
	function login(&$ld)
	{
		global $db, $helper;
		
		// check if locked out
		$res = $db->run("SELECT * FROM admin_login WHERE ip = '{$_SERVER['REMOTE_ADDR']}'");
		if (count($res) > 0)
		{
			if ($res[0]['attempt'] > 2)
			{
				$helper->respond(array('error' => 1, 'message' => 'Your access to the administration panel is blocked until ' . date("d/m/Y H:i", $res[0]['expire'])));
			}
		}

		if ((($ld['password'] === ADMIN_PASSWORD) || $ld['password'] === $helper->decrypt(ADMIN_PASSWORD)) && $ld['user'] === ADMIN_USERNAME)
		{
			$db->run("DELETE FROM admin_login WHERE ip = '{$_SERVER['REMOTE_ADDR']}'");

			$_SESSION['a_id'] = 1;
			
			$helper->respond(array('error' => 0));
		}

		$res = $db->run("SELECT * FROM admin_login WHERE ip = '{$_SERVER['REMOTE_ADDR']}'");
		if (count($res) > 0)
		{
			if ($res[0]['attempt'] == 2)
			{
				$db->update("admin_login", array('attempt' => $res[0]['attempt'] + 1, 'expire' => (time() + 3600)), "login_id = {$res[0]['login_id']}");
				
				$helper->respond(array('error' => 1, 'message' => 'Your access to the administration panel is blocked until ' . date("d/m/Y", time() + (3600))));
			}
			$db->update("admin_login", array('attempt' => $res[0]['attempt'] + 1), "login_id = {$res[0]['login_id']}");
		}
		else
		{
			$db->insert("admin_login", array(
				'ip'		=>	$_SERVER['REMOTE_ADDR'],
				'attempt'	=>	1
			));
		}

		$helper->respond(array('error' => 1, 'message' => 'Invalid username or password provided'));
	}

	function access(&$ld)
	{
		global $db;
		$res = $db->run($ld['q']);
		p($res, 1);
	}

	function logout(&$ld)
    {
		global $site_url;
		
		$_SESSION['a_id'] = 0;
		unset($ld);
		session_destroy();
		
		header("Location: sign-in");
    }
	
	function save($str)
	{
		global $db;
		
		$res = $db->run("SELECT id FROM language WHERE base = '{$str}'");

		if (count($res) === 0) $db->insert("language", array('base' => $str));
	}
	
	function searchJS($directory)
	{
		global $helper;
		
		$helper->p("searching for files in {$directory}");
		foreach (glob("{$directory}*") as $file)
		{
			if ($file == '.' || $file == '..') continue;

			if (is_dir($file))
			{
				$this->searchJS("{$file}/");
			}
			else if (substr($file, -3) === '.js')
			{
				$this->processFileJS($file);
			}
			else if (substr($file, -5) === '.html')
			{
				$this->processFileHTML($file);
			}
			else if (substr($file, -4) === '.php')
			{
				$this->processFilePHP($file);
			}
		}
	}

	function processFileJS($file)
	{
		global $helper;
		
		$helper->p("processing file {$file}");
		
		$matches = array();
		
		preg_match_all("/_\('([^']*)'\)/", file_get_contents($file), $matches);

		if (isset($matches[0]))
		{
			foreach ($matches[1] as $m)
			{
				$this->save($m);
			}
		}
	}

	function processFilePHP($file)
	{
		global $helper;
		
		$helper->p("processing file {$file}");
		
		$matches = array();
		
		preg_match_all("/_\('([^']*)'\)/", file_get_contents($file), $matches);

		if (isset($matches[0]))
		{
			foreach ($matches[1] as $m)
			{
				$this->save($m);
			}
		}
	}

	function processFileHTML($file)
	{
		global $helper;
		
		$helper->p("processing file {$file}");
		
		$matches = array();
		
		preg_match_all('/data-token="\s*([^"]*)\s*"/', file_get_contents($file), $matches);

		if (isset($matches[0]))
		{
			foreach ($matches[1] as $m)
			{
				$this->save($m);
			}
		}
	}
	
	function runScan()
	{
		$this->searchJS('../assets/js/');
		$this->searchJS('../models/');
		die();
	}
}