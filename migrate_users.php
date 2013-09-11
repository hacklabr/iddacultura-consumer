<?php

/**
 * Actions do WP para avisar os usuários já existentes
 * de que o ID da Cultura foi habilitado e que eles podem
 * conectar a conta deles com este serviço.
 */
 
 // desabilita as actions do painel de administração na mão ao 
// invés de usar a constante OPENID_DISABLE_ADMIN_PANELS pois 
// duas funções do admin_panels.php são utilizadas 
remove_action('admin_init', 'openid_admin_register_settings');
remove_action('admin_menu', 'openid_admin_panels');
remove_action('personal_options_update', 'openid_personal_options_update');
remove_filter('pre_update_option_openid_cap', 'openid_set_cap');

// adiciona script que remove formulários da página de login
// para exibir apenas mensagens relacionadas com a integração com o ID da Cultura
add_action('login_head', function() {
    $user_id = get_current_user_id();
    
    if (($user_id && !get_user_openids($user_id) && !get_user_meta($user_id, '_iddacultura_optout', true))
        || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'iddacultura_feedback')) 
    {
        wp_enqueue_script('iddacultura-login-message', plugin_dir_url(__FILE__) . 'js/iddacultura-login-message.js', array('jquery'));
    }
});

// avisa um usuário existente do ID da Cultura e 
// pergunta se ele quer passar a usar este caminho para
// entrar no site ou se prefere continuar usando o login do WP
add_action('wp_login', function($user_login, $user) {
    if (!get_user_openids($user->ID) && !get_user_meta($user->ID, '_iddacultura_optout', true) && !isset($_REQUEST['openid_mode'])) {
        wp_redirect(wp_login_url());
        die;
    }
}, 10, 2);

// inicia o processo do openid para associar uma conta do wp
// a uma conta no ID da Cultura. implementado como um action no
// wp-login.php já que parte das instalações do WP do MinC
// desabilitam o acesso ao wp-admin 
add_action('login_form_iddacultura_connect', function() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $finish_url = wp_login_url() . '?action=iddacultura_feedback';

    openid_start_login(IDDACULTURA_PROVIDER, 'verify', $finish_url);
});

// deixa de avisar ao usuário que ele tem a opção de conectar sua
// conta com uma conta no ID da Cultura
add_action('login_form_iddacultura_optout', function() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    update_user_meta($user_id, '_iddacultura_optout', true);
    
    wp_redirect(site_url());
    die;
});

// exibe mensagens para o usuário referentes a integração da conta
// dele no WP com o ID da Cultura
add_filter('login_message', function($message) {
    $user_id = get_current_user_id();
    $site_url = site_url();

    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'iddacultura_feedback') {
        if (!get_user_openids($user_id)) {
            $message = '<div class="error"><p>Houve um erro e não foi possível conectar usa conta com o ID da Cultura. É possível que um outro usuário deste site já esteja associado a mesma conta do ID da Cultura.</p></div>';
        } else if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'success') {
            $message = '<p class="message">Conexão realizada com sucesso. A partir de agora você pode usar a sua conta no ID da Cultura para entrar neste site.</p>';
        }
        
        $message .= "<a href='{$site_url}'>Ir para a página inicial</a>";
    } else if ($user_id && !get_user_openids($user_id) && !get_user_meta($user_id, '_iddacultura_optout', true)) {
        $site_name = get_bloginfo('name');
        $login_url = wp_login_url();
        
        $message = "<div class='message'>";
        $message .= "<p>O site {$site_name} agora suporta o <b>ID da Cultura</b>, um servidor de autenticação centralizado, que permite utilizar um único usuário e senha para acessar diversos sites do Ministério da Cultura. Clique em um dos botões abaixo para começar a usar o ID da Cultura e conectar este serviço com a sua conta neste site.</p><br />";
        $message .= "<p>Se você ainda não tem uma conta no ID da Cultura, será necessário primeiro criar uma para então depois conectar ela com a sua conta neste site.</p><br />";
        $message .= "</div>";
        $message .= "<p><a href='" . IDDACULTURA_PROVIDER . "/accounts/register' target='_blank'>Criar uma conta no ID da Cultura</a></p>";
        $message .= "<p><a href='{$login_url}?action=iddacultura_connect'>Conectar usando uma conta do ID da Cultura já existente</a></p>";
        $message .= "<p><a href='{$login_url}?action=iddacultura_optout'>Não usar o ID da Cultura</a></p>";
        $message .= "<p><a href='{$site_url}'>Agora não</a></p>";
    }
    
    return $message;
});

