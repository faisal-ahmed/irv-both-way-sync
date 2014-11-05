<?php
/**
 * Template Name: Zoho Sync
 *
 */

if ($_REQUEST['access_token'] === 'irv-inventory-both') {
    $post_id = (isset($_REQUEST['post_id'])) ? $_REQUEST['post_id'] : null;
    syncFromZoho($_REQUEST['id'], $post_id, $_REQUEST['action']);
}

