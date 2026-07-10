(function ($) {
    $(document).on('show.bs.modal', '.modal', function () {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function () {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });

    $(document).ready(function () {
        $('#tb_consultas').DataTable({
            language: dataTable_es,
            processing: true,
            serverSide: true,
            searching: false,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: 'listar_consultas.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.id = $('#txt_id_filtro').val();
                    data.nombre = $('#txt_titulo_filtro').val();
                }
            },
            columns: [
                { 'data': 'id_consulta' }, //Index=0
                { 'data': 'titulo_consulta' },
                { 'data': 'botones' }
            ],
            order: [
                [1, "ASC"]
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
        });

        $('.bttn-plus-dt span').html('<span class="icon-dt fas fa-plus-circle fa-lg"></span>');
        // Wrap table to make it responsive on small screens
        $('#tb_consultas').wrap('<div class="table-responsive"/>');
    });

    //Buascar registros
    $('#btn_buscar_filtro').on("click", function () {
        $('#tb_consultas').DataTable().ajax.reload(null, false);
    });

    $('.filtro').keypress(function (e) {
        if (e.keyCode == 13) {
            $('#tb_consultas').DataTable().ajax.reload(null, false);
        }
    });
    
    $('#tb_consultas').on('click', 'tr', function () {
        let tabla = $('#tb_consultas').DataTable();
        let fila = tabla.row(this).data();
        let id = fila.id_consulta;
        
        if (id) {
            $.ajax({
                url: "datos_consulta.php",
                dataType: "json",
                type: 'POST',
                data: { id: id }
            }).done(function (data) {
                $('#txt_id_consulta').val(data.id_consulta);
                $('#txt_detalle_consulta').html(
                    '<b style="color:#800000;">' + data.nom_consulta + '</b><br><br>' +
                    '<b style="color:#000080;">Descripción</b><br>' + data.des_consulta + '<br><br>' +
                    '<b style="color:#000080;">Tipos de Bases de Datos :</b>' + data.nom_tipo_bdatos + '<br>' +
                    '<b style="color:#000080;">Tipo de Informe :</b>' + data.nom_tipo_informe + '<br>' +
                    '<b style="color:#000080;">Tipo de Consulta :</b>' + data.nom_tipo_consulta + '<br>' +
                    '<b style="color:#000080;">Tipo de Acceso :</b>' + data.nom_tipo_acceso
                );
            });
        }
    });

    /* -------------------------------------------------------
    CREAR LAS TABS DINAMINICAS PARA LAS CONSULTAS ANALÍTICAS
    --------------------------------------------------------- */
    // Ajusta la altura de un textarea al contenido
    function adjustTextareaHeight(selector) {
        var $ta = $(selector);
        $ta.each(function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });    
    }

    class TabManager {
        constructor() {
            this.map = {}; // key: paneId, value: data
            this.results = {}; // key: paneId, value: results (resp from server)
            this.active = null; // current active paneId
        }
        addData(paneId, data) {
            this.map[paneId] = data;
        }
        getData(paneId) {
            return this.map[paneId] || null;
        }
        setResults(paneId, results) {
            this.results[paneId] = results;
        }        
        getResults(paneId) {
            return this.results[paneId] || null;
        }        
        getResultsCols(paneId) {
            if (!this.results[paneId]) return [];
            return this.results[paneId].columns || [];
        }        
        getResultsRows(paneId) {
            if (!this.results[paneId]) return [];
            return this.results[paneId].rows || [];
        }
        setActive(paneId) {
            this.active = paneId;
        }
        getActive() {
            return this.getData(this.active);
        }        
        removeTab(paneId) {
            if (this.map[paneId]) delete this.map[paneId];
            if (this.results[paneId]) delete this.results[paneId];
            if (this.active === paneId) this.active = null;
        }
    }

    const tabManager = new TabManager();
    let numTabs = 0;
    
    $('#tb_consultas').on('click', '.btn_acceder', function () {
        let id = $(this).attr('value');
        cargarDatosConsulta(id);
    });

    $("#btn_acceder_consulta").on("click", function () {
        let id = $('#txt_id_consulta').val();
        if (id == '') {
            mjeError('Debe seleccionar una Consulta Analítica');
            return;
        }        
        cargarDatosConsulta(id);
    });

    function cargarDatosConsulta(id){
        $.ajax({
            url: "datos_consulta.php",
            type: "POST",
            dataType: "json",
            data: {id:id}
        }).done(function(data){
            crearTabConsulta(data);
        });
    }

    function crearTabConsulta(data) {
        numTabs++;
        let idTab = "tab_" + numTabs;
        let idPane = "pane_" + numTabs;

        let htmlBdatos = "";
        if(data.tipo_bdatos == 2){
            // Añadir columna de selección (checkbox) y checkbox de "select all" en el header
            htmlBdatos = `<table class="table table-bordered table-sm table-hover" id="tb_bdatos${numTabs}" style="font-size:80%">
                    <thead>
                        <tr>
                            <th class="bg-info-subtle" style="width:5%"><input type="checkbox" id="select_all_bdatos${numTabs}" title="Seleccionar todo"></th>
                            <th class="bg-info-subtle">Fuente de Datos</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>`;
        }

        // Crear la pestaña (con botón de cerrar)
        $("#nav-tab").append(`
            <div class="nav-item d-inline-flex align-items-center" id="nav_item_${numTabs}" style="margin-right:6px;">
                <button type="button" class="nav-link" id="${idTab}" data-bs-toggle="tab" data-bs-target="#${idPane}" title="Consulta de ${data.nom_consulta}">CA ${numTabs}</button>
                <button type="button" class="btn btn-sm btn-close-tab" data-tab="${numTabs}" aria-label="Cerrar" title="Cerrar Consulta ${data.nom_consulta}">&times;</button>
            </div>
        `);

        // Crear el contenido de la pestaña
        $("#tab-content").append(`<div class="tab-pane fade p-2" id="${idPane}" role="tabpanel" aria-labelledby="${idTab}">
            <div class="row">
                <div class="col-md-2">
                    <div style="border:1px solid rgba(0,0,0,0.08);padding:8px;border-radius:4px;">
                        <label>Parámetros de Búsqueda</label>
                        <form class="mb-3" id="frm_parametros${numTabs}"></form>
                        
                        ${htmlBdatos}

                        <button type="button" class="btn btn-outline-primary btn-sm btn_ejecutar_consulta" data-tab="${numTabs}">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-search fa-lg me-2"></i>
                                <span class="small">Buscar</span>
                            </span>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm btn_ejecutar_consulta_dll" data-tab="${numTabs}">
                            <span class="d-flex align-items-center">
                                <i class="fab fa-searchengin fa-lg me-2"></i>
                                <span class="small">Buscar det.</span>
                            </span>
                        </button>
                    </div>    
                    <div style="border:1px solid rgba(0,0,0,0.08);padding:8px;border-radius:4px;">
                        <label>Parámetros de la Gráfica</label>                                
                        <form class="mb-3" id="frm_parametros_grafica${numTabs}">
                            
                            <label class="small">Eje X:</label>
                            <select class="form-select form-select-sm" id="sl_categoria${numTabs}">                            
                            </select>
                            
                            <label class="small">Eje Y:</label>
                            <table class="table table-bordered table-sm table-hover" id="tb_series${numTabs}" style="font-size:80%">
                                <thead>
                                    <tr>
                                        <th class="bg-info-subtle" style="width:5%">Sel</th>
                                        <th class="bg-info-subtle">Informacion</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>

                            <label class="small">Tipo de Grafica:</label>
                            <select class="form-select form-select-sm" id="sl_tipo_grafica${numTabs}">
                                // Graficos de Comparación (Categorias)
                                <option value="bar">Barra</option>
                                <option value="bar_horizontal">Barra horizontal</option>
                                <option value="bar_stacked">Barra apilada</option>

                                // Graficos de Tendencias (Series de tiempo)
                                <option value="line">Línea</option>
                                <option value="area">Área</option>
                                <option value="area_stacked">Área apilada</option>     
                                
                                // Graficos de Proporción (Partes de un todo)
                                <option value="pie">Pastel</option>
                                <option value="doughnut">Dona</option>
                                <option value="funnel">Embudo</option>

                                // Graficas de Distribución yi Relación
                                <option value="scatter">Dispersión</option>
                                <option value="bubbles">Burbujas</option>
                                <option value="boxplot">Caja (Boxplot)</option>
                                <option value="heatmap">Mapa de calor</option>
                                <option value="parallel">Coordenadas paralelas</option>
                                
                                // Graficos Jerarquicos
                                <option value="treemap">Treemap</option>
                                <option value="sunburst">Sunburst</option>
                                <option value="graph">Grafo</option>

                                // Graficos Especiales
                                <option value="radar">Radar</option>                                
                                <option value="gauge">Indicador (Gauge)</option>                                
                                <option value="candlestick">Velas (K-line)</option>
                                <option value="themeriver">ThemeRiver (Río temático)</option>
                            </select>
                        </form>

                        <button type="button" class="btn btn-outline-primary btn-sm btn_graficar_datos" data-tab="${numTabs}">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-chart-bar fa-lg me-2"></i>
                                <span class="small">Graficar</span>
                            </span>
                        </button>
                    </div>    
                </div>

                <div class="col-md-10">  
                    <div style="border:1px solid rgba(0,0,0,0.08);padding:8px;border-radius:4px;">                  
                        <div id="dv_resultado${numTabs}" class="overflow-auto" style="height:300px;max-height:50vh; overflow-y:auto;"></div>
                    </div>
                    <div style="border:1px solid rgba(0,0,0,0.08);padding:8px;border-radius:4px;">    
                        <div id="dv_grafica${numTabs}" class="chart" style="width:100%; height:600px;"></div>
                    </div>    
                </div>
            </div>
        `);
        
        // Cargar los parámetros de la consulta
        let str = "";
        $("#frm_parametros"+numTabs).html("");
        if (data.parametros.length > 0) {
            data.parametros.forEach(function(p){
                str = '<label class="small">'+p.etiqueta+'</label>';            
                switch(p.tipo){
                    case '2':
                        str +='<input type="number" id="' + p.id_parametro + '" data-parametro="' + p.parametro + '" data-tipo="' + p.tipo + '" class="form-control form-control-sm numberint" title="' + p.descripcion + '"/>';
                        break;
                    case '3':
                        str +='<input type="date" id="' + p.id_parametro + '" data-parametro="' + p.parametro + '" data-tipo="' + p.tipo + '" class="form-control form-control-sm" title="' + p.descripcion + '"/>';
                        break;
                    default:
                        str +='<input type="text" id="' + p.id_parametro + '" data-parametro="' + p.parametro + '" data-tipo="' + p.tipo + '" class="form-control form-control-sm" title="' + p.descripcion + '"/>';
                }
                $("#frm_parametros"+numTabs).append(str);
            });
        }

        // Cargar las bases de datos asociadas a la consulta
        if(data.tipo_bdatos == 2){
            let tbody = $("#tb_bdatos"+numTabs+" tbody");
            data.bdatos.forEach(function(bd){
                tbody.append(`
                    <tr>
                        <td class="text-center"><input type="checkbox" class="chk_bdatos" value="${bd.id_bdatos}" checked></td>
                        <td>${bd.nombre_entidad}</td>
                    </tr>
                `);
            });

            // Marcar checkbox 'select all' como seleccionado por defecto
            $("#select_all_bdatos"+numTabs).prop('checked', true);

            // Para checkbox 'select all'
            $("#select_all_bdatos"+numTabs).on('change', function(){
                var checked = $(this).prop('checked');
                $("#tb_bdatos"+numTabs+" tbody input.chk_bdatos").prop('checked', checked);
            });
        }

        // Guardar los datos de la pestaña creada
        tabManager.addData(idPane, data);

        // Activar la nueva pestaña
        bootstrap.Tab.getOrCreateInstance(document.getElementById(idTab)).show();        
        
        // Ajustar la altura del textarea con el nombre de la consulta
        adjustTextareaHeight('#txt_nom_consulta' + numTabs);

        // marcar como activa en TabManager
        tabManager.setActive(idPane);
    }
    
    /* -------------------------------------------------------
    EJECUTAR LA CONSULTA ANALÍTICA
    --------------------------------------------------------- */
    
    function Parametros(parametro, tipo, valor) {
        this.parametro = parametro;
        this.tipo = tipo;
        this.valor = valor;
    }

    $(document).on("click", ".btn_ejecutar_consulta_dll", function () {
        ejecutar_consulta($(this).data("tab"), 1);
    });

    $(document).on("click", ".btn_ejecutar_consulta", function () {
        ejecutar_consulta($(this).data("tab"), 0);    
    });

    function ejecutar_consulta(numTab, detalles = 0) {

        // Obtener los datos data en TabManager para esta pestaña
        let paneId = 'pane_' + numTab;
        let data = tabManager.getData(paneId);

        $('.is-invalid').removeClass('is-invalid'); 
        $('#dv_resultado' + numTab).html('');
        let error = 0;
        let parametros = new Array();

        $('#frm_parametros' + numTab + ' input').each(function () {
            let parametro = $(this);
            let par_val = parametro.val().trim();
            let par_non = parametro.attr('data-parametro');
            let par_tip = parametro.attr('data-tipo');

            if (par_tip == 2 || par_tip == 3 || par_tip == 4) {
                error += verifica_vacio(this);
            }            
            par_val = /[a-zA-Z]/.test(par_val) && par_val.includes(",") ? "\'" + par_val.replace(/,/g, "\',\'") + "\'" : par_val;

            let ObjParametro = new Parametros(par_non, par_tip, par_val);
            parametros[parametros.length] = ObjParametro;
        });
        
        if (error >= 1) {
            mjeError('Los datos resaltados son obligatorios');
            return;
        }    

        // Consulta Multiple BD 
        let $ejecutar_consulta = 'ejecutar_consulta.php';
        let ids_bd = [];
        if (data.tipo_bdatos == 2) {
            if ($("#tb_bdatos" + numTab).length) {
                ids_bd = $("#tb_bdatos" + numTab + " tbody input.chk_bdatos:checked").map(function () {
                    return $(this).val();
                }).get();
            }
            if (ids_bd.length == 0) {
                mjeError('Debe seleccionar por lo menos una fuente de datos');
                return;
            }
            $ejecutar_consulta = 'ejecutar_consulta_ml.php';

            if (data.tipo_consulta == 2) {
                $ejecutar_consulta = 'ejecutar_consulta_mr.php';
            }
        }

        $.ajax({
            url: $ejecutar_consulta,
            type: 'POST',
            dataType: 'json',
            data: {
                id: data.id_consulta,
                parametros: JSON.stringify(parametros),
                ids_bd: JSON.stringify(ids_bd),
                detalles: detalles
            }
        }).done(function (resp) {
            if (!resp || resp.error) {
                let msg = (resp && resp.error) ? resp.error : 'Respuesta inválida del servidor';
                alert(msg);
                return;
            }

            // Construir tabla HTML a partir de resp
            let cols = resp.columns || [];
            let rows = resp.rows || [];

            let html = '<label><strong>' + data.nom_consulta + '</strong></label>';
            html += '<div class="table-responsive"><table class="table table-striped table-bordered table-sm" style="width:100%; font-size:80%"><thead><tr>';
            cols.forEach(function (c) { html += '<th class="bg-sofia">' + c + '</th>'; });
            html += '</tr></thead><tbody>';

            rows.forEach(function (r) {
                html += '<tr>';
                cols.forEach(function (c) {
                    let v = r[c];
                    if (v === null || typeof v === 'undefined') v = '';
                    html += '<td>' + v + '</td>';
                });
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            html += '<label>Máximo No. Registros Visualizados:' + resp.count + '</label>';
            html += '<label class="ms-3">De un Total de:' + resp.total + '</label>';

            $('#dv_resultado' + numTab).html(html);

            tabManager.setResults(paneId, resp);

            // LLenar las columnas de la consulta par la graficación
            if (cols.length && $("#sl_categoria" + numTab).length) {
                let sel = $("#sl_categoria" + numTab);
                sel.html('');
                cols.forEach(function (c) { sel.append('<option value="' + c + '">' + c + '</option>'); });

                let tbodySeries = $("#tb_series" + numTab + " tbody");
                tbodySeries.html('');
                    cols.forEach(function (c) {
                        tbodySeries.append('<tr><td class="p-1 text-center"><input type="checkbox" class="chk_series" value="' + c + '"></td><td class="p-1">' + c + '</td></tr>');
                    });
            }

            $('#divModalEspera').fadeOut(0);
            setTimeout(function () { $('#divModalEspera').modal('hide'); }, 1000);

        }).fail(function (xhr, status, err) {
            alert('Ocurrió un error: ' + err);
        });
    }

    // Actualizar pestaña activa en TabManager cuando el usuario cambia de tab
    $(document).on('shown.bs.tab', '#nav-tab button, #nav-tab a', function (e) {
        let target = $(e.target).data('bsTarget') || $(e.target).attr('data-bs-target');
        if (target) {
            let paneId = target.replace('#', '');
            tabManager.setActive(paneId);
            // If there's a chart inside this pane, ask grafias to resize it (ECharts needs visible container)
            try {
                const num = paneId.replace('pane_','');
                const divId = 'dv_grafica' + num;
                if (typeof grafias !== 'undefined' && typeof grafias.resize === 'function') grafias.resize(divId);
            } catch (e) { }
        }
    });

    // Cerrar una pestaña
    $(document).on('click', '.btn-close-tab', function (e) {
        e.stopPropagation();
        let num = $(this).data('tab');
        let idPane = 'pane_' + num;
        let navItem = '#nav_item_' + num;

        // Destruir gráfica si existe
        try {
            if (typeof grafias !== 'undefined' && grafias.destroyChart) grafias.destroyChart('dv_grafica' + num);
        } catch (err) { }

        // Eliminar elementos DOM: nav item y pane
        $(navItem).remove();
        $('#' + idPane).remove();

        // Limpiar datos en TabManager
        if (typeof tabManager.removeTab === 'function') {
            tabManager.removeTab(idPane);
        } else {
            try { delete tabManager.map[idPane]; delete tabManager.results[idPane]; } catch (e) { }
        }

        // Activar la última pestaña disponible o la de consultas principal
        const lastBtn = $('#nav-tab .nav-link').last();
        if (lastBtn && lastBtn.length) {
            bootstrap.Tab.getOrCreateInstance(lastBtn[0]).show();
        } else {
            // fallback: show main consultas tab
            try { bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-consultas')).show(); } catch (e) {}
        }
    });

    /* -------------------------------------------------------
    GRAFICAR LOS DATOS DE LA CONSULTA ANALÍTICA
    --------------------------------------------------------- */
    $(document).on('click', '.btn_graficar_datos', function () {
        let numTab = $(this).data('tab');
        let paneId = 'pane_' + numTab;

        // Obtener resultados guardados
        let resp = tabManager.getResults(paneId);
        if (!resp) { 
            mjeError('No hay resultados para graficar'); 
            return; 
        }

        let cols = resp.columns || [];
        let rows = resp.rows || [];

        let categoria = $('#sl_categoria' + numTab).val();
        let tipo = $('#sl_tipo_grafica' + numTab).val() || $('#sl_tipo_grafica' + numTab).val();
        let series = $('#tb_series' + numTab + ' tbody input.chk_series:checked').map(function(){ return $(this).val(); }).get();

        if (!categoria) { mjeError('Seleccione la columna de categoría'); return; }
        if (!series || series.length === 0) { mjeError('Seleccione al menos una serie'); return; }

        // labels = values of category column
        const labels = rows.map(r => r[categoria] === undefined ? '' : String(r[categoria]));

        // call grafias
        if (typeof grafias === 'undefined') {
            mjeError('grafias.js no está cargado');
            return;
        }

        const targetDiv = 'dv_grafica' + numTab;
        // Clear previous
        document.getElementById(targetDiv).innerHTML = '';
        
        switch (tipo) {
            case 'bar':
                grafias.drawBar(targetDiv, labels, series, rows, categoria);
                break;
            case 'line':
                grafias.drawLine(targetDiv, labels, series, rows, categoria);
                break;
            case 'pie':
                grafias.drawPie(targetDiv, labels, series, rows, categoria);
                break;
            case 'doughnut':
                grafias.drawDoughnut(targetDiv, labels, series, rows, categoria);
                break;
            case 'scatter':
                grafias.drawScatter(targetDiv, labels, series, rows, categoria);
                break;
            case 'radar':
                grafias.drawRadar(targetDiv, labels, series, rows, categoria);
                break;
            case 'funnel':
                grafias.drawFunnel(targetDiv, labels, series, rows, categoria);
                break;
            case 'gauge':
                grafias.drawGauge(targetDiv, labels, series, rows, categoria);
                break;
            case 'heatmap':
                grafias.drawHeatmap(targetDiv, labels, series, rows, categoria);
                break;
            case 'treemap':
                grafias.drawTreemap(targetDiv, labels, series, rows, categoria);
                break;
            case 'boxplot':
                grafias.drawBoxplot(targetDiv, labels, series, rows, categoria);
                break;
            case 'candlestick':
                grafias.drawCandlestick(targetDiv, labels, series, rows, categoria);
                break;
            case 'sankey':
                grafias.drawSankey(targetDiv, labels, series, rows, categoria);
                break;
            case 'graph':
                grafias.drawGraph(targetDiv, labels, series, rows, categoria);
                break;
            case 'sunburst':
                grafias.drawSunburst(targetDiv, labels, series, rows, categoria);
                break;
            case 'area':
                grafias.drawArea(targetDiv, labels, series, rows, categoria);
                break;
            case 'area_stacked':
                grafias.drawAreaStacked(targetDiv, labels, series, rows, categoria);
                break;
            case 'bar_stacked':
                grafias.drawBarStacked(targetDiv, labels, series, rows, categoria);
                break;
            case 'bar_horizontal':
                grafias.drawBarHorizontal(targetDiv, labels, series, rows, categoria);
                break;
            case 'bubbles':
                grafias.drawBubbles(targetDiv, labels, series, rows, categoria);
                break;
            case 'parallel':
                grafias.drawParallel(targetDiv, labels, series, rows, categoria);
                break;
            case 'themeriver':
                grafias.drawThemeRiver(targetDiv, labels, series, rows, categoria);
                break;
            default:
                mjeError('Tipo de gráfico no soportado: ' + tipo);
        }
    });

})(jQuery);