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

    // BESTE LOKALE SEITE FINDEN
    $words = preg_split('/\s+/', strtolower($msg));
    $best_url = $best_title = '';
    $best_score = 0;

    foreach ($site as $p) {
        $text = strtolower($p['title'] . ' ' . $p['content']);
        $score = 0;
        foreach ($words as $w) {
            if (strlen($w) < 3) continue;
            $score += substr_count($text, $w) * 10;
            if (stripos($p['title'], $w) !== false) $score += 100;
        }
        if ($score > $best_score) {
            $best_score = $score;
            $best_url   = get_permalink($p['id']) ?: '#';
            $best_title = $p['title'];
        }
    }

     // 3. SMART-PROMPT: Nur echte Treffer verlinken!
    $system = "Du bist ein schlanker Website-Assistent.\n";
    if ($best_score > 30) {   // ← ECHT relevant!
        $system .= "Lokaler Artikel: \"$best_title\"\n$best_url\n\n";
    }
    $system .= "Antworte kurz, ehrlich und nur mit Fakten.\n";
    $system .= "Keine Datenschutz-Floskeln, Wenn nur ein externer Link.\n";
    $system .= "Frage: $msg";

    // DEEPSEEK CALL
    $res = wp_remote_post('https://api.deepseek.com/chat/completions', array(
        'headers' => array('Authorization' => "Bearer $key", 'Content-Type' => 'application/json'),
        'body'    => json_encode(array(
            'model' => 'deepseek-chat',
            'messages' => array(
                array('role' => 'system', 'content' => $system),
                array('role' => 'user',   'content' => $msg)
            ),
            'temperature' => 0.7,
            'max_tokens'  => 900
        )),
        'timeout' => 90
    ));

    if (is_wp_error($res)) { wp_send_json_error('Internet: ' . $res->get_error_message()); }
    $code = wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);
    if ($code !== 200) { wp_send_json_error("DeepSeek (Code $code): $body"); }

    $json = json_decode($body, true);
    $answer = $json['choices'][0]['message']['content'] ?? 'Keine Antwort';

    // LINKS KLICKBAR
    $answer = preg_replace(
        '/(https?:\/\/[^\s\)]+)/',
        '<a href="$1" target="_blank" rel="noopener" style="color:#0073aa; text-decoration:underline;">$1</a>',
        $answer
    );

    wp_send_json_success($answer);
}

function deepseek_crawl() {
    $posts = get_posts( array(
        'numberposts' => -1,
        'post_status' => array('publish', 'private'),
        'post_type'   => 'any'
    ) );

    $data = array();
    foreach ($posts as $p) {
        $data[] = array(
            'id'      => $p->ID,
            'title'   => $p->post_title,
            'content' => wp_strip_all_tags($p->post_content)
        );
    }
    update_option('dsb_site', $data);
    return count($data);
}
?>
