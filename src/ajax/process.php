<?php
include ("../includes.php");
if (isset($_POST["action"])) {
    if ($_POST["action"] == "login") {
        $response = array();
        // Set the error
        $response["error"] = "false";
        // Do this
        if (!isset($_POST["facepunchurl"])) {
            $response["error"] = "no url";
        }
        else {
            // Lets check the URL
            $page = explode('/', $_POST["facepunchurl"]) [3];
            // Now to get the values
            $step_1 = explode("?", $page) [1];
            // Do we have anything?
            if (!isset($step_1)) {
                $response["error"] = "broken url";
            }
            else {
                $values = explode("=", $step_1);
                // get the first value
                if ($values[0] == "u") {
                    // Do checks here.
                    $facepunch = new facepunch();
                    // Set the userid
                    $facepunch->setUserID($values[1]);
                    // Check to see if they have their messages disbabled.
                    if ($facepunch->messagesDisabled()) {
                        $response["error"] = "visitor messages are disabled";
                    }
                    else {
                        // if the user valid?
                        if (!$facepunch->isUserValid()) {
                            $response["error"] = "user is invalid";
                        }
                        else {
                            if ($facepunch->isGold() || $facepunch->isDeveloper() || $facepunch->isMod()) {
                                $response["username"] = $facepunch->getUsername();
                                $response["avatar"] = $facepunch->getAvatar();
                                $response["userid"] = $facepunch->currentUserID();
                                $response["membership"] = $facepunch->getUserInformation() ["membership"];
                                // Assuming the player is valid, we will now give him a key.
                                $response["authenticationkey"] = $db->addAuthenticationKey($facepunch->currentUserID());
                            }
                            else {
                                $response["error"] = "not gold";
                            }
                        }
                    }
                }
            }
        }
        die(json_encode($response));
    }
    if ($_POST["action"] == "auth") {
        $response = array();
        $response["error"] = "false";
        if (!isset($_POST["key"])) {
            $response["error"] = "no key given";
        }
        else {
            // Time to do some magic.
            if (!$db->hasKey($_POST["key"])) {
                $response["error"] = "error in key, does not exist.";
            }
            else {
                if ($db->keyExpired($_POST["key"])) {
                    $response["error"] = "key expired";
                    // Delete the key.
                    $db->deleteKey($_POST["key"]);
                }
                else {
                    $facepunch = new facepunch();
                    // Set the user id
                    $facepunch->setUserID($db->getUserIDFromKey($_POST["key"]));
                    // Check the user id's page for the key posted on their page.
                    if ($facepunch->compareComments($_POST["key"])) {
                        $response["posted"] = "true";
                        // Create session
                        $session->createSession($facepunch->currentUserID());
                        $account->setPaths('../json/facepunch/', '../../images/facepunch/avatars/');
                        $account->checkInformation($facepunch->currentUserID());
                        // Delete the key.
                        $db->deleteKey($_POST["key"]);
                    }
                    else {
                        $response["error"] = "no key found";
                        // Delete the key.
                        $db->deleteKey($_POST["key"]);
                    }
                }
            }
        }
        die(json_encode($response));
    }
    if ($_POST["action"] == "getinfo") {
        $response = array();
        $response["error"] = "false";
        if (!$_POST["userid"]) {
            $response["error"] = "no user id";
        }
        else {
            if (!$_POST["sessionid"]) {
                $response["error"] = "no session id";
            }
            else {
                if (!$session->activeSession($_POST["sessionid"])) {
                    $response["error"] = "session id is invalid!";
                }
                else {
                    $facepunch = new facepunch();
                    $facepunch->setUserID($_POST["userid"]);
                    if (!$facepunch->isUserValid()) {
                        $response["error"] = "user is invalid";
                        unset($facepunch);
                    }
                    else {
                        // Do we have information?
                        $account->setPaths('../json/facepunch/', '../../images/facepunch/avatars/');
                        if (!$account->hasInformation($facepunch->currentUserID())) {
                            $account->checkInformation($facepunch->currentUserID());
                        }
                        $response["information"] = $account->getInformation($facepunch->currentUserID() , false);
                    }
                }
            }
        }
        die(json_encode($response));
    }
    if( $_POST["action"] == "getmyinformation" ){
        $response = array();
        $response["error"] = "false";
        if (!$_POST["sessionid"]) {
            $response["error"] = "no session id";
        }
        else {
            if (!$session->activeSession($_POST["sessionid"])) {
                $response["error"] = "session id is invalid!";
            } else {
                $facepunch = new facepunch();
                $facepunch->setUserID( $session->sessionToUserID( $_POST["sessionid"] ) );
                if (!$facepunch->isUserValid()) {
                    $response["error"] = "user is invalid";
                    unset($facepunch);
                }else{
                    $account->setPaths('../json/facepunch/', '../../images/facepunch/avatars/');
                    if (!$account->hasInformation($facepunch->currentUserID())) {
                        $account->checkInformation($facepunch->currentUserID());
                    }
                    $response["information"] = $account->getInformation($facepunch->currentUserID() , false);
                }
            }
        }
        die(json_encode($response));
    }
    if ($_POST["action"] == "getactiveusers") {
        $response = array();
        $response["error"] = "false";
        if (!$_POST["sessionid"]) {
            $response["error"] = "no session id";
        }
        else {
            if (!$session->activeSession($_POST["sessionid"])) {
                $response["error"] = "session id is invalid!";
            }
            else {
                $response["information"] = $db->activeUsers();
            }
        }
        die(json_encode($response));
    }
}
else {
    die("{error:'no data'}");
}

