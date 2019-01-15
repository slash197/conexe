<?php
/*
 * @Author: Slash Web Design						
 */

class languages
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
		$glob['data']['url'] = $helper->sanitizeURL($glob['data']['url']);
		
		if ($glob['id'] !== '')
		{
			$this->db->update("menu", $glob['data'], "menu_id = {$glob['id']}");
		}
		else
		{
			$this->db->insert("menu", $glob['data']);
			$glob['id'] = $this->db->lastInsertId();
		}
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}
	
	public function getItem(&$glob)
	{
		global $helper;
		
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$res = $this->db->run("SELECT * FROM menu WHERE menu_id = {$glob['id']}");
			$item = $res[0];

			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['label'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this menu item',
				'LABEL'				=>  htmlentities($item['label']),
				'URL'				=>	htmlentities($item['url']),
				'PARENT'			=>	$this->menuDD(0, $item['parent_id']),
				'SORT_ORDER'		=>	htmlentities($item['sort_order']),
				'SITE_URL'			=>	SITE_URL,
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'New menu item',
				'PAGE_HINT'			=>	'Fill in the fields below to set up a new menu item',
				'LABEL'				=>	$helper->prefill('label'),
				'URL'				=>	$helper->prefill('url'),
				'SITE_URL'			=>	SITE_URL,
				'SORT_ORDER'		=>	$helper->prefill('sort_order'),
				'PARENT'			=>	$this->menuDD(0, $helper->prefill('parent_id')),
			));
		}
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
	
	public function getList(&$glob)
	{
		global $helper, $config;
		
		$p = new Parser(get_class($this) . ".list.html");
		$p->defineBlock('item');
		
		$res = $this->db->run("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='{$config['database']}' AND `TABLE_NAME`= 'language'");
		
		foreach ($res as $item)
		{
			if ($item['COLUMN_NAME'] === 'id') continue;
			if ($item['COLUMN_NAME'] === 'base') $item['COLUMN_NAME'] = 'english';
			
			$p->parseBlock(array(
				'LABEL'		=>	$item['COLUMN_NAME'],
			), 'item');
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Website languages',
			'FILTER'		=>	$glob['param']['filter'],
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="6" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset'])
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}

	public function delete(&$glob)
	{
		global $helper;

		$glob['ids'] = implode(",", $glob['ids']);
		
		$this->db->run("DELETE FROM menu WHERE menu_id IN ({$glob['ids']})");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}
}