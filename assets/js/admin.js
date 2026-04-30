(function($) {
    'use strict';

    $(function() {
        var $configInput  = $('#owg_layout_config');
        var activeCanvas  = 'frente';

        var config = { frente: [], verso: [] };

        try {
            var raw = JSON.parse($configInput.val() || '{}');
            if (Array.isArray(raw)) {
                config.frente = raw;
                config.verso  = [];
            } else {
                config.frente = raw.frente || [];
                config.verso  = raw.verso  || [];
            }
        } catch(e) {
            config = { frente: [], verso: [] };
        }

        function updateConfig() {
            $configInput.val(JSON.stringify(config));
        }

        function renderField(fieldData, index, side) {
            var $canvas = side === 'verso' ? $('#owg-canvas-verso') : $('#owg-canvas-frente');
            var isImage = fieldData.type === 'image';
            var label   = fieldData.label || (isImage ? 'FOTO' : 'TEXTO');

            var style = 'left:' + fieldData.x + 'px; top:' + fieldData.y + 'px;'
                      + 'width:' + fieldData.w + 'px; height:' + fieldData.h + 'px;'
                      + 'transform:rotate(' + (fieldData.r || 0) + 'deg);'
                      + 'font-size:' + (fieldData.fs || 14) + 'px;';

            var html  = '<div class="owg-draggable field-type-' + fieldData.type + '"'
                      + ' data-index="' + index + '" data-side="' + side + '" style="' + style + '">';
            html += '<div class="owg-controls">';
            html += '<button type="button" class="owg-rotate-btn" title="Girar 90deg">&#x1F504;</button>';
            if (!isImage) {
                html += '<input type="number" class="owg-font-size" value="' + (fieldData.fs || 14) + '" title="Fonte" style="width:40px;">';
            }
            html += '<button type="button" class="owg-remove-field" title="Remover">&#x2716;</button>';
            html += '</div>';
            html += '<span class="owg-label-text">' + label + '</span>';
            html += '</div>';

            var $el = $(html);
            $canvas.append($el);

            $el.draggable({
                containment: 'parent',
                scroll: false,
                stop: function(event, ui) {
                    var idx = parseInt($(this).data('index'));
                    var s   = $(this).data('side');
                    config[s][idx].x = ui.position.left;
                    config[s][idx].y = ui.position.top;
                    updateConfig();
                }
            }).resizable({
                containment: 'parent',
                handles: 'all',
                stop: function(event, ui) {
                    var idx = parseInt($(this).data('index'));
                    var s   = $(this).data('side');
                    config[s][idx].w = ui.size.width;
                    config[s][idx].h = ui.size.height;
                    config[s][idx].x = ui.position.left;
                    config[s][idx].y = ui.position.top;
                    updateConfig();
                }
            });
        }

        $('#owg-add-text-field, #owg-add-image-field').click(function() {
            var type  = $(this).attr('id') === 'owg-add-text-field' ? 'text' : 'image';
            var label = prompt('Nome do campo (ex: Nome, CPF, Validade):');
            if (!label) return;

            var newField = {
                type:  type,
                label: label,
                id:    label.toLowerCase().replace(/[^a-z0-9]/g, '_') + '_' + Date.now(),
                x: 10, y: 10,
                w: type === 'image' ? 100 : 150,
                h: type === 'image' ? 130 :  30,
                r: 0, fs: 14
            };

            config[activeCanvas].push(newField);
            renderField(newField, config[activeCanvas].length - 1, activeCanvas);
            updateConfig();
        });

        $(document).on('click', '.owg-remove-field', function() {
            if (!confirm('Deseja remover este campo?')) return;
            var $el  = $(this).closest('.owg-draggable');
            var idx  = parseInt($el.data('index'));
            var side = $el.data('side');
            config[side].splice(idx, 1);
            var $canvas = side === 'verso' ? $('#owg-canvas-verso') : $('#owg-canvas-frente');
            $canvas.find('.owg-draggable').remove();
            config[side].forEach(function(f, i) { renderField(f, i, side); });
            updateConfig();
        });

        $(document).on('click', '.owg-rotate-btn', function() {
            var $el  = $(this).closest('.owg-draggable');
            var idx  = parseInt($el.data('index'));
            var side = $el.data('side');
            config[side][idx].r = ((config[side][idx].r || 0) + 90) % 360;
            $el.css('transform', 'rotate(' + config[side][idx].r + 'deg)');
            updateConfig();
        });

        $(document).on('change keyup', '.owg-font-size', function() {
            var $el  = $(this).closest('.owg-draggable');
            var idx  = parseInt($el.data('index'));
            var side = $el.data('side');
            config[side][idx].fs = parseInt($(this).val());
            $el.css('font-size', config[side][idx].fs + 'px');
            updateConfig();
        });

        function loadBgImage(url, canvasId) {
            var img = new Image();
            img.onload = function() {
                var w = this.width;
                var h = this.height;
                var $c = $('#' + canvasId);
                $c.css({
                    'width':            w + 'px',
                    'height':           h + 'px',
                    'background-image': 'url(' + url + ')',
                    'background-size':  w + 'px ' + h + 'px'
                });
            };
            img.src = url;
        }

        $('#owg_upload_bg_frente').click(function(e) {
            e.preventDefault();
            var frame = wp.media({ title: 'Fundo – Frente', multiple: false }).open();
            frame.on('select', function() {
                var att = frame.state().get('selection').first().toJSON();
                $('#owg_layout_bg_frente').val(att.url);
                loadBgImage(att.url, 'owg-canvas-frente');
            });
        });

        $('#owg_upload_bg_verso').click(function(e) {
            e.preventDefault();
            var frame = wp.media({ title: 'Fundo – Verso', multiple: false }).open();
            frame.on('select', function() {
                var att = frame.state().get('selection').first().toJSON();
                $('#owg_layout_bg_verso').val(att.url);
                loadBgImage(att.url, 'owg-canvas-verso');
            });
        });

        function applyVersoMode() {
            if ($('#owg_has_verso').is(':checked')) {
                $('#owg-verso-section').show();
                $('#owg-tab-bar').show();
            } else {
                $('#owg-verso-section').hide();
                $('#owg-tab-bar').hide();
                // Forçar aba frente
                activeCanvas = 'frente';
                $('.owg-tab-btn').removeClass('active');
                $('#owg-tab-frente').addClass('active');
                $('#owg-section-frente').show();
                $('#owg-section-verso').hide();
            }
        }

        $('#owg_has_verso').on('change', applyVersoMode);

        $(document).on('click', '.owg-tab-btn', function() {
            var side = $(this).data('side');
            activeCanvas = side;
            $('.owg-tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.owg-canvas-section').hide();
            $('#owg-section-' + side).show();
        });

        // Init
        config.frente.forEach(function(f, i) { renderField(f, i, 'frente'); });
        config.verso.forEach(function(f, i)  { renderField(f, i, 'verso');  });

        var bgFrente = $('#owg_layout_bg_frente').val();
        var bgVerso  = $('#owg_layout_bg_verso').val();
        if (bgFrente) loadBgImage(bgFrente, 'owg-canvas-frente');
        if (bgVerso)  loadBgImage(bgVerso,  'owg-canvas-verso');

        applyVersoMode();
        activeCanvas = 'frente';
    });
})(jQuery);
