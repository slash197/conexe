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
		
		if ($glob['id'] !== '')
		{
			$this->db->run("ALTER TABLE language CHANGE `{$glob['id']}` `{$glob['data']['label']}` TEXT");
			$glob['id'] = $glob['data']['label'];
		}
		else
		{
			$this->db->run("ALTER TABLE language ADD COLUMN {$glob['data']['label']} TEXT");
			$glob['id'] = $glob['data']['label'];
		}
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}
	
	public function getItem(&$glob)
	{
		global $helper;
		
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	$glob['id'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this language item',
				'LABEL'				=>  htmlentities($glob['id'])
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'New language',
				'PAGE_HINT'			=>	'Fill in the fields below to set up a new language',
				'LABEL'				=>	$helper->prefill('label')
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
			$name = ($item['COLUMN_NAME'] === 'base') ? 'english' : $item['COLUMN_NAME'];
			
			$p->parseBlock(array(
				'ID'		=>	$item['COLUMN_NAME'],
				'LABEL'		=>	$name,
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

		$glob['ids'] = implode(", DROP COLUMN ", $glob['ids']);
		
		$this->db->run("ALTER TABLE language DROP COLUMN {$glob['ids']}");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}
}