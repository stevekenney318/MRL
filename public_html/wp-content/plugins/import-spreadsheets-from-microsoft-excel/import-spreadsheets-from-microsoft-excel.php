<?php
/**
 *
Plugin Name: Import Spreadsheets from Microsoft Excel
Plugin URI: https://www.spreadsheetconverter.com/support/online-help/help-wordpress-plugin-to-import-spreadsheets-from-microsoft-excel
Description: Import Spreadsheets from Microsoft Excel
Version: 10.1.5
Author: SpreadsheetConverter
Author URI: http://www.spreadsheetconverter.com
Text Domain: SpreadsheetConverter
License: GPLv2 or later
Publish tool: Subversion
Permission: needs user's permission to embed Powered-by link on embedded page
Documentation: readme.txt

 *

Copyright (C) 2019-2024  SpreadsheetConverter, http://www.spreadsheetconverter.com
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

************************************************************************************/
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'IMS_FME_SSC_PLUGIN_URL_MANAGER', plugin_dir_url(__FILE__) ) ;

register_activation_hook( __FILE__, 'ims_fme_ssc_activate' );
function ims_fme_ssc_activate( $network_wide ) {
    if ( !extension_loaded('zip') ) {
        echo '<h3>';
        esc_html_e('Please install or enable PHP extension ZIPArchive before activating plugin.', 'SpreadsheetConverter');
        echo '</h3>';
        ims_fme_ssc_errorLog( __('Please install or enable PHP extension ZIPArchive before activating plugin.', 'SpreadsheetConverter') );
        @trigger_error(esc_html__('Please install or enable PHP extension ZIPArchive before activating plugin.', 'SpreadsheetConverter'), E_USER_ERROR);
    }
}

//Register shortcode output frontent style sheet
add_action( 'wp_enqueue_scripts', 'ims_fme_ssc_shortcode_manager_style' );
function ims_fme_ssc_shortcode_manager_style() {
    wp_enqueue_style( 'ims-fme-ssc-custom', IMS_FME_SSC_PLUGIN_URL_MANAGER . 'css/custom_plugin.css', array(), '1.0.0' );
}

