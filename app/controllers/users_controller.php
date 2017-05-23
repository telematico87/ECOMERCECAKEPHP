<?php
App::import('Sanitize');
class UsersController extends AppController{
	
	var $name = 'Users';
	/**
	 * User Model
	 * @var User
	 */
	var $User;
	
	
	function beforeFilter(){
	    if($this->action == 'register' || $this->action == 'admin_edit_user' ){
            $this->Auth->authenticate = ClassRegistry::init('User');			
		}
		parent::beforeFilter();		
			
		
		$this->Auth->autoRedirect = false;
		$this->Auth->loginRedirect = '/';
	
		$this->Auth->allow('login','logout','register','admin_login','reset_password');
		if($this->Auth->user()){
		
			$this->Auth->allow('login','logout','index','register','admin_login','changeUserPassword','changeUserAddress');
			
		}
		if($this->isAuthorized()){
			
			$this->Auth->allow('*');
		}
		
		
	}
	
	function isAuthorized(){	
		if($this->Auth->user('admin')){

			return true;
		}else{
			
			return false;
		}
	}
	
	function index(){
		$user = $this->User->findById($this->Session->read('Auth.User.id'));
		
		$this->set('user',$user);
	}
	
	function login($credentials = null){
	    
		if($this->Auth->user()){
		
			$this->User->updateLastLogin($this->Auth->user('id'), $this->Session->read('Config.userAgent'));
			if($this->referer() != "/users/login"){			   
			    $this->redirect($this->referer());
			}else{		    
			    $this->redirect(array('controller' => 'carts', 'action' => 'index'), null, true);
			}
			
			
	    
		}
		
		
		
	}
	
	function logout(){
	    $this->Session->delete('Auth.User');
		$this->Auth->logout();
		$this->redirect(array('controller' => 'carts', 'action' => 'index'));
	}
	
	
	function register(){
	    
		if(!empty($this->data)){
		    debug($this->data);
		  
				$this->User->create();
				if($this->User->save($this->data)){
					//$this->User->validates();
					//$this->Auth->login($this->data);
					$this->Session->setFlash('Thank you for registering!'); 
					//$this->redirect(array('controller' => 'carts', 'action' => 'index'));
				}
			
		}
	}
	
	
	function changeUserPassword(){    
	    
	    $this->set('userId', $this->data['User']['id']);
	    
	    if(!empty($this->data) && isset($this->data['User']['password'])){
	        //$password = $this->data['User']['password'];
	        //$sql = "UPDATE users SET users.password = '$password' WHERE users.id = $userId";
	        if($this->User->reset_password($this->data['User']['id'], $this->data)){
	            $this->Session->setFlash('Password changed successfully!');
	            $user = $this->User->find('first', array('conditions' => array('User.id' => $this->data['User']['id'])));
	            $this->Auth->login($user);
	            $this->redirect("index");
	        }else{
	            $this->Session->setFlash('Password change failed!');
	        }
	    }
	}
	
	
	function changeUserAddress(){
	   
	    if(!empty($this->data)){
	        $this->User->id = $this->Session->read('Auth.User.id');
	        if($this->User->save($this->data)){
	            $this->Session->setFlash('Your address has been changed successfully!');
	            $this->redirect('index');
	        }
	    }else{
	        $this->data = $this->User->read(array('address','postal_code','city','phone_number'), $this->Session->read('Auth.User.id'));
	    }
	}
	
	
	function get_new_users(){
	    $users = $this->User->get_new_users();
	    
	    if(isset($this->params['requested'])){
	        return $users;
	    }else{
	        $this->set('users',$users);
	    }
	}
	

	
	/*
	 * Admin functions ***********************************************
	 */
	
	function admin_index(){
		
	
	}
	
	function admin_login(){
		if($this->Auth->user('admin')){
		    
			$this->Session->setFlash('Hey Admin!');
			$this->redirect(array('controller' => 'users', 'action' => 'admin_index', 'admin' => true));
			exit();
		}elseif (!empty($this->data)){
			if($this->Auth->user('admin')){
				$this->redirect(array('controller' => 'users', 'action' => 'admin_index', 'admin' => true));
			}else{
				$this->data['User']['password'] = '';
				$this->Session->setFlash('For christ\'s sake, would you at least care to login at the right place, puny human!!!');
			}
			
		}else{
			$this->Session->setFlash('please login!');
		}
	}
	
	function admin_logout(){
		$this->Session->delete('Auth.User');
		$this->Auth->logout();
		$this->Session->setFlash('You have successfully logged off!');
		$this->redirect(array('controller' => 'carts', 'action' => 'index', 'admin' => false));
	}
	
	
	function admin_show_all_users(){
	    
	    $this->set('users', $this->paginate());
	}
	
	function admin_reset_password(){
	    
	    $this->set('uid',$this->passedArgs['uid']);
	    $this->set('uname', $this->passedArgs['uname']);
	    
	    if(!empty($this->data)){
	        $changedPass = $this->data['User']['password'];
            
            
	        if($this->User->reset_password($this->passedArgs['uid'],$this->data)){
	            $this->Session->setFlash('Password changed successfully!');
	            
	            $this->set('changedPass',$changedPass);
	            $this->MyEmail->sendEmail();
	            $this->redirect(array('action' => 'admin_show_all_users','admin' => true));
	        }else{
	            $this->Session->setFlash('Password change error!');
	        }
	    }
	}
	
	
	function admin_view_user($id = null){
	    if(!$id){
	        $this->Session->setFlash('Wrong user ID!');
	    }else{
	        $user = $this->User->findById($id);
	        $this->set(compact('user'));
	    }
	    
	}
	
	
	function admin_edit_user($id = null){
	    pr($this->action);
	    if (!$id && empty($this->data)) {
			$this->Session->setFlash('Invalid User');
			$this->redirect(array('action' => 'admin_show_all_users', 'admin' => true));
		}
		if (!empty($this->data)) {
		           
		    if ($this->User->save($this->data)) {
			    $this->Session->setFlash('User has been edited successfully');
			    $this->redirect(array('action' => 'admin_show_all_users', 'admin' => true));
		    } else {
			    $this->Session->setFlash('User could not be edited!');
		    }
	    
		   
		}
	    if (empty($this->data)) {
			$this->data = $this->User->read(null, $id);
			$this->data['User']['password'] = '';
		}
	}
	
	
	
}

?>