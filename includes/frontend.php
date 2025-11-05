<?php
if (!defined('ABSPATH')) exit;
add_action('wp_ajax_dsb_chat', 'dsb_chat'); add_action('wp_ajax_nopriv_dsb_chat', 'dsb_chat');
function dsb_chat() {
    check_ajax_referer('dsb_nonce', 'nonce');
    $key = get_option('deepseek_api_key'); if (!$key) wp_die('Key fehlt');
    $msg = sanitize_text_field($_POST['msg']);
    $site = get_option('dsb_site', []); if (!$site) { deepseek_crawl(); $site = get_option('dsb_site', []); }
    $ctx = ''; foreach ($site as $p) $ctx .= "Titel: {$p['title']}\n{$p['content']}\n\n";
    $res = wp_remote_post('https://api.deepseek.com/chat/completions', [
        'headers' => ['Authorization'=>"Bearer $key", 'Content-Type'=>'application/json'],
        'body' => json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role'=>'system', 'content'=>"Website-Kontext:\n$ctx"],
                ['role'=>'user', 'content'=>$msg]
            ]
        ]),
        'timeout' => 60
    ]);
    $json = json_decode(wp_remote_retrieve_body($res), true);
    wp_send_json_success($json['choices'][0]['message']['content'] ?? 'Oops');
}
function deepseek_crawl() {
    $posts = get_posts(['numberposts'=>-1, 'post_status'=>'publish']);
    $data = [];
    foreach ($posts as $p) $data[] = ['title'=>$p->post_title, 'content'=>wp_strip_all_tags($p->post_content)];
    update_option('dsb_site', $data);
}
