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
    </table>
    <?php
}
