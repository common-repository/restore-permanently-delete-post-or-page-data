<?php
/**
 * Plugin Name: Restore Permanently delete Post or Page Data
 * Plugin URL: http://wordpress.org/plugins/restore-permanently-delete-post-and-page-data
 * Description:  This plugin will integrate to restore permaently delete post and page all details in store.
 * Version: 1.0
 * Author: David Pokorny
 * Author URI: http://www.wpbuilderweb.com/
 * Developer: The Wpbuilderweb Team
 * Developer E-Mail: pokornydavid4@gmail.com
 * Text Domain: delete-Post-or-page-data
 * Domain Path: /languages
 * 
 * Copyright: © 2009-2015 Wpbuilderweb.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
  * Deactivate and activate plugin
  */ 
define( 'RP_DPO_DPA_PATH', plugin_dir_path( __FILE__ ) );
define( 'RP_DPO_DPA_NAME', plugin_basename( __FILE__ ) );

$scporder = new RP_DPO_DPA_Engine();

class RP_DPO_DPA_Engine {
    function __construct() {
        if (!get_option('rp_dpo_dpa_activate'))
            $this->rp_dpo_dpa_activate();

        add_action('admin_menu', array($this, 'rp_dpo_dpa_admin_menu'));
        add_action('admin_init', array($this, 'rp_dpo_dpa_load_scripts'));
        add_action( 'before_delete_post', array($this,'rp_dpo_dpa_deleted_post' ));
        add_action('wp_ajax_dp_restore_data', array(&$this, 'rp_dpo_dpa_ajax_dp_restore_data'));
        add_action('wp_ajax_nopriv_dp_restore_data', array(&$this, 'rp_dpo_dpa_ajax_dp_restore_data'));
        add_action('wp_ajax_dp_show_details', array(&$this, 'rp_dpo_dpa_ajax_dp_show_details'));
        add_action('wp_ajax_nopriv_dp_show_details', array(&$this, 'rp_dpo_dpa_ajax_dp_show_details'));
        add_action('wp_ajax_dp_delete_data', array(&$this, 'rp_dpo_dpa_ajax_dp_delete_data'));
        add_action('wp_ajax_nopriv_dp_delete_data', array(&$this, 'rp_dpo_dpa_ajax_dp_delete_data'));
    }
	
	/* Activated Plugin */
    
	function rp_dpo_dpa_activate(){
    	 //Check if Contact Form 7 is active and add table to database for elavon extension
	    global $wpdb;
	    $table_posts = $wpdb->prefix.'delete_posts';
	    $table_id_list = $wpdb->prefix.'delete_id_list';
	    $table_postmeta = $wpdb->prefix.'delete_postmeta';
		if($wpdb->get_var("SHOW TABLES LIKE '$table_posts'") != $table_posts){
	    	$sql = "CREATE TABLE ". $wpdb->prefix ."delete_posts LIKE ". $wpdb->prefix ."posts";
	    	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
	    }
	    if($wpdb->get_var("SHOW TABLES LIKE '$table_id_list'") != $table_id_list) {
		    $charset_collate = $wpdb->get_charset_collate();
		    $table_name = $wpdb->prefix .'delete_id_list';
		    $sql = "CREATE TABLE $table_name (
		      id bigint(20) NOT NULL AUTO_INCREMENT,
		      d_post_id bigint(20) NOT NULL,
		      post_id  bigint(20) NOT NULL,
		      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		      PRIMARY KEY  (id)
		    ) $charset_collate;";  
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
		if($wpdb->get_var("SHOW TABLES LIKE '$table_postmeta'") != $table_postmeta) {
	    	$sql =  "CREATE TABLE ".$wpdb->prefix ."delete_postmeta LIKE ".$wpdb->prefix ."postmeta";
	    	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
    	}
    }

	/* Add menu in sidebar */
	
	function rp_dpo_dpa_admin_menu() {
    	add_menu_page( 'My Delete Store Options', 'Delete Store', 'manage_options', 'rp_dpo_dpa_store', array($this, 'rp_dpo_dpa_admin_page') );
    }
	
	function rp_dpo_dpa_admin_page(){
        require RP_DPO_DPA_PATH . "setting.php";
    }

	/* Load script or css */
	
    function rp_dpo_dpa_load_scripts() {
	    wp_enqueue_script('jquery');
	    wp_localize_script( 'jquery', 'deletedp', 
	    	array(
	    		'ajax_url' 	=> admin_url( 'admin-ajax.php' ),
	    		'site_url' 	=> site_url(),
	    		'admin_url' => admin_url('admin.php?page=rp_dpo_dpa_store')
	    	)
	    );
		wp_enqueue_script( 'deletep_script', plugins_url( 'assets/js/script.js', __FILE__ ), null, true );
    }

