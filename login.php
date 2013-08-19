<?php 

/**
 * Funções para customizar a página de login do
 * Wordpress para adicionar o ID da Cultura e também
 * funções para lidar com os dados do usuário enviados
 * pelo OpenID provider.
 */

add_action('login_head', function() {
    wp_register_script('iddacultura-login', plugin_dir_url(__FILE__) . 'js/iddacultura-login.js', array('jquery'));
    wp_print_scripts(array('iddacultura-login'));
});

add_action('init', function() {
    // remove o formulário openid de login do plugin openid
    remove_action('login_form', 'openid_wp_login_form');
});

add_action('login_form', 'iddacultura_wp_login_form');
/**
 * Add OpenID input field to wp-login.php
 *
 * @action: login_form
 **/
function iddacultura_wp_login_form() {
    ?>
    <?php if (defined('IDDACULTURA_PROVIDER')) : ?>
        <div id="iddacultura-login">
            <br /><br /><br /><br />
            <input type="hidden" name="openid_identifier" id="openid_identifier" class="input openid_identifier" value="<?php echo IDDACULTURA_PROVIDER; ?>" />
            <p><input id="submit-iddacultura" class="button-primary" type="submit" tabindex="100" value="Entrar usando o ID da Cultura" name="submit-iddacultura"></p>
            <br /><br />
            <p style="text-align: right;"><a href="<?php echo IDDACULTURA_PROVIDER; ?>/accounts/register">Criar um ID da Cultura</a></p>
            <p style="text-align: right;"><a href="<?php echo IDDACULTURA_PROVIDER; ?>">Saiba mais sobre o ID da Cultura</a></p>
        </div>
    <?php endif;
}

add_filter('authenticate', function() {
    // interrompe o processo de autenticação se o usuário tentar usar um OpenID provider que não o ID da Cultura
    // não exibe nenhuma mensagem apenas recarrega a página (de qualquer maneira não esperamos que algum usuário faça isso)
    if (isset($_POST['openid_identifier']) && $_POST['openid_identifier'] != IDDACULTURA_PROVIDER) {
        remove_filter('authenticate', 'openid_authenticate');
    }
}, 1);

add_action('init', 'iddacultura_login_errors');
/**
 * Substitui o mecanismo para lidar com erros do
 * openid para evitar problemas com caracteres com
 * acentos na chamada da htmlentities2()
 * (ver lib/openid/login.php na linha 119)
 */
function iddacultura_login_errors() {
    global $pagenow;

    if ($pagenow != 'wp-login.php') {
        return;
    }

    if (array_key_exists('openid_error', $_REQUEST)) {
        global $error;
        $error = filter_input(INPUT_GET, 'openid_error', FILTER_SANITIZE_STRING);
    }
}

// não solicita não dado do usuário usando a extensão sreg
add_filter('openid_consumer_sreg_optional_fields', function($fields) {
    return array();
});

// não solicita não dado do usuário usando a extensão sreg
add_filter('openid_consumer_sreg_required_fields', function($fields) {
    return array();
});

// solicita os dados do usuário usando a extensão ax ignorando os campos solicitados pelo plugin openid
// para uma lista de campos padrão veja http://openid.net/specs/openid-attribute-properties-list-1_0-01.html
add_filter('openid_consumer_ax_fields', function() {
    require_once('Auth/OpenID/AX.php');

    $fields = array(
        Auth_OpenID_AX_AttrInfo::make('http://openid.net/schema/namePerson/first', 1, true),
        Auth_OpenID_AX_AttrInfo::make('http://openid.net/schema/namePerson/last', 1, true),
        Auth_OpenID_AX_AttrInfo::make('http://openid.net/schema/namePerson/friendly', 1, true),
        Auth_OpenID_AX_AttrInfo::make('http://openid.net/schema/contact/internet/email', 1, true),
        Auth_OpenID_AX_AttrInfo::make('http://id.culturadigital.br/schema/cpf', 1, true),
        Auth_OpenID_AX_AttrInfo::make('http://id.culturadigital.br/schema/occupation_primary', 1, true),
        Auth_OpenID_AX_AttrInfo::make('http://id.culturadigital.br/schema/occupation_secondary', 1, true),
        Auth_OpenID_AX_AttrInfo::make('http://id.culturadigital.br/schema/occupation_tertiary', 1, true),
        Auth_OpenID_AX_AttrInfo::make('http://id.culturadigital.br/schema/occupation_quartenary', 1, true),
        Auth_OpenID_AX_AttrInfo::make('http://id.culturadigital.br/schema/occupation_quinary', 1, true),
    );

    return $fields;
});

