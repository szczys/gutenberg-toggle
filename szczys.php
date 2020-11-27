<?php
/*
Plugin Name: Szczys
*/

function szczys_enqueue() {
	wp_enqueue_script(
		'szczys-script',
		plugins_url( 'build/index.js', __FILE__ ),
		array( 'wp-plugins', 'wp-edit-post', 'wp-i18n', 'wp-element', 'wp-components', 'wp-data', 'wp-compose' )
	);
}
add_action( 'enqueue_block_editor_assets', 'szczys_enqueue' );

function myprefix_register_meta() {
    register_meta('post', '_use_block_editor',array(
		'show_in_rest' => true,
		'type' => 'boolean',
		'single' => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback' => function() { 
		  return current_user_can('edit_posts');
		}
	  ));
}
add_action('init', 'myprefix_register_meta');

//Enable Gutenburg Editor but only if the use_block_editor meta is 'true' (string)
add_filter('use_block_editor_for_post', function($can_edit, $post) {
	
	if (empty($post->ID)) return $can_edit;
	if (get_post_meta($post->ID, 'use_block_editor', true) === 'true') {
		add_filter( 'user_can_richedit', '__return_true', 50);
		return true;
	}
	return $can_edit;
}, 10, 2);

?>