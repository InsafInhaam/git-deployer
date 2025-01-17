<?php
/**
 * Plugin Name: Git Deployer
 * Plugin URI:  https://yourwebsite.com/git-deployer
 * Description: A plugin to install WordPress themes from a Git repository and pull changes.
 * Version:     1.1
 * Author:      Your Name
 * Author URI:  https://yourwebsite.com
 * License:     GPL2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function git_deployer_enqueue_styles() {
    // Only load the stylesheet on the admin page for Git Deployer
    if (isset($_GET['page']) && $_GET['page'] === 'git_deployer') {
        wp_enqueue_style('git-deployer-styles', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    }
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
}
add_action('admin_menu', 'git_deployer_menu');

// Plugin settings page HTML
function git_deployer_page()
{
    ?>
    <div class="wrap">
        <h1 style="font-size: 24px; margin-bottom: 20px;">Install Theme from Git Repository</h1>
        <form method="post" action=""
            style="max-width: 600px; padding: 20px; background-color: #f9f9f9; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="margin-bottom: 15px;">
                <label for="git_repo_url" style="font-weight: bold; display: block; margin-bottom: 5px;">Git Repository URL:</label>
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
                echo '<strong class="theme-list-theme-title"><svg xmlns="http://www.w3.org/2000/svg" height="20" width="19.375" viewBox="0 0 496 512"><path d="M165.9 397.4c0 2-2.3 3.6-5.2 3.6-3.3 .3-5.6-1.3-5.6-3.6 0-2 2.3-3.6 5.2-3.6 3-.3 5.6 1.3 5.6 3.6zm-31.1-4.5c-.7 2 1.3 4.3 4.3 4.9 2.6 1 5.6 0 6.2-2s-1.3-4.3-4.3-5.2c-2.6-.7-5.5 .3-6.2 2.3zm44.2-1.7c-2.9 .7-4.9 2.6-4.6 4.9 .3 2 2.9 3.3 5.9 2.6 2.9-.7 4.9-2.6 4.6-4.6-.3-1.9-3-3.2-5.9-2.9zM244.8 8C106.1 8 0 113.3 0 252c0 110.9 69.8 205.8 169.5 239.2 12.8 2.3 17.3-5.6 17.3-12.1 0-6.2-.3-40.4-.3-61.4 0 0-70 15-84.7-29.8 0 0-11.4-29.1-27.8-36.6 0 0-22.9-15.7 1.6-15.4 0 0 24.9 2 38.6 25.8 21.9 38.6 58.6 27.5 72.9 20.9 2.3-16 8.8-27.1 16-33.7-55.9-6.2-112.3-14.3-112.3-110.5 0-27.5 7.6-41.3 23.6-58.9-2.6-6.5-11.1-33.3 2.6-67.9 20.9-6.5 69 27 69 27 20-5.6 41.5-8.5 62.8-8.5s42.8 2.9 62.8 8.5c0 0 48.1-33.6 69-27 13.7 34.7 5.2 61.4 2.6 67.9 16 17.7 25.8 31.5 25.8 58.9 0 96.5-58.9 104.2-114.8 110.5 9.2 7.9 17 22.9 17 46.4 0 33.7-.3 75.4-.3 83.6 0 6.5 4.6 14.4 17.3 12.1C428.2 457.8 496 362.9 496 252 496 113.3 383.5 8 244.8 8zM97.2 352.9c-1.3 1-1 3.3 .7 5.2 1.6 1.6 3.9 2.3 5.2 1 1.3-1 1-3.3-.7-5.2-1.6-1.6-3.9-2.3-5.2-1zm-10.8-8.1c-.7 1.3 .3 2.9 2.3 3.9 1.6 1 3.6 .7 4.3-.7 .7-1.3-.3-2.9-2.3-3.9-2-.6-3.6-.3-4.3 .7zm32.4 35.6c-1.6 1.3-1 4.3 1.3 6.2 2.3 2.3 5.2 2.6 6.5 1 1.3-1.3 .7-4.3-1.3-6.2-2.2-2.3-5.2-2.6-6.5-1zm-11.4-14.7c-1.6 1-1.6 3.6 0 5.9 1.6 2.3 4.3 3.3 5.6 2.3 1.6-1.3 1.6-3.9 0-6.2-1.4-2.3-4-3.3-5.6-2z"/></svg>&nbsp;' . esc_html($theme) . '</strong>';
                echo '<div style="margin-top: 5px;">';
                
                // Pull updates button
                echo '<form method="post" action="" style="display:inline-block; margin-right: 10px;">';
                echo '<input type="hidden" name="theme_name" value="' . esc_attr($theme) . '">';
                echo '<button class="pull_updates" type="submit" name="pull_updates" style="padding: 5px 10px; background-color: #0073aa; color: #fff; border: none; border-radius: 3px; cursor: pointer;"><svg xmlns="http://www.w3.org/2000/svg" height="14" width="14" viewBox="0 0 512 512"><path fill="#ffffff" d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160 352 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l111.5 0c0 0 0 0 0 0l.4 0c17.7 0 32-14.3 32-32l0-112c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1L16 432c0 17.7 14.3 32 32 32s32-14.3 32-32l0-35.1 17.6 17.5c0 0 0 0 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.8c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352l34.4 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L48.4 288c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>&nbsp;Pull Changes</button>';
                echo '</form>';

                // Delete button
                echo '<form method="post" action="" style="display:inline-block;">';
                echo '<input type="hidden" name="theme_name" value="' . esc_attr($theme) . '">';
                echo '<button class="delete_theme" type="submit" name="delete_theme" style="padding: 5px 10px; background-color: #a00; color: #fff; border: none; border-radius: 3px; cursor: pointer;"><svg xmlns="http://www.w3.org/2000/svg" height="14" width="12.25" viewBox="0 0 448 512"><path fill="#ffffff" d="M135.2 17.7L128 32 32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0-7.2-14.3C307.4 6.8 296.3 0 284.2 0L163.8 0c-12.1 0-23.2 6.8-28.6 17.7zM416 128L32 128 53.2 467c1.6 25.3 22.6 45 47.9 45l245.8 0c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>&nbsp;Delete Theme</button>';
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
        if ($item == '.' || $item == '..') continue;
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

