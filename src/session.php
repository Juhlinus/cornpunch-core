<?php

class session{
    protected $database;
    public function __construct( PDO $database ){
        $this->database = $database;
    }

    public function createSession( $userid ){
        $facepunch = new facepunch();
        //Set user ID
        $facepunch->setUserID( $userid );
        //Check if valid
        if(!$facepunch->isUserValid()){
            //Don't do shit. Not a valid user no session for you kiddo.
            return false;
        }
        //Regenerate an ID here.
        //Delete broken sessions.
        $this->deleteBrokenSessions( $userid );
        //Create a fresh session ID
        $this->startFreshSession();
        if( session_id() ){
            //Insert this session ID
            //Add a new auth key
            $STH = $this->database->prepare("INSERT INTO activeusers(userid,sessionid,userlevel,ip,logintime) VALUES (?,?,?,?,?)");
            //Bind the params
            $STH->bindParam(1, $fpid);
            $STH->bindParam(2, $fpsessionkey);
            $STH->bindParam(3, $fpuserlevel);
            $STH->bindParam(4, $fpip);
            $STH->bindParam(5, $fptime);
            //Set the params
            $fpid = $userid;
            $fpsessionkey = session_id();
            $fpip = $_SERVER["REMOTE_ADDR"];
            $fpuserlevel = "0";
            $fptime = time();
            //Execute it
            $STH->execute();
            //Return true!
            return true;
        }
        //Nope!
        return false;
    }

    public function activeSession( $sessionid ){
        $STH = $this->database->prepare("SELECT * FROM activeusers WHERE sessionid = '".$sessionid."' LIMIT 1");
        $STH->execute();
        $result = $STH->fetch(PDO::FETCH_ASSOC);
        //Get the result
        if( $result ){
            //I have a session!
            return true;
        }else{
            return false;
        }
    }

    public function sessionToUserID( $sessionid ){
        $STH = $this->database->prepare("SELECT * FROM activeusers WHERE sessionid = '".$sessionid."' LIMIT 1");
        $STH->execute();
        $result = $STH->fetch(PDO::FETCH_ASSOC);
        //Get the result
        if( $result ){
            //I have an ID!
            return $result["userid"];
        }else{
            return false;
        }
    }

    public function deleteBrokenSessions( $userid ){
        $STH = $this->database->prepare("DELETE FROM activeusers WHERE userid = '".$userid."'");
        $STH->execute();
    }

    public function startSession(){
        session_name("fpcpsession");
        ini_set("session.cookie_lifetime","30758400000");
        session_start();
    }

    public function startFreshSession(){
        session_name("fpcpsession");
        ini_set("session.cookie_lifetime","30758400000");
        session_start();
        session_regenerate_id();
    }

    public function checkSession(){
        $this->startSession();
        if($this->activeSession( session_id())){
            $this->setCookies();
            return true;
        }
        //Session is not valid.
        return false;
    }

    public function setCookies(){
        $facepunch = new facepunch();
        $facepunch->setUserID($this->sessionToUserID(session_id()));
        //Set cookies
        setcookie("fpusername",$facepunch->getUsername());
        setcookie("fpid",$facepunch->currentUserID());

        unset( $facepunch );
    }
}