    /* Post or Page delete before get content */
	
	function rp_dpo_dpa_deleted_post($post_id){
		global $wpdb;
		$post_content_list = get_post($post_id);
		$post_field = array('ID','post_author','post_date','post_date_gmt','post_content','post_title','post_excerpt','post_status','comment_status','ping_status','post_password','post_name','to_ping','pinged','post_modified','post_modified_gmt','post_content_filtered','post_parent','guid','menu_order','post_type','post_mime_type','comment_count');
		$post_meta_fields = array('post_id', 'meta_key', 'meta_value');
		$new_post_field_key_arr = array();
		$old_post_field_key_arr = array();
		foreach ($post_content_list as $key => $value) {
			if(in_array($key,$post_field)){
				if($key=='ID'){
					$new_post_field_key_arr[] = 'ID';
				}else{
					$new_post_field_key_arr[] = $key;
				}
					$old_post_field_key_arr[] = $key;
			}
		}
			
		$new_post_field_list = implode(",",$new_post_field_key_arr);
		$old_post_fields_list = implode(", ",$old_post_field_key_arr);
		$post_meta_fields_list = implode(", ",$post_meta_fields);

		$rowsCount = $wpdb->get_var($wpdb->prepare ("SELECT COUNT(*) FROM ".$wpdb->prefix."delete_id_list WHERE post_id = %d ", $post_id));
		if($rowsCount >= 1){
			$getPostList = $wpdb->get_results($wpdb->prepare ("SELECT * FROM ".$wpdb->prefix."delete_posts WHERE post_id = %d", $post_id) );
			$postDetail = get_post($post_id);
			$data = array(
				'post_author' => $postDetail->post_author,
				'post_date' => $postDetail->post_date,
				'post_date_gmt' => $postDetail->post_date_gmt,
				'post_content' => $postDetail->post_content,
				'post_title' => $postDetail->post_title,
				'post_excerpt' => $postDetail->post_excerpt, 
				'post_status' => $postDetail->post_status, 
				'comment_status' => $postDetail->comment_status,
				'ping_status' => $postDetail->ping_status,
				'post_password' => $postDetail->post_password,
				'post_name' => $postDetail->post_name,
				'to_ping' => $postDetail->to_ping,
				'pinged' => $postDetail->pinged,
				'post_modified' => $postDetail->post_modified,
				'post_modified_gmt' => $postDetail->post_modified_gmt,
				'post_content_filtered' => $postDetail->post_content_filtered,
				'post_parent' => $postDetail->post_parent,
				'guid' => $postDetail->guid,
				'menu_order' => $postDetail->menu_order, 
				'post_type' => $postDetail->post_type,
				'post_mime_type' => $postDetail->post_mime_type,
				'comment_count' => $postDetail->comment_count,
				);
			$where = array('ID' => $post_id);

			$wpdb->update( $wpdb->prefix.'delete_posts' , $data, $where, $format = null, $where_format = null );

			$wpdb->query($wpdb->prepare ("DELETE FROM ".$wpdb->prefix."delete_postmeta  WHERE post_id = %d ",$post_id));
			//Insert post meta

			$thumbnail = get_post_meta( $post_id, '_thumbnail_id',true);

			$wpdb->query($wpdb->prepare ( "INSERT INTO ".$wpdb->prefix."delete_postmeta ( ".$post_meta_fields_list." ) SELECT ".$post_meta_fields_list." FROM ".$wpdb->prefix."postmeta WHERE post_id='%d' OR post_id='%d' ", $post_id, $thumbnail));
			
			//Thumbnail
			
			$thumbnail_count = $wpdb->get_var( $wpdb->prepare ("SELECT COUNT(*) FROM ".$wpdb->prefix."delete_postmeta WHERE post_id = '%d' AND meta_key='%s' ",$post_id,'_thumbnail_id') );
				if($thumbnail_count == 1){
					$wpdb->query( $wpdb->prepare ( "INSERT INTO ".$wpdb->prefix."delete_posts ( ".$new_post_field_list." ) SELECT ".$old_post_fields_list." FROM ".$wpdb->prefix."posts WHERE ID='%d'", $thumbnail_count[0]->meta_value ) );
				}
		}else{

			//Insert post data
			$wpdb->query( $wpdb->prepare ( "INSERT INTO ".$wpdb->prefix."delete_posts (".$new_post_field_list.") SELECT ".$old_post_fields_list."  FROM ".$wpdb->prefix."posts WHERE ID = '%d' ", $post_id ) );
			$last_inserted_id = $wpdb->insert_id;

			if($last_inserted_id){
				$wpdb->query( $wpdb->prepare ( "INSERT INTO ".$wpdb->prefix."delete_id_list (`d_post_id`,`post_id`) VALUES ( %s , %d ) ", $last_inserted_id, $post_id) );
				
				$thumbnail = get_post_meta( $post_id, '_thumbnail_id', true );

				//Insert post metaecho 
			    $wpdb->query( $wpdb->prepare (  "INSERT INTO ".$wpdb->prefix."delete_postmeta ( ".$post_meta_fields_list." ) SELECT ".$post_meta_fields_list." FROM ".$wpdb->prefix."postmeta WHERE post_id='%d'  OR post_id='%d' ", $post_id, $thumbnail ));
			}
			//Thumbnail

			$thumbnail_count = $wpdb->get_var( $wpdb->prepare ("SELECT COUNT(*) FROM ".$wpdb->prefix."delete_postmeta WHERE post_id = '%d' AND meta_key='%s' ",$post_id,'_thumbnail_id') );
			if($thumbnail_count == 1){
				$wpdb->query( $wpdb->prepare ( "INSERT INTO ".$wpdb->prefix."delete_posts ( ".$new_post_field_list." ) SELECT ".$old_post_fields_list." FROM ".$wpdb->prefix."posts WHERE ID='%d'", $thumbnail_count[0]->meta_value ) );
			}	
		}
	}

