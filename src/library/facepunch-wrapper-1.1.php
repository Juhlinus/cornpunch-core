<?php

/**
 *
 *   _    _           _        _ _
 *  | |  | |         | |      | | |
 *  | |__| | __ _ ___| | _____| | |
 *  |  __  |/ _` / __| |/ / _ \ | |
 *  | |  | | (_| \__ \   <  __/ | |
 *  |_|  |_|\__,_|___/_|\_\___|_|_|
 *
 * This was written by Lewis Lancaster in 2015. You are free to use this library only for good, meaning please
 * don't use this to hack facepunch. I don't want to get sued or anything. All of this was written for
 * educational purposes. I did not write this for bad, I promise! :c
 */

class facepunch {
    public $pagehtml;
    public $userid;
    public function __construct() {
        $curlInit = curl_init("http://www.facepunch.com");
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curlInit);
        curl_close($curlInit);
        if ($response) {
            error_reporting(E_ERROR);
            return true;
        } else {
            die("Facepunch is down at the moment so we cannot wrap our APIs... sorry!");
        }
    }
    public function messagesDisabled() {
        if ($this->getIDNodeFromUserPage("visitor_messaging-tab")) {
            return false;
        }
        return true;
    }
    public function getIDNodeFromUserPage($idnode) {
        $html_page = new DomDocument;
        $html_page->loadHTML($this->getCachedUserPage());
        if ($html_page->getElementById($idnode)) {
            return $html_page->getElementById($idnode);
        } else {
            return false;
        }
    }
    public function getCachedUserPage() {
        return $this->pagehtml;
    }
    public function getAvatar( $html_output = false) {
        if( $html_output ) {
            return "<img src='http://facepunch.com/image.php?u=$this->userid'>";
        }
        return file_get_contents("http://facepunch.com/image.php?u=$this->userid");
    }
    public function getPostRatings($postid, $json = false) {
        $html_page        = new DOMDocument;
        $post_information = json_decode(file_get_contents("http://www.facepunch.com/ajax.php?do=rate_list&postid=$postid"));
        if ($post_information->error) {
            die("Post is either invalid or has moved.");
        }
        $html_page->loadHTML($post_information->list);
        $ratings = array();
        foreach ($html_page->getElementsByTagName('span') as $span) {
            if ($span->getElementsByTagName('img')) {
                $image = $span->getElementsByTagName('img')->item(0);
                if ($image) {
                    $ratings[$image->getAttribute('alt')] = $span->getElementsByTagName('strong')->item(0)->textContent;
                }
            }
        }
        if ($json) {
            return json_encode($ratings);
        }
        return $ratings;
    }
    public function refreshCache() {
        $this->cacheUserPage($this->userid);
    }
    public function cacheUserPage($userid) {
        $this->pagehtml = file_get_contents("http://www.facepunch.com/member.php?u=$userid");
        if (!$this->userid == $userid) {
            $this->userid = $userid;
        }
    }
    public function currentUserID() {
        return $this->userid;
    }
    public function setUserID($userid) {
        $this->userid = $userid;
        $this->cacheUserPage($userid);
    }
    public function getUserInformation($json = false) {
        if (!$this->isUserValid()) {
            return false;
        }
        $user_information = array(
            "username" => $this->getUsername(),
            "membership" => $this->getMembership(),
            "joindate" => $this->getJoinDate(),
            "postcount" => $this->getPostCount(),
            "isonline" => $this->isOnline(),
            "isbanned" => $this->isBanned()
        );
        if ($json) {
            return json_encode($user_information);
        }
        return $user_information;
    }
    public function isUserValid() {
        $html_page = new DomDocument;
        $html_page->loadHTML($this->getCachedUserPage());
        foreach ($html_page->getElementsByTagName("div") as $div) {
            if ($div->getAttribute("class") == "standard_error") {
                return false;
            }
        }
        return true;
    }
    public function getUsername() {
        if ($this->isGold()) {
            $username = $this->getIDNodeFromUserPage('userinfo')->getElementsByTagName('font')->item(0);
            return $username->textContent;
        }
        $username = $this->getIDNodeFromUserPage('userinfo')->getElementsByTagName('span')->item(0);
        return $username->textContent;
    }
    public function isGold() {
        if ($this->getIDNodeFromUserPage('userinfo')) {
            $username = $this->getIDNodeFromUserPage('userinfo')->getElementsByTagName('font')->item(0);
            if (!$username) {
                return false;
            }
            if ($username->getAttribute('color')) {
                if ($username->getAttribute('color') == '#A06000') {
                    return true;
                }
            }
        }
        return false;
    }
    public function getMembership() {
        if ($this->isDeveloper()) {
            return "developer";
        }
        if ($this->isMod()) {
            return "moderator";
        }
        if ($this->isGold()) {
            return "gold";
        }
        if ($this->isUserValid()) {
            return "normal";
        }
    }
    public function isDeveloper() {
        if ($this->getIDNodeFromUserPage('userinfo')) {
            $username = $this->getIDNodeFromUserPage('userinfo')->getElementsByTagName('span')->item(1);
            if (!$username) {
                return false;
            }
            if ($username) {
                if ($username->getAttribute('style')) {
                    if ($username->getAttribute('style') == "color:rgb(0, 112, 255);font-weight:bold;") {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    public function isMod() {
        if ($this->getIDNodeFromUserPage('userinfo')) {
            $username = $this->getIDNodeFromUserPage('userinfo')->getElementsByTagName('span')->item(1);
            if (!$username) {
                return false;
            }
            if ($username) {
                if ($username->getAttribute('style')) {
                    if ($username->getAttribute('style') == "color:#00aa00;font-weight:bold;") {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    public function getJoinDate() {
        $joindate = $this->getIDNodeFromUserPage('view-stats_mini')->getElementsByTagName('dd')->item(0);
        return trim($joindate->textContent);
    }
    public function getPostCount() {
        $postcount   = $this->getIDNodeFromUserPage('view-stats_mini')->getElementsByTagName('dd')->item($this->getIDNodeFromUserPage('view-stats_mini')->getElementsByTagName('dd')->length - 1);
        $new_string  = trim(str_replace(",", "", $postcount->textContent));
        return (int) $new_string;
    }
    public function isOnline() {
        if ($this->getClassNodeFromUserPage('online')) {
            return true;
        }
        return false;
    }
    public function getClassNodeFromUserPage($classnode) {
        $html_page = new DomDocument;
        $html_page->loadHTML($this->getCachedUserPage());
        $html_location = new DOMXPath($html_page);
        $html_needle   = $html_location->query("//*[contains(concat(' ', normalize-space(@class), ' '), '$classnode')]");
        if ($html_needle) {
            return $html_needle;
        } else {
            return false;
        }
    }
    public function isBanned() {
        if ($this->getIDNodeFromUserPage('userinfo')) {
            $username = $this->getIDNodeFromUserPage('userinfo')->getElementsByTagName('font')->item(0);
            if (!$username) {
                return false;
            }
            if ($username->getAttribute('color')) {
                if ($username->getAttribute('color') == 'red') {
                    return true;
                }
            }
        }
        return false;
    }
    public function compareComments($key) {
        $posts = $this->getIDNodeFromUserPage('message_list')->getElementsByTagName('li');
        foreach ($posts as $message) {
            if ($message->getElementsByTagName('div')->item(0)->getElementsByTagName('a')->item(0)->textContent == $this->getUsername()) {
                if (trim($message->getElementsByTagName('div')->item(1)->getElementsByTagName('blockquote')->item(0)->textContent) == "CP:" . $key) {
                    return true;
                }
            }
        }
        return false;
    }
}

