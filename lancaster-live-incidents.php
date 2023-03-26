<?php
/*
* Plugin Name: Lancaster Live Incidents
* Plugin URI: https://evan-herman.com
* Description: This plugin displays live incidents in Lancaster.
* Version: 1.0.0
* Author: Evan Herman
* Author URI: https://evan-herman.com/
* License: GPL-2.0+
* License URI: https://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain: lancaster-live-incidents
* Domain Path: /languages
* Requires at least: 5.6
* Requires PHP: 7.4
* Tested up to: 6.2
*/

final class Lancaster_Live_Incidents {

	private $incident_data;

	private $widget_title;

	private $widget_description;

	private $incidents;

	public function __construct() {

		define( 'LANCASTER_LIVE_INCIDENTS_VER', '1.0.0' );

		$this->incident_data = $this->get_lancaster_live_incidents_feed();

		if ( empty( $this->incident_data ) ) {

			return;

		}

		$this->widget_title       = $this->incident_data['channel']['title'] ?? __( 'Lancaster County Live Incidents', 'lancaster-live-incidents' );
		$this->widget_description = $this->incident_data['channel']['description'] ?? __( 'Active incidents from Lancaster County-Wide Communications.', 'lancaster-live-incidents' );
		$this->incidents          = $this->incident_data['channel']['item'] ?? array();

		add_action( 'wp_dashboard_setup', array( $this, 'add_lancaster_live_incidents_dashboard_widget' ) );

	}

	public function add_lancaster_live_incidents_dashboard_widget() {

		wp_enqueue_style(
			'lancaster-live-incidents',
			plugin_dir_url( __FILE__ ) . 'style.css',
			array(),
			LANCASTER_LIVE_INCIDENTS_VER,
			'all'
		);

		wp_add_dashboard_widget(
			'lancaster_live_incidents', // Widget ID
			$this->widget_title, // Widget title
			array( $this, 'lancaster_live_incidents_dashboard_widget' ) // Callback function
		);

	}

	public function lancaster_live_incidents_dashboard_widget() {

		if ( empty( $this->incidents ) ) {

			printf(
				'<p>%s</p>',
				__( 'Lancaster County is currently safe. No incidents have been reported.', 'lancaster-live-incidents' )
			);

			return;

		}

		printf(
			'<p>%s</p>
			<hr />',
			esc_html( $this->widget_description )
		);

		print( '<ul class="lancaster-live-incidents">' );

		foreach ( $this->incidents as $key => $incident ) {

			printf(
				'<li class="%1$s">
					<p class="bold">%2$s</p>
					<p><em>%3$s</em><a href="%4$s" target="_blank" title="%5$s"><span class="dashicons dashicons-admin-site"></span></a></p>
					<p><small>%6$s</small></p>
				</li>',
				esc_attr( $key % 2 ? 'odd' : 'even' ),
				esc_html( $incident['title'] ),
				wp_kses_post( $incident['description'] ),
				esc_url( sprintf(
					'https://www.google.com/maps/search/%s',
					urlencode( $incident['description'] )
				) ),
				esc_attr__( 'View on Map', 'lancaster-live-incidents' ),
				sprintf(
					esc_html__( 'Reported: %1$s at %2$s', 'lancaster-live-incidents' ),
					get_date_from_gmt( $incident['pubDate'], get_option( 'date_format' ) ),
					get_date_from_gmt( $incident['pubDate'], get_option( 'time_format' ) )
				)
			);
	
		}

		print( '</ul>' );

	}

	public function get_lancaster_live_incidents_feed() {
	
		$response = wp_remote_get( 'https://webcad.lcwc911.us/Pages/Public/LiveIncidentsFeed.aspx' );

		if ( is_wp_error( $response ) ) {

			return array();

		}

		$body = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $body ) || empty( $body ) ) {

			return array();

		}

		$incidents = json_decode( json_encode( simplexml_load_string( $body ), true ), true );

		// If only one incident is reported, convert the item data into an array that we can loop over.
		if ( isset( $incidents['channel']['item'] ) && ! empty( $incidents['channel']['item'] ) && ! isset( $incidents['channel']['item'][0] ) ) {

			$incidents['channel']['item'] = array( $incidents['channel']['item'] );

		}

		return $incidents;

	}

}

new Lancaster_Live_Incidents();
