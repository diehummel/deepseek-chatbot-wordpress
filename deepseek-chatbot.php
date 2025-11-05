<?php
/**
 * Plugin Name: DeepSeek Chatbot
 * Description: Floating AI-Chatbubble mit voller Website-KI (DeepSeek API)
 * Version: 1.0.0
 * Author: Grok
 */

if (!defined('ABSPATH')) exit;
define('DEEPSEEK_CHATBOT_VERSION', '1.2.0');
define('DEEPSEEK_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DEEPSEEK_CHATBOT_PLUGIN_PATH', plugin_dir_path(__FILE__));

if (is_admin()) {
    require_once DEEPSEEK_CHATBOT_PLUGIN_PATH . 'includes/admin.php';
}
require_once DEEPSEEK_CHATBOT_PLUGIN_PATH . 'includes/frontend.php';

add_action('wp_enqueue_scripts', function() {
    if (is_admin()) return;
    wp_enqueue_script('deepseek-js', DEEPSEEK_CHATBOT_PLUGIN_URL . 'assets/chat.js', ['jquery'], DEEPSEEK_CHATBOT_VERSION, true);
    wp_enqueue_style('deepseek-css', DEEPSEEK_CHATBOT_PLUGIN_URL . 'assets/style.css', [], DEEPSEEK_CHATBOT_VERSION);
    wp_localize_script('deepseek-js', 'deepseek_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('deepseek_chat_nonce')
    ]);
});

add_action('wp_footer', function() {
    if (is_admin()) return; ?>
    <div id="deepseek-chatbot-bubble">Chat</div>
    <div id="deepseek-chatbot-container" class="closed">
        <div id="chat-header"><span>DeepSeek Bot</span><button id="chat-close">×</button></div>
        <div id="chat-messages"></div>
        <div id="chat-input-container">
            <input type="text" id="chat-input" placeholder="Frag mich zur Website…">
            <button id="chat-send">Senden</button>
        </div>
    </div>

register_uninstall_hook(__FILE__, 'deepseek_chatbot_uninstall');
function deepseek_chatbot_uninstall() {
    delete_option('deepseek_api_key');
    delete_option('deepseek_site_data');
}
<?php });
