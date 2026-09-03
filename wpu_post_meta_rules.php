<?php
defined('ABSPATH') || die;
/*
Plugin Name: WPU Post Meta Rules
Description: Block publication if some post metas dont match the defined rules
Plugin URI: https://github.com/WordPressUtilities/wpu_post_meta_rules
Update URI: https://github.com/WordPressUtilities/wpu_post_meta_rules
Version: 0.1.0
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wpu_post_meta_rules
Domain Path: /lang
Requires at least: 6.2
Requires PHP: 8.0
Network: Optional
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUPostMetaRules {

    private $fields = array();
    private $options = array(
        'plugin_name' => 'WPU Post Meta Rules',
        'plugin_id' => 'wpu_post_meta_rules'
    );
    private $running = false;
    private $messages;
    private $plugin_description = '';

    public function __construct() {
        add_action('init', array(&$this, 'load_translation'));
        add_action('init', array(&$this, 'load_messages'));
        add_action('init', array(&$this, 'init'));
    }

    public function load_translation() {
        # TRANSLATION
        $lang_dir = dirname(plugin_basename(__FILE__)) . '/lang/';
        if (strpos(__DIR__, 'mu-plugins') !== false) {
            load_muplugin_textdomain('wpu_post_meta_rules', $lang_dir);
        } else {
            load_plugin_textdomain('wpu_post_meta_rules', false, $lang_dir);
        }
        $this->plugin_description = __('Block publication if some post metas dont match the defined rules', 'wpu_post_meta_rules');

    }

    public function load_messages() {
        require_once __DIR__ . '/inc/WPUBaseMessages/WPUBaseMessages.php';
        $this->messages = new \wpu_post_meta_rules\WPUBaseMessages($this->options['plugin_id']);
    }

    public function init() {
        /* 'post_types' is optional : applies to any post type if not set */
        $this->fields = apply_filters('wpu_post_meta_rules__fields', array());
        if(!is_array($this->fields)){
            $this->fields = array();
        }

        /* Runs after ACF (priority 10) has saved the metas */
        add_action('save_post', array(&$this, 'save_post'), 90, 2);
    }

    public function save_post($post_id, $post) {
        if ($this->running) {
            return;
        }
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || $post->post_status != 'publish') {
            return;
        }

        $errors = $this->get_validation_errors($post_id, $post);
        if (!$errors) {
            return;
        }

        $sep = '<br />- ';
        $this->messages->set_message('invalid_fields', esc_html__('Publication blocked, invalid fields :', 'wpu_post_meta_rules') . $sep . implode($sep, array_map('esc_html', $errors)), 'error');

        $this->running = true;
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft'
        ));
        $this->running = false;
    }

    /* Pure validation : returns a list of human-readable error strings */
    public function get_validation_errors($post_id, $post) {
        $errors = array();
        foreach ($this->fields as $meta_key => $rules) {
            $post_types = isset($rules['post_types']) ? (array) $rules['post_types'] : array();
            if ($post_types && !in_array($post->post_type, $post_types, true)) {
                continue;
            }
            $name = !empty($rules['name']) ? $rules['name'] : $meta_key;
            $length = mb_strlen(trim((string) get_post_meta($post_id, $meta_key, true)));
            if (isset($rules['min']) && $length < $rules['min']) {
                /* translators: %1$s: field name, %2$s: min length */
                $errors[] = sprintf(_n('%1$s : minimum %2$s character', '%1$s : minimum %2$s characters', $rules['min'], 'wpu_post_meta_rules'), $name, $rules['min']);
                continue;
            }
            if (isset($rules['max']) && $length > $rules['max']) {
                /* translators: %1$s: field name, %2$s: max length */
                $errors[] = sprintf(_n('%1$s : maximum %2$s character', '%1$s : maximum %2$s characters', $rules['max'], 'wpu_post_meta_rules'), $name, $rules['max']);
            }
        }
        return $errors;
    }
}

new WPUPostMetaRules();
