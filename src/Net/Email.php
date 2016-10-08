<?php
namespace BlueFission\Net;

use BlueFission;

@include_once('Loader.php');
$loader = BlueFission\Loader::instance();
$loader->load('com.bluefission.behavioral.Configurable');

class Email extends Configurable 
{
	protected $_config = array('sender' => '', 
		'html'=>false, 
		'eol' => "\r\n",
	);

	private $_headers = array();
	private $_attachments = array();
	private $_recipients = array();

	static $DEFAULT = 'default';
	static $CC = 'cc';
	static $BCC = 'bcc';
	
	protected $_data = array( 
		'from'=>'', 
		'message'=>'', 
		'subject'=>'',
	);

	function __construct($rcpt = '', $from = '', $subject = '', $message = '', $cc = '', $bcc = '', $html = false, $headers_r = '', $additional = '', $attachments = '') {
		//Prepare addresses
		$this->recipients($rcpt);
		$this->recipients($cc, self::$CC);
		$this->recipients($bcc, self::$BCC);
		$this->from( $from );
		$this->subject( $subject );
		$this->message( $message );
		$this->headers( $headers_r );
	}

	private function field($field, $value = null)
	{
		if ( !array_key_exists( $field, $this->_data ) )
			return null;
		if ( dev_not_null($value) ) 
		{
			$this->_data[$field] = $value;
		}
		else 
		{
			$value = (isset($this->_data[$field])) ? $this->_data[$field] : null;
		}
		return $value;
	}

	public function headers( $input = null, $value = null )
	{
		if (dev_is_null ($input))
			return $this->_headers;
		elseif (is_string($input))
		{
			if (dev_is_null ($value))
				return isset($this->_headers[$input]) ? $this->_headers[$input] : null;
			$this->_headers[$input] = self::sanitize($value); 
		}
		elseif (is_array($input))
		{
			foreach ($input as $a=>$b)
				$this->_headers[self::sanitize($a)] = self::sanitize($b);
		}
	}

	public function attach( $input = null, $value = null )
	{
		if (dev_is_null ($input))
			return $this->_attachments;
		elseif (is_string($input))
		{
			if (dev_is_null ($value))
				return isset($this->_attachments[$input]) ? $this->_attachments[$input] : null;
			$this->_attachments[$input] = $value; 
		}
		elseif (is_array($input))
		{
			foreach ($input as $a=>$b)
				$this->_attachments[$a] = $b;
		}
	}

	public function recipients($value = null, $type = null)
	{
		if (dev_is_null($value))
			return $this->_recipients;
			
		$type = $type ? $type : self::$DEFAULT;
		$value = self::filterAddresses($value);
		$this->_recipients[$type] = ( isset($this->_recipients[$type]) && count( $this->_recipients[$type] ) > 0 ) ? array_merge( $this->_recipients[$type], $value ) : $value;	 
	}
	
	private function getRecipients( $type = null )
	{
		$type = (dev_is_null($type)) ? self::$DEFAULT : $type;
		return isset($this->_recipients[$type]) ? $this->_recipients[$type] : array();
	}
	
	public function from($value = null)
	{
		if ( (dev_not_null($value)) && !self::validateAddress($value));
			return false;
		$this->field('from', $value);
		return $this->field('from') ? $this->field('from', $value) : $this->config('sender');
	}
	
	public function message($value = null)
	{	
		$value = self::sanitize($value);
		return $this->field('message', $value);
	}
	
	public function subject($value = null)
	{
		$value = self::sanitize($value);
		return $this->field('subject', $value);
	}
	
	public function sendHTML($value = null)
	{
		return $this->config('html', $value);
	}	

	public function status($message = null)
	{
		if (dev_is_null($message))
		{
			$message = end($this->_status);
			return $message;
		}
		$this->_status[] = $message;	
	}

	// validate an email address 
	static function validateAddress($address = '') 
	{
		$address = dev_value_to_array($address);
		$p = '/^[a-z0-9!#$%&*+-=?^_`{|}~]+(\.[a-z0-9!#$%&*+-=?^_`{|}~]+)*';
		$p.= '@([-a-z0-9]+\.)+([a-z]{2,3}';
		$p.= '|com|net|edu|org|gov|mil|int|biz|pro|info|arpa|aero|coop|name|museum|au|jp|tv|us|nz|nt)$/ix';
		$pattern = $p;
		$passed = false;
		$i = 0;
		$count = count($address);
		do 
		{
			$match = preg_match($pattern, $address[$i]);
			$passed = ($match > 0 && $match !== false) ? true : false;
			$i++;
		} while ($passed === true && $i < $count);
		
		return $passed;
	}

	// filter out invalid email addresses from an array
	public function filterAddresses($addresses = null) 
	{
		$address_r = dev_value_to_array($addresses);
		$valid_address_r = array();
		foreach ($address_r as $a) if (self::validateAddress($a)) $valid_address_r[] = self::sanitize($a);
		if ( count($valid_address_r) == 0 ) return false;
		return $valid_address_r;
	}

	static function sanitize( $field )
	{
		//Remove line feeds
		$ret = str_replace("\r", "", $field);
		// Remove injected headers
		$find = array("/bcc\:/i",
		        "/Content\-Type\:/i",
		        "/Mime\-Type\:/i",
		        "/cc\:/i",
		        "/to\:/i");
		$ret = preg_replace($find, "", $field);
		
		return $ret;
	}

