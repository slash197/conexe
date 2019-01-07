<?php
/*
 * @Author: Slash Web Design						
 */

class updates
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
		$glob['data']['date'] = strtotime($glob['data']['date']);
		
		if ($glob['id'] !== '')
		{
			$this->db->update("mom_update", $glob['data'], "update_id = {$glob['id']}");
		}
		else
		{
			$this->db->insert("mom_update", $glob['data']);
			$glob['id'] = $this->db->lastInsertId();
			
			$res = $this->db->run("SELECT fname FROM mom WHERE mom_id = {$glob['data']['mom_id']}");
			$mom = $res[0];
			
			// get all supporters
			$res = $this->db->run("SELECT DISTINCT t.member_id, m.fname, m.email FROM member m, transaction t WHERE t.mom_id = {$glob['data']['mom_id']} AND t.member_id = m.member_id");
			foreach ($res as $r)
			{
				$helper->sendMailTemplate(
					'profile.update',
					array('[NAME]', '[MOM]', '[URL]', '[UPDATE]'),
					array($r['fname'], $mom['fname'], SITE_URL . "mom/{$glob['data']['mom_id']}/{$mom['fname']}", $glob['data']['text']),
					array('name' => $r['fname'], 'email' => $r['email'])
				);
			}
		}
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}
	
	public function getItem(&$glob)
	{
		global $site_url, $helper;
		
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$res = $this->db->run("SELECT m.fname, m.lname, mu.* FROM mom m, mom_update mu WHERE mu.update_id = {$glob['id']} AND mu.mom_id = m.mom_id");
			$item = $res[0];
			
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'Update for ' . $item['fname'] . ' ' . $item['lname'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this update',
				'FNAME'				=>  htmlentities($item['fname']),
				'LNAME'				=>  htmlentities($item['lname']),
				'MOMS'				=>	$this->buildMoms($item['mom_id']),
				'TEXT'				=>	htmlentities($item['text']),
				'DATE'				=>	date("m/d/Y", $item['date'])
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'Create new mom update',
				'PAGE_HINT'			=>	'Modify the fields below to edit this update',
				'MOMS'				=>	$this->buildMoms($helper->prefill('mom_id')),
				'TEXT'				=>	$helper->prefill('text'),
				'DATE'				=>	$helper->prefill('date')
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

		$where = "WHERE (CONCAT(LOWER(fname), ' ', LOWER(lname)) LIKE '%" . strtolower($glob['param']['filter']) . "%')";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'name';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'asc';
		$index = $glob['param']['offset'];
		$sql = "SELECT m.mom_id, CONCAT(m.fname, ' ', m.lname) AS name, mu.date, mu.update_id, mu.text FROM mom m, mom_update mu {$where} AND mu.mom_id = m.mom_id ORDER BY {$sort} {$order}";
		
		$res = $this->db->run($sql);
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'			=>	$r['update_id'],
				'NAME'			=>	$r['name'],
				'TEXT'			=>	$helper->strLimit($r['text'], 128),
				'DATE'			=>	date('d F Y', $r['date']),
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Mom updates',
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
		
		$this->db->run("DELETE FROM mom_update WHERE update_id IN ({$glob['ids']})");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}
	
	private function buildMoms($id)
	{
		$out = '';
		$res = $this->db->run("SELECT mom_id, fname, lname FROM mom ORDER BY lname ASC");
		foreach ($res as $r)
		{
			$sel = ($id === $r['mom_id']) ? 'selected="selected"' : '';
			//$out .= '<option value="' . $r['mom_id'] . '" ' . $sel . '>' . $r['fname'] . ' ' . $r['lname'] . ' [ID = ' . $r['mom_id'] . ']</option>';
			$out .= '<option value="' . $r['mom_id'] . '" ' . $sel . '>' . $r['fname'] . ' ' . $r['lname'] . '</option>';
		}
		
		return $out;
	}
}
?>