<?php
 App::import('Helper', 'Time');
class Cart extends AppModel{
	var $name = 'Cart';
	var $belongsTo = array('Product');
	
	
	
	function getCart($pid, $sid, $user_id = null){
	    if(!empty($user_id)){
    		return $this->find('all', array('conditions' => array('Cart.product_id' => $pid, 'OR' => array('Cart.user_id' => $user_id, 'Cart.ct_session_id' => $sid)),
    										'order' => 'Cart.id ASC'));
	    }else{
	        return $this->find('all', array('conditions' => array('Cart.product_id' => $pid,
    																'Cart.ct_session_id' => $sid),
    										'order' => 'Cart.id ASC'));
	    }
	}
	
	function addToCart($product_id, $session_id, $user_id = null){
	    $time = new TimeHelper();
		$this->data['Cart']['product_id'] = $product_id;
		$this->data['Cart']['ct_date'] = $time->format("Y-m-d H:i:s", $time->gmt());
		$this->data['Cart']['ct_qty'] = 1;
		$this->data['Cart']['ct_session_id'] = $session_id;
		$this->data['Cart']['user_id'] = $user_id;
		
		$this->save();
	}
	
	function getCartContent($session_id, $user_id = null, $recursion = null){
	
	
		if(isset($recursion) && !empty($recursion)){
		    pr($recursion)
		    $this->recursive = $recursion;
		}else{
		    $this->recursive = 2;
		}
		
		
		if(!empty($user_id)){
		    $cartContent = $this->find('all', array('conditions' => array('OR' => array('Cart.user_id' => $user_id, 'Cart.ct_session_id' => $session_id))));
		}else{
		    $cartContent = $this->find('all', array('conditions' => array('Cart.ct_session_id' => $session_id)));
		}
		
		
		
		
		return $cartContent;
	}
	
	/**
	 * 
	 * Obtiene el precio total del carrito
	 * @param $cart
	 */
	function getCartTotalPrice($cart = null, $session_id = null, $user_id = null){
	    if(empty($cart)){
	        if(!empty($user_id)){
	            $cart = $this->getCartContent($session_id, $user_id);
    	        $totalPrice = 0.00;
        	    $i = 0;
            	foreach($cart as $item){
        			$totalPrice += ($item['Product']['pd_price'] * $item['Cart']['ct_qty']);     			
        		}
	        }else{
	            $cart = $this->getCartContent($session_id);
    	        $totalPrice = 0.00;
        	    $i = 0;
            	foreach($cart as $item){
        			$totalPrice += ($item['Product']['pd_price'] * $item['Cart']['ct_qty']);       			
        		}            
	        }
	        
	    }else{
    	    $totalPrice = 0.00;
    	    $i = 0;
        	foreach($cart as $item){
    			$totalPrice += ($item['Product']['pd_price'] * $item['Cart']['ct_qty']);
    		}    
	    }
	    
		
		return $totalPrice;
	}
	
	//actualiza carro
	function updateCart($product_id, $session_id, $user_id = null){
	    if(!empty($user_id)){
	        $sql = "UPDATE carts SET ct_qty = ct_qty + 1 WHERE user_id = '$user_id' OR ct_session_id = '$session_id' AND product_id = $product_id";
	    }else{
	        $sql = "UPDATE carts SET ct_qty = ct_qty + 1 WHERE ct_session_id = '$session_id' AND product_id = $product_id";
	    }
		
		$this->query($sql);
	}
	
	
	function doUpdate($cart){
		$time = new TimeHelper();
		foreach($cart as $item){
			$this->data['Cart']['ct_qty'] = $item['ct_qty'];
			$this->data['Cart']['ct_date'] = $time->format("Y-m-d H:i:s", time());
			$this->id = $item['id'];
			$this->save();
		}
		
		
		
	//Tiempo de expiracion
	function cleanUp(){
		$threeDaysAgo = date('Y-m-d H:i:s',mktime(0,0,0, date('m'),date('d') - 3, date('Y')));
		$delete_condition = "Cart.ct_date < '$threeDaysAgo'";
		$this->deleteAll($delete_condition, false);
	}
	
	//vaciado
	function emptyCart($cartId){
		if($cartId){
			$this->delete($cartId);
		}
	}
}

?>