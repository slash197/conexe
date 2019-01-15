<?php
/*
 * @Author: Slash Web Design
 */

class Language
{
	protected $db;
	public $store = array();
	public $input = null;
	public $output = null;
	public $language = null;
	public $modifiers = array();
	
	function __construct()
	{
		global $db;
		
		$this->db = $db;
		$this->language = isset($_SESSION['language']) ? $_SESSION['language'] : isset($_COOKIE['conexe_language']) ? $_COOKIE['conexe_language'] : 'base';
		
		$res = $this->db->run("SELECT * FROM language");
		foreach ($res as $expression)
		{
			$key = $expression['base'];
			
			unset($expression['base']);
			unset($expression['id']);
			
			$this->store[$key] = $expression;
		}
	}
	
	protected function process()
	{
		foreach ($this->modifiers as $value)
		{
			$this->output = str_replace('{$}', $value, $this->output);
		}
		
		return $this->output;
	}
	
	public function change($ld)
	{
		if (isset($ld['l']))
		{
			$_SESSION['language'] = $ld['l'];
			$this->language = $_SESSION['language'];
			setcookie("conexe_language", $this->language, time() + 31536000);
		}
	}

	public function translate($options)
	{
		$this->input = $options[0];

		unset($options[0]);
		
		if (count($options) > 0) $this->modifiers = $options;
		
		if ($this->language === 'base')
		{
			$this->output = $this->input;
		}
		else
		{
			$res = $this->db->run("SELECT {$this->language} FROM language WHERE base = '{$this->input}'");
			if (count($res)) $this->output = $res[0][$this->language];
		}
		
		return $this->process();
	}
}

class Helper
{
	protected $db;
	protected $timer;
	public $language = null;
	
	function __construct()
	{
		global $db;
		
		$this->db = $db;
		$this->language = new Language();
		$this->startUp();
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
				return ($diff == 1) ? __('1 second ago') : __('{$} seconds ago', $diff);
			}
			
			$v = round($diff / 60);
			return ($v == 1) ? __('1 minute ago') : __('{$} minutes ago', $v);
		}
		
		$v = round($diff / 3600);
		return ($v == 1) ? __('1 hour ago') : __('{$} hours ago');
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
			'from'		=>	$from ? $from : array('name' => 'Conexe Support', 'email' => 'support@conexe.ro'),
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
	
	public function buildCountryDD($id)
	{
		global $db;
		
		$out = '';
		$res = $db->run("SELECT * FROM location_country ORDER BY name ASC");
		
		foreach ($res as $country)
		{
			$selected = ($id === $country['id']) ? 'selected="selected"' : '';
			$out .= '<option value="' . $country['id'] . '" ' . $selected . '>' . $country['name'] . '</option>';
		}
		
		return $out;
	}
	
	public function buildStateDD($id, $countryId)
	{
		global $db;
		
		if ($countryId === 0) return '<option>select country</option>';
		
		$out = '';
		$res = $db->run("SELECT id, name FROM location_region WHERE country_id = {$countryId} ORDER BY name ASC");
		
		foreach ($res as $region)
		{
			$selected = ($id === $region['id']) ? 'selected="selected"' : '';
			$out .= '<option value="' . $region['id'] . '" ' . $selected . '>' . $region['name'] . '</option>';
		}
		
		return $out;
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
						'<li><a href="account">' . __('My account') . '</a></li>' .
						'<li class="divider"></li>' .
						'<li><a href="sign-out">' . __('Sign out') . '</a></li>' .
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
			$out .= ($user != null) ? '<li>' . $this->buildUserMenu() . '</li>' : '<li><a href="sign-up">' . __('Sign up') . '</a></li><li><a href="sign-in">' . __('Sign in') . '</a></li>';
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