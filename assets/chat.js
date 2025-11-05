jQuery(function ($) {
    const $b = $('#dsb-bubble');
    const $c = $('#dsb-chat');
    const $m = $('#dsb-messages');
    const $i = $('#dsb-text');
    const $s = $('#dsb-send');
    const $x = $('#dsb-close');
    let first = true;

    $b.on('click', () => {
        $c.toggleClass('closed');
        if (first) { setTimeout(welcome, 400); first = false; }
        setTimeout(() => $i.focus(), 500);
    });

    $x.on('click', () => $c.addClass('closed'));
    $s.on('click', send);
    $i.on('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send();
        }
    });

    function welcome() {
        $m.html('<div class="bot">' + dsb.welcome + '</div>');
        scroll();
    }

    function send() {
        let msg = $i.val().trim();
        if (!msg) return;
        $m.append('<div class="user">Du: ' + msg + '</div>');
        $i.val(''); scroll();

        $.post(dsb.ajax, {
            action: 'dsb_chat',
            msg: msg,
            nonce: dsb.nonce
        }, r => {
            // MAGIC: URLs werden klickbar + _blank!
            let text = r.success ? r.data : r.data;
            text = text.replace(
                /(https?:\/\/[^\s]+)/g,
                '<a href="$1" target="_blank" rel="noopener" style="color:#0073aa; text-decoration:underline;">$1</a>'
            );
            $m.append('<div class="bot">' + text + '</div>');
            scroll();
        });
    }

    function scroll() {
        $m.scrollTop($m[0].scrollHeight);
    }

    setInterval(() => $b.toggleClass('pulse'), 3000);
});
