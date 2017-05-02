<div class="inner-box">
    <div class="background-container">
       <div class="cycle-slideshow" 
            data-cycle-fx="fade" 
            data-cycle-timeout="2400"
            data-cycle-speed="1200"
            data-cycle-slides="> div"
        >
            <% loop $SliderPhoto %>
                <div class="background" style="background-image:url($Filename);"></div>              
            <% end_loop %>
        </div>
    </div>

    <div class="content-container" style="background-image:url($BackgroundImage.Filename);">
        <div class="content-inner">
            <div class="intro-box section-box clearfix">

                <div class="intro-block">
                    <div class="intro-block-inner">
                        <a href="$OnlineShopLink" target='_blank'>
                            <div class="title">ONLINE SHOP</div>
                            <div class="sub-title"></div>
                        </a>
                        <div class="content">
                            <img src="$ShopImage.filename">
                        </div>
                    </div>
                </div>

                 <div class="intro-block video-box">
                    <div class="intro-block-inner">
                        <iframe src="$VideoLink" frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>

            </div>
            <a name="location"></a>
            <% include LocationPage %>
        </div>
    </div>
</div>
