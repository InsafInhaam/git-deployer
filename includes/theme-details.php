<?php
// Ensure this file is only accessible within the WordPress admin area.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Get the theme name from the URL.
$theme_name = isset($_GET['theme']) ? sanitize_text_field($_GET['theme']) : '';

// Check if the theme name is provided.
if (!$theme_name) {
    echo '<div class="error-message">Theme not specified.</div>';
    exit;
}

// Fetch theme details based on the theme name.
// This would involve custom logic to retrieve the details you need, such as GitHub URL, branches, etc.
$github_url = "https://github.com/insafinhaam/{$theme_name}"; // Replace with actual logic to get the GitHub URL.

echo "<div class='theme-details-container'>";
echo "<h1 class='theme-details-heading'>Details for Theme: " . esc_html($theme_name) . "</h1>";

// Display the GitHub URL.
echo "<div class='github-link'>
        <p><i class='fab fa-github'></i> GitHub Repository: <a href='" . esc_url($github_url) . "' target='_blank'>" . esc_html($github_url) . "</a></p>
      </div>";

// Form to delete a branch.
echo "<div class='form-container'>
        <h2>Delete Branch</h2>
        <form method='post' action=''>
            <input type='hidden' name='theme_name' value='" . esc_attr($theme_name) . "'>
            <label for='branch' class='form-label'>Branch to Delete:</label>
            <input type='text' name='branch' id='branch' class='form-input' required placeholder='main'>
            <button type='submit' name='delete_branch' class='btn btn-danger'><i class='fas fa-trash'></i> Delete Branch</button>
        </form>
      </div>";

// Form to pull changes.
echo "<div class='form-container'>
        <h2>Pull Changes</h2>
        <form method='post' action=''>
            <input type='hidden' name='theme_name' value='" . esc_attr($theme_name) . "'>
            <button type='submit' name='pull_changes' class='btn btn-primary'>
            <i class='fa-solid fa-rotate'></i> Pull Changes</button>
        </form>
      </div>";

// Toggle for enabling auto pull.
$auto_pull_enabled = false; // Fetch from database or settings.
echo "<div class='form-container'>
        <h2>Auto Pull Settings</h2>
        <form method='post' action=''>
            <input type='hidden' name='theme_name' value='" . esc_attr($theme_name) . "'>
            <label for='auto_pull' class='form-label'>Enable Auto Pull:</label>
            <input type='checkbox' name='auto_pull' id='auto_pull' value='1' " . checked($auto_pull_enabled, true, false) . " class='form-checkbox'>
            <button type='submit' name='set_auto_pull' class='btn btn-success'><i class='fas fa-save'></i> Save</button>
        </form>
      </div>";

// Display pull history.
echo "<div class='pull-history'>
        <h2>Pull History</h2>
        <ul class='history-list'>
            <li><i class='fas fa-clock'></i> 2025-01-10: Pulled changes from branch 'main'</li>
            <li><i class='fas fa-clock'></i> 2025-01-05: Pulled changes from branch 'development'</li>
        </ul>
      </div>";

echo "</div>";
?>