//Register backend scripts and styles
add_action( 'admin_enqueue_scripts', 'ims_fme_ssc_shortcode_manager_scripts', 10 );
function ims_fme_ssc_shortcode_manager_scripts( $hook ) {
    wp_register_script( 'ims-fme-ssc-shortcode-manager-scripts', IMS_FME_SSC_PLUGIN_URL_MANAGER . 'js/plugin-shortcode-manager-scripts.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'media-upload','thickbox' ), '1.0.0', true );
    wp_enqueue_script( 'ims-fme-ssc-shortcode-manager-scripts' );

    $args = array(
                    'ajax_url'        => admin_url( 'admin-ajax.php' ), 
                    'ajax_nonce'      => wp_create_nonce( 'link_page' )
                 );
    wp_localize_script( 'ims-fme-ssc-shortcode-manager-scripts', 'SMC_OBJ', $args );

    wp_enqueue_script('jquery-ui-resizable');      
    wp_register_style( 'ims-fme-ssc-shortcode-manager-styles', IMS_FME_SSC_PLUGIN_URL_MANAGER . 'css/style_plugin.css', array( 'thickbox' ), '1.0.0' );
    wp_enqueue_style( 'ims-fme-ssc-shortcode-manager-styles' );
}

add_action( 'after_setup_theme', 'ims_fme_ssc_add_editor_styles' );
function ims_fme_ssc_add_editor_styles() {
    add_editor_style( IMS_FME_SSC_PLUGIN_URL_MANAGER . 'css/admin_plugin.css' );
}

add_action( 'init', 'ims_fme_ssc_wptuts_buttons' );
function ims_fme_ssc_wptuts_buttons() {
    
    //check current user can validation
    if ( !current_user_can( 'edit_posts' ) ){
        return;
    }
    //add_filter( "mce_external_plugins", "ims_fme_ssc_add_buttons" );
    add_filter( 'mce_buttons', 'ims_fme_ssc_register_buttons' );
}
function ims_fme_ssc_add_buttons( $plugin_array ) {
    $plugin_array['imsFmeScc'] = IMS_FME_SSC_PLUGIN_URL_MANAGER . 'js/admin_plugin.js';
    return $plugin_array;
    //return '';
}
function ims_fme_ssc_register_buttons( $buttons ) {
    //check current user can validation
    if ( !current_user_can( 'delete_posts' ) ){
        return;
    }
    array_push( $buttons, 'deleteCalculator');
    return $buttons;
}

add_action('admin_print_styles', 'ims_fme_ssc_add_my_tinymce_button_css');
function ims_fme_ssc_add_my_tinymce_button_css() {
        $screen = get_current_screen();      
        if ( 'page' == $screen->id || 'post' == $screen->id ) {
        wp_register_style('ims-fme-ssc-button-css', IMS_FME_SSC_PLUGIN_URL_MANAGER . ('/css/admin_plugin.css'), array(), '1.0.0');        
        wp_enqueue_style('ims-fme-ssc-button-css');
        wp_enqueue_style('dashicons');
        }
}

//Add button to the media button context in edit/add new post screen
add_action('media_buttons_context',  'ims_fme_ssc_ttc_files_button');
function ims_fme_ssc_ttc_files_button( $context ) {
    $context .= "<a id='files_media_link' href='#TB_inline?width=650&height=600&inlineId=tt_shortcode_popup_container&guid=".uniqid()."' class='button thickbox' title='". __('Embed SpreadsheetConverter Calculator', 'SpreadsheetConverter') ."'><span class='files_media_icon'></span>". __('Embed SSC Calculator', 'SpreadsheetConverter' )."</a>";
    $context .= "
    <style>
    .files_media_icon{
    background:url(" . IMS_FME_SSC_PLUGIN_URL_MANAGER . "/icon/add_shortcode.png) no-repeat top left;
    display: inline-block;
    height: 16px;
    margin: 0 2px 0 0;
    vertical-align: text-top;
    width: 16px;
    }
    .wp-core-ui a.files_media_link{
    padding-left: 0.4em;
    }
    </style>";
    return $context;
}

add_action( 'admin_footer',  'ims_fme_ssc_add_inline_files_popup_content' );
function ims_fme_ssc_add_inline_files_popup_content() {
?>
<div id="tt_shortcode_popup_container" style="display:none;">
    <h2><?php bloginfo('name');esc_html_e('Shortcode Manager', 'projectx');?></h2>
    <div class="wrap" id="tabs_container">
        <ul class="tabs">
            <li class="active">
                <a href="#" id="tab1" rel="#tab_1_contents" class="tab">Calculator Block</a>
            </li>
        </ul>

        <div class="tab_contents_container">
            <div id="tab_1_contents" class="tab_contents tab_contents_active">
                <form>        
                    <div style="padding-left:143px; margin-bottom: -10px;" class="">
                        <i><?php __('Select the previously uploaded calculator or form you want to embed at the current cursor position.', 'SpreadsheetConverter') ?></i>
                    </div>
                        <ul id="btn_shortcode">
                            <li>
                                <label style="width:140px;display: inline-block;">
                                    <?php esc_html_e('Calculator Link', 'SpreadsheetConverter'); ?><em style="color:red">*</em>
                                </label>
                                    <?php
                                    remove_all_filters('posts_orderby');
                                    $args = array(
                                                        'post_status'   => 'publish',
                                                        'post_type'     => array( 'imsfmessc-file' ),
                                                        'showposts'     => -1,
                                                        'orderby'       => 'post_date',
                                                        'order'         => 'DESC'
                                                    );

                                    $my_query = new WP_Query( $args );
                                    ?>
                                    <select name="btn_link" id="btn_link_calculator" style="width:67%">
                                        <option value="0" ><?php esc_html_e('Select Links', 'SpreadsheetConverter'); ?></option>
                                        <?php 
                                        while ( $my_query->have_posts() ) : 
                                                $m_post = $my_query->next_post();?>
                                                <option value="<?php echo intval(($m_post->ID)) ?>">
                                                <?php
                                                $post_type          = $m_post->post_type;
                                                $post_type_data     = get_post_type_object( $post_type );
                                                echo esc_html( $m_post->post_title );
                                                ?>
                                                </option>
                                            <?php
                                        endwhile; ?>
                                    </select>
                                    <?php wp_reset_postdata(); ?>
                            </li>

                            <li style="display:none;">
                                <label style="width:140px;display: inline-block;">File URL:</label>
                                <input type="text" id="file_url_calculator" name="file_url" style="width:67%" />
                            </li>

                            <div style="padding-left:143px; ">
                                <i><?php esc_html_e('To change the size of the windows frame used to show the calculator or form, adjust the Iframe width and Iframe height.', 'SpreadsheetConverter'); ?></i>
                            </div>
                            
                            <li>
                                <label style="width:140px;display: inline-block;">
                                   <?php esc_html_e( 'Iframe Width', 'SpreadsheetConverter'); ?>:<em style="color:red">*</em></label>
                                <input type="text" id="iframe_width_calculator" name="iframe_width" style="width:25%" />
                            </li>

                            <li>
                                <label style="width:140px;display: inline-block;"><?php esc_html_e('Iframe Height', 'SpreadsheetConverter') ?>: <em style="color:red">*</em></label>
                                <input type="text" id="iframe_height_calculator" name="iframe_height" style="width:25%" />
                            </li>
                              
                            <input type="text" style="display:none" id="admin_url" value="<?php echo esc_url( admin_url('admin-ajax.php') );?>" />
                    </ul>

                        <input class="button-primary" type="button" id="insertbutton_calculator" style="margin-left:145px" value="<?php esc_html_e('Insert Calculator', 'SpreadsheetConverter'); ?>" />
                        <a class="button" onclick="tb_remove(); return false;" href="#"><?php esc_html_e('Cancel', 'SpreadsheetConverter'); ?></a>

                    
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript"></script>

<?php
}

//Ajax Call For Url of Pages
add_action( 'wp_ajax_ims_fme_ssc_Ajax_Link_Page', 'ims_fme_ssc_Ajax_Link_Page' );
//add_action( 'wp_ajax_nopriv_ims_fme_ssc_Ajax_Link_Page', 'ims_fme_ssc_Ajax_Link_Page' );
function ims_fme_ssc_Ajax_Link_Page() {
    
    check_ajax_referer( 'link_page', 'security' );
    
    if( isset($_POST['post_id']) ):
            $postid    = intval( $_POST['post_id'] );
    endif;

    $fileurl    = esc_url( get_post_meta( $postid, 'wp_custom_attachment', true ) );
    $height     = intval( get_post_meta( $postid, 'wp_custom_attachment_height', true ) );
    $width      = intval( get_post_meta( $postid, 'wp_custom_attachment_width', true ) );

    echo wp_json_encode(array( 
                            'html'      => $fileurl,
                            'height'    => $height,
                            'width'     => $width
                 ));
    exit;
}


//create a post type to upload file attachment
add_action( 'init', 'ims_fme_ssc_custom_init' ); 
function ims_fme_ssc_custom_init() {
    
    //check permission
    if ( !current_user_can( 'edit_pages' ) ) {
        return;
    } 

    $labels = array(
    'name'                => _x( 'Upload SSC Calculator', 'Post Type General Name', 'SpreadsheetConverter' ),
    'singular_name'       => _x( 'Calculator', 'Post Type Singular Name', 'SpreadsheetConverter' ),
    'menu_name'           => __( 'Upload SSC Calculator', 'SpreadsheetConverter' ),
    'parent_item_colon'   => __( 'Parent Calculator', 'SpreadsheetConverter' ),
    'all_items'           => __( 'All Calculators', 'SpreadsheetConverter' ),
    'view_item'           => __( 'View Calculator', 'SpreadsheetConverter' ),
    'add_new_item'        => __( 'Add New Calculator', 'SpreadsheetConverter' ),
    'add_new'             => __( 'Add New', 'SpreadsheetConverter' ),
    'edit_item'           => __( 'Edit Calculator', 'SpreadsheetConverter' ),
    'update_item'         => __( 'Update Calculator', 'SpreadsheetConverter' ),
    'search_items'        => __( 'Search Calculator', 'SpreadsheetConverter' ),
    'not_found'           => __( 'Not Found', 'SpreadsheetConverter' ),
    'not_found_in_trash'  => __( 'Not found in Trash', 'SpreadsheetConverter' ),
    );

    $supports = array('title');
    $args = array(
    'public'    => true,
    'labels'    => $labels,
    'supports'  => $supports,

    );
    register_post_type( 'imsfmessc-file', $args );
}


add_action( 'add_meta_boxes', 'ims_fme_ssc_add_custom_meta_boxes' );
function ims_fme_ssc_add_custom_meta_boxes() {
    $screen = get_current_screen();
    // Define the custom attachment for posts
    add_meta_box(
    'wp_custom_attachment',
    __( 'Upload the package of SSC calculator', 'SpreadsheetConverter' ),
    'ims_fme_ssc_wp_custom_attachment',
    'imsfmessc-file',
    'normal'
    );
    if( 'add' != $screen->action ){
        add_meta_box(
            'demo-meta-box',
            'Calculator Iframe',
            'isfme_wp_custom_shortcode',
            'imsfmessc-file',
            'normal',
            'low',
            null
        );
    }
}

function ims_fme_ssc_wp_custom_attachment() {
    global $post;
    wp_nonce_field( plugin_basename(__FILE__), 'wp_custom_attachment_nonce' );
    $link = esc_url( get_post_meta( $post->ID,'wp_custom_attachment','true' ) );
    $zipFileName = '';
    $row = [];
    // if ( !empty( $link ) ) {

    //     $fileArray      = explode( '/', $link );
    //     $reverse        = array_reverse( $fileArray );
    //     $zipFileName    = $reverse[1].'.zip';
    //     $search_term    = "%" . $reverse[1]."%";
    //     global $wpdb;
    //     $row = $wpdb->get_results( 
    //             $wpdb->prepare( 'SELECT * FROM  %1$sposts WHERE ((post_content LIKE "%2$s") and post_status = "publish") or ((post_content LIKE "%3$s") and post_status = "draft")', $wpdb->prefix, $search_term, $search_term)
    //     );
    // }

    // if ( !empty( $link ) ) {

    //     $fileArray      = explode( '/', $link );
    //     $reverse        = array_reverse( $fileArray );
    //     $zipFileName    = $reverse[1].'.zip';
    //     $search_term    = '%' . $reverse[1] . '%';
    //     global $wpdb;
    //     $row = $wpdb->get_results( 
    //         $wpdb->prepare( 
    //             'SELECT * FROM  ' . $wpdb->prefix . 'posts WHERE (post_content LIKE %s and post_status = "publish") or (post_content LIKE %s and post_status = "draft")', 
    //             $search_term, 
    //             $search_term 
    //         )
    //     );
    // }

    if ( !empty( $link ) ) {

        $fileArray      = explode( '/', $link );
        $reverse        = array_reverse( $fileArray );
        $zipFileName    = $reverse[1].'.zip';
        $search_term    = '%' . $reverse[1] . '%';
    
        // Use WP_Query to search for posts
        $args = array(
            's'              => $reverse[1], // The search term derived from the URL
            'post_status'    => array( 'publish', 'draft' ), // Search in both publish and draft statuses
            'posts_per_page' => -1, // Get all matching posts
        );
    
        $query = new WP_Query( $args );
    
        if ( $query->have_posts() ) {
            $row = $query->posts;
        } else {
            $row = array();
        }
    
        wp_reset_postdata(); // Reset the global post data after the query
    }
    
    

    // $html  = '<div class="errorMessage"  class="messageBox messageError"></div><p class="description">';
    // $html .= __( 'Please upload the package created by the SpreadsheetConverter. To create the package, go to Excel > SpreadsheetConverter Ribbon > Publish section > WordPress Plugin.', 'SpreadsheetConverter' );
    // $html .= '</p>';
    // $html .= '<div style="position:relative"><div class="uploadFileWrapper">        
    // <input id="uploadFile" placeholder="No zip selected" disabled="disabled" style="display:none;" />
    // <div class="fileUpload btn btn-primary">
    // <span class="button-primary">'. __('Choose Package' , 'SpreadsheetConverter') .'</span>
    // <input id="wp_custom_attachment1" name="wp_custom_attachment" type="file" class="upload" data-oldzip="'. $zipFileName .'" data-samezip="'. count($row) .'" />
    // </div></div>';

    // if ( !empty( $link ) ) {
        
    //     $html .= '<div id="fileNameZip" class="fileName">'. $zipFileName .'</div></div>
    //                 <div id="successMessage" class="messageBox messageSuccess update"></div>
    //                 <div id="errorMessage" class="messageBox messageError update"></div>';

    // } else {
        
    //     $html .= '<div id="fileNameZip" class="fileName"></div></div>
    //                 <div id="successMessage" class="messageBox messageSuccess"></div>
    //                 <div id="errorMessage" class="messageBox messageError"></div>';
    // }
       
    // if ( !empty( $link ) ) {
        
    //     $html .= '<input readonly type="text" id="wp_custom_attachment_hidden" name="wp_custom_attachment_hidden" value="'. $link .'" size="90" class="hidden" />';
    // }

    // // echo esc_html($html);
    // // echo esc_html(wp_strip_all_tags($html));
    // echo $html;

    echo '<div class="errorMessage" class="messageBox messageError"></div>';
    echo '<p class="description">' . esc_html__('Please upload the package created by the SpreadsheetConverter. To create the package, go to Excel > SpreadsheetConverter Ribbon > Publish section > WordPress Plugin.', 'SpreadsheetConverter') . '</p>';
    echo '<div style="position:relative"><div class="uploadFileWrapper">        
        <input id="uploadFile" placeholder="' . esc_attr__('No zip selected', 'SpreadsheetConverter') . '" disabled="disabled" style="display:none;" />
        <div class="fileUpload btn btn-primary">
        <span class="button-primary">' . esc_html__('Choose Package', 'SpreadsheetConverter') . '</span>
        <input id="wp_custom_attachment1" name="wp_custom_attachment" type="file" class="upload" data-oldzip="' . esc_attr($zipFileName) . '" data-samezip="' . esc_attr(count($row)) . '" />
        </div></div>';

    if (!empty($link)) {
        echo '<div id="fileNameZip" class="fileName">' . esc_html($zipFileName) . '</div></div>
                <div id="successMessage" class="messageBox messageSuccess update"></div>
                <div id="errorMessage" class="messageBox messageError update"></div>';
    } else {
        echo '<div id="fileNameZip" class="fileName"></div></div>
                <div id="successMessage" class="messageBox messageSuccess"></div>
                <div id="errorMessage" class="messageBox messageError"></div>';
    }

    if (!empty($link)) {
        echo '<input readonly type="text" id="wp_custom_attachment_hidden" name="wp_custom_attachment_hidden" value="' . esc_attr($link) . '" size="90" class="hidden" />';
    }

    // echo $html;
    // echo esc_html($html);
}

//sanitize upload file type and existence
function ims_fme_ssc_sanitize_doc( $file_url ) {
    
    $output = '';

    //check file type
    $file_type = wp_check_filetype( $file_url ); 

    $mime_type = $file_type['type'];
    if (  false !== strpos( $mime_type , 'text/html' ) ) :
        $output = $file_url;
    endif;

    return $output;

}

function isfme_wp_custom_shortcode( $object ) {
    $title   = get_the_title( $object->ID );
    $height  = intval(get_post_meta( $object->ID, 'wp_custom_attachment_height', true ));
    $width   = intval(get_post_meta( $object->ID, 'wp_custom_attachment_width', true ));
    $fileurl = get_post_meta( $object->ID, 'wp_custom_attachment', true );
    ?>
    <table id="hasIframe" style="width:<?php echo esc_attr($width).'px' ?>;height:<?php echo esc_attr($height).'px' ?>;"><tbody><tr><td style="border: 0px; padding: 0px;">
        <iframe src="<?php echo esc_url($fileurl); ?>" height="100%" width="100%"></iframe>
    </td></tr></tbody></table>
    <br/>
    <button id="button1" onclick="CopyToClipboard('<?php echo esc_js($object->ID); ?>');return false;">Click to Copy</button>
    <textarea id="<?php echo esc_attr($object->ID); ?>" style="display:none">
        <table id="hasIframe" style="width:<?php echo esc_attr($width).'px' ?>;height:<?php echo esc_attr($height).'px' ?>;">
            <tbody>
                <tr>
                    <td style="border: 0px; padding: 0px;">
                        <iframe src="<?php echo esc_url($fileurl); ?>" height="100%" width="100%"></iframe>
                    </td>
                </tr>
            </tbody>
        </table>
    </textarea>
    <div id="copysuccessMessage" class="messageBox messageSuccess"></div>
    <div id="copyerrorMessage" class="messageBox messageError"></div>
    <script type="text/javascript">
        function CopyToClipboard(containerid) {
            if (document.selection) {
                var range = document.body.createTextRange();
                range.moveToElementText(document.getElementById(containerid));
                range.select().createTextRange();
                document.execCommand("Copy");

            } else if (window.getSelection) {
                var range = document.createRange();
                document.getElementById(containerid).style.display = "block";
                range.selectNode(document.getElementById(containerid));
                window.getSelection().addRange(range);
                var successful = document.execCommand("Copy");
                document.getElementById(containerid).style.display = "none";
                var msg = successful ? 'successful' : 'unsuccessful';
                
                if( 'successful' == msg ) {
                  jQuery("#copysuccessMessage").html('<div class="message">Shorcode Copied Successfully</div>');
                } else {
                  jQuery("#copyerrorMessage").html('<div class="message">Error in Copying Shortcode</div>');
                }
                
            }
          }
    </script>
    <?php
}

add_action( 'save_post', 'ims_fme_ssc_save_custom_meta_data' );
function ims_fme_ssc_save_custom_meta_data( $id ) {
    global $wpdb;
    
    //security nonce verification
    // if( isset( $_POST['wp_custom_attachment_nonce'] ) ): 
    //     if ( !wp_verify_nonce( $_POST['wp_custom_attachment_nonce'] , plugin_basename(__FILE__) ) ) {
    //     return $id;
    //     }
    // endif;

    if( isset( $_POST['wp_custom_attachment_nonce'] ) ): 
        $nonce = sanitize_text_field( wp_unslash( $_POST['wp_custom_attachment_nonce'] ) ); // Unslash and sanitize the nonce
        if ( !wp_verify_nonce( $nonce , plugin_basename(__FILE__) ) ) {
            return $id;
        }
    endif;
    

    // verify if this is an auto save routine.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {            
        return $id;
    } 

    //permission check
    if ( !current_user_can( 'edit_page', $id ) ) {
        return $id;
    }

if ( !empty( $_FILES['wp_custom_attachment']['name'] ) ) {

    // Setup the array of supported file types. In this case, it's just PDF.
    $supported_types = array( 'application/zip' );
    $allowed_file_extenstions = array( 'htm', 'html', 'appcache', 'js', 'css', 'png', 'jpg', 'jpeg','gif','svg', 'bmp', 'woff', 'woff2', 'ttf', 'eot');

    // Get the file type of the upload
    // $arr_file_type  = wp_check_filetype( basename( $_FILES['wp_custom_attachment']['name'] ) );

    $arr_file_type = sanitize_file_name( wp_unslash( $_FILES['wp_custom_attachment']['name'] ) ); // Unslash and sanitize the file name
    $arr_file_type = wp_check_filetype( basename( $arr_file_type ) );


    $uploaded_type  = $arr_file_type['type'];
    $upload_dir     = wp_upload_dir();

    // Check if the type is supported. If not, throw an error.
    if ( in_array( $uploaded_type, $supported_types ) ) {

        //file name is compactible with sanitize_file_name() function
        //plugin functional requirement  
        // if( sanitize_file_name( $_FILES['wp_custom_attachment']['name'] ) != $_FILES['wp_custom_attachment']['name'] ){
            
        //     set_transient( 'ims-fme-ssc-admin-notice-plug-special-char' , $_FILES['wp_custom_attachment']['name']);
            
        //     // unhook this function to prevent infinite loop
        //     remove_action( 'save_post', 'ims_fme_ssc_save_custom_meta_data' );
        //     wp_update_post( array('ID' => intval( $id ), 'post_status' => 'draft') );
        //     add_action( 'save_post', 'ims_fme_ssc_save_custom_meta_data' );

        //     return $id;
        // }

        $folder_name    = basename( sanitize_file_name( $_FILES['wp_custom_attachment']['name'] ),".zip" );

        $pub_dir        = $upload_dir['basedir'] . '/ssc';
        $upload         = $upload_dir['baseurl'] . '/ssc/' . $folder_name . '/' . $folder_name . '.htm';

        //create directory of not found 
        if ( !is_dir( $pub_dir ) ) {
            wp_mkdir_p( $pub_dir );
        }
        
        // $source         = $_FILES['wp_custom_attachment']['tmp_name'];

        if ( isset( $_FILES['wp_custom_attachment']['tmp_name'] ) && !empty( $_FILES['wp_custom_attachment']['tmp_name'] ) ) {
            $source = sanitize_text_field($_FILES['wp_custom_attachment']['tmp_name']);
        } else {
            $source = '';
            ims_fme_ssc_errorLog( 'Temporary file name is missing.' );
            return;
        }
        
        $target_path    = $pub_dir."/". sanitize_file_name( $_FILES['wp_custom_attachment']['name'] );

        

        // Initialize the WordPress Filesystem API
        global $wp_filesystem;

        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        WP_Filesystem();

        // change this to the correct site path
        $path = $upload_dir['basedir'].'/ssc/'.$folder_name.'/'.$folder_name.'.htm';

        // if ( move_uploaded_file( $source, $target_path ) ) {  
        if ( $wp_filesystem->move( $source, $target_path ) ) { 

            $zip = new ZipArchive();
            $x = $zip->open($target_path);
            $disallowed_files = array();                         
            
            if ( $x === true ) {
                if (version_compare(phpversion(), '8.0.0', '<')) 
                {
                    // version 5-7 code
                    $zipall = zip_open( $target_path );
                    while ( ($zip_entry = zip_read($zipall)) ):
                        $pathall = zip_entry_name($zip_entry);
                        $extension = pathinfo($pathall, PATHINFO_EXTENSION);
                        if(!empty($extension) && !in_array(strtolower($extension), $allowed_file_extenstions)){
                            $disallowed_files[] = $extension;
                        }
                    endwhile;
                }
                else
                {
                    // version 8 code
                    for($i = 0; $i < $zip->numFiles; $i++)
                    {  
                        $pathall = $zip->getNameIndex( $i );
                        $extension = pathinfo($pathall, PATHINFO_EXTENSION);
                        if(!empty($extension) && !in_array(strtolower($extension), $allowed_file_extenstions)){
                        $disallowed_files[] = $extension;
                        }
                    }
                }

                $pathall = explode("/", $pathall);
                if ( $pathall[0] == $folder_name ) {
                    $pub_dir = $pub_dir;
                    $rename = false;
                }elseif($pathall[0] != $folder_name){
                        //$pub_dir = $pub_dir."/".$folder_name;
                        $pub_dir = $pub_dir;
                        $rename = true;


                }
                
                if(count($disallowed_files) > 0){
                    ims_fme_ssc_errorLog('The zip contains disallowed file types:' . implode(", ", array_unique($disallowed_files)));
                    set_transient( 'ims-fme-ssc-admin-notice-disallowed-file-error', implode(", ", array_unique($disallowed_files)), 5 );                                                   
                    
                    remove_action( 'save_post', 'ims_fme_ssc_save_custom_meta_data' );                               
                    wp_update_post( array('ID' => intval( $id ), 'post_status' => 'draft') );
                    add_action( 'save_post', 'ims_fme_ssc_save_custom_meta_data' );
                
                    $zip->close();
                    wp_delete_file($target_path);    

                    return;               
                }

                // change this to the correct site path
                $zip->extractTo( $pub_dir.'/');
                
                // if ( true === $rename ){
                //     //rename folder to sanitize name
                //     rename( $pub_dir .'/'. $pathall[0] , $pub_dir .'/'. sanitize_file_name( $pathall[0] ) );

                //     //reanme file to sanitize name
                //     if( file_exists( $pub_dir .'/'. sanitize_file_name( $pathall[0] ) .'/'. $pathall[0] . '.htm'  ) ) {
                //         $oldname = $pub_dir .'/'. sanitize_file_name( $pathall[0] ) .'/'. $pathall[0] . '.htm';
                //         $sanitize_name = $pub_dir .'/'. sanitize_file_name( $pathall[0] ) .'/'. sanitize_file_name( $pathall[0] ) . '.htm';
                //         copy( $oldname ,  $sanitize_name );
                //         wp_delete_file( $oldname );
                //     }

                // }

                if ( true === $rename ) {
                    global $wp_filesystem;
                
                    // Initialize the WordPress Filesystem API
                    if ( ! function_exists( 'WP_Filesystem' ) ) {
                        require_once( ABSPATH . 'wp-admin/includes/file.php' );
                    }
                
                    WP_Filesystem();
                
                    // Rename folder to sanitize name
                    $old_folder = $pub_dir . '/' . $pathall[0];
                    $new_folder = $pub_dir . '/' . sanitize_file_name( $pathall[0] );
                
                    if ( ! $wp_filesystem->move( $old_folder, $new_folder ) ) {
                        ims_fme_ssc_errorLog( 'Failed to rename folder: ' . $old_folder );
                    }
                
                    // Rename file to sanitize name
                    $old_file = $new_folder . '/' . $pathall[0] . '.htm';
                    $new_file = $new_folder . '/' . sanitize_file_name( $pathall[0] ) . '.htm';
                
                    if ( $wp_filesystem->exists( $old_file ) ) {
                        if ( $wp_filesystem->copy( $old_file, $new_file ) ) {
                            wp_delete_file( $old_file );
                        } else {
                            ims_fme_ssc_errorLog( 'Failed to rename file: ' . $old_file );
                        }
                    }
                }
                

                $zip->close();
                wp_delete_file($target_path);
            }
                        
            if ( file_exists( $path ) ){

                $upload         = $upload_dir['baseurl'] . '/ssc/' . $folder_name . '/' . str_replace(" ","%20",$folder_name) . '.htm';
                $upload_link    = $upload_dir['baseurl'] . '/ssc/' . str_replace(" ","%20",$folder_name) . '/' . str_replace(" ","%20",$folder_name) . '.htm';
                $absolute_path  = $upload_dir['basedir'] . '/ssc/' . str_replace(" ","%20",$folder_name) . '/' . str_replace(" ","%20",$folder_name) . '.htm';
            } else {

                $upload         =  $upload_dir['baseurl'] . '/ssc/' . $folder_name . '/index.htm';
                $upload_link    =  $upload_dir['baseurl'] . '/ssc/' . str_replace(" ","%20",$folder_name) . '/index.htm';
                $absolute_path  = $upload_dir['basedir'] . '/ssc/' . str_replace(" ","%20",$folder_name) . '/index.htm';
            }

            $input      =  $upload_dir['baseurl'] . '/ssc/' . str_replace(" ","%20",$folder_name) . '/insert-into-website.htm'; 
            // match plugin and spreadsheet converter version
            $address    = $upload_link;  
            $dir        = $upload_dir['basedir'] . '/ssc/' . $folder_name . '/';           
            // $inputData  = file_get_contents( $address );

            $inputData = wp_remote_get( $address );

            if ( is_wp_error( $inputData ) ) {
                // Handle the error. For example, log the error and return.
                ims_fme_ssc_errorLog( 'Failed to retrieve content from URL: ' . $address . ' - ' . $inputData->get_error_message() );
                return; // Exit or handle the error appropriately
            }

            $inputData = wp_remote_retrieve_body( $response );

            if ( $inputData ) {  
                    
                $regexp = "<input type='hidden' id='xl_client' name='xl_client' value='([^']+)' />";

                if ( preg_match_all( $regexp, $inputData, $matches, PREG_SET_ORDER ) ) { 
                    
                    $zip_file_version   = $matches[0][1];
                    $zip_file_version   = str_replace('x', '', $zip_file_version);
                    $zip_file_version   = explode('.',$zip_file_version);
                    $plugin_version     = ims_fme_ssc_shortcode_manager_version();
                    $plugin_version     = explode('.',$plugin_version);

                    if ( $plugin_version[0] < $zip_file_version[0] ) {

                        ims_fme_ssc_errorLog('You need version '.$zip_file_version[0].' of Wordpress Plugin for upload process');
                        set_transient( 'ims-fme-ssc-admin-notice-plug-error', $zip_file_version[0], 5 );
                        $prevent_publish = true;//Set to true if data was invalid.

                        if ( $prevent_publish ) {
                            // unhook this function to prevent indefinite loop
                            remove_action( 'save_post', 'ims_fme_ssc_save_custom_meta_data' );
                            // update the post to change post status
                            wp_update_post( array('ID' => intval( $id ), 'post_status' => 'draft') );
                            // re-hook this function again
                            add_action( 'save_post', 'ims_fme_ssc_save_custom_meta_data' );
                        }
                        ims_fme_ssc_removermdir($dir);
                        return false;
                        
                    }elseif( $plugin_version[0] > $zip_file_version[0] ) {

                        ims_fme_ssc_errorLog('You need version '.$plugin_version[0].' of SpreadsheetConverter to use this plugin');
                        set_transient( 'ims-fme-ssc-admin-notice-zip-error', $plugin_version[0], 5 );                       
                        $prevent_publish = true;//Set to true if data was invalid.

                        if ( $prevent_publish ) {
                            
                            remove_action( 'save_post', 'ims_fme_ssc_save_custom_meta_data' );                               
                            wp_update_post( array('ID' => intval( $id ), 'post_status' => 'draft') );
                            add_action( 'save_post', 'ims_fme_ssc_save_custom_meta_data' );
                        }
                    
                        ims_fme_ssc_removermdir($dir);
                        return false;

                    }
                } 
            }      
            
            // if ( $_POST['save'] || $_POST['publish'] ) {
            //         if(  '' == $_POST['post_title'] ) {
            //             update_post_meta($id, '_title', wp_strip_all_tags($folder_name));
            //             $wpdb->query( $wpdb->prepare("UPDATE $wpdb->posts SET post_title = %s WHERE ID = %d ", wp_strip_all_tags($folder_name),$id) );
            //         }                        
            // }

            // if ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) {
            //     if ( empty( $_POST['post_title'] ) ) {
            //         update_post_meta( $id, '_title', wp_strip_all_tags( $folder_name ) );
            //         $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_title = %s WHERE ID = %d ", wp_strip_all_tags( $folder_name ), $id ) );
            //     }
            // }

            if ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) {
                if ( empty( $_POST['post_title'] ) ) {
                    update_post_meta( $id, '_title', wp_strip_all_tags( $folder_name ) );
                    
                    $post_data = array(
                        'ID'         => $id,
                        'post_title' => wp_strip_all_tags( $folder_name ),
                    );
                    
                    wp_update_post( $post_data );
                }
            }
            
            // $height     = ims_fme_ssc_get_iframe_height( file_get_contents( $input) );

            $height     = wp_remote_get( $input );
            $inputData  = wp_remote_retrieve_body( $height );
            $height     = ims_fme_ssc_get_iframe_height( $inputData );

            // $width      = ims_fme_ssc_get_iframe_width( file_get_contents( $input) );
            $width      = wp_remote_get( $input );
            $inputData  = wp_remote_retrieve_body( $width );
            $width      = ims_fme_ssc_get_iframe_width( $inputData );

            
            $postID     = get_post_meta( $id, 'wp_custom_attachment',true );

            if ( $postID ) :
                $sanitized_upload = ims_fme_ssc_sanitize_doc( $upload );
                if ( !empty( $sanitized_upload ) ) :
                    update_post_meta( $id, 'wp_custom_attachment', $sanitized_upload );
                    update_post_meta( $id, 'wp_custom_attachment_height', intval($height[0]) );
                    update_post_meta( $id, 'wp_custom_attachment_width', intval($width[0]) );
                endif;
            else:
                $sanitized_upload = ims_fme_ssc_sanitize_doc( $upload );
                if ( !empty( $sanitized_upload ) ) :
                    add_post_meta( $id, 'wp_custom_attachment', $sanitized_upload );
                    add_post_meta( $id, 'wp_custom_attachment_height', intval($height[0]) );
                    add_post_meta( $id, 'wp_custom_attachment_width', intval($width[0]) );
                endif;
            endif;
    
        } else {
            ims_fme_ssc_errorLog('There was a problem with the upload.');
            wp_die("There was a problem with the upload. Please try again.");
        }

    } else {
        ims_fme_ssc_errorLog('The file type that you have uploaded is not a ZIP.');
        wp_die("The file type that you've uploaded is not a ZIP.");
    } // end if/else

    } // end if


} // end ims_fme_save_custom_meta_data    


