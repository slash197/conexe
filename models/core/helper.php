<?php
/*
 * @Author: Slash Web Design
 */

class Helper
{
	protected $db;
	protected $timer;
	
	function __construct()
	{
		global $db;
		
		$this->db = $db;
		$this->startUp();
	}
	
	public function sendInvoice($transactionId)
	{
		$res = $this->db->run("SELECT t.*, m.fname, m.email FROM transaction t, member m WHERE t.transaction_id = {$transactionId} AND t.member_id = m.member_id");
		$data = $res[0];
		
		$this->sendMailTemplate(
			'payment.invoice',
			array('[NAME]', '[INVOICE_DATA]'),
			array($data['fname'], $this->generateInvoice($data)),
			array('name' => $data['fname'], 'email' => $data['email'])
		);
	}
	
	public function generateInvoice($data)
	{
		$index = 1;
		$items = '<tr><td>' . $index . '. </td><td>' . $this->getDescription($data['type']) . '</td><td align="right">$ ' . number_format($data['amount'], 2) . '</td></tr>';
		
		if ($data['tip'] !== '0.00')
		{
			$index++;
			$items .= '<tr><td>' . $index . '. </td><td>Tip</td><td align="right">$ ' . number_format($data['tip'], 2) . '</td></tr>';
		}
		
		if ($data['card_amount'] !== '0.00')
		{
			$index++;
			$items .= '<tr><td>' . $index . '. </td><td>Gift card</td><td align="right">- $ ' . number_format($data['card_amount'], 2) . '</td></tr>';
		}
		
		$total = '<tr style="background-color: #f0f0f0"><td></td><td align="right"><strong>Total</strong></td><td align="right"><strong>$ ' . number_format($data['amount'] + $data['tip'] - $data['card_amount'], 2) . '</strong></td></tr>';
		
		return
			'<table style="width: 50%; font-size: 16px; color: #3a4fa2;">' .
				'<thead>' .
					'<th style="width: 10%; text-align: left;">Item ID</th>' .
					'<th style="width: 60%; text-align: left;">Description</th>' .
					'<th style="width: 30%; text-align: right;">Price</th>' .
				'</thead>' .
				'<tbody>' . 
					'<tr><td colspan="3" height="20"></td></tr>' .
					$items . 
					$total .
				'</tbody>' .
			'</table>'
		;
	}
	
	public function getDescription($type)
	{
		switch ($type)
		{
			case 'payment': return 'Donation';
			case 'gift card': return 'Gift card';
			case 'universal fund': return 'Universal fund donation';
		}
	}
	
	public function hasReachedTarget($momId)
	{
		$funds = $this->getFunds($momId);
		
		if ($funds >= FUND_TARGET)
		{
			$res = $this->db->run("SELECT fname FROM mom WHERE mom_id = {$momId}");
			$mom = $res[0];
			
			// get all supporters
			$res = $this->db->run("SELECT DISTINCT t.member_id, m.fname, m.email FROM member m, transaction t WHERE t.mom_id = {$momId} AND t.member_id = m.member_id");
			foreach ($res as $r)
			{
				$this->sendMailTemplate(
					'profile.fundingreached',
					array('[NAME]', '[MOM]', '[URL]'),
					array($r['fname'], $mom['fname'], SITE_URL . "{$momId}/{$mom['fname']}"),
					array('name' => $r['fname'], 'email' => $r['email'])
				);
			}
		}
	}
	
	public function getCardBuyer($cardId)
	{
		$res = $this->db->run("SELECT m.* FROM card c, member m, transaction t WHERE c.card_id = {$cardId} AND c.transaction_id = t.transaction_id AND t.member_id = m.member_id");
		
		return (count($res) > 0) ? $res[0] : false;
	}
	
	public function createWebhook()
	{
		if (!$this->checkWebhook())
		{
			$webhook = new \PayPal\Api\Webhook();

			$webhook->setUrl(SITE_URL . "daemon.php");

			$webhookEventTypes = array();
			$webhookEventTypes[] = new \PayPal\Api\WebhookEventType('{"name":"PAYMENT.SALE.COMPLETED"}');
			$webhook->setEventTypes($webhookEventTypes);

			try
			{
				$webhook->create($this->createPayPalAPIContext());
			}
			catch (Exception $ex)
			{
				$this->p($ex, 1);
				$glob['error'] = $this->helper->buildMessageBox('error', $ex->getMessage());
				return false;
			}
		}
	}
	
