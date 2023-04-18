<?php


// Square Form Ajax
add_action('wp_ajax_nopriv_pos_tokens_connect', 'pos_tokens_connect');
add_action('wp_ajax_pos_tokens_connect', 'pos_tokens_connect');


function pos_tokens_connect()
{
    global $wpdb;

    if (is_user_logged_in()) {

        $userID =  get_current_user_id();

        $getUser = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}square_creds WHERE user_id = $userID LIMIT 1");

        if (count($getUser) > 0) {
            echo $square_access_token = $getUser[0]->access_token;
        } else {
            echo '';
        }
    }

    die();
}


/*
* Adding extra field on New product popup/without popup form
*/

add_action('dokan_new_product_after_product_tags', 'new_product_field', 10);


function new_product_field()
{ ?>

    <div class="dokan-form-group">

        <input type="text" class="dokan-form-control" name="upc_field" placeholder="<?php esc_attr_e('GTIN', 'dokan-lite'); ?>">
    </div>

    <div class="dokan-form-group">

        <input type="text" class="dokan-form-control" name="Address_2" placeholder="<?php esc_attr_e('ADDRESS', 'dokan-lite'); ?>">

    </div>

<?php
}

/*
* Saving product field data for edit and update
*/

add_action('dokan_new_product_added', 'save_add_product_meta', 10, 2);
add_action('dokan_product_updated', 'save_add_product_meta', 10, 2);

function save_add_product_meta($product_id, $postdata)
{

    if (!dokan_is_user_seller(get_current_user_id())) {
        return;
    }

    if (!empty($postdata['upc_field'])) {
        update_post_meta($product_id, 'upc_field', $postdata['upc_field']);
    }
}

/*
* Showing field data on product edit page
*/

add_action('dokan_product_edit_after_product_tags', 'show_on_edit_page', 99, 2);

function show_on_edit_page($post, $post_id)
{

    global $wpdb;

    $upc_field     = get_post_meta($post_id, 'upc_field', true);

    $variety     = get_post_meta($post_id, 'variety', true);

    if($variety ==1){

    
    // Table Work
    $product = wc_get_product($post_id);
    $variations = $product->get_available_variations();
    $exists_variations_ids = wp_list_pluck( $variations, 'variation_id' );

    // print_r($exists_variations_ids);

}

?>
    <div class="dokan-form-group">
        <input type="hidden" name="upc_field" id="dokan-edit-product-id" value="<?php echo esc_attr($post_id); ?>" />
        <label for="upc_field" class="form-label"><?php esc_html_e('GTIN', 'dokan-lite'); ?></label>
        <?php dokan_post_input_box($post_id, 'upc_field', array('placeholder' => __('GTIN', 'dokan-lite'), 'value' => $upc_field)); ?>
        <div class="dokan-product-title-alert dokan-hide">
            <?php esc_html_e('Please enter GTIN!', 'dokan-lite'); ?>
        </div>
    </div>


    <?php if($variety ==1){ ?>


    <div class="additional__addresses">

        <table style="width: 155%;">
            <thead>
                <th>#</th>
                <th>Image</th>
                <th>Variation</th>
                <th>Price</th>
                <th>Sku</th>
                <th>Location</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Quantity</th>
            </thead>
            <tbody>

    <?php

    $i = 1;

        foreach($exists_variations_ids as $exists_variations_id => $exists_variationValue){

            

            $variation_name = get_post_meta($exists_variationValue, 'attribute_item-variations', true);
            $attachment_id  =  get_post_meta($exists_variationValue, '_thumbnail_id', true); 

            $variation_price  =  get_post_meta($exists_variationValue, '_price', true); 
            $variation_sku  =  get_post_meta($exists_variationValue, '_sku', true); 
            
            $variation_data = get_post_meta($exists_variationValue, 'by_variation', true);
            $variation_data = unserialize($variation_data);
            
            // print_r($variation_data);

    ?>
                <tr>
                    <td><?php echo $exists_variationValue; ?></td>
                    <td style="width:100px"><img src="<?php echo $attachment_id ? wp_get_attachment_url(  $attachment_id ) : plugin_dir_url( __FILE__ ) . 'images/thumb-img.jpg'; ?>"/></td>
                    <td><?php echo $variation_name; ?></td>
                    
                    <td><?php echo wc_price($variation_price); ?></td>
                    <td><?php echo $variation_sku; ?></td>

                    <td>
                        <?php foreach($variation_data as $variation_item => $variation_loc_value){ ?>
                            <p>
                            <?php echo $variation_loc_value->proper_address;?>
                            </p>
                        <?php } ?>
                    </td>
                    
                    <td>
                        <?php foreach($variation_data as $variation_item => $variation_loc_value){ ?>
                            <p>
                            <?php echo $variation_loc_value->lat;?>
                            </p>
                        <?php } ?>
                    </td>
                    <td>
                        <?php foreach($variation_data as $variation_item => $variation_loc_value){ ?>
                            <p>
                                <?php echo $variation_loc_value->long;?>
                            </p>
                        <?php } ?>
                    </td>
                    <td>
                        <?php foreach($variation_data as $variation_item => $variation_loc_value){ ?>
                        <p><?php echo $variation_loc_value->qty; ?></p>
                        <?php } ?>
                    </td>
                </tr>

        <?php
        $i++;
        // }
         
    }
        ?>
            </tbody>
        </table>

    </div>
    <?php
 }
}

// showing on single product page
add_action('woocommerce_single_product_summary', 'show_product_code', 13);

function show_product_code()
{
    global $product;

    if (empty($product)) {
        return;
    }
    $upc_field = get_post_meta($product->get_id(), 'upc_field', true);

    if (!empty($upc_field)) {
    ?>
        <span class="details"><?php echo esc_attr__('Product Code:', 'dokan-lite'); ?> <strong><?php echo esc_attr($upc_field); ?></strong></span>
<?php
    }
}


// Square API Processing

require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use Square\SquareClient;
use Square\LocationsApi;
use Square\Exceptions\ApiException;
use Square\Http\ApiResponse;
use Square\Models\ListLocationsResponse;
use Square\Environment;

// Square Form Ajax
add_action('wp_ajax_nopriv_pos_square_connect', 'pos_square_connect');
add_action('wp_ajax_pos_square_connect', 'pos_square_connect');

function pos_square_connect()
{

    global $wpdb;

    // if(!is_user_logged_in()){
    //     return;
    // }

    try {

    if (is_user_logged_in()) {

        $userID =  get_current_user_id();

        $getUser = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}square_creds WHERE user_id = $userID");

        if(count($getUser)>0){
            $square_access_token = $getUser[0]->access_token;
        }else{
            return;
        }

        $client = new SquareClient([
            'accessToken' => $square_access_token,
            'environment' => Environment::PRODUCTION,
        ]);

        // if (count($getUser) > 0) {
        //     $wpdb->update(
        //         $wpdb->prefix . 'pos_creds',
        //         array(
        //             'access_token' => $square_access_token,
        //             'user_id' => $userID
        //         ),
        //         array(
        //             'user_id' => $userID
        //         ),
        //     );
        // } else {
        //     $wpdb->insert(
        //         $wpdb->prefix . 'pos_creds',
        //         array(
        //             'access_token' => $square_access_token,
        //             'app_key' => '',
        //             'secret_key' => '',
        //             'user_id' => $userID
        //         )
        //     );
        // }

        $catalog_response = $client->getCatalogApi()->listCatalog(null, 'ITEM');

        if ($catalog_response->isSuccess()) {
            $result = $catalog_response->getResult();
            $josn_enc =  json_encode($result);
            $json_dec = json_decode($josn_enc);
            $json_decObjects = $json_dec->objects;

            // print_r($json_decObjects);

            $output = [];

            foreach ($json_decObjects as $key => $value) {

                // print_r($value->type);

                $item_id = $value->id;

                $item_name = $value->item_data->name;

                $item_description = $value->item_data->description;


                $category_id = $value->item_data->category_id;

                // print_r($item_name);

                // Category Fetched
                if ($category_id) {

                    $catobject_ids = [$category_id];
                    $catbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($catobject_ids);
                    $catbody->setIncludeRelatedObjects(false);

                    $catapi_responses = $client->getCatalogApi()->batchRetrieveCatalogObjects($catbody);

                    if ($catapi_responses->isSuccess()) {
                        $catresults = $catapi_responses->getResult();

                        $cat_decoded = json_decode(json_encode($catresults));

                        $cat_name = $cat_decoded->objects[0]->category_data->name;
                    } else {
                        $errors = $catapi_responses->getErrors();
                    }
                }


                // Retrive Images

                $img_ids = $value->item_data->image_ids;

                if ($img_ids) {

                    $imgobject_ids = [];

                    foreach ($img_ids as $img_id) {

                        array_push($imgobject_ids, $img_id);
                    }

                    $imgbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($imgobject_ids);
                    $imgbody->setIncludeRelatedObjects(false);

                    $imgapi_response = $client->getCatalogApi()->batchRetrieveCatalogObjects($imgbody);

                    if ($imgapi_response->isSuccess()) {
                        $imgresult = $imgapi_response->getResult();

                        $img_decoded = json_decode(json_encode($imgresult));

                        $imgObj = $img_decoded->objects;

                        $img_array = [];

                        foreach ($imgObj as $img) {
                            $img_array[] = $img->image_data->url;
                        }

                        // print_r($img_decoded->objects[0]->image_data->url);

                    } else {
                        $errors = $imgapi_response->getErrors();
                    }
                }

                // Inventory Count

                $variation_ids = $value->item_data->variations[0]->id;

                $variation_location_id = $value->item_data->variations[0]->item_variation_data->location_overrides[0]->location_id;

                $variation_SKU = $value->item_data->variations[0]->item_variation_data->sku;

                $variation_UPC = $value->item_data->variations[0]->item_variation_data->upc;

                $variation_price = $value->item_data->variations[0]->item_variation_data->price_money;

                // print_r($variation_price);


                if ($variation_ids && $variation_location_id) {

                    $inv_catalog_object_ids = [$variation_ids];
                    $inv_location_ids = [$variation_location_id];
                    $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                    $inv_body->setCatalogObjectIds($inv_catalog_object_ids);
                    $inv_body->setLocationIds($inv_location_ids);

                    $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                    if ($inv_api_response->isSuccess()) {
                        $inv_result = $inv_api_response->getResult();

                        $inv_decoded = json_decode(json_encode($inv_result));

                        $quantity = $inv_decoded->counts[0]->quantity;
                    } else {
                        $errors = $inv_api_response->getErrors();
                    }
                }



                array_push(
                    $output,
                    (object)[
                        'type'                  => $value->type,
                        'item_id'               => $item_id,
                        'item_name'             => $item_name,
                        'item_description'      => $item_description,
                        'cat_name'              => $category_id ? $cat_name : '',
                        'variation_SKU'         =>  $variation_SKU ? $variation_SKU : '',
                        'variation_UPC'         =>  $variation_UPC ? $variation_UPC : '',
                        'quantity'              => ($variation_ids && $variation_location_id) ? $quantity : '',
                        'variation_price'       =>  $variation_price ?: '',
                        'image_url'             =>  $img_ids ? $img_array : ''

                    ]
                );
            }

            // print_r($output);

            echo json_encode($output);


            // print_r($json_dec->objects[0]->item_data->variations[0]->id);
            // print_r($json_dec->objects[0]->item_data->variations[0]->item_variation_data->location_overrides[0]->location_id);

        } else {
            $errors = $catalog_response->getErrors();
        }
    }
  } catch (ApiException $e) {
        echo "Invald Token";
    }


    die();
}




// Square Form Ajax
add_action('wp_ajax_nopriv_pos_square_pos_import', 'pos_square_pos_import');
add_action('wp_ajax_pos_square_pos_import', 'pos_square_pos_import');

