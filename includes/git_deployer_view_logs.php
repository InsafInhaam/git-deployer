<?php 

// Add submenu for logs
function git_deployer_add_submenu() {
    add_submenu_page(
        'git_deployer', // Parent slug
        'View Logs', // Page title
        'View Logs', // Submenu title
        'manage_options', // Capability
        'git_deployer_logs', // Menu slug
        'git_deployer_view_logs' // Function to display the logs
    );
}
add_action('admin_menu', 'git_deployer_add_submenu');

// Function to display the logs
function git_deployer_view_logs() {
    $log_file = plugin_dir_path(__FILE__) . 'git-deployer-debug.log';

    echo '<div class="wrap">';
    echo '<h1>Git Deployer Logs</h1>';

    if (file_exists($log_file)) {
        echo '<textarea readonly style="width: 100%; height: 500px; font-family: monospace;">';
        echo esc_textarea(file_get_contents($log_file));
        echo '</textarea>';
    } else {
        echo '<p>No log file found.</p>';
    }

    echo '</div>';
}