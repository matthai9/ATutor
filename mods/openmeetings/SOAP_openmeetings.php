<?php
/************************************************************************/
/* ATutor																*/
/************************************************************************/
/* Copyright (c) 2002-2008 by Harris Wong								*/
/* Adaptive Technology Resource Centre / University of Toronto			*/
/* http://atutor.ca														*/
/*																		*/
/* This program is free software. You can redistribute it and/or		*/
/* modify it under the terms of the GNU General Public License			*/
/* as published by the Free Software Foundation.						*/
/************************************************************************/
// $Id: SOAP_openmeetings.php 7575 2008-06-02 18:17:14Z hwong $
if (!defined('AT_INCLUDE_PATH')) { exit; }
//require(AT_INCLUDE_PATH . 'classes/nusoap.php');
require('lib/nusoap.php');

/**
* SOAP_openmeetings
* Class for using the SOAP service for openmeetings
* @access	public
* @author	Harris Wong
*/
class SOAP_openmeetings {
	var $_sid= "";	//session id
	var $_soapClient = NULL;	//soap connector
	var $_wsdl;		//soap service link

	function SOAP_openmeetings($wsdl) {
		$this->_wsdl			= $wsdl;
		$this->_soapClient		= new nusoap_client($this->_wsdl, true);
		$getSession_obj			= $this->_performAPICall('getSession', '');	
		//check session id
		if (!$getSession_obj){
			$this->_sid = session_id();
		} else {
			$this->_sid = $getSession_obj['return']['session_id'];
		}
	}

    /**
    * Login as an user and sets a session
    *
    * @param  array
    * @return mixed
    * @access public
    */
    function login($parameters = array()) {
        if (!isset($parameters["username"])) {
            return false;
        }
        return $this->_performAPICall(
          "loginUser",

          array(
            "SID"         => $this->_sid,
            "username"    => $parameters["username"],
            "userpass"    => $parameters["userpass"]
          )
        );
    }


	/**
	 * Sets user object
     * @param  array
     * @return mixed
     * @access public
     */
    function saveUserInstance($parameters = array()) {
        return $this->_performAPICall(
          "setUserObject",

          array(
            "SID"					=> $this->_sid,
            "username"				=> $parameters["username"],
            "firstname"				=> $parameters["firstname"],
		    "lastname"				=> $parameters["lastname"],
		    "profilePictureUrl"		=> $parameters[""],
		    "email"					=> $parameters["email"]
          )
        );
    }

	/**
	 * Get error message
	 */
	function getError($code){
		return $this->_performAPICall(
			"getErrorByCode",
			array(
				"SID"				=> $this->_sid,
				"errorid"			=> $code,
				"language_id"		=> 1
				)
		);
	}

	/**
	 * Creating a room
	 */
	function addRoom($parameters = array()){
        return $this->_performAPICall(
          "addRoom",

          array(
            "SID"						=> $parameters["SID"],
			'name'						=> $parameters["name"],
			'roomtypes_id'				=> 1,
			'comment'					=> 'Room created by ATutor',
			'numberOfPartizipants'		=> 16,
			'ispublic'					=> true,
			'videoPodWidth'				=> 270, 
			'videoPodHeight'			=> 280,
			'videoPodXPosition'			=> 2, 
			'videoPodYPosition'			=> 2, 
			'moderationPanelXPosition'	=> 400, 
			'showWhiteBoard'			=> true, 
			'whiteBoardPanelXPosition'	=> 276, 
			'whiteBoardPanelYPosition'	=> 2, 
			'whiteBoardPanelHeight'		=> 592, 
			'whiteBoardPanelWidth'		=> 660, 
			'showFilesPanel'			=> true, 
			'filesPanelXPosition'		=> 2, 
			'filesPanelYPosition'		=> 284, 
			'filesPanelHeight'			=> 310, 
			'filesPanelWidth'			=> 270
          )
        );
	}


	/**
	 * Delete room
	 */
	function deleteRoom($parameters = array()){
		return $this->_performAPICall(
			"deleteRoom",
			array(
				"SID"		=> $parameters["SID"],
				"rooms_id"	=> $parameters["rooms_id"]
			)
		);
	}


	/**
	 * return the session id.
	 */
	function getSid(){
		return $this->_sid;
	}
	 


   /**
    * @param  string
    * @param  array
    * @return mixed
    * @access private
    */
    function _performAPICall($apiCall, $parameters) {
			$result = $this->_soapClient->call(
			  $apiCall,
			  $parameters
			);
		if ($this->_soapClient->fault){
			debug($result, 'fault');
			return $result;
		} elseif ($this->_soapClient->getError()){
			debug($this->_soapClient->getError(), 'getError');
			return $result;
		}

		// if (!PEAR::isError($result)) {
		debug($result, $apiCall . ' ' . $this->_sid);
		if (is_array($result)) {
            return $result;
        } else {
//          return $this->_getError($result);
//			return $result;
			return false;
        }
    }

	function myErrors(){
		return $this->_soapClient->getError();
	}
}
?>