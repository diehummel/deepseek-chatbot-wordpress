<?php
/**
 * Plugin Name: DeepSeek Chatbot
 * Description: KI-Sprechblase mit voller Website-Intelligenz
 * Version: 1.3
 * Author: Du + Grok
 */

if (!defined('ABSPATH')) exit;
define('DSB_URL', plugin_dir_url(__FILE__));
define('DSB_PATH', plugin_dir_path(__FILE__));

require_once DSB_PATH . 'includes/admin.php';
require_once DSB_PATH . 'includes/frontend.php';

add_action('wp_enqueue_scripts', function() {
    if (is_admin()) return;
    wp_enqueue_script('dsb-js', DSB_URL . 'assets/chat.js', ['jquery'], '1.3', true);
    wp_enqueue_style('dsb-css', DSB_URL . 'assets/style.css', [], '1.3');
    wp_localize_script('dsb-js', 'dsb', [
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dsb_nonce'),
        'welcome' => get_option('dsb_welcome', "Hallo! Ich bin dein KI-Assistent.\nFrag mich alles über diese Website! 😊")
    ]);
});

add_action('wp_footer', function() {
    if (is_admin()) return; ?>
    <div id="dsb-bubble">
        <svg viewBox="0 0 24 24"><path fill="#fff" d="M20 2H4c-1.1 0-2 .9-2 2v14l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
    </div>
    <div id="dsb-chat" class="closed">
        <div id="dsb-header">KI-Assistent <span id="dsb-close">✕</span></div>
        <div id="dsb-messages"></div>
        <div id="dsb-input">
            <input type="text" id="dsb-text" placeholder="Deine Frage...">
            <button id="dsb-send">➢</button>
        </div>
    </div>
<?php });
