<?php
/*
 * @Author: Slash Web Design						
 */

class kira
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
			$this->db->update("kira", $glob['data'], "item_id = {$glob['id']}");
		}
		else
		{
			$this->db->insert("kira", $glob['data']);
			$glob['id'] = $this->db->lastInsertId();
		}
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}
	
	public function getItem(&$glob)
	{
		global $site_url, $helper;
		
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$res = $this->db->run("SELECT * FROM kira WHERE item_id = {$glob['id']}");
			$item = $res[0];
			
			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['name'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this item',
				'NAME'				=>  htmlentities($item['name']),
				'QUANTITY'			=>	$item['quantity'],
				'SORT_ORDER'		=>	$item['sort_order']
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'Create new kirawawa item',
				'PAGE_HINT'			=>	'Modify the fields below to edit this item',
				'NAME'				=>	$helper->prefill('name'),
				'QUANTITY'			=>	$helper->prefill('quantity'),
				'SORT_ORDER'		=>	$helper->prefill('sort_order')
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

		$where = "WHERE (LOWER(name) LIKE '%" . strtolower($glob['param']['filter']) . "%')";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'name';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'asc';
		$index = $glob['param']['offset'];
		$sql = "SELECT * FROM kira {$where} ORDER BY {$sort} {$order}";
		
		$res = $this->db->run($sql);
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'			=>	$r['item_id'],
				'NAME'			=>	$r['name'],
				'QUANTITY'		=>	$r['quantity'],
				'SORT_ORDER'	=>	$r['sort_order']
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Kirawawa items',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="5" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset']),
			'EXPORT_QUERY'	=>	base64_encode($sql)
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
	
	public function delete(&$glob)
	{
		global $helper;

		$glob['ids'] = implode(",", $glob['ids']);
		
		$this->db->run("DELETE FROM kira WHERE item_id IN ({$glob['ids']})");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}
}
?>