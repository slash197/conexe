<?php
/*
 * @Author: Slash Web Design						
 */

class moms
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
		$glob['data']['baby_date'] = strtotime($glob['data']['baby_date']);
		
		if (!isset($glob['data']['featured'])) $glob['data']['featured'] = 0;
		
		if ($glob['id'] !== '')
		{
			$this->db->update("mom", $glob['data'], "mom_id = {$glob['id']}");
		}
		else
		{
			$this->db->insert("mom", $glob['data']);
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
			$p->defineBlock('fund');
			
			$funds = $this->db->run("SELECT * FROM transaction WHERE mom_id = {$glob['id']} AND status = 'success' ORDER BY date DESC");
			foreach ($funds as $r)
			{
				$p->parseBlock(array(
					'DATE'		=>	date('F d, Y H:i:s', $r['date']),
					'TYPE'		=>	$r['type'],
					'AMOUNT'	=>	$r['amount'],
					'ACTION'	=>	($r['type'] === 'assigned') ? '<button class="btn btn-danger" data-id="' . $r['transaction_id'] . '"><span class="ico ico-clear"></span>delete</button>' : ''
				), 'fund');
			}
			
			$res = $this->db->run("SELECT * FROM mom WHERE mom_id = {$glob['id']}");
			$item = $res[0];
			
			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['fname'] . ' ' . $item['lname'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this profile',
				'FEATURED'			=>	($item['featured'] === '1') ? 'checked="checked"' : '',
				'FNAME'				=>  htmlentities($item['fname']),
				'LNAME'				=>  htmlentities($item['lname']),
				'AGE'				=>  $item['age'],
				'DESC_SHORT'		=>	htmlentities($item['desc_short']),
				'DESC_LONG'			=>	htmlentities($item['desc_long']),
				'BABY_NAME'			=>	$item['baby_name'],
				'BABY_GENDER'		=>	$this->buildGenders($item['baby_gender']),
				'BABY_DATE'			=>	date("m/d/Y", $item['baby_date']),
				'IMAGES'			=>  $this->getImages($glob['id']),
				'FUNDS_EMPTY'		=>	(count($funds) === 0) ? '<tr><td colspan="3" class="empty">There are no items to display</td></tr>' : '',
				'RAISED'			=>	number_format($helper->getFunds($glob['id']), 2),
				'TARGET'			=>	number_format(FUND_TARGET, 2),
				'UF'				=>	number_format($helper->getUniversalFunds(), 2),
				'SHOW_ON_EDIT'		=>	'',
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'Create new mom profile',
				'PAGE_HINT'			=>	'Modify the fields below to edit this profile',
				'FEATURED'			=>	'',
				'FNAME'				=>  $helper->prefill('fname'),
				'LNAME'				=>  $helper->prefill('lname'),
				'AGE'				=>  $helper->prefill('age'),
				'BABY_NAME'			=>  $helper->prefill('baby_name'),
				'BABY_DATE'			=>  $helper->prefill('baby_date'),
				'BABY_GENDER'		=>  $this->buildGenders($helper->prefill('baby_gender')),
				'DESC_SHORT'		=>  $helper->prefill('desc_short'),
				'DESC_LONG'			=>  $helper->prefill('desc_long'),
				'IMAGE'				=>  "../assets/img/profile.na.png",
				'SHOW_ON_EDIT'		=>	'hide',
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
		$sql = "SELECT mom_id, CONCAT(fname, ' ',  lname) AS name, age, baby_name, baby_date, featured FROM mom {$where} ORDER BY {$sort} {$order}";
		
		$res = $this->db->run($sql);
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'			=>	$r['mom_id'],
				'NAME'			=>	$r['name'],
				'AGE'			=>	$r['age'],
				'BABY_NAME'		=>	$r['baby_name'],
				'BABY_DATE'		=>	date('d F Y', $r['baby_date']),
				'FEATURED'		=>	($r['featured'] === '1') ? 'Yes' : 'No',
				'FUNDS'			=>	$helper->getFunds($r['mom_id'])
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Mom profiles',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="8" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset']),
			'EXPORT_QUERY'	=>	base64_encode($sql)
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
	
	public function reassign(&$glob)
	{
		global $helper;

		$this->db->run("DELETE FROM transaction WHERE transaction_id = {$glob['id']} AND status = 'success'");
		
		$helper->respond(array('error' => 0, 'message' => 'Funds reassigned to Universal Fund successfully'));
	}
	
	public function assign(&$glob)
	{
		global $helper;
		
		$glob['amount'] = (float) $glob['amount'];
		
		if ($glob['amount'] < 1) $helper->respond(array('error' => 1, 'message' => 'Amount must be greater than 1'));
		if ($glob['amount'] > $helper->getUniversalFunds()) $helper->respond(array('error' => 1, 'message' => 'Amount must be less than or equal to Universal Fund total'));
		if ($glob['amount'] + $helper->getFunds($glob['mom_id']) > FUND_TARGET) $helper->respond(array('error' => 1, 'message' => 'Amount must be less than or equal to remaining value of target'));
		
		$this->db->insert("transaction", array(
			'type'			=>	'assigned',
			'member_id'		=>	0,
			'mom_id'		=>	$glob['mom_id'],
			'amount'		=>	$glob['amount'],
			'status'		=>	'success',
			'date'			=>	time()
		));
		
		$helper->respond(array('error' => 0, 'message' => 'Funds assigned successfully'));
	}
	
	private function getImages($id)
	{
		$images = array();
		
		$res = $this->db->run("SELECT image_id, filename FROM mom_image WHERE mom_id = {$id}");
		foreach ($res as $r)
		{
			$images[] = '<div><img src="../uploads/mom/' . $r['filename'] . '" /><button class="btn btn-info btn-delete" data-id="' . $r['image_id'] . '"><span class="ico ico-clear"></span> remove</button><button class="btn btn-info btn-profile" data-id="' . $r['image_id'] . '"><span class="ico ico-account-circle"></span> profile</button></div>';
		}
		
		return implode('', $images);
	}
	
	public function image(&$glob)
	{
		global $helper;
		
		$filename = $glob['id'] . "-" . microtime(true) . ".jpg";
		
		$this->db->insert("mom_image", array(
			'mom_id'	=>	$glob['id'],
			'filename'	=>	$filename
		));
		$imageId = $this->db->lastInsertId();
		
		$r = new Resize("../" . $glob['image']);
		$r->resizeImage(775, 420, 'crop');
		$r->saveImage("../uploads/mom/{$filename}", 100);
			
		unset($r);
		@unlink("../" . $glob['image']);
		
		$helper->respond(array('error' => 0, 'message' => 'Image uploaded successfully', 'filename' => $filename, 'id' => $imageId));
	}
	
	public function imageDelete(&$glob)
	{
		global $helper;
		
		$res = $this->db->run("SELECT filename FROM mom_image WHERE image_id = {$glob['id']}");
		$this->db->run("DELETE FROM mom_image WHERE image_id = {$glob['id']}");
		
		@unlink("../uploads/mom/" . $res[0]['filename']);
		
		$helper->respond(array('error' => 0, 'message' => 'Image deleted successfully'));
	}
	
	public function imageProfile(&$glob)
	{
		global $helper;
		
		$this->db->run("UPDATE mom_image SET profile = 0 WHERE mom_id = {$glob['mom_id']}");
		$this->db->run("UPDATE mom_image SET profile = 1 WHERE image_id = {$glob['id']}");
		
		$helper->respond(array('error' => 0, 'message' => 'Image set as profile successfully'));
	}

	public function delete(&$glob)
	{
		global $helper;
		
		foreach ($glob['ids'] as $id)
		{
			// clean up profile images
			$res = $this->db->run("SELECT filename FROM mom_image WHERE mom_id = {$id}");
			foreach ($res as $r)
			{
				@unlink("../uploads/mom/{$r['filename']}");
			}
			
			$this->db->run("DELETE FROM mom_image WHERE mom_id = {$id}");
			$this->db->run("DELETE FROM mom_update WHERE mom_id = {$id}");
			$this->db->run("DELETE FROM transaction WHERE mom_id = {$id} AND type = 'assigned'");
		}
		
		$glob['ids'] = implode(",", $glob['ids']);
		
		$this->db->run("DELETE FROM mom WHERE mom_id IN ({$glob['ids']})");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}

	private function buildGenders($gender = '')
	{
		global $db;

		$arr = array('male', 'female');

		$out = '<option value="">select gender</option>';
		foreach ($arr as $m)
		{
			$sel = ($gender == $m) ? 'selected="selected"' : '';
			$out .= '<option value="' . $m . '" ' . $sel . '>' . $m . '</option>';
		}

		return $out;
	}
}
?>