/**
* Recursively removes a folder along with all its files and directories
* 
* @param String $path 
*/    
// function ims_fme_ssc_removermdir( $path ) {
//     // Open the source directory to read in files
//     $i = new DirectoryIterator( $path );
//     foreach( $i as $f ) {
//         if ( $f->isFile() ) {
//             wp_delete_file( $f->getRealPath() );
//         } else if( !$f->isDot() && $f->isDir() ) {
//             ims_fme_ssc_removermdir( $f->getRealPath() );
//         }
//     }
//     rmdir($path);
// }
function ims_fme_ssc_removermdir( $path ) {
    global $wp_filesystem;

    // Initialize the WordPress filesystem API
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }

    WP_Filesystem();

    // Open the source directory to read in files
    $i = new DirectoryIterator( $path );
    foreach( $i as $f ) {
        if ( $f->isFile() ) {
            wp_delete_file( $f->getRealPath() );
        } else if( !$f->isDot() && $f->isDir() ) {
            ims_fme_ssc_removermdir( $f->getRealPath() );
        }
    }

    // Use WP_Filesystem to delete the directory
    $wp_filesystem->delete( $path, true ); // The second argument true allows it to delete directories recursively.
}


add_action( 'admin_notices', 'ims_fme_ssc_check_version' );
function ims_fme_ssc_check_version() { 
   if ( get_transient( 'ims-fme-ssc-admin-notice-plug-error' ) ) { ?>
        <div class="updated" style="color:red"><p>You need version <?php echo esc_html(get_transient( 'ims-fme-ssc-admin-notice-plug-error' )); ?> of Wordpress Plugin for upload process</p></div>
    <?php
        delete_transient( 'ims-fme-ssc-admin-notice-plug-error' );

    } elseif( get_transient( 'ims-fme-ssc-admin-notice-zip-error' ) ) { ?>
        <div class="updated" style="color:red"><p>You need version <?php echo esc_html(get_transient( 'ims-fme-ssc-admin-notice-zip-error' )); ?> of SpreadsheetConverter to use this plugin</p></div>
    <?php
        delete_transient( 'ims-fme-ssc-admin-notice-zip-error' );

    } elseif( get_transient( 'ims-fme-ssc-admin-notice-disallowed-file-error' ) ) { ?>
        <div class="updated" style="color:red"><p>The zip contains disallowed file types: <?php echo esc_html(get_transient( 'ims-fme-ssc-admin-notice-disallowed-file-error' )); ?> </p></div>
    <?php
        delete_transient( 'ims-fme-ssc-admin-notice-disallowed-file-error' );
    
    }/*elseif( get_transient( 'ims-fme-ssc-admin-notice-plug-special-char' ) ) { ?>
        <div class="updated" style="color:red"><p>Invalid name <?php echo get_transient( 'ims-fme-ssc-admin-notice-plug-special-char' ); ?></p></div>
    <?php
        delete_transient( 'ims-fme-ssc-admin-notice-plug-special-char' );
    }*/

}

