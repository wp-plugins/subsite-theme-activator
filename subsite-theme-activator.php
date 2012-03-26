<?php
/*
Plugin Name: Subsite Theme Activator
Description: Activate a theme across all subsites.
Version: 1
Author: Colin Robinson
Author URI: http://www.colinrrobinson.com/
*/


/**
 * Add the "Subsite Activate" option to /wp-admin/network/themes.php
 */
function sa_add_subsite_activate_action( $actions, $theme_key ) {
	// Theme is not yet enabled
	if ( isset($actions['enable']) )
		return $actions;
	
	$actions['subsiteactivate'] = '<a href="' . esc_url( wp_nonce_url('themes.php?action=subsite_activate&amp;theme=' . $theme_key . '&amp;paged=1', 'subsiteactivate_' . $theme_key) ) . '" title="Activate this theme for all subsites" class="edit">Subsite Activate</a>';
	
	return $actions;	
}
add_filter('theme_action_links', 'sa_add_subsite_activate_action', 10, 2);


/**
 * The main function to active a themes across all subsites
 */
function sa_subsite_activate() {
	
	// Authenticate and verify nonce
	if ( !current_user_can('manage_network_themes') ) return;
	
	if ( !isset($_GET['theme']) || !isset($_GET['_wpnonce']) ) return;
	
	$theme = $_GET['theme'];
	$nonce = $_GET['_wpnonce'];
	
	if ( !wp_verify_nonce( $nonce, 'subsiteactivate_' . $theme ) ) return;

	// Get all the subsites
	global $wpdb;
	$query = "SELECT * FROM {$wpdb->blogs} as b ";
	$sql = $wpdb->prepare($query);
	$sites = $wpdb->get_results($sql, ARRAY_A);
	
	// Activate the theme for all sites
	foreach ( $sites as $site ) {
		switch_to_blog( $site['blog_id'] );
		switch_theme($theme, $theme);
	}
	restore_current_blog();
	
	add_action('network_admin_notices', 'sa_update_notice');
	
	return;
}
if ( isset($_GET['action']) && $_GET['action'] == 'subsite_activate' )
	add_action('init', 'sa_subsite_activate');


function sa_update_notice() {
    echo '<div class="updated"><p>All sites in the network set to ' . get_current_theme() . '</p></div>';
}

