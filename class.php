<?php
/*  Copyright 2011  Rob Holmes  (email : rob@onemanonelaptop.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if (!class_exists('Cwantwm')) {
abstract class Cwantwm {
	function Cwantwm() {
		$this->__construct();	
	} // function

	
	function __construct() {
		// Plugin Specific construction
		$this->plugin_construct();	
		$this->filename = $this->name . "/" . $this->name . ".php";
		$this->url = WP_PLUGIN_URL.'/'.str_replace(basename( $this->page),"",plugin_basename($this->page));
		
		// Default widgets
		$this->yesno = array ('1'=>'Yes','0'=>'No');
		
		// Store publicly availbale post types
		$this->post_types=get_post_types(array('public'   => true,'_builtin' => false),'names','and'); 
		$this->post_types['post'] = 'post';	$this->post_types['page'] = 'page';
		
		// Add the options page
		add_action('admin_menu',  array(&$this, 'plugin_add_options_page'));

		// Admin page hooks
		add_action('admin_init', array(&$this,  'plugin_admin_init') );
	
		// Set plugin page to two columns
		add_filter('screen_layout_columns', array(&$this, 'layout_columns'), 10, 2);
		
		// Activation and deactivation hooks
		register_activation_hook($this->page, array(&$this,'plugin_activate'));
		register_deactivation_hook($this->page,  array(&$this,'plugin_deactivate'));
		
		
	} // function
	
	function plugin_admin_scripts() {
		// add the plugins admin.js file
		wp_register_script('cwantwm',  WP_PLUGIN_URL.'/cwantwm/' .'admin.js', array('jquery','media-upload','thickbox','editor'));
		wp_enqueue_script('cwantwm');
		
		// wysiwyg editor
		wp_tiny_mce( false );	
	} // function

	function plugin_admin_styles() {
		// used by media upload
		wp_enqueue_style('thickbox');
		
		wp_register_style('cwantwm',WP_PLUGIN_URL.'/cwantwm/' .'admin.css');
		wp_enqueue_style('cwantwm');
	} // function
	

	
	function plugin_deactivate() {
       delete_option($this->options);
	} // function

	
	function plugin_activate() {
		// Define default option settings
		$options = get_option($this->options);
		$defaults = $this->plugin_defaults();
		update_option($this->options, $defaults);	
	} // function

	function plugin_admin_init() {
		// Add custom scripts and styles to this page only
		add_action('admin_print_scripts-' . $this->page, array(&$this, 'plugin_admin_scripts'));
		add_action('admin_print_styles-' . $this->page,array(&$this,  'plugin_admin_styles'));
	
		// Register the options array and define the validation callback
		register_setting( $this->options, $this->options,array(&$this, 'plugin_validate_options' ));
		
		//  Define the sidebar meta boxes
		add_meta_box('admin-section-support','Support', array(&$this, 'cwantwm_support'), $this->page, 'side', 'core',array('section' => 'admin_section_support'));
		//add_meta_box('admin-section-forum','Recent Forum Posts', array(&$this, 'cwantwm_forum'), $this->page, 'side', 'core',array('section' => 'admin_section_forum'));
		add_meta_box('admin-section-tweets','Recent Tweets', array(&$this, 'cwantwm_tweets'), $this->page, 'side', 'core',array('section' => 'admin_section_tweets'));
		
		
		// Do some plugin specific stuff
		$this->plugin_initiate();
	} // function

	// Add menu page
	function plugin_add_options_page() {
		$this->page = add_options_page($this->title , $this->title , 'manage_options',  $this->filename ,  array(&$this, 'plugin_settings_form'));
		add_action('load-'.$this->page,  array(&$this, 'plugin_load_page'));
		add_filter( 'plugin_action_links', array(&$this, 'plugin_add_settings_link'), 10, 2 );

	} // function
	
	// Add a settings link to the plugin list page
	function plugin_add_settings_link($links, $file) {
	
		if ( $file ==  $this->filename  ){
			$settings_link = '<a href="options-general.php?page=' .$this->filename . '">' . __('Settings') . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	} // function
	
	// Validation
	function plugin_validate_options($opts){ 
		return $opts;
	} // function

	function layout_columns($columns, $screen) {
		if ($screen == $this->page) {
			$columns[$this->page] = 2;
		}
		return $columns;
	} // function
	
	// Null Callback for the section creation
	function section_cb () {}
	
	
	// post meta box builder
	function post_meta_builder($data,$args) {
		global $post;
		$fields=$args['args']['fields'] ;
		foreach( $fields as $meta_box) {
			$meta_box_value = get_post_meta($post->ID, $meta_box['name'].'_value', true);
 		
			if($meta_box_value == "") {	$meta_box_value = $meta_box['std']; };
 
			echo'<input type="hidden" name="'.$meta_box['name'].'_noncename" id="'.$meta_box['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
	
			echo "<table class='form-table'><tr><th scope='row'><atrong>" . $meta_box['title'] . "</atrong></th><td>";		
			if ($meta_box['type'] == 'textarea') {
 				echo'<textarea  name="'.$meta_box['name'].'_value" >' . $meta_box_value . '</textarea>';
			} else if ($meta_box['type'] == 'text') {
 				echo'<input type="text" name="'.$meta_box['name'].'_value" value="'.$meta_box_value.'" size="55" /><br />';
 			} else if ($meta_box['type'] == 'select') {
				echo '<select name="'.$meta_box['name'].'_value" >';
				foreach ($meta_box['options'] as $key =>  $value) {
					echo "<option " . ( $meta_box_value == $key ? 'selected' : '' ). " value='" . $key . "'>" . $value . "</option>";	
				}
				echo "</select>";
			}
			echo'<p><label for="'.$meta_box['name'].'_value">'.$meta_box['description'].'</label></p>';
			echo "</td></tr></table>";
		} // end for
	}

	
	// Save post meta data
	function save_post_meta( $post_id ) {
		global $post, $new_meta_boxes;
	
		// only save if we have something to save
		if (isset($_POST['post_type'])  && $_POST['post_type'] && $this->postmeta[$_POST['post_type']]  ) {
	
		// save fields only for the current custom post type.	
		foreach($this->postmeta[$_POST['post_type']] as $meta_box) {
		// Verify
		if ( !wp_verify_nonce( $_POST[$meta_box['name'].'_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
 
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ))
			return $post_id;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ))
			return $post_id;
		}
 
		$data = $_POST[$meta_box['name'].'_value'];
 
		if(get_post_meta($post_id, $meta_box['name'].'_value') == "")
			add_post_meta($post_id, $meta_box['name'].'_value', $data, true);
		elseif($data != get_post_meta($post_id, $meta_box['name'].'_value', true))
			update_post_meta($post_id, $meta_box['name'].'_value', $data);
		elseif($data == "")
			delete_post_meta($post_id, $meta_box['name'].'_value', get_post_meta($post_id, $meta_box['name'].'_value', true));
		}
	
		} //end if isset
	} // function
	
	
	
	// build the meta box content using the section definition
	function admin_section_builder($data,$args) {echo '<table class="form-table">'; do_settings_fields(  $this->page, $args['args']['section'] );  echo '</table>';}
	
	function plugin_load_page() {
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
	} // function
	
	function plugin_settings_form() {
		global $screen_layout_columns;
		$data = array();
		?>
		<div class="wrap">
			<?php screen_icon('options-general'); ?>
			<h2><?php print $this->title . ' Settings'; ?></h2>
			<form id="settings" action="options.php" method="post" enctype="multipart/form-data">
		
				<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
				<input type="hidden" name="action" value="save_howto_metaboxes_general" />
				<?php settings_fields($this->options); ?>
				<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
					<div id="side-info-column" class="inner-sidebar">
						<?php do_meta_boxes($this->page, 'side', $data); ?>
					</div>
					<div id="post-body" class="has-sidebar">
						<div id="post-body-content" class="has-sidebar-content">
							<?php do_meta_boxes($this->page, 'normal', $data); ?>
							<br/>
							<p>
								<input type="submit" value="Save Changes" class="button-primary" name="Submit"/>	
							</p>
						</div>
					</div>
					<br class="clear"/>				
				</div>	
			</form>
		</div>
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				postboxes.add_postbox_toggles('<?php echo $this->page; ?>');
			});
			//]]>
		</script>
		<?php
	} // function

	
	// Meta box for the support info
	function cwantwm_support() {
		print "<ul id='admin-section-support-wrap'>";
		print "<li><a id='cwantwm_support' href='http://www.cwantwm.co.uk/forum' target='_blank' style=''><img src='" . WP_PLUGIN_URL. "/cwantwm/cwantwm.png' alt='Cwantwm Development' /></a></li>";
		print "</ul>"; 
	} // function
	
	// Meta box for the forum posts
	function cwantwm_forum() {
		$feed = fetch_feed($this->forum);
		print "<ul id='admin-section-forum-wrap'>"; 
		foreach ($feed->get_items() as $item){
			printf('<li class="speech"><div><a href="%s">%s</a></div>',$item->get_permalink(), $item->get_title());
			printf('<div>%s</div></li>',wp_html_excerpt(wp_strip_all_tags($item->get_description()),120).' ...');
		}
		print "</ul>"; 
		print "<div id='admin-section-forum-wrap-footer'><a target='_blank' href='http://www.cwantwm.co.uk/forum' title='Try The Support Forum'>Need Help? Try The Support Forum</a></div>";
	} // function
	
	// Meta box for the recent tweets
	function cwantwm_tweets() {
		print "<ul id='admin-section-tweets-wrap'></ul>"; 
		print "<div id='admin-section-tweets-wrap-footer'><a target='_blank' href='http://twitter.com/cwantwm' title='Follow us on twitter'>Follow Us on Twitter</a></div>";
	} // function
	
	// Build a textarea on options page
	function textarea($args) {
		$options = get_option($this->options);
		echo "<textarea name='$this->options[" . $args['id'] . "]' rows='7' cols='50' type='textarea'>" . $options[$args['id']] . "</textarea><br /><span class='description'>" .$args['description']. "</span>";			
	} // function
	
	// Build a textarea on options page
	function wysiwyg($args) {
		$options = get_option($this->options);
		echo "<div class='postarea'>";
		the_editor($options[$args['id']]  ,  "$this->options[" . $args['id'] . "]");
		echo "</div>";
		echo "<span  class='description'>" . $args['description'] . "</span>" ;
	} // function
	
	// Build a text input on options page
	function text($args) {
		$options = get_option($this->options);
		echo "<input type='text' size='57' placeholder='" . $args['placeholder'] . "' name='$this->options[" . $args['id']. "]' value='" . $options[$args['id']] . "'/><br /><span class='description'>".$args['description']. "</span>";					
	} // function

	// Build A Checkbox On The Options Page
	function checkbox($args) {
		$options = get_option($this->options);
		echo "<input name='$this->options[" . $args['id'] . "]' type='checkbox' value='1' ";
		checked('1', $options[$args['id'] ]); 
		echo " /><span  class='description'>" . $args['description'] . "</span>" ;
	} // function
	
	function get_attachment_id ($image_src) {
		global $wpdb;
		$query = "SELECT ID FROM {$wpdb->posts} WHERE guid='$image_src'";
		$id = $wpdb->get_var($query);
		return $id;
	}
	
	// Build an attachment upload form
	function attachment($args) {
		$options = get_option($this->options);
		echo "<div><input class='attachment' id='" . $args['id'] . "' type='text' size='57' name='$this->options[" . $args['id'] . "]' value='" . $options[$args['id']] . "' />";
		echo "<input class='attachment_upload button-secondary' id='$this->options[" . $args['id'] . "]_button' type='button' value='Upload'/>";
		echo "<br/><span class='description'>" . $args['description'] . "</span></div>" ;
		// check if file exists
		$file = str_replace(get_site_url().'/',ABSPATH ,$options[$args['id']]);
		if (file_exists($file)) {
			$thumb = wp_get_attachment_image( $this->get_attachment_id($options[$args['id']]), array(80,80),1);
			print "<div class='option_preview' ><a href='" . $options[$args['id']] . "'>" . $thumb . $options[$args['id']] . "</a></div>";
		}
	  } // function
	
	// Render a selectbox or multi selectbox
	function select($args)  {
		$options = get_option($this->options);
	    if ($args['multiple']) {
			echo "<select multiple style='height:80px;' name='$this->options[" . $args['id']. "][]'>";
			foreach ($args['select'] as $key => $value) {
				echo "<option " . (array_search($value , $options[$args['id']]) === false ? '' : 'selected' ). " value='" . $key . "'>" . $value . "</option>";	
			}	
			echo "</select>";
		} else {
			echo "<select  name='$this->options[" . $args['id']. "]'>";
			foreach ($args['select'] as $key => $value) {
				echo "<option " . ($options[$args['id']] == $key ? 'selected' : '' ). " value='" . $key . "'>" . $value . "</option>";	
			}	
			echo "</select>";
		}
		echo "<br/><span  class='description'>" . $args['description'] . "</span>" ;
	} // function

	// Add a metbox to control visibility
	function add_visibility() {
		add_meta_box('admin-section-visibility','Visibility', array(&$this, 'admin_section_builder'), $this->page, 'normal', 'core',array('section' => 'admin_section_visibility'));
			// Visibility
		add_settings_field('visibility_post_types', 'Show for these post types', array(&$this, 'select'), $this->page , 'admin_section_visibility',
			array('id' => 'visibility_post_types','description' => 'Show for these post types', 'select'=>$this->post_types, 'multiple'=>true));		
		add_settings_field('visibility_method', 'Include or Exclude ', array(&$this, 'select'), $this->page , 'admin_section_visibility',
			array('id' => 'visibility_method','description' => 'You can choose whther to include or exclude the promotional text on the list of post id\'s below.', 'select'=>array('1'=>'Include','0'=>'Exclude'), 'multiple'=>false));		
		add_settings_field('visibility_list', 'Include or Exclude Id\'s', array(&$this, 'text'), $this->page , 'admin_section_visibility',
			array('id' => 'visibility_list','description' => 'Include or Exclude  the promotional text on the following post/page id\'s, separate multiple id\'s by a comma E.g. 45, 23, 128, 73', 'placeholder'=>'E.g. 1,2,3,4,5'));			
	} // function
	
	
	// Check whether the plugin affects a page
	function visible() {
		$options = get_option($this->options);
		global $post;
		
		// check include list
		if (in_array(get_post_type( $post ),$options['visibility_post_types'])) {
			$status = true;
		} else {
			$status = false;
		}
	 
		// if set to include then
		if ($options['visibility_method'] == '1') {
			if (in_array($post->ID,explode(',',	$options['visibility_list']))) {
				$status = true;
			} 
		} else {
			if (in_array($post->ID,explode(',',	$options['visibility_list']))) {
				$status = false;
			} 
		}
		return $status;
	} // function

} // class
} // if
?>