function pos_square_pos_import()
{

    global $wpdb;


    if (is_user_logged_in()) {

        $userID =  get_current_user_id();

        $getUser = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}square_creds WHERE user_id = $userID LIMIT 1");

        $square_access_token = $getUser[0]->access_token;

        $client = new SquareClient([
            'accessToken' => $square_access_token,
            'environment' => Environment::PRODUCTION,
        ]);



        $catalog_response = $client->getCatalogApi()->listCatalog(null, 'ITEM');

        if ($catalog_response->isSuccess()) {
            $result = $catalog_response->getResult();
            $josn_enc =  json_encode($result);
            $json_dec = json_decode($josn_enc);
            $json_decObjects = $json_dec->objects;

            // print_r($json_decObjects);

            $output = [];

            foreach ($json_decObjects as $key => $value) {

                // For Variable Products
                if (count($value->item_data->variations) > 1) {

                    $item_id = $value->id;

                    $item_name = $value->item_data->name;

                    $item_description = $value->item_data->description;


                    $category_id = $value->item_data->category_id;

                    // Category Fetched
                    if ($category_id) {

                        $catobject_ids = [$category_id];
                        $catbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($catobject_ids);
                        $catbody->setIncludeRelatedObjects(false);

                        $catapi_responses = $client->getCatalogApi()->batchRetrieveCatalogObjects($catbody);

                        if ($catapi_responses->isSuccess()) {

                            $catresults = $catapi_responses->getResult();

                            $cat_decoded = json_decode(json_encode($catresults));

                            $cat_name = $cat_decoded->objects[0]->category_data->name;
                        } else {
                            $errors = $catapi_responses->getErrors();
                        }
                    }



                    // Retrive Images

                    $img_ids = $value->item_data->image_ids;

                    if ($img_ids) {

                        $imgobject_ids = [];

                        foreach ($img_ids as $img_id) {

                            array_push($imgobject_ids, $img_id);
                        }

                        $imgbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($imgobject_ids);
                        $imgbody->setIncludeRelatedObjects(false);

                        $imgapi_response = $client->getCatalogApi()->batchRetrieveCatalogObjects($imgbody);

                        if ($imgapi_response->isSuccess()) {
                            $imgresult = $imgapi_response->getResult();

                            $img_decoded = json_decode(json_encode($imgresult));

                            $imgObj = $img_decoded->objects;

                            $img_array = [];

                            foreach ($imgObj as $img) {


                                // print_r($img->image_data);

                                $img_array[] = (object)['name' => $img->image_data->name, 'url' => $img->image_data->url];
                            }

                            // print_r($img_decoded->objects[0]->image_data->url);

                        } else {
                            $errors = $imgapi_response->getErrors();
                        }
                    }


                    // Inventory Count Against Multiple Locations

                    $qtys = [];

                    if (count($value->present_at_location_ids) > 0 || $value->present_at_all_locations == 1) {

                        if ($value->present_at_all_locations == 1) {
                            $variation_location_idss = [];

                            $alll_locations_api_response = $client->getLocationsApi()->listLocations();

                            if ($alll_locations_api_response->isSuccess()) {
                                $result = $alll_locations_api_response->getResult();


                                $all_locationsArray = json_decode(json_encode($result));

                                $all_locationsArray = $all_locationsArray->locations;

                                foreach ($all_locationsArray as $allLocation => $allLocationValue) {
                                    array_push($variation_location_idss, $allLocationValue->id);
                                }

                                // print_r($variation_location_idss);

                                // exit;

                            } else {
                                $errors = $alll_locations_api_response->getErrors();
                            }

                            //$variation_location_idss = $value->present_at_location_ids;

                        } else {

                            $variation_location_idss = $value->present_at_location_ids;
                        }


                        // print_r($variation_location_idss);

                        if (count($variation_location_idss) > 0) {

                            $i = 0;

                            foreach ($variation_location_idss as $vli => $val_inv) {

                                // print_r($val_inv);
                                // echo "<br>";
                                // echo $i;


                                $inv_catalog_object_idss = [$value->item_data->variations[$i]->id];

                                $inv_location_idss = [$val_inv];


                                $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                                $inv_body->setCatalogObjectIds($inv_catalog_object_idss);
                                $inv_body->setLocationIds($inv_location_idss);

                                $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                                if ($inv_api_response->isSuccess()) {
                                    $inv_result = $inv_api_response->getResult();

                                    $inv_decoded = json_decode(json_encode($inv_result));

                                    // print_r($inv_decoded);

                                    $qty = $inv_decoded->counts[0]->quantity;
                                    // print_r($qty);

                                    $qtys[] = $qty;
                                } else {
                                    $errors = $inv_api_response->getErrors();
                                }


                                $i++;
                            }
                        }

                        // exit;


                    }

                    // print_r($qtys);

                    // exit;



                    // Multiple Locations

                    $all_address = [];

                    if (count($value->present_at_location_ids) > 0 || $value->present_at_all_locations == 1) {

                        if($value->present_at_all_locations == 1){

                            $variation_location_idss = [];

                            $alll_locations_api_response = $client->getLocationsApi()->listLocations();

                            if ($alll_locations_api_response->isSuccess()) {

                                $result = $alll_locations_api_response->getResult();


                                $all_locationsArray = json_decode(json_encode($result));

                                $all_locationsArray = $all_locationsArray->locations;


                                foreach ($all_locationsArray as $allLocation => $allLocationValue) {

                                    $address_line_1 = $allLocationValue->address->address_line_1 ?: '';
                                    $address_line_2 = $allLocationValue->address->address_line_2 ?: '';
                                    $locality = $allLocationValue->address->locality ?: '';

                                    $all_proper_address = $address_line_1 . ', ' . $address_line_2 . ', ' . $locality;

                                    $all_add_lat = $allLocationValue->coordinates->latitude ?: '';
                                    $all_add_long = $allLocationValue->coordinates->longitude ?: '';

                                    array_push($all_address, (object)['address' => $all_proper_address, 'lat' => $all_add_lat, 'long' => $all_add_long]);

                                }

                                // print_r($all_locationsArray);

                                // foreach ($all_locationsArray as $allLocation => $allLocationValue) {
                                //     array_push($variation_location_idss, $allLocationValue->id);
                                // }

                                // print_r($variation_location_idss);

                                // exit;

                            } else {
                                $errors = $alll_locations_api_response->getErrors();
                            }


                        }else{

                            $mul_locations = $value->present_at_location_ids;

                            // print_r($mul_locations);

                            for ($i = 0; $i < count($mul_locations); $i++) {
                                // echo $mul_locations[$i];


                                $location_api_response = $client->getLocationsApi()->retrieveLocation($mul_locations[$i]);

                                if ($location_api_response->isSuccess()) {
                                    $locationResult = $location_api_response->getResult();

                                    $locationResult = json_decode(json_encode($locationResult));

                                    // print_r($locationResult);

                                    $addressline = $locationResult->location->address->address_line_1 ?: '';
                                    $locality = $locationResult->location->address->locality ?: '';
                                    $administrative_district_level_1 = $locationResult->location->address->administrative_district_level_1 ?: '';

                                    $all_proper_address = $addressline . ', ' . $locality . ', ' . $administrative_district_level_1;

                                    $all_add_lat = $locationResult->location->coordinates->latitude ?: '';
                                    $all_add_long = $locationResult->location->coordinates->longitude ?: '';

                                    array_push($all_address, (object)['address' => $all_proper_address, 'lat' => $all_add_lat, 'long' => $all_add_long]);
                                } else {
                                    $errors = $location_api_response->getErrors();
                                }
                            }

                        }


                    }


                    $variations = [];
                    $variationsArray = $value->item_data->variations;
                    $variationqtys = [];

                    foreach($variationsArray as $variationItem => $variationItemValue){

                        $vName  =  $variationItemValue->item_variation_data->name?:'N/A';
                        $vSku   =  $variationItemValue->item_variation_data->sku?:'N/A';
                        $vPrice =  $variationItemValue->item_variation_data->price_money;
                        $vUpc   =  $variationItemValue->item_variation_data->upc ?:'N/A';



                        // Inventory Count Against Multiple Locations

                    if (count($variationItemValue->item_variation_data->location_overrides) > 0 || $variationItemValue->present_at_all_locations == 1) {

                        // if ($value->present_at_all_locations == 1) {
                        //     $variation_location_idss = [];

                        //     $alll_locations_api_response = $client->getLocationsApi()->listLocations();

                        //     if ($alll_locations_api_response->isSuccess()) {
                        //         $result = $alll_locations_api_response->getResult();


                        //         $all_locationsArray = json_decode(json_encode($result));

                        //         $all_locationsArray = $all_locationsArray->locations;

                        //         foreach ($all_locationsArray as $allLocation => $allLocationValue) {
                        //             array_push($variation_location_idss, $allLocationValue->id);
                        //         }

                        //         // print_r($variation_location_idss);

                        //         // exit;

                        //     } else {
                        //         $errors = $alll_locations_api_response->getErrors();
                        //     }

                        //     //$variation_location_idss = $value->present_at_location_ids;

                        // } else {

                            $variation_location_idss = $variationItemValue->item_variation_data->location_overrides;
                        // }


                        // print_r($variation_location_idss);

                        if (count($variation_location_idss) > 0) {

                            $byVariationData =  [];

                            $i = 0;

                            foreach ($variation_location_idss as $vli => $val_inv) {

                                // print_r($val_inv);
                                // echo "<br>";
                                // echo $i;


                                $inv_catalog_object_idss = [ $variationItemValue->id];

                                $inv_location_idss = [$val_inv->location_id];


                                // ------------ Locations info ------------
                                $var_location_api_response = $client->getLocationsApi()->retrieveLocation($val_inv->location_id);

                                if ($var_location_api_response->isSuccess()) {

                                    $vari_locationResult = $var_location_api_response->getResult();
                                    $vari_locationResult = json_decode(json_encode($vari_locationResult));
                                    $vari_locationResult = $vari_locationResult->location;

                                    $vari_address_line_1 = $vari_locationResult->address->address_line_1 ?: '';
                                    $vari_address_line_2 = $vari_locationResult->address->address_line_2 ?: '';
                                    $vari_locality = $vari_locationResult->address->locality ?: '';

                                    $vari_all_proper_address = $vari_address_line_1 . ', ' . $vari_address_line_2 . ', ' . $vari_locality;

                                    $vari_all_add_lat = $vari_locationResult->coordinates->latitude ?: '';
                                    $vari_all_add_long = $vari_locationResult->coordinates->longitude ?: '';

                                    // print_r($vari_locationResult);

                                } else {
                                    $errors = $var_location_api_response->getErrors();
                                }


                                // ------------ End Locations info -----------

                                $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                                $inv_body->setCatalogObjectIds($inv_catalog_object_idss);
                                $inv_body->setLocationIds($inv_location_idss);

                                $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                                if ($inv_api_response->isSuccess()) {
                                    $inv_result = $inv_api_response->getResult();

                                    $inv_decoded = json_decode(json_encode($inv_result));

                                    // print_r($inv_decoded);

                                    $qty = $inv_decoded->counts[0]->quantity;
                                    // print_r($qty);

                                    $variationqtys[] = (object)['name'=> $vName, 'qty' => $qty, 'proper_address' => $vari_all_proper_address, 'lat' => $vari_all_add_lat, 'long' => $vari_all_add_long];
                                    $byVariationData[] = (object)['qty' => $qty, 'proper_address' => $vari_all_proper_address, 'lat' => $vari_all_add_lat, 'long' => $vari_all_add_long];
                                } else {
                                    $errors = $inv_api_response->getErrors();
                                }


                                $i++;
                            }
                        }

                        // exit;


                    }

                        // Retrive Images

                        $img_idss = $variationItemValue->item_variation_data->image_ids;

                        if ($img_idss) {

                            $imgobject_ids = [];

                            foreach ($img_idss as $img_id) {

                                array_push($imgobject_ids, $img_id);
                            }

                            $imgbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($imgobject_ids);
                            $imgbody->setIncludeRelatedObjects(false);

                            $imgapi_response = $client->getCatalogApi()->batchRetrieveCatalogObjects($imgbody);

                            if ($imgapi_response->isSuccess()) {
                                $imgresult = $imgapi_response->getResult();

                                $img_decoded = json_decode(json_encode($imgresult));

                                $imgObj = $img_decoded->objects;

                                $imgs_array = [];

                                foreach ($imgObj as $img) {

                                    // print_r($img->image_data);

                                    $imgs_array[] = (object)['name' => $img->image_data->name?:'N/A', 'url' => $img->image_data->url?:'N/A'];
                                }

                                // print_r($img_decoded->objects[0]->image_data->url);

                            } else {
                                $errors = $imgapi_response->getErrors();
                            }
                        }

                        //print_r( $byVariationData);
                        
                        array_push($variations, (object)['name' => $vName, 'sku' => $vSku, 'price' => $vPrice, 'upc' => $vUpc, 'images' => $img_idss ? $img_array : [], "by_variation"=> $byVariationData ]);

                    }

                    // print_r($variationqtys);
                    // exit;

                    array_push(
                        $output,
                        (object)[
                            'type'                  => $value->type,
                            'item_id'               => $item_id,
                            'item_name'             => $item_name,
                            'item_description'      => $item_description,
                            'cat_name'              => $category_id ? $cat_name : '',
                            // 'variation_SKU'         =>  $variation_SKU ? $variation_SKU : '',
                            //'variation_UPC'         =>  $variation_UPC ? $variation_UPC : '',
                            //'quantity'              => ($variation_ids && $variation_location_id) ? $quantity : '',
                            // 'variation_price'       =>  $variation_price ?: '',
                            'image_url'             =>  $img_ids ? $img_array : [],
                            //'proper_address'        =>  $proper_address ? $proper_address : '',
                            //'add_lat'               =>  $add_lat ? $add_lat : '',
                            //'add_long'              =>  $add_long ? $add_long : '',
                            'all_address'           =>  $all_address ? $all_address : [],
                            'qtys'                  =>  $qtys ? $qtys : [],
                            'variations'            =>  $variations ? $variations : [],
                            'variationqtys'         =>  $variationqtys,
                            'multi_variation'       =>  true,
                        ]
                    );

                   
                }
                else{

                // Single Variation

                // }

                // print_r($value->type);

                $item_id = $value->id;

                $item_name = $value->item_data->name;

                $item_description = $value->item_data->description;


                $category_id = $value->item_data->category_id;

                // print_r($item_name);

                // Category Fetched
                if ($category_id) {

                    $catobject_ids = [$category_id];
                    $catbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($catobject_ids);
                    $catbody->setIncludeRelatedObjects(false);

                    $catapi_responses = $client->getCatalogApi()->batchRetrieveCatalogObjects($catbody);

                    if ($catapi_responses->isSuccess()) {

                        $catresults = $catapi_responses->getResult();

                        $cat_decoded = json_decode(json_encode($catresults));

                        $cat_name = $cat_decoded->objects[0]->category_data->name;
                    } else {
                        $errors = $catapi_responses->getErrors();
                    }
                }


                // Retrive Images

                $img_ids = $value->item_data->image_ids;

                if ($img_ids) {

                    $imgobject_ids = [];

                    foreach ($img_ids as $img_id) {

                        array_push($imgobject_ids, $img_id);
                    }

                    $imgbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($imgobject_ids);
                    $imgbody->setIncludeRelatedObjects(false);

                    $imgapi_response = $client->getCatalogApi()->batchRetrieveCatalogObjects($imgbody);

                    if ($imgapi_response->isSuccess()) {
                        $imgresult = $imgapi_response->getResult();

                        $img_decoded = json_decode(json_encode($imgresult));

                        $imgObj = $img_decoded->objects;

                        $img_array = [];

                        foreach ($imgObj as $img) {


                            // print_r($img->image_data);

                            $img_array[] = (object)['name' => $img->image_data->name, 'url' => $img->image_data->url];
                        }

                        // print_r($img_decoded->objects[0]->image_data->url);

                    } else {
                        $errors = $imgapi_response->getErrors();
                    }
                }

                // Inventory Count

                $variation_ids = $value->item_data->variations[0]->id;

                $variation_location_id = $value->item_data->variations[0]->item_variation_data->location_overrides[0]->location_id;

                $variation_SKU = $value->item_data->variations[0]->item_variation_data->sku;

                $variation_UPC = $value->item_data->variations[0]->item_variation_data->upc;

                $variation_price = $value->item_data->variations[0]->item_variation_data->price_money;

                // print_r($variation_location_id);


                if ($variation_ids && $variation_location_id) {

                    $inv_catalog_object_ids = [$variation_ids];
                    $inv_location_ids = [$variation_location_id];
                    $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                    $inv_body->setCatalogObjectIds($inv_catalog_object_ids);
                    $inv_body->setLocationIds($inv_location_ids);

                    $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                    if ($inv_api_response->isSuccess()) {
                        $inv_result = $inv_api_response->getResult();

                        $inv_decoded = json_decode(json_encode($inv_result));

                        $quantity = $inv_decoded->counts[0]->quantity;
                    } else {
                        $errors = $inv_api_response->getErrors();
                    }
                }


                // Inventory Count Against Multiple Locations

                $qtys = [];

                if (count($value->present_at_location_ids) > 0) {

                    $variation_location_idss = $value->present_at_location_ids;

                    // print_r($variation_location_idss);

                    if (count($variation_location_idss) > 0) {

                        foreach ($variation_location_idss as $vli => $val_inv) {

                            $inv_catalog_object_idss = [$variation_ids];

                            $inv_location_idss = [$val_inv];


                            $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                            $inv_body->setCatalogObjectIds($inv_catalog_object_idss);
                            $inv_body->setLocationIds($inv_location_idss);

                            $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                            if ($inv_api_response->isSuccess()) {
                                $inv_result = $inv_api_response->getResult();

                                $inv_decoded = json_decode(json_encode($inv_result));

                                // print_r($inv_decoded);

                                $qty = $inv_decoded->counts[0]->quantity;
                                // print_r($qty);

                                $qtys[] = $qty;
                            } else {
                                $errors = $inv_api_response->getErrors();
                            }
                        }
                    }
                }

                // print_r($qtys);



                // if($variation_ids && $variation_location_id){

                //     $inv_catalog_object_ids = [$variation_ids];
                //     $inv_location_ids = [$variation_location_id];
                //     $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                //     $inv_body->setCatalogObjectIds($inv_catalog_object_ids);
                //     $inv_body->setLocationIds($inv_location_ids);

                //     $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                //     if ($inv_api_response->isSuccess()) {
                //         $inv_result = $inv_api_response->getResult();

                //         $inv_decoded = json_decode(json_encode($inv_result));

                //         $quantity = $inv_decoded->counts[0]->quantity;

                //     } else {
                //         $errors = $inv_api_response->getErrors();
                //     }
                // }


                if ($variation_location_id) {

                    $location_api_response = $client->getLocationsApi()->retrieveLocation($variation_location_id);

                    if ($location_api_response->isSuccess()) {
                        $locationResult = $location_api_response->getResult();

                        $locationResult = json_decode(json_encode($locationResult));

                        // print_r($locationResult);

                        $addressline = $locationResult->location->address->address_line_1 ?: '';
                        $locality = $locationResult->location->address->locality ?: '';
                        $administrative_district_level_1 = $locationResult->location->address->administrative_district_level_1 ?: '';

                        $proper_address = $addressline . ', ' . $locality . ', ' . $administrative_district_level_1;

                        $add_lat = $locationResult->location->coordinates->latitude ?: '';
                        $add_long = $locationResult->location->coordinates->longitude ?: '';
                    } else {
                        $errors = $location_api_response->getErrors();
                    }
                }


                // Multiple Locations

                $all_address = [];

                if (count($value->present_at_location_ids) > 0) {

                    $mul_locations = $value->present_at_location_ids;

                    // print_r($mul_locations);

                    for ($i = 0; $i < count($mul_locations); $i++) {
                        // echo $mul_locations[$i];


                        $location_api_response = $client->getLocationsApi()->retrieveLocation($mul_locations[$i]);

                        if ($location_api_response->isSuccess()) {
                            $locationResult = $location_api_response->getResult();

                            $locationResult = json_decode(json_encode($locationResult));

                            // print_r($locationResult);

                            $addressline = $locationResult->location->address->address_line_1 ?: '';
                            $locality = $locationResult->location->address->locality ?: '';
                            $administrative_district_level_1 = $locationResult->location->address->administrative_district_level_1 ?: '';

                            $all_proper_address = $addressline . ', ' . $locality . ', ' . $administrative_district_level_1;

                            $all_add_lat = $locationResult->location->coordinates->latitude ?: '';
                            $all_add_long = $locationResult->location->coordinates->longitude ?: '';

                            array_push($all_address, (object)['address' => $all_proper_address, 'lat' => $all_add_lat, 'long' => $all_add_long]);
                        } else {
                            $errors = $location_api_response->getErrors();
                        }
                    }
                }


                array_push(
                    $output,
                    (object)[
                        'type'                  => $value->type,
                        'item_id'               => $item_id,
                        'item_name'             => $item_name,
                        'item_description'      => $item_description,
                        'cat_name'              => $category_id ? $cat_name : '',
                        'variation_SKU'         =>  $variation_SKU ? $variation_SKU : '',
                        'variation_UPC'         =>  $variation_UPC ? $variation_UPC : '',
                        'quantity'              => ($variation_ids && $variation_location_id) ? $quantity : '',
                        'variation_price'       =>  $variation_price ?: '',
                        'image_url'             =>  $img_ids ? $img_array : [],
                        'proper_address'        =>  $proper_address ? $proper_address : '',
                        'add_lat'               =>  $add_lat ? $add_lat : '',
                        'add_long'              =>  $add_long ? $add_long : '',
                        'all_address'           =>  $all_address ? $all_address : [],
                        'qtys'                  =>  $qtys ? $qtys : [],
                        'multi_variation'       =>  false,
                    ]
                );

              }
            }

            if (count($output) > 0 ) {

                $symobl = [
                    'BHD'       =>     1000,
                    'CNY'        =>     10,
                    'IRR'        =>    1,
                    'IQD'        =>    1000,
                    'KWD'       =>    1000,
                    'LYD'       =>    1000,
                    'MGA'       =>    5,
                    'MRU'       =>  5,
                    'OMR'       =>  1000,
                    'MRU'       =>  5,
                    'TND'       =>  1000,
                    'VND'       =>  10,
                ];



                foreach ($output as $item_a => $val) {

                    if($val->multi_variation == 1){

                        $square_catalog_item_id = $val->item_id;

                        $getItemID = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE `meta_key` = 'square_catalog_item_id' AND `meta_value` = '$square_catalog_item_id'");

                        if (count($getItemID) == 0) {

                            // $article_name = 'Test';

                            $post_id = wp_insert_post( array(
                                'post_author' => $userID,
                                'post_title' => $val->item_name,
                                'post_content' =>$val->item_description,
                                'post_status' => 'publish',
                                'post_type' => "product",
                            ) );

                            wp_set_object_terms( $post_id, 'variable', 'product_type' );


                            // Add Custom Attributes
                            update_post_meta($post_id, 'square_catalog_item_id', $square_catalog_item_id);

                            update_post_meta($post_id, 'variationqtys', serialize($val->variationqtys));

                            wp_set_object_terms($post_id, $val->cat_name, 'product_cat');

                            update_post_meta($post_id, '_visibility', 'visible');
                            update_post_meta($post_id, '_stock_status', 'instock');

                            update_post_meta($post_id, '_regular_price', $val->variations[0]->price->amount);
                            update_post_meta($post_id, '_sku', $val->variations[0]->sku);

                            update_post_meta($post_id, '_stock', $val->qtys[0]);

                            update_post_meta($post_id, 'variety', 1);


                            if (count($val->qtys) > 0) {
                                for ($i = 0; $i < count($val->qtys); $i++) {
                                    update_post_meta($post_id, 'qtys' . $i, $val->qtys[$i] ?: 0);
                                }
                            }

                            $all_addr = $val->all_address;

                            // Store one address
                            if (count($all_addr) > 0) {

                                update_post_meta($post_id, "dokan_geo_address",  $all_addr[0]->address);
                                update_post_meta($post_id, "dokan_geo_latitude", $all_addr[0]->lat);
                                update_post_meta($post_id, "dokan_geo_longitude", $all_addr[0]->long);

                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'no');
                            } else {
                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'yes');
                            }

                            update_post_meta($post_id, 'upc_field', $val->variations[0]->upc != 'N/A' ? $val->variations[0]->upc : 'N/A');


                            // Store All Addresses  
                           

                            if (count($all_addr) > 0) {
                                for ($i = 0; $i < count($all_addr); $i++) {
                                    update_post_meta($post_id, 'addr' . $i, $all_addr[$i]->address ?: '');
                                    update_post_meta($post_id, 'addr_latitude' . $i, $all_addr[$i]->lat ?: '');
                                    update_post_meta($post_id, 'addr_longitude' . $i, $all_addr[$i]->long ?: '');
                                }
                            }



                            /* ============================ Product Thumbnail ================= */

                            if (count($val->image_url) > 0) {

                                // Add Featured Image to Post
                                $image_url        = $val->image_url[0]->url; // Define the image URL here
                                $image_name       = $val->image_url[0]->name;
                                $upload_dir       = wp_upload_dir(); // Set upload folder
                                $image_data       = file_get_contents($image_url); // Get image data
                                $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                $filename         = basename($unique_file_name); // Create image file name

                                // Check folder permission and define file location
                                if (wp_mkdir_p($upload_dir['path'])) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }

                                // Create the image  file on the server
                                file_put_contents($file, $image_data);

                                // Check image file type
                                $wp_filetype = wp_check_filetype($filename, null);

                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name($filename),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                // Create the attachment
                                $attach_id = wp_insert_attachment($attachment, $file, $post_id);

                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata($attach_id, $file);

                                // Assign metadata to attachment
                                wp_update_attachment_metadata($attach_id, $attach_data);

                                // And finally assign featured image to post
                                set_post_thumbnail($post_id, $attach_id);
                            }

                            /* ============================ End Product Thumbnail ================= */


                            // Variation Process
                            $attr_label = 'Item Variations';
                            $attr_slug = sanitize_title($attr_label);

                            $attributeName = '';

                            // Remove | from last element
                            $numItems = count($val->variations);
                            $i = 0;

                            foreach($val->variations as $variationName => $variationValue){

                                $separator = (++$i == $numItems) ? '' : ' | ';

                               $attributeName .= $variationValue->name . $separator;   
                            }
                            
                            $attributes_array[$attr_slug] = array(
                                'name' => $attr_label,
                                'value' => $attributeName,
                                'is_visible' => '1',
                                'is_variation' => '1',
                                'is_taxonomy' => '0' // for some reason, this is really important       
                            );
                            update_post_meta( $post_id, '_product_attributes', $attributes_array );


                            // Start Variation Loop

                            $parent_id = $post_id;
                            $variation = array(
                                'post_title'   => $val->item_name . ' (variation)',
                                'post_content' => '',
                                'post_status'  => 'publish',
                                'post_parent'  => $parent_id,
                                'post_type'    => 'product_variation'
                            );


                            foreach($val->variations as $variationItem => $variationValue){


                                $price = 0;

                                if ($variationValue->price->amount) {

                                    $price = $variationValue->price->amount;

                                    $currency = $variationValue->price->currency;

                                    if (@$symobl[$currency]) {
                                        $price = $price / $symobl[$currency];
                                        // $price = number_format($price, count($symobl[$currency]));
                                    } else {
                                        $price = $price / 100;
                                        // $price = number_format($price, 2);
                                    }
                                }


                                $variation_id = wp_insert_post( $variation );
                                update_post_meta( $variation_id, '_regular_price', $price );
                                update_post_meta( $variation_id, '_price', $price );
                                update_post_meta($variation_id, '_sku', $variationValue->sku);
                                update_post_meta( $variation_id, '_stock_qty', 10 );
                                update_post_meta( $variation_id, 'attribute_' . $attr_slug, $variationValue->name );
                                
                                update_post_meta( $variation_id, 'by_variation', serialize($variationValue->by_variation) );


                                $v=1;

                                $qtyVSum = 0;



                                foreach($variationValue->by_variation as $by_variation_key => $by_variation_value){

                                   update_post_meta( $variation_id, 'by_variation_qty_' . $v,       $by_variation_value->qty );

                                   update_post_meta( $variation_id, 'by_variation_address_' . $v,   $by_variation_value->proper_address );

                                   update_post_meta( $variation_id, 'by_variation_lat_' . $v,       $by_variation_value->lat );

                                   update_post_meta( $variation_id, 'by_variation_long_' . $v,      $by_variation_value->long );

                                   $qtyVSum += $by_variation_value->qty;

                                    $v++;
                                }

                                update_post_meta($variation_id, '_stock',  $qtyVSum);

                                // if($qtySum > 0){
                                    update_post_meta( $variation_id, '_stock_status', 'instock');
                                // }



                                WC_Product_Variable::sync( $parent_id );

                                // Start Variation Image

                                if (count($variationValue->images) > 0) {

                                    // Add Featured Image to Post
                                    $image_url        = $variationValue->images[0]->url; // Define the image URL here
                                    $image_name       = $variationValue->images[0]->name;
                                    $upload_dir       = wp_upload_dir(); // Set upload folder
                                    $image_data       = file_get_contents($image_url); // Get image data
                                    $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                    $filename         = basename($unique_file_name); // Create image file name
    
                                    // Check folder permission and define file location
                                    if (wp_mkdir_p($upload_dir['path'])) {
                                        $file = $upload_dir['path'] . '/' . $filename;
                                    } else {
                                        $file = $upload_dir['basedir'] . '/' . $filename;
                                    }
    
                                    // Create the image  file on the server
                                    file_put_contents($file, $image_data);
    
                                    // Check image file type
                                    $wp_filetype = wp_check_filetype($filename, null);
    
                                    // Set attachment data
                                    $attachment = array(
                                        'post_mime_type' => $wp_filetype['type'],
                                        'post_title'     => sanitize_file_name($filename),
                                        'post_content'   => '',
                                        'post_status'    => 'inherit'
                                    );
    
                                    // Create the attachment
                                    $attach_id = wp_insert_attachment($attachment, $file, $variation_id);
    
                                    // Include image.php
                                    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
                                    // Define attachment metadata
                                    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    
                                    // Assign metadata to attachment
                                    wp_update_attachment_metadata($attach_id, $attach_data);
    
                                    // And finally assign featured image to post
                                    set_post_thumbnail($variation_id, $attach_id);
                                }
                                // End variation Image

                            }
                            


                            // $variation_id = wp_insert_post( $variation );
                            // update_post_meta( $variation_id, '_regular_price', 2 );
                            // update_post_meta( $variation_id, '_price', 2 );
                            // update_post_meta( $variation_id, '_stock_qty', 10 );
                            // update_post_meta( $variation_id, 'attribute_' . $attr_slug, 'alternative 2' );
                            // WC_Product_Variable::sync( $parent_id );

                            

                            echo "Not Exisists";
                        }else{

                           $post_id = $getItemID[0]->post_id;

                           wp_update_post( array(
                            'ID' => $post_id,
                            'post_author' => $userID,
                            'post_title' => $val->item_name,
                            'post_content' =>$val->item_description,
                            'post_status' => 'publish',
                            'post_type' => "product",
                            ) );

                            wp_set_object_terms( $post_id, 'variable', 'product_type' );

                            // Add Custom Attributes
                            update_post_meta($post_id, 'square_catalog_item_id', $square_catalog_item_id);

                            update_post_meta($post_id, 'variationqtys', serialize($val->variationqtys));

                            wp_set_object_terms($post_id, $val->cat_name, 'product_cat');

                            update_post_meta($post_id, '_visibility', 'visible');
                            
                            // update_post_meta($post_id, '_regular_price', $val->variations[0]->price->amount);
                            update_post_meta($post_id, '_sku', $val->variations[0]->sku);

                            update_post_meta($post_id, '_stock', $val->qtys[0]);

                            update_post_meta($post_id, 'variety', 1);

                            if (count($val->qtys) > 0) {
                                for ($i = 0; $i < count($val->qtys); $i++) {
                                    update_post_meta($post_id, 'qtys' . $i, $val->qtys[$i] ?: 0);
                                }
                            }
                            
                            $all_addr = $val->all_address;

                            // Store one address
                            if (count($all_addr) > 0) {

                                update_post_meta($post_id, "dokan_geo_address",  $all_addr[0]->address);
                                update_post_meta($post_id, "dokan_geo_latitude", $all_addr[0]->lat);
                                update_post_meta($post_id, "dokan_geo_longitude", $all_addr[0]->long);

                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'no');
                            } else {
                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'yes');
                            }

                            update_post_meta($post_id, 'upc_field', $val->variations[0]->upc != 'N/A' ? $val->variations[0]->upc : 'N/A');

                            
                            // Store All Addresses  
                        
                            if (count($all_addr) > 0) {
                                for ($i = 0; $i < count($all_addr); $i++) {
                                    update_post_meta($post_id, 'addr' . $i, $all_addr[$i]->address ?: '');
                                    update_post_meta($post_id, 'addr_latitude' . $i, $all_addr[$i]->lat ?: '');
                                    update_post_meta($post_id, 'addr_longitude' . $i, $all_addr[$i]->long ?: '');
                                }
                            }
                            
                            echo "Product ids: ".$post_id."<br>";
                            $thum_id = get_post_thumbnail_id($post_id);
                            wp_delete_attachment($thum_id);
                           
                            /* ============================ Product Thumbnail ================= */

                            if (count($val->image_url) > 0) {

                                // Add Featured Image to Post
                                $image_url        = $val->image_url[0]->url; // Define the image URL here
                                $image_name       = $val->image_url[0]->name;
                                $upload_dir       = wp_upload_dir(); // Set upload folder
                                $image_data       = file_get_contents($image_url); // Get image data
                                $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                $filename         = basename($unique_file_name); // Create image file name

                                // Check folder permission and define file location
                                if (wp_mkdir_p($upload_dir['path'])) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }

                                // Create the image  file on the server
                                file_put_contents($file, $image_data);

                                // Check image file type
                                $wp_filetype = wp_check_filetype($filename, null);

                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name($filename),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                // Create the attachment
                                $attach_id = wp_insert_attachment($attachment, $file, $post_id);

                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata($attach_id, $file);

                                // Assign metadata to attachment
                                wp_update_attachment_metadata($attach_id, $attach_data);

                                // And finally assign featured image to post
                                set_post_thumbnail($post_id, $attach_id);
                            }

                            /* ============================ End Product Thumbnail ================= */

                            // Variation Process
                            $attr_label = 'Item Variations';
                            $attr_slug = sanitize_title($attr_label);

                            $attributeName = '';

                            // Remove | from last element
                            $numItems = count($val->variations);
                            $i = 0;

                            foreach($val->variations as $variationName => $variationValue){

                                $separator = (++$i == $numItems) ? '' : ' | ';
                        
                               $attributeName .= $variationValue->name . $separator;   
                            }

                            $attributes_array[$attr_slug] = array(
                                'name' => $attr_label,
                                'value' => $attributeName,
                                'is_visible' => '1',
                                'is_variation' => '1',
                                'is_taxonomy' => '0' // for some reason, this is really important       
                            );
                            update_post_meta( $post_id, '_product_attributes', $attributes_array );

                            // Start Variation Loop
                            $parent_id = $post_id;
                            


                            $product = wc_get_product($post_id);
                            $variations = $product->get_available_variations();
                            $exists_variations_id = wp_list_pluck( $variations, 'variation_id' );
                            
                            echo "The variable ID's";
                            print_r($variations_id);

                            // if(count($exists_variations_id) == count($val->variations)){
                            //     // Update Variations

                            //     $variation = array(
                            //         'post_title'   => $val->item_name . ' (variation)',
                            //         'post_content' => '',
                            //         'post_status'  => 'publish',
                            //         'post_parent'  => $parent_id,
                            //         'post_type'    => 'product_variation'
                            //     );


                            // }else{

                                // Delete Variations
                                if(count( $exists_variations_id)>0){
                                    foreach($exists_variations_id as $exists_variation_id){

                                        $thum_id = get_post_thumbnail_id($exists_variation_id);

                                        wp_delete_attachment($thum_id);

                                        // wp_delete_attachment($exists_variations_id);
                                        wp_delete_post($exists_variation_id, true);
                                    }
                                }


                                // Insert Variations
                                $variation = array(
                                    'post_title'   => $val->item_name . ' (variation)',
                                    'post_content' => '',
                                    'post_status'  => 'publish',
                                    'post_parent'  => $parent_id,
                                    'post_type'    => 'product_variation'
                                );
                                
                                $qtySum = 0;

                                foreach($val->variations as $variationItem => $variationValue){


                                    $price = 0;

                                    if ($variationValue->price->amount) {

                                        $price = $variationValue->price->amount;

                                        $currency = $variationValue->price->currency;

                                        if (@$symobl[$currency]) {
                                            $price = $price / $symobl[$currency];
                                            // $price = number_format($price, count($symobl[$currency]));
                                        } else {
                                            $price = $price / 100;
                                            // $price = number_format($price, 2);
                                        }
                                    }

                                    echo $variation_id = wp_insert_post( $variation );
                                    echo "<br/>";
                                    update_post_meta( $variation_id, '_regular_price', $price );
                                    update_post_meta( $variation_id, '_price', $price );
                                    update_post_meta($variation_id, '_sku', $variationValue->sku);
                                    update_post_meta( $variation_id, '_stock_qty', 10 );
                                    update_post_meta( $variation_id, 'attribute_' . $attr_slug, $variationValue->name );

                                    update_post_meta( $variation_id, 'by_variation', serialize($variationValue->by_variation) );

                                    $v=1;

                                    $qtyVSum = 0;



                                    foreach($variationValue->by_variation as $by_variation_key => $by_variation_value){

                                       update_post_meta( $variation_id, 'by_variation_qty_' . $v,       $by_variation_value->qty );

                                       update_post_meta( $variation_id, 'by_variation_address_' . $v,   $by_variation_value->proper_address );

                                       update_post_meta( $variation_id, 'by_variation_lat_' . $v,       $by_variation_value->lat );

                                       update_post_meta( $variation_id, 'by_variation_long_' . $v,      $by_variation_value->long );

                                       $qtyVSum += $by_variation_value->qty;

                                        $v++;
                                    }

                                    $qtySum += $qtyVSum;

                                    update_post_meta($variation_id, '_stock',  $qtyVSum);

                                    // if($qtySum > 0){
                                        update_post_meta( $variation_id, '_stock_status', 'instock');
                                    // }

                                    WC_Product_Variable::sync( $parent_id );

                                    // Start Variation Image

                                    if (count($variationValue->images) > 0) {

                                        // Add Featured Image to Post
                                        $image_url        = $variationValue->images[0]->url; // Define the image URL here
                                        $image_name       = $variationValue->images[0]->name;
                                        $upload_dir       = wp_upload_dir(); // Set upload folder
                                        $image_data       = file_get_contents($image_url); // Get image data
                                        $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                        $filename         = basename($unique_file_name); // Create image file name

                                        // Check folder permission and define file location
                                        if (wp_mkdir_p($upload_dir['path'])) {
                                            $file = $upload_dir['path'] . '/' . $filename;
                                        } else {
                                            $file = $upload_dir['basedir'] . '/' . $filename;
                                        }

                                        // Create the image  file on the server
                                        file_put_contents($file, $image_data);

                                        // Check image file type
                                        $wp_filetype = wp_check_filetype($filename, null);

                                        // Set attachment data
                                        $attachment = array(
                                            'post_mime_type' => $wp_filetype['type'],
                                            'post_title'     => sanitize_file_name($filename),
                                            'post_content'   => '',
                                            'post_status'    => 'inherit'
                                        );

                                        // Create the attachment
                                        $attach_id = wp_insert_attachment($attachment, $file, $variation_id);

                                        // Include image.php
                                        require_once(ABSPATH . 'wp-admin/includes/image.php');

                                        // Define attachment metadata
                                        $attach_data = wp_generate_attachment_metadata($attach_id, $file);

                                        // Assign metadata to attachment
                                        wp_update_attachment_metadata($attach_id, $attach_data);

                                        // And finally assign featured image to post
                                        set_post_thumbnail($variation_id, $attach_id);
                                    }
                                    // End variation Image

                                }


                            // }

                            if($qtySum > 0){

                                update_post_meta($post_id, '_stock', $qtySum);

                                update_post_meta($post_id, '_manage_stock', 'yes');

                                update_post_meta($post_id, '_stock_status', 'instock');

                            }

                            echo "Exists";
                        }
                        // echo "Multi Variant--------!";

                    }
                    else{

                        $price = 0;

                        if ($val->variation_price->amount) {

                            $price = $val->variation_price->amount;

                            $currency = $val->variation_price->currency;

                            if (@$symobl[$currency]) {
                                $price = $price / $symobl[$currency];
                                // $price = number_format($price, count($symobl[$currency]));
                            } else {
                                $price = $price / 100;
                                // $price = number_format($price, 2);
                            }
                        }


                        $square_catalog_item_id = $val->item_id;

                        $getItemID = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE `meta_key` = 'square_catalog_item_id' AND `meta_value` = '$square_catalog_item_id'");

                        if (count($getItemID) == 0) {


                            $price = $val->variation_price->amount ? $price : '';

                            $post = array(
                                'post_author' => $userID,
                                'post_content' => $val->item_description,
                                'post_status' => "publish",
                                'post_title' => $val->item_name,
                                'post_parent' => '',
                                'post_type' => "product",
                            );

                            //Create post
                            $post_id = wp_insert_post($post, $wp_error);


                            update_post_meta($post_id, 'square_catalog_item_id', $square_catalog_item_id);

                            wp_set_object_terms($post_id, $val->cat_name, 'product_cat');
                            wp_set_object_terms($post_id, 'simple', 'product_type');

                            update_post_meta($post_id, '_visibility', 'visible');
                            update_post_meta($post_id, '_stock_status', 'instock');
                            update_post_meta($post_id, '_regular_price', $price);
                            //update_post_meta( $post_id, '_sale_price', $price );
                            update_post_meta($post_id, '_sku', $val->variation_SKU);
                            // update_post_meta( $post_id, '_price', $price );
                            update_post_meta($post_id, '_stock', $val->quantity);

                            update_post_meta($post_id, 'variety', 0);


                            if (count($val->qtys) > 0) {
                                for ($i = 0; $i < count($val->qtys); $i++) {
                                    update_post_meta($post_id, 'qtys' . $i, $val->qtys[$i] ?: 0);
                                }
                            }


                            if ($variation_location_id) {

                                update_post_meta($post_id, "dokan_geo_address", $val->proper_address);
                                update_post_meta($post_id, "dokan_geo_latitude", $val->add_lat);
                                update_post_meta($post_id, "dokan_geo_longitude", $val->add_long);

                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'no');
                            } else {
                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'yes');
                            }



                            update_post_meta($post_id, 'upc_field', $val->variation_UPC ? $val->variation_UPC : '');

                            $all_addr = $val->all_address;

                            if (count($all_addr) > 0) {
                                for ($i = 0; $i < count($all_addr); $i++) {
                                    update_post_meta($post_id, 'addr' . $i, $all_addr[$i]->address ?: '');
                                    update_post_meta($post_id, 'addr_latitude' . $i, $all_addr[$i]->lat ?: '');
                                    update_post_meta($post_id, 'addr_longitude' . $i, $all_addr[$i]->long ?: '');
                                }
                            }


                            if (count($val->image_url) > 0) {

                                // Add Featured Image to Post
                                $image_url        = $val->image_url[0]->url; // Define the image URL here
                                $image_name       = $val->image_url[0]->name;
                                $upload_dir       = wp_upload_dir(); // Set upload folder
                                $image_data       = file_get_contents($image_url); // Get image data
                                $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                $filename         = basename($unique_file_name); // Create image file name

                                // Check folder permission and define file location
                                if (wp_mkdir_p($upload_dir['path'])) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }

                                // Create the image  file on the server
                                file_put_contents($file, $image_data);

                                // Check image file type
                                $wp_filetype = wp_check_filetype($filename, null);

                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name($filename),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                // Create the attachment
                                $attach_id = wp_insert_attachment($attachment, $file, $post_id);

                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata($attach_id, $file);

                                // Assign metadata to attachment
                                wp_update_attachment_metadata($attach_id, $attach_data);

                                // And finally assign featured image to post
                                set_post_thumbnail($post_id, $attach_id);
                            }

                            // echo 'Simple Procudt post_id:' . $post_id . '<br>';

                        } else {

                            $post_id = $getItemID[0]->post_id;

                            $price = $val->variation_price->amount ? $price : '';

                            wp_update_post( array(
                                'ID' => $post_id,
                                'post_author' => $userID,
                                'post_title' => $val->item_name,
                                'post_content' =>$val->item_description,
                                'post_status' => 'publish',
                                'post_type' => "product",
                            ) );

                            // echo 'Simple Procudt post_id:' . $post_id . '<br>';

                        //     $thum_id = get_post_thumbnail_id($post_id);

                        //    // wp_delete_attachment($thum_id);


                            update_post_meta($post_id, 'square_catalog_item_id', $square_catalog_item_id);

                            wp_set_object_terms($post_id, $val->cat_name, 'product_cat');
                            wp_set_object_terms($post_id, 'simple', 'product_type');

                            update_post_meta($post_id, '_visibility', 'visible');
                            update_post_meta($post_id, '_stock_status', 'instock');
                            update_post_meta($post_id, '_regular_price', $price);
                            //update_post_meta( $post_id, '_sale_price', $price );
                            update_post_meta($post_id, '_sku', $val->variation_SKU);
                            // update_post_meta( $post_id, '_price', $price );
                            update_post_meta($post_id, '_stock', $val->quantity);

                            update_post_meta($post_id, 'variety', 0);

                            if (count($val->qtys) > 0) {
                                for ($i = 0; $i < count($val->qtys); $i++) {
                                    update_post_meta($post_id, 'qtys' . $i, $val->qtys[$i] ?: 0);
                                }
                            }

                            if ($variation_location_id) {

                                update_post_meta($post_id, "dokan_geo_address", $val->proper_address);
                                update_post_meta($post_id, "dokan_geo_latitude", $val->add_lat);
                                update_post_meta($post_id, "dokan_geo_longitude", $val->add_long);
                            
                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'no');
                            } else {
                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'yes');
                            }

                            update_post_meta($post_id, 'upc_field', $val->variation_UPC ? $val->variation_UPC : '');

                            $all_addr = $val->all_address;

                            if (count($all_addr) > 0) {
                                for ($i = 0; $i < count($all_addr); $i++) {
                                    update_post_meta($post_id, 'addr' . $i, $all_addr[$i]->address ?: '');
                                    update_post_meta($post_id, 'addr_latitude' . $i, $all_addr[$i]->lat ?: '');
                                    update_post_meta($post_id, 'addr_longitude' . $i, $all_addr[$i]->long ?: '');
                                }
                            }

                            if (count($val->image_url) > 0) {

                                // Add Featured Image to Post
                                $image_url        = $val->image_url[0]->url; // Define the image URL here
                                $image_name       = $val->image_url[0]->name;
                                $upload_dir       = wp_upload_dir(); // Set upload folder
                                $image_data       = file_get_contents($image_url); // Get image data
                                $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                $filename         = basename($unique_file_name); // Create image file name
                            
                                // Check folder permission and define file location
                                if (wp_mkdir_p($upload_dir['path'])) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }
                            
                                // Create the image  file on the server
                                file_put_contents($file, $image_data);
                            
                                // Check image file type
                                $wp_filetype = wp_check_filetype($filename, null);
                            
                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name($filename),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );
                            
                                // Create the attachment
                                $attach_id = wp_insert_attachment($attachment, $file, $post_id);
                            
                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');
                            
                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata($attach_id, $file);
                            
                                // Assign metadata to attachment
                                wp_update_attachment_metadata($attach_id, $attach_data);
                            
                                // And finally assign featured image to post
                                set_post_thumbnail($post_id, $attach_id);
                            }
                        }

                    }

                    
                }
                // print_r($output);
            }

            // echo json_encode($output);

            print_r($output);
            // print_r($json_dec->objects);
            // print_r($json_dec->objects[0]->item_data->variations[0]->item_variation_data->location_overrides[0]->location_id);

        } else {
            $errors = $catalog_response->getErrors();
        }
    }

    die();
}