	public function checkWebhook()
	{
		try
		{
			$output = \PayPal\Api\Webhook::getAll($this->createPayPalAPIContext());
			
			return count($output->webhooks) > 0;
		}
		catch (Exception $ex)
		{
			$this->p($ex, 1);
			$glob['error'] = $this->helper->buildMessageBox('error', $ex->getMessage());
			return false;
		} 
	}
	
	public function validateCode($code)
	{
		$res = $this->db->run("SELECT * FROM card WHERE code = '{$code}' AND active = 1");
		if (count($res) > 0)
		{
			$response = array(
				'error'		=>	0,
				'message'	=>	'Gift card code is valid',
				'amount'	=>	(float) $res[0]['amount'],
				'id'		=>	$res[0]['card_id']
			);
		}
		else
		{
			$response = array(
				'error'		=>	1,
				'message'	=>	'Gift card code is not valid',
				'amount'	=>	0,
				'id'		=>	0
			);
		}
		
		return $response;
	}
	
	public function createPayPalAPIContext()
	{
		$apiContext = new ApiContext(
			new OAuthTokenCredential(PAYPAL_CLIENT_ID, PAYPAL_SECRET)
		);

		$apiContext->setConfig(
			array(
				'mode' => PAYPAL_MODE,
				'log.LogEnabled' => false,
				'log.FileName' => '../PayPal.log',
				'log.LogLevel' => 'DEBUG', // PLEASE USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
				'cache.enabled' => false
			)
		);

		return $apiContext;
	}
	
	public function getFunds($id)
	{
		$res = $this->db->run("SELECT SUM(amount) AS funds FROM transaction WHERE mom_id = {$id} AND status = 'success'");
		$funds = (float) $res[0]['funds'];
		
		if ($funds > FUND_TARGET) $funds = FUND_TARGET;
		
		return $funds;
	}
	
	public function getFunders($id)
	{
		$res = $this->db->run("SELECT COUNT(DISTINCT member_id) AS total FROM transaction WHERE mom_id = {$id} AND status = 'success'");
		return $res[0]['total'];
	}
	
	public function getUniversalFunds()
	{
		$credit = $this->db->run("SELECT SUM(amount) AS credit FROM transaction WHERE type = 'universal fund' AND status = 'success'");
		$debit = $this->db->run("SELECT SUM(amount) AS debit FROM transaction WHERE type = 'assigned' AND status = 'success'");
		
		return (float) $credit[0]['credit'] - (float) $debit[0]['debit'];
	}
	
	public function genderDD($g)
	{
		$out = '';
		$arr = array('male', 'female');
		foreach ($arr as $a)
		{
			$sel = ($a === $g) ? 'selected="selected"' : '';
			$out .= '<option value="' . $a . '" ' . $sel . '>' . $a . '</option>';
		}
		
		return $out;
	}

	public function timePassed($ts)
	{
		$diff = time() - $ts;
		
		if ($diff < 3600)
		{
			if ($diff < 60)
			{
				return ($diff == 1) ? "{$v} second ago" : "{$v} seconds ago";
			}
			
			$v = round($diff / 60);
			return ($v == 1) ? "{$v} minute ago" : "{$v} minutes ago";
		}
		
		$v = round($diff / 3600);
		return ($v == 1) ? "{$v} hour ago" : "{$v} hours ago";
	}

	public function getRatingHTML($value)
	{
		$out = '';
		for ($i = 1; $i <= 5; $i++)
		{
			if ($value >= 1)
			{
				$out .= '<span class="ico ico-star"></span>';
				$value--;
			}
			else if ($value >= 0.5)
			{
				$out .= '<span class="ico ico-star-half"></span>';
				$value = 0;
			}
			else
			{
				$out .= '<span class="ico ico-star-outline"></span>';
			}
		}
		return $out;
	}

	protected function mailWrap($content)
	{
		return '
		<table width="100%" style="background-color: #e0e0e0; margin: 0px;">
			<tr>
				<td height="100">&nbsp;</td>
			</tr>
			<tr>
				<td>
					<table style="font-family: Arial; font-size: 14px; background-color: #ffffff; color: #636363; border-bottom: 2px solid #d0d0d0" width="80%" align="center" cellpadding="20" cellspacing="0">
						<tr>
							<td align="center"><img src="' . SITE_URL . 'assets/img/logo.dark.png" style="max-height: 60px" /></td>
						</tr>
						<tr>
							<td>' . $content . '</td>
						</tr>
						<tr>
							<td align="center">
								<div style="border-top: 1px solid #e0e0e0; font-size: 0px; margin-bottom: 20px">&nbsp;</div>
								<a style="color: #62a8ea; text-decoration: none" href="' . SITE_URL . '">' . SITE_NAME . ' &copy; ' . date("Y", time()) . '</a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td height="100">&nbsp;</td>
			</tr>
		</table>';
	}
	