    /* Show Single post or page details */
	
	function rp_dpo_dpa_ajax_dp_show_details(){
    	global $wpdb;
    	if(sanitize_text_field($_POST['id'])){
    		$id = sanitize_text_field($_POST['id']);
	    	$postIDListArr = $wpdb->get_results( $wpdb->prepare ( " SELECT * FROM ".$wpdb->prefix."delete_posts Where post_status = %s AND ID ='%d' ",'trash',$id) );
	        $postMetaIDListArr = $wpdb->get_results( $wpdb->prepare (" SELECT * FROM ".$wpdb->prefix."delete_postmeta Where  post_id ='%d' ", $id) );  
	    ?>
	        <div class="showdetails">
	            <?php if ( $postIDListArr ) { ?>
	            <h2>View Details</h2>
	            <div class="post-content-list">
	                <?php if($id){ ?>
	                    <h3>Here show Post Or Page id <i><u><?php echo $id; ?></u></i> and below display Post Content List.</h3>
	                <?php } ?>
	                <table class="wp-list-table widefat post-type-listing">
	                    <thead>
	                        <tr>
	                            <th>Type Post / Page No</th>
	                            <th>Field Name</th>
	                            <th>Description</th>
	                        </tr>
	                    </thead>
	                    <tbody>
	                        <?php 
	                        $k = 1;
	                        foreach ($postIDListArr as $key => $postValue) { ?>
	                            <?php $array = get_object_vars($postValue); 
	                            foreach ($array as $key => $value) {
	                                if($key != 'post_type' ){ ?>
	                                    <tr class="alternate">
	                                        <td><?php if($key == 'ID'){ echo  $k; } ?></td> 
	                                        <td><?php echo $key; ?></td>
	                                        <td ><?php echo $value; ?></td>
	                                    </tr>
	                                <?php  } ?>
	                            <?php } ?>
	                        <?php } wp_reset_postdata(); ?> 
	                    </tbody>
	                    <tfoot>
	                    <tr>
	                        <th>Type Post / Page No</th>
	                        <th>Field Name</th>
	                        <th>Description</th>
	                    </tr>
	                    </tfoot>
	                </table>
	            </div>
	            <?php }else{ ?>
	            <p><?php _e( 'Sorry, no posts matched your criteria.' ); ?></p>
	            <?php } ?>
	        </div>
	    <?php
			}
    	die();	
    }

    /* Restore Data Ajax */
	