// add_action('init','cron_pos_square_pos_import');

// cron_pos_square_pos_import();
function cron_pos_square_pos_import()
{


    global $wpdb;

    $useQuery = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pos_creds");

    foreach($useQuery as $users){
        $userID =  $users->user_id;
    // }


    // if (is_user_logged_in()) {

        // $userID =  get_current_user_id();

        // foreach($){

        // }

        $getUser = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pos_creds WHERE user_id = $userID LIMIT 1");

        $square_access_token = $getUser[0]->access_token;

        $client = new SquareClient([
            'accessToken' => $square_access_token,
            'environment' => Environment::PRODUCTION,
        ]);



        $catalog_response = $client->getCatalogApi()->listCatalog(null, 'ITEM');

        if ($catalog_response->isSuccess()) {
            $result = $catalog_response->getResult();
            $josn_enc =  json_encode($result);
            $json_dec = json_decode($josn_enc);
            $json_decObjects = $json_dec->objects;

            // print_r($json_decObjects);

            $output = [];

            foreach ($json_decObjects as $key => $value) {

                // For Variable Products
                if (count($value->item_data->variations) > 1) {

                    $item_id = $value->id;

                    $item_name = $value->item_data->name;

                    $item_description = $value->item_data->description;


                    $category_id = $value->item_data->category_id;

                    // Category Fetched
                    if ($category_id) {

                        $catobject_ids = [$category_id];
                        $catbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($catobject_ids);
                        $catbody->setIncludeRelatedObjects(false);

                        $catapi_responses = $client->getCatalogApi()->batchRetrieveCatalogObjects($catbody);

                        if ($catapi_responses->isSuccess()) {

                            $catresults = $catapi_responses->getResult();

                            $cat_decoded = json_decode(json_encode($catresults));

                            $cat_name = $cat_decoded->objects[0]->category_data->name;
                        } else {
                            $errors = $catapi_responses->getErrors();
                        }
                    }



                    // Retrive Images

                    $img_ids = $value->item_data->image_ids;

                    if ($img_ids) {

                        $imgobject_ids = [];

                        foreach ($img_ids as $img_id) {

                            array_push($imgobject_ids, $img_id);
                        }

                        $imgbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($imgobject_ids);
                        $imgbody->setIncludeRelatedObjects(false);

                        $imgapi_response = $client->getCatalogApi()->batchRetrieveCatalogObjects($imgbody);

                        if ($imgapi_response->isSuccess()) {
                            $imgresult = $imgapi_response->getResult();

                            $img_decoded = json_decode(json_encode($imgresult));

                            $imgObj = $img_decoded->objects;

                            $img_array = [];

                            foreach ($imgObj as $img) {


                                // print_r($img->image_data);

                                $img_array[] = (object)['name' => $img->image_data->name, 'url' => $img->image_data->url];
                            }

                            // print_r($img_decoded->objects[0]->image_data->url);

                        } else {
                            $errors = $imgapi_response->getErrors();
                        }
                    }


                    // Inventory Count Against Multiple Locations

                    $qtys = [];

                    if (@count($value->present_at_location_ids) > 0 || $value->present_at_all_locations == 1) {

                        if ($value->present_at_all_locations == 1) {
                            $variation_location_idss = [];

                            $alll_locations_api_response = $client->getLocationsApi()->listLocations();

                            if ($alll_locations_api_response->isSuccess()) {
                                $result = $alll_locations_api_response->getResult();


                                $all_locationsArray = json_decode(json_encode($result));

                                $all_locationsArray = $all_locationsArray->locations;

                                foreach ($all_locationsArray as $allLocation => $allLocationValue) {
                                    array_push($variation_location_idss, $allLocationValue->id);
                                }

                                // print_r($variation_location_idss);

                                // exit;

                            } else {
                                $errors = $alll_locations_api_response->getErrors();
                            }

                            //$variation_location_idss = $value->present_at_location_ids;

                        } else {

                            $variation_location_idss = $value->present_at_location_ids;
                        }


                        // print_r($variation_location_idss);

                        if (count($variation_location_idss) > 0) {

                            $i = 0;

                            foreach ($variation_location_idss as $vli => $val_inv) {

                                // print_r($val_inv);
                                // echo "<br>";
                                // echo $i;


                                $inv_catalog_object_idss = [$value->item_data->variations[$i]->id];

                                $inv_location_idss = [$val_inv];


                                $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                                $inv_body->setCatalogObjectIds($inv_catalog_object_idss);
                                $inv_body->setLocationIds($inv_location_idss);

                                $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                                if ($inv_api_response->isSuccess()) {
                                    $inv_result = $inv_api_response->getResult();

                                    $inv_decoded = json_decode(json_encode($inv_result));

                                    // print_r($inv_decoded);

                                    $qty = $inv_decoded->counts[0]->quantity;
                                    // print_r($qty);

                                    $qtys[] = $qty;
                                } else {
                                    $errors = $inv_api_response->getErrors();
                                }


                                $i++;
                            }
                        }

                        // exit;


                    }

                    // print_r($qtys);

                    // exit;



                    // Multiple Locations

                    $all_address = [];

                    if (@count($value->present_at_location_ids) > 0 || $value->present_at_all_locations == 1) {

                        if($value->present_at_all_locations == 1){

                            $variation_location_idss = [];

                            $alll_locations_api_response = $client->getLocationsApi()->listLocations();

                            if ($alll_locations_api_response->isSuccess()) {

                                $result = $alll_locations_api_response->getResult();


                                $all_locationsArray = json_decode(json_encode($result));

                                $all_locationsArray = $all_locationsArray->locations;


                                foreach ($all_locationsArray as $allLocation => $allLocationValue) {

                                    $address_line_1 = $allLocationValue->address->address_line_1 ?: '';
                                    $address_line_2 = $allLocationValue->address->address_line_2 ?: '';
                                    $locality = $allLocationValue->address->locality ?: '';

                                    $all_proper_address = $address_line_1 . ', ' . $address_line_2 . ', ' . $locality;

                                    $all_add_lat = $allLocationValue->coordinates->latitude ?: '';
                                    $all_add_long = $allLocationValue->coordinates->longitude ?: '';

                                    array_push($all_address, (object)['address' => $all_proper_address, 'lat' => $all_add_lat, 'long' => $all_add_long]);

                                }

                                // print_r($all_locationsArray);

                                // foreach ($all_locationsArray as $allLocation => $allLocationValue) {
                                //     array_push($variation_location_idss, $allLocationValue->id);
                                // }

                                // print_r($variation_location_idss);

                                // exit;

                            } else {
                                $errors = $alll_locations_api_response->getErrors();
                            }


                        }else{

                            $mul_locations = $value->present_at_location_ids;

                            // print_r($mul_locations);

                            for ($i = 0; $i < count($mul_locations); $i++) {
                                // echo $mul_locations[$i];


                                $location_api_response = $client->getLocationsApi()->retrieveLocation($mul_locations[$i]);

                                if ($location_api_response->isSuccess()) {
                                    $locationResult = $location_api_response->getResult();

                                    $locationResult = json_decode(json_encode($locationResult));

                                    // print_r($locationResult);

                                    $addressline = $locationResult->location->address->address_line_1 ?: '';
                                    $locality = $locationResult->location->address->locality ?: '';
                                    $administrative_district_level_1 = $locationResult->location->address->administrative_district_level_1 ?: '';

                                    $all_proper_address = $addressline . ', ' . $locality . ', ' . $administrative_district_level_1;

                                    $all_add_lat = $locationResult->location->coordinates->latitude ?: '';
                                    $all_add_long = $locationResult->location->coordinates->longitude ?: '';

                                    array_push($all_address, (object)['address' => $all_proper_address, 'lat' => $all_add_lat, 'long' => $all_add_long]);
                                } else {
                                    $errors = $location_api_response->getErrors();
                                }
                            }

                        }


                    }


                    $variations = [];
                    $variationsArray = $value->item_data->variations;
                    $variationqtys = [];

                    foreach($variationsArray as $variationItem => $variationItemValue){

                        $vName  =  $variationItemValue->item_variation_data->name?:'N/A';
                        $vSku   =  $variationItemValue->item_variation_data->sku?:'N/A';
                        $vPrice =  $variationItemValue->item_variation_data->price_money;
                        $vUpc   =  @$variationItemValue->item_variation_data->upc ?:'N/A';



                        // Inventory Count Against Multiple Locations

                    if (count($variationItemValue->item_variation_data->location_overrides) > 0 || $variationItemValue->present_at_all_locations == 1) {

                        // if ($value->present_at_all_locations == 1) {
                        //     $variation_location_idss = [];

                        //     $alll_locations_api_response = $client->getLocationsApi()->listLocations();

                        //     if ($alll_locations_api_response->isSuccess()) {
                        //         $result = $alll_locations_api_response->getResult();


                        //         $all_locationsArray = json_decode(json_encode($result));

                        //         $all_locationsArray = $all_locationsArray->locations;

                        //         foreach ($all_locationsArray as $allLocation => $allLocationValue) {
                        //             array_push($variation_location_idss, $allLocationValue->id);
                        //         }

                        //         // print_r($variation_location_idss);

                        //         // exit;

                        //     } else {
                        //         $errors = $alll_locations_api_response->getErrors();
                        //     }

                        //     //$variation_location_idss = $value->present_at_location_ids;

                        // } else {

                            $variation_location_idss = $variationItemValue->item_variation_data->location_overrides;
                        // }


                        // print_r($variation_location_idss);

                        if (count($variation_location_idss) > 0) {

                            $byVariationData =  [];

                            $i = 0;

                            foreach ($variation_location_idss as $vli => $val_inv) {

                                // print_r($val_inv);
                                // echo "<br>";
                                // echo $i;


                                $inv_catalog_object_idss = [ $variationItemValue->id];

                                $inv_location_idss = [$val_inv->location_id];


                                // ------------ Locations info ------------
                                $var_location_api_response = $client->getLocationsApi()->retrieveLocation($val_inv->location_id);

                                if ($var_location_api_response->isSuccess()) {

                                    $vari_locationResult = $var_location_api_response->getResult();
                                    $vari_locationResult = json_decode(json_encode($vari_locationResult));
                                    $vari_locationResult = $vari_locationResult->location;

                                    $vari_address_line_1 = $vari_locationResult->address->address_line_1 ?: '';
                                    $vari_address_line_2 = $vari_locationResult->address->address_line_2 ?: '';
                                    $vari_locality = $vari_locationResult->address->locality ?: '';

                                    $vari_all_proper_address = $vari_address_line_1 . ', ' . $vari_address_line_2 . ', ' . $vari_locality;

                                    $vari_all_add_lat = $vari_locationResult->coordinates->latitude ?: '';
                                    $vari_all_add_long = $vari_locationResult->coordinates->longitude ?: '';

                                    // print_r($vari_locationResult);

                                } else {
                                    $errors = $var_location_api_response->getErrors();
                                }


                                // ------------ End Locations info -----------

                                $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                                $inv_body->setCatalogObjectIds($inv_catalog_object_idss);
                                $inv_body->setLocationIds($inv_location_idss);

                                $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                                if ($inv_api_response->isSuccess()) {
                                    $inv_result = $inv_api_response->getResult();

                                    $inv_decoded = json_decode(json_encode($inv_result));

                                    // print_r($inv_decoded);

                                    $qty = $inv_decoded->counts[0]->quantity;
                                    // print_r($qty);

                                    $variationqtys[] = (object)['name'=> $vName, 'qty' => $qty, 'proper_address' => $vari_all_proper_address, 'lat' => $vari_all_add_lat, 'long' => $vari_all_add_long];
                                    $byVariationData[] = (object)['qty' => $qty, 'proper_address' => $vari_all_proper_address, 'lat' => $vari_all_add_lat, 'long' => $vari_all_add_long];
                                } else {
                                    $errors = $inv_api_response->getErrors();
                                }


                                $i++;
                            }
                        }

                        // exit;


                    }


                        // Retrive Images

                        $img_idss = @$variationItemValue->item_variation_data->image_ids;

                        if ($img_idss) {

                            $imgobject_ids = [];

                            foreach ($img_idss as $img_id) {

                                array_push($imgobject_ids, $img_id);
                            }

                            $imgbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($imgobject_ids);
                            $imgbody->setIncludeRelatedObjects(false);

                            $imgapi_response = $client->getCatalogApi()->batchRetrieveCatalogObjects($imgbody);

                            if ($imgapi_response->isSuccess()) {
                                $imgresult = $imgapi_response->getResult();

                                $img_decoded = json_decode(json_encode($imgresult));

                                $imgObj = $img_decoded->objects;

                                $imgs_array = [];

                                foreach ($imgObj as $img) {

                                    // print_r($img->image_data);

                                    $imgs_array[] = (object)['name' => $img->image_data->name?:'N/A', 'url' => $img->image_data->url?:'N/A'];
                                }

                                // print_r($img_decoded->objects[0]->image_data->url);

                            } else {
                                $errors = $imgapi_response->getErrors();
                            }
                        }

                        array_push($variations, (object)['name' => $vName, 'sku' => $vSku, 'price' => $vPrice, 'upc' => $vUpc, 'images' => $img_idss ? $img_array : [], "by_variation"=> $byVariationData ]);

                    }

                    // print_r($variations);
                    // exit;

                    array_push(
                        $output,
                        (object)[
                            'type'                  => $value->type,
                            'item_id'               => $item_id,
                            'item_name'             => $item_name,
                            'item_description'      => $item_description,
                            'cat_name'              => $category_id ? $cat_name : '',
                            // 'variation_SKU'         =>  $variation_SKU ? $variation_SKU : '',
                            //'variation_UPC'         =>  $variation_UPC ? $variation_UPC : '',
                            //'quantity'              => ($variation_ids && $variation_location_id) ? $quantity : '',
                            // 'variation_price'       =>  $variation_price ?: '',
                            'image_url'             =>  $img_ids ? $img_array : [],
                            //'proper_address'        =>  $proper_address ? $proper_address : '',
                            //'add_lat'               =>  $add_lat ? $add_lat : '',
                            //'add_long'              =>  $add_long ? $add_long : '',
                            'all_address'           =>  $all_address ? $all_address : [],
                            'qtys'                  =>  $qtys ? $qtys : [],
                            'variations'            =>  $variations ? $variations : [],
                            'multi_variation'       =>  true,
                            'variationqtys'         =>  $variationqtys,
                        ]
                    );

                   
                }
                else{

                // Single Variation

                // }

                // print_r($value->type);

                $item_id = $value->id;

                $item_name = $value->item_data->name;

                $item_description = $value->item_data->description;


                $category_id = $value->item_data->category_id;

                // print_r($item_name);

                // Category Fetched
                if ($category_id) {

                    $catobject_ids = [$category_id];
                    $catbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($catobject_ids);
                    $catbody->setIncludeRelatedObjects(false);

                    $catapi_responses = $client->getCatalogApi()->batchRetrieveCatalogObjects($catbody);

                    if ($catapi_responses->isSuccess()) {

                        $catresults = $catapi_responses->getResult();

                        $cat_decoded = json_decode(json_encode($catresults));

                        $cat_name = $cat_decoded->objects[0]->category_data->name;
                    } else {
                        $errors = $catapi_responses->getErrors();
                    }
                }


                // Retrive Images

                $img_ids = $value->item_data->image_ids;

                if ($img_ids) {

                    $imgobject_ids = [];

                    foreach ($img_ids as $img_id) {

                        array_push($imgobject_ids, $img_id);
                    }

                    $imgbody = new \Square\Models\BatchRetrieveCatalogObjectsRequest($imgobject_ids);
                    $imgbody->setIncludeRelatedObjects(false);

                    $imgapi_response = $client->getCatalogApi()->batchRetrieveCatalogObjects($imgbody);

                    if ($imgapi_response->isSuccess()) {
                        $imgresult = $imgapi_response->getResult();

                        $img_decoded = json_decode(json_encode($imgresult));

                        $imgObj = $img_decoded->objects;

                        $img_array = [];

                        foreach ($imgObj as $img) {


                            // print_r($img->image_data);

                            $img_array[] = (object)['name' => $img->image_data->name, 'url' => $img->image_data->url];
                        }

                        // print_r($img_decoded->objects[0]->image_data->url);

                    } else {
                        $errors = $imgapi_response->getErrors();
                    }
                }

                // Inventory Count

                $variation_ids = $value->item_data->variations[0]->id;

                $variation_location_id = $value->item_data->variations[0]->item_variation_data->location_overrides[0]->location_id;

                $variation_SKU = $value->item_data->variations[0]->item_variation_data->sku;

                $variation_UPC = $value->item_data->variations[0]->item_variation_data->upc;

                $variation_price = $value->item_data->variations[0]->item_variation_data->price_money;

                // print_r($variation_location_id);


                if ($variation_ids && $variation_location_id) {

                    $inv_catalog_object_ids = [$variation_ids];
                    $inv_location_ids = [$variation_location_id];
                    $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                    $inv_body->setCatalogObjectIds($inv_catalog_object_ids);
                    $inv_body->setLocationIds($inv_location_ids);

                    $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                    if ($inv_api_response->isSuccess()) {
                        $inv_result = $inv_api_response->getResult();

                        $inv_decoded = json_decode(json_encode($inv_result));

                        $quantity = $inv_decoded->counts[0]->quantity;
                    } else {
                        $errors = $inv_api_response->getErrors();
                    }
                }


                // Inventory Count Against Multiple Locations

                $qtys = [];

                if (count($value->present_at_location_ids) > 0) {

                    $variation_location_idss = $value->present_at_location_ids;

                    // print_r($variation_location_idss);

                    if (count($variation_location_idss) > 0) {

                        foreach ($variation_location_idss as $vli => $val_inv) {

                            $inv_catalog_object_idss = [$variation_ids];

                            $inv_location_idss = [$val_inv];


                            $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                            $inv_body->setCatalogObjectIds($inv_catalog_object_idss);
                            $inv_body->setLocationIds($inv_location_idss);

                            $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                            if ($inv_api_response->isSuccess()) {
                                $inv_result = $inv_api_response->getResult();

                                $inv_decoded = json_decode(json_encode($inv_result));

                                // print_r($inv_decoded);

                                $qty = $inv_decoded->counts[0]->quantity;
                                // print_r($qty);

                                $qtys[] = $qty;
                            } else {
                                $errors = $inv_api_response->getErrors();
                            }
                        }
                    }
                }

                // print_r($qtys);



                // if($variation_ids && $variation_location_id){

                //     $inv_catalog_object_ids = [$variation_ids];
                //     $inv_location_ids = [$variation_location_id];
                //     $inv_body = new \Square\Models\BatchRetrieveInventoryCountsRequest();
                //     $inv_body->setCatalogObjectIds($inv_catalog_object_ids);
                //     $inv_body->setLocationIds($inv_location_ids);

                //     $inv_api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($inv_body);

                //     if ($inv_api_response->isSuccess()) {
                //         $inv_result = $inv_api_response->getResult();

                //         $inv_decoded = json_decode(json_encode($inv_result));

                //         $quantity = $inv_decoded->counts[0]->quantity;

                //     } else {
                //         $errors = $inv_api_response->getErrors();
                //     }
                // }


                if ($variation_location_id) {

                    $location_api_response = $client->getLocationsApi()->retrieveLocation($variation_location_id);

                    if ($location_api_response->isSuccess()) {
                        $locationResult = $location_api_response->getResult();

                        $locationResult = json_decode(json_encode($locationResult));

                        // print_r($locationResult);

                        $addressline = $locationResult->location->address->address_line_1 ?: '';
                        $locality = $locationResult->location->address->locality ?: '';
                        $administrative_district_level_1 = $locationResult->location->address->administrative_district_level_1 ?: '';

                        $proper_address = $addressline . ', ' . $locality . ', ' . $administrative_district_level_1;

                        $add_lat = $locationResult->location->coordinates->latitude ?: '';
                        $add_long = $locationResult->location->coordinates->longitude ?: '';
                    } else {
                        $errors = $location_api_response->getErrors();
                    }
                }


                // Multiple Locations

                $all_address = [];

                if (count($value->present_at_location_ids) > 0) {

                    $mul_locations = $value->present_at_location_ids;

                    // print_r($mul_locations);

                    for ($i = 0; $i < count($mul_locations); $i++) {
                        // echo $mul_locations[$i];


                        $location_api_response = $client->getLocationsApi()->retrieveLocation($mul_locations[$i]);

                        if ($location_api_response->isSuccess()) {
                            $locationResult = $location_api_response->getResult();

                            $locationResult = json_decode(json_encode($locationResult));

                            // print_r($locationResult);

                            $addressline = $locationResult->location->address->address_line_1 ?: '';
                            $locality = $locationResult->location->address->locality ?: '';
                            $administrative_district_level_1 = $locationResult->location->address->administrative_district_level_1 ?: '';

                            $all_proper_address = $addressline . ', ' . $locality . ', ' . $administrative_district_level_1;

                            $all_add_lat = $locationResult->location->coordinates->latitude ?: '';
                            $all_add_long = $locationResult->location->coordinates->longitude ?: '';

                            array_push($all_address, (object)['address' => $all_proper_address, 'lat' => $all_add_lat, 'long' => $all_add_long]);
                        } else {
                            $errors = $location_api_response->getErrors();
                        }
                    }
                }


                array_push(
                    $output,
                    (object)[
                        'type'                  => $value->type,
                        'item_id'               => $item_id,
                        'item_name'             => $item_name,
                        'item_description'      => $item_description,
                        'cat_name'              => $category_id ? $cat_name : '',
                        'variation_SKU'         =>  $variation_SKU ? $variation_SKU : '',
                        'variation_UPC'         =>  $variation_UPC ? $variation_UPC : '',
                        'quantity'              => ($variation_ids && $variation_location_id) ? $quantity : '',
                        'variation_price'       =>  $variation_price ?: '',
                        'image_url'             =>  $img_ids ? $img_array : [],
                        'proper_address'        =>  $proper_address ? $proper_address : '',
                        'add_lat'               =>  $add_lat ? $add_lat : '',
                        'add_long'              =>  $add_long ? $add_long : '',
                        'all_address'           =>  $all_address ? $all_address : [],
                        'qtys'                  =>  $qtys ? $qtys : [],
                        'multi_variation'       =>  false,
                    ]
                );

              }
            }

            if (count($output) > 0 ) {

                $symobl = [
                    'BHD'       =>     1000,
                    'CNY'        =>     10,
                    'IRR'        =>    1,
                    'IQD'        =>    1000,
                    'KWD'       =>    1000,
                    'LYD'       =>    1000,
                    'MGA'       =>    5,
                    'MRU'       =>  5,
                    'OMR'       =>  1000,
                    'MRU'       =>  5,
                    'TND'       =>  1000,
                    'VND'       =>  10,
                ];



                foreach ($output as $item_a => $val) {

                    if($val->multi_variation == 1){

                        $square_catalog_item_id = $val->item_id;

                        $getItemID = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE `meta_key` = 'square_catalog_item_id' AND `meta_value` = '$square_catalog_item_id'");

                        if (count($getItemID) == 0) {

                            // $article_name = 'Test';

                            $post_id = wp_insert_post( array(
                                'post_author' => $userID,
                                'post_title' => $val->item_name,
                                'post_content' =>$val->item_description,
                                'post_status' => 'publish',
                                'post_type' => "product",
                            ) );

                            wp_set_object_terms( $post_id, 'variable', 'product_type' );


                            // Add Custom Attributes
                            update_post_meta($post_id, 'square_catalog_item_id', $square_catalog_item_id);

                            wp_set_object_terms($post_id, $val->cat_name, 'product_cat');

                            update_post_meta($post_id, '_visibility', 'visible');
                            update_post_meta($post_id, '_stock_status', 'instock');

                            update_post_meta($post_id, '_regular_price', $val->variations[0]->price->amount);
                            update_post_meta($post_id, '_sku', $val->variations[0]->sku);

                            update_post_meta($post_id, '_stock', $val->qtys[0]);

                            update_post_meta($post_id, 'variety', 1);


                            if (count($val->qtys) > 0) {
                                for ($i = 0; $i < count($val->qtys); $i++) {
                                    update_post_meta($post_id, 'qtys' . $i, $val->qtys[$i] ?: 0);
                                }
                            }

                            $all_addr = $val->all_address;

                            // Store one address
                            if (count($all_addr) > 0) {

                                update_post_meta($post_id, "dokan_geo_address",  $all_addr[0]->address);
                                update_post_meta($post_id, "dokan_geo_latitude", $all_addr[0]->lat);
                                update_post_meta($post_id, "dokan_geo_longitude", $all_addr[0]->long);

                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'no');
                            } else {
                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'yes');
                            }

                            update_post_meta($post_id, 'upc_field', $val->variations[0]->upc != 'N/A' ? $val->variations[0]->upc : 'N/A');


                            // Store All Addresses  
                           

                            if (count($all_addr) > 0) {
                                for ($i = 0; $i < count($all_addr); $i++) {
                                    update_post_meta($post_id, 'addr' . $i, $all_addr[$i]->address ?: '');
                                    update_post_meta($post_id, 'addr_latitude' . $i, $all_addr[$i]->lat ?: '');
                                    update_post_meta($post_id, 'addr_longitude' . $i, $all_addr[$i]->long ?: '');
                                }
                            }



                            /* ============================ Product Thumbnail ================= */

                            if (count($val->image_url) > 0) {

                                // Add Featured Image to Post
                                $image_url        = $val->image_url[0]->url; // Define the image URL here
                                $image_name       = $val->image_url[0]->name;
                                $upload_dir       = wp_upload_dir(); // Set upload folder
                                $image_data       = file_get_contents($image_url); // Get image data
                                $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                $filename         = basename($unique_file_name); // Create image file name

                                // Check folder permission and define file location
                                if (wp_mkdir_p($upload_dir['path'])) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }

                                // Create the image  file on the server
                                file_put_contents($file, $image_data);

                                // Check image file type
                                $wp_filetype = wp_check_filetype($filename, null);

                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name($filename),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                // Create the attachment
                                $attach_id = wp_insert_attachment($attachment, $file, $post_id);

                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata($attach_id, $file);

                                // Assign metadata to attachment
                                wp_update_attachment_metadata($attach_id, $attach_data);

                                // And finally assign featured image to post
                                set_post_thumbnail($post_id, $attach_id);
                            }

                            /* ============================ End Product Thumbnail ================= */


                            // Variation Process
                            $attr_label = 'Item Variations';
                            $attr_slug = sanitize_title($attr_label);

                            $attributeName = '';

                            // Remove | from last element
                            $numItems = count($val->variations);
                            $i = 0;

                            foreach($val->variations as $variationName => $variationValue){

                                $separator = (++$i == $numItems) ? '' : ' | ';

                               $attributeName .= $variationValue->name . $separator;   
                            }
                            
                            $attributes_array[$attr_slug] = array(
                                'name' => $attr_label,
                                'value' => $attributeName,
                                'is_visible' => '1',
                                'is_variation' => '1',
                                'is_taxonomy' => '0' // for some reason, this is really important       
                            );
                            update_post_meta( $post_id, '_product_attributes', $attributes_array );


                            // Start Variation Loop

                            $parent_id = $post_id;
                            $variation = array(
                                'post_title'   => $val->item_name . ' (variation)',
                                'post_content' => '',
                                'post_status'  => 'publish',
                                'post_parent'  => $parent_id,
                                'post_type'    => 'product_variation'
                            );


                            foreach($val->variations as $variationItem => $variationValue){


                                $price = 0;

                                if ($variationValue->price->amount) {

                                    $price = $variationValue->price->amount;

                                    $currency = $variationValue->price->currency;

                                    if (@$symobl[$currency]) {
                                        $price = $price / $symobl[$currency];
                                        // $price = number_format($price, count($symobl[$currency]));
                                    } else {
                                        $price = $price / 100;
                                        // $price = number_format($price, 2);
                                    }
                                }


                                $variation_id = wp_insert_post( $variation );
                                update_post_meta( $variation_id, '_regular_price', $price );
                                update_post_meta( $variation_id, '_price', $price );
                                update_post_meta($variation_id, '_sku', $variationValue->sku);
                                update_post_meta( $variation_id, '_stock_qty', 10 );
                                update_post_meta( $variation_id, 'attribute_' . $attr_slug, $variationValue->name );

                                update_post_meta( $variation_id, 'by_variation', serialize($variationValue->by_variation) );

                                WC_Product_Variable::sync( $parent_id );

                                // Start Variation Image

                                if (count($variationValue->images) > 0) {

                                    // Add Featured Image to Post
                                    $image_url        = $variationValue->images[0]->url; // Define the image URL here
                                    $image_name       = $variationValue->images[0]->name;
                                    $upload_dir       = wp_upload_dir(); // Set upload folder
                                    $image_data       = file_get_contents($image_url); // Get image data
                                    $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                    $filename         = basename($unique_file_name); // Create image file name
    
                                    // Check folder permission and define file location
                                    if (wp_mkdir_p($upload_dir['path'])) {
                                        $file = $upload_dir['path'] . '/' . $filename;
                                    } else {
                                        $file = $upload_dir['basedir'] . '/' . $filename;
                                    }
    
                                    // Create the image  file on the server
                                    file_put_contents($file, $image_data);
    
                                    // Check image file type
                                    $wp_filetype = wp_check_filetype($filename, null);
    
                                    // Set attachment data
                                    $attachment = array(
                                        'post_mime_type' => $wp_filetype['type'],
                                        'post_title'     => sanitize_file_name($filename),
                                        'post_content'   => '',
                                        'post_status'    => 'inherit'
                                    );
    
                                    // Create the attachment
                                    $attach_id = wp_insert_attachment($attachment, $file, $variation_id);
    
                                    // Include image.php
                                    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
                                    // Define attachment metadata
                                    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    
                                    // Assign metadata to attachment
                                    wp_update_attachment_metadata($attach_id, $attach_data);
    
                                    // And finally assign featured image to post
                                    set_post_thumbnail($variation_id, $attach_id);
                                }
                                // End variation Image

                            }
                            


                            // $variation_id = wp_insert_post( $variation );
                            // update_post_meta( $variation_id, '_regular_price', 2 );
                            // update_post_meta( $variation_id, '_price', 2 );
                            // update_post_meta( $variation_id, '_stock_qty', 10 );
                            // update_post_meta( $variation_id, 'attribute_' . $attr_slug, 'alternative 2' );
                            // WC_Product_Variable::sync( $parent_id );

                            

                            // echo "Not Exisists";
                        }else{

                           $post_id = $getItemID[0]->post_id;

                           wp_update_post( array(
                            'ID' => $post_id,
                            'post_author' => $userID,
                            'post_title' => $val->item_name,
                            'post_content' =>$val->item_description,
                            'post_status' => 'publish',
                            'post_type' => "product",
                            ) );

                            wp_set_object_terms( $post_id, 'variable', 'product_type' );

                            // Add Custom Attributes
                            update_post_meta($post_id, 'square_catalog_item_id', $square_catalog_item_id);

                            wp_set_object_terms($post_id, $val->cat_name, 'product_cat');

                            update_post_meta($post_id, '_visibility', 'visible');
                            update_post_meta($post_id, '_stock_status', 'instock');

                            // update_post_meta($post_id, '_regular_price', $val->variations[0]->price->amount);
                            update_post_meta($post_id, '_sku', $val->variations[0]->sku);

                            update_post_meta($post_id, '_stock', $val->qtys[0]);

                            update_post_meta($post_id, 'variety', 1);

                            if (count($val->qtys) > 0) {
                                for ($i = 0; $i < count($val->qtys); $i++) {
                                    update_post_meta($post_id, 'qtys' . $i, $val->qtys[$i] ?: 0);
                                }
                            }
                            
                            $all_addr = $val->all_address;

                            // Store one address
                            if (count($all_addr) > 0) {

                                update_post_meta($post_id, "dokan_geo_address",  $all_addr[0]->address);
                                update_post_meta($post_id, "dokan_geo_latitude", $all_addr[0]->lat);
                                update_post_meta($post_id, "dokan_geo_longitude", $all_addr[0]->long);

                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'no');
                            } else {
                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'yes');
                            }

                            update_post_meta($post_id, 'upc_field', $val->variations[0]->upc != 'N/A' ? $val->variations[0]->upc : 'N/A');

                            
                            // Store All Addresses  
                        
                            if (count($all_addr) > 0) {
                                for ($i = 0; $i < count($all_addr); $i++) {
                                    update_post_meta($post_id, 'addr' . $i, $all_addr[$i]->address ?: '');
                                    update_post_meta($post_id, 'addr_latitude' . $i, $all_addr[$i]->lat ?: '');
                                    update_post_meta($post_id, 'addr_longitude' . $i, $all_addr[$i]->long ?: '');
                                }
                            }

                            /* ============================ Product Thumbnail ================= */

                            if (count($val->image_url) > 0) {

                                // Add Featured Image to Post
                                $image_url        = $val->image_url[0]->url; // Define the image URL here
                                $image_name       = $val->image_url[0]->name;
                                $upload_dir       = wp_upload_dir(); // Set upload folder
                                $image_data       = file_get_contents($image_url); // Get image data
                                $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                $filename         = basename($unique_file_name); // Create image file name

                                // Check folder permission and define file location
                                if (wp_mkdir_p($upload_dir['path'])) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }

                                // Create the image  file on the server
                                file_put_contents($file, $image_data);

                                // Check image file type
                                $wp_filetype = wp_check_filetype($filename, null);

                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name($filename),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                // Create the attachment
                                $attach_id = wp_insert_attachment($attachment, $file, $post_id);

                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata($attach_id, $file);

                                // Assign metadata to attachment
                                wp_update_attachment_metadata($attach_id, $attach_data);

                                // And finally assign featured image to post
                                set_post_thumbnail($post_id, $attach_id);
                            }

                            /* ============================ End Product Thumbnail ================= */

                            // Variation Process
                            $attr_label = 'Item Variations';
                            $attr_slug = sanitize_title($attr_label);

                            $attributeName = '';

                            // Remove | from last element
                            $numItems = count($val->variations);
                            $i = 0;

                            foreach($val->variations as $variationName => $variationValue){

                                $separator = (++$i == $numItems) ? '' : ' | ';
                        
                               $attributeName .= $variationValue->name . $separator;   
                            }

                            $attributes_array[$attr_slug] = array(
                                'name' => $attr_label,
                                'value' => $attributeName,
                                'is_visible' => '1',
                                'is_variation' => '1',
                                'is_taxonomy' => '0' // for some reason, this is really important       
                            );
                            update_post_meta( $post_id, '_product_attributes', $attributes_array );

                            // Start Variation Loop
                            $parent_id = $post_id;
                            


                            $product = wc_get_product($post_id);
                            $variations = $product->get_available_variations();
                            $exists_variations_id = wp_list_pluck( $variations, 'variation_id' );

                            // print_r($variations_id);

                            // if(count($exists_variations_id) == count($val->variations)){
                            //     // Update Variations

                            //     $variation = array(
                            //         'post_title'   => $val->item_name . ' (variation)',
                            //         'post_content' => '',
                            //         'post_status'  => 'publish',
                            //         'post_parent'  => $parent_id,
                            //         'post_type'    => 'product_variation'
                            //     );


                            // }else{

                                // Delete Variations
                                if(count( $exists_variations_id)>0){
                                    foreach($exists_variations_id as $exists_variation_id){
                                        $attachment_idddd  =  get_post_meta($exists_variation_id, '_thumbnail_id', true);
                                        wp_delete_attachment($attachment_idddd, true);
                                        wp_delete_post($exists_variation_id, true);
                                    }
                                }


                                // Insert Variations
                                $variation = array(
                                    'post_title'   => $val->item_name . ' (variation)',
                                    'post_content' => '',
                                    'post_status'  => 'publish',
                                    'post_parent'  => $parent_id,
                                    'post_type'    => 'product_variation'
                                );


                                foreach($val->variations as $variationItem => $variationValue){


                                    $price = 0;

                                    if ($variationValue->price->amount) {

                                        $price = $variationValue->price->amount;

                                        $currency = $variationValue->price->currency;

                                        if (@$symobl[$currency]) {
                                            $price = $price / $symobl[$currency];
                                            // $price = number_format($price, count($symobl[$currency]));
                                        } else {
                                            $price = $price / 100;
                                            // $price = number_format($price, 2);
                                        }
                                    }


                                    $variation_id = wp_insert_post( $variation );
                                    update_post_meta( $variation_id, '_regular_price', $price );
                                    update_post_meta( $variation_id, '_price', $price );
                                    update_post_meta($variation_id, '_sku', $variationValue->sku);
                                    update_post_meta( $variation_id, '_stock_qty', 10 );
                                    update_post_meta( $variation_id, 'attribute_' . $attr_slug, $variationValue->name );

                                    update_post_meta( $variation_id, 'by_variation', serialize($variationValue->by_variation) );

                                    WC_Product_Variable::sync( $parent_id );

                                    // Start Variation Image

                                    if (count($variationValue->images) > 0) {

                                        // Add Featured Image to Post
                                        $image_url        = $variationValue->images[0]->url; // Define the image URL here
                                        $image_name       = $variationValue->images[0]->name;
                                        $upload_dir       = wp_upload_dir(); // Set upload folder
                                        $image_data       = file_get_contents($image_url); // Get image data
                                        $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                        $filename         = basename($unique_file_name); // Create image file name

                                        // Check folder permission and define file location
                                        if (wp_mkdir_p($upload_dir['path'])) {
                                            $file = $upload_dir['path'] . '/' . $filename;
                                        } else {
                                            $file = $upload_dir['basedir'] . '/' . $filename;
                                        }

                                        // Create the image  file on the server
                                        file_put_contents($file, $image_data);

                                        // Check image file type
                                        $wp_filetype = wp_check_filetype($filename, null);

                                        // Set attachment data
                                        $attachment = array(
                                            'post_mime_type' => $wp_filetype['type'],
                                            'post_title'     => sanitize_file_name($filename),
                                            'post_content'   => '',
                                            'post_status'    => 'inherit'
                                        );

                                        // Create the attachment
                                        $attach_id = wp_insert_attachment($attachment, $file, $variation_id);

                                        // Include image.php
                                        require_once(ABSPATH . 'wp-admin/includes/image.php');

                                        // Define attachment metadata
                                        $attach_data = wp_generate_attachment_metadata($attach_id, $file);

                                        // Assign metadata to attachment
                                        wp_update_attachment_metadata($attach_id, $attach_data);

                                        // And finally assign featured image to post
                                        set_post_thumbnail($variation_id, $attach_id);
                                    }
                                    // End variation Image

                                }

                            // }



                            // echo "Exists";
                        }
                        // echo "Multi Variant--------!";

                    }
                    else{

                        $price = 0;

                        if ($val->variation_price->amount) {

                            $price = $val->variation_price->amount;

                            $currency = $val->variation_price->currency;

                            if (@$symobl[$currency]) {
                                $price = $price / $symobl[$currency];
                                // $price = number_format($price, count($symobl[$currency]));
                            } else {
                                $price = $price / 100;
                                // $price = number_format($price, 2);
                            }
                        }


                        $square_catalog_item_id = $val->item_id;

                        $getItemID = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE `meta_key` = 'square_catalog_item_id' AND `meta_value` = '$square_catalog_item_id'");

                        if (count($getItemID) == 0) {


                            $price = $val->variation_price->amount ? $price : '';

                            $post = array(
                                'post_author' => $userID,
                                'post_content' => $val->item_description,
                                'post_status' => "publish",
                                'post_title' => $val->item_name,
                                'post_parent' => '',
                                'post_type' => "product",
                            );

                            //Create post
                            $post_id = wp_insert_post($post, $wp_error);


                            update_post_meta($post_id, 'square_catalog_item_id', $square_catalog_item_id);

                            wp_set_object_terms($post_id, $val->cat_name, 'product_cat');
                            wp_set_object_terms($post_id, 'simple', 'product_type');

                            update_post_meta($post_id, '_visibility', 'visible');
                            update_post_meta($post_id, '_stock_status', 'instock');
                            update_post_meta($post_id, '_regular_price', $price);
                            //update_post_meta( $post_id, '_sale_price', $price );
                            update_post_meta($post_id, '_sku', $val->variation_SKU);
                            // update_post_meta( $post_id, '_price', $price );
                            update_post_meta($post_id, '_stock', $val->quantity);

                            update_post_meta($post_id, 'variety', 0);


                            if (count($val->qtys) > 0) {
                                for ($i = 0; $i < count($val->qtys); $i++) {
                                    update_post_meta($post_id, 'qtys' . $i, $val->qtys[$i] ?: 0);
                                }
                            }


                            if ($variation_location_id) {

                                update_post_meta($post_id, "dokan_geo_address", $val->proper_address);
                                update_post_meta($post_id, "dokan_geo_latitude", $val->add_lat);
                                update_post_meta($post_id, "dokan_geo_longitude", $val->add_long);

                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'no');
                            } else {
                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'yes');
                            }



                            update_post_meta($post_id, 'upc_field', $val->variation_UPC ? $val->variation_UPC : '');

                            $all_addr = $val->all_address;

                            if (count($all_addr) > 0) {
                                for ($i = 0; $i < count($all_addr); $i++) {
                                    update_post_meta($post_id, 'addr' . $i, $all_addr[$i]->address ?: '');
                                    update_post_meta($post_id, 'addr_latitude' . $i, $all_addr[$i]->lat ?: '');
                                    update_post_meta($post_id, 'addr_longitude' . $i, $all_addr[$i]->long ?: '');
                                }
                            }


                            if (count($val->image_url) > 0) {

                                // Add Featured Image to Post
                                $image_url        = $val->image_url[0]->url; // Define the image URL here
                                $image_name       = $val->image_url[0]->name;
                                $upload_dir       = wp_upload_dir(); // Set upload folder
                                $image_data       = file_get_contents($image_url); // Get image data
                                $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                $filename         = basename($unique_file_name); // Create image file name

                                // Check folder permission and define file location
                                if (wp_mkdir_p($upload_dir['path'])) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }

                                // Create the image  file on the server
                                file_put_contents($file, $image_data);

                                // Check image file type
                                $wp_filetype = wp_check_filetype($filename, null);

                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name($filename),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                // Create the attachment
                                $attach_id = wp_insert_attachment($attachment, $file, $post_id);

                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata($attach_id, $file);

                                // Assign metadata to attachment
                                wp_update_attachment_metadata($attach_id, $attach_data);

                                // And finally assign featured image to post
                                set_post_thumbnail($post_id, $attach_id);
                            }
                        } else {

                            $post_id = $getItemID[0]->post_id;

                            $price = $val->variation_price->amount ? $price : '';

                            wp_update_post( array(
                                'ID' => $post_id,
                                'post_author' => $userID,
                                'post_title' => $val->item_name,
                                'post_content' =>$val->item_description,
                                'post_status' => 'publish',
                                'post_type' => "product",
                            ) );

                            update_post_meta($post_id, 'square_catalog_item_id', $square_catalog_item_id);

                            wp_set_object_terms($post_id, $val->cat_name, 'product_cat');
                            wp_set_object_terms($post_id, 'simple', 'product_type');

                            update_post_meta($post_id, '_visibility', 'visible');
                            update_post_meta($post_id, '_stock_status', 'instock');
                            update_post_meta($post_id, '_regular_price', $price);
                            //update_post_meta( $post_id, '_sale_price', $price );
                            update_post_meta($post_id, '_sku', $val->variation_SKU);
                            // update_post_meta( $post_id, '_price', $price );
                            update_post_meta($post_id, '_stock', $val->quantity);

                            update_post_meta($post_id, 'variety', 0);

                            if (count($val->qtys) > 0) {
                                for ($i = 0; $i < count($val->qtys); $i++) {
                                    update_post_meta($post_id, 'qtys' . $i, $val->qtys[$i] ?: 0);
                                }
                            }

                            if ($variation_location_id) {

                                update_post_meta($post_id, "dokan_geo_address", $val->proper_address);
                                update_post_meta($post_id, "dokan_geo_latitude", $val->add_lat);
                                update_post_meta($post_id, "dokan_geo_longitude", $val->add_long);
                            
                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'no');
                            } else {
                                update_post_meta($post_id, '_dokan_geolocation_use_store_settings', 'yes');
                            }

                            update_post_meta($post_id, 'upc_field', $val->variation_UPC ? $val->variation_UPC : '');

                            $all_addr = $val->all_address;

                            if (count($all_addr) > 0) {
                                for ($i = 0; $i < count($all_addr); $i++) {
                                    update_post_meta($post_id, 'addr' . $i, $all_addr[$i]->address ?: '');
                                    update_post_meta($post_id, 'addr_latitude' . $i, $all_addr[$i]->lat ?: '');
                                    update_post_meta($post_id, 'addr_longitude' . $i, $all_addr[$i]->long ?: '');
                                }
                            }

                            if (count($val->image_url) > 0) {

                                // Add Featured Image to Post
                                $image_url        = $val->image_url[0]->url; // Define the image URL here
                                $image_name       = $val->image_url[0]->name;
                                $upload_dir       = wp_upload_dir(); // Set upload folder
                                $image_data       = file_get_contents($image_url); // Get image data
                                $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                                $filename         = basename($unique_file_name); // Create image file name
                            
                                // Check folder permission and define file location
                                if (wp_mkdir_p($upload_dir['path'])) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }
                            
                                // Create the image  file on the server
                                file_put_contents($file, $image_data);
                            
                                // Check image file type
                                $wp_filetype = wp_check_filetype($filename, null);
                            
                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name($filename),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );
                            
                                // Create the attachment
                                $attach_id = wp_insert_attachment($attachment, $file, $post_id);
                            
                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');
                            
                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata($attach_id, $file);
                            
                                // Assign metadata to attachment
                                wp_update_attachment_metadata($attach_id, $attach_data);
                            
                                // And finally assign featured image to post
                                set_post_thumbnail($post_id, $attach_id);
                            }
                        }

                    }

                    
                }
                // print_r($output);
            }

            // echo json_encode($output);


            // print_r($json_dec->objects);
            // print_r($json_dec->objects[0]->item_data->variations[0]->item_variation_data->location_overrides[0]->location_id);

        } else {
            $errors = $catalog_response->getErrors();
        }
    }

    // die();
}


