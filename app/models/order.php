<?php
class Order extends AppModel{
	var $name = 'order';
	/**
	 * 
	 * Product associated model
	 * @var Product
	 */
	var $Product;
	
	
	
	var $hasAndBelongsToMany = array(
		'Product' => 
			array(
				'className' => 'Product',
				'joinTable' => 'orders_products',
				'foreignKey' => 'order_id',
				'associationForeignKey' => 'product_id'
			)
	);
	
	var $virtualFields = array('od_shipping_full_name' => 'CONCAT(Order.od_shipping_first_name, " ", Order.od_shipping_last_name)');
		
	var $validate = array(
	    'payment_option' => array(
	        'rule' => array('notEmpty'),
	        'message' => 'this field must not be empty!'
	    ),
	);	
		
		
	//almacena orden
	function saveOrder($orderData, $session_id ,$user_id = null){
		$time = new TimeHelper();
		//guardar orden en la tabla de pedidos
		$orderData['Order']['od_date'] = $time->format("Y-m-d H:i:s", time());
		$orderData['Order']['od_payment_tax'] = 0.00;
		$this->save($orderData);
		    			
		//obtiene id de orden
		$order_id = $this->getInsertID();
				
		if(!empty($user_id)){
		    $result = $this->Product->Cart->getCartContent($session_id, $user_id);
		}else{
		    $result = $this->Product->Cart->getCartContent($session_id);
		}
		//productos y cantidades
		$product_ids = array();
		$orderQty = array();
		$i = 0;		
		foreach ($result as $item){
		    $product_ids[$i] = $item['Product']['id'];
		    $orderQty[$i] = $item['Cart']['ct_qty'];
		    $i++;
		}
		//almacenamiento provicional
		$this->addAssoc('Product', $product_ids, $order_id, $orderQty);
		
		
		//preparar productos
		$products = array();	
		foreach($result as $item){
			array_push($products, $item['Product']);
			$value = $item['Product']['pd_qty'] - $item['Cart']['ct_qty'];
			$this->Product->id = $item['Cart']['product_id'];
			$this->Product->saveField('pd_qty', $value);
		}
		
		//eliminar el contenido de carritos de compras
		foreach($result as $item){
			$this->Product->Cart->emptyCart($item['Cart']['id']);
		}
		
		$order = $this->findById($order_id);
		
		return $order;
		
		
	}
	
	//obtener orden de no mas de un dia
	function get_recent_orders(){
	    $time = new TimeHelper();
	    	    
        $dayAgo = time() - 86400;
        
        $formatedDayAgo = $time->format("Y-m-d H:i:s",$dayAgo);
	    $result =  $this->find('all', array('conditions' => array('od_date >=' => $formatedDayAgo)));
	    
	    return $result;
	}
	
	//suma de todos los pedidos o ordenes
	function get_total_payed_orders_sum($orders = null, $enforce = false){
	    if(empty($orders)){
	        $orders =  $this->find('all', array('conditions' => array('od_status' => 'Completed')));
	    }
	    
	    $totalSum = 0.00;
	    foreach($orders as $order){
    	        $totalSum += ($order['Order']['od_payment_total']);
	    }
	    
    
	    return $totalSum;
	}
	
	
	
	// producto de un orden especifico
	function get_ordered_items($id){
	    $result = $this->find('first', array('conditions' => array('Order.id' => $id)));
	    $orderedProducts = array();
	    
	    
	    return $result;
	}
	
	//obetener pedidos en un rango de fechas
	function get_orders_by_time($fromDate, $toDate){
	    $orders = $this->find('all', array('conditions' => array('Order.od_date >=' => $fromDate, 'Order.od_date <=' => $toDate)));
	    
	    return $orders;
	}
	
	
	
}
?>