<?php
/*
Plugin Name: IRV to Zoho Sync Tool
Description: This Plugin syncs the inventory with Zoho CRM Products
Author: Mohammad Faisal Ahmed
Version: 1
*/

include_once 'utils_zoho_request.php';

/********** Admin Panel **************************/
add_action('admin_menu', 'irv_zoho_plugin_menu');

function irv_zoho_plugin_menu() {
	add_options_page('IRV Zoho Sync Tool', 'IRV Zoho Sync Tool', 'manage_options', 'irv_zoho-social-id', 'irv_zoho_options');
}

function irv_zoho_options() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
    if( isset($_POST['irv_zoho_hidden']) && $_POST['irv_zoho_hidden'] == 'Y' ) {
        update_option( 'irv_zoho_auth_token', $_POST['irv_zoho_auth_token'] );
        update_option( 'irv_zoho_custom_post_slug', $_POST['irv_zoho_custom_post_slug'] );
        update_option( 'irv_zoho_sync_field_count', $_POST['irv_zoho_sync_field_count'] );
		echo '<div class="updated"><p><strong>Settings Saved.</strong></p></div>';
	}

    if( isset($_POST['zoho_irv_mapping']) && $_POST['zoho_irv_mapping'] == 'Y' ) {
        for ($i = 1; $i < get_option( 'irv_zoho_sync_field_count', 5 ); $i++) {
            $key1 = "irv_zoho_mapping_wpField_$i";
            $value1 = $_POST["irv_zoho_mapping_wpField_$i"];
            update_option( $key1, $value1 );
            $key = "irv_zoho_mapping_zohoField_$i";
            $value = $_POST["irv_zoho_mapping_zohoField_$i"];
            update_option( $key, $value );
        }
		echo '<div class="updated"><p><strong>Settings Saved.</strong></p></div>';
	}

    $zohoFields = getZohoFields();
    $wpFields = getWpFields();

    ?>
<link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>style.css" type="text/css"/>
<link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>select2/select2.css" type="text/css"/>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script>
<script type="text/javascript" src="<?php echo plugin_dir_url( __FILE__ ); ?>select2/select2.js"></script>
<h2>IRV Zoho Sync Tools Settings</h2>
<form name="form1" method="post" action="">
	<input type="hidden" name="irv_zoho_hidden" value="Y">
    <div class="zoho_irv_settings_form">
        <span>
            <label style="width: 155px; display: inline-block;">Zoho Auto Token:</label>
            <input style="width: 200px" type="text" name="irv_zoho_auth_token" value="<?php echo get_option( 'irv_zoho_auth_token' ); ?>" size="50">
        </span>
        <span>
            <label style="width: 155px; display: inline-block;">Wordpress Post Type Slug to Sync:</label>
            <input style="width: 200px" type="text" name="irv_zoho_custom_post_slug" value="<?php echo get_option( 'irv_zoho_custom_post_slug' ); ?>" size="50">
        </span>
    </div>
    <div class="zoho_irv_settings_form">
        <span>
            <label style="width: 155px; display: inline-block;">Zoho Module Name to Sync:</label>
            <input style="width: 200px" type="text" disabled="disabled" name="irv_zoho_module_name" value="Zoho Products" size="50">
        </span>
        <span>
            <label style="width: 155px; display: inline-block;">How many fields to sync:</label>
            <input style="width: 200px" type="number" name="irv_zoho_sync_field_count" min="5" value="<?php echo get_option( 'irv_zoho_sync_field_count', 2 ); ?>" size="50">
        </span>
    </div>
    <div class="zoho_irv_settings_form">
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
        </p>
    </div>
	<hr />
</form>

