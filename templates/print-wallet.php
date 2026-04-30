<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Impressão de Carteira</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f0f0; }

        /* ── Wrapper da face ───────────────────────────────────────── */
        .wallet-face {
            position: relative;
            display: inline-block;   /* colapsa no tamanho exato */
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            /* SEM overflow:hidden — campos ficariam ocultos atrás do bg */
        }

        /* Background como <img> absoluta — z-index 0, atrás de tudo */
        .wallet-bg {
            display: block;
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            object-fit: fill;
        }

        /* ── Campos de texto ───────────────────────────────────────── */
        .field {
            position: absolute;
            display: block;
            font-weight: bold;
            color: #000;
            white-space: nowrap;
            line-height: normal;
            text-align: left;
            z-index: 10;   /* sempre acima do background */
        }

        /* ── Campos de imagem (foto do usuário) ────────────────────── */
        .user-photo {
            position: absolute;
            object-fit: fill;
            display: block;
            z-index: 10;
        }

        /* ── Espaçador que dá altura ao .wallet-face ───────────────── */
        .wallet-spacer {
            display: block;
            visibility: hidden;
        }

        /* ── Layout de página ──────────────────────────────────────── */
        .wallet-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            padding: 40px 0;
        }

        .face-label {
            text-align: center;
            font-size: 13px;
            color: #555;
            margin-bottom: 6px;
            font-family: Arial, sans-serif;
        }

        @media print {
            body             { background: none; }
            .no-print        { display: none !important; }
            .wallet-wrapper  { padding: 0; gap: 20px; }
        }

        .controls  { text-align: center; margin-bottom: 20px; }
        .btn-print { padding: 10px 20px; background: #2271b1; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
    </style>
</head>
<body>

<div class="no-print controls">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir Carteira</button>
</div>

<?php
/* ── Opções salvas ──────────────────────────────────────────────────── */
$has_verso   = get_option( 'owg_has_verso', '0' ) === '1';
$bg_frente   = get_option( 'owg_layout_bg_frente', get_option( 'owg_layout_bg', '' ) );
$bg_verso    = get_option( 'owg_layout_bg_verso', '' );
$config_json = get_option( 'owg_layout_config', '{"frente":[],"verso":[]}' );
$config_all  = json_decode( $config_json, true );

/* Compatibilidade com versão anterior (array simples) */
if ( is_array( $config_all ) && array_values( $config_all ) === $config_all ) {
    $config_frente = $config_all;
    $config_verso  = array();
} else {
    $config_frente = isset( $config_all['frente'] ) ? $config_all['frente'] : array();
    $config_verso  = isset( $config_all['verso']  ) ? $config_all['verso']  : array();
}

/* extra_data da carteira */
$extra_data = array();
if ( ! empty( $wallet->extra_data ) ) {
    $decoded = @unserialize( $wallet->extra_data );
    if ( is_array( $decoded ) ) {
        $extra_data = $decoded;
    }
}

/* ── Helper: dimensões reais da imagem ──────────────────────────────── */
function owg_bg_dimensions( $url ) {
    if ( empty( $url ) ) return array( 500, 320 );
    $data = @getimagesize( $url );
    return ( $data && $data[0] > 0 ) ? array( $data[0], $data[1] ) : array( 500, 320 );
}

/* ── Helper: renderizar campos de uma face ──────────────────────────── */
function owg_render_fields( $fields, $extra_data ) {
    if ( empty( $fields ) ) return;
    foreach ( $fields as $field ) {
        if ( empty( $field['id'] ) ) continue;
        $fid   = $field['id'];
        $value = isset( $extra_data[ $fid ] ) ? $extra_data[ $fid ] : '';

        $style = sprintf(
            'left:%dpx; top:%dpx; width:%dpx; height:%dpx; transform:rotate(%ddeg); font-size:%dpx;',
            (int) $field['x'],
            (int) $field['y'],
            (int) $field['w'],
            (int) $field['h'],
            isset( $field['r']  ) ? (int) $field['r']  : 0,
            isset( $field['fs'] ) ? (int) $field['fs'] : 14
        );

        if ( $field['type'] === 'image' ) {
            if ( ! empty( $value ) ) {
                printf( '<img src="%s" class="user-photo" style="%s" alt="">', esc_url( $value ), $style );
            }
        } else {
            printf( '<div class="field" style="%s">%s</div>', $style, esc_html( $value ) );
        }
    }
}

/* ── Dimensões ──────────────────────────────────────────────────────── */
list( $w_frente, $h_frente ) = owg_bg_dimensions( $bg_frente );
list( $w_verso,  $h_verso  ) = owg_bg_dimensions( $bg_verso );
?>

<div class="wallet-wrapper">

    <!-- ── FRENTE ── -->
    <div>
        <?php if ( $has_verso ) : ?>
            <p class="face-label no-print">FRENTE</p>
        <?php endif; ?>

        <div class="wallet-face" style="width:<?php echo (int)$w_frente; ?>px; height:<?php echo (int)$h_frente; ?>px;">
            <?php if ( ! empty( $bg_frente ) ) : ?>
                <img class="wallet-bg" src="<?php echo esc_url( $bg_frente ); ?>" alt="" width="<?php echo (int)$w_frente; ?>" height="<?php echo (int)$h_frente; ?>">
            <?php endif; ?>
            <?php owg_render_fields( $config_frente, $extra_data ); ?>
        </div>
    </div>

    <!-- ── VERSO (somente se ativado) ── -->
    <?php if ( $has_verso ) : ?>
    <div>
        <p class="face-label no-print">VERSO</p>
        <div class="wallet-face" style="width:<?php echo (int)$w_verso; ?>px; height:<?php echo (int)$h_verso; ?>px;">
            <?php if ( ! empty( $bg_verso ) ) : ?>
                <img class="wallet-bg" src="<?php echo esc_url( $bg_verso ); ?>" alt="" width="<?php echo (int)$w_verso; ?>" height="<?php echo (int)$h_verso; ?>">
            <?php endif; ?>
            <?php owg_render_fields( $config_verso, $extra_data ); ?>
        </div>
    </div>
    <?php endif; ?>

</div>

</body>
</html>
