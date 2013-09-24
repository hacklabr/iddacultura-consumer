<?php

/*
 * Plugin Name: ID da Cultura
 * Description: Extende o plugin OpenID para adicionar funcionalidade especifica do ID da Cultura.
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

if (!defined('IDDACULTURA_PROVIDER')) {
    define('IDDACULTURA_PROVIDER', 'http://id.culturadigital.br');
}