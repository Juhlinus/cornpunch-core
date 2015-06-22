<?php

class database{
    protected $database;
    public function __construct( PDO $database ){
        try{
            $database = new PDO("mysql:host=localhost;dbname=cornpunch", "root","");
            $this->database = $database;
        }
        catch(PDOException $e) {
            die($e->getMessage());
        }
    }
    public function addAuthenticationKey( $userid ){
        $haskey = $this->userAlreadyHasKey( $userid );
        if( $haskey != false){
            $this->deleteKey( $haskey );
        }
        //Add a new auth key
        $STH = $this->database->prepare("INSERT INTO activations (userid, activationkey, ip, time) VALUES (?,?,?,?)");
        //Bind the params
        $STH->bindParam(1, $fpid);
        $STH->bindParam(2, $fpkey);
        $STH->bindParam(3, $fpip);
        $STH->bindParam(4, $fptime);
        //Set the params
        $fpid = $userid;
        $fpkey = md5(sha1(rand()));
        $fpip = $_SERVER["REMOTE_ADDR"];
        $fptime = time();
        //Execute it
        $STH->execute();
        //Return the key.
        return $fpkey;
    }
    public function hasKey( $key ){
        $STH = $this->database->prepare("SELECT * FROM activations WHERE activationkey = '".$key."' LIMIT 1");
        $STH->execute();
        $result = $STH->fetch(PDO::FETCH_ASSOC);
        //Get the result
        if( $result ){
            //I have that key!
            return true;
        }else{
            return false;
        }
    }
    public function deleteKey( $key ){
        $STH = $this->database->prepare("DELETE FROM activations WHERE activationkey = '".$key."'");
        $STH->execute();
    }
    public function userAlreadyHasKey( $userid ){
        $STH = $this->database->prepare("SELECT * FROM activations WHERE userid = '".$userid."' LIMIT 1");
        $STH->execute();
        $result = $STH->fetch( PDO::FETCH_ASSOC );
        if( $result ){
            return $result["activationkey"];
        }else{
            return false;
        }
    }
    public function getUserIDFromKey( $key ){
        $STH = $this->database->prepare("SELECT * FROM activations WHERE activationkey = '".$key."' LIMIT 1");
        $STH->execute();
        $result = $STH->fetch( PDO::FETCH_ASSOC );
        if( $result ){
            return $result["userid"];
        }else{
            return false;
        }
    }

    public function keyExpired( $key ){
        $STH = $this->database->prepare("SELECT * FROM activations WHERE activationkey = '".$key."' LIMIT 1");
        $STH->execute();
        $result = $STH->fetch( PDO::FETCH_ASSOC );
        if( $result ){
            if( (int)$result["time"] - time() > 1000){
                return true;
            }else {
                return false;
            }
        }else{
            return false;
        }
    }

    public function ipMatch( $key, $current_ip ){
        $STH = $this->database->prepare("SELECT * FROM activations WHERE activationkey = '".$key."' LIMIT 1");
        $STH->execute();
        $result = $STH->fetch( PDO::FETCH_ASSOC );
        if( $result ){
            if( $current_ip == "127.0.0.1"){
                //Ignore host.
                return true;
            }
            if( $result["ip"] == $current_ip ){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function activeUsers(){
        //Returns a table of all the active users.
        $STH = $this->database->prepare("SELECT userid FROM activeusers");
        $userids = array();
        if( $STH->execute() ){
            while ($row = $STH->fetch(PDO::FETCH_ASSOC)) {
                $userids[] = $row;
            }
        }
        return $userids;
    }
}
//Create class
$db = new database( $database );
