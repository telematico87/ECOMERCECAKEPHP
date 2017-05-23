<?php
App::import('File');
App::import('Sanitize');
class ProductsController extends AppController{
	var $name = 'Products';
	//var $helpers = array('Paginator');
	/**
	 * 
	 * Product Model
	 * @var Product
	 */
	var $Product;
	//var $paginate = array('limit' => 10, 'order' => 'Product.id DESC');
	//var $paginate = array('Product', array('conditions' => array()));
	function beforeFilter(){
		parent::beforeFilter();
		
		$this->Auth->allow('view');
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
	
	
	function listProducts(){
		
		$this->autoRender = false;
		$this->Product->recursive = 1;
		
		$categories = $this->Product->Category->getAllCategories();
		$categories = $this->Product->Category->buildCategories($categories,$this->passedArgs['c']);
		$catChildren = $this->Product->Category->getChildCategories($categories,$this->passedArgs['c'],true);
		$allCatIds = array_merge(array($this->passedArgs['c']),$catChildren);
		
	
		$this->paginate = array('conditions' => array('Product.category_id' => $allCatIds),'limit' => 10);
		$data = $this->paginate();
		
		//caracteres HTML reparados de la descripción del produc
		$i=0;
		foreach($data as $product){
		    $data[$i]['Product']['pd_description'] = Sanitize::html($product['Product']['pd_description'], array('remove' => true));
		    $i++;
		}
		
		$this->set('products',$data);
		$this->helpers['Paginator'] = array('ajax' => 'Ajax');
		//pr($this->helpers);		
		if ($this->RequestHandler->isAjax()) {
	        $this->render('/elements/product_list');
	        return;
	    }

		if(isset($this->params['requested'])){		
			//ClassRegistry::getObject('view')->loaded['paginator']->params = $this->params;
			//$this->set('paging', $this->params['paging']);
			return array('products' => $data, 'paging' => $this->params['paging']);
		}else{
			
			$this->render('/elements/product_list');
		}		
	}
	
	
	function view($id = null){
	    if(!empty($id)){
	        $result = $this->Product->read(null,$id);
	    }else{
	        $result = $this->Product->read(null,$this->passedArgs['p']);
	    }
		
		$this->set('product',$result);
		
		if(isset($this->params['requested'])){
			return $result;
		}else{
		    $this->render('/elements/product_details');
		}
	}
	
	function get_featured_products(){
	    $featuredProducts = $this->Product->get_featured_products();
	    
	    return $featuredProducts;
	}
	
	
	function admin_add_product(){
	    
		if(!empty($this->data)){
		    if($this->data['Product']['file']['error'] == 4){
		        
		    }
		    if(!$this->Product->admin_upload_photo($this->data)){
		        //$this->Product->invalidate('file','isUploaded');
		        $this->Session->setFlash('Incorrect file type');
		        //$this->render();
		    }else{
    			$this->Product->create();
    			//$this->data['Product']['pd_image'] = $this->data['Product']['pd_image']['name'];
    			if($this->Product->save($this->data)){
    				$this->Session->setFlash('Product was saved successfully!');
    			}else{
    				$this->Session->setFlash('The product could not be saved');
    			}
		    }
		}
		
	$categories = $this->Product->Category->find('list',array('fields' => array('Category.id','Category.cat_name','Category.cat_parent_id'), 'order' => array('Category.cat_parent_id ASC')));
	//$subCategories = $this->Product->Category->find('list', array('fields' => array('Category.cat_parent_id')));
	//pr($subCategories);
	$this->set(compact('categories'));
	}
	
	function admin_edit_product($id = null){
		if (!$id && empty($this->data)) {
			$this->Session->setFlash('Invalid product');
			$this->redirect(array('action' => 'admin_show_all_products', 'admin' => true));
		}
		if (!empty($this->data)) {
		    
		    //fijamos en el archivo de carga si está vacía
		    if($this->data['Product']['file']['error'] == 4){
		         
		         if(!empty($this->data['Product']['image'])){
		           
		             $this->data['Product']['pd_image'] = $this->data['Product']['image'];
		         }else{
		             $this->data['Product']['pd_image'] = null;
		         }
		    // podemos guardar la imagen en una nueva o sobrescribir
		    }else{
		        $this->data['Product']['pd_image'] = $this->Product->admin_upload_photo($this->data);
		    }
		    
		    if(empty($this->data)){
		        //$this->Product->invalidate('file','isUploaded');
		        $this->Session->setFlash('You must upload a product image');
		        //$this->render();
		    }else{
		        //$this->data['Product']['pd_last_update'] = date('Y-m-d H:i:s', time());
			    if ($this->Product->save($this->data)) {
				    $this->Session->setFlash('Product has been edited successfully');
				    $this->redirect(array('action' => 'admin_show_all_products', 'admin' => true));
			    } else {
				    $this->Session->setFlash('Product could not be edited!');
			    }
		    }
		   
		}
		if (empty($this->data)) {
		    $this->Product->recursive = 1;
			$this->data = $this->Product->read(null, $id);
		}
		
		$categories = $this->Product->Category->find('list',array('fields' => array('Category.id','Category.cat_name')));
	
		$this->set(compact('categories'));
	}
	
	
	function admin_delete_product($id = null){
		if (!$id) {
			$this->Session->setFlash('Invalid id for a product');
			$this->redirect(array('action'=>'admin_show_all_products'));
		}
		if ($this->Product->delete($id)) {
			$this->Session->setFlash('Product was deleted successfully!');
			$this->redirect(array('action'=>'admin_show_all_products'));
		}
		$this->Session->setFlash('Product was not deleted!');
		$this->redirect(array('action' => 'admin_show_all_products'));
	 
	}
	
	function admin_show_all_products(){
		$this->paginate = array('limit' => 5);
		//$products = $this->Product->find('all');
	    $data = $this->paginate();
	    
	    //caracteres HTML reparados de la descripción del producto
	    $i=0;
	    
    	    foreach($data as $product){
                //$data = $data[$i]['Product']['pd_featured'];
    		    $data[$i]['Product']['pd_description'] = Sanitize::html($product['Product']['pd_description'], array('remove' => true));
    		    $i++;
    		}
	    
		
		$this->set('products', $data);
		
		//si el producto se coloca en la primera página o no 
		if(!empty($this->data)){		    
		    $this->Product->id = $this->data['Product']['id'];		    
		    $this->Product->saveField('pd_featured', $this->data['Product']['pd_featured']);
		    if($this->data['Product']['pd_featured'] == 1){
                $this->Session->setFlash('Product is now featured on the main page!'); 
		    }else{
                $this->Session->setFlash('Product has been un-featured!'); 
		    }
		    
		}
		
	}
	
	function admin_get_products_by_category(){
	    if(!empty($this->data)){
            $categories = $this->Product->Category->find('list', array('fields' => array('Category.id','Category.cat_name')));
	        $this->set(compact('categories'));	
	                
	        $categories = $this->Product->Category->getAllCategories();
	        $categories = $this->Product->Category->buildCategories($categories,$this->data['Product']['Category']);
	        $children = $this->Product->Category->getChildCategories($categories, $this->data['Product']['Category'], true);
	        $allCatIds = array_merge(array($this->data['Product']['Category']),$children);
	        //pr($allCatIds);
	        //die;
	        $this->paginate = array('conditions' => array('Product.category_id' => $allCatIds));
	        $data = $this->paginate();
	        
    	    //caracteres HTML reparados de la descripción del producto
    	    $i=0;
    		foreach($data as $product){
    		    $data[$i]['Product']['pd_description'] = Sanitize::html($product['Product']['pd_description'], array('remove' => true));
    		    $i++;
    		}
	        
	        $this->set('products',$data);
	        //$this->data = null;
	    }
	    else{
	        $categories = $this->Product->Category->find('list', array('fields' => array('Category.id','Category.cat_name')));
	        $this->set(compact('categories'));
	        //$this->data = $this->Product->Category->find('list');
	    }
	    
	}
	
	function admin_get_stock_info(){
	 
	    $this->paginate = array('fields' => array('Product.id','Product.pd_name','Product.pd_qty'), 'order' => 'Product.pd_qty ASC');
	    $this->set('products', $this->paginate());
	    
	    if(!empty($this->data)){
		  
		    $productId = $this->passedArgs['pd_id'];
		    $stockQty = $this->data['Product']['pd_qty'];
		    if($this->Product->update_stock_qty($productId,$stockQty)){
		        $this->Session->setFlash('Product stock updated successfully!');
		        $this->redirect(array('action' => "admin_get_stock_info", 'admin' => true));
		    }else{
		        $this->Session->setFlash('Product stock failed to update');
		        $this->redirect(array('action' => "admin_get_stock_info", 'admin' => true));
		    }
		    
		    
		}
	}
	
	//actualizar XMl
	function admin_batch_xml_stock_update(){
	    App::import('Xml');
	    
	    if(!empty($this->data)){
	        $path = WWW_ROOT.'files\\';
	      
	        $xml = $this->data['Product']['file']['tmp_name'];
	        $xmlName = $this->data['Product']['file']['name'];
	        $file = new File($xml);
	        $xmlContent = $file->read();
	        $file->close();
	        
	        /
	        $file = new File($path.$xmlName, true);
	        $file->write($xmlContent);
	        $file->close();
	        
	   
	        $parsed_xml = new Xml($path.$xmlName);
	        $parsed_xml = Set::reverse($parsed_xml);
	        
	      
	        $this->Product->batch_xml_update($parsed_xml);
	        $this->Session->setFlash('Xml update successful!'); 
	    }    
	    
	}
	
	/*cartacteisticas de un producto*/
	function admin_feature_product($id = null){
	    if (!$id) {
			$this->Session->setFlash('Invalid id for a product');
			$this->redirect(array('action'=>'admin_show_all_products'));
		}
		$this->Product->id = $this->data['Product']['id'];
		    
		if ($this->Product->saveField('featured', 1)) {
			$this->Session->setFlash('Product was featured successfully!');
			$this->redirect(array('action'=>'admin_show_all_products'));
		}
		$this->Session->setFlash('Product was not featured!');
		$this->redirect(array('action' => 'admin_show_all_products'));
	}
	
	
}
?>