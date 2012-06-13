<?php
/*
 Plugin Name: Category Sticky Posts
 Plugin URI: http://www.workinginboxershorts.com/wordpress-custom-taglines
 Description: Set sticky posts for individual category archives
 Author: Brian Zeligson
 Version: 0.13
 Author URI: http://www.workinginboxershorts.com

 ==
 Copyright 2011 - present date  Brian Zeligson 

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

   // Determine plugin directory. Accomodate for symlinks
    $parts = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
    $filename = basename(__FILE__);
    unset($parts[array_search($filename, $parts)]);
    $dir = array_pop($parts);
    
    // Define constants
    define('BZ_STICKY_CATEGORY_PLUGIN_DIR',         path_join(WP_PLUGIN_DIR, $dir));
    define('BZ_STICKY_CATEGORY_PLUGIN_URL',         path_join(WP_PLUGIN_URL, $dir));
    define('BZ_STICKY_JQMS_PLUGIN_DIR',	    	    path_join(BZ_STICKY_CATEGORY_PLUGIN_DIR, 'wp_jq_multiselect'));
    define('BZ_STICKY_CATEGORY_PLUGIN_JS_URL',      path_join(BZ_STICKY_CATEGORY_PLUGIN_URL, 'js'));
    define('BZ_STICKY_CATEGORY_PLUGIN_VIEWS_DIR',   path_join(BZ_STICKY_CATEGORY_PLUGIN_DIR, 'views'));
    define('WPBZCSP', 'bz_sticky_categories');
    include_once(path_join(BZ_STICKY_JQMS_PLUGIN_DIR, 'class.wpjqmultiselect.php'));

class bz_category_sticky
{
    
    protected $bz_sticky_categories;
    protected $wp_site_categories;
    
    public function __construct()
    {
        $this->bz_sticky_categories = get_option(WPBZCSP);
        $this->wp_site_categories = get_terms( 'category', 'orderby=count&hide_empty=0' );
        register_activation_hook( __FILE__, array($this, 'bz_category_sticky_is_on') );
        register_uninstall_hook( __FILE__, array($this, 'bz_category_sticky_is_off') );
	$wpjqms = new WpJqMultiSelect(array('.bz-category-sticky-multiselect'), true );
        add_action( 'admin_init', array($this, 'bz_category_sticky_add_custom_box'), 1 );
        add_action( 'save_post', array($this, 'bz_category_sticky_save_postdata') );
	add_filter('post_class', array($this, 'bz_category_sticky_add_sticky_class'));
	add_filter( 'the_posts', array($this, 'bz_category_sticky_filter_output'), 1);
    }

    public function bz_category_sticky_is_on()
    {
        if (!is_array($this->bz_sticky_categories) or empty($this->bz_sticky_categories))
        {
            $this->bz_sticky_categories = array();
            foreach ($this->wp_site_categories as $category) $this->bz_sticky_categories[$category->term_id] = array();
            update_option(WPBZCSP, $this->bz_sticky_categories);
        }
    }

    public function bz_category_sticky_is_off()
    {
        if (get_option(WPBZCSP)) delete_option(WPBZCSP);
    }

    public function bz_category_sticky_add_custom_box()
    {
        add_meta_box( 
            'bz_category_sticky_sectionid',
            __( 'Category Sticky', 'bz_category_sticky_textdomain' ),
            array($this, 'bz_category_sticky_inner_custom_box'),
            'post' 
        );
    }


    public function bz_category_sticky_inner_custom_box()
    {
	global $post;
      // Use nonce for verification
      wp_nonce_field( plugin_basename( __FILE__ ), 'bz_category_sticky_nonce' );
    
      // The actual fields for data entry
      echo '<div class="bz-wp-multiselect" style="height:240px;">';
      
      echo '<label for="bz_sticky_categories">';
           _e("Choose categories where this post should be sticky:", 'bz_category_sticky_textdomain' );
      echo '</label> <br /><br />';
      echo '<select multiple="multiple" style="width:700px;" name="bz_post_sticky_categories[]" class="bz-category-sticky-multiselect">';
      foreach ($this->wp_site_categories as $category) {echo '<option value="'.$category->term_id.'"';  if ($this->bz_sticky_categories[$category->term_id][$post->ID] == 'sticky') echo ' selected="selected" '; echo '>'.$category->name.'</option>'."\n";}
      echo '</select>';
      echo '</div>';
      }

    function bz_category_sticky_save_postdata( )
    {
	global $post;
      // verify if this is an auto save routine. 
      // If it is our form has not been submitted, so we dont want to do anything
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
          return;
    
      // verify this came from the our screen and with proper authorization,
      // because save_post can be triggered at other times
    
      if ( !wp_verify_nonce( $_POST['bz_category_sticky_nonce'], plugin_basename( __FILE__ ) ) )
          return;
    
      
      // Check permissions
      if ( 'page' == $_POST['post_type'] ) 
      {
        if ( !current_user_can( 'edit_page', $post->ID ) )
            return;
      }
      else
      {
        if ( !current_user_can( 'edit_post', $post->ID ) )
            return;
      }
    
      // OK, we're authenticated: we need to find and save the data
    
      $bz_post_is_category_stickied = $_POST['bz_category_sticky_enabled'];
      $bz_post_sticky_categories = $_POST['bz_post_sticky_categories'];
      
      if (is_array($bz_post_sticky_categories)) {foreach ($bz_post_sticky_categories as $bz_post_sticky_category) $this->bz_sticky_categories[$bz_post_sticky_category][$post->ID] = 'sticky';}
      if (is_array($this->bz_sticky_categories)) {foreach ($this->bz_sticky_categories as $key => $bz_sticky_category) if (isset($bz_sticky_category[$post->ID]) and !in_array($key, $bz_post_sticky_categories)) unset($this->bz_sticky_categories[$key][$post->ID]);}
      update_option(WPBZCSP, $this->bz_sticky_categories);
    }
    
    function bz_category_sticky_add_sticky_class($classes)
    {
	global $post;
	if (property_exists($post, 'sticky_in_cat')) $classes[] = 'category_sticky_post';
	return $classes;
    }
    
    public function bz_category_sticky_filter_output($posts)
    {
	if (!is_category()) return $posts;
	global $wp_query;
	$cat_obj = $wp_query->get_queried_object();
	if (!is_array($this->bz_sticky_categories[$cat_obj->term_id])) return $posts;
	foreach($this->bz_sticky_categories[$cat_obj->term_id] as $bz_cat_sticky_post => $val) 
	{
		$sticky_post = get_post($bz_cat_sticky_post);
		$sticky_post->sticky_in_cat = true;
		if ($sticky_post->post_status === 'publish') $newposts[] = $sticky_post;
	}
	foreach($posts as $post) if (!isset($this->bz_sticky_categories[$cat_obj->term_id][$post->ID])) $newposts[] = $post;
	return $newposts;
    }

}

$bz_category_sticky = new bz_category_sticky();