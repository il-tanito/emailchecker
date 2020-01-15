<html>

<!-- links, scripts y titulo -->
<title> Email Checker by il tanito</title>
<script src="https://kit.fontawesome.com/c57e637d22.js"></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Armata">
<link rel="stylesheet" href="style.css">

</html>
<!-- fin -->

<!-- php -->
<?php
    
$fname=$_POST["email"];

require_once('smtpvalidate.class.php');

$email = $fname;
$sender = 'user@mydomain.com';

$SMTP_Validator = new SMTP_validateEmail();

$SMTP_Validator->debug = false;

$results = $SMTP_Validator->validate(array($email), $sender);

echo '<br><span style="color: #000; font-family: Armata; font-size: 20px;">The email address</span> <span style="color: #0073da; font-family: Armata; font-size: 25px; text-decoration: underline;">' .$email.'</span><span style="color: #000; font-family: Armata; font-size: 20px;"> is '.($results[$email] ? 'valid</span>' : 'invalid</span>')."\n";



?>
