<?php
class CartsController extends AppController{
	var $name = 'Carts';
	/**
	 * 
	 * Cart Model
	 * @var Cart
	 */
	var $Cart;
	
	
	function beforeFilter(){
		parent::beforeFilter();
		$this->Auth->allow('*');
	}
	
	
	function getCartContent(){
	    
	    if($this->Auth->user()){
		    return $this->Cart->getCartContent($this->sid, $this->Session->read('Auth.User.id'));
	    }else{
	        return $this->Cart->getCartContent($this->sid);
	    }
	}
	
	
	function addToCart(){
		$result = $this->Cart->Product->findById($this->p);	
		
		if(empty($result)){
			$this->Session->setFlash('This product was not found!');
			$this->redirect(array('action' => 'index'));
		}else{
			if($result['Product']['pd_qty'] <= 0){
				$this->Session->setFlash('The product you requested is out of stock!');
				$this->redirect(array('action' => 'index'));
			}
		}		
		//El usuario esta registrado
		if($this->Auth->user()){	     
    		$sessionData = $this->Cart->getCart($this->p, $this->sid, $this->Session->read('Auth.User.id'));
    		if(empty($sessionData)){   		    
    	        $this->Cart->addToCart($this->p, $this->sid, $this->Session->read('Auth.User.id'));  		
    			$this->Session->setFlash('Product added to cart! -> through user ID / inserted');
    		}else{
    			$this->Cart->updateCart($this->p, $this->sid, $this->Session->read('Auth.User.id'));
    			$this->Session->setFlash('Product added to cart! -> through session ID / updated');
    		}
    	//Si el usuario no esta registrado
		}else{
		    $sessionData = $this->Cart->getCart($this->p, $this->sid);
    		if(empty($sessionData)){ 		    
    	        $this->Cart->addToCart($this->p, $this->sid);
    			$this->Session->setFlash('Product added to cart! -> through session ID / inserted');
    		}else{
    			$this->Cart->updateCart($this->p, $this->sid);
    			$this->Session->setFlash('Product added to cart! -> through session ID / updated');
    		}
		}		
		$this->Cart->cleanUp();
		$this->redirect(array('controller' => 'carts', 'action' => "index/c:$this->c/p:$this->p"));
	}
	
	//ver el contenido del carrito
	function view(){
	    if($this->Auth->user()){
		    $cartContents = $this->Cart->getCartContent($this->sid, $this->Session->read('Auth.User.id'), 1);
	    }else{
	        $cartContents = $this->Cart->getCartContent($this->sid, null, 1);
	    }
		
		
		$totalPrice = $this->Cart->getCartTotalPrice($cartContents);
		$i = 0;
		
		
		$this->set('cartContents', $cartContents);
		$this->set('totalPrice', $totalPrice);
		
		
		
		if(!empty($this->data['Cart'])){
		   
			$this->Cart->doUpdate($this->data['Cart']);
			$this->redirect(array('controller' => 'carts','action' => 'view/c:'.$this->c));
		}
	}
	
	//vacia carrito
	function emptyCart(){
		$this->Cart->emptyCart($this->passedArgs['ct']);
		$this->redirect(array('action' => 'view/c:'.$this->c));
	}
	
	//ver si vacio carro
	function isCartEmpty(){
		$result = $this->Cart->find('first',array('conditions' => array('Cart.ct_session_id' => $this->sid)));
		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}
}
?>