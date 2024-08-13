<?php

namespace MeuMouse\Flexify_Dashboard\Recaptcha;

use MeuMouse\Flexify_Dashboard\Init;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for integration with Google reCAPTCHA
 * 
 * @since 1.0.0
 * @package MeuMouse.com
 */
class Core {

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ));
        add_action( 'admin_notices', array( $this, 'admin_notices' ));

        $site_key = Init::get_setting('recaptcha_site_key');
        $secret_key = Init::get_setting('recaptcha_secret_key');
        $valid_recaptcha_key = self::valid_key_secret( $site_key );
        $valid_recaptcha_secret = self::valid_key_secret( $secret_key );
        
        if ( $valid_recaptcha_key && $valid_recaptcha_secret ) {
            add_action( 'login_enqueue_scripts', array( $this, 'enqueue_recaptcha_script' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_recaptcha_script' ) );
            add_action( 'wp_head', array( $this, 'enqueue_recaptcha_script' ) );
            add_action( 'login_form', array( $this, 'recaptcha_form' ) );
            add_action( 'register_form', array( $this, 'recaptcha_form' ), 99 );
            add_action( 'signup_extra_fields', array( $this, 'recaptcha_form' ), 99 );
            add_action( 'lostpassword_form', array( $this, 'recaptcha_form' ) );
            add_filter( 'registration_errors', array( $this, 'authenticate' ), 10, 3 );
            add_action( 'lostpassword_post', array( $this, 'authenticate' ), 10, 1 );
            add_filter( 'authenticate', array( $this, 'authenticate' ), 30, 3 );
            add_filter( 'shake_error_codes', array( $this, 'add_shake_error_codes' ) );
            delete_option('flexify_dashboard_recaptcha_notice');
        } else {
            delete_option( 'flexify_dashboard_recaptcha_working' );
            update_option( 'flexify_dashboard_recaptcha_message_type', 'notice-error' );
            update_option( 'flexify_dashboard_recaptcha_error', sprintf( __( 'A proteção reCAPTCHA para login administrativo não foi configurado corretamente. <a href="%s">Clique aqui</a> para configurar.', 'flexify-dashboard-recaptcha-addon' ), 'admin.php?page=flexify-dashboard-for-woocommerce'));
        }
    }


    /**
     * Regiter settings on load WordPress
     * 
     * @since 1.0.0
     * @return void
     */
    public function register_settings() {
        $site_key = Init::get_setting('recaptcha_site_key');
        $secret_key = Init::get_setting('recaptcha_secret_key');
    
        delete_option('flexify_dashboard_recaptcha_working');
        update_option('flexify_dashboard_recaptcha_site_key', $site_key);
        add_option('flexify_dashboard_recaptcha_error', sprintf( __('A proteção reCAPTCHA para login administrativo não foi configurado corretamente. <a href="%s">Clique aqui</a> para configurar.','flexify-dashboard-recaptcha-addon'), 'admin.php?page=flexify-dashboard-for-woocommerce'));
        add_option('flexify_dashboard_recaptcha_message_type', 'notice-error');
        
        if ( self::valid_key_secret( $site_key ) &&
           self::valid_key_secret( $secret_key ) ) {
            update_option('flexify_dashboard_recaptcha_working', true);
        } else {
            delete_option('flexify_dashboard_recaptcha_working');
            update_option('flexify_dashboard_recaptcha_message_type', 'notice-error');
            update_option('flexify_dashboard_recaptcha_error', sprintf( __('A proteção reCAPTCHA para login administrativo não foi configurado corretamente. <a href="%s">Clique aqui</a> para configurar.','flexify-dashboard-recaptcha-addon'), 'admin.php?page=flexify-dashboard-for-woocommerce'));
        }
    }


    /**
     * Filters a string to ensure it only contains valid characters
     * 
     * @since 1.0.0
     * @param string $string | String for check
     * @return string
     */
    public static function filter_string( $string ) {
        return trim( filter_var( $string, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );//must consist of valid string characters
    }


    /**
     * Check if key is valid
     * 
     * @since 1.0.0
     * @param string $string | String for verify
     * @return bool
     */
    public static function valid_key_secret( $string ) {
        if ( strlen( $string ) === 40 ) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Enqueue reCAPTCHA script
     * 
     * @since 1.0.0
     * @return void
     */
    public static function enqueue_recaptcha_script() {
        $api_url = 'https://www.google.com/recaptcha/api.js?onload=submitDisable';
        wp_register_script('flexify_dashboard_recaptcha_google_api', $api_url, array(), null );
        
        if ( ( !empty( $GLOBALS['pagenow'] ) && ( $GLOBALS['pagenow'] == 'options-general.php' || $GLOBALS['pagenow'] == 'wp-login.php' ) ) ) {
            wp_enqueue_script('flexify_dashboard_recaptcha_google_api');
        }
    }

    public static function get_google_errors_as_string( $g_response ) {
        $string = '';
        $codes = array(
            'missing-input-secret' => __('O parâmetro secreto está faltando.','flexify-dashboard-recaptcha-addon'),
            'invalid-input-secret' => __('O parâmetro secreto é inválido ou está malformado.','flexify-dashboard-recaptcha-addon'),
            'missing-input-response' => __('O parâmetro de resposta está faltando.','flexify-dashboard-recaptcha-addon'),
            'invalid-input-response' => __('O parâmetro de resposta é inválido ou está incorreto.','flexify-dashboard-recaptcha-addon')
        );

        foreach ( $g_response->{'error-codes'} as $code ) {
            $string .= $codes[$code].' ';
        }

        return trim( $string );
    }


    /**
     * Create reCAPTCHA form check
     * 
     * @since 1.0.0
     * @return void
     */
    public static function recaptcha_form() {
        echo sprintf('<div class="g-recaptcha" id="g-recaptcha" data-sitekey="%s" data-callback="submitEnable" data-expired-callback="submitDisable"></div>', get_option('flexify_dashboard_recaptcha_site_key'))."\n";
        echo '<script>'."\n";
        echo "function submitEnable() {\n";
        echo "var button = document.getElementById('wp-submit');\n";
        echo "if (button === null) {\n";
        echo "button = document.getElementById('submit');\n";
        echo "}\n";
        echo "if (button !== null) {\n";
        echo "button.removeAttribute('disabled');\n";
        echo "}\n";

        echo "}\n";
        echo "function submitDisable() {\n";
        echo "var button = document.getElementById('wp-submit');\n";
        
        // do not disable button with id "submit" in admin context, as this is the settings submit button
        if ( !is_admin() ) {
            echo "if (button === null) {\n";
            echo "button = document.getElementById('submit');\n";
            echo "}\n";
        }

        echo "if (button !== null) {\n";
        echo "button.setAttribute('disabled','disabled');\n";
        echo "}\n";

        echo "}\n";
        echo '</script>'."\n";
        
        echo '<noscript>'."\n";
        echo '<div style="width: 100%; height: 473px;">'."\n";
        echo '<div style="width: 100%; height: 422px; position: relative;">'."\n";
        echo '<div style="width: 302px; height: 422px; position: relative;">'."\n";
        echo sprintf('<iframe src="https://www.google.com/recaptcha/api/fallback?k=%s"', get_option('flexify_dashboard_recaptcha_site_key'))."\n";
        echo 'frameborder="0" title="captcha" scrolling="no"'."\n";
        echo 'style="width: 302px; height:422px; border-style: none;">'."\n";
        echo '</iframe>'."\n";
        echo '</div>'."\n";
        echo '<div style="width: 100%; height: 60px; border-style: none;'."\n";
        echo 'bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px; background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">'."\n";
        echo '<textarea id="g-recaptcha-response" name="g-recaptcha-response"'."\n";
        echo 'title="response" class="g-recaptcha-response"'."\n";
        echo 'style="width: 250px; height: 40px; border: 1px solid #c1c1c1;'."\n";
        echo 'margin: 10px 25px; padding: 0px; resize: none;" value="">'."\n";
        echo '</textarea>'."\n";
        echo '</div>'."\n";
        echo '</div>'."\n";
        echo '</div><br>'."\n";
        echo '</noscript>'."\n";
    }


    /**
     * Filters whether a set of user login credentials are valid
     * 
     * @since 1.0.0
     * @param string $user_or_email | Username or email address
     * @param null|WP_User|WP_Error $username | WP_User if the user is authenticated. WP_Error or null otherwise.
     * @param string $password | User password
     * @return string
     */
    public static function authenticate( $user_or_email, $username = null, $password = null ) {
        if ( isset( $_SERVER['PHP_SELF'] ) && basename( $_SERVER['PHP_SELF'] ) !== 'wp-login.php' ) {
            update_option('flexify_dashboard_recaptcha_notice', time());
            update_option('flexify_dashboard_recaptcha_message_type', 'notice-error');
            update_option('flexify_dashboard_recaptcha_error', sprintf(__('Login reCAPTCHA was bypassed on login page: %s.','flexify-dashboard-recaptcha-addon'),basename($_SERVER['PHP_SELF'])));
            
            return $user_or_email;
        }

        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
          return $user_or_email;
        }

        if ( isset( $_POST['g-recaptcha-response'] ) ) {
            $response = self::filter_string( $_POST['g-recaptcha-response'] );
            $payload = array(
                'secret' => Init::get_setting('recaptcha_secret_key'),
                'response' => $response,
            );

            $result = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array('body' => $payload) );
           
            if ( is_wp_error( $result ) ) { // disable SSL verification for older clients and misconfigured TLS trust certificates
                $error_msg = $result->get_error_message();
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $result = curl_exec($ch);
                $g_response = json_decode( $result );
                update_option('flexify_dashboard_recaptcha_notice', time() );
                update_option('flexify_dashboard_recaptcha_message_type', 'notice-warning');
                update_option('flexify_dashboard_recaptcha_error', sprintf( __('O login reCAPTCHA voltou a usar cURL em vez de wp_remote_post(). A mensagem de erro foi: %s.','flexify-dashboard-recaptcha-addon'), $error_msg) );
            } else {
                $g_response = json_decode( $result['body'] );
            }

            if ( is_object( $g_response ) ) {
                if ( $g_response->success ) {
                    update_option('flexify_dashboard_recaptcha_working', true);
                    
                    return $user_or_email; // success, let them in
                } else {
                    if ( isset( $g_response->{'error-codes'} ) && $g_response->{'error-codes'} && in_array( 'missing-input-response', $g_response->{'error-codes'} ) ) {
                        update_option('flexify_dashboard_recaptcha_working', true);
                        
                        if ( is_wp_error( $user_or_email ) ) {
                            $user_or_email->add('no_captcha', __('<strong>ERRO</strong>: Por favor, marque a caixa ReCaptcha.','flexify-dashboard-recaptcha-addon'));
                            return $user_or_email;
                        } else {
                            return new \WP_Error('authentication_failed', __('<strong>ERRO</strong>: Por favor, marque a caixa ReCaptcha.','flexify-dashboard-recaptcha-addon'));
                        }
                    } elseif ( isset( $g_response->{'error-codes'} ) && $g_response->{'error-codes'} &&
                            ( in_array( 'missing-input-secret', $g_response->{'error-codes'} ) || in_array( 'invalid-input-secret', $g_response->{'error-codes'} ) ) ) {
                        delete_option('flexify_dashboard_recaptcha_working');
                        update_option('flexify_dashboard_recaptcha_notice', time() );
                        update_option('flexify_dashboard_recaptcha_google_error', 'error');
                        update_option('flexify_dashboard_recaptcha_error', sprintf( __('O login reCAPTCHA não está funcionando. <a href="%s">Verifique suas configurações</a>. A mensagem do Google foi: %s', 'flexify-dashboard-recaptcha-addon'), 'admin.php?page=flexify-dashboard-for-woocommerce', self::get_google_errors_as_string( $g_response ) ) );
                        
                        return $user_or_email; //invalid secret entered; prevent lockouts
                    } elseif ( isset( $g_response->{'error-codes'} ) ) {
                        update_option('flexify_dashboard_recaptcha_working', true);

                        if ( is_wp_error( $user_or_email ) ) {
                            $user_or_email->add('invalid_captcha', __('<strong>ERRO</strong>: ReCaptcha incorreto, tente novamente.','flexify-dashboard-recaptcha-addon'));
                            return $user_or_email;
                        } else {
                            return new \WP_Error('authentication_failed', __('<strong>ERRO</strong>: ReCaptcha incorreto, tente novamente.','flexify-dashboard-recaptcha-addon'));
                        }
                    } else {
                        delete_option('flexify_dashboard_recaptcha_working');
                        update_option('flexify_dashboard_recaptcha_notice', time());
                        update_option('flexify_dashboard_recaptcha_google_error', 'error');
                        update_option('flexify_dashboard_recaptcha_error', sprintf(__('O login reCAPTCHA não está funcionando. <a href="%s">Verifique suas configurações</a>.', 'flexify-dashboard-recaptcha-addon'), 'admin.php?page=flexify-dashboard-for-woocommerce').' '.__('A resposta do Google não foi válida.','flexify-dashboard-recaptcha-addon'));
                        
                        return $user_or_email; //not a sane response, prevent lockouts
                    }
                }
            } else {
                delete_option('flexify_dashboard_recaptcha_working');
                update_option('flexify_dashboard_recaptcha_notice', time() );
                update_option('flexify_dashboard_recaptcha_google_error', 'error');
                update_option('flexify_dashboard_recaptcha_error', sprintf( __('O login reCAPTCHA não está funcionando. <a href="%s">Verifique suas configurações</a>.', 'flexify-dashboard-recaptcha-addon'), 'admin.php?page=flexify-dashboard-for-woocommerce').' '.__('A resposta do Google não foi válida.','flexify-dashboard-recaptcha-addon'));
                
                return $user_or_email; //not a sane response, prevent lockouts
            }
        } else {
            update_option('flexify_dashboard_recaptcha_working', true);
            
            if ( isset( $_POST['action'] ) && $_POST['action'] === 'lostpassword' ) {
                return new \WP_Error('authentication_failed', __('<strong>ERRO</strong>: Por favor, marque a caixa ReCaptcha.','flexify-dashboard-recaptcha-addon'));
            }

            //If you don't have 'g-recaptcha-response', return only a generic captcha error, not info about about a correct/incorrect user/password.
            return new \WP_Error('authentication_failed', __('<strong>ERRO</strong>: Por favor, marque a caixa ReCaptcha.','flexify-dashboard-recaptcha-addon'));
        }
    }


    /**
     * Display admin notices
     * 
     * @since 1.0.0
     * @return void
     */
    public static function admin_notices() {
        // not working, or notice fired in last 30 seconds
        $flexify_dashboard_recaptcha_error = get_option('flexify_dashboard_recaptcha_error');
        $flexify_dashboard_recaptcha_working = get_option('flexify_dashboard_recaptcha_working');
        $flexify_dashboard_recaptcha_notice = get_option('flexify_dashboard_recaptcha_notice');
        $time = time();
        
        if ( !empty( $flexify_dashboard_recaptcha_error ) && ( empty( $flexify_dashboard_recaptcha_working ) || ( $time - $flexify_dashboard_recaptcha_notice < 30 ) ) ) {
            $message_type = get_option('flexify_dashboard_recaptcha_message_type');
            
            if ( empty( $message_type ) ) {
                $message_type = 'notice-info';
            }

            echo '<div class="notice '. $message_type .' is-dismissible">'."\n";
            echo '<p>'."\n";
            echo get_option('flexify_dashboard_recaptcha_error');
            echo '</p>'."\n";
            echo '</div>'."\n";
        }
    }


    /**
     * Error codes for login form
     * 
     * @since 1.0.0
     * @param array $shake_error_codes | Error codes that shake the login form
     * @return array
     */
    public static function add_shake_error_codes( $shake_error_codes ) {
        $shake_error_codes[] = 'no_captcha';
        $shake_error_codes[] = 'invalid_captcha';
        
        return $shake_error_codes;
    }
}

new Core();