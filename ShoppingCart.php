<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ShoppingCart
 *
 * @author Jenny
 */
class ShoppingCart extends DataObject {
    
    const CART_SESSION_NAME = "shopping-cart";
    
    
    private static $db = array(
        "Content" => "Varchar(500)"
    );
    
    
    private static $has_one = array(
        "Customer" => "Customer_Member"
    );
    
    
    
    
    /**
     * Function is to check if customer has logged in or not
     * @return boolean
     */
    public function ifCustomerLoggedIn(){
        if($this->CustomerID){
            return TRUE;
        }
        return FALSE;
    }
    
    
    
    /**
     * load cart content from database if customer logged in
     * or we need to load from session
     * @return type
     */
    protected function loadCartContent(){
        if($this->ifCustomerLoggedIn()){
            return $this->Content;
        }
        
        return Session::get(self::CART_SESSION_NAME);
    }
    
    
    /**
     * Function is to get cart from content string
     * @return type
     */
    protected function getCart(){
        $content = $this->loadCartContent();

        if($content){
            return Convert::json2array($content);
        }
        return array();
    }


    /**
     * Function is to setup content back to shopping cart
     * @param type $content
     */
    protected function setCart($content){
        $string = Convert::array2json($content);
        
        //if customer logged in, we need to write string into database
        if($this->ifCustomerLoggedIn()){
            $this->Content = $string;
            $this->write();
            return;
        }
        
        //otherwise we write into session
        Session::set(self::CART_SESSION_NAME, $string);
    }
    
    
    
    /**
     * Function is to check if cart is empty
     * @return boolean
     */
    public function isCartEmpty(){
        $cart = $this->getCart();

        if(count($cart) == 0){
            return TRUE;
        }
        return FALSE;
    }
    
    
    /**
     * Function is to check if this store cart EMPTY / NOT
     * @param type $store
     * @return boolean
     */
    public function isCartEmptyByStore($store){
        $cart = $this->getCart();

        if(count($cart) == 0){
            return TRUE;
        }
        
        //To find the store
        if(!key_exists($store->ID, $cart)){
            return TRUE;
        }
        
        foreach($cart[$store->ID] as $id => $item){
            
            $option = Product_Option::get()->byID($id);
            if(!$option){
                //To remove item from cart
                $this->remove($store->ID, $id);
                //To remove from temp cart array
                unset($cart[$store->ID][$id]);
            }
            
        }//END foreach
        
        
        
        //When there is on items in certain store, delete the store cart 
        if(count($cart[$store->ID]) == 0){
            return TRUE;
        }
        
        
        
        return FALSE;
    }
    
    




    /**
     * Function is to add product option into cart
     * @param type $store_id Description
     * @param type $option_id
     * @param type $qty
     */
    public function add($store_id, $option_id, $qty){
        $cart = $this->getCart();
        
        //To find the store
        if(key_exists($store_id, $cart)){
            $store = $cart[$store_id];
        }else{
            $store = array();
        }
        
        //To find the option
        if(key_exists($option_id, $store)){
            $store[$option_id] = $store[$option_id] + $qty;
        }else{
            $store[$option_id] = $qty;
        }
        
        //we need to setup store backto cart
        $cart[$store_id] = $store;
        
        $this->setCart($cart);
    }
    
    
    
    /**
     * Function is to edit product option into cart
     * @param type $store_id
     * @param type $option_id
     * @param type $qty
     */
    public function edit($store_id, $option_id, $qty) {
        $cart = $this->getCart();
        
        //To find the store
        if(!key_exists($store_id, $cart)){
            die("CANNOT FIND THE STORE FROM SHOPPING CART");
        }
        
        //To find the option and then remove it from cart
        if(!key_exists($option_id, $cart[$store_id])){
            die("CANNOT FIND THE PRODUCT OPTION FROM SHOPPING CART");
        }
        
        
        //if qty == 0 we just remove it from shopping cart
        if($qty == 0){
            unset($cart[$store_id][$option_id]);
        }else{
            $cart[$store_id][$option_id] = $qty;
        }
        
        //When there is on items in certain store, delete the store cart 
        if(count($cart[$store_id]) == 0){
            unset($cart[$store_id]);
        }
        
        
        
        $this->setCart($cart);
    }
    
    
    
    
    
    
    
