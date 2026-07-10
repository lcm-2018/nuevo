(function(window){
    // Lightweight ECharts wrapper with many chart types
    const charts = {};

    function destroyChart(divId){
        const inst = charts[divId];
        if (inst) {
            try { inst.dispose(); } catch(e){}
            delete charts[divId];
        }
    }

    function ensureContainer(divId){
        const container = document.getElementById(divId);
        if (!container) return null;
        container.innerHTML = '';
        return container;
    }

    function colorFor(i){
        const palette = ['#36A2EB','#FF6384','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#8A2BE2','#20B2AA'];
        return palette[i % palette.length];
    }

    function drawCommon(divId, option){
        const container = ensureContainer(divId);
        if (!container) { console.error('Container not found:', divId); return; }
        if (typeof echarts === 'undefined'){
            container.innerHTML = '<div class="alert alert-warning">ECharts no está cargado</div>';
            return;
        }
        destroyChart(divId);
        const chart = echarts.init(container);
        chart.setOption(option);
        charts[divId] = chart;
        return chart;
    }

    // Basic series builders
    function drawBar(divId, labels, seriesNames, rows, categoryName){
        const series = seriesNames.map(function(name, idx){
            const data = rows.map(r => {
                const n = parseFloat(r[name]);
                return isNaN(n)?0:n;
            });
            return { name: name, type: 'bar', data: data, itemStyle: { color: colorFor(idx) } };
        });
        const option = {
            tooltip:{ trigger: 'axis' },
            legend:{ data: seriesNames },
            grid:{ left:'3%', right:'4%', bottom:'3%', containLabel:true },
            xAxis:{ type:'category', data: labels, name: categoryName || '' },
            yAxis:{ type:'value' },
            series: series
        };
        return drawCommon(divId, option);
    }

    function drawLine(divId, labels, seriesNames, rows, categoryName){
        const series = seriesNames.map(function(name, idx){
            const data = rows.map(r => { const n = parseFloat(r[name]); return isNaN(n)?0:n; });
            return { name: name, type: 'line', data: data, smooth:true, itemStyle:{ color: colorFor(idx) } };
        });
        const option = {
            tooltip:{ trigger: 'axis' },
            legend:{ data: seriesNames },
            grid:{ left:'3%', right:'4%', bottom:'3%', containLabel:true },
            xAxis:{ type:'category', data: labels, name: categoryName || '' },
            yAxis:{ type:'value' },
            series: series
        };
        return drawCommon(divId, option);
    }

    function drawPie(divId, labels, seriesNames, rows/*, categoryName not used for pie*/){
        if (!seriesNames || seriesNames.length === 0) return;
        if (seriesNames.length > 1){
            const data = seriesNames.map(function(name, idx){
                const sum = rows.reduce(function(acc, r){ const n = parseFloat(r[name]); return acc + (isNaN(n)?0:n); }, 0);
                return { name: name, value: sum, itemStyle:{ color: colorFor(idx) } };
            });
            const option = {
                tooltip:{ trigger:'item' },
                legend:{ orient:'vertical', left:'left', data: seriesNames },
                series:[{ name:'Series', type:'pie', radius:'50%', data: data, emphasis:{ itemStyle:{ shadowBlur:10, shadowOffsetX:0, shadowColor:'rgba(0,0,0,0.5)'} } }]
            };
            return drawCommon(divId, option);
        }
        const serie = seriesNames[0];
        const data = rows.map(function(r,i){ const v = parseFloat(r[serie]); return { name: labels[i] || String(i), value: isNaN(v)?0:v, itemStyle:{ color: colorFor(i) } }; });
        const option = {
            tooltip:{ trigger:'item' },
            legend:{ orient:'vertical', left:'left', data: labels },
            series:[{ name: serie, type:'pie', radius:'50%', data: data, emphasis:{ itemStyle:{ shadowBlur:10, shadowOffsetX:0, shadowColor:'rgba(0,0,0,0.5)'} } }]
        };
        return drawCommon(divId, option);
    }

    function drawDoughnut(divId, labels, seriesNames, rows, categoryName){
        const chart = drawPie(divId, labels, seriesNames, rows);
        try{
            const inst = charts[divId];
            if (inst && inst.getOption){
                const opt = inst.getOption();
                if (opt && opt.series && opt.series.length>0){
                    opt.series[0].radius = ['40%','70%'];
                    inst.setOption(opt);
                }
            }
        }catch(e){}
        return chart;
    }

    function drawScatter(divId, labels, seriesNames, rows, categoryName){
        if (!seriesNames || seriesNames.length === 0) return;
        if (seriesNames.length >= 2){
            const xName = seriesNames[0];
            const yName = seriesNames[1];
            const data = rows.map(r=>[parseFloat(r[xName])||0, parseFloat(r[yName])||0]);
            const option = { tooltip:{ trigger:'item' }, xAxis:{ type:'value', name: categoryName || '' }, yAxis:{ type:'value' }, series:[{ symbolSize:8, data:data, type:'scatter' }] };
            return drawCommon(divId, option);
        } else {
            const name = seriesNames[0];
            const data = rows.map((r,i)=>[i, parseFloat(r[name])||0]);
            const option = { tooltip:{ trigger:'item' }, xAxis:{ type:'category', data: labels, name: categoryName || '' }, yAxis:{ type:'value' }, series:[{ symbolSize:8, data:data, type:'scatter' }] };
            return drawCommon(divId, option);
        }
    }

    function drawRadar(divId, labels, seriesNames, rows){
        if (!seriesNames || seriesNames.length === 0) return;
        const indicators = seriesNames.map(function(name){
            const vals = rows.map(r=>parseFloat(r[name])||0);
            const max = Math.max.apply(null, vals.concat([1]));
            return { name: name, max: max };
        });
        const seriesData = rows.map(function(r,i){ return { name: labels[i] || ('r'+i), value: seriesNames.map(n=>parseFloat(r[n])||0) }; });
        const option = { tooltip:{}, legend:{ data: seriesData.map(s=>s.name) }, radar:{ indicator: indicators }, series:[{ type:'radar', data: seriesData }] };
        return drawCommon(divId, option);
    }

    function drawFunnel(divId, labels, seriesNames, rows){
        if (!seriesNames || seriesNames.length === 0) return;
        const data = seriesNames.map(function(name, idx){ const sum = rows.reduce((acc,r)=>{ const n=parseFloat(r[name]); return acc+(isNaN(n)?0:n); },0); return { name: name, value: sum }; });
        const option = { tooltip:{ trigger:'item' }, series:[{ type:'funnel', data: data, label:{ show:true } }] };
        return drawCommon(divId, option);
    }

    function drawGauge(divId, labels, seriesNames, rows){
        if (!seriesNames || seriesNames.length === 0) return;
        const name = seriesNames[0];
        const value = rows.reduce((acc,r)=>{ const n=parseFloat(r[name]); return acc+(isNaN(n)?0:n); },0);
        const option = { tooltip:{ formatter: '{a} <br/>{b} : {c}' }, series:[{ name: name, type:'gauge', detail:{ formatter:'{value}' }, data:[{ value: value, name: name }] }] };
        return drawCommon(divId, option);
    }

    function drawHeatmap(divId, labels, seriesNames, rows, categoryName){
        if (!seriesNames || seriesNames.length === 0) return;
        const x = labels;
        const y = seriesNames;
        const data = [];
        for (let i=0;i<rows.length;i++){
            for (let j=0;j<seriesNames.length;j++){
                const v = parseFloat(rows[i][seriesNames[j]])||0;
                data.push([i, j, v]);
            }
        }
        const option = { tooltip:{ position:'top' }, xAxis:{ type:'category', data:x, name: categoryName || '' }, yAxis:{ type:'category', data:y }, visualMap:{ min:0, max: Math.max.apply(null, data.map(d=>d[2]).concat([1])), calculable:true, orient:'horizontal', left:'center', bottom:'15%' }, series:[{ name:'heat', type:'heatmap', data:data, emphasis:{ itemStyle:{ borderColor:'#333', borderWidth:1 } } }] };
        return drawCommon(divId, option);
    }

    function drawTreemap(divId, labels, seriesNames, rows){
        if (!seriesNames || seriesNames.length === 0) return;
        const children = seriesNames.map(function(name){ const sum = rows.reduce((acc,r)=>{ const n=parseFloat(r[name]); return acc+(isNaN(n)?0:n); },0); return { name:name, value: sum }; });
        const option = { series:[{ type:'treemap', data: children, breadcrumb:{ show:false } }] };
        return drawCommon(divId, option);
    }

    // Area chart (simple and stacked variants)
    function drawArea(divId, labels, seriesNames, rows, categoryName){
        const series = seriesNames.map(function(name, idx){
            const data = rows.map(r=>parseFloat(r[name])||0);
            return { name:name, type:'line', areaStyle:{}, data:data, itemStyle:{ color: colorFor(idx) } };
        });
        const option = { tooltip:{ trigger:'axis' }, legend:{ data: seriesNames }, xAxis:{ type:'category', data: labels, name: categoryName || '' }, yAxis:{ type:'value' }, series: series };
        return drawCommon(divId, option);
    }

    function drawAreaStacked(divId, labels, seriesNames, rows, categoryName){
        const series = seriesNames.map(function(name, idx){
            const data = rows.map(r=>parseFloat(r[name])||0);
            return { name:name, type:'line', stack: 'stack', areaStyle:{}, data:data, itemStyle:{ color: colorFor(idx) } };
        });
        const option = { tooltip:{ trigger:'axis' }, legend:{ data: seriesNames }, xAxis:{ type:'category', data: labels, name: categoryName || '' }, yAxis:{ type:'value' }, series: series };
        return drawCommon(divId, option);
    }

    function drawBarStacked(divId, labels, seriesNames, rows, categoryName){
        const series = seriesNames.map(function(name, idx){
            const data = rows.map(r=>parseFloat(r[name])||0);
            return { name:name, type:'bar', stack:'stack', data:data, itemStyle:{ color: colorFor(idx) } };
        });
        const option = { tooltip:{ trigger:'axis' }, legend:{ data: seriesNames }, xAxis:{ type:'category', data: labels, name: categoryName || '' }, yAxis:{ type:'value' }, series: series };
        return drawCommon(divId, option);
    }

    function drawBarHorizontal(divId, labels, seriesNames, rows, categoryName){
        // horizontal: categories on y-axis
        const series = seriesNames.map(function(name, idx){
            const data = rows.map(r=>parseFloat(r[name])||0);
            return { name:name, type:'bar', data:data, itemStyle:{ color: colorFor(idx) } };
        });
        const option = { tooltip:{ trigger:'axis' }, legend:{ data: seriesNames }, xAxis:{ type:'value' }, yAxis:{ type:'category', data: labels, name: categoryName || '' }, series: series };
        return drawCommon(divId, option);
    }

    // Bubbles: first two series => x,y, optional third => size
    function drawBubbles(divId, labels, seriesNames, rows, categoryName){
        if (!seriesNames || seriesNames.length === 0) return;
        let data = [];
        if (seriesNames.length >= 2){
            const xName = seriesNames[0], yName = seriesNames[1], sizeName = seriesNames[2];
            data = rows.map(function(r, i){
                const x = parseFloat(r[xName])||0;
                const y = parseFloat(r[yName])||0;
                const s = sizeName ? (parseFloat(r[sizeName])||5) : 8;
                return [x, y, s];
            });
            const option = { tooltip:{ formatter: function(p){ return p.data; } }, xAxis:{ type:'value', name: xName }, yAxis:{ type:'value', name: yName }, series:[{ type:'scatter', symbolSize: function(val){ return Math.max(4, Math.sqrt(val[2])); }, data: data }] };
            return drawCommon(divId, option);
        } else {
            // fallback: use index as x and value as y
            const name = seriesNames[0];
            data = rows.map(function(r,i){ const y = parseFloat(r[name])||0; return [i, y, Math.abs(y)||5]; });
            const option = { tooltip:{}, xAxis:{ type:'category', data: labels, name: categoryName || '' }, yAxis:{ type:'value' }, series:[{ type:'scatter', symbolSize: function(val){ return Math.max(4, Math.sqrt(val[2])); }, data: data }] };
            return drawCommon(divId, option);
        }
    }

    // Parallel coordinates
    function drawParallel(divId, labels, seriesNames, rows, categoryName){
        if (!seriesNames || seriesNames.length === 0) return;
        const dims = seriesNames.map(function(name){ return { name: name }; });
        const data = rows.map(function(r){ return seriesNames.map(function(n){ return parseFloat(r[n])||0; }); });
        const option = { parallelAxis: seriesNames.map(function(name, idx){ return { dim: idx, name: name }; }), series: [{ type: 'parallel', data: data }], tooltip: {} };
        return drawCommon(divId, option);
    }

    // ThemeRiver: expects [time, value, name]
    function drawThemeRiver(divId, labels, seriesNames, rows, categoryName){
        if (!seriesNames || seriesNames.length === 0) return;
        // labels used as time dimension
        const data = [];
        for (let i=0;i<rows.length;i++){
            const t = labels[i] || String(i);
            seriesNames.forEach(function(name){
                const v = parseFloat(rows[i][name])||0;
                data.push([t, v, name]);
            });
        }
        const option = { tooltip:{}, series:[{ type:'themeRiver', data: data }] };
        return drawCommon(divId, option);
    }

    function quantile(arr,q){
        const pos = (arr.length - 1) * q;
        const base = Math.floor(pos);
        const rest = pos - base;
        if (arr[base+1] !== undefined) return arr[base] + rest * (arr[base+1] - arr[base]);
        return arr[base];
    }

    function drawBoxplot(divId, labels, seriesNames, rows, categoryName){
        if (!seriesNames || seriesNames.length === 0) return;
        const boxData = [];
        seriesNames.forEach(function(name){
            const vals = rows.map(r=>{ const n = parseFloat(r[name]); return isNaN(n)?null:n; }).filter(v=>v!==null).sort((a,b)=>a-b);
            if (vals.length===0){ boxData.push([0,0,0,0,0]); return; }
            const q1 = quantile(vals,0.25);
            const q2 = quantile(vals,0.5);
            const q3 = quantile(vals,0.75);
            boxData.push([vals[0], q1, q2, q3, vals[vals.length-1]]);
        });
        const option = { tooltip:{ formatter: function(param){ return param.name + ': ' + param.data; } }, xAxis:{ type:'category', data: seriesNames, name: categoryName || '' }, yAxis:{ type:'value' }, series:[{ name:'boxplot', type:'boxplot', data: boxData }] };
        return drawCommon(divId, option);
    }

    function drawCandlestick(divId, labels, seriesNames, rows, categoryName){
        if (!seriesNames || seriesNames.length < 4){
            const container = document.getElementById(divId);
            if (container) container.innerHTML = '<div class="alert alert-warning">Candlestick requiere 4 series (open, close, low, high)</div>';
            return;
        }
        const open = rows.map(r=>parseFloat(r[seriesNames[0]])||0);
        const close = rows.map(r=>parseFloat(r[seriesNames[1]])||0);
        const low = rows.map(r=>parseFloat(r[seriesNames[2]])||0);
        const high = rows.map(r=>parseFloat(r[seriesNames[3]])||0);
        const data = [];
        for (let i=0;i<rows.length;i++) data.push([open[i], close[i], low[i], high[i]]);
        const option = { xAxis:{ type:'category', data: labels, name: categoryName || '' }, yAxis:{ scale:true }, series:[{ type:'candlestick', data: data }] };
        return drawCommon(divId, option);
    }

    function drawSankey(divId, labels, seriesNames, rows){
        if (!seriesNames || seriesNames.length < 2) return;
        const nodes = [];
        const nodeSet = new Set();
        const linksMap = {};
        rows.forEach(function(r){
            const a = String(r[seriesNames[0]]||'');
            const b = String(r[seriesNames[1]]||'');
            const v = parseFloat(r[seriesNames[2]])||1;
            if (!nodeSet.has(a)){ nodeSet.add(a); nodes.push({ name: a }); }
            if (!nodeSet.has(b)){ nodeSet.add(b); nodes.push({ name: b }); }
            const key = a+'|'+b;
            linksMap[key] = (linksMap[key]||0) + v;
        });
        const links = Object.keys(linksMap).map(k=>{ const parts = k.split('|'); return { source: parts[0], target: parts[1], value: linksMap[k] }; });
        const option = { series:[{ type:'sankey', data: nodes, links: links, emphasis:{ focus:'adjacency' } }] };
        return drawCommon(divId, option);
    }

    function drawGraph(divId, labels, seriesNames, rows){
        const nodesMap = {};
        const linksMap = {};
        rows.forEach(function(r){
            const cat = String(r[labels[0]]||'');
            seriesNames.forEach(function(s){
                const nodeA = cat;
                const nodeB = s;
                const val = parseFloat(r[s])||0;
                nodesMap[nodeA] = true; nodesMap[nodeB] = true;
                const key = nodeA+'|'+nodeB;
                linksMap[key] = (linksMap[key]||0) + val;
            });
        });
        const nodeList = Object.keys(nodesMap).map(n=>({ name: n }));
        const links = Object.keys(linksMap).map(k=>{ const p = k.split('|'); return { source: p[0], target: p[1], value: linksMap[k] }; });
        const option = { series:[{ type:'graph', layout:'force', data: nodeList, links: links, roam: true }] };
        return drawCommon(divId, option);
    }

    function drawSunburst(divId, labels, seriesNames, rows){
        const children = seriesNames.map(function(name){ const sum = rows.reduce((acc,r)=>{ const n=parseFloat(r[name]); return acc+(isNaN(n)?0:n); },0); return { name: name, value: sum }; });
        const option = { series:[{ type:'sunburst', data: children, radius:[0,'90%'] }] };
        return drawCommon(divId, option);
    }

    // Expose API
    window.grafias = {
        drawBar: drawBar,
        drawLine: drawLine,
        drawPie: drawPie,
        drawDoughnut: drawDoughnut,
        drawScatter: drawScatter,
        drawRadar: drawRadar,
        drawFunnel: drawFunnel,
        drawGauge: drawGauge,
        drawHeatmap: drawHeatmap,
        drawTreemap: drawTreemap,
        drawArea: drawArea,
        drawAreaStacked: drawAreaStacked,
        drawBarStacked: drawBarStacked,
        drawBarHorizontal: drawBarHorizontal,
        drawBubbles: drawBubbles,
        drawParallel: drawParallel,
        drawThemeRiver: drawThemeRiver,
        drawBoxplot: drawBoxplot,
        drawCandlestick: drawCandlestick,
        drawSankey: drawSankey,
        drawGraph: drawGraph,
        drawSunburst: drawSunburst,
        destroyChart: destroyChart,
        resize: function(divId){ try{ const c = charts[divId]; if (c && typeof c.resize === 'function') c.resize(); }catch(e){} }
    };

})(window);
