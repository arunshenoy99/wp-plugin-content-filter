<?php

/*
    Plugin Name: Word Filter
    Description: Replaces a list of words.
    Version: 1.0
    Author: Arun
    Author Uri: https://github.com/arunshenoy99
*/ 

if (! defined('ABSPATH')) exit; // Exit if accessed directly i.e load the url of this php file in the browser

class WordFilterPlugin {
    function __construct() {
        add_action('admin_menu', array($this, 'menu'));
        if (get_option('wfp_words')) add_filter('the_content', array($this, 'filterLogic'));
        add_action('admin_init', array($this, 'settings'));
    }

    function settings() {
        add_settings_section('wfp_replacement_text_section', null, null, 'word-filter-options');
        register_setting('replacementFields', 'wfp_replacement_text');
        add_settings_field(
            'wfp_replacement_text',
            'Filtered Text',
            array($this, 'replacementFieldHTML'),
            'word-filter-options',
            'wfp_replacement_text_section'
        );
    }

    function replacementFieldHTML() { ?>
        <input type="text" name="wfp_replacement_text" value="<?php echo esc_attr(get_option('wfp_replacement_text', '***')); ?>">
        <p>Leave blank to simply remove the filter word</p>
    <?php }

    function filterLogic($content) {
        $badWords = explode(',', get_option('wfp_words'));
        $badWordsTrimmed = array_map('trim', $badWords);
        return str_ireplace($badWordsTrimmed, esc_html(get_option('wfp_replacement_text', '****')), $content);
    }

    function menu() {
        // The add_menu_page returns a hook we can call whenever that menu page loads
        $mainPageHook = add_menu_page(
            // This shows up on the tab of the browser
             'Words To Filter',
             // This is the actual title that appears in the menu
             'Word Filter',
             // This is the capability required to view the menu
             'manage_options',
             // This is the slug for the menu page
             'wordfilter',
             // This is the HTML for the menu page
             array($this, 'wordFilterPage'),
             // This is the icon for the menu title, best way is inline svg as wordpress can then customize the color
             'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMCAyMEMxNS41MjI5IDIwIDIwIDE1LjUyMjkgMjAgMTBDMjAgNC40NzcxNCAxNS41MjI5IDAgMTAgMEM0LjQ3NzE0IDAgMCA0LjQ3NzE0IDAgMTBDMCAxNS41MjI5IDQuNDc3MTQgMjAgMTAgMjBaTTExLjk5IDcuNDQ2NjZMMTAuMDc4MSAxLjU2MjVMOC4xNjYyNiA3LjQ0NjY2SDEuOTc5MjhMNi45ODQ2NSAxMS4wODMzTDUuMDcyNzUgMTYuOTY3NEwxMC4wNzgxIDEzLjMzMDhMMTUuMDgzNSAxNi45Njc0TDEzLjE3MTYgMTEuMDgzM0wxOC4xNzcgNy40NDY2NkgxMS45OVoiIGZpbGw9IiNGRkRGOEQiLz4KPC9zdmc+Cg==',
             // Priority/position of the plugin menu in the admin menu
             100
        );
        // Customize the sub menu for the plugin menu, by default this takes the name of Word Filter
        add_submenu_page(
            // Menu to which add the submenu
            'wordfilter',
            // This is the title on the browser tab
            'Words to Filter',
            // This is the sub menu title
            'Words List',
            // This is the capability required to view the sub menu
            'manage_options',
            // Slug of the page, same as the menu page
            'wordfilter',
            // HTML of the sub menu page, same as the menu page
            array($this, 'wordFilterPage')
        );

        add_submenu_page(
            // Menu under which to add the sub menu
            'wordfilter',
            // Title appears in the browser window
            'Word Filter Options',
            // Title for the sub menu
            'Options',
            // Capability required to view the sub menu
            'manage_options',
            // Slug for the sub menu page
            'word-filter-options',
            // HTML for the sub menu page
            array($this, 'optionsSubPage')
        );
        // Load css only when the plugin menu page loads
        add_action("load-{$mainPageHook}", array($this, 'mainPageAssets'));
    }

    function mainPageAssets() {
        wp_enqueue_style(
            'filterAdminCss',
            plugin_dir_url(__FILE__) . 'styles.css'
        );
    }

    function handleForm() {
        if (wp_verify_nonce($_POST['ourNonce'], 'saveFilteredWords') and current_user_can('manage_options')) {
            update_option('wfp_words', sanitize_text_field($_POST['plugin-words-to-filter'])); ?>
            <div class="updated">
                <p>Your filtered words were saved.</p>
            </div>
        <?php } else { ?>
            <div class="error">
                <p>Sorry you do not have permission to perform that action.</p>
            </div>
        <?php }
    }

    function wordFilterPage () { ?>
        <div class="wrap">
            <h1>Word Filter</h1>
            <?php if ($_POST['justsubmitted'] == 'true') $this->handleForm() ?>
            <form method="POST">
                <input type="hidden" name="justsubmitted" value="true">
                <?php wp_nonce_field('saveFilteredWords', 'ourNonce'); ?>
                <label for="plugin-words-to-filter">
                    <p>Enter a <strong>comma separated </strong>list of words to filter.</p>
                </label>
                <div class="word-filter__flex-container">
                    <textarea name="plugin-words-to-filter" id="plugin-words-to-filter" placeholder="bad, mean, awful, horrible"><?php echo esc_textarea(get_option('wfp_words')); ?></textarea>
                </div>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            </form>
        </div>
    <?php }

    function optionsSubPage () { ?>
        <div class="wrap">
            <h1>Word Filter Options</h1>
            <form action="options.php" method="POST">
                <?php
                // This gets added in the word ount plugin because that is part of the settings menu.
                settings_errors();
                settings_fields('replacementFields');
                do_settings_sections('word-filter-options');
                submit_button(); 
                ?>
            </form>
        </div>
    <?php }
}

$wordFilterPlugin = new WordFilterPlugin();

?>