    /**
     * Function is to remove option from cart
     * @param type $store_id
     * @param type $option_id
     */
    public function remove($store_id, $option_id) {
        $cart = $this->getCart();
        
        //To find the store
        if(!key_exists($store_id, $cart)){
            die("CANNOT FIND THE STORE FROM SHOPPING CART");
        }
        
        //To find the option and then remove it from cart
        if(key_exists($option_id, $cart[$store_id])){
            unset($cart[$store_id][$option_id]);
        }
        
        //When there is on items in certain store, delete the store cart 
        if(count($cart[$store_id]) == 0){
            unset($cart[$store_id]);
        }
        
        $this->setCart($cart);
    }
    
    
    
    
    
    
    /**
     * Function is to clear shopping cart
     */
    public function clear(){
        //we just pass empty array
        $this->setCart(array());
    }
    
    
    /**
     * Function is to clear store shopping cart
     * @param Store $store
     */
    public function clearStoreCart(Store $store){
        $cart = $this->getCart();
        
        //To find the store object
        if(key_exists($store->ID, $cart)){
            unset($cart[$store->ID]);
        }
        
        $this->setCart($cart);
    }
    
    
    
    /**
     * Function is to get cart data by store
     * @param Store $store
     * @return \ArrayData
     */
    public function CartDataByStore(Store $store){
        
        $cart = $this->getCart();
        //define the list
        $list = new ArrayList();
        
        if($this->isCartEmpty()){  
            return new ArrayData(array(
                "IsEmpty" => TRUE,
                "Total" => 0,
                "Products" => null,
                "TotalAmount" => new Currency(),
                "TotalWeight" => 0
            ));
        }
        
        //To find the store
        if(!key_exists($store->ID, $cart)){
            die("CANNOT FIND THE STORE FROM SHOPPING CART");
        }
        
        
        // define the value
        $total_amount = 0;
        $product_total_weight =0;
        
        //For loop options
        foreach ($cart[$store->ID] as $option_id => $qty) {
            
            $option = Product_Option::get()->byID($option_id);
            //If we didn't find the option, we just do nothing here
            if(!$option){
                continue;
            }
            
            
            $subtotal = $option->RealCurrentPrice()->getValue() * $qty;
            
            
            $subTotal_obj = new Currency();
            $subTotal_obj->setValue($subtotal);
        
            //To get total amount
            $total_amount += $subtotal;
            
            $list->push(new ArrayData(array(
                "Option" => $option,
                "QTY" => $qty,
                "SubTotal" => $subTotal_obj
            )));
            
            
            //start calculate products weight
            $product = $option->Product();

            if($product->CanShipping){
                
                $unit_weight_value = $product->KGWeightValue();

                $product_total_weight += ($unit_weight_value * $qty);
            }
        }//END foreach
        
        $total_obj = new Currency();
        $total_obj->setValue($total_amount);
        
        
        return new ArrayData(array(
            "IsEmpty" => FALSE,
            "Total" => $list->count(),
            "Products" => $list,
            "TotalAmount" => $total_obj,
            "TotalWeight" => $product_total_weight
        ));
    }
    
    
    /**
     * Function is to get JSON cart data by store
     * @param Store $store
     * @return type
     */
    public function CartJSONDataByStore(Store $store){
        $data = $this->CartDataByStore($store);
        $json = $data->toMap();
        
        $json["TotalAmount"] = $data->TotalAmount->value;
        
        if(key_exists("Products", $json)){
            unset($json["Products"]);
        }
        
        return Convert::array2json($json);
    }
    
    
    /**
     * Function is to cart info applied by coupon
     * @param Store $store
     * @param Coupon $coupon
     * @return string
     */
    public function ApplyCouponDataByStore(Store $store, Coupon $coupon){
        $data = $this->CartDataByStore($store);
        
        //To get cart total
        $cart_amount = $data->TotalAmount->getValue();
        
        if($coupon->isBasedOnRate()){
            $discount_string = $coupon->DiscountRate . "%";
            $discount_amount = $cart_amount * ($coupon->DiscountRate / 100);
        }else{
            $discount_string = "NZ $" . $coupon->DiscountAmount;
            $discount_amount = $coupon->DiscountAmount;
        }
        
        //To get total amount
        $total_amount = $cart_amount - $discount_amount;
        
        //To setup discount amount currency
        $discount_amount_obj = new Currency();
        $discount_amount_obj->setValue($discount_amount);
        
        //To setup total amount currency
        $total_amount_obj = new Currency();
        $total_amount_obj->setValue($total_amount);
        
        $return = array(
            "DiscountString" => $discount_string,
            "DiscountAmount" => $discount_amount_obj,
            "CartTotalAmount" => $data->TotalAmount,
            //current cart total after applied coupon discount amount
            "TotalAmount" => $total_amount_obj
        );
        
        return $return;
    }
    
    
}
