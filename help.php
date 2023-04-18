<?php
/**
 *  Dokan Dashboard Template
 *
 *  Dokan Main Dahsboard template for Fron-end
 *
 *  @since 2.4
 *
 *  @package dokan
 */
?>

<style>

ul.btn-tabs{
    border-bottom:none !important;
    margin-left:0px !important;
}
.btn-tabs.nav-tabs .nav-item.show .nav-link, .btn-tabs.nav-tabs .nav-link.active{
    background-color: #2370F4 !important;
    border-color: #2370F4 !important;
    color: #fff !important;
}
.btn-nav.nav-link{
    background-color: #f5f5f5 !important;
    border-color: #f5f5f5 !important;
    color: #000 !important;
    /* margin-right: 10px; */
}

.square-line{
    width: 100%;
    height: 600px;
    display: flex;
    /* overflow-y: scroll; */
    /* align-items: center; */
    /* justify-content: center; */
    /* flex:1; */
}

.form-container{
    display: flex;
    flex-direction: column;
    /* justify-content: flex-start; */
    margin-top: 3rem;
    width: 30%;

}

.form-container{
    margin-right: 40px;
}

.form-container h2{
    font-size: 18px;
}

.form-container .square_token{
    width: 250px !important;
}

.form-container .suqare_connect_btn{
    margin-top: 5%;
}


.square-listing{
    overflow-y: scroll;
    width: 70%;
    /* display: flex; */
    /* flex-direction: column;
    justify-content: center;
    align-items: center; */
}

.square-listing img{
    width: 100px;
    height: 100px;
}

.square-item-container{}
.square-item{
    display: flex;
    margin-bottom: 10px;
}
.square-item h3{
    font-size: 15px;
}

.square-item .square-item-img{
    border-radius: 16px;
    overflow: hidden;
    /* width: 50%; */
}
.square-item-content{
    margin-left: 10px;
}

.square_import_btn{
    width: 45%;
    display: none;
    margin: 0 auto;
}


.loading__square{
  width: 108px;
  height: 108px;
  list-style-type: none;
  display: flex;
  flex-wrap: wrap;
}
.loading__square li{
  width: 30px;
  height: 30px;
  margin: 2px;
  background-color: transparent;
  animation: loading 0.8s infinite;

}

.loading__square li:nth-child(5){
  opacity: 0;
}
.loading__square li:nth-child(1){
  animation-delay: 0.1s;
}
.loading__square li:nth-child(2){
  animation-delay: 0.2s;
}
.loading__square li:nth-child(3){
  animation-delay: 0.3s;
}
.loading__square li:nth-child(6){
  animation-delay: 0.4s;
}
.loading__square li:nth-child(9){
  animation-delay: 0.5s;
}
.loading__square li:nth-child(8){
  animation-delay: 0.6s;
}
.loading__square li:nth-child(7){
  animation-delay: 0.7s;
}
.loading__square li:nth-child(4){
  animation-delay: 0.8s;
}
@keyframes loading{
  1%{
    background-color: #0086b3;
  }
}

.square__tab{
    position: relative;
}
.loading__square_section{
    background-color: #fff;
    position: absolute;
    height: 100%;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.5s;
}

/* .square-listing{

} */

/* .square-item  */
/* .square-item-img
.square-item-content */


.import-features{
    /* display: flex; */
    /* justify-content: space-between; */
    /* flex-wrap: wrap; */
}

.import-features div{
    display: flex;
    margin-right: 10px;
}

.import-features i{
    padding-right: 5px;
    margin-top: 5px;
}


    @media (min-width:768px) {
  .tab-content .accordion-item {
    border: 0;
    border-radius: 0;
  }
}
@media (max-width:767px) {
  .tab-content > .tab-pane {
        display: block;
        opacity: 1;
    }
}
</style>