function my_cron_schedules($schedules){
    if(!isset($schedules["15min"])){
        $schedules["15min"] = array(
            'interval' => 15*60,
            'display' => __('Once every 15 minutes'));
    }
    if(!isset($schedules["30min"])){
        $schedules["30min"] = array(
            'interval' => 30*60,
            'display' => __('Once every 30 minutes'));
    }
    if(!isset($schedules["1day"])){
        $schedules["1day"] = array(
            'interval' => 24*60*60,
            'display' => __('Once every 1 day'));
    }
    return $schedules;
}
add_filter('cron_schedules','my_cron_schedules');

// wp_schedule_event(time(), '5min', 'cron_pos_square_pos_import', $args);

// if (!wp_next_scheduled('cron_pos_square_pos_import')) {
//     wp_schedule_event(time(), '15min', 'cron_pos_square_pos_import');
// }
// add_action('cron_pos_square_pos_import', 'cron_pos_square_pos_import');


add_action( 'woocommerce_product_after_variable_attributes', 'variation_settings_fields', 10, 3 );
add_action( 'woocommerce_save_product_variation', 'save_variation_settings_fields', 10, 2 );
add_filter( 'woocommerce_available_variation', 'load_variation_settings_fields' );

function variation_settings_fields( $loop, $variation_data, $variation ) {
    woocommerce_wp_text_input(
        array(
            'id'            => "my_text_field{$loop}",
            'name'          => "my_text_field[{$loop}]",
            'value'         => get_post_meta( $variation->ID, 'my_text_field', true ),
            'label'         => __( 'Some label', 'woocommerce' ),
            'desc_tip'      => true,
            'description'   => __( 'Some description.', 'woocommerce' ),
            'wrapper_class' => 'form-row form-row-full',
        )
    );
}

