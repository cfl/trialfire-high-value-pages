<?php
/*
 Plugin Name: Trialfire High Value Pages
 Plugin URI: https://github.com/cfl/trialfire-high-value-pages
 Description: Adds a metabox to the Post and Page post types, allowing you to designate a Post/Page as a "high value page" worthy of followup action via Trialfire.
 Author: Canadian Football League
 Version: 1.0.0
 Author URI: https://www.cfl.ca/
*/

/**
 * Add a WordPress REST API endpoint at the following URL:
 *      https://www.yoursite.com/wp-json/trialfire-high-value-pages/v1/list/
 */
function trialfire_hvp_rest_api_init() {
    register_rest_route( 
        'trialfire-high-value-pages/v1', 
        '/list/', 
        array(
            'methods' => 'GET',
            'callback' => 'trialfire_hvp_get_posts',
        ) 
    );
}
add_action( 'rest_api_init', 'trialfire_hvp_rest_api_init' );

/**
 * Return a list of Posts and Pages that have been designated as High Value Pages, 
 * requiring some action to be taken on them for Trialfire-identified Persons.
 */
function trialfire_hvp_get_posts() {
    $arr_posts = array();

    $arr_post_ids = get_posts( 
        array(
            'post_type'     => array('post', 'page'),
            'post_status'   => 'publish', 
            'fields'        => 'ids',
            'meta_key'      => 'is_trialfire_high_value_page',
            'meta_value'    => '1',
            'numberposts'   => -1,
        ) 
    );

    foreach ( $arr_post_ids as $post_id ) {
        $obj_post = new stdClass;
        $obj_post->post_id = $post_id;
        $obj_post->title = esc_js(get_the_title($post_id));
        $obj_post->url = get_permalink($post_id);

        $arr_posts[] = $obj_post;
    }
 
    return $arr_posts;
}

/**
 * Add a "Trialfire - High Value Page" sidebar metabox for the Post and Page 
 * post types.
 */
function trialfire_hvp_metabox_add() {
    add_meta_box( 'add-trialfire-hvp-metabox', 'Trialfire - High Value Page', 'trialfire_hvp_metabox_callback', 'post', 'side', 'low' );
    add_meta_box( 'add-trialfire-hvp-metabox', 'Trialfire - High Value Page', 'trialfire_hvp_metabox_callback', 'page', 'side', 'low' );
}
add_action( 'add_meta_boxes', 'trialfire_hvp_metabox_add' );

/**
 * Actually display the HTML markup for the "Trialfire - High Value Page" sidebar metabox.
 */
function trialfire_hvp_metabox_callback( $post ) {
    $values = get_post_custom( $post->ID );
    $current_value_checked = isset( $values['is_trialfire_high_value_page'] ) ? $values['is_trialfire_high_value_page'][0] : '';

    wp_nonce_field( 'display_sidebar_meta_box_nonce', 'trialfire_hvp_meta_box_nonce' );

    $str_checked = '';
    if ( $current_value_checked == '1' ) {
        $str_checked = 'checked="checked"';
    }
    ?>
    <p>
        <input type="checkbox" name="is_trialfire_high_value_page" id="is_trialfire_high_value_page" value="1" <?php echo $str_checked; ?>> 
        <label for="is_trialfire_high_value_page">Mark as High Value Page?</label>

        <br><br>

        <span class="howto">If checked, site visitors identified via Trialfire who visit this page will be marked for follow-up actions in your Salesforce CRM.</a>
    </p>
    <?php  
}

/**
 * Save the value of the "Mark as High Value Page?" field as post metadata.
 */
function trialfire_hvp_metabox_save( $post_id ) {
    // Bail if we're doing an auto save.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // If our current user can't edit this page, bail.
    if ( !current_user_can( 'edit_pages' ) ) return;

    // If a value is set, we'll take it as a yes that we designate this post as a High Value Page.
    // If no value is set, we delete that value (if it ever existed). 
    if( isset( $_POST['is_trialfire_high_value_page'] ) ) {
        update_post_meta( $post_id, 'is_trialfire_high_value_page', $_POST['is_trialfire_high_value_page'] );
    }
    else {
        delete_post_meta( $post_id, 'is_trialfire_high_value_page' );
    }
}
add_action( 'save_post', 'trialfire_hvp_metabox_save' );

?>