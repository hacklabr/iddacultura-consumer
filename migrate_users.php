<?php
/*
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
        wp_redirect(site_url() . '/iddacultura');
        die;
    }
}, 10, 2);

add_action('generate_rewrite_rules', function($wp_rewrite) {
    $new_rules = array(
        "iddacultura/?$" => "index.php?&tpl=iddacultura",
    );
    $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
});

add_filter('query_vars', function($public_query_vars) {
    $public_query_vars[] = "tpl";
    return $public_query_vars;
});

add_action('template_redirect', function() {
    global $wp_query, $wpdb;
    $user_id = get_current_user_id();

    $tpl = $wp_query->get('tpl');
    
    if ($tpl && $tpl == 'iddacultura' && is_user_logged_in()) {
        iddacultura_migrate_users();
        die;
    }
});

/**
 * Página que imita o estilo da página de login
 * e é exibida para o usuário após o login para ele
 * escolher se quer ou não passar a usar o ID da Cultura.
 * 
 * @return null
 */
function iddacultura_migrate_users() {
    $user_id = get_current_user_id();
    $site_url = site_url();
    $message = '';
    
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'feedback') {
        if (!get_user_openids($user_id)) {
            $message = '<div class="error"><p>Houve um erro e não foi possível conectar usa conta com o ID da Cultura. É possível que um outro usuário deste site já esteja associado a mesma conta do ID da Cultura.</p></div>';
        } else if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'success') {
            $message = '<p class="message">Conexão realizada com sucesso. A partir de agora você pode usar a sua conta no ID da Cultura para entrar neste site.</p>';
        }
        
        $message .= "<a href='{$site_url}'>Ir para a página inicial</a>";
    }
    
    $site_name = get_bloginfo('name');
    $login_url = wp_login_url();
    
    ?>
    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
        <head>
            <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
            <title><?php bloginfo('name'); ?> &rsaquo; <?php echo $title; ?></title>
            <?php

            wp_admin_css( 'wp-admin', true );
            wp_admin_css( 'colors-fresh', true );
            do_action( 'login_enqueue_scripts' );
            do_action( 'login_head' );
            ?>
        </head>
        <body class="login wp-core-ui">
            <div id="login">
                <h1><a href="<?php echo esc_url( $login_header_url ); ?>" title="<?php echo esc_attr( $login_header_title ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
                
                <?php if (!empty($message)) : ?>
                    <?php echo $message; ?>
                <?php elseif ($user_id && !get_user_openids($user_id) && !get_user_meta($user_id, '_iddacultura_optout', true)) : ?>
                    <div class='message'>
                        <p>O site <?php echo $site_name; ?> agora suporta o <b>ID da Cultura</b>, um servidor de autenticação centralizado, que permite utilizar um único usuário e senha para acessar diversos sites do Ministério da Cultura. Clique em um dos botões abaixo para começar a usar o ID da Cultura e conectar este serviço com a sua conta neste site.</p>
                    </div>
                    <p class='accept-margin'><a href='<?php echo $login_url; ?>?action=iddacultura_connect' class='btn-auto button-primary'>Usar o ID da Cultura</a></p>
                    <br><br>
                    <p class='accept-margin'><a href='<?php echo $login_url; ?>?action=iddacultura_optout' class='btn-auto button-primary'>Não usar o ID da Cultura</a></p>
                    <br><br>
                    <p class='btn-auto'><a href='<?php echo $site_url; ?>'>Agora não</a></p>
                    <p class='btn-auto'><a href='<?php echo IDDACULTURA_PROVIDER; ?>'>Saiba mais sobre o ID da Cultura</a></p>
                <?php endif; ?>
            </div>

            <div class="clear"></div>
        </body>
    </html>
    <?php
}

// inicia o processo do openid para associar uma conta do wp
// a uma conta no ID da Cultura. implementado como um action no
// wp-login.php já que parte das instalações do WP do MinC
// desabilitam o acesso ao wp-admin 
add_action('login_form_iddacultura_connect', function() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $finish_url = site_url() . '/iddacultura?action=feedback';

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
