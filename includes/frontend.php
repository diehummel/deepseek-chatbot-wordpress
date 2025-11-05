<?php
/**
 * FRONTEND – DeepSeek Chatbot v2.0
 * Findet JEDE deiner 113 Seiten + schlägt automatisch die beste URL vor!
 */

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_dsb_chat', 'dsb_chat');
add_action('wp_ajax_nopriv_dsb_chat', 'dsb_chat');

function dsb_chat() {
    check_ajax_referer('dsb', 'nonce');

    $key = trim(get_option('deepseek_api_key'));
    if (!$key) {
        wp_send_json_error('API-Key fehlt! Gehe zu Einstellungen → DeepSeek Chatbot');
        return;
    }

    $msg = sanitize_text_field($_POST['msg']);

    // Lade alle Seiten (wird beim Crawlen gespeichert)
    $site = get_option('dsb_site', []);
    if (empty($site)) {
        deepseek_crawl();
        $site = get_option('dsb_site', []);
    }

    // ——— SUPER-SUCHE: Findet JEDE Seite ———
    $words = preg_split('/\s+/', strtolower($msg));
    $scores = [];

    foreach ($site as $i => $p) {
        $text = strtolower($p['title'] . ' ' . $p['content']);
        $score = 0;
        foreach ($words as $w) {
            if (strlen($w) < 3) continue;
            $score += substr_count($text, $w) * 10;
            if (stripos($p['title'], $w) !== false) $score += 50;
        }
        $scores[$i] = $score;
    }
    arsort($scores);

    // TOP-8 relevante Seiten
    $top = array_slice($scores, 0, 8, true);

    $ctx = "Du bist der Website-Experte. Antworte kurz und genau.\n";
    $ctx .= "Verwende NUR diese Seiten:\n\n";

    $best_url = '';
    $best_score = 0;

    foreach ($top as $i => $score) {
        $page = $site[$i];
        $url = get_permalink($page['id']) ?: '#';
        $chunk = "Titel: {$page['title']}\n";
        $chunk .= substr($page['content'], 0, 1300) . "\n";
        $chunk .= "URL: $url\n\n";
        $ctx .= $chunk;

        if ($score > $best_score) {
            $best_score = $score;
            $best_url = $url;
        }
    }

    // System-Prompt: Immer beste URL vorschlagen!
    $ctx .= "\nWenn die Frage passt, gib am Ende diese URL als klickbaren Link:\n$best_url";

    // ——— DeepSeek API Call ———
    $res = wp_remote_post('https://api.deepseek.com/chat/completions', [
        'headers' => [
            'Authorization' => "Bearer $key",
            'Content-Type'  => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => $ctx],
                ['role' => 'user',   'content' => $msg]
            ],
            'temperature' => 0.6,
            'max_tokens'  => 800
        ]),
        'timeout' => 90
    ]);

    if (is_wp_error($res)) {
        wp_send_json_error('Internet-Fehler: ' . $res->get_error_message());
        return;
    }

    $code = wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);

    if ($code !== 200) {
        wp_send_json_error("DeepSeek (Code $code): $body");
        return;
    }

    $json = json_decode($body, true);
    $answer = $json['choices'][0]['message']['content'] ?? 'Keine Antwort';

    // URL im Text klickbar machen
    $answer = preg_replace(
        '/(https?:\/\/[^\s]+)/',
        '<a href="$1" target="_blank" rel="noopener" style="color:#0073aa; text-decoration:underline;">$1</a>',
        $answer
    );

    wp_send_json_success($answer);
}

// ——— CRAWLER: Speichert ALLE Seiten mit ID ———
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
