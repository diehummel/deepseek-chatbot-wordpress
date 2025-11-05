jQuery(() => {
    const $b = $('#dsb-bubble'), $c = $('#dsb-chat'), $m = $('#dsb-messages'),
          $i = $('#dsb-text'), $s = $('#dsb-send'), $x = $('#dsb-close');
    let first = true;
    $b.on('click', () => { $c.toggleClass('closed'); if (first) { welcome(); first=false; } });
    $x.on('click', () => $c.addClass('closed'));
    $s.on('click', send); $i.on('keydown', e => e.key==='Enter' && send());

    function welcome() {
        $m.append(`<div class="bot">🤖 ${dsb.welcome.replace(/\n/g,'<br>')}</div>`);
        scroll();
    }
    function send() {
        let msg = $i.val().trim(); if (!msg) return;
        $m.append(`<div class="user">Du: ${msg}</div>`); $i.val(''); scroll();
        $.post(dsb.ajax, {action:'dsb_chat', msg:msg, nonce:dsb.nonce}, r => {
            $m.append(`<div class="bot">Bot: ${r.success ? r.data : 'Fehler'}</div>`); scroll();
        });
    }
    function scroll() { $m.scrollTop($m[0].scrollHeight); }
    setTimeout(() => $b.addClass('pulse'), 2000); // kleine Animation
});
