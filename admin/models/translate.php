<?php
/*
 * @Author: Slash Web Design						
 */

class translate
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
		
		$this->db->update("language", array($glob['data']['language'] => $glob['data']['translated']), "id = {$glob['id']}");
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}
	
	public function getItem(&$glob)
	{
		global $helper;
		
		$p = new Parser(get_class($this) . ".item.html");
		$field = $glob['param']['key'];

		$res = $this->db->run("SELECT base, {$field} FROM language WHERE id = {$glob['id']}");
		$item = $res[0];

		$p->parseValue(array(
			'PAGE_TITLE'		=>	$item['base'],
			'PAGE_HINT'			=>	'Modify the fields below to edit this translation',
			'TRANSLATED'		=>  htmlentities($item[$field]),
			'LANGUAGE'			=>	$field
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
	
	public function getList(&$glob)
	{
		global $helper;
		
		$p = new Parser(get_class($this) . ".list.html");
		$p->defineBlock('item');

		$field = $glob['param']['key'];
		$index = $glob['param']['offset'];
		$where = "
			WHERE
				(LOWER({$field}) LIKE '%" . strtolower($glob['param']['filter']) . "%') OR
				(LOWER(base) LIKE '%" . strtolower($glob['param']['filter']) . "%')
		";
		
		$res = $this->db->run("SELECT id, base, {$field } FROM language {$where} ORDER BY id ASC");
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'		=>	$r['id'],
				'BASE'		=>	$r['base'],
				'EXPRESSION'=>	$r[$field]
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Website language "' . $field . '"',
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