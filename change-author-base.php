<?php
/**
 * Plugin Name: Change Author Base
 * Plugin URI: https://rathly.com/plugins/change-author-base/
 * Description: Customize your WordPress author URLs with any base you want. Change from 'author' to 'about-us', 'team', or any custom base easily.
 * Version: 1.0.1
 * Author: Harry Laurel
 * Author URI: https://rathly.com/about-us/harrylaurel/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: change-author-base
 * 
 * @package Change_Author_Base
 * @author Harry Laurel
 * @copyright 2024 Rathly
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die('Direct access is not allowed.');
}

class Change_Author_Base {
    
    private $options_name = 'change_author_base_settings';
    private $default_base = 'about-us';
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Hook into WordPress init
        add_action('init', array($this, 'change_author_base'));
        
        // Add settings section to permalink page
        add_action('admin_init', array($this, 'add_permalink_settings'));
        
        // Hook into permalink structure update
        add_action('admin_init', array($this, 'save_permalink_settings'));
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        
        // Hook into plugin activation
        register_activation_hook(__FILE__, array($this, 'plugin_activate'));
        
        // Hook into plugin deactivation
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivate'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('change-author-base', false, dirname(plugin_basename(__FILE__)));
    }
    
    /**
     * Get the current author base
     */
    public function get_author_base() {
        $options = get_option($this->options_name);
        return isset($options['author_base']) ? sanitize_title($options['author_base']) : $this->default_base;
    }
    
    /**
     * Change the author base
     */
    public function change_author_base() {
        global $wp_rewrite;
        $wp_rewrite->author_base = $this->get_author_base();
        $wp_rewrite->author_structure = '/' . $wp_rewrite->author_base . '/%author%';
    }
    
    /**
     * Save permalink settings
     */
    public function save_permalink_settings() {
        if (!is_admin()) {
            return;
        }

        // Check if we're updating permalinks
        if (isset($_POST['permalink_structure']) || isset($_POST['category_base'])) {
            // Verify nonce
            check_admin_referer('update-permalink');
            
            // Get and sanitize the new author base
            $author_base = isset($_POST[$this->options_name]['author_base']) 
                ? sanitize_title(wp_unslash($_POST[$this->options_name]['author_base'])) 
                : $this->default_base;
            
            // Update the option
            update_option($this->options_name, array('author_base' => $author_base));
            
            // Flush rewrite rules
            flush_rewrite_rules();
        }
    }
    
    /**
     * Add settings to permalink page
     */
    public function add_permalink_settings() {
        // Add settings section
        add_settings_section(
            'change_author_base_section',
            esc_html__('Change Author Base Settings', 'change-author-base'),
            array($this, 'section_description'),
            'permalink'
        );
        
        // Add settings field
        add_settings_field(
            'change_author_base_field',
            esc_html__('Author Base', 'change-author-base'),
            array($this, 'settings_field_callback'),
            'permalink',
            'change_author_base_section'
        );
    }
    
    /**
     * Settings section description
     */
    public function section_description() {
        echo '<p>' . esc_html__('Customize the base slug for author URLs. Default is "about-us".', 'change-author-base') . '</p>';
    }
    
    /**
     * Settings field callback
     */
    public function settings_field_callback() {
        $options = get_option($this->options_name);
        $value = isset($options['author_base']) ? $options['author_base'] : $this->default_base;
        ?>
        <input type="text" 
               name="<?php echo esc_attr($this->options_name); ?>[author_base]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               required
        />
        <p class="description">
            <?php
            /* translators: %s: Example URL showing the author base structure. */
            printf(
                esc_html__('Your author URLs will be like: %s', 'change-author-base'),
                '<code>' . esc_html(home_url('/')) . '<strong>' . esc_html($value) . '</strong>/sample-author/</code>'
            );
            ?>
        </p>
        <?php
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        /* translators: %s: The settings page link text. */
        $settings_link = '<a href="' . esc_url(admin_url('options-permalink.php')) . '">' . 
                        esc_html__('Settings', 'change-author-base') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Actions to perform on plugin activation
     */
    public function plugin_activate() {
        // Set default options if they don't exist
        if (!get_option($this->options_name)) {
            add_option($this->options_name, array(
                'author_base' => $this->default_base
            ));
        }
        
        // Change the author base
        $this->change_author_base();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Actions to perform on plugin deactivation
     */
    public function plugin_deactivate() {
        global $wp_rewrite;
        // Reset to default author base
        $wp_rewrite->author_base = 'author';
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Remove all plugin options
        delete_option($this->options_name);
    }
}

// Initialize the plugin
new Change_Author_Base();