	public function send() {
		$status = 'Failed to send mail. ';
		$from = $this->from();
		$subject = $this->subject();
		
		$attachments = $this->_attachments;
		
		$eol = $this->config('eol');
		$mime_boundary=md5(time());
		
		//Build Headers
		$this->headers = array();
		if ( $this->_attachments ) 
		{
			$this->headers['MIME-Version'] = "1.0";
			$this->headers['Content-Type'] = "multipart/mixed; boundary=\"mixed-{$mime_boundary}\"";
		}
		elseif ($this->sendHTML()) 
		{
			$this->headers['MIME-Version'] = "1.0";
			$this->headers['Content-Type'] = "multipart/related; boundary=\"mixed-{$mime_boundary}\"";
		}
		else
		{
			$this->headers['Content-Type'] = "text/plain; charset=iso-8859-1";
		}
		
		if ($from != '' && self::validateAddress($this->from())) {
			$this->headers['From'] = "{$from}";
	   		$this->headers['Reply-To'] = "{$from}";
	   		$this->headers['Return-Path'] = "{$from}";
	   		$this->headers['Message-ID'] = "<".time()."-{$from}>";
		}
		$rcpts = $this->getRecipients();
		$cc = $this->getRecipients(self::$CC);
		$bcc = $this->getRecipients(self::$BCC);
		if (count($cc) > 0) $this->_headers["Cc"] = implode(', ', $cc);
		if (count($bcc) > 0) $this->_headers["Bcc"] = implode(', ', $bcc);
		$this->headers['X-Mailer'] = "PHP/" . phpversion();
		
		//Compile mail data
		
		foreach ( $this->headers() as $a=>$b )
		{
			$headers = "{$a}: $b";
		}
		$header_info = implode($eol, $this->_headers);
		$message = $this->message();
		$message = wordwrap($message, 70);
		
		$body = "";
		
		if ( $attachments )
		{
			foreach( $attachments as $file )
			{
				if (is_file($file["file"]))
				{  
					if ( file_exists($file["file"]) )
					{
						$file_name = substr($file["file"], (strrpos($file["file"], "/")+1));
						
						$handle=fopen($file["file"], 'rb');
						$f_contents=fread($handle, filesize($file["file"]));
						$f_contents=chunk_split(base64_encode($f_contents));    //Encode The Data For Transition using base64_encode();
						fclose($handle);
						
						// Attach
						$body .= "--mixed-{$mime_boundary}{$eol}";
						$body .= "Content-Type: {$file["type"]}; name=\"{$file_name}\"{$eol}";
						$body .= "Content-Transfer-Encoding: base64{$eol}";
						$body .= "Content-Disposition: attachment; filename=\"{$file_name}\"{$eol}{$eol}"; // !! This line needs TWO end of lines !! IMPORTANT !!
						$body .= $f_contents.$eol.$eol;
					}
				}
			}
			$body .= "--mixed-".$mime_boundary.$eol;
		}
		
		// Begin message text
		if( $this->sendHTML() === true )
		{
			$body .= "Content-Type: multipart/alternative; boundary: \"alt-{$mime_boundary}\"{$eol}";
			// HTML Text
			$body .= "--alt-".$mime_boundary.$eol;
			$body .= "Content-Type: text/html; charset=iso-8859-1{$eol}";
			$body .= "Content-Transfer-Encoding: 8bit{$eol}{$eol}";
			$body .= $message.$eol.$eol;
			
			// Ready plain text headers
			$body .= "--alt-".$mime_boundary.$eol;
			$body .= "Content-Type: text/plain; charset=iso-8859-1{$eol}";
			$body .= "Content-Transfer-Encoding: 8bit{$eol}{$eol}";
		}	
		
		// Plain Text
		$body .= strip_tags(dev_br2nl( $message )).$eol.$eol;
		
		// Body end
		if ( $this->sendHTML() )
			$body .= "--alt-{$mime_boundary}--{$eol}{$eol}";
			  
		if ($attachments )
			$body .= "--mixed-{$mime_boundary}--{$eol}{$eol}";  // finish with two eol's for better security. see Injection.
	  
		
		// the INI lines are to force the From Address to be used
		ini_set( "sendmail_from", $this->from() ); 
		
		if (count($rcpts) <= 0) {
			$status .= "The send to address is empty.\n";
		} elseif (!self::validateAddress($this->getRecipients())) {
			$status .= "Email address '" . implode(', ', $this->rcpt) . "' is invalid.\n";
		} elseif ($subject == '') {
			$status .= "Subject line is empty.\n";
		} elseif ($message == '') {
			$status .= "Message is empty.\n";
		} elseif (count($cc) > 0 && !self::validateAddress($cc)) {
			$status .= "Invalid address in the CC line\n";
		} elseif (count($bcc) > 0 && !self::validateAddress($bcc)) {
			$status .= "Invalid address in the BCC line\n";
		} elseif (mail ( implode(', ', $this->getRecipients()), $this->subject(), $body, $header_info, $this->field('additional') )) {
			$status = "Mail delivered successfully\n";
		}
		ini_restore( "sendmail_from" );
		
		$this->status($status);
	}
}