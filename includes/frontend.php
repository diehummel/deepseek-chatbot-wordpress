<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_deepseek_chat', 'deepseek_handle');
add_action('wp_ajax_nopriv_deepseek_chat', 'deepseek_handle');

function deepseek_handle() {
    check_ajax_referer('deepseek_chat_nonce', 'nonce');
    $key = get_option('deepseek_api_key');
    if (!$key) wp_send_json_error('Kein API-Key');

    $msg = sanitize_text_field($_POST['message']);
    $site = get_option('deepseek_site_data', []);
    if (empty($site)) { deepseek_chatbot_crawl(); $site = get_option('deepseek_site_data', []); }

    // RAG
    $ctx = ''; $words = preg_split('/\s+/', strtolower($msg));
    foreach ($site as $page) {
        $score = 0;
        foreach ($words as $w) if (strlen($w)>3) $score += substr_count(strtolower($page['title'].' '.$page['content']), $w);
        if ($score) $ctx .= "Titel: {$page['title']}\n{$page['content']}\n\n";
    }
    $system = "Du bist der Website-Experte. Nutze diesen Kontext:\n$ctx";

    $res = wp_remote_post('https://api.deepseek.com/chat/completions', [
        'headers' => ['Authorization' => "Bearer $key", 'Content-Type' => 'application/json'],
        'body'    => json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role'=>'system', 'content'=>substr($system,0,30000)],
                ['role'=>'user', 'content'=>$msg]
            ],
            'temperature' => 0.6
        ]),
        'timeout' => 60
    ]);

    if (is_wp_error($res)) wp_send_json_error($res->get_error_message());
    $json = json_decode(wp_remote_retrieve_body($res), true);
    $answer = $json['choices'][0]['message']['content'] ?? 'Error';
    wp_send_json_success($answer);
}
