<?php

use SendGrid\SendGrid;

class MailAccount
{
	var $name;
	var $email;
	
	function __construct($data)
	{
		$this->name = $data['name'];
		$this->email = $data['email'];
	}
}

class MailRecipient
{
	var $to;
	var $subject;
	
	function __construct($to, $subject)
	{
		$this->to = array(new MailAccount($to));
		$this->subject = $subject;
	}
}

class MailBody
{
	var $type;
	var $value;
	
	function __construct($type, $body)
	{
		$this->type = $type;
		$this->value = $body;
	}
}

class MailData
{
	var $personalizations;
	var $from;
	var $content;
	
	function __construct($data)
	{
		$this->personalizations = array(new MailRecipient($data['to'], $data['subject']));
		$this->from = new MailAccount($data['from']);
		$this->content = array(
			new MailBody('text/plain', strip_tags($data['body'])),
			new MailBody('text/html', $data['body'])			
		);
	}
}

class Mail
{
	var $api;
	var $data;
	
	function __construct()
	{
		$this->api = new SendGrid(SENDGRID_API_KEY);
	}
	
	public function setOptions($data)
	{
		$this->data = new MailData($data);
	}
	
	public function send()
	{
		return $this->api->client->mail()->send()->post($this->data);
	}
}