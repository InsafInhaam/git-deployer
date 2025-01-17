<?php
/**
 * Plugin Name: Git Deployer
 * Plugin URI:  https://yourwebsite.com/git-deployer
 * Description: A plugin to install WordPress themes from a Git repository and pull changes.
 * Version:     1.1
 * Author:      Insaf Inhaam
 * Author URI:  https://insafinhaam.com
 * License:     GPL2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function git_deployer_enqueue_styles()
{
    // Only load the stylesheet on the admin page for Git Deployer
    // if (isset($_GET['page']) && $_GET['page'] === 'git_deployer') {
        wp_enqueue_style('git-deployer-styles', plugin_dir_url(__FILE__) . 'assets/css/style.css');
        wp_enqueue_style('font-awesome', plugin_dir_url(__FILE__) . 'assets/fontawesome/css/all.min.css', array(), null);

    // }
}
add_action('admin_enqueue_scripts', 'git_deployer_enqueue_styles');

// Create the plugin's settings page
function git_deployer_menu()
{
    add_menu_page(
        'Git Deployer', // Page title
        'Git Deployer', // Menu title
        'manage_options', // Capability
        'git_deployer', // Menu slug
        'git_deployer_page', // Function to display the page
        'dashicons-hammer', // Icon
        80 // Position
    );

    add_submenu_page(
        'git_deployer', // Parent slug
        'View Logs', // Page title
        'View Logs', // Submenu title
        'manage_options', // Capability
        'git_deployer_logs', // Menu slug
        'git_deployer_view_logs' // Function to display the logs
    );

    // Add a submenu item for theme details
    add_submenu_page(
        'git_deployer', // Parent menu slug
        'Theme Details', // Page title
        'Theme Details', // Menu title
        'manage_options', // Capability required to access
        'git_deployer_theme_details', // Menu slug
        'git_deployer_theme_details_page' // Function to render the page
    );
}
add_action('admin_menu', 'git_deployer_menu');


// Function to render the Theme Details page
function git_deployer_theme_details_page() {
    // Check if the theme parameter is provided
    if (isset($_GET['theme'])) {
        $theme_slug = sanitize_text_field($_GET['theme']); // Sanitize the theme parameter
        
        // Include your theme details page file
        include(plugin_dir_path(__FILE__) . 'includes/theme-details.php');
    } else {
        echo '<h1><strong>No theme selected.</strong></h1>';
    }
}


// Plugin settings page HTML
function git_deployer_page()
{
    ?>
    <div class="wrap">
        <h1 style="font-size: 24px; margin-bottom: 20px;">Install Theme from Git Repository</h1>
        <form method="post" action=""
            style="max-width: 600px; padding: 20px; background-color: #f9f9f9; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="margin-bottom: 15px;">
                <label for="git_repo_url" style="font-weight: bold; display: block; margin-bottom: 5px;">Git Repository
                    URL:</label>
                <input type="text" name="git_repo_url" id="git_repo_url" class="regular-text"
                    placeholder="https://github.com/username/theme-repo"
                    style="width: 100%;  border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="git_branch" style="font-weight: bold; display: block; margin-bottom: 5px;">Branch Name:</label>
                <input type="text" name="git_branch" id="git_branch" class="regular-text" placeholder="main"
                    style="width: 100%;  border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
            </div>

            <div style="text-align: left;">
                <button type="submit" name="install_theme" class="button button-primary"
                    style="padding: 0 10px; font-size: 16px; background-color: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s;">
                    Install Theme
                </button>
            </div>
        </form>

        <?php
        if (isset($_POST['install_theme'])) {
            git_deployer_install_theme();
        }

        // List installed themes and give an option to pull the latest changes
        git_deployer_list_installed_themes();
        ?>
    </div>
    <?php
}

// Function to handle the theme installation from the provided GitHub repository
function git_deployer_install_theme()
{
    if (isset($_POST['git_repo_url']) && isset($_POST['git_branch'])) {
        $repo_url = sanitize_text_field($_POST['git_repo_url']);
        $branch = sanitize_text_field($_POST['git_branch']);

        // Validate the repository URL
        if (filter_var($repo_url, FILTER_VALIDATE_URL) === false) {
            echo '<div class="error"><p>Invalid Git repository URL.</p></div>';
            return;
        }

        // Define the theme directory path
        $theme_dir = WP_CONTENT_DIR . '/themes/';
        $theme_name = basename($repo_url, '.git'); // Get theme name from the URL

        // Check if the theme already exists
        if (is_dir($theme_dir . $theme_name)) {
            echo '<div class="error"><p>The theme already exists in the themes directory.</p></div>';
            return;
        }

        // Define the full path to the Git command
        $git_path = '/usr/bin/git'; // Use the path returned by `which git`
        $command = "{$git_path} clone -b {$branch} {$repo_url} \"{$theme_dir}{$theme_name}\"";

        // Capture the output and errors
        $output = shell_exec($command . ' 2>&1'); // Redirect stderr to stdout

        if ($output) {
            // Log the output to a debug log file for debugging purposes
            $log_file = plugin_dir_path(__FILE__) . 'git-deployer-debug.log';
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Command: $command\n$output\n\n", FILE_APPEND);

            // Check if output contains a successful message
            if (strpos($output, 'fatal') === false && strpos($output, 'error') === false) {
                // Activate the theme after successful installation
                switch_theme($theme_name); // Activate the theme programmatically
                echo '<div class="updated"><p>Theme installed and activated successfully!</p></div>';
            } else {
                echo '<div class="error"><p>Theme installation failed. Check the debug log for more details.</p></div>';
            }
        } else {
            echo '<div class="error"><p>Theme installation failed. No output received.</p></div>';
        }
    } else {
        echo '<div class="error"><p>Please provide both the Git repository URL and branch name.</p></div>';
    }
}




// Function to list installed themes and give options to pull updates or delete
function git_deployer_list_installed_themes()
{
    $theme_dir = WP_CONTENT_DIR . '/themes/';
    $themes = array_diff(scandir($theme_dir), array('..', '.'));

    echo '<div class="theme-list-container">';
    echo '<h2>Installed Themes</h2>';

    if (!empty($themes)) {
        echo '<ul style="list-style: none; padding: 0;">';
        foreach ($themes as $theme) {
            if (is_dir($theme_dir . $theme)) {
                echo '<li style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">';
                echo '<strong class="theme-list-theme-title">';
                echo '<i class="fab fa-github"></i>&nbsp;';
                echo '<a href="' . admin_url('admin.php?page=git_deployer_theme_details&theme=' . urlencode($theme)) . '" style="text-decoration: none; color: inherit;">' . esc_html($theme) . '</a>';
                echo '</strong>';
                echo '<div style="margin-top: 5px;">';

                // View button
                echo '<a href="' . admin_url('admin.php?page=git_deployer_theme_details&theme=' . urlencode($theme)) . '" class="pull_updates" style="margin-right: 10px; padding: 5px 10px; background-color: #000; color: #fff; border: none; border-radius: 3px; cursor: pointer; text-decoration: none;"><i class="fa-solid fa-eye"></i>&nbsp;View</a>';

                // Pull updates button
                echo '<form method="post" action="" style="display:inline-block; margin-right: 10px;">';
                echo '<input type="hidden" name="theme_name" value="' . esc_attr($theme) . '">';
                echo '<button class="pull_updates" type="submit" name="pull_updates" style="padding: 5px 10px; background-color: #0073aa; color: #fff; border: none; border-radius: 3px; cursor: pointer;"><i class="fa-solid fa-rotate"></i>&nbsp;Pull Updates</button>';
                echo '</form>';

                // Remove theme button
                echo '<form method="post" action="" style="display:inline-block;">';
                echo '<input type="hidden" name="theme_name" value="' . esc_attr($theme) . '">';
                echo '<button class="remove_theme" type="submit" name="remove_theme" style="padding: 5px 10px; background-color: #dc3545; color: #fff; border: none; border-radius: 3px; cursor: pointer;"><i class="fas fa-trash"></i>&nbsp;Remove Theme</button>';
                echo '</form>';

                echo '</div>';
                echo '</li>';
            }
        }

        echo '</ul>';
    } else {
        echo '<p>No themes installed.</p>';
    }

    echo '</div>';

    // Handle the pull updates request
    if (isset($_POST['pull_updates'])) {
        git_deployer_pull_updates(sanitize_text_field($_POST['theme_name']));
    }

    // Handle the delete theme request
    if (isset($_POST['delete_theme'])) {
        git_deployer_delete_theme(sanitize_text_field($_POST['theme_name']));
    }
}

// Function to pull the latest changes from the Git repository
function git_deployer_pull_updates($theme_name)
{
    $theme_dir = WP_CONTENT_DIR . '/themes/' . $theme_name;
    if (is_dir($theme_dir)) {
        $escaped_theme_dir = escapeshellarg($theme_dir);
        $git_path = '/usr/bin/git';
        $command = "{$git_path} -C {$escaped_theme_dir} pull";
        $output = shell_exec($command . ' 2>&1');
        if ($output) {
            $log_file = plugin_dir_path(__FILE__) . 'git-deployer-debug.log';
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Command: $command\n$output\n\n", FILE_APPEND);
            if (strpos($output, 'Already up to date.') !== false) {
                echo '<div class="updated"><p>The theme is already up to date.</p></div>';
            } elseif (strpos($output, 'fatal') === false && strpos($output, 'error') === false) {
                echo '<div class="updated"><p>Theme updated successfully!</p></div>';
            } else {
                echo '<div class="error"><p>Failed to update the theme. Check the debug log for more details.</p></div>';
            }
        } else {
            echo '<div class="error"><p>Failed to pull updates. No output received.</p></div>';
        }
    } else {
        echo '<div class="error"><p>The theme does not exist.</p></div>';
    }
}

// Function to delete the selected theme
function git_deployer_delete_theme($theme_name)
{
    $theme_dir = WP_CONTENT_DIR . '/themes/' . $theme_name;
    if (is_dir($theme_dir)) {
        // Recursively delete the theme directory
        git_deployer_recursive_delete($theme_dir);
        echo '<div class="updated"><p>Theme deleted successfully!</p></div>';
    } else {
        echo '<div class="error"><p>The theme does not exist.</p></div>';
    }
}

// Helper function to recursively delete a directory
function git_deployer_recursive_delete($dir)
{
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..')
            continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            git_deployer_recursive_delete($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}




// Security check during activation
function git_deployer_activate()
{
    // You can check for server environment or dependencies here
}
register_activation_hook(__FILE__, 'git_deployer_activate');


// Function to display the logs
function git_deployer_view_logs()
{
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

