<?php
/*
	Plugin Name: Comment Defaults
	Plugin URI: https://github.com/kasparsd/comment-defaults
	GitHub URI: https://github.com/kasparsd/comment-defaults
	Description: Enable or disable comments on per post type basis
	Author: Kaspars Dambis
	Version: 1.0
	Author URI: http://konstruktors.com
*/


/**
 * Register our settings field and add it to the discussion settings page
 * in WordPress backend
 */
add_action( 'admin_init', 'register_comment_defaults_settings' );

function register_comment_defaults_settings() {

	if ( ! current_user_can( 'install_plugins' ) )
		return;

	register_setting( 'discussion', 'comment_defaults_settings' );

	add_settings_field(
		'comment_defaults_field', 
		__( 'Comment Defaults', 'comment-defaults' ), 
		'comment_defaults_settings_render', 
		'discussion', 
		'default' 
	);

}


/**
 * Generate the admin UI for disabling comments for specific post types
 * @return null
 */
function comment_defaults_settings_render() {

	$settings = get_option( 'comment_defaults_settings', array() );
	$post_types = get_post_types( array( 'public' => true ), 'objects' );

	$fields = array();

	foreach ( $post_types as $post_type ) {

		if ( ! post_type_supports( $post_type->name, 'comments' ) )
			continue;

		if ( ! isset( $settings[ $post_type->name ]['comment_status'] ) )
			$settings[ $post_type->name ]['comment_status'] = false;

		$fields[] = sprintf(
				'<label>
					<input type="hidden" name="comment_defaults_settings[%s][comment_status]" value="0" />
					<input type="checkbox" name="comment_defaults_settings[%s][comment_status]" value="1" %s /> %s
				</label>',
				esc_attr( $post_type->name ),
				esc_attr( $post_type->name ),
				checked( $settings[ $post_type->name ]['comment_status'], true, false ),
				esc_html( $post_type->label )
			);
	}

	if ( empty( $fields ) )
		$fields[] = __( 'No post types with comment support were found.', 'comment-defaults' );

	printf( 
		'<fieldset>
			<p>%s</p>
			%s
		</fieldset>',
		esc_html__( 'Disable comments by default for these post types:', 'comment-defaults' ),
		implode( "<br />\n", $fields ) 
	);

}


/**
 * Prefill default post variables with either comments hidden or not
 */
add_filter( 'default_content', 'maybe_disable_comments_defaults_posts', 10, 2 );

function maybe_disable_comments_defaults_posts( $content, $post ) {

	$settings = get_option( 'comment_defaults_settings', array() );

	if ( isset( $settings[ $post->post_type ]['comment_status'] ) && $settings[ $post->post_type ]['comment_status'] ) {
		$post->comment_status = 'closed';
		$post->ping_status = 'closed';
	}

	return $content;
}


/**
 * For some reason WordPress doesn't use "default_content" filter when creating attachments
 * so we need to manually hook into the add_attachment hook and modify comment settings
 */
add_action( 'add_attachment', 'maybe_disable_comments_defaults_attachments' );

function maybe_disable_comments_defaults_attachments( $post_id ) {

	$settings = get_option( 'comment_defaults_settings', array() );
	$post = get_post( $post_id );

	if ( isset( $settings['attachment']['comment_status'] ) && $settings['attachment']['comment_status'] ) {
		$post->comment_status = 'closed';
		$post->ping_status = 'closed';

		wp_update_post( $post );
	}

}