	function rp_dpo_dpa_ajax_dp_restore_data(){
    	global $wpdb;
    	$getPostID = sanitize_text_field($_POST['id']);
    	$results = 'false';
    	if($getPostID)
    	{
	        $rowsCount = $wpdb->get_var( $wpdb->prepare ("SELECT COUNT(*) FROM ".$wpdb->prefix."delete_id_list WHERE post_id = %d ",$getPostID));
	        if($rowsCount == 1)
	        {
	            $getPostListArr = $wpdb->get_results( $wpdb->prepare ( "SELECT * FROM ".$wpdb->prefix."delete_posts WHERE ID = %d ",$getPostID ) );	
	            $post_content_list = $getPostListArr[0];
	            $post_field = array('ID','post_author','post_date','post_date_gmt','post_content','post_title','post_excerpt','post_status','comment_status','ping_status','post_password','post_name','to_ping','pinged','post_modified','post_modified_gmt','post_content_filtered','post_parent','guid','menu_order','post_type','post_mime_type','comment_count');
	            $post_meta_fields = array('post_id', 'meta_key', 'meta_value');
	            $new_post_field_key_arr = array();
	            $old_post_field_key_arr = array();
	            foreach ($post_content_list as $key => $value)
	            {
	                if(in_array($key,$post_field)){
	                    if($key=='ID'){
	                        $new_post_field_key_arr[] = 'ID';
	                    }else{
	                        $new_post_field_key_arr[] = $key;
	                    }
	                        $old_post_field_key_arr[] = $key;
	                }
	            }
	            
	            $new_post_field_list = implode(",",$new_post_field_key_arr);
	            $old_post_fields_list = implode(", ",$old_post_field_key_arr);
	            $post_meta_fields_list = implode(", ",$post_meta_fields);

	              $wpdb->query(  $wpdb->prepare ( "INSERT INTO ".$wpdb->prefix."posts (".$new_post_field_list.") SELECT ".$old_post_fields_list." FROM ".$wpdb->prefix."delete_posts WHERE ID='%d'",$getPostID) );

	            $thumbnail = get_post_meta( $getPostID, '_thumbnail_id', true );

	            //Insert post metaecho 
	            $wpdb->query( $wpdb->prepare ( "INSERT INTO ".$wpdb->prefix."postmeta (".$post_meta_fields_list.") SELECT ".$post_meta_fields_list." FROM ".$wpdb->prefix."delete_postmeta WHERE post_id='%d'  OR post_id='%d' ", $getPostID, $thumbnail ));
	            if($wpdb->insert_id)
	            {
	                $wpdb->query( $wpdb->prepare ( "DELETE FROM ".$wpdb->prefix."delete_postmeta  WHERE post_id ='%d'",$getPostID) );
	                $wpdb->query( $wpdb->prepare ( "DELETE FROM ".$wpdb->prefix."delete_posts  WHERE ID ='%d'",$getPostID) );
	                $wpdb->query( $wpdb->prepare ( "DELETE FROM ".$wpdb->prefix."delete_id_list  WHERE post_id ='%d'",$getPostID) );
	            	$results = 'true';
	            }

	    	}
        }
        echo $results;
    	die();
    }

    /* Delete Data Ajax */
	
	function rp_dpo_dpa_ajax_dp_delete_data(){
    	global $wpdb;
    	$result = 'false';
    	$userArry = json_decode(stripslashes($_POST['users']),true);
    	$array_filter = array_filter($userArry);
    	if(!empty($array_filter))
    	{
	    	foreach ($userArry as $userArry_value)
	    	{
	    		foreach ($userArry_value as $single_user_key => $single_user_id)
	    		{
		    		if($single_user_key  == 'value'){
		    			$getPostID = $single_user_id;
			    		$wpdb->query( $wpdb->prepare ( "DELETE FROM ".$wpdb->prefix."delete_postmeta  WHERE post_id ='%d'",$getPostID) );
		                $wpdb->query( $wpdb->prepare ( "DELETE FROM ".$wpdb->prefix."delete_posts  WHERE ID ='%d'",$getPostID) );
		                $wpdb->query( $wpdb->prepare ( "DELETE FROM ".$wpdb->prefix."delete_id_list  WHERE post_id ='%d'",$getPostID) );
		        	}	
	    		}
	    		$result = 'true';
	    	}
    	}
    	echo $result;
    	die();
    }
}

/**
 * Deactivated Plugin
 */

register_deactivation_hook(__FILE__, 'rp_dpo_dpa_delete_store_deactivate');
function rp_dpo_dpa_delete_store_deactivate(){
	global $wpdb;
		$drop_posts = $wpdb->query("DROP TABLE ".$wpdb->prefix."delete_posts");
		$drop_id_list = $wpdb->query("DROP TABLE ".$wpdb->prefix."delete_id_list");
		$drop_postmeta = $wpdb->query("DROP TABLE ".$wpdb->prefix."delete_postmeta");
	delete_option('rp_dpo_dpa_activate');
}

?>