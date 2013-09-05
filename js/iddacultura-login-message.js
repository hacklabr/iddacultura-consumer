jQuery(document).ready(function() {
    // após o login de um usuário usando a autenticação do WP 
    // remove os elementos HTML da página de login para exibir apenas
    // a mensagem para o usuário logado para ele escolher se quer ou 
    // não usar o ID da Cultura
    
    jQuery('#loginform').hide();
    jQuery('#nav').hide();
    jQuery('#backtoblog').hide();
});
