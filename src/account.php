<?php

namespace CornpunchCore;

use CornpunchCore\lib\FacepunchWrapper;

class account{
    /**
     * Not being used
     *
     * protected $database;
     * 
     * File paths ( So I don't have to keep rewriting them.
    **/
    protected $filepath;
    public function __construct(/* PDO $database */){
        //$this->database = $database;
        $this->filepath = array();
    }

    public function setJsonPath($path){
        $this->filepath["json"] = $path;
    }

    public function setImagePath($path){
        $this->filepath["image"] = $path;
    }

    public function setPaths($file, $image){
        $this->filepath = array(
            "json" => $file,
            "image" => $image
        );
    }

    public function saveInformation($userid ){
        //Writes JSON to file so we can load it later.
        $facepunch = new facepunch();
        $facepunch->setUserID($userid);
        //Opens/Creates a new file.
        $file = fopen($this->filepath["json"].$userid.'.json', 'w');
        //Write information.
        if( $facepunch->getUserInformation(true)){
            //Write the information too file.
            fwrite($file,$facepunch->getUserInformation(true));
        }else{
            return false;
        }
        //Close the file.
        fclose($file);
        //Return true
        return true;
    }

    public function hasInformation($userid){
        if( file_exists($this->filepath["json"].$userid.'.json')){
            return true;
        }
        return false;
    }

    public function checkInformation($userid){
        if(! $this->hasInformation($userid)) {
            $this->saveInformation($userid);
        }
        if(! $this->hasAvatar($userid)) {
            $this->downloadAvatar($userid);
        }
    }

    public function downloadAvatar($userid){
        file_put_contents($this->filepath["image"].$userid.'.png', file_get_contents('http://facepunch.com/image.php?u='.$userid));
    }

    public function hasAvatar($userid ){
        if( file_exists($this->filepath["image"].$userid.'.png')){
            return true;
        }
        return false;
    }

    public function getInformation($userid, $json=false ){
        if( !$this->hasInformation($userid)){
            $this->saveInformation($userid );
        }
        //This will return information of a given user.
        //Get the information
        $file = fopen($this->filepath["json"].$userid.'.json', "r");
        //Now we use json
        $json_string = fread($file, filesize($this->filepath["json"].$userid.'.json'));
        //Close the file
        fclose($file);
        //Do we return this as it is?
        if( $json ){
            return  $json_string;
        }else{
            return json_decode(  $json_string );
        }
    }
}



