<?php
class Ticket extends AppModel {
	var $name = 'Ticket';
	var $displayField = 'name';
	
	
	
	function findUser($email){
	    $sql = "SELECT * FROM users WHERE email = '$email'";
	    $result = $this->query($sql);
	    
	    $cakeConventionArray = array();
	    
	    $cakeConventionArray['User']['id'] = $result[0]['users']['id'];
	    $cakeConventionArray['User']['email'] = $result[0]['users']['email'];
	    return $cakeConventionArray;
	}
	
	/*
	 * Time in Seconds
     * 1 Minute: 60 seconds
     * 1 Hour: 3,600 seconds
     * 1 Day: 86,400 seconds	
    */
	
	// si se trata de más de 1 día se elimina
	function checkTicketDateValidity($ticket){
	    App::import('Helper', 'Time');
	    $time = new TimeHelper();
	    $dayOld = time()-86400;
	    
	    //Si tiene mas de un dia se eliminara
	    if(strtotime($ticket['Ticket']['creation_date']) < $dayOld){
	        $this->delete($ticket['Ticket']['id']);
	        return false;
	    }else{
	        return true;
	    }
	    
	}
}
?>