function save_variation_settings_fields( $variation_id, $loop ) {
    $text_field = $_POST['my_text_field'][ $loop ];

    if ( ! empty( $text_field ) ) {
        update_post_meta( $variation_id, 'my_text_field', esc_attr( $text_field ));
    }
}

function load_variation_settings_fields( $variation ) {     
    $variation['my_text_field'] = get_post_meta( $variation[ 'variation_id' ], 'my_text_field', true );

    return $variation;
}


// Custom Template

add_filter( 'page_template', 'wpa3396_page_template' );
function wpa3396_page_template( $page_template )
{
    if ( is_page( 'square-authorization' ) ) {
        $page_template = dirname( __FILE__ ) . '/authorization.php';
    }
    return $page_template;
}

// AJAX for authorization

add_action('wp_ajax_nopriv_square_pos_verify_connect', 'square_pos_verify_connect');
add_action('wp_ajax_square_pos_verify_connect', 'square_pos_verify_connect');

function square_pos_verify_connect(){

    global $wpdb;

    $ownerAccessToekn = 'EAAAED4wr-EjavCCGSjThhRy9dsk0kQ5jPCFWOCAT5vO7NZGkSeynB3SySf_KMPc';

    // if (is_user_logged_in()) {

       $userID =  get_current_user_id();

    // }

    // exit;

    $client = new SquareClient([
        'accessToken' => $ownerAccessToekn,
        'environment' => Environment::PRODUCTION,
    ]);

    $code = $_POST['code'];


    // $body = new \Square\Models\ObtainTokenRequest(
    //     'sq0idp-nMIO_y2pDTfPk6MqK9_vyQ',
    //     'sq0csp-CtF_unV1c3KRhDHkYAnCWnA0Qf43RtvepZaxUBHXh5Y',
    //     'authorization_code'
    // );
    $body = new \Square\Models\ObtainTokenRequest(
        'sq0idp-7w6KCKAR2s1ei5L9BpocdQ',
        'sq0csp-gYh5yD6TSlvbsH3vEOBC6UxdfNlaUbGxVN4d50Q8SNA',
        'authorization_code'
    );
    $body->setCode($code);
    $body->setShortLived(false);
    
    $api_response = $client->getOAuthApi()->obtainToken($body);
    
    if ($api_response->isSuccess()) {
        $result = $api_response->getResult();
        $result = json_decode(json_encode($result));

        // print_r($result);

        // exit;

        $getUser = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}square_creds WHERE user_id = $userID");


        if (count($getUser) > 0) {
            $wpdb->update(
                $wpdb->prefix . 'square_creds',
                array(
                    'access_token' => $result->access_token,
                    'refresh_token' => $result->refresh_token,
                    'expires_at' => $result->expires_at,
                    'merchant_id' => $result->merchant_id,
                    'status' => 1,
                    'user_id' => $userID
                ),
                array(
                    'user_id' => $userID
                ),
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'square_creds',
                array(
                    'access_token' => $result->access_token,
                    'refresh_token' => $result->refresh_token,
                    'expires_at' => $result->expires_at,
                    'merchant_id' => $result->merchant_id,
                    'status' => 1,
                    'user_id' => $userID
                )
            );
        }

    } else {
        $errors = $api_response->getErrors();
    }


    die();
}

