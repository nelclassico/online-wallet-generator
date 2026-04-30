<?php
/**
 * Plugin Name: Gerador de Carteira Online
 * Description: Plugin para geração de carteiras online com layout customizável e opção de impressão.
 * Version: 1.0.0
 * Author: Emanoel Oliveira
 * Text Domain: online-wallet-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'OWG_PATH', plugin_dir_path( __FILE__ ) );
define( 'OWG_URL', plugin_dir_url( __FILE__ ) );

// Carregar dependências
require_once OWG_PATH . 'includes/class-owg-admin.php';
require_once OWG_PATH . 'includes/class-owg-wallet.php';

// Ativação do plugin
register_activation_hook( __FILE__, 'owg_activate' );
function owg_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'owg_wallets';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        wallet_number varchar(50) NOT NULL,
        full_name varchar(255) NOT NULL,
        cpf varchar(20) NOT NULL,
        photo_url text,
        extra_data text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

function owg_init() {
    new OWG_Admin();
    new OWG_Wallet();
}
add_action( 'plugins_loaded', 'owg_init' );
