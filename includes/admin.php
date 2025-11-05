<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_options_page('DeepSeek Chatbot', 'DeepSeek Chatbot', 'manage_options', 'deepseek-chatbot', 'deepseek_chatbot_page');
});

function deepseek_chatbot_page() {
    if ($_POST['deepseek_api_key'] ?? false) {
        update_option('deepseek_api_key', sanitize_text_field($_POST['deepseek_api_key']));
        echo '<div class="notice notice-success"><p>API-Key gespeichert!</p></div>';
    }
    if ($_POST['crawl_site'] ?? false) {
        deepseek_chatbot_crawl();
    }
    $key = get_option('deepseek_api_key', '');
    $data = get_option('deepseek_site_data', []);
    ?>
    <div class="wrap">
        <h1>DeepSeek Chatbot</h1>
        <form method="post">
            <table class="form-table">
                <tr><th>API-Key</th><td><input type="password" name="deepseek_api_key" value="<?=esc_attr($key)?>" class="regular-text">
                    <p><a href="https://platform.deepseek.com" target="_blank">platform.deepseek.com</a></p></td></tr>
            </table>
            <?php submit_button('Speichern'); ?>
        </form>
        <hr>
        <h2>Website lernen</h2>
        <form method="post"><input type="hidden" name="crawl_site" value="1">
            <?php submit_button('Vollständig crawlen', 'secondary'); ?>
        </form>
        <p>✓ <?=count($data)?> Seiten gespeichert</p>
    </div>
    <?php
}

function deepseek_chatbot_crawl() {
    $posts = get_posts(['numberposts' => -1, 'post_status' => 'publish', 'post_type' => ['post','page']]);
    $data = [];
    foreach ($posts as $p) {
        $data[$p->ID] = [
            'title'   => $p->post_title,
            'content' => wp_strip_all_tags($p->post_content),
            'url'     => get_permalink($p->ID)
        ];
    }
    update_option('deepseek_site_data', $data);
}
