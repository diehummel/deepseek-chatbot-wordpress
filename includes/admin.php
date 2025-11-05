<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_options_page('DeepSeek Chatbot', 'DeepSeek Chatbot', 'manage_options', 'dsb', 'dsb_page');
});

function dsb_page() {
    $msg = '';
    if (isset($_POST['save'])) {
        update_option('deepseek_api_key', sanitize_text_field($_POST['key']));
        update_option('dsb_welcome', wp_kses_post($_POST['welcome']));
        $msg = '<div class="notice notice-success"><p>✔ Gespeichert!</p></div>';
    }
    if (isset($_POST['crawl'])) {
        $count = deepseek_crawl();
        $msg = '<div class="notice notice-success"><p>✔ ' . $count . ' Seiten NEU gecrawlt & gespeichert!</p></div>';
    }
    echo $msg;
    ?>
    <div class="wrap">
        <h1>DeepSeek Chatbot</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>API-Key</th>
                    <td><input name="key" type="password" value="<?= esc_attr(get_option('deepseek_api_key')) ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Willkommensnachricht</th>
                    <td><textarea name="welcome" rows="4" class="large-text"><?= esc_textarea(get_option('dsb_welcome', "Hallo! Ich bin dein KI-Assistent.\nFrag mich alles über diese Website! 😊")) ?></textarea></td>
                </tr>
            </table>
            <p><input type="submit" name="save" class="button button-primary" value="Speichern"></p>
        </form>
        <hr>
        <form method="post">
            <p><input type="submit" name="crawl" class="button button-secondary" value="🔄 Jetzt NEU crawlen"></p>
        </form>
    </div>
    <?php
}