	public function sendMailTemplate($code, $in, $out, $to)
	{
		$res = $this->db->run("SELECT * FROM email WHERE code = '{$code}'");
		$m = $res[0];
		
		return $this->sendMail(
			str_replace($in, $out, $m['content']),
			$m['subject'],
			$to,
			array('name' => $m['from_name'], 'email' => $m['from_address'])
		);
	}
	
	public function sendMail($body, $subject, $to, $from = null, $wrap = true)
	{
		$mail = new Mail();
		
		$mail->setOptions(array(
			'to'		=>	$to,
			'from'		=>	$from ? $from : array('name' => 'Conexe Support', 'email' => 'support@conexe.com'),
			'subject'	=>	$subject,
			'body'		=>	($wrap) ? $this->mailWrap($body) : $body
		));
		
		return $mail->send();
	}
	
	public function encrypt($plain)
	{
		$c = new Cryptor();
		return $c->Encrypt($plain, 'abc');
	}
	
	public function decrypt($encrypted)
	{
		$c = new Cryptor();
		return $c->Decrypt($encrypted, 'abc');
	}
	
	public function respond($obj, $html = false)
	{
		//sleep(5);
		if ($html === true)
		{
			header("Content-type: text/html; charset=utf-8;");
			echo $obj;
		}
		else
		{
			header("Content-type: application/json; charset=utf-8;");
			echo json_encode($obj);
		}
		die();
	}
	
	public function p($obj, $die = false, $dump = false, $return = false)
	{
		if ($return === true)
		{
			return '<pre>' . print_r($obj, true) . '</pre>';
		}
		
		echo '<pre>';
		if ($dump) var_dump($obj); else print_r($obj);
		echo '</pre>';
		if ($die) die();
	}
	
	public function prefill($key)
	{
		global $glob;
		return isset($glob[$key]) ? $glob[$key] : '';
	}
	
	public function esc($str)
	{
		return str_replace("'", "\'", $str);
	}
	
	public function startUp()
	{
		global $config, $glob;
		
		$this->db->run('SET NAMES utf8');
		$this->db->run('SET CHARACTER SET utf8');
		$this->db->run('SET COLLATION_CONNECTION="utf8_general_ci"');

		$res = $this->db->run("SELECT name, value FROM settings");
		foreach ($res as $r)
		{
			$config[$r['name']] = $r['value'];
		}
		
		// turns all config data to constants
		foreach ($config as $key => $value)
		{
			define(strtoupper($key), $value);
		}

		foreach($_POST as $key => $value)
		{
			$glob[$key] = $this->sanitizeInput($value);
		}
		foreach($_GET as $key => $value)
		{
			$glob[$key] = $this->sanitizeInput($value);
		}
		
		//$this->createWebhook();
	}
	
	public function timerStart()
	{
		$this->timer = microtime(1);
	}
	
	public function timerEnd($return = false)
	{
		$time = round(microtime(1) - $this->timer, 8);
		if ($return === true)
		{
			return $time;
		}
		p($time, 1);
	}
	
	public function sanitizeInput($param)
	{
		if (is_array($param))
		{
			$arr = array();
			foreach ($param as $key => $value)
			{
				if (is_array($value))
				{
					$arr2 = array();
					foreach ($value as $key2 => $value2)
					{
						$arr2[$key2] = strip_tags($value2);
					}
					
					$arr[$key] = $arr2;
				}
				else
				{
					$arr[$key] = strip_tags($value);
				}
			}
			
			return $arr;
		}
		
		return strip_tags($param);
	}
	
