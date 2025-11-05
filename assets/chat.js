jQuery(function($){
    const $b = $('#deepseek-chatbot-bubble'),
          $c = $('#deepseek-chatbot-container'),
          $m = $('#chat-messages'),
          $i = $('#chat-input'),
          $s = $('#chat-send'),
          $x = $('#chat-close');

    $b.on('click',()=> $c.toggleClass('closed'));
    $x.on('click',()=> $c.addClass('closed'));
    $s.on('click',send); $i.on('keypress',e=>e.which===13&&send());

    function send(){
        let msg = $i.val().trim(); if(!msg) return;
        $m.append(`<div class="message user">Du: ${msg}</div>`); $i.val(''); scroll();
        $.post(deepseek_ajax.ajax_url, {
            action:'deepseek_chat', message:msg, nonce:deepseek_ajax.nonce
        }, r => {
            $m.append(`<div class="message assistant">Bot: ${r.success ? r.data : r.data}</div>`);
            scroll();
        });
    }
    function scroll(){ $m.scrollTop($m[0].scrollHeight); }
});