<form name="mapping" method="post" action="">
    <input type="hidden" name="zoho_irv_mapping" value="Y" />
    <div class="zoho_irv_settings_form">
        <span>
            <label>Zoho Field: </label>
            <select name="irv_zoho_mapping_zohoField_0" disabled="disabled" style="width: 200px">
                <option value="Product_Name">Product Name</option>
            </select>
            <span>&nbsp;<<===>>&nbsp;</span>
            <label>WP Inventory Field: </label>
            <select name="irv_zoho_mapping_wpField_0" disabled="disabled" style="width: 200px">
                <option value="title">Post Title</option>
            </select>
        </span>
    </div>
    <div class="zoho_irv_settings_form">
        <span>
            <label>Zoho Field: </label>
            <select name="irv_zoho_mapping_zohoField_1" disabled="disabled" style="width: 200px">
                <option value="Link_to_listing">Link to listing</option>
            </select>
            <span>&nbsp;<<===>>&nbsp;</span>
            <label>WP Inventory Field: </label>
            <select name="irv_zoho_mapping_wpField_1" disabled="disabled" style="width: 200px">
                <option value="permalink">Post URL</option>
            </select>
        </span>
    </div>
    <div class="zoho_irv_settings_form">
        <span>
            <label>Zoho Field: </label>
            <select name="irv_zoho_mapping_zohoField_2" disabled="disabled" style="width: 200px">
                <option value="Image_1">Image 1</option>
            </select>
            <span>&nbsp;<<===>>&nbsp;</span>
            <label>WP Inventory Field: </label>
            <select name="irv_zoho_mapping_wpField_2" disabled="disabled" style="width: 200px">
                <option value="product_images_gallery">First Attached Image (If Any)</option>
            </select>
        </span>
    </div>
    <div class="zoho_irv_settings_form">
        <span>
            <label>Zoho Field: </label>
            <select name="irv_zoho_mapping_zohoField_3" disabled="disabled" style="width: 200px">
                <option value="Image_2">Image 2</option>
            </select>
            <span>&nbsp;<<===>>&nbsp;</span>
            <label>WP Inventory Field: </label>
            <select name="irv_zoho_mapping_wpField_3" disabled="disabled" style="width: 200px">
                <option value="product_images_gallery">Second Attached Image (If Any)</option>
            </select>
        </span>
    </div>
    <?php for ($i = 4; $i < get_option( 'irv_zoho_sync_field_count', 5 ); $i++) { ?>
        <div class="zoho_irv_settings_form">
            <span>
                <label>Zoho Field: </label>
                <select id="irv_zoho_mapping_zohoField_<?php echo $i ?>" name="irv_zoho_mapping_zohoField_<?php echo $i ?>" style="width: 200px">
                    <option value="">None</option>
                    <?php foreach ($zohoFields as $key => $value) {
                        $selected = ($key == get_option("irv_zoho_mapping_zohoField_$i")) ? "selected=\"selected\"" : "";
                        echo "<option " . $selected . " value=\"{$key}\">$value</option>";
                    } ?>
                </select>
                <span>&nbsp;&nbsp;<<===>>&nbsp;&nbsp;</span>
                <label>WP Inventory Field: </label>
                <select id="irv_zoho_mapping_wpField_<?php echo $i ?>" name="irv_zoho_mapping_wpField_<?php echo $i ?>" style="width: 200px">
                    <option value="">None</option>
                    <?php foreach ($wpFields as $key => $value) {
                        $selected = ($key == get_option("irv_zoho_mapping_wpField_$i")) ? "selected=\"selected\"" : "";
                        echo "<option " . $selected . " value=\"{$key}\">$value</option>";
                    } ?>
                </select>
            </span>
            <script type="text/javascript">
                $(function() {
                    $("#irv_zoho_mapping_zohoField_<?php echo $i ?>").select2();
                    $("#irv_zoho_mapping_wpField_<?php echo $i ?>").select2();
                });
            </script>
        </div>
    <?php } ?>
    <div class="zoho_irv_settings_form">
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
        </p>
    </div>
    <hr />
</form>

<?php	
}

