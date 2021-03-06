<?php
/*
 * @Author: Slash Web Design						
 */

class members
{
	var $db;
	
	function __construct()
	{
		global $db;
		
		$this->db = $db;
	}
	
	public function export(&$glob)
	{
		$res = $this->db->run(base64_decode($glob['q']));

		$fp = fopen('user.export.csv', 'w');
		fputs($fp, "\xEF\xBB\xBF");

		fputcsv($fp, array('User ID', 'Name', 'Email', 'Registration date'));
		foreach ($res as $fields)
		{
			foreach ($fields as $key => $value)
			{
				if ($key === 'date') $fields[$key] = date("d/m/Y H:i:s", $value);
			}
			fputcsv($fp, $fields);
		}

		fclose($fp);
		
		header("Content-type: application/csv;charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"user.export.csv\"");
		
		echo file_get_contents("user.export.csv");
		die();
	}
	
	public function save(&$glob)
	{
		global $helper;
		
		parse_str($glob['data'], $glob['data']);
		$glob['data']['password'] = $helper->encrypt($glob['data']['password']);
		
		if ($glob['id'] !== '')
		{
			$this->db->update("member", $glob['data'], "member_id = {$glob['id']}");
		}
		else
		{
			$glob['data']['date'] = time();
			$this->db->insert("member", $glob['data']);
			$glob['id'] = $this->db->lastInsertId();
		}
		
		// process profile image
		if ($glob['data']['image'] !== '')
		{
			$r = new Resize("../" . $glob['data']['image']);
			$r->resizeImage(300, 300, 'crop');
			$r->saveImage("../uploads/profile/{$glob['id']}.jpg", 75);
			
			unset($r);
			@unlink("../" . $glob['data']['image']);
		}
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}
	
	public function getItem(&$glob)
	{
		global $helper;
		
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$res = $this->db->run("SELECT * FROM member WHERE member_id = {$glob['id']}");
			$item = $res[0];
			
			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['fname'] . ' ' . $item['lname'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this user',
				'FNAME'				=>  htmlentities($item['fname']),
				'LNAME'				=>  htmlentities($item['lname']),
				'EMAIL'				=>  htmlentities($item['email']),
				'IMAGE'				=>  file_exists("../uploads/profile/{$glob['id']}.jpg") ? "../uploads/profile/{$glob['id']}.jpg?v=" . rand(111, 999) : "../assets/img/profile.na.png",
				'PASSWORD'			=>  $helper->decrypt($item['password']),
				'ACTIVE'			=>  ($item['active'] === '0') ? '' : 'checked="checked"',
				'INACTIVE'			=>  ($item['active'] === '1') ? '' : 'checked="checked"',
				'DELETED'			=>  ($item['deleted'] === '0') ? '' : 'checked="checked"',
				'NOTDELETED'		=>  ($item['deleted'] === '1') ? '' : 'checked="checked"',
				'CUSTOMER'			=>  ($item['type'] !== 'customer') ? '' : 'checked="checked"',
				'VENDOR'			=>  ($item['type'] !== 'vendor') ? '' : 'checked="checked"',
				'EDIT_S'			=>	'',
				'EDIT_E'			=>	'',
				'PHONE'				=>	$item['phone']
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'Create new user',
				'PAGE_HINT'			=>	'Modify the fields below to edit this user',
				'FNAME'				=>  $helper->prefill('fname'),
				'LNAME'				=>  $helper->prefill('lname'),
				'EMAIL'				=>  $helper->prefill('email'),
				'IMAGE'				=>  "../assets/img/profile.na.png",
				'PASSWORD'			=>  $helper->prefill('password'),
				'ACTIVE'			=>  'checked="checked"',
				'INACTIVE'			=>  '',
				'CUSTOMER'			=>  'checked="checked"',
				'VENDOR'			=>  '',
				'EDIT_S'			=>	'<!--',
				'EDIT_E'			=>	'-->',
				'PHONE'				=>	''
			));
		}
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
	
	public function getList(&$glob)
	{
		global $helper;
		
		$p = new Parser(get_class($this) . ".list.html");
		$p->defineBlock('item');

		$where = "
			WHERE
				(CONCAT(LOWER(fname), ' ', LOWER(lname)) LIKE '%" . strtolower($glob['param']['filter']) . "%') OR
				(LOWER(email) LIKE '%" . strtolower($glob['param']['filter']) . "%')
		";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'name';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'asc';
		$index = $glob['param']['offset'];
		$sql = "SELECT member_id, CONCAT(fname, ' ',  lname) AS name, email, date, active, deleted FROM member {$where} ORDER BY {$sort} {$order}";
		
		$res = $this->db->run($sql);
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{			
			$r = $res[$index];
			
			$p->parseBlock(array(
				'ID'			=>	$r['member_id'],
				'NAME'			=>	$r['name'],
				'EMAIL'			=>	$r['email'],
				'REGISTRATION'	=>	date("d-m-Y H:i", $r['date']),
				'STATUS'		=>	($r['active'] === '1') ? '<span class="ico ico-check"></span>' : '<span class="ico ico-clear"></span>',
				'DELETED'		=>	($r['deleted'] === '1') ? '<span class="ico ico-check"></span>' : '<span class="ico ico-clear"></span>'
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Website members',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="8" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset']),
			'EXPORT_QUERY'	=>	base64_encode($sql),
			'EXPORT_QUERY_NEWSLETTER'	=>	base64_encode("SELECT member_id, CONCAT(fname, ' ', lname) AS name, email, date FROM member ORDER BY member_id DESC"),
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}

	public function delete(&$glob)
	{
		global $helper;
		
		foreach ($glob['ids'] as $id)
		{
			// clean up profile images
			@unlink("../uploads/profile/{$id}.jpg");
		}

		$glob['ids'] = implode(",", $glob['ids']);
		
		$this->db->run("DELETE FROM member WHERE member_id IN ({$glob['ids']})");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}
}