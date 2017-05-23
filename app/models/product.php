<?php
class Product extends AppModel{
	var $name = 'Product';
	/**
	 * 
	 * Cart model
	 * @var Cart
	 */
	var $Cart;
	
	
	var $belongsTo = array(
		'Category' => array(
			'className' => 'Category',
			'foreignKey' => 'category_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
	var $hasMany = array('Cart');
	var $hasAndBelongsToMany = array(
		'Order' => 
			array(
				'className' => 'Order',
				'joinTable' => 'orders_products',
				'foreignKey' => 'product_id',
				'associationForeignKey' => 'order_id'
			)
		);
		
		
	
	function listProducts($catId = null){
		$results = $this->find('all', array('conditions' => array('Product.category_id' => $catId),
											'order' => 'Product.category_id ASC'));
		return $results;
	}
	
	

    function isUploadedFile($params,$field){
	    $val = $params['file'];
    	if ((isset($val['error']) && $val['error'] == 0) || (!empty( $val['tmp_name']) && $val['tmp_name'] != 'none')) {
    		return is_uploaded_file($val['tmp_name']);
    	}
    	$this->invalidate($field,'Error uploading file');
	    return false;
    }
    
    function update_stock_qty($id, $qty){
             
        $this->id = $id;
        if($this->saveField('pd_qty', $qty)){
            return true;
        }else{    
            return false;
        }
    }
    
    //operaciones CRUD vía XML
    function batch_xml_update($data){
        foreach($data['Products']['Product'] as $product){
            
            if(isset($product['operation']) && $product['operation']== 'insert'){
                $this->id = null;
                $this->data['Product']['id'] = $product['id'];
                $this->data['Product']['category_id'] = $product['category_id'];
                $this->data['Product']['pd_name'] = $product['pd_name'];
                $this->data['Product']['pd_description'] = $product['pd_description'];
                $this->data['Product']['pd_price'] = $product['pd_price'];
                $this->data['Product']['pd_qty'] = $product['pd_qty'];
                //$this->data['Product']['pd_image'] = $product['pd_image'];
                $this->data['Product']['pd_date'] = $product['pd_date'];
                
                              $this->save();
            }elseif (isset($product['operation']) && $product['operation']== 'update'){
                $this->id = $product['id'];
                $this->saveField('pd_qty', $product['pd_qty']);
            }elseif(isset($product['operation']) && $product['operation']== 'delete'){
                $this->id = $product['id'];
                $this->delete();
            }
            
        }
        
        return true;
    }
    
    
    function admin_upload_photo($image = null){
	    $path = "img\\products\\";
	    $dir = WWW_ROOT.$path;
	    
	    //ubicacion actual de la imagen
	    $imageTmp = $image['Product']['file']['tmp_name'];
	    $imageName = $image['Product']['file']['name'];
	    $file = new File($imageTmp);
	    
	    //extension
	    $ext = $file->ext();	  
	   
	    
	    $fileData = $file->read();
	    $file->close();
	    
	    //grabar nuevo fichero
	    $file = new File($dir.$imageName,true);
	    $file->write($fileData);
	    $file->close();
	    
	  
	    
	    return $imageName;
	}
	
	
	function get_featured_products(){
	    $this->recursive = 0;
	    $results = $this->find('all',array('conditions' => array('Product.pd_featured' => true)));
	    return $results;
	}
	
}
?>