add_action('post_edit_form_tag', 'ims_fme_ssc_update_edit_form');
function ims_fme_ssc_update_edit_form() {
    echo ' enctype="multipart/form-data"';
}

/* change title text*/
add_filter( 'enter_title_here', 'ims_fme_ssc_wpb1_change_title_text' );
function ims_fme_ssc_wpb1_change_title_text( $title ) {

    $screen = get_current_screen();
    if  ( 'imsfmessc-file' == $screen->post_type ) {
    $title = __('Enter calculator name', 'SpreadsheetConverter');
    }
    return $title;

}



/**
* Returns current plugin version.
* 
* @return string Plugin version
*/
function ims_fme_ssc_shortcode_manager_version() {
    if ( function_exists( 'get_plugins' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
        $plugin_file = basename( ( __FILE__ ) );
        return $plugin_folder[$plugin_file]['Version'];
    }
}



add_action( 'edit_form_advanced', 'ims_fme_ssc_get_version' );
function ims_fme_ssc_get_version() {  
    $screen = get_current_screen();
        if  ( 'imsfmessc-file' == $screen->post_type ) {
            echo "<script type='text/javascript'>\n";
            echo "
              jQuery('#publish').click(function(){
                if (!jQuery('#wp_custom_attachment').find('#uploadFile').val() && !jQuery('#wp_custom_attachment').find('#wp_custom_attachment_hidden').val())
                {    
                  jQuery('[id^=\"wp_custom_attachment\"]').css('background', '#F96');
                  setTimeout(\"jQuery('#ajax-loading').css('visibility', 'hidden');\", 100);
                  alert('Please upload the zip file');
                  setTimeout(\"jQuery('#publish').removeClass('button-primary-disabled');\", 100);
                  return false;
                }
              });

            ";

            echo "</script>\n";

        }
}


/* get iframe width and height from sample page*/
function ims_fme_ssc_get_iframe_height( $input ) {
    preg_match_all( "/<iframe[^>]*height=[\"|']([^'\"]+)[\"|'][^>]*>/i", $input, $output );
    $return = array();
        if ( isset( $output[1][0] ) ) {
            $return = $output[1];
        }
    return $return;
}

function ims_fme_ssc_get_iframe_width( $input ) {
    preg_match_all( "/<iframe[^>]*width=[\"|']([^'\"]+)[\"|'][^>]*>/i", $input, $output );
    $return = array();
        if ( isset( $output[1][0] ) ) {
            $return = $output[1];
        }
    return $return;
}


// writing to the log file
// function ims_fme_ssc_errorLog( $message ){     
//     $dir        = dirname(__FILE__) . '/debug.txt';       
//     $time       = date( "F j, Y");
//     $message    = '['.$time.'] '.$message . "\n";      
//     $fp         = fopen( $dir, "a" );
//     fwrite( $fp, $message );
//     fclose( $fp ); 
// }

function ims_fme_ssc_errorLog( $message ){     
    global $wp_filesystem;

    // Initialize the WordPress Filesystem API
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }

    WP_Filesystem();

    $dir        = dirname(__FILE__) . '/debug.txt';       
    $time       = gmdate( "F j, Y" ); 
    $message    = '['.$time.'] '.$message . "\n";      

    // Use WP_Filesystem to handle file operations
    if ( ! $wp_filesystem->put_contents( $dir, $message, FS_CHMOD_FILE | FILE_APPEND ) ) {
        error_log( 'Failed to write to debug log: ' . $dir );
    }
}


function file_custom_columns( $columns ) {
    $columns['shortcode'] = __( 'Calculator Iframe' );
    $new                       = array();
    foreach ( $columns as $key => $value ) {
        if ( 'date' === $key ) {
            $new['shortcode'] = 'Shortcode';
        }
        $new[ $key ] = $value;
    }
    return $new;
}
add_filter( 'manage_edit-imsfmessc-file_columns', 'file_custom_columns' );

/**
 * Post Type Image
 *
 * @param string $column Column Name.
 * @param string $post_id Post ID.
 */
function file_custom_columns_data( $column, $post_id ) {
    switch ( $column ) {
        case 'shortcode':
            $title   = get_the_title( $post_id );
            $height  = intval(get_post_meta( $post_id, 'wp_custom_attachment_height', true ));
            $width   = intval(get_post_meta( $post_id, 'wp_custom_attachment_width', true ));
            $fileurl = get_post_meta( $post_id, 'wp_custom_attachment', true );
            ?>
            <!-- <input class="js-copytextarea" id="<?php //echo $post_id ?>" value="[calculator title='<?php //echo $title ?>' height='<?php //echo $height ?>' width='<?php //echo $width; ?>']" style="width:60%;opacity: 0.5;" readonly> -->
            <textarea class="js-copytextarea" id="<?php echo esc_attr($post_id); ?>" style="width:35%;height:100px;opacity: 0.5;" readonly>
                    <tbody>
                        <tr>
                            <td style="border: 0px; padding: 0px;">
                                <iframe src="<?php echo esc_url($fileurl); ?>" height="100%" width="100%"></iframe>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </textarea>
            <button class="js-textareacopybtn" data-clipboard-target="#<?php echo esc_attr($post_id) ?>">Click to Copy</button>
            <div id="successMessage-<?php echo esc_html( $post_id ); ?>" class="messageBox messageSuccess"></div>
            <div id="errorMessage-<?php echo esc_html( $post_id ); ?>" class="messageBox messageError"></div>
            <script type="text/javascript">
                jQuery(document).on('click', '.js-textareacopybtn', function(e) {
                    e.preventDefault();
                    var id = jQuery(this).data('clipboard-target');
                    id = id.split('#')[1];
                    var copyTextarea = jQuery(this).prev('.js-copytextarea');
                    copyTextarea.focus();
                      copyTextarea.select();

                      try {
                        var successful = document.execCommand('copy');
                        var msg = successful ? 'successful' : 'unsuccessful';
                        if( 'successful' == msg ) {
                          jQuery("#successMessage-" + id).html('<div class="message">Shorcode Copied Successfully</div>');
                        } else {
                          jQuery("#copyerrorMessage-" + id).html('<div class="message">Error in Copying Shortcode</div>');
                        }
                      } catch (err) {
                        jQuery("#copyerrorMessage-" + id).html('<div class="message">Error in Copying Shortcode</div>');
                      }
                  });
            </script>
            <?php
        break;
    }
}
add_action( 'manage_imsfmessc-file_posts_custom_column', 'file_custom_columns_data', 10, 2 );

if( ! is_admin() ) {
    add_shortcode( 'calculator', 'calculator_shortcode_function' );
    function calculator_shortcode_function( $atts ) {
        if(isset($atts['height']))
            $height = intval($atts['height']);
        if(isset($atts['width']))
            $width = intval($atts['width']);
        $arr = get_page_by_title( $atts['title'], $output, 'imsfmessc-file' );
        $postid = $arr->ID;
        $fileurl = get_post_meta($postid,'wp_custom_attachment',true);
        if( empty( $height ) )
            $height = intval(get_post_meta($postid,'wp_custom_attachment_height',true));
        if ( empty( $width ) )
            $width = intval(get_post_meta($postid,'wp_custom_attachment_width',true));
        //return '<div class="embed-responsive"><iframe class="embed-responsive-item" src="'.$fileurl.'" height="'.$height.'" width="'.$width.'"></iframe></div>';
        return '<table id="hasIframe" style="width: '.$width.'px; height: '.$height.'px;"><tbody><tr><td style="border: 0px; padding: 0px;">
        <iframe src="'.$fileurl.'" height="100%" width="100%"></iframe>
        </td></tr></tbody></table>';
    }
}