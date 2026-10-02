<?php
/** Dedicated entry point; reading documents remain portable WordPress Pages. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function kilka_reader_admin_url( $id = 0 ) {
	return add_query_arg( array( 'page' => 'kilka-reader', 'document' => absint( $id ) ), admin_url( 'admin.php' ) );
}
function kilka_reader_admin_document( $id ) {
	$post = get_post( $id );
	if ( ! $post || 'page' !== $post->post_type || 'trash' === $post->post_status || KILKA_READER_TEMPLATE !== get_page_template_slug( $post ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		wp_die( esc_html__( 'This reading document is not available.', 'kilka-reader' ), '', array( 'response' => 403 ) );
	}
	return $post;
}
add_action( 'admin_menu', function () {
	add_menu_page( __( 'Reader', 'kilka-reader' ), __( 'Reader', 'kilka-reader' ), 'edit_pages', 'kilka-reader', 'kilka_reader_admin_screen', 'dashicons-book-alt', 21 );
} );
// Validate before WordPress sends the admin page headers.
add_action( 'load-toplevel_page_kilka-reader', function () {
	$id = isset( $_GET['document'] ) && is_scalar( $_GET['document'] ) ? absint( $_GET['document'] ) : 0;
	if ( $id ) { kilka_reader_admin_document( $id ); }
} );
function kilka_reader_admin_screen() {
	if ( ! current_user_can( 'edit_pages' ) ) { return; }
	$id = isset( $_GET['document'] ) && is_scalar( $_GET['document'] ) ? absint( $_GET['document'] ) : 0;
	echo '<div class="wrap kilka-reader-admin"><h1>' . esc_html__( 'Reader', 'kilka-reader' ) . '</h1>';
	if ( $id ) {
		$post = kilka_reader_admin_document( $id );
		echo '<p><a href="' . esc_url( kilka_reader_admin_url() ) . '">' . esc_html__( 'All reading documents', 'kilka-reader' ) . '</a></p><h2>' . esc_html( get_the_title( $post ) ) . '</h2>';
		echo '<p><a class="button" href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html__( 'Edit story text', 'kilka-reader' ) . '</a> <a class="button" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html__( 'View reader', 'kilka-reader' ) . '</a></p>';
		if ( isset( $_GET['saved'] ) ) { echo '<div class="notice notice-success"><p>' . esc_html__( 'Reader settings saved.', 'kilka-reader' ) . '</p></div>'; }
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="kilka_reader_settings"><input type="hidden" name="document" value="' . esc_attr( $id ) . '">';
		wp_nonce_field( 'kilka_reader_settings_' . $id );
		echo '<h2>' . esc_html__( 'Introduction — Space', 'kilka-reader' ) . '</h2>';
		kilka_reader_intro_editor( $post );
		echo '<h2>' . esc_html__( 'Reading document', 'kilka-reader' ) . '</h2>';
		kilka_reader_meta_box( $post, true );
		submit_button( __( 'Save reader settings', 'kilka-reader' ) );
		echo '</form></div>';
		return;
	}
	if ( isset( $_GET['trashed'] ) ) {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Reading document moved to Trash.', 'kilka-reader' ) . ' <a href="' . esc_url( admin_url( 'edit.php?post_type=page&post_status=trash' ) ) . '">' . esc_html__( 'Open Trash to restore it', 'kilka-reader' ) . '</a></p></div>';
	}
	echo '<p>' . esc_html__( 'Choose a document to set its opening image and text, or edit the story itself.', 'kilka-reader' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="kilka_reader_create">';
	wp_nonce_field( 'kilka_reader_create' );
	submit_button( __( 'Add reading document', 'kilka-reader' ), 'secondary', 'submit', false );
	echo '</form><br>';
	kilka_reader_import_form();
	echo '<br><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Document', 'kilka-reader' ) . '</th><th>' . esc_html__( 'Introduction', 'kilka-reader' ) . '</th><th>' . esc_html__( 'Actions', 'kilka-reader' ) . '</th></tr></thead><tbody>';
	$current = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$args = array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'posts_per_page' => 20, 'paged' => $current, 'meta_key' => '_wp_page_template', 'meta_value' => KILKA_READER_TEMPLATE );
	if ( ! current_user_can( 'edit_others_pages' ) ) { $args['author'] = get_current_user_id(); }
	$query = new WP_Query( $args );
	foreach ( $query->posts as $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) { continue; }
		$status = get_post_status_object( $post->post_status );
		echo '<tr><td><strong><a href="' . esc_url( kilka_reader_admin_url( $post->ID ) ) . '">' . esc_html( get_the_title( $post ) ?: __( '(Untitled)', 'kilka-reader' ) ) . '</a></strong><br><code>/' . esc_html( $post->post_name ) . '/</code><br>' . esc_html( $status ? $status->label : '' ) . '</td><td>' . esc_html( kilka_reader_intro_settings( $post->ID )['enabled'] ? __( 'Space', 'kilka-reader' ) : __( 'None', 'kilka-reader' ) ) . '</td><td><a class="button" href="' . esc_url( kilka_reader_admin_url( $post->ID ) ) . '">' . esc_html__( 'Reader settings', 'kilka-reader' ) . '</a> <a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html__( 'Edit story text', 'kilka-reader' ) . '</a> | <a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html__( 'View', 'kilka-reader' ) . '</a>';
		if ( EMPTY_TRASH_DAYS && current_user_can( 'delete_post', $post->ID ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline"><input type="hidden" name="action" value="kilka_reader_trash"><input type="hidden" name="document" value="' . esc_attr( $post->ID ) . '">';
			wp_nonce_field( 'kilka_reader_trash_' . $post->ID );
			echo ' | <button type="submit" class="button-link button-link-delete">' . esc_html__( 'Move to Trash', 'kilka-reader' ) . '</button></form>';
		}
		echo '</td></tr>';
	}
	if ( ! $query->posts ) { echo '<tr><td colspan="3">' . esc_html__( 'No reading documents yet.', 'kilka-reader' ) . '</td></tr>'; }
	echo '</tbody></table>';
	echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%', kilka_reader_admin_url() ), 'format' => '', 'current' => $current, 'total' => $query->max_num_pages ) ) );
	echo '</div>';
}
add_action( 'admin_post_kilka_reader_settings', function () {
	$id = isset( $_POST['document'] ) && is_scalar( $_POST['document'] ) ? absint( $_POST['document'] ) : 0;
	kilka_reader_admin_document( $id );
	check_admin_referer( 'kilka_reader_settings_' . $id );
	if ( ! isset( $_POST['kilka_intro'] ) || ! is_array( $_POST['kilka_intro'] ) ) { wp_die( esc_html__( 'Missing settings.', 'kilka-reader' ) ); }
	update_post_meta( $id, '_kilka_reader_intro', wp_unslash( $_POST['kilka_intro'] ) );
	$target = isset( $_POST['kilka_reader_publication'] ) && is_scalar( $_POST['kilka_reader_publication'] ) ? absint( $_POST['kilka_reader_publication'] ) : 0;
	update_post_meta( $id, '_kilka_reader_publication', kilka_reader_publication( $target ) ? $target : 0 );
	$language = isset( $_POST['kilka_reader_language'] ) && is_string( $_POST['kilka_reader_language'] ) ? trim( wp_unslash( $_POST['kilka_reader_language'] ) ) : '';
	update_post_meta( $id, '_kilka_reader_language', kilka_reader_language( $language ) );
	wp_safe_redirect( add_query_arg( 'saved', 1, kilka_reader_admin_url( $id ) ) );
	exit;
} );
add_action( 'admin_post_kilka_reader_create', function () {
	if ( ! current_user_can( 'edit_pages' ) ) { wp_die( esc_html__( 'You cannot create reading documents.', 'kilka-reader' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'kilka_reader_create' );
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => __( 'New reading document', 'kilka-reader' ), 'meta_input' => array( '_wp_page_template' => KILKA_READER_TEMPLATE ) ), true );
	if ( is_wp_error( $id ) ) { wp_die( esc_html( $id->get_error_message() ) ); }
	wp_safe_redirect( get_edit_post_link( $id, 'raw' ) );
	exit;
} );

add_action( 'admin_post_kilka_reader_trash', function () {
	$id = isset( $_POST['document'] ) && is_scalar( $_POST['document'] ) ? absint( $_POST['document'] ) : 0;
	kilka_reader_admin_document( $id );
	check_admin_referer( 'kilka_reader_trash_' . $id );
	// Never turn this reversible action into permanent deletion when Trash is disabled.
	if ( ! EMPTY_TRASH_DAYS || ! current_user_can( 'delete_post', $id ) ) {
		wp_die( esc_html__( 'This reading document cannot be moved to Trash.', 'kilka-reader' ), '', array( 'response' => 403 ) );
	}
	if ( ! wp_trash_post( $id ) ) {
		wp_die( esc_html__( 'The reading document could not be moved to Trash.', 'kilka-reader' ) );
	}
	wp_safe_redirect( add_query_arg( 'trashed', 1, kilka_reader_admin_url() ) );
	exit;
} );
