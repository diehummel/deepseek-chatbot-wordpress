<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_dsb_chat', 'dsb_chat');
add_action('wp_ajax_nopriv_dsb_chat', 'dsb_chat');

function dsb_chat() {
    check_ajax_referer('dsb', 'nonce');
    $key = get_option('deepseek_api_key');
    if (!$key) wp_send_json_error('API-Key fehlt');

    $msg = sanitize_text_field($_POST['msg']);
    $site = get_option('dsb_site', []);
    if (empty($site)) {
        $count = deepseek_crawl();
        $site = get_option('dsb_site', []);
    }

    $ctx = '';
    foreach ($site as $p) $ctx .= "Titel: {$p['title']}\n{$p['content']}\n\n";

    $res = wp_remote_post('https://api.deepseek.com/chat/completions', [
        'headers' => ['Authorization' => "Bearer $key", 'Content-Type' => 'application/json'],
        'body' => json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => "Website-Kontext (vollständig):\n$ctx"],
                ['role' => 'user', 'content' => $msg]
            ],
            'temperature' => 0.7
        ]),
        'timeout' => 60
    ]);

    if (is_wp_error($res)) {
    wp_send_json_error('API-Fehler: ' . $res->get_error_message());
}
$body = wp_remote_retrieve_body($res);
$code = wp_remote_retrieve_response_code($res);
$json = json_decode($body, true);

if ($code !== 200) {
    wp_send_json_error("DeepSeek sagt (Code $code): " . ($json['error']['message'] ?? $body));
}
$answer = $json['choices'][0]['message']['content'] ?? 'Leere Antwort';
}

function deepseek_crawl() {
    $posts = get_posts(['numberposts' => -1, 'post_status' => 'publish', 'post_type' => ['post', 'page']]);
    $data = [];
    foreach ($posts as $p) {
        $data[] = [
            'title' => $p->post_title,
            'content' => wp_strip_all_tags($p->post_content)
        ];
    }
    update_option('dsb_site', $data);
    return count($data);
}
