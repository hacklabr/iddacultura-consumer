(function($) {
    $(document).ready(function() {
        // limpa o campo do openid caso o usuário opte pelo login normal do wp
        $('#wp-submit').click(function() {
            $('#openid_identifier').val('');
        });
        
        // limpa o campo do openid caso o usuário opte pelo login normal do wp
        $(document).keypress(function(e) {
            if (e.which == 13) {
                $('#openid_identifier').val('');
                
                // força o clique no botão de login, sem isso o enter usa o botão do id da cultura que vem antes
                $('#wp-submit').trigger('click');
            }
        });
        
        // limpa os campos de login caso o usuário opte por usar o id da cultura
        $('#submit-iddacultura').click(function() {
            $('#user_login').val('');
            $('#user_pass').val('');
        });
        
        // move os campos do id da cultura para depois do botão de login
        jQuery('#wp-submit').after(jQuery('#iddacultura-login'));
    });
})(jQuery);