// adiciona ao array com dados customizados do usuário as informações passadas pela extensão ax
add_filter('openid_user_data', function($data, $identity_url) {
    require_once('Auth/OpenID/AX.php');

    // ignora os dados do usuário que foram adicionados pelo plugin openid
    $data = array();

    $response = openid_response();
    $ax = Auth_OpenID_AX_FetchResponse::fromSuccessResponse($response);

    if (!$ax) {
        return $data;
    }

    $first_name = $ax->getSingle('http://openid.net/schema/namePerson/first');
    if ($first_name && !is_a($first_name, 'Auth_OpenID_AX_Error')) {
        $data['first_name'] = $first_name;
    }

    $last_name = $ax->getSingle('http://openid.net/schema/namePerson/last');
    if ($last_name && !is_a($last_name, 'Auth_OpenID_AX_Error')) {
        $data['last_name'] = $last_name;
    }

    $nickname = $ax->getSingle('http://openid.net/schema/namePerson/friendly');
    if ($nickname && !is_a($nickname, 'Auth_OpenID_AX_Error')) {
        $data['nickname'] = $nickname;
    }

    if ($first_name && $last_name) {
        $data['display_name'] = "$first_name $last_name";
    } else {
        $data['display_name'] = $nickname;
    }

    $email = $ax->getSingle('http://openid.net/schema/contact/internet/email');
    if ($email && !is_a($email, 'Auth_OpenID_AX_Error')) {
        $data['user_email'] = $email;
    }

    $cpf = $ax->getSingle('http://id.culturadigital.br/schema/cpf');
    if ($cpf && !is_a($cpf, 'Auth_OpenID_AX_Error')) {
        $data['cpf'] = $cpf;
    }

    $occupation_primary = $ax->getSingle('http://id.culturadigital.br/schema/occupation_primary');
    if ($occupation_primary	&& !is_a($occupation_primary, 'Auth_OpenID_AX_Error')) {
        $data['occupation_primary'] = $occupation_primary;
    }

    $occupation_secondary = $ax->getSingle('http://id.culturadigital.br/schema/occupation_secondary');
    if ($occupation_secondary && !is_a($occupation_secondary, 'Auth_OpenID_AX_Error')) {
        $data['occupation_secondary'] = $occupation_secondary;
    }

    $occupation_tertiary = $ax->getSingle('http://id.culturadigital.br/schema/occupation_tertiary');
    if ($occupation_tertiary && !is_a($occupation_tertiary, 'Auth_OpenID_AX_Error')) {
        $data['occupation_tertiary'] = $occupation_tertiary;
    }

    $occupation_quartenary = $ax->getSingle('http://id.culturadigital.br/schema/occupation_quartenary');
    if ($occupation_quartenary && !is_a($occupation_quartenary, 'Auth_OpenID_AX_Error')) {
        $data['occupation_quartenary'] = $occupation_quartenary;
    }

    $occupation_quinary = $ax->getSingle('http://id.culturadigital.br/schema/occupation_quinary');
    if ($occupation_quinary && !is_a($occupation_quinary, 'Auth_OpenID_AX_Error')) {
        $data['occupation_quinary'] = $occupation_quinary;
    }

    return $data;
}, 20, 2);

add_action('openid_consumer_new_user_custom_data', 'iddacultura_user_data', 10, 2);
add_action('openid_consumer_update_user_custom_data', 'iddacultura_user_data', 10, 2);

/**
 * Atualiza informações do usuário sempre que um usuário é
 * criado ou entre novamente no site.
 *
 * @param int $user_id
 * @param array $data
*/
function iddacultura_user_data($user_id, $data) {
    $userdata = array('ID' => $user_id);

    if (isset($data['first_name'])) {
        $userdata['first_name'] = $data['first_name'];
    }

    if (isset($data['last_name'])) {
        $userdata['last_name'] = $data['last_name'];
    }

    if (isset($data['first_name']) && isset($data['last_name'])) {
        $userdata['display_name'] = $data['first_name'] . ' ' . $data['last_name'];
    }

    wp_update_user($userdata);

    if (isset($data['cpf'])) {
        update_user_meta($user_id, 'cpf', $data['cpf']);
    }

    if (isset($data['occupation_primary'])) {
        update_user_meta($user_id, 'occupation_primary', $data['occupation_primary']);
    }

    if (isset($data['occupation_secondary'])) {
        update_user_meta($user_id, 'occupation_secondary', $data['occupation_secondary']);
    }

    if (isset($data['occupation_tertiary'])) {
        update_user_meta($user_id, 'occupation_tertiary', $data['occupation_tertiary']);
    }

    if (isset($data['occupation_quartenary'])) {
        update_user_meta($user_id, 'occupation_quartenary', $data['occupation_quartenary']);
    }

    if (isset($data['occupation_quinary'])) {
        update_user_meta($user_id, 'occupation_quinary', $data['occupation_quinary']);
    }
}