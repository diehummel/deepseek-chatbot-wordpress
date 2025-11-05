<?php
/**
 * HYBRID-CHATBOT v3.0
 * 1. Crawlt ALLE deine Seiten
 * 2. DeepSeek darf Internet-Wissen nutzen
 * 3. Gibt IMMER deine lokale URL + Internet-Tipps
 */

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_dsb_chat', 'dsb_chat');
add_action('wp_ajax_nopriv_dsb_chat', 'dsb_chat');

function dsb_chat() {
    check_ajax_referer('dsb', 'nonce');
    $key = trim(get_option('deepseek_api_key'));
    if (!$key) { wp_send_json_error('API-Key fehlt!'); }

    $msg = sanitize_text_field($_POST['msg']);

    // 1. ALLE deine Seiten laden
    $site = get_option('dsb_site', []);
    if (empty($site)) { deepseek_crawl(); $site = get_option('dsb_site', []); }

    // 2. Beste lokale Seite finden
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

    // 3. SYSTEM-PROMPT: Lokale Seite zuerst, dann Internet!
    $system = "Du bist ein freundlicher Website-Assistent.\n";
    $system .= "1. Wenn du etwas auf dieser Website findest, nenne zuerst den lokalen Artikel:\n";
    $system .= "   \"$best_title\" → $best_url\n";
    $system .= "2. Ergänze dann mit aktuellem Internet-Wissen (z.B. Tools, Tutorials).\n";
    $system .= "3. Gib ALLE Links klickbar aus.\n\n";
    $system .= "Frage: $msg";

    // 4. DeepSeek API (volles Internet-Wissen erlaubt!)
    $res = wp_remote_post('https://api.deepseek.com/chat/completions', [
        'headers' => ['Authorization' => "Bearer $key", 'Content-Type' => 'application/json'],
        'body' => json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $msg]
            ],
            'temperature' => 0.7,
            'max_tokens'  => 900
        ]),
        'timeout' => 90
    ]);

    if (is_wp_error($res)) { wp_send_json_error('Internet: ' . $res->get_error_message()); }
    $code = wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);
    if ($code !== 200) { wp_send_json_error("DeepSeek (Code $code): $body"); }

    $json = json_decode($body, true);
    $answer = $json['choices'][0]['message']['content'] ?? 'Keine Antwort';

    // 5. URLs klickbar machen
    $answer = preg_replace(
        '/(https?:\/\/[^\s\)]+)/',
        '<a href="$1" target="_blank" rel="noopener" style="color:#0073aa; text-decoration:underline;">$1</a>',
        $answer
    );

    wp_send_json_success($answer);
}

// Crawler – speichert ID für echte URLs
function deepseek_crawl() {
    $posts = get_posts([
        'numberposts' => -1,
        'post_status' => ['publish', 'private'],
        'post_type'   => 'any',
    ]);
    $data = [];
    foreach ($posts as $p) {
        $data[] = [
            'id'      => $p->ID,
            'title'   => $p->post_title,
            'content' => wp_strip_all_tags($p->post_content)
        ];
    }
    update_option('dsb_site', $data);
    return count($data);
}
?>
