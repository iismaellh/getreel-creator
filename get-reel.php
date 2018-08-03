<?php
/**
 * Get Reel Movie Creator
 *
 * @package   get-reel
 * @link      https://github.com/randomtu/getreel-creator
 * @author    Ismael Herrera <ismael13herrera@gmail.com>
 * @copyright 2018 Ismael Herrera
 * @license   GPL v2 or later
 *
 * Plugin Name:  Get Reel Creator
 * Description:  Movie fetcher using WP_Http::request wrapper.
 * Version:      1.0
 * Plugin URI:   n/a
 * Author:       Ismael L. Herrera
 * Author URI:   n/a
 * Text Domain:  getreel
 * Requires PHP: 5.3.6
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * filter content and add the movie creation form
 * 
 * @since 1.0
 * @param string $output
 * @return null
 */
function getreel_movie_creation_form($output) {
	if ( is_page( 'create-movie' ) ) {
		if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
			$output  = '<div id="movie-creation-form">';
			$output .= '<input type="text" id="movie-disc-start" name="movie-start" value="1">';
			$output .= '<input type="text" id="movie-disc-end" name="movie-end" value="2">';
			$output .= '<button onClick="discoverMovies()">Discover Movies</button>';
			$output .= '</div>';

			$output  = '<div id="people-creation-form">';
			$output .= '<input type="text" id="movie-disc-start" name="movie-start" value="1">';
			$output .= '<input type="text" id="movie-disc-end" name="movie-end" value="2">';
			$output .= '<button onClick="discoverMovies()">Discover Movies</button>';
			$output .= '</div>';

			$ajax_url = admin_url( 'admin-ajax.php' );

			?>
			<script>
				function discoverMovies(){
					<?php $nonce = wp_create_nonce( 'start-movie-creation' );?>
					jQuery.ajax({
						type: "post",
						url: "<?php echo $ajax_url; ?>",
						data: { 
							action: 'discover_movies', 
							_ajax_nonce: '<?php echo $nonce; ?>' 
						},
						success: function(html){
							console.log('movie discovered'); 
						}
					});
				}
			</script>
			<?php	
		} else {
			$output = sprintf( '<a href="%1s">%2s</a>', esc_url( wp_login_url() ), __( 'Login Here', 'getreel' ) );
		}
	}

	return $output;
}

add_filter( 'the_content', 'getreel_movie_creation_form', 10, 1 ); {


/**
 * localize movie and people creator scripts
 * 
 * @since 1.0
 * @return null
 */
 add_action( 'wp_enqueue_scripts', function() {
 	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {

 		wp_enqueue_script( 'people-creator', plugin_dir_url( __FILE__ ) . '/js/people-creator.js', array( 'jquery' ) );

 		wp_localize_script( 'people-creator', 'PEOPLE_CREATOR', array(
 				'ajax_url' => admin_url( 'admin-ajax.php' ),
 				'root' => esc_url_raw( rest_url() ),
 				'nonce' => wp_create_nonce( 'wp_rest' ),
 				'success' => __( 'New people registered!', 'getreel' ),
 				'failure' => __( 'Registration failed.', 'getreel' ),
 				'current_user_id' => get_current_user_id()
 			)
 		);

 		wp_enqueue_script( 'movie-creator', plugin_dir_url( __FILE__ ) . '/js/movie-creator.js', array( 'jquery' ) );

 		wp_localize_script( 'movie-creator', 'MOVIE_CREATOR', array(
 				'ajax_url' => admin_url( 'admin-ajax.php' ),
 				'root' => esc_url_raw( rest_url() ),
 				'nonce' => wp_create_nonce( 'wp_rest' ),
 				'success' => __( 'New movie created!', 'getreel' ),
 				'failure' => __( 'Creation failed.', 'getreel' ),
 				'current_user_id' => get_current_user_id()
 			)
 		);
 	}
 });

/**
 * automatically creates the Create Movie page if it doesn't exist
 * 
 * @since 1.0
 * @return int
 */
function getreel_add_movie_creator() {
	if ( ! is_admin() ) {
		require_once( ABSPATH . 'wp-admin/includes/post.php' );
	}

	if( ! empty( post_exists( 'Create Movie' ) ) )  return;

	$id = wp_insert_post( array(
		'post_title'    => wp_strip_all_tags( 'Create Movie' ),
		'post_content'  => 'Please dont delete this page. This is the Movies and People Creator.',
		'post_type'     => 'page',
		'post_status'   => 'private'
		)
	);

	if( ! is_wp_error($id) ){
		echo 'Create Movie Page Created :p';
	} else {
		$errors = $movie_id->get_error_messages();
		echo 'Create Movie Page Failed';
		foreach ($errors as $error) {
			echo $error;
		}
	}	
	
	return $id;
}

add_action( 'init', 'getreel_add_movie_creator' );

/**
 * automatically creates default movie pages like Films, Discover and People
 * 
 * @since 1.0
 * @return int
 */

function getree_create_movie_pages($wp_error) {
	if ( ! is_admin() ) {
   		require_once( ABSPATH . 'wp-admin/includes/post.php' );
	}

	if( empty( post_exists( 'Films' ) ) )  {
		$id1 = wp_insert_post( array(
			'post_title'    => wp_strip_all_tags( 'Films' ),
			'post_content'  => 'This is the movie page. Don\'t delete it!',
			'post_type'     => 'page',
			'post_status'   => 'publish'
			)
		);
	}

	if( empty( post_exists( 'Discover' ) ) )  {
		$id2 = wp_insert_post( array(
			'post_title'    => wp_strip_all_tags( 'Discover' ),
			'post_content'  => 'This is the disover movies page. Don\'t delete it!',
			'post_type'     => 'page',
			'post_status'   => 'publish'
			)
		);
	}

	if( empty( post_exists( 'People' ) ) )  {
		$id3 = wp_insert_post( array(
			'post_title'    => wp_strip_all_tags( 'People' ),
			'post_content'  => 'This is the actors page. Don\'t delete it!',
			'post_type'     => 'page',
			'post_status'   => 'publish'
			)
		);
	}

	if( ! is_wp_error($id) ){
		echo 'Default Pages Created :]';
	} else {
		$errors = $movie_id->get_error_messages();
		echo 'Default Pages Failed :[';
		foreach ($errors as $error) {
			echo $error;
		}
	}	

	return $wp_error;
}

add_action( 'init', 'getreel_create_movie_pages' );
