<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class OWG_Wallet {
    public function __construct() {
        add_action( 'init', array( $this, 'register_print_endpoint' ) );
        add_action( 'template_redirect', array( $this, 'render_print_page' ) );
        add_shortcode( 'consultar_carteira', array( $this, 'render_search_form' ) );
    }

    public function register_print_endpoint() {
        add_rewrite_rule( '^imprimir-carteira/?$', 'index.php?owg_print=1', 'top' );
        add_filter( 'query_vars', function( $vars ) {
            $vars[] = 'owg_print';
            return $vars;
        } );
    }

    public function render_print_page() {
        if ( get_query_var( 'owg_print' ) ) {
            global $wpdb;
            $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
            $table_name = $wpdb->prefix . 'owg_wallets';
            
            // Se for admin, pode ver qualquer uma. Se não, precisa validar a sessão ou CPF (simplificado para o ID neste caso)
            $wallet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );

            if ( ! $wallet ) {
                wp_die( 'Carteira não encontrada ou você não tem permissão para visualizá-la.' );
            }

            $bg_image = get_option( 'owg_layout_bg', '' );
            $config_json = get_option( 'owg_layout_config', '{}' );
            $config = json_decode( $config_json, true );

            include OWG_PATH . 'templates/print-wallet.php';
            exit;
        }
    }

    public function render_search_form() {
        ob_start();
        ?>
        <div class="owg-search-container">
            <form method="post" action="">
                <p>Digite seu CPF para consultar sua carteira:</p>
                <input type="text" name="owg_cpf" placeholder="000.000.000-00" required>
                <input type="submit" name="owg_search" value="Consultar">
            </form>

            <?php
            if ( isset( $_POST['owg_search'] ) && !empty( $_POST['owg_cpf'] ) ) {
                global $wpdb;
                $cpf = sanitize_text_field( $_POST['owg_cpf'] );
                $table_name = $wpdb->prefix . 'owg_wallets';
                $wallet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE cpf = %s", $cpf ) );

                if ( $wallet ) {
                    echo '<div style="margin-top:20px; padding:15px; border:1px solid #ccc; background:#f9f9f9;">';
                    echo '<h4>Carteira encontrada para: ' . esc_html( $wallet->full_name ) . '</h4>';
                    echo '<a href="' . home_url( '/imprimir-carteira/?id=' . $wallet->id ) . '" target="_blank" class="button">Visualizar e Imprimir Carteira</a>';
                    echo '</div>';
                } else {
                    echo '<p style="color:red; margin-top:20px;">Nenhuma carteira encontrada para este CPF.</p>';
                }
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
