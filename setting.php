<?php
/*
 * View Content
 */
global $wpdb;
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    } 
?>
    <div class="notice" style="display: none;"></div>
    <div class="wrap">
        <div class="view-content-section" id="view_content_section">
            <div class="view-content">
                <h2>Delete Store</h2>
                <h4>Here is where the form would go if I actually had options.</h4>
            <form method="post">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1">Bulk Actions</option>
                            <option value="delete">Delete</option>
                        </select>
                        <input type="button" name="submit" id="submit" class="button action" value="Apply">
                    </div>
                    
                    <div class="tablenav-pages one-page">
                        <span class="displaying-num">1 item</span>
                        <span class="pagination-links">
                            <span class="tablenav-pages-navspan" aria-hidden="true">«</span>
                            <span class="tablenav-pages-navspan" aria-hidden="true">‹</span>
                            <span class="paging-input">
                                <label for="current-page-selector" class="screen-reader-text">Current Page</label>
                                    <input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging">
                                <span class="tablenav-paging-text"> of <span class="total-pages">1</span></span>
                            </span>
                            <span class="tablenav-pages-navspan" aria-hidden="true">›</span>
                            <span class="tablenav-pages-navspan" aria-hidden="true">»</span>
                        </span>
                    </div>
                    <br class="clear">
                </div>
                <table class="wp-list-table widefat fixed striped users">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th scope="col" id="username" class="manage-column column-name">Post Title</th>
                            <th scope="col" id="name" class="manage-column column-name">Post Content</th>
                            <th scope="col" id="post-type" class="manage-column column-name">Post Type</th>
                            <th scope="col" id="username" class="manage-column column-name">Author</th>
                            <th scope="col" id="role" class="manage-column column-role">Post Date</th>  
                        </tr>
                    </thead>
                    <tbody id="the-list" data-wp-lists="list:user">
                        <?php
                        global $wpdb; 
                        $getPostIDListArr = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."delete_id_list");
                        $getPostIDListFilter = array_filter($getPostIDListArr);
                        if(!empty($getPostIDListFilter)){
                            $array_list = '';
                            foreach ($getPostIDListArr as $key => $value) {
                                $array_list[] = $value->post_id;
                            }

                            $implode = implode(",", $array_list);
                            $query             = "SELECT * FROM ".$wpdb->prefix."delete_posts WHERE ID IN (".$implode.") AND post_status ='trash' ";
                            $total_query     = "SELECT COUNT(1) FROM (${query}) AS combined_table";
                            $total             = $wpdb->get_var( $total_query );
                            $items_per_page = 10;
                            $page             = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
                            $offset         = ( $page * $items_per_page ) - $items_per_page;
                            $getSinglePostIDListArr         = $wpdb->get_results(  $query . " ORDER BY ID DESC LIMIT ${offset}, ${items_per_page}" );
                            $totalPage         = ceil($total / $items_per_page);
                            if($totalPage >= 1){
                                $post_title = '';
                                $post_content = '';
                                $post_author = '';
                                $post_date = '';
                                $post_type = '';
                                foreach ($getSinglePostIDListArr as $key => $postList) {
                                    $postID = $postList->ID;
                                    $post_title = $postList->post_title;
                                    $post_content = substr( $postList->post_content,0,
                                        500);
                                    $author_list = get_user_by('id', $postList->post_author);
                                    $post_author_name = $author_list->data->user_nicename;
                                    if($post_author_name){
                                        $post_author = $post_author_name;
                                    }else{
                                        $post_author = " ";
                                    }
                                    $post_date = $postList->post_date;
                                    $post_type = $postList->post_type;

                                    ?>
                                    <tr id="post-<?php echo $postID; ?>">
                                        <th scope="row" class="check-column">
                                            <label class="screen-reader-text" for="user_1">Select admin</label>
                                            <input type="checkbox" name="users" id="user_<?php echo $postID; ?>" class="users" value="<?php echo $postID; ?>">
                                        </th>
                                        <td class="username column-username has-row-actions column-primary" data-colname="Username">
                                            <strong><a href="#"><?php echo $post_title; ?></a></strong><br>
                                            <div class="row-actions">
                                                <span class="edit">
                                                <a href="javascript:void(0);" class="restore_data" data-id="<?php echo $postID; ?>">Restore</a> | 
                                                </span>
                                                <span class="inline hide-if-no-js">
                                                    <a href="javascript:void(0);" class="editinline show_details" data-id="<?php echo $postID; ?>" aria-label="Quick edit “Test” inline">Show more details</a>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="name column-name" data-colname="Name"><?php echo $post_content; ?></td>
                                        <td class="name column-name" data-colname="Posttype"><?php echo $post_type; ?></td>
                                        <td class="email column-email" data-colname="Email">
                                            <a href="#"><?php echo get_the_author_meta('display_name', $postList->post_author); ?></a>
                                        </td>
                                        <td class="role column-role" data-colname="Role"><?php echo $post_date; ?></td>
                                    </tr>
                                   
                            <?php } ?>
                        <?php } }else{ ?>

                        <td class="email column-email" data-colname="Email">
                            <td><p><?php _e( 'No posts found.' ); ?></p></td>
                        </td>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" id="username" class="manage-column column-name">Post Title</th>
                        <th scope="col" id="name" class="manage-column column-name">Post Content</th>
                        <th scope="col" id="post-type" class="manage-column column-name">Post Type</th>
                        <th scope="col" id="username" class="manage-column column-name">Author</th>
                        <th scope="col" id="role" class="manage-column column-role">Post Date</th>  
                    </tr>
                    </tfoot>
                </table>
            </form>
            <?php if(isset($total) && !empty($total)){ ?>
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <span class="displaying-num"><?php echo $total; ?> items</span>
                        <span class="pagination-links">
                        <span class="screen-reader-text">Current Page</span>
                            <span id="table-paging" class="paging-input">
                                <span class="tablenav-paging-text"><?php echo $page; ?> of <span class="total-pages"><?php echo $totalPage; ?></span>
                            </span>
                        </span>
                    </div>
                    <div class="tablenav-pages">
                        <?php echo  paginate_links( array('base' => add_query_arg( 'cpage', '%#%' ),'format' => '','prev_text' => __('&laquo;'),'next_text' => __('&raquo;'),'total' => $totalPage,'current' => $page)); ?>
                    </div>
                    <br class="clear">
                </div>
            <?php } ?>
            </div>
        </div>
        <div class="show-content-section" id="show_content_section"></div>
    </div>
    <?php ?>