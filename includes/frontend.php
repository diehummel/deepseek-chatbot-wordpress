<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_dsb_chat', 'dsb_chat');
add_action('wp_ajax_nopriv_dsb_chat', 'dsb_chat');

function dsb_chat() {
    check_ajax_referer('dsb', 'nonce');
    $key = trim(get_option('deepseek_api_key'));
    if (!$key) { wp_send_json_error('API-Key fehlt!'); }

    $msg = sanitize_text_field($_POST['msg']);
    $site = get_option('dsb_site', []);
    if (empty($site)) { deepseek_crawl(); $site = get_option('dsb_site', []); }

    // NEU: Nur die 5 relevantesten Seiten (max 12.000 Zeichen)
    $words = preg_split('/\s+/', strtolower($msg));
    $scores = [];
    foreach ($site as $i => $p) {
        $text = strtolower($p['title'] . ' ' . substr($p['content'],0,800));
        $score = 0;
        foreach ($words as $w) if (strlen($w)>2) $score += substr_count($text, $w);
        $scores[$i] = $score;
    }
    arsort($scores);
    $top = array_slice($scores, 0, 5, true);

    $ctx = "Du kennst genau diese Seiten:\n";
    $used = 0;
    foreach ($top as $i => $score) {
        if ($used > 11000) break;
        $page = $site[$i];
        $chunk = "Seite: {$page['title']}\n{$page['content']}\n\n";
        if ($used + strlen($chunk) > 12000) {
            $chunk = substr($chunk, 0, 12000 - $used);
        }
        $ctx .= $chunk;
        $used += strlen($chunk);
    }

    $res = wp_remote_post('https://api.deepseek.com/chat/completions', [
        'headers' => ['Authorization' => "Bearer $key", 'Content-Type' => 'application/json'],
        'body' => json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => $ctx],
                ['role' => 'user',   'content' => $msg]
            ],
            'temperature' => 0.6,
            'max_tokens' => 800
        ]),
        'timeout' => 90
    ]);

    if (is_wp_error($res)) { wp_send_json_error('Internet: ' . $res->get_error_message()); }
    $code = wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);
    if ($code !== 200) { wp_send_json_error("DeepSeek (Code $code): $body"); }

    $json = json_decode($body, true);
    $answer = $json['choices'][0]['message']['content'] ?? 'Oops';
    wp_send_json_success($answer);
}

function deepseek_crawl() {
    $posts = get_posts(['numberposts' => -1, 'post_status' => 'publish']);
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
?>
