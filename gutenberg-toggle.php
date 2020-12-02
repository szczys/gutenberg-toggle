<?php
/*
Plugin Name: Gutenberg Toggle
Plugin URI: https://github.com/szczys/gutenberg-toggle
Description: Simple plugin adds a control to the sidebar of each post to choose between MCE and Block Editor.
Version: 0.0.1
Author: Mike Szczys
Author URI: https://twitter.com/szczys
License: MIT License
*/

//Enqueue the dependencies needed
function gutenberg_toggle_enqueue()
{
	wp_enqueue_script(
		'gutenberg-toggle-script',
		plugins_url('build/index.js', __FILE__),
		array('wp-plugins', 'wp-edit-post', 'wp-i18n', 'wp-element', 'wp-components', 'wp-data', 'wp-compose')
	);
}
add_action('enqueue_block_editor_assets', 'gutenberg_toggle_enqueue');

//Register the datatype needed to keep track of block editor usage for individual posts
function gutenberg_toggle_register_meta()
{
	register_meta('post', '_use_block_editor', array(
		'show_in_rest' => true,
		'type' => 'boolean',
		'single' => true,
		'default' => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback' => function () {
			return current_user_can('edit_posts');
		}
	));
}
add_action('init', 'gutenberg_toggle_register_meta');

//Affect the editor only if the user_block_editor meta actually exists
//Enable Gutenburg Editor but only if the use_block_editor meta is 'true' (string)
add_filter('use_block_editor_for_post', function ($can_edit, $post) {
	if (empty($post->ID)) return $can_edit;
	if (metadata_exists('post', $post->ID, '_use_block_editor') === false) return $can_edit;
	if (get_post_meta($post->ID, '_use_block_editor', true) === '1') {
		add_filter('user_can_richedit', '__return_true', 50);
		return true;
	}
	else { return false; }
}, 10, 2);

//Need metaboxes for the old MCE editor (Javascript handles block editor controls)
function gutenberg_toggle_add_meta_box()
{
	add_meta_box(
		'gutenberg_toggle_metabox',
		'Use Block Editor',
		'gutenberg_toggle_metabox_html',
		'post',
		'side',
		'default',
		array (
			'__back_compat_meta_box' => true,
		)
	);
}
add_action('add_meta_boxes', 'gutenberg_toggle_add_meta_box');
function gutenberg_toggle_metabox_html($post)
{
	$should_use_block_editor = get_post_meta($post->ID, '_use_block_editor', true);
	wp_nonce_field('gutenberg_toggle_update_post_metabox', 'gutenberg_toggle_update_post_nonce');
	?>
	<p>
		<label for="gutenberg_toggle_control">
			<input type="checkbox" style="margin-left:12px; margin-right:6px;" name="gutenberg_toggle_control" value="1" <?php if ($should_use_block_editor === '1') echo "checked"; ?> />
			Enable Block Editor
		</label>
	</p>
	<?php
}
function gutenberg_save_post_metabox($post_id, $post) {
	$edit_cap = get_post_type_object( $post->post_type )->cap->edit_post;
	if( !current_user_can( $edit_cap, $post_id )) {
	  return;
	}
	if( !isset( $_POST['gutenberg_toggle_update_post_nonce']) || !wp_verify_nonce( $_POST['gutenberg_toggle_update_post_nonce'], 'gutenberg_toggle_update_post_metabox' )) {
	  return;
	}
	if( isset( $_POST[ 'gutenberg_toggle_control' ] ) ) {
		update_post_meta( $post_id, '_use_block_editor', true );
	} else {
		update_post_meta( $post_id, '_use_block_editor', false );
	}
  }
  add_action( 'save_post', 'gutenberg_save_post_metabox', 10, 2 );

//Make a settings page
function gutenberg_toggle_add_settings_page() {
    add_options_page( __('Gutenberg Toggle Plugin Settings', 'textdomain'), __('Block Editor Toggle', 'textdomain'), 'manage_options', "gutenberg-toggle-plugin", 'gutenburg_toggle_render_plugin_settings_page' );
}
add_action( 'admin_menu', 'gutenberg_toggle_add_settings_page' );

function gutenburg_toggle_render_plugin_settings_page() {
    ?>
    <h2>Gutenberg Toggle Plugin Settings</h2>
    <form action="options.php" method="post">
        <?php 
        settings_fields( 'gutenberg_toggle_plugin_options' );
        do_settings_sections( 'gutenberg_toggle_plugin' ); ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
    </form>
    <?php
}

function gutenberg_toggle_register_settings() {
    register_setting( 'gutenberg_toggle_plugin_options', 'gutenberg_toggle_plugin_options', 'gutenberg_toggle_plugin_options_validate' );
    add_settings_section( 'api_settings', 'API Settings', 'gutenberg_toggle_plugin_section_text', 'gutenberg_toggle_plugin' );

    add_settings_field( 'gutenberg_toggle_plugin_setting_api_key', 'API Key', 'gutenberg_toggle_plugin_setting_api_key', 'gutenberg_toggle_plugin', 'api_settings' );
    add_settings_field( 'gutenberg_toggle_plugin_setting_results_limit', 'Results Limit', 'gutenberg_toggle_plugin_setting_results_limit', 'gutenberg_toggle_plugin', 'api_settings' );
    add_settings_field( 'gutenberg_toggle_plugin_setting_start_date', 'Start Date', 'gutenberg_toggle_plugin_setting_start_date', 'gutenberg_toggle_plugin', 'api_settings' );
}
add_action( 'admin_init', 'gutenberg_toggle_register_settings' );

function gutenberg_toggle_plugin_options_validate( $input ) {
	//TODO: Validate input
	$outlist = explode(",", $input);
	$outlist = arra_map('trim', $outlist);
    return $outlist;
}

function gutenberg_toggle_plugin_section_text() {
    echo '<p>Here you can set all the options for using the API</p>';
}

function gutenberg_toggle_plugin_setting_api_key() {
	$options = get_option( 'gutenberg_toggle_plugin_options' );
	if ($options === false) {
		$writers_list = "";
	}
	else $writers_list = esc_attr( $options['api_key'] );
    echo "<input id='gutenberg_toggle_plugin_setting_api_key' name='gutenberg_toggle_plugin_options[api_key]' type='text' value='".$writers_list."' />";
	//echo "<input type='text' value='hello' />";
}

function gutenberg_toggle_plugin_setting_results_limit() {
    $options = get_option( 'gutenberg_toggle_plugin_options' );
    //echo "<input id='gutenberg_toggle_plugin_setting_results_limit' name='gutenberg_toggle_plugin_options[results_limit]' type='text' value='{esc_attr( $options['results_limit'] )}' />";
	echo "<input type='text' value='there' />";
}

function gutenberg_toggle_plugin_setting_start_date() {
    $options = get_option( 'gutenberg_toggle_plugin_options' );
    //echo "<input id='gutenberg_toggle_plugin_setting_start_date' name='gutenberg_toggle_plugin_options[start_date]' type='text' value='{esc_attr( $options['start_date'] )}' />";
	echo "<input type='text' value='general' />";
}