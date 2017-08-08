/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function($){
    
    //To define the global object
    $.store = $.store || {};
    
    /**
     * TODO
     * define the cart form object
     */
    $.store.cartform = function(){
        /* --- PRIVATE PROPERTY --- */
        var $cartForm,
            base_url,
            $successBox,
            form_serialize
            ;
    
        
        /**
         * Function is to setup form
         * @returns {undefined}
         */
        function _setupForm(){
            
            /**
             * TODO
             * bind the form change & input
             */
            $cartForm.on("change input", function(){      
                var _current_serialize = $cartForm.serialize();
                
                
                
                //To check current form value if changed
                if(_current_serialize !== form_serialize){
                    
                    $cartForm
                        .find(".actions-box .button")
                        .removeClass("disabled");
                    
                }else{
                    
                    $cartForm
                        .find(".actions-box .button")
                        .addClass("disabled");
                        
                    
                }//END if
                
            });
            
            
            
            //To bind the qty button event
            //Bind minus one event
            $cartForm.on(
                "click", 
                "[sis-tag='qty-holder'] .qty-minus-btn", 
                function(){
                    var _$this = $(this),
                        _$parent = _$this.parents("[sis-tag='qty-holder']"),
                        _$input = _$parent.find("[sis-tag='qty-input']");
                    
                    if(canQTYMinusOne(_$input)){
                        updateInputValue(_$input, -1);
                    }
                }
            );


            //Bind add one event
            $cartForm.on(
                "click", 
                "[sis-tag='qty-holder'] .qty-add-btn", 
                function(){
                    var _$this = $(this),
                        _$parent = _$this.parents("[sis-tag='qty-holder']"),
                        _$input = _$parent.find("[sis-tag='qty-input']"),
                        _max = JSON.parse(_$this.attr("track-qty-max"));
                    
                    if(canQTYAddOne(_$input, _max)){
                        updateInputValue(_$input, 1);
                    }
                }
            );
        
        
            //Bind blur & change event
            $cartForm
                .find("[sis-tag='qty-input']")
                .bind("focus", function(){
                        $(this).attr("old-qty", this.value);
                    })
                    .bind("blur", function(){
                        //define the currency regex
                        var regex = /^[0-9]\d*$/,
                            _$this = $(this),
                            _$parent = _$this.parents("[sis-tag='qty-holder']"),
                            _$addBtn = _$parent.find(".qty-add-btn"),
                            //To get max setting object
                            _max = JSON.parse(_$addBtn.attr("track-qty-max"))
                            ;

                        if(
                            this.value === "" || 
                            !regex.test(this.value) ||
                            !_canQTYUpdated(_max, this.value)
                        ){
                            this.value = _$this.attr("old-qty");
                            _$this.removeAttr("old-qty");
                            return false;
                        }

                        
                        //To update items price
                        _$this.removeAttr("old-qty");
                        $(this).trigger("change");
                    });
                
            
            
            //To bind the buynow btn
            $cartForm
                .find("[sis-tag='buynow-btn']")
                .bind("click", function(){
                    
                    if($(this).hasClass("disabled")){
                       return false;
                    }
                   
                    //To update new url of form
                    var _new_url = base_url + "buynow";

                    $cartForm.attr({"action":_new_url});

                    $cartForm.submit();      
                    return false;
                });
            
            
            //To bind the buynow btn
            $cartForm
                .find("[sis-tag='addcart-btn']")
                .bind("click", function(){
                   
                   if($(this).hasClass("disabled")){
                       return false;
                   }
                   
                   doAJAXAddCartRequest();    
                   
                   return false;
                });
            
        };
        
        
        /**
         * Function is to update input value
         * @param {type} $input
         * @param {type} qty
         * @returns {undefined}
         */
        function updateInputValue($input, qty){
            var _value = parseInt($input.val());
            
            _value += qty;
            $input.val(_value);
            $input.trigger("change");
        }
        
        
        /**
         * Function is to check if this input can minus one
         * @param {type} $input
         * @returns {Boolean}
         */
        function canQTYMinusOne($input){
            var _value = parseInt($input.val());
            
            if(_value > 0){
                return true;
            }
            return false;
        };
        
        /**
         * Function is to check if this input can add one
         * @param {type} $input
         * @returns {Boolean}
         */
        function canQTYAddOne($input, max){
            if(!max.IsTrackQTY || max.MAX == 0){
                return true;
            }
            
            var _value = parseInt($input.val()) + 1;
            
            if(_value <= max.MAX){
                return true;
            }
            return false;
        };
        
        
        
        /**
         * Function is to check if QTY can be updated by number
         * @param {type} max
         * @param {type} number
         * @returns {Boolean}
         */
        function _canQTYUpdated(max, number){
            if(!max.IsTrackQTY || max.MAX == 0){
                return true;
            }
            
            if(number <= max.MAX){
                return true;
            }
            return false;
        }
        
        
        /**
         * Function is to do ajax request for adding cart
         * @returns {undefined}
         */
        function doAJAXAddCartRequest(){
            
            //To make form ajax
            $cartForm.ajaxSubmit({
                url : base_url + "add",
                dataType: "JSON",
                
                beforeSubmit: function(){
                    $cartForm.addClass("ajax-submitting");
                },
                
                complete: function(){
                    $cartForm.removeClass("ajax-submitting");
                },
                
                //To handle form success
                success: function(result){
                    
                    if(!result.success){
                        return;
                    }
                    
                    
                    //To reset form
                    $cartForm
                        .resetForm()
                        .trigger("change");
                    
                    //To re-append options box
                    $cartForm.find(".shopping-cart-box")
                            .empty()
                            .html(result.data.html);
                    
                    //To update total number of cart
                    $("#shopping-cart-btn .btn-text-inner")
                            .html(result.data.total);
                    
                    
                    //To play animation
                    playSuccessAnimation();
                    
                    //To call reload right side bar
                    $.isstore.rightsidebar.reload();
                }
            });
            
        };
        
        
        /**
         * Function is to play success animtion
         * @returns {undefined}
         */
        function playSuccessAnimation(){
            $successBox.show();
            
            //To get offset position
            var _offset = $("#shopping-cart-btn").offset();
            
            //To play animate
            $successBox.animate(
                {
                    top:     _offset.top, 
                    left:    _offset.left, 
                    opacity: .1
                },
                800,
                function(){
                    
                    $successBox
                            .removeAttr("style")
                            .hide();
                    
                }
            );
        };
        
        
        
    
        
        /* --- PUBLIC PROPERTY --- */
        var _public = {
            
            /**
             * init function
             * @returns {undefined}
             */
            init: function(){
                if($("#shopping-cart-form").length == 0){
                    return;
                }
                
                
                //init vars
                $cartForm = $("#shopping-cart-form"); 
                form_serialize = $cartForm.serialize();
                base_url = $cartForm.attr("action");
                $successBox = $cartForm.find("#added-cart-animate");
                
                //To setup form
                _setupForm();
                
            }
            
        };
        return _public;
    }();
    
    
})(jQuery);
