<?php
/*
Plugin Name: Gutenberg Toggle
Plugin URI: https://github.com/szczys/gutenberg-toggle
Description: Simple plugin adds a control to the sidebar of each post to choose between MCE and Block Editor.
Version: 0.0.2
Author: Mike Szczys
Author URI: https://twitter.com/szczys
License: MIT License
*/

//Enqueue the dependencies needed
function gutenberg_toggle_enqueue()
{
	if (!(gutenberg_toggle_user_has_permission(wp_get_current_user()))) return; //Trap based on settings
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
	if (!(gutenberg_toggle_user_has_permission(wp_get_current_user()))) return $can_edit; //Trap based on settings
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
	if (!(gutenberg_toggle_user_has_permission(wp_get_current_user()))) return; //Trap based on settings
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

//Does this user have permission?
function gutenberg_toggle_user_has_permission($user_obj) {
	$opts = gutenberg_toggle_get_opts_with_default();
	if (array_key_exists('enable_all', $opts)) {
		return true;
	}
	//TODO: Parse the enable user list here
	$username = $user_obj->get('user_login');
	$users_allowed = array_map('trim', explode(",",$opts['users_allowed']));
	if (in_array($username, $users_allowed)) {
		return true;
	}
	return false;
}

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
    add_settings_section( 'which_users', 'Select Users', 'gutenberg_toggle_plugin_section_text', 'gutenberg_toggle_plugin' );

    add_settings_field( 'gutenberg_toggle_plugin_setting_users', 'Enable Plugin Users on this List<br>(comma separated):', 'gutenberg_toggle_plugin_setting_users', 'gutenberg_toggle_plugin', 'which_users' );
    add_settings_field( 'gutenberg_toggle_plugin_setting_all', 'Make Plugin Active for All Users', 'gutenberg_toggle_plugin_setting_all', 'gutenberg_toggle_plugin', 'which_users' );
}
add_action( 'admin_init', 'gutenberg_toggle_register_settings' );

function gutenberg_toggle_plugin_options_validate( $input ) {
	//TODO: Validate input
    return $input;
}

function gutenberg_toggle_plugin_section_text() {
    ?>
	<p>Choose which users will see this plugin.<br>Users not specified will not see plugin controls in post editor and will be show the system wide default editor.</p>
	<?php
}

function gutenberg_toggle_get_opts_with_default() {
	//Returns default values when no entry exists in db (defaults to all users)
	return get_option( 'gutenberg_toggle_plugin_options', array('users_allowed'=>"", 'enable_all'=>"on"));
}

function gutenberg_toggle_plugin_setting_users() {
	$options = gutenberg_toggle_get_opts_with_default();
	$writers_list = $options['users_allowed'];
	?>
	<input id='gutenberg_toggle_plugin_setting_users' name='gutenberg_toggle_plugin_options[users_allowed]' type='text' value='<?php echo esc_attr( $writers_list ); ?>' />
	<?php
}

function gutenberg_toggle_plugin_setting_all() {
	$options = gutenberg_toggle_get_opts_with_default();
	$checked = "unchecked";
	if (array_key_exists('enable_all',$options)) {
		$checked = "checked";
	}
	?>
	<input id='gutenberg_toggle_plugin_setting_all' name='gutenberg_toggle_plugin_options[enable_all]' type='checkbox' <?php echo esc_attr( $checked ); ?> />
	<?php
}