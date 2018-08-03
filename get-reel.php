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
 * Plugin URI:   https://github.com/randomtu/getreel-creator
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
			$output .= '<button onClick="getReelConnect()">Discover Movies</button>';
			$output .= '</div>';

			// $output .= '<div id="people-creation-form">';
			// $output .= '<input type="text" id="movie-disc-start" name="movie-start" value="1">';
			// $output .= '<input type="text" id="movie-disc-end" name="movie-end" value="2">';
			// $output .= '<button onClick="getReelDiscover()">Discover Movies</button>';
			// $output .= '</div>';

			$ajax_url = admin_url( 'admin-ajax.php' );

			?>
			<script>
				function getReelConnect() {
					var start = $('#movie-disc-start').val();
					var end = $('#movie-disc-end').val();

					for(i = start; i < end; i++) {
						getReelDiscover(i, end);
						console.log('discover started');
					}
				}

				function getReelDiscover(start, end){
					<?php $nonce = wp_create_nonce( 'start-movie-creation' );?>
					jQuery.ajax({
						type: "post",
						url: "<?php echo $ajax_url; ?>",
						data: { 
							action: 'getreel_discover_movies', 
							_ajax_nonce: '<?php echo $nonce; ?>',
							start: start,
						},
						success: function(html){
							console.log(start);
							console.log(end);
							if(start == end + 1) $(window).trigger('discover finished');
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

add_filter( 'the_content', 'getreel_movie_creation_form', 10, 1 ); 

/**
 * create a link to the movie creator page in the WP toolbar
 * 
 * @since 1.0
 * @param object $wp_admin_bar
 * @return null
 */
function getreel_movie_creator_toolbar_link($wp_admin_bar) {
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		$args = array(
			'id' => 'getreel-creator',
			'title' => 'Get Reel Creator', 
			'href' => 'http://localhost/getreel/create-movie/', 
			'meta' => array(
				'class' => 'getreel', 
				'title' => 'Get Reel Movie Creator'
				)
		);
		$wp_admin_bar->add_node($args);
	}
}
add_action('admin_bar_menu', 'getreel_movie_creator_toolbar_link', 999);


/**
 * function that fetches movie array from tmdb
 * calls the get_reel_create_movie
 * 
 * @since 1.0
 * @return null
 */
function getreel_discover_movies() {	
	global  $wp_version;

	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		$args = array(
			'timeout'     => 60,
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
		); 

		$page = $_REQUEST['start'];

		$response = wp_remote_get( 'https://api.themoviedb.org/3/discover/movie?api_key=f9cf4ece2f9aeccbe524aaa92a1515ae&language=en-US&sort_by=popularity.desc&include_adult=false&include_video=false&page=' . $page, $args );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		foreach($body['results'] as $key => $movie) {
			$movie_id = getreel_create_movie($movie['id']);
		}

		exit();
	}
}

add_action('wp_ajax_getreel_discover_movies','getreel_discover_movies');
add_action('wp_ajax_priv_getreel_discover_movies','getreel_discover_movies');


/**
 * function inside the getreel_create_movie 
 * update existing movies
 * 
 * @since 1.0
 * @param int $id
 * @param array $data
 * @param array $movie
 * @return int
 */
function getreel_update_movie($id, $data, $movie) {
	$reviews = $data['reviews'] == NULL ? 'none' : json_encode($data['reviews']);
	$credits = $data['credits'] == NULL ? 'none' : json_encode($data['credits']);
	$similar = $data['similar'] == NULL ? 'none' : json_encode($data['similar']);
	$changes = $data['changes'] == NULL ? 'none' : json_encode($data['changes']);
	$videos = $data['videos'] == NULL ? 'none' : json_encode($data['videos']);
	$images = $data['images'] == NULL ? 'none' : json_encode($data['images']);
	$imdb = $data['imdb_id'] == NULL ? 0: $data['imdb_id'];

	$args = array(
		'ID' => $id,
		'post_title' => wp_strip_all_tags( $data['title'] ),
		'post_content' => $data['overview'],
		'post_tmdb' => $data['id'],
		'post_imdb' => $imdb,
		'post_credits' => $credits,
		'post_reviews' => $reviews,
		'post_similar' => $similar,
		'post_videos' => $videos,
		'post_changes' => $changes,
		'post_images' => $images,
		'post_data' => json_encode($movie)
	);

	$movie_id = wp_update_post($args, true);

	if( ! is_wp_error($movie_id) ){
		echo 'update success: ' . $data['title'] . '<br />';
	} else {
		$errors = $movie_id->get_error_messages();
		echo 'update failed: ' . $data['title'] . '<br />';
		foreach ($errors as $error) {
			echo $error;
		}
	}		

	return $movie_id;
}

/**
 * function called by getreel_discover_movies
 * fetches movie data from tmdb
 * update or returns the newly created movie id
 * 
 * @since 1.0
 * @param int $id
 * @return int
 */
function getreel_create_movie($id) {
	global $wp_version;

	$args = array(
		'timeout'     => 60,
		'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
	); 
	
	$response = wp_remote_get( 'https://api.themoviedb.org/3/movie/'.$id.'?api_key=f9cf4ece2f9aeccbe524aaa92a1515ae&language=en-US&append_to_response=credits,changes,videos,images,similar,reviews', $args );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	$movie_data = array();
	$movie_data['adult'] = $data['adult'];
	$movie_data['backdrop_path'] = $data['backdrop_path'];
	$movie_data['belongs_to_collection'] = $data['belongs_to_collection'];
	$movie_data['budget'] = $data['budget'];
	$movie_data['genres'] = $data['genres'];
	$movie_data['homepage'] = $data['homepage'];
	$movie_data['id'] = $data['id'];
	$movie_data['imdb_id'] = $data['imdb_id'];
	$movie_data['original_language'] = $data['original_language'];
	$movie_data['original_title'] = $data['original_title'];
	$movie_data['overview'] = $data['overview'];
	$movie_data['popularity'] = $data['popularity'];
	$movie_data['poster_path'] = $data['poster_path'];
	$movie_data['production_companies'] = $data['production_companies'];
	$movie_data['production_countries'] = $data['production_countries'];
	$movie_data['release_date'] = $data['release_date'];
	$movie_data['revenue'] = $data['revenue'];
	$movie_data['runtime'] = $data['runtime'];
	$movie_data['spoken_languages'] = $data['spoken_languages'];
	$movie_data['status'] = $data['status'];
	$movie_data['tagline'] = $data['tagline'];
	$movie_data['title'] = $data['title'];
	$movie_data['video'] = $data['video'];
	$movie_data['vote_average'] = $data['vote_average'];
	$movie_data['vote_count'] = $data['vote_count'];
	
	$reviews = $data['reviews'] == NULL ? 'none' : json_encode($data['reviews']);
	$credits = $data['credits'] == NULL ? 'none' : json_encode($data['credits']);
	$similar = $data['similar'] == NULL ? 'none' : json_encode($data['similar']);
	$changes = $data['changes'] == NULL ? 'none' : json_encode($data['changes']);
	$videos = $data['videos'] == NULL ? 'none' : json_encode($data['videos']);
	$images = $data['images'] == NULL ? 'none' : json_encode($data['images']);
	$imdb = $data['imdb_id'] == NULL ? 0: $data['imdb_id'];

	$args = array(
		'post_title' => wp_strip_all_tags( $data['title'] ),
		'post_content' =>  $data['overview'],
		'post_status' => 'publish',
		'post_author' => 1,
		'post_type' => 'movie',
		'post_tmdb' => $id,
		'post_imdb' => $imdb,
		'post_credits' => $credits,
		'post_reviews' => $reviews,
		'post_similar' => $similar,
		'post_videos' => $videos,
		'post_changes' => $changes,
		'post_images' => $images,
		'post_data' => json_encode($movie_data)
	);

	$exists = post_exists( wp_strip_all_tags( $data['title'] ) );

	if( $exists ) {
		getreel_update_movie($exists, $data, $movie_data);
	} else {
		$movie_id = wp_insert_post($args);

		if( ! is_wp_error($movie_id) ){
			echo 'creation success: ' . $data['title'] . '<br />';
		} else {
			$errors = $movie_id->get_error_message();
			echo 'creation failed: ' . $data['title'] . '<br />';
			foreach ($errors as $error) {
				echo $error;
			}
		}
	}
}

/**
 * insert movie data or columns in the database
 * $data is the current database structure
 * $postarr holds the data of the newly created posts
 * 
 * @since 1.0
 * @param array $data
 * @param array $postarr
 * @return int
 */
function getreel_wp_insert_movie_data( $data , $postarr ) {
	if( $data['post_type'] == 'movie' || $postarr['post_type'] == 'movie' ) {
		$data['post_videos'] = $postarr['post_videos'];
		$data['post_changes'] = $postarr['post_changes'];
		$data['post_images'] = $postarr['post_images'];
		$data['post_similar'] = $postarr['post_similar'];
		$data['post_reviews'] = $postarr['post_reviews'];
		$data['post_credits'] = $postarr['post_credits'];
		$data['post_tmdb'] = $postarr['post_tmdb'];
		$data['post_imdb'] = $postarr['post_imdb'];
		$data['post_data'] = $postarr['post_data'];
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'getreel_wp_insert_movie_data', 100, 2 );


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
		$errors = $id->get_error_messages();
		echo 'Create Movie Page Failed';
		foreach ($errors as $error) {
			echo $error;
		}
	}	
	
	return $id;
}

add_action( 'wp', 'getreel_add_movie_creator' );

/**
 * automatically creates default movie pages like Films, Discover and People
 * 
 * @since 1.0
 * @return int
 */

function getreel_create_movie_pages($wp_error) {
	if ( ! is_admin() ) {
   		require_once( ABSPATH . 'wp-admin/includes/post.php' );
	}

	if( ! empty( post_exists( 'Films' ) ) ) return;

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

	if( ! is_wp_error($id1) ){
		echo 'Default Pages Created :]';
	} else {
		$errors = $id1->get_error_messages();
		echo 'Default Pages Failed :[';
		foreach ($errors as $error) {
			echo $error;
		}
	}	

	return $wp_error;
}

add_action( 'wp', 'getreel_create_movie_pages' );