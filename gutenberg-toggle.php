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
		update_post_meta( $post_id, '_use_block_editor', 1 );
	} else {
		update_post_meta( $post_id, '_use_block_editor', null );
	}
  }
  add_action( 'save_post', 'gutenberg_save_post_metabox', 10, 2 );
