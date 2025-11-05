<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_options_page('DeepSeek Chatbot', 'DeepSeek Chatbot', 'manage_options', 'dsb', 'dsb_page');
});

function dsb_page() {
    if ($_POST['save']) {
        update_option('deepseek_api_key', sanitize_text_field($_POST['key']));
        update_option('dsb_welcome', wp_kses_post($_POST['welcome']));
        echo '<div class="notice notice-success"><p>Gespeichert!</p></div>';
    }
    if ($_POST['crawl']) deepseek_crawl();
    ?>
    <div class="wrap">
        <h1>DeepSeek Chatbot</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>API-Key</th>
                    <td><input name="key" value="<?=esc_attr(get_option('deepseek_api_key'))?>" class="regular-text" type="password"></td>
                </tr>
                <tr>
                    <th>Willkommensnachricht</th>
                    <td><textarea name="welcome" rows="4" class="large-text"><?=esc_textarea(get_option('dsb_welcome', "Hallo! Ich bin dein KI-Assistent.\nFrag mich alles über diese Website! 😊"))?></textarea></td>
                </tr>
            </table>
            <p><input type="submit" name="save" class="button button-primary" value="Speichern"></p>
        </form>
        <hr>
        <form method="post">
            <input type="hidden" name="crawl" value="1">
            <p><input type="submit" class="button" value="Website jetzt crawlen"></p>
        </form>
    </div>
    <?php
}