// AJAX for authorization

add_action('wp_ajax_nopriv_revoke_square_access', 'revoke_square_access');
add_action('wp_ajax_revoke_square_access', 'revoke_square_access');

function revoke_square_access(){
    
    global $wpdb;

    if (is_user_logged_in()) {

        $userID =  get_current_user_id();

        $getUser = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}square_creds WHERE user_id = $userID");
        
        $wpdb->delete(
            $wpdb->prefix . 'square_creds',
            array(
                'user_id' => $userID
            ),
        );

        echo get_site_url() . '/dashboard/pos/';

    }
    
    die();
}

if (!wp_next_scheduled('revoke__cron__square_token')) {
    wp_schedule_event(time(), '1day', 'revoke__cron__square_token');
}
add_action('revoke__cron__square_token', 'revoke__cron__square_token');
function revoke__cron__square_token(){
    
        global $wpdb;
    
        $getUser = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}square_creds WHERE status = 1");
    
        foreach ($getUser as $key => $value) {
            $userID = $value->user_id;
            $accessToken = $value->access_token;
            $refreshToken = $value->refresh_token;
            $expiresAt = $value->expires_at;
            $merchantId = $value->merchant_id;
            
            $cuurent_time = time();

            $expiresAt = strtotime($expiresAt);

            if($cuurent_time > $expiresAt){

                $wpdb->delete(
                    $wpdb->prefix . 'square_creds',
                    array(
                        'user_id' => $userID
                    ),
                );
            }


        }
}
