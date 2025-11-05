jQuery(function ($) {
    const $b = $('#dsb-bubble'), $c = $('#dsb-chat'), $m = $('#dsb-messages'),
          $i = $('#dsb-text'), $s = $('#dsb-send'), $x = $('#dsb-close');
    let first = true;

    $b.on('click', () => {
        $c.toggleClass('closed');
        if (first) { setTimeout(welcome, 300); first = false; }
    });
    $x.on('click', () => $c.addClass('closed'));
    $s.on('click', send);
    $i.on('keydown', e => e.key === 'Enter' && !e.shiftKey && (e.preventDefault(), send()));

    function welcome() {
        $m.html('<div class="bot">' + dsb.welcome + '</div>');
        scroll();
    }
    function send() {
        let msg = $i.val().trim();
        if (!msg) return;
        $m.append('<div class="user">Du: ' + msg + '</div>');
        $i.val(''); scroll();
        $.post(dsb.ajax, { action: 'dsb_chat', msg: msg, nonce: dsb.nonce }, r => {
            $m.append('<div class="bot">Bot: ' + (r.success ? r.data : 'Fehler') + '</div>');
            scroll();
        });
    }
    function scroll() { $m.scrollTop($m[0].scrollHeight); }

    // Puls
    setInterval(() => $b.toggleClass('pulse'), 3000);
});
