<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class OWG_Admin {
    public function __construct() {
        add_action( 'admin_menu',             array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_admin_assets' ) );
    }

    public function add_admin_menu() {
        add_menu_page( 'Gerador Carteira', 'Gerador Carteira', 'manage_options', 'owg-wallets',    array( $this, 'render_wallets_page' ),     'dashicons-id', 20 );
        add_submenu_page( 'owg-wallets', 'Configurar Layout', 'Configurar Layout', 'manage_options', 'owg-settings',    array( $this, 'render_settings_page' ) );
        add_submenu_page( 'owg-wallets', 'Nova Carteira',     'Nova Carteira',     'manage_options', 'owg-add-wallet',  array( $this, 'render_add_wallet_page' ) );
    }

    public function enqueue_admin_assets() {
        wp_enqueue_media();
        wp_enqueue_style(  'owg-admin-style',  OWG_URL . 'assets/css/admin.css' );
        wp_enqueue_style(  'jquery-ui-style',  'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-resizable' );
        wp_enqueue_script( 'owg-admin-js', OWG_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-resizable' ), '3.0', true );
    }

    /* ── Lista de carteiras ─────────────────────────────────────────── */
    public function render_wallets_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'owg_wallets';
        $wallets    = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
        ?>
        <div class="wrap">
            <h1>Carteiras Geradas
                <a href="<?php echo admin_url( 'admin.php?page=owg-add-wallet' ); ?>" class="page-title-action">Adicionar Nova</a>
            </h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Nome / Referência</th><th>CPF / ID</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php if ( $wallets ) : foreach ( $wallets as $wallet ) : ?>
                        <tr>
                            <td><?php echo esc_html( $wallet->full_name ); ?></td>
                            <td><?php echo esc_html( $wallet->cpf ); ?></td>
                            <td>
                                <a href="<?php echo add_query_arg( 'owg_print', '1', home_url( '/imprimir-carteira/' ) ); ?>&id=<?php echo $wallet->id; ?>" target="_blank">Imprimir</a> |
                                <a href="<?php echo admin_url( 'admin.php?page=owg-add-wallet&action=delete&id=' . $wallet->id ); ?>" style="color:red;" onclick="return confirm('Tem certeza?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="3">Nenhuma carteira encontrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /* ── Configurar Layout ──────────────────────────────────────────── */
    public function render_settings_page() {
        if ( isset( $_POST['owg_save_settings'] ) && check_admin_referer( 'owg_settings_action', 'owg_settings_nonce' ) ) {
            update_option( 'owg_layout_bg_frente', sanitize_text_field( $_POST['owg_layout_bg_frente'] ) );
            update_option( 'owg_layout_bg_verso',  sanitize_text_field( $_POST['owg_layout_bg_verso'] ) );
            update_option( 'owg_has_verso',        isset( $_POST['owg_has_verso'] ) ? '1' : '0' );
            // stripslashes: evita barras extras que WP adiciona no JSON
            update_option( 'owg_layout_config', stripslashes( $_POST['owg_layout_config'] ) );
            echo '<div class="updated"><p>Layout salvo com sucesso!</p></div>';
        }

        $bg_frente  = get_option( 'owg_layout_bg_frente', '' );
        $bg_verso   = get_option( 'owg_layout_bg_verso',  '' );
        $has_verso  = get_option( 'owg_has_verso', '0' );
        $config_json = get_option( 'owg_layout_config', '{"frente":[],"verso":[]}' );
        ?>
        <div class="wrap">
            <h1>Configurar Layout Dinâmico</h1>

            <div class="owg-sidebar">
                <strong>Ações:</strong>
                <button type="button" class="button" id="owg-add-text-field">+ Campo de Texto</button>
                <button type="button" class="button" id="owg-add-image-field">+ Campo de Imagem (Foto)</button>
            </div>

            <form method="post" id="owg-settings-form">
                <?php wp_nonce_field( 'owg_settings_action', 'owg_settings_nonce' ); ?>
                <input type="hidden" name="owg_layout_bg_frente" id="owg_layout_bg_frente" value="<?php echo esc_attr( $bg_frente ); ?>">
                <input type="hidden" name="owg_layout_bg_verso"  id="owg_layout_bg_verso"  value="<?php echo esc_attr( $bg_verso ); ?>">
                <input type="hidden" name="owg_layout_config"    id="owg_layout_config"     value='<?php echo esc_attr( $config_json ); ?>'>

                <div class="owg-frente-verso-toggle">
                    <label>
                        <!-- checkbox DENTRO do form, com name para ser enviado no POST -->
                        <input type="checkbox" name="owg_has_verso" id="owg_has_verso" value="1" <?php checked( $has_verso, '1' ); ?>>
                        &nbsp;Carteira tem <strong>frente e verso</strong>
                    </label>
                </div>

                <!-- Abas frente / verso -->
                <div id="owg-tab-bar" style="<?php echo $has_verso === '1' ? '' : 'display:none;'; ?>">
                    <button type="button" class="owg-tab-btn active" id="owg-tab-frente" data-side="frente">🪪 Frente</button>
                    <button type="button" class="owg-tab-btn"        id="owg-tab-verso"  data-side="verso">↩️ Verso</button>
                </div>

                <!-- ── FRENTE ── -->
                <div id="owg-section-frente" class="owg-canvas-section active">
                    <p class="owg-section-label">
                        Frente da Carteira &nbsp;
                        <button type="button" class="button button-small" id="owg_upload_bg_frente">🖼 Selecionar Imagem de Fundo</button>
                    </p>
                    <div class="owg-canvas-wrap">
                        <div id="owg-canvas-frente" class="owg-canvas"
                             style="background-image:url('<?php echo esc_url( $bg_frente ); ?>');"></div>
                    </div>
                </div>

                <!-- ── VERSO ── -->
                <div id="owg-verso-section" style="<?php echo $has_verso === '1' ? '' : 'display:none;'; ?>">
                    <div id="owg-section-verso" class="owg-canvas-section" style="display:none;">
                        <p class="owg-section-label">
                            Verso da Carteira &nbsp;
                            <button type="button" class="button button-small" id="owg_upload_bg_verso">🖼 Selecionar Imagem de Fundo</button>
                        </p>
                        <div class="owg-canvas-wrap">
                            <div id="owg-canvas-verso" class="owg-canvas"
                                 style="background-image:url('<?php echo esc_url( $bg_verso ); ?>');"></div>
                        </div>
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="owg_save_settings" class="button-primary" value="Salvar Configurações de Layout">
                </p>
            </form>

        </div>
        <?php
    }

    /* ── Adicionar / Editar Carteira ────────────────────────────────── */
    public function render_add_wallet_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'owg_wallets';

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            $wpdb->delete( $table_name, array( 'id' => (int) $_GET['id'] ) );
            echo '<script>window.location.href="' . admin_url( 'admin.php?page=owg-wallets' ) . '";</script>';
            exit;
        }

        $config_raw = get_option( 'owg_layout_config', '{"frente":[],"verso":[]}' );
        $config_all = json_decode( $config_raw, true );
        if ( ! is_array( $config_all ) ) $config_all = array();

        // Compatibilidade com versão anterior (array simples)
        if ( isset( $config_all[0] ) || empty( $config_all ) ) {
            $config = array_merge(
                isset( $config_all['frente'] ) ? $config_all['frente'] : ( is_array( $config_all ) && ! isset( $config_all['frente'] ) ? $config_all : array() ),
                isset( $config_all['verso']  ) ? $config_all['verso']  : array()
            );
        } else {
            $config = array_merge(
                isset( $config_all['frente'] ) ? $config_all['frente'] : array(),
                isset( $config_all['verso']  ) ? $config_all['verso']  : array()
            );
        }
        if ( ! is_array( $config ) ) $config = array();

        if ( isset( $_POST['owg_save_wallet'] ) && check_admin_referer( 'owg_wallet_action', 'owg_wallet_nonce' ) ) {
            $extra_data    = array();
            $full_name     = 'Carteira';
            $cpf           = '';
            $wallet_number = '';

            foreach ( $config as $field ) {
                $field_id            = $field['id'];
                $val                 = isset( $_POST[ 'field_' . $field_id ] ) ? sanitize_text_field( $_POST[ 'field_' . $field_id ] ) : '';
                $extra_data[$field_id] = $val;

                $slug = strtolower( $field['label'] );
                if ( strpos( $slug, 'nome' ) !== false )                                                  $full_name = $val;
                if ( strpos( $slug, 'cpf' )  !== false )                                                  $cpf       = $val;
                if ( strpos( $slug, 'numero' ) !== false || strpos( $slug, 'número' ) !== false )         $wallet_number = $val;
            }

            $wpdb->insert( $table_name, array(
                'wallet_number' => $wallet_number,
                'full_name'     => $full_name,
                'cpf'           => $cpf,
                'photo_url'     => '',
                'extra_data'    => serialize( $extra_data ),
            ) );
            echo '<div class="updated"><p>Carteira salva com sucesso!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Preencher Dados da Carteira</h1>
            <?php if ( empty( $config ) ) : ?>
                <div class="notice notice-warning"><p>Configure o layout primeiro antes de adicionar carteiras.</p></div>
            <?php else : ?>
                <form method="post">
                    <?php wp_nonce_field( 'owg_wallet_action', 'owg_wallet_nonce' ); ?>
                    <table class="form-table">
                        <?php foreach ( $config as $field ) : ?>
                            <tr>
                                <th><?php echo esc_html( $field['label'] ); ?></th>
                                <td>
                                    <?php if ( $field['type'] === 'image' ) : ?>
                                        <input type="text" name="field_<?php echo $field['id']; ?>" id="img_<?php echo $field['id']; ?>" class="regular-text">
                                        <button type="button" class="button owg-upload-field-img" data-target="#img_<?php echo $field['id']; ?>">Selecionar Foto</button>
                                    <?php else : ?>
                                        <input type="text" name="field_<?php echo $field['id']; ?>" class="regular-text">
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <p class="submit"><input type="submit" name="owg_save_wallet" class="button-primary" value="Salvar Carteira"></p>
                </form>
            <?php endif; ?>
            <script>
                jQuery(document).ready(function($){
                    $('.owg-upload-field-img').click(function(e){
                        e.preventDefault();
                        var target = $(this).data('target');
                        var frame  = wp.media({ title: 'Selecionar Imagem', multiple: false }).open();
                        frame.on('select', function(){
                            var attachment = frame.state().get('selection').first().toJSON();
                            $(target).val(attachment.url);
                        });
                    });
                });
            </script>
        </div>
        <?php
    }
}