function save_inventory( $post_id ) {

    //debug($_REQUEST); die;
    $slug = get_option('irv_zoho_custom_post_slug');
    $post = get_post($post_id, ARRAY_A);
    $zohoProductID = get_post_meta($post_id, 'zoho_id', true);
    $zohoConnector = new ZohoDataSync(get_option( 'irv_zoho_auth_token' ));

    if ( $slug != $post['post_type'] ) {
        return;
    }

    if ($post['post_status'] === 'trash') {
        //////This is Delete Operation
        $zohoConnector->deleteRecords(PRODUCT_MODULE, $zohoProductID);
    } else if ($post['post_status'] === 'publish') {
        ///// This is Create Operation
        $imageIdArray = $_REQUEST['fields'][PRODUCT_IMAGES_GALLERY];
        $image_1 = (isset($imageIdArray[0])) ? wp_get_attachment_url($imageIdArray[0]) : "";
        $image_2 = (isset($imageIdArray[1])) ? wp_get_attachment_url($imageIdArray[1]) : "";
        $xmlArray = array(
            1 => array(
                'Product Name' => $_REQUEST['post_title'],
                'WP Inventory ID' => $post_id,
                'Link to listing' => get_permalink($post_id),
                'Image 1' => $image_1,
                'Image 2' => $image_2,
            ),
        );

        for ($i = 4; $i < get_option( 'irv_zoho_sync_field_count', 5 ); $i++) {
            $value1 = get_option("irv_zoho_mapping_wpField_$i");
            $value = get_option("irv_zoho_mapping_zohoField_$i");
            if ($value != '') {
                $xmlField = str_replace("_", " ", $value);
                $xmlValue = (isset($_REQUEST['fields'][$value1])) ? $_REQUEST['fields'][$value1] : "";
                $xmlArray[1][$xmlField] = $xmlValue;
            }
        }

        if ($xmlArray[1]['RV Status'] != '' && count($xmlArray[1]['RV Status'])) {
            $RVStatus = get_term_by('id', $xmlArray[1]['RV Status'][0], 'vehicle_status');
            $xmlArray[1]['RV Status'] = $RVStatus->name;
        }

        if ($xmlArray[1]['RV Location'] != '' && count($xmlArray[1]['RV Location'])) {
            $RVStatus = get_term_by('id', $xmlArray[1]['RV Location'][0], 'vehicle_location');
            $xmlArray[1]['RV Location'] = $RVStatus->name;
        }

        if ($xmlArray[1]['RV Year Of Manufacture'] != '' && count($xmlArray[1]['RV Year Of Manufacture'])) {
            $RVStatus = get_term_by('id', $xmlArray[1]['RV Year Of Manufacture'][0], 'vehicle_year');
            $xmlArray[1]['RV Year Of Manufacture'] = $RVStatus->name;
        }

        if ($xmlArray[1]['RV Type'] != '' && count($xmlArray[1]['RV Type'])) {
            $RVStatus = get_term_by('id', $xmlArray[1]['RV Type'][0], 'vehicle_type');
            $xmlArray[1]['RV Type'] = $RVStatus->name;
        }

        if ($xmlArray[1]['RV Make'] != '' && count($xmlArray[1]['RV Make'])) {
            $grandParent = '';
            $parent = '';
            $child = '';
            foreach ($xmlArray[1]['RV Make'] as $key => $id){
                $RVStatus = get_term_by('id', $id, 'vehicle_model');
                if ($RVStatus->parent == 0) {
                    $grandParent = $RVStatus->name;
                } else {
                    $RVStatus2 = get_term_by('id', $RVStatus->parent, 'vehicle_model');
                    if (isset($RVStatus2->parent) && $RVStatus2->parent == 0) {
                        $parent = $RVStatus->name;
                    } else {
                        $child = $RVStatus->name;
                    }
                }
            }
            $xmlArray[1]['RV Manufacture'] = $grandParent;
            $xmlArray[1]['RV Make'] = $parent;
            $xmlArray[1]['RV Model'] = $child;
        }

        //debug($_REQUEST);
        //debug($xmlArray); die;
        if ($_REQUEST['original_publish'] == 'Publish') {
            $zohoCampaignUpdates = $zohoConnector->insertRecords(PRODUCT_MODULE, $xmlArray, 'false', 'true');
            $xml = simplexml_load_string($zohoCampaignUpdates);

            /******************Wordpress Update Start*******************/
            if (trim($xml->result->message) === 'Record(s) added successfully') {
                $zoho_id = trim($xml->result->recorddetail->FL[0]);
                add_post_meta($post_id, 'zoho_id', $zoho_id);
            }
            /******************Wordpress Update End*******************/
        } else if ($_REQUEST['original_publish'] == 'Update') {
            $zohoCampaignUpdates = $zohoConnector->updateRecords(PRODUCT_MODULE, $zohoProductID, $xmlArray, 'true');
        }
    }
}

add_action( 'save_post', 'save_inventory');

function getZohoFields(){
    $wpExcludeMeta = array(
        "Product Name",
        "Image 1",
        "Image 2",
        "Link to listing",
    );

    $zohoConnector = new ZohoDataSync(get_option( 'irv_zoho_auth_token' ));
    $fields = $zohoConnector->getFields(PRODUCT_MODULE);
    $xml = simplexml_load_string($fields);

    $zohoFields = array();
    foreach ($xml->section as $key => $value) {
        foreach ($value->FL as $key2 => $value2) {
            $temp = (string)$value2['label'];
            if (!in_array($temp, $wpExcludeMeta)) {
                $zohoFields[str_replace(" ", "_", $temp)] = $temp;
            }
        }
    }

    return $zohoFields;
}

function getWpFields(){
    $wpExcludeMeta = array(
        "_product_images_gallery",
        "_video_1",
        "_video_2",
        "_video_3",
        "_edit_lock",
        "_edit_last",
    );

    $meta_values = get_post_meta( 34130 );
    $wpFields = array();
    foreach ($meta_values as $key => $value) {
        if ($key[0] == '_' && !in_array($key, $wpExcludeMeta))
            $wpFields[$value[0]] = ucwords(trim(str_replace("_", " ", $key)));
    }

    return $wpFields;
}