<div class="dokan-dashboard-wrap">
    <?php
        /**
         *  dokan_dashboard_content_before hook
         *
         *  @hooked get_dashboard_side_navigation
         *
         *  @since 2.4
         */
        do_action( 'dokan_dashboard_content_before' );
    ?>

    <div class="dokan-dashboard-content">

        <?php
            /**
             *  dokan_dashboard_content_before hook
             *
             *  @hooked show_seller_dashboard_notice
             *
             *  @since 2.4
             */
            do_action( 'dokan_help_content_inside_before' );
        ?>

        <article class="pos-content-area">
        
            <div class="tabs_with_accordion_wrapper">
                <div class="container">

                    <input type="hidden" name="ajax_url" class="ajax_url" id="ajax_url" data-imgdum="<?php echo plugin_dir_url( __FILE__ ).'images/thumb-img.jpg'; ?>" value="<?php echo admin_url('admin-ajax.php'); ?>">

                    <!-- Nav Tabs List -->
                    <ul class="btn-tabs nav nav-tabs d-none d-md-flex" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="btn-nav nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Square</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="btn-nav nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Lightspeed</button>
                        </li>
                    </ul>

                    <!-- Nav Tabs Content -->
                    <div class="tab-content" id="myTabContent">
                    
                        <div class="tab-pane square__tab accordion-item fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <!-- Square main container -->
                            <div class="loading__square_section" >
                            <ul class="loading__square">
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                            </ul>
                            </div>


                            <div class="square-line">
                                <form class="form-container square__form-container">
                                    <!-- <h2>Connect With Square</h2>
                                    <input type="text" name="square_token" id="square_access_token" class="square_token" placeholder="Place your square access token"/>
                                    <button class="btn btn-primary suqare_connect_btn">Connect</button> -->
                                    <a href="https://connect.squareup.com/oauth2/authorize?client_id=sq0idp-7w6KCKAR2s1ei5L9BpocdQ&scope=CUSTOMERS_WRITE+CUSTOMERS_READ+MERCHANT_PROFILE_READ+ITEMS_READ+INVENTORY_READ&session=false&state=82201dd8d83d23cc8a48caf52b" style="background-color: #2370F4; margin-top: 10px; padding:10px; display:flex; align-items:center">
                                        <img src="<?php echo plugin_dir_url( __FILE__ ).'images/square.svg'; ?>" alt="">
                                        <span style="color:#FFF; font-weight:700; font-size:18px; margin-left:5px;">Connect with Square</span>
                                    </a>
                                </form>
                                <div class="square-listing">
                                       <div class="square-item-container">
                                            <!-- Render Items -->
                                            <!-- <div class="square-item">
                                                    <div class="square-item-img">
                                                        <img src="<?php //echo plugin_dir_url( __FILE__ ).'images/thumb-img.jpg'; ?>" alt="">
                                                    </div>
                                                    <div class="square-item-content">
                                                        <h3>Square</h3>
                                                        <p>Square is a payment processing platform.</p>
                                                    </div>
                                            </div> -->
                                            <h3>No Item</h3>

                                       </div>
                                       <button class="btn btn-primary square_import_btn" data-toggle="modal" data-target="#modalInfo">Import All Products</button>
                                </div>

                                <!-- Square Modal -->
                                
                                <div class="modal square-modal-confirm fade" tabindex="-1" role="dialog" aria-labelledby="modalInfo" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                        <div class="modal-header">
                                            <h4 class="modal-title" id="myModalLabel">Please confirm</h4>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="text-shadow: none; opacity: 1; color: #FFF;">
                                            <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="import-features">

                                                <h3 style="font-size: 16px;">Following attributes would be in your products from SquareUp.</h3>

                                                <div>
                                                    <i class="fa-solid fa-check"></i>
                                                    <p>Prodcut title</p>
                                                </div>
                                                <div>
                                                    <i class="fa-solid fa-check"></i>
                                                    <p>Prodcut description</p>
                                                </div>

                                                <div>
                                                    <i class="fa-solid fa-check"></i>
                                                    <p>Prodcut SKU</p>
                                                </div>

                                                <div>
                                                    <i class="fa-solid fa-check"></i>
                                                    <p>Prodcut UPC</p>
                                                </div>

                                                <div>
                                                    <i class="fa-solid fa-check"></i>
                                                    <p>Prodcut price</p>
                                                </div>

                                                <div>
                                                    <i class="fa-solid fa-check"></i>
                                                    <p>Prodcut category</p>
                                                </div>

                                                <div>
                                                    <i class="fa-solid fa-check"></i>
                                                    <p>Prodcut images</p>
                                                </div>

                                                <div>
                                                    <i class="fa-solid fa-check"></i>
                                                    <p>Prodcut locations</p>
                                                </div>
                                            </div>
                                        
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn" style="background: #f5f5f5 !important; color:#000" data-dismiss="modal">Cancel</button>
                                            <button type="button" class="btn btn-primary square_connect_confirm">Confirm</button>
                                        </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- End Square Modal -->


                            </div>
                        </div>
                    
                        <div class="tab-pane accordion-item fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <h2 class="accordion-header d-md-none" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Profile</button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse d-md-block" aria-labelledby="headingTwo" data-bs-parent="#myTabContent">
                            <div class="accordion-body">
                                <!-- <strong>This is the second item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow. -->
                            </div>
                            </div>
                        </div>
                
                    </div>
                </div>
            </div>

        </article><!-- .dashboard-content-area -->

         <?php
            /**
             *  dokan_dashboard_content_inside_after hook
             *
             *  @since 2.4
             */
            do_action( 'dokan_dashboard_content_inside_after' );
        ?>


    </div><!-- .dokan-dashboard-content -->

    <?php
        /**
         *  dokan_dashboard_content_after hook
         *
         *  @since 2.4
         */
        do_action( 'dokan_dashboard_content_after' );
    ?>

</div><!-- .dokan-dashboard-wrap -->