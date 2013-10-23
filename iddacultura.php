<?php

/*
 * Plugin Name: ID da Cultura
 * Description: Transforma uma instalação do WordPress em um cliente do ID da Cultura
 * Author: Hacklab
 * Author URI: http://hacklab.com.br
 * Version: 1.0
 * License: GPL (http://www.fsf.org/licensing/licenses/info/GPLv2.html)
 */

// constantes para desabilitar partes do plugin openid que não são usadas
define('OPENID_DISABLE_COMMENTS', true);
define('OPENID_DISABLE_SERVER', true);

// inclui manualmente os plugins xrds-simple e openid
// que são distribuídos junto com este
require_once('lib/xrds-simple/xrds-simple.php');
require_once('lib/openid/openid.php');

// remove os actions e filters dos plugins openid e xrds-simple
// que não são utilizados pelo ID da Cultura.
add_action('init', function() {
    // remove o menu do plugin xrds-simple do admin
    remove_action('admin_menu', 'xrds_admin_menu');

    // remove os metas do xrds-simple do head
    remove_action('wp_head', 'xrds_meta');

    // remove mecanismo de erro do plugin openid (ver iddacultura_login_errors())
    remove_action('init', 'openid_login_errors');
});

require_once('custom_profile.php');
require_once('login.php');
require_once('migrate_users.php');

add_action('admin_notices', function() {
    if (!defined('IDDACULTURA_PROVIDER') && current_user_can('manage_options') && is_admin()) {
        echo '<div class="error"><p>É necessário adicionar a constante IDDACULTURA_PROVIDER ao arquivo wp-config.php com a URL para o provider openid para que o botão do ID da Cultura funcione na página de login.</p></div>';
    }
});

if (!defined('IDDACULTURA_PROVIDER')) {
    define('IDDACULTURA_PROVIDER', 'http://id.culturadigital.br');
}
add_action( 'login_head', 'css_dos_botoes');

function css_dos_botoes() {
    echo '
    <style  type="text/css">
        .btn-auto {
            width: 100%;
            text-align: center;
            margin-bottom: 6px;
        }
        .login form {
            padding-bottom: 24px;
        }
        .accept-margin {
            margin-left: 8px;
        }
    </style>';
}
