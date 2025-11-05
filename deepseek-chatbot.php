<?php
/**
 * Plugin Name: DeepSeek Chatbot
 * Description: KI-Sprechblase mit voller Website-Intelligenz
 * Version: 1.4.1
 * Author: Du
 */

if (!defined('ABSPATH')) exit;

define('DSB', [plugin_dir_url(__FILE__), plugin_dir_path(__FILE__)]);
require_once DSB[1] . 'includes/admin.php';
require_once DSB[1] . 'includes/frontend.php';

add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;
    wp_enqueue_script('dsb-js', DSB[0] . 'assets/chat.js', ['jquery'], '1.4.1', true);
    wp_enqueue_style('dsb-css', DSB[0] . 'assets/style.css', [], '1.4.1');
    wp_localize_script('dsb-js', 'dsb', [
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dsb'),
        'welcome' => nl2br(esc_html(get_option('dsb_welcome', "Hallo! Ich bin dein KI-Assistent.\nFrag mich alles über diese Website! 😊")))
    ]);
});

add_action('wp_footer', function () {
    if (is_admin()) return; ?>
    <div id="dsb-bubble">✉</div>
    <div id="dsb-chat" class="closed">
        <div id="dsb-header">KI-Assistent <span id="dsb-close">✕</span></div>
        <div id="dsb-messages"></div>
        <div id="dsb-input">
            <input type="text" id="dsb-text" placeholder="Deine Frage…">
            <button id="dsb-send">➤</button>
        </div>
    </div>
<?php });