	public function sanitizeURL($str)
	{
		$out = '';
		
		$allowedChars = array(
			"0", "1", "2", "3", "4", "5", "6", "7", "8", "9",
			"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z" 
		);
		
		for ($i = 0; $i < strlen($str); $i++)
		{
			if (in_array(strtolower($str[$i]), $allowedChars))
			{
				$out .= strtolower($str[$i]);
			}
			else
			{
				switch ($str[$i])
				{
					case " ": $out .= '-'; break;
					case "-": $out .= '-'; break;
					case "&": $out .= '-'; break;
					case "!": $out .= '-'; break;
					case "?": $out .= '-'; break;
					case "@": $out .= '-'; break;
					case "$": $out .= '-'; break;
					case "*": $out .= '-'; break;
					case "/": $out .= '-'; break;
					case "|": $out .= '-'; break;
				}
			}
		}
		
		return $this->stripMultipleDashes($out);
	}
	
	protected function stripMultipleDashes($str)
	{
		return str_replace(array('------', '-----', '----', '---', '--'), '-', $str);
	}

	public function buildPagination($num_rows, $row_per_page, $offset)
	{
		global $glob;

		$visible = 3; //both sides
		$pages = ceil($num_rows / $row_per_page);
		$pn = ($offset / $row_per_page) + 1;

		//debug data
		$glob['pag-data']['rpp'] = $row_per_page;
		$glob['pag-data']['num_rows'] = $num_rows;
		$glob['pag-data']['pn'] = $pn;
		$glob['pag-data']['pages'] = $pages;

		if ($pages > 1)
		{
			$out_str = '<div class="pagination"><ul>';

			if ($pn > 1)
			{
				$out_str .= '<li><a href="#" data-offset="' . ($offset - $row_per_page) . '">Prev</a></li>';
			}
			else
			{
				$out_str .= '<li class="disabled"><span>Prev</span></li>';
			}

			if ((($pn - $visible) > 1) && (($pn + $visible) < $pages))
			{
				$out_str .= '<li class="disabled"><span>...</span></li>';
				for ($i = $pn - $visible; $i <= $pn + $visible; $i++)
				{
					($i == $pn) ? $sel = 'class="active"' : $sel = '';
					$out_str .= '<li ' . $sel . '><a href="#" data-offset="' . (($i - 1) * $row_per_page) . '">' . $i . '</a></li>';
				}
				$out_str .= '<li class="disabled"><span>...</span></li>';
			}

			if ($pn - $visible <= 1)
			{
				$to = ($pages > 7) ? 7 : $pages;
				for ($i = 1; $i <= $to ; $i++)
				{
					($i == $pn) ? $sel = 'class="active"' : $sel = '';
					$out_str .= '<li ' . $sel . '><a href="#" data-offset="' . (($i - 1) * $row_per_page) . '">' . $i . '</a></li>';
				}
				if ($pages > 7)	$out_str .= '<li class="disabled"><span>...</span></li>';
			}
			if (($pn + $visible >= $pages) && ($pages > 7))
			{
				$from = ($pages > 7) ? $pages - 7 : 1;
				if ($pages > 7)	$out_str .= '<li class="disabled"><span>...</span></li>';
				for ($i = $from; $i <= $pages; $i++)
				{
					($i == $pn) ? $sel = 'class="active"' : $sel = '';
					$out_str .= '<li ' . $sel . '><a href="#" data-offset="' . (($i - 1) * $row_per_page) . '">' . $i . '</a></li>';
				}
			}

			if (($pn * $row_per_page) < $num_rows)
			{
				$out_str .= '<li><a href="#" data-offset="' . ($offset + $row_per_page) . '">Next</a></li>';
			}
			else
			{
				$out_str .= '<li class="disabled"><span>Next</span></li>';
			}


			$out_str .= '</ul></div>';
			return $out_str;
		}
		return "";
	}

	public function strLimit($str, $limit = 128)
	{
		if (strlen($str) < $limit)
		{
			return $str;
		}
		else
		{
			return substr($str, 0, $limit) . "...";
		}
	}

	public function buildUserMenu()
	{
		global $user;

		if ($user === null) return '';

		return
			'<div class="user-menu">' .
				'<a><img src="' . $user->image . '" class="profile h30" />' . $user->fname . '</a><span class="ico ico-keyboard-arrow-down"></span>' . 
				'<div class="dd">' .
					'<ul>' .
						'<li><a href="account">My account</a></li>' .
						'<li class="divider"></li>' .
						'<li><a href="sign-out">Sign out</a></li>' .
					'</ul>' .
				'</div>' .
			'</div>'
		;
	}

