<?php

class getreel_options_page {

	function __construct() {
        add_action( 'admin_menu', array( $this, 'getreel_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'getreel_register_settings' ) );
	}

	function getreel_admin_menu() {
		add_options_page(
			'Get Reel Options',
			'Get Reel',
			'manage_options',
			'getreel_options_slug',
			array(
				$this,
				'getreel_settings_page'
			)
		);
    }

    function getreel_register_settings() {
        register_setting( 'getreel_settings', 'getreel_tmdb_api_key' );
    }
     
	function getreel_settings_page() {
		?>
        <div class="wrap">
        <form action="options.php" method="post">
    
            <?php
            settings_fields( 'getreel_settings' );
            do_settings_sections( 'getreel_settings' );
            ?>
            <div> 
                <h3>TMDB API Key<h3>
                <p>Put your API key here: <input type="text" placeholder="TMDB API Key" name="getreel_tmdb_api_key" value="<?php echo esc_attr( get_option('getreel_tmdb_api_key') ); ?>" size="100" /></p>
                
                <?php submit_button(); ?>
            </div>
            </form>
        </div>
    <?php
	}
}

new getreel_options_page;

?>