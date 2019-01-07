<?php
/*
 * @Author: Slash Web Design						
 */

class staff
{
	var $db;
	
	function __construct()
	{
		global $db;
		
		$this->db = $db;
	}
	
	public function save(&$glob)
	{
		global $helper;
		
		parse_str($glob['data'], $glob['data']);
		if (!isset($glob['data']['is_director'])) $glob['data']['is_director'] = 0;
		
		if ($glob['id'] !== '')
		{
			$this->db->update("staff", $glob['data'], "staff_id = {$glob['id']}");
		}
		else
		{
			$this->db->insert("staff", $glob['data']);
			$glob['id'] = $this->db->lastInsertId();
		}
		
		if (isset($glob['data']['image']) && ($glob['data']['image'] !== ''))
		{
			$filename = "{$glob['id']}.jpg";
			$r = new Resize("../" . $glob['data']['image']);
			$r->resizeImage(200, 200, 'crop');
			$r->saveImage("../uploads/staff/{$filename}", 100);

			unset($r);
			@unlink("../" . $glob['data']['image']);
		}
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}
	
	public function getItem(&$glob)
	{
		global $site_url, $helper;
		
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$res = $this->db->run("SELECT * FROM staff WHERE staff_id = {$glob['id']}");
			$item = $res[0];
			
			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['name'],
				'PAGE_HINT'			=>	'Manage staff member details',
				'SRC'				=>	file_exists("../uploads/staff/{$item['staff_id']}.jpg") ? "../uploads/staff/{$item['staff_id']}.jpg" : "../assets/img/profile.na.png",
				'SORT_ORDER'		=>	$item['sort_order'],
				'NAME'				=>	$item['name'],
				'ROLE'				=>	$item['role'],
				'IS_DIRECTOR'		=>	($item['is_director'] === '1') ? 'checked="checked"' : '',
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'New staff member',
				'PAGE_HINT'			=>	'Manage staff member details',
				'SRC'				=>	'../assets/img/profile.na.png',
				'SORT_ORDER'		=>	$helper->prefill('sort_order'),
				'NAME'				=>	$helper->prefill('name'),
				'ROLE'				=>	$helper->prefill('role'),
				'IS_DIRECTOR'		=>	'',
			));
		}
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
	
	public function getList(&$glob)
	{
		global $site_url, $helper;
		
		$p = new Parser(get_class($this) . ".list.html");
		$p->defineBlock('item');

		$where = "
			WHERE (LOWER(name) LIKE '%" . strtolower($glob['param']['filter']) . "%')
		";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'sort_order';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'asc';
		$index = $glob['param']['offset'];
		
		$res = $this->db->run("SELECT * FROM staff {$where} ORDER BY {$sort} {$order}");
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'			=>	$r['staff_id'],
				'IMAGE'			=>	file_exists("../uploads/staff/{$r['staff_id']}.jpg") ? "../uploads/staff/{$r['staff_id']}.jpg" : "../assets/img/profile.na.png",
				'NAME'			=>	$r['name'],
				'ROLE'			=>	$r['role'],
				'IS_DIRECTOR'	=>	($r['is_director'] === '1') ? 'Yes' : 'No',
				'SORT_ORDER'	=>	$r['sort_order']
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Staff members',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="7" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset'])
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}

	public function delete(&$glob)
	{
		global $db, $helper;

		foreach ($glob['ids'] as $id)
		{
			@unlink("../uploads/staff/{$id}.jpg");
		}
		$glob['ids'] = implode(",", $glob['ids']);
		
		$this->db->run("DELETE FROM staff WHERE staff_id IN ({$glob['ids']})");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}
}
?>