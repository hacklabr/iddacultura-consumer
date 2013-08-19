<?php

/**
 * Customiza a página do perfil do usuário para
 * exibir os campos extra (CPF e informações de
 * ocupação).
 */

add_action('admin_print_scripts-profile.php', function() {
    wp_register_script('iddacultura-profile', plugin_dir_url(__FILE__) . 'js/iddacultura-profile.js', array('jquery'));
    wp_print_scripts(array('iddacultura-profile'));
});

add_action('admin_notices', function() {
    global $pagenow;
    
    if ($pagenow == 'profile.php') {
        echo '<div class="updated"><p>Os campos desabilitados devem ser alterados no seu perfil do ID da Cultura.</p></div>';
    }
});

add_action('show_user_profile', 'iddacultura_show_profile_fields');
add_action('edit_user_profile', 'iddacultura_show_profile_fields');
/**
 * Exibe campos adicionais no perfil do usuário.
 * 
 * @param WP_User $user
 * @return null
 */
function iddacultura_show_profile_fields($user) {
    ?>
    <table class="form-table">
        <tr>
            <th><label for="cpf">CPF</label></th>
            <td>
                <input type="text" name="cpf" id="cpf" value="<?php echo esc_attr(get_the_author_meta('cpf', $user->ID)); ?>" class="regular-text" disabled /><br />
            </td>
        </tr>
        <tr>
            <th><label for="occupation_primary">Grupo</label></th>
            <td>
                <input type="text" name="occupation_primary" id="occupation_primary" value="<?php echo iddacultura_get_occupation_name(esc_attr(get_the_author_meta('occupation_primary', $user->ID))); ?>" class="regular-text" disabled /><br />
            </td>
        </tr>
        <tr>
            <th><label for="occupation_secondary">Sub-grupo principal</label></th>
            <td>
                <input type="text" name="occupation_secondary" id="occupation_secondary" value="<?php echo iddacultura_get_occupation_name(esc_attr(get_the_author_meta('occupation_secondary', $user->ID))); ?>" class="regular-text" disabled /><br />
            </td>
        </tr>
        <tr>
            <th><label for="occupation_tertiary">Sub-grupo</label></th>
            <td>
                <input type="text" name="occupation_tertiary" id="occupation_tertiary" value="<?php echo iddacultura_get_occupation_name(esc_attr(get_the_author_meta('occupation_tertiary', $user->ID))); ?>" class="regular-text" disabled /><br />
            </td>
        </tr>
        <tr>
            <th><label for="occupation_quartenary">Família</label></th>
            <td>
                <input type="text" name="occupation_quartenary" id="occupation_quartenary" value="<?php echo iddacultura_get_occupation_name(esc_attr(get_the_author_meta('occupation_quartenary', $user->ID))); ?>" class="regular-text" disabled /><br />
            </td>
        </tr>
        <tr>
            <th><label for="occupation_quinary">Ocupação</label></th>
            <td>
                <input type="text" name="occupation_quinary" id="occupation_quinary" value="<?php echo iddacultura_get_occupation_name(esc_attr(get_the_author_meta('occupation_quinary', $user->ID))); ?>" class="regular-text" disabled /><br />
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Retorna o nome de uma ocupação a partir do 
 * seu código.
 *
 * @param int $code
 * @return str
 */
function iddacultura_get_occupation_name($code) {
    $occupations = json_decode(file_get_contents('user_occupation.json', true)); 

    if (property_exists($occupations, $code)) {
        return $occupations->$code->name;
    }
}
