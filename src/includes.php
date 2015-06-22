<?php
include("library/facepunch-wrapper-1.1.php");
include("database.php");
include("account.php");
include("session.php");

/** Create new instances of the classes **/
$session = new session( $database );
$account = new account( $database );


?>