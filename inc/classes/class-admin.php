<?php

namespace MeuMouse\Flexify_Dashboard\Recaptcha;

use MeuMouse\Flexify_Dashboard\Init;
use MeuMouse\Flexify_Dashboard\Recaptcha\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Add admin settings panel to Flexify Dashboard
 * 
 * @since 1.0.0
 * @package MeuMouse.com
 */
class Admin {

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'flexify_dashboard_recaptcha_addon', array( $this, 'add_recaptcha_admin_settings' ) );
    }


    /**
     * Add reCAPTCHA modal settings on Flexify Dashboard extensions section
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_recaptcha_admin_settings() {
        ?>
        <div class="modal fade" id="recaptcha_modal" tabindex="-1" aria-labelledby="recaptchaLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="recaptchaLabel"><?php echo esc_html__( 'Configurar integração com Google reCAPTCHA', 'flexify-dashboard-recaptcha-addon' ) ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo esc_html__( 'Fechar', 'flexify-dashboard-recaptcha-addon' ) ?>"></button>
                    </div>

                    <div class="modal-body">
                        <table class="popup-table">
                            <tr>
                                <th>
                                    <?php echo esc_html__( 'Ativar reCAPTCHA no login de administrador', 'flexify-dashboard-recaptcha-addon' ) ?>
                                    <span class="flexify-dashboard-description"><?php echo esc_html__( 'Ative essa opção para que a proteção reCAPTCHA possa ser inicializado.', 'flexify-dashboard-recaptcha-addon' ) ?></span>
                                </th>
                                <td>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="toggle-switch" id="enable_recaptcha_admin_login" name="enable_recaptcha_admin_login" value="yes" <?php checked( Init::get_setting('enable_recaptcha_admin_login') === 'yes' ); ?> />
                                    </div>
                                </td>
                            <tr>
                        
                            <tr>
                                <th>
                                    <?php echo esc_html__( 'Consultar credenciais', 'flexify-dashboard-recaptcha-addon' ) ?>
                                    <span class="flexify-dashboard-description"><?php echo esc_html__('Acesse o link ao lado para consultar suas chaves de integração para o reCAPTCHA.', 'flexify-dashboard-recaptcha-addon' ) ?></span>
                                </th>
                                <td>
                                    <a class="fancy-link" href="https://www.google.com/recaptcha/admin/" target="_blank"><?php echo esc_html__( 'Google reCAPTCHA', 'flexify-dashboard-recaptcha-addon' ) ?></a>
                                </td>
                            <tr>

                            <tr>
                                <th>
                                    <?php echo esc_html__( 'Chave do site', 'flexify-dashboard-recaptcha-addon' ) ?>
                                    <span class="flexify-dashboard-description"><?php echo esc_html__('Informe aqui a chave do site relacionado a API do Google reCAPTCHA (Caixa de verificação v2).', 'flexify-dashboard-recaptcha-addon' ) ?></span>
                                </th>
                                <td>
                                    <div class="form-check form-switch">
                                        <input type="text" class="form-control input-control-wd-20" id="recaptcha_site_key" name="recaptcha_site_key" value="<?php echo Init::get_setting('recaptcha_site_key'); ?>"/>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th>
                                    <?php echo esc_html__( 'Chave secreta', 'flexify-dashboard-recaptcha-addon' ) ?>
                                    <span class="flexify-dashboard-description"><?php echo esc_html__('Informe aqui a chave secreta relacionado a API do Google reCAPTCHA (Caixa de verificação v2).', 'flexify-dashboard-recaptcha-addon' ) ?></span>
                                </th>
                                <td>
                                    <div class="form-check form-switch">
                                        <input type="text" class="form-control input-control-wd-20" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?php echo Init::get_setting('recaptcha_secret_key'); ?>"/>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="modal-footer align-content-start flex-column">
                        <?php if ( Init::get_setting('enable_recaptcha_admin_login') === 'yes' ) :
                            wp_enqueue_script('flexify_dashboard_recaptcha_google_api');
                            Core::recaptcha_form();
                        endif; ?>

                        <div class="d-block mt-4">
                            <h5 class="fs-6"><?php echo esc_html__( 'Observações importantes!', 'flexify-dashboard-recaptcha-addon' ) ?></h5>
                            <ul class="text-start">
                                <li><?php echo esc_html__( '1. Se você estiver vendo alguma mensagem de erro. Verifique suas credenciais antes de continuar.', 'flexify-dashboard-recaptcha-addon' ) ?></li>
                                <li><?php echo esc_html__( '2. Se o reCAPTCHA apareceu corretamente, verifique os seguintes pontos:', 'flexify-dashboard-recaptcha-addon' ) ?>
                                    <ul>
                                        <li><?php echo esc_html__( 'Abra seu site em uma guia anônima.', 'flexify-dashboard-recaptcha-addon' ) ?></li>
                                        <li><?php echo esc_html__( 'Tente fazer login como administrador.', 'flexify-dashboard-recaptcha-addon' ) ?></li>
                                    </ul>
                                </li>
                                <li><?php echo esc_html__( '3. Só feche essa janela após verificar que consegue fazer login perfeitamente.', 'flexify-dashboard-recaptcha-addon' ) ?></li>
                                <li><?php echo esc_html__( '4. Se não conseguir fazer login como administrador em uma guia anônima, desative esse recurso. Do contrário você pode não conseguir entrar mais no painel.', 'flexify-dashboard-recaptcha-addon' ) ?></li>
                                <li><?php echo __( "5. Em caso de emergência, adicione <code>define('FLEXIFY_DASHBOARD_RECAPTCHA_DISABLED', true);</code> no arquivo wp-config.php do WordPress para desativar o reCAPTCHA no login de administrador.", 'flexify-dashboard-recaptcha-addon' ) ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

new Admin();