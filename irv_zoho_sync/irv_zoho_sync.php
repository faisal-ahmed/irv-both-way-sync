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

    echo "This is working";
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

        foreach ($xmlArray[1] as $key => $value) {
            if ($value == '') {
                unset($xmlArray[1][$key]);
            }
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

function getWpFields($format = null){
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
            if ($format === null) {
                $wpFields[$value[0]] = ucwords(trim(str_replace("_", " ", $key)));
            } else {
                $wpFields[$value[0]] = $key;
            }
    }

    return $wpFields;
}

function syncFromZoho($zohoId, $post_id = null, $action){
    $zohoConnector = new ZohoDataSync(get_option( 'irv_zoho_auth_token' ));

    if ($action != 'delete') {
        $wpFields = getWpFields(true);
        $zohoData = $zohoConnector->getRecordById(PRODUCT_MODULE, $zohoId);
        $xml = simplexml_load_string($zohoData);
        $zohoFields = array();
        foreach ($xml->result->Products->row as $key => $value) {
            foreach ($value->FL as $key2 => $row) {
                $zohoFields[(string)$row['val']] = trim($row);
            }
        }

        $postMeta = array(
            '_zoho_id' => $zohoId
        );

        for ($i = 4; $i < get_option( 'irv_zoho_sync_field_count', 5 ); $i++) {
            $wpValue = get_option("irv_zoho_mapping_wpField_$i");
            $zohoValue = get_option("irv_zoho_mapping_zohoField_$i");
            if ($zohoValue != '') {
                $postMeta[$wpFields[$wpValue]] = $zohoFields[str_replace("_", " ", $zohoValue)];
            }
        }

        $post = array(
            'post_title' => $zohoFields['Product Name'],
            'post_type'   => 'vehicles',
            'post_status'   => 'publish',
            'post_author'   => 1,
        );

        if ($action === 'update') {
            $post['ID'] = $zohoFields['WP Inventory ID'];
        }

        $id = wp_insert_post( $post , true);
        var_dump($id);

        if (isset($zohoFields['RV Status']) && $zohoFields['RV Status'] != '') {
            $RVStatus = get_term_by('name', $zohoFields['RV Status'], 'vehicle_status');
            unset($postMeta['_rv_status']);
            wp_set_post_terms( $id, $RVStatus->term_id, 'vehicle_status' );
        }

        if (isset($zohoFields['RV Location']) && $zohoFields['RV Location'] != '') {
            $RVStatus = get_term_by('name', $zohoFields['RV Location'], 'vehicle_location');
            unset($postMeta['_rv_location']);
            wp_set_post_terms( $id, $RVStatus->term_id, 'vehicle_location' );
        }

        if (isset($zohoFields['RV Year Of Manufacture']) && $zohoFields['RV Year Of Manufacture'] != '') {
            $RVStatus = get_term_by('name', $zohoFields['RV Year Of Manufacture'], 'vehicle_year');
            unset($postMeta['_rv_year_of_manufacture']);
            wp_set_post_terms( $id, $RVStatus->term_id, 'vehicle_year' );
        }

        if (isset($zohoFields['RV Type']) && $zohoFields['RV Type'] != '') {
            $RVStatus = get_term_by('name', $zohoFields['RV Type'], 'vehicle_type');
            unset($postMeta['_rv_type']);
            wp_set_post_terms( $id, $RVStatus->term_id, 'vehicle_type' );
        }

        unset($postMeta['_rv_make_model']);
        $rv_make_model = array();
        if (isset($zohoFields['RV Manufacture']) && $zohoFields['RV Manufacture'] != '') {
            $temp = get_term_by('name', $zohoFields['RV Manufacture'], 'vehicle_model');
            $rv_make_model[] = $temp->term_id;
        }

        if (isset($zohoFields['RV Make']) && $zohoFields['RV Make'] != '') {
            $temp = get_term_by('name', $zohoFields['RV Make'], 'vehicle_model');
            $rv_make_model[] = $temp->term_id;
        }

        if (isset($zohoFields['RV Model']) && $zohoFields['RV Model'] != '') {
            $temp = get_term_by('name', $zohoFields['RV Model'], 'vehicle_model');
            $rv_make_model[] = $temp->term_id;
        }

        wp_set_post_terms( $id, $rv_make_model, 'vehicle_model' );

        foreach ($postMeta as $key => $value) {
            $updateMeta = update_post_meta($id, substr($key, 1), $value);
            debug($updateMeta);
        }

        if ($action === 'create') {
            $xmlArray = array(
                1 => array(
                    'WP Inventory ID' => $id,
                ),
            );

            $zohoPostUpdate = $zohoConnector->updateRecords(PRODUCT_MODULE, $zohoId, $xmlArray, 'true');
            debug($zohoPostUpdate);
        }
        debug($postMeta);
        debug(get_post_meta( $id ));
    } else {
        $delete_status = wp_delete_post($post_id);
        debug($delete_status);
    }
}
