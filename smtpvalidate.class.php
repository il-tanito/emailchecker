<?php
 
class SMTP_validateEmail {

 var $sock;

 var $user;

 var $domain;

 var $domains;

 var $port = 25;

 var $max_conn_time = 35;

 var $max_read_time = 25;

 var $from_user = 'user';

 var $from_domain = 'localhost';
 
 /**
  * @var Array  
  */
 var $nameservers = array(
	'192.168.0.1'
);
 
 var $debug = false;

 /**
  * @return SMTP_validateEmail 
  * @param 
  * @param 
  */
 function SMTP_validateEmail($emails = false, $sender = false) {
  if ($emails) {
   $this->setEmails($emails);
  }
  if ($sender) {
   $this->setSenderEmail($sender);
  }
 }
 
 function _parseEmail($email) {
 	$parts = explode('@', $email);
	$domain = array_pop($parts);
	$user= implode('@', $parts);
	return array($user, $domain);
 }
 
 /**
  * @param 
  */
 function setEmails($emails) {
 	foreach($emails as $email) {
		list($user, $domain) = $this->_parseEmail($email);
		if (!isset($this->domains[$domain])) {
			 $this->domains[$domain] = array();
		}
		$this->domains[$domain][] = $user;
	}
 }
 
 /**
  * @param 
  */
 function setSenderEmail($email) {
	$parts = $this->_parseEmail($email);
	$this->from_user = $parts[0];
	$this->from_domain = $parts[1];
 }
 
 /**
 * @param String 
 * @param String 
 * @return Array 
 */
 function validate($emails = false, $sender = false) {
 	
  $results = array();

  if ($emails) {
   $this->setEmails($emails);
  }
  if ($sender) {
   $this->setSenderEmail($sender);
  }

  
  foreach($this->domains as $domain=>$users) {
  	
	 $mxs = array();
	 
	 
	 $this->domain = $domain;
  
	 
	  list($hosts, $mxweights) = $this->queryMX($domain);

	  
	  for($n=0; $n < count($hosts); $n++){
	   $mxs[$hosts[$n]] = $mxweights[$n];
	  }
	  asort($mxs);
	
	  
	  $mxs[$this->domain] = 0;
	  
	  $this->debug(print_r($mxs, 1));
	  
	  $timeout = $this->max_conn_time;
	   
	  
	  while(list($host) = each($mxs)) {
	  
	   $this->debug("try $host:$this->port\n");
	   if ($this->sock = fsockopen($host, $this->port, $errno, $errstr, (float) $timeout)) {
	    stream_set_timeout($this->sock, $this->max_read_time);
	    break;
	   }
	  }
	 
	  
	  if ($this->sock) {
	   $reply = fread($this->sock, 2082);
	   $this->debug("<<<\n$reply");
	   
	   preg_match('/^([0-9]{3}) /ims', $reply, $matches);
	   $code = isset($matches[1]) ? $matches[1] : '';
	
	   if($code != '220') {
	    
	    foreach($users as $user) {
	    	$results[$user.'@'.$domain] = false;
		}
		continue;
	   }

	   
	   $this->send("HELO ".$this->from_domain);
	   
	   $this->send("MAIL FROM: <".$this->from_user.'@'.$this->from_domain.">");
	   
	   
	   foreach($users as $user) {
	   
		   
		   $reply = $this->send("RCPT TO: <".$user.'@'.$domain.">");
		   
		   
		   preg_match('/^([0-9]{3}) /ims', $reply, $matches);
		   $code = isset($matches[1]) ? $matches[1] : '';
		
		   if ($code == '250') {
		    
		    $results[$user.'@'.$domain] = true;

		   } else {
		   	$results[$user.'@'.$domain] = false;
		   }
	   
	   }
	   
	   
	   $this->send("RSET");
	   
	   
	   $this->send("quit");
	   
	   fclose($this->sock);
	  
	  }
 	}
	return $results;
 }


 function send($msg) {
  sleep(1);
  fwrite($this->sock, $msg."\r\n");

  $reply = fread($this->sock, 2082);

  $this->debug(">>>\n$msg\n");
  $this->debug("<<<\n$reply");
  
  return $reply;
 }
 
 /**
  * 
  * @return 
  */
 function queryMX($domain) {
 	$hosts = array();
	$mxweights = array();
 	if (function_exists('getmxrr')) {
 		getmxrr($domain, $hosts, $mxweights);
 	} else {
 		
		require_once 'Net/DNS.php';

		$resolver = new Net_DNS_Resolver();
		$resolver->debug = $this->debug;
		
		$resolver->nameservers = $this->nameservers;
		$resp = $resolver->query($domain, 'MX');
		if ($resp) {
			foreach($resp->answer as $answer) {
				$hosts[] = $answer->exchange;
				$mxweights[] = $answer->preference;
			}
		}
		
 	}
	return array($hosts, $mxweights);
 }
 

 function microtime_float() {
  list($usec, $sec) = explode(" ", microtime());
  return ((float)$usec + (float)$sec);
 }

 function debug($str) {
  if ($this->debug) {
   echo '<pre>'.htmlentities($str).'</pre>';
  }
 }

}

 
?>
