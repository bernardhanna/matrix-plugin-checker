<?php
/**
 * @Author: Bernard Hanna
 * @Date:   2023-09-18 08:35:28
 * @Last Modified by:   Bernard Hanna
 * @Last Modified time: 2023-09-18 08:44:55
 */

/**
 * Plugin Name: Matrix Plugin Checker
 * Description: Lists all installed plugins sorted by last update date.
 * Version: 1.0
 * Author: Bernard Hanna
 */

// Register a submenu under the Tools menu in the WordPress admin dashboard
add_action('admin_menu', 'register_my_custom_submenu_page');

// Function to add the submenu
function register_my_custom_submenu_page() {
    // Add a submenu page under 'Tools'
    add_submenu_page(
        'tools.php', // Parent menu slug
        'Plugin Checker', // Page title
        'Plugin List by Last Updated on Wordpress Repo', // Menu title
        'manage_options', // Capability
        'plugin_list_by_update_date', // Menu slug
        'display_plugin_list' // Function to display content
    );
}

// Function to display the plugin list and the "Run Check" button
function display_plugin_list() {
    // Start the admin page wrapper
    echo '<div class="wrap">';
    // Display the title of the admin page
    echo '<h1>Plugin List by Update Date</h1>';
    // Add a button to trigger the plugin check
    echo '<button id="run-plugin-check">Run Check</button>';
    // Add a container to display the plugin list
    echo '<div id="plugin-list-container"></div>';
    // End the admin page wrapper
    echo '</div>';

    // JavaScript code to handle the "Run Check" button click
    ?>
    <script>
    // Wait for the DOM to be ready
    jQuery(document).ready(function($) {
        // Add click event listener to the "Run Check" button
        $('#run-plugin-check').click(function() {
            // Display "Checking Plugins" text
            $('#plugin-list-container').html('<p>Checking Plugins...</p>');
            
            // Make an AJAX request to fetch the plugin data
            $.ajax({
                url: ajaxurl, // WordPress AJAX URL
                data: {
                    'action': 'fetch_plugin_data' // Action hook
                },
                success: function(response) {
                    // Populate the plugin list container with the fetched data
                    $('#plugin-list-container').html(response);
                },
                error: function() {
                    // Show an alert if the AJAX request fails
                    alert('Failed to fetch plugin data.');
                }
            });
        });
    });
    </script>
    <?php
}

// Register the AJAX action to fetch the plugin data
add_action('wp_ajax_fetch_plugin_data', 'fetch_plugin_data');

// Function to fetch and display the plugin data
function fetch_plugin_data() {
    // Get all installed plugins
    $all_plugins = get_plugins();
    // Initialize an empty array to hold the plugin data
    $plugin_data = [];

    // Loop through each installed plugin
    foreach ($all_plugins as $plugin_path => $plugin_details) {
        // Get the plugin slug
        $slug = dirname($plugin_path);
        // Skip if the slug is empty
        if (empty($slug)) continue;

        // Make an API request to get plugin details
        $request = wp_remote_get('https://api.wordpress.org/plugins/info/1.0/' . $slug . '.json');
        // Skip if the API request fails
        if (is_wp_error($request)) continue;

        // Decode the API response
        $plugin_info = json_decode(wp_remote_retrieve_body($request));
        // Skip if the API response is empty
        if (empty($plugin_info)) continue;

        // Get the last updated date or set it to 'Unknown'
        $last_updated = isset($plugin_info->last_updated) ? $plugin_info->last_updated : 'Unknown';
        // Add the plugin data to the array
        $plugin_data[] = [
            'name' => $plugin_details['Name'],
            'last_updated' => $last_updated
        ];
    }

    // Sort the plugins by their last updated date
    usort($plugin_data, function($a, $b) {
        return strtotime($a['last_updated']) <=> strtotime($b['last_updated']);
    });

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Plugin Name</th><th>Last Updated</th><th>Information</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($plugin_data as $plugin) {
        $last_updated = $plugin['last_updated'];
        $date_diff = (strtotime('now') - strtotime($last_updated)) / (60 * 60 * 24);
    
        $highlight = '';
        $information = '';
        $text_color = 'style="color: black;"';
    
        if ($date_diff > 180 && $last_updated !== 'Unknown') {
            $highlight = 'style="background-color: red; color: black;"';
            $information = 'Not updated in last 6 months!';
        } elseif ($last_updated === 'Unknown') {
            $highlight = 'style="background-color: yellow; color: black;"';
            $information = 'Could not find last update date, possible reasons are because it is a premium plugin, custom plugin or has been removed from the WordPress Repository';
        }
    
        echo "<tr $highlight><td $text_color>{$plugin['name']}</td><td $text_color>{$last_updated}</td><td $text_color>{$information}</td></tr>";
    }
    
    echo '</tbody>';
    echo '</table>';

    die();
}

// Enqueue jQuery for the admin page
add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');

// Function to enqueue jQuery
function enqueue_admin_scripts() {
    // Enqueue the jQuery library
    wp_enqueue_script('jquery');
}
