<?php
/*
 * @Author: Slash Web Design						
 */

class transactions
{
	var $db;
	
	function __construct()
	{
		global $db;
		
		$this->db = $db;
	}
	
	public function getList(&$glob)
	{
		global $site_url, $helper;
		
		$p = new Parser(get_class($this) . ".list.html");
		$p->defineBlock('item');

		$where = "
			WHERE 
				(LOWER(fname) LIKE '%{$glob['param']['filter']}%' OR LOWER(lname) LIKE '%{$glob['param']['filter']}%') AND
				t.member_id = m.member_id
		";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'date';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'desc';
		$index = $glob['param']['offset'];
		
		$res = $this->db->run("SELECT t.*, CONCAT(m.fname, ' ', m.lname) AS name FROM transaction t, member m {$where} ORDER BY {$sort} {$order}");
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			
			$res2 = $this->db->run("SELECT CONCAT(fname, ' ', lname) AS name FROM mom WHERE mom_id = {$r['mom_id']}");
			$to = (count($res2) > 0) ? $res2[0]['name'] : 'Universal Fund';
			
			$p->parseBlock(array(
				'ID'		=>	$r['transaction_id'],
				'DATE'		=>	date("d/m/Y H:i", $r['date']),
				'NAME'		=>  $r['name'],
				'PAYMENT'	=>  $r['payment'],
				'TYPE'		=>	$r['type'],
				'TO'		=>	$to,
				'AMOUNT'	=>  '$ ' . number_format($r['amount'], 2, ".", ","),
				'STATUS'	=>	$r['status']
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Transactions',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="8" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset'])
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
}
?>