	public function buildMainMenu($parent_id = 0, $addUser = false)
	{
		global $db, $user;

		$i = 0;
		$out = ($parent_id == 0) ? '<ul class="nav">' : '<ul class="dropdown-menu">';
		$items = $db->run("SELECT * FROM menu WHERE parent_id = $parent_id ORDER BY sort_order ASC");
		foreach ($items as $m)
		{
			$submenu = $this->buildMainMenu($m['menu_id']);
			if ($submenu == '<ul class="dropdown-menu"></ul>')
			{
				$out .= '<li><a href="' . $m['url'] . '">' . $m['label'] . '</a></li>';
			}
			else
			{
				$type = ($parent_id == 0) ? "dropdown" : "dropdown-submenu";
				$out .= '<li class="' . $type . '"><a href="' . $m['url'] . '" class="dropdown-toggle" data-toggle="dropdown">' . $m['label'] . '</a>' . $submenu . '</li>';
			}
			$i++;
		}

		//add user menu
		if ($addUser === true)
		{
			$out .= ($user != null) ? '<li>' . $this->buildUserMenu() . '</li>' : '<li><a href="sign-up">Sign up</a></li><li><a href="sign-in">Sign in</a></li>';
		}

		$out .= '</ul>';

		return $out;
	}

	public function buildPadding($level)
	{
		$out = '';

		if ($level == 0) return $out;
		if ($level == 1) return "└─";

		for ($i = 0; $i < $level; $i++)
		{
			$out .= "&nbsp;";
		}
		return $out . "└─";
	}

	public function buildParentDD($id = 0, $sp = -1, $parent = 0, $level = 0)
	{
		global $db;
		$out = ($level == '') ? '<option value="0">Top Level (no parent)</option>' : '';
		$res = $db->run("SELECT * FROM menu WHERE parent_id = $parent ORDER BY sort");
		foreach ($res as $m)
		{
			if (($id == 0) || (($id > 0) && ($id != $m['menu_id'])))
			{
				$sel = ($m['menu_id'] == $sp) ? 'selected="selected"' : '';
				$out .= '<option value="' . $m['menu_id'] . '" ' . $sel . '>' . buildPadding($level) . $m['label'] . '</option>';
			}

			$out .= buildParentDD($id, $sp, $m['menu_id'], $level + 1);
		}
		return $out;
	}

	public function buildMessageBox($type, $text, $block = true)
	{
		$ab = ($block) ? 'alert-block' : '';
		$title = ($block) ? '<h4>' . ucfirst($type) . '</h4>' : '';
		return '<div class="alert alert-' . $type . ' ' . $ab . '"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $title . $text . '</div>';
	}
	
	public function debug()
	{
		global $user, $glob, $pageLoadTimeStart;
		
		$pageLoadTimeEnd = microtime(true);
		$userObj = isset($user) ? print_r($user, true) : '';
		
		$ret = '
			<div class="debug-holder">
			<div class="controller"></div>
				<table class="debug">
					<tr>
						<td colspan="4"><strong>Current Directory: </strong>' . getcwd() . '</td>
					</tr>
					<tr>
						<td colspan="4"><strong>Session ID: </strong>' . session_id() . '</td>
					</tr>
					<tr>
						<td colspan="4"><strong>Load time: </strong>' . round($pageLoadTimeEnd - $pageLoadTimeStart, 10) . '</td>
					</tr>
					<tr>
						<td><strong>$glob</strong></td>
						<td><strong>$_FILES</strong></td>
						<td><strong>$_SESSION</strong></td>
						<td><strong>$user</strong></td>
					</tr>
					<tr>
						<td valign="top" width="25%"><pre>' . print_r($glob, true) . '</pre></td>
						<td valign="top" width="25%"><pre>' . print_r($_FILES, true) . '</pre></td>
						<td valign="top" width="25%"><pre>' . print_r($_SESSION, true) . '</pre></td>
						<td valign="top" width="25%"><pre>' . $userObj . '</pre></td>
					</tr>
					<tr>
						<td colspan="4"><strong>MySQL: </strong></td>
					</tr>
					<tr>
						<td colspan="4"><pre>';
							if ((isset($this->db->qs)) && (is_array($this->db->qs)))
							{
								foreach ($this->db->qs as $index => $query)
								{
									$ret .= '' . $index . '. ' . $query . '<br />';
								}
							}
						$ret .= '</pre>
						</td>
					</tr>
				</table>
			</div>';
						
		return $ret;
	}
	
	public function cleanUp()
	{
		global $glob, $user;
		
		unset($this->db);
		unset($glob);
		unset($user);
	}
}