(function($) {

    $.widget("mapbender.mbPrintClient",  {
        options: {
            style: {
                fillColor:     '#ffffff',
                fillOpacity:   0.5,
                strokeColor:   '#000000',
                strokeOpacity: 1.0,
                strokeWidth:    2
            }
        },
        map: null,
        layer: null,
        control: null,
        feature: null,
        lastScale: null,
        lastRotation: null,
        width: null,
        height: null,
        rotateValue: 0,
        formElement: null,

        _create: function() {

            var widget = this;

            if(!Mapbender.checkTarget("mbPrintClient", widget.options.target)){
                return;
            }
            Mapbender.elementRegistry.onElementReady(widget.options.target, $.proxy(widget._setup, widget));
        },

        _setup: function(){

            var widget = this;

            widget.elementUrl = Mapbender.configuration.application.urls.element + '/' + widget.element.attr('id') + '/';
            widget.map = $('#' + widget.options.target).data('mapbenderMbMap');
            
            $('select[name="scale_select"]', widget.element)
                .on('change', $.proxy(widget._updateGeometry, widget));
            $('input[name="rotation"]', widget.element)
                .on('keyup', $.proxy(widget._updateGeometry, widget));
            $('select[name="template"]', widget.element)
                .on('change', $.proxy(widget._getTemplateSize, widget));
        
            if (widget.options.type === 'element') {
                $(widget.element).show();
                $(widget.element).on('click', '#printToggle', function(){
                    var active = $(widget).attr('active');
                    if(active === 'true') {// deactivate
                        $(widget).attr('active','false').removeClass('active');
                        $(widget).val(Mapbender.trans('mb.core.printclient.btn.activate'));
                        widget._updateElements(false);
                        $('.printSubmit', widget.element).addClass('hidden');
                    }else{ // activate
                        $(widget).attr('active','true').addClass('active');
                        $(widget).val(Mapbender.trans('mb.core.printclient.btn.deactivate'));
                        widget._getTemplateSize();
                        widget._updateElements(true);
                        widget._setScale();
                        $('.printSubmit', widget.element).removeClass('hidden');
                    }
                });
                $('.printSubmit', widget.element).on('click', $.proxy(widget._print, widget));
            }

            widget._trigger('ready');
            widget._ready();
        },

        defaultAction: function(callback) {

            var widget = this;

            widget.open(callback);
        },

        /**
         * Open feature popup print dialog
         *
         * @param featureTypeName feature type name
         * @param featureId feature id
         */
        openFeatureDialog: function(featureTypeName, featureId) {

            var widget = this;

            widget.query( 'printFeatureDialog',{featureId:featureId,featureType:featureTypeName}, 'GET').success(function(response) {

                // Response includes a serverside build child of formgenerator - it is incomplete!
                // console.log(response);

                var formElement = $("<div/>").generateElements({
                    type:     "form",
                    cssClass: "printfeatureform",
                    children: _.toArray(response.nameFields)
                });

                widget.formElement = formElement;

                formElement.popupDialog({
                    title:    "Druck",
                    cssClass: "printfeaturedialog",
                    buttons:     [{
                        text:  Mapbender.trans('mb.core.printclient.popup.btn.ok'),
                        cssClass: 'button right',
                        click: function(e) {
                            var form = $(e.currentTarget).closest(".ui-dialog").find("form");
                            console.log(form.formData());
                            widget._print();
                        }
                    },{
                        text:  Mapbender.trans('mb.core.printclient.popup.btn.cancel'),
                        cssClass: 'button buttonCancel critical right',
                        click: function(e) {
                            $('.printfeaturedialog').parent().find('.close').trigger('click');
                            widget._updateElements(false);
                        }
                    }]
                });

                widget.formElement.find("[name='rotation']").on('input',function(){
                    widget._updateGeometry.call(widget,false);
                });

                widget.formElement.find("[name='templates']").on('change',function(){
                    widget._getTemplateSize();
                    widget._updateElements(true);
                    widget._setScale();
                });

                widget._getTemplateSize();
                widget._updateElements(true);
                widget._setScale();

            });
        },

        open: function(callback){

            var widget = this;

            widget.callback = callback ? callback : null;

            var me = $(widget.element);
            if (widget.options.type === 'dialog') {
                if(!widget.popup || !widget.popup.$element){
                    widget.popup = new Mapbender.Popup2({
                            title: widget.element.attr('title'),
                            draggable: true,
                            header: true,
                            modal: false,
                            closeButton: false,
                            closeOnESC: false,
                            content: widget.element,
                            width: 400,
                            height: 490,
                            cssClass: 'customPrintDialog',
                            buttons: {
                                    'cancel': {
                                        label: Mapbender.trans('mb.core.printclient.popup.btn.cancel'),
                                        cssClass: 'button buttonCancel critical right',
                                        callback: function(){
                                            widget.close();
                                        }
                                    },
                                    'ok': {
                                        label: Mapbender.trans('mb.core.printclient.popup.btn.ok'),
                                        cssClass: 'button right',
                                        callback: function(){
                                            widget._print();
                                        }
                                    }
                            }
                        });
                    widget.popup.$element.on('close', $.proxy(widget.close, widget));
                }else{
                     return;
                }
                me.show();
                widget._getTemplateSize();
                widget._updateElements(true);
                widget._setScale();
            }
        },

        close: function() {

            var widget = this;

            if(widget.popup){
                widget.element.hide().appendTo($('body'));
                widget._updateElements(false);
                if(widget.popup.$element){
                    widget.popup.destroy();
                }
                widget.popup = null;
            }
            widget.callback ? widget.callback.call() : widget.callback = null;
        },
        
        _setScale: function() {

            var widget = this;

            if (widget.formElement) {
                var select = widget.formElement.find("select[name='scale_select']");
            } else {
                var select = $(widget.element).find("select[name='scale_select']");
            }

            var styledSelect = select.parent().find(".dropdownValue.iconDown");
            var scales = widget.options.scales;
            var currentScale = Math.round(widget.map.map.olMap.getScale());
            var selectValue;

            $.each(scales, function(idx, scale) {
                if(scale == currentScale){
                    selectValue = scales[idx];
                    return false;
                }
                if(scale > currentScale){
                    selectValue = scales[idx-1];
                    return false;
                }
            });
            if(currentScale <= scales[0]){
                selectValue = scales[0];
            }
            if(currentScale > scales[scales.length-1]){
                selectValue = scales[scales.length-1];
            }

            select.val(selectValue);
            styledSelect.html('1:'+selectValue);

            widget._updateGeometry(true);
        },

        _updateGeometry: function(reset) {

            var widget = this;

            if (widget.formElement) {
                var rotationField = widget.formElement.find("input[name='rotation']");
            } else {
                var rotationField = $(widget.element).find("input[name='rotation']");
            }


            var width = widget.width,
                height = widget.height,
                scale = widget._getPrintScale()

            // remove all not numbers from input
            rotationField.val(rotationField.val().replace(/[^\d]+/,''));

            if (rotationField.val() === '' && widget.rotateValue > '0'){
                rotationField.val('0');
            }
            var rotation = rotationField.val();
            widget.rotateValue = rotation;

            if(!(!isNaN(parseFloat(scale)) && isFinite(scale) && scale > 0)) {
                if(null !== widget.lastScale) {
                //$('input[name="scale_text"]').val(widget.lastScale).change();
                }
                return;
            }
            scale = parseInt(scale);

            if(!(!isNaN(parseFloat(rotation)) && isFinite(rotation))) {
                if(null !== widget.lastRotation) {
                    rotationField.val(widget.lastRotation).change();
                }
            }
            rotation= parseInt(-rotation);

            widget.lastScale = scale;

            var world_size = {
                x: width * scale / 100,
                y: height * scale / 100
            };

            var center = (reset === true || !widget.feature) ?
            widget.map.map.olMap.getCenter() :
            widget.feature.geometry.getBounds().getCenterLonLat();

            if(widget.feature) {
                widget.layer.removeAllFeatures();
                widget.feature = null;
            }

            widget.feature = new OpenLayers.Feature.Vector(new OpenLayers.Bounds(
                center.lon - 0.5 * world_size.x,
                center.lat - 0.5 * world_size.y,
                center.lon + 0.5 * world_size.x,
                center.lat + 0.5 * world_size.y).toGeometry(), {});
            widget.feature.world_size = world_size;

            if(widget.map.map.olMap.units === 'degrees' || widget.map.map.olMap.units === 'dd') {
                var centroid = widget.feature.geometry.getCentroid();
                var centroid_lonlat = new OpenLayers.LonLat(centroid.x,centroid.y);
                var centroid_pixel = widget.map.map.olMap.getViewPortPxFromLonLat(centroid_lonlat);
                var centroid_geodesSize = widget.map.map.olMap.getGeodesicPixelSize(centroid_pixel);

                var geodes_diag = Math.sqrt(centroid_geodesSize.w*centroid_geodesSize.w + centroid_geodesSize.h*centroid_geodesSize.h) / Math.sqrt(2) * 100000;

                var geodes_width = width * scale / (geodes_diag);
                var geodes_height = height * scale / (geodes_diag);

                var ll_pixel_x = centroid_pixel.x - (geodes_width) / 2;
                var ll_pixel_y = centroid_pixel.y + (geodes_height) / 2;
                var ur_pixel_x = centroid_pixel.x + (geodes_width) / 2;
                var ur_pixel_y = centroid_pixel.y - (geodes_height) /2 ;
                var ll_pixel = new OpenLayers.Pixel(ll_pixel_x, ll_pixel_y);
                var ur_pixel = new OpenLayers.Pixel(ur_pixel_x, ur_pixel_y);
                var ll_lonlat = widget.map.map.olMap.getLonLatFromPixel(ll_pixel);
                var ur_lonlat = widget.map.map.olMap.getLonLatFromPixel(ur_pixel);

                widget.feature = new OpenLayers.Feature.Vector(new OpenLayers.Bounds(
                    ll_lonlat.lon,
                    ur_lonlat.lat,
                    ur_lonlat.lon,
                    ll_lonlat.lat).toGeometry(), {});
                widget.feature.world_size = {
                    x: ur_lonlat.lon - ll_lonlat.lon,
                    y: ur_lonlat.lat - ll_lonlat.lat
                };
            }

            widget.feature.geometry.rotate(rotation, new OpenLayers.Geometry.Point(center.lon, center.lat));
            widget.layer.addFeatures(widget.feature);
            widget.layer.redraw();
        },

        _updateElements: function(active) {

            var widget = this;

            if(true === active){
                if(null === widget.layer) {
                    widget.layer = new OpenLayers.Layer.Vector("Print", {
                        styleMap: new OpenLayers.StyleMap({
                            'default': $.extend({}, OpenLayers.Feature.Vector.style['default'], widget.options.style)
                        })
                    });
                }
                if(null === widget.control) {
                    widget.control = new OpenLayers.Control.DragFeature(widget.layer,  {
                        onComplete: function() {
                            widget._updateGeometry(false);
                        }
                    });
                }
                widget.map.map.olMap.addLayer(widget.layer);
                widget.map.map.olMap.addControl(widget.control);
                widget.control.activate();

                widget._updateGeometry(true);
            }else{
                if(null !== widget.control) {
                    widget.control.deactivate();
                    widget.map.map.olMap.removeControl(widget.control);
                }
                if(null !== widget.layer) {
                    widget.map.map.olMap.removeLayer(widget.layer);
                }
            }
        },

        _getPrintScale: function() {

            var widget = this;

            if (widget.formElement) {
                return widget.formElement.find("select[name='scale_select']").val();
            } else {
                return $(widget.element).find("select[name='scale_select']").val();
            }

        },

        _getPrintExtent: function() {

            var widget = this;

            var data = {
                extent: {},
                center: {}
            };

            data.extent.width = widget.feature.world_size.x;
            data.extent.height = widget.feature.world_size.y;
            data.center.x = widget.feature.geometry.getBounds().getCenterLonLat().lon;
            data.center.y = widget.feature.geometry.getBounds().getCenterLonLat().lat;

            return data;
        },

        _print: function() {

            var widget = this;

            if (widget.formElement) {
                var form = $('form', widget.formElement);
            } else {
                var form = $('form#formats', widget.element);
            }

            var extent = widget._getPrintExtent();

            // Felder für extent, center und layer dynamisch einbauen
            var fields = $();

            $.merge(fields, $('<input />', {
                type: 'hidden',
                name: 'extent[width]',
                value: extent.extent.width
            }));

            $.merge(fields, $('<input />', {
                type: 'hidden',
                name: 'extent[height]',
                value: extent.extent.height
            }));

            $.merge(fields, $('<input />', {
                type: 'hidden',
                name: 'center[x]',
                value: extent.center.x
            }));

            $.merge(fields, $('<input />', {
                type: 'hidden',
                name: 'center[y]',
                value: extent.center.y
            }));

            // extent feature
            var feature_coords = new Array();
            var feature_comp = widget.feature.geometry.components[0].components;
            for(var i = 0; i < feature_comp.length-1; i++) {
                feature_coords[i] = new Object();
                feature_coords[i]['x'] = feature_comp[i].x;
                feature_coords[i]['y'] = feature_comp[i].y;
            }

            $.merge(fields, $('<input />', {
                type: 'hidden',
                name: 'extent_feature',
                value: JSON.stringify(feature_coords)
            }));

            // wms layer
            var sources = widget.map.getSourceTree(), lyrCount = 0;

            function _getLegends(layer) {
                var legend = null;
                if (layer.options.legend && layer.options.legend.url && layer.options.treeOptions.selected == true) {
                    legend = {};
                    legend[layer.options.title] = layer.options.legend.url;
                }
                if (layer.children) {
                    for (var i = 0; i < layer.children.length; i++) {
                        var help = _getLegends(layer.children[i]);
                        if (help) {
                            legend = legend ? legend : {};
                            for (key in help) {
                                legend[key] = help[key];
                            }
                        }
                    }
                }
                return legend;
            } 
            var legends = [];

            for (var i = 0; i < sources.length; i++) {
                var layer = widget.map.map.layersList[sources[i].mqlid],
                        type = layer.olLayer.CLASS_NAME;

                if (0 !== type.indexOf('OpenLayers.Layer.')) {
                    continue;
                }

                if (Mapbender.source[sources[i].type] && typeof Mapbender.source[sources[i].type].getPrintConfig === 'function') {
                    var source = sources[i],
                            scale = widget._getPrintScale(),
                            toChangeOpts = {options: {children: {}}, sourceIdx: {mqlid: source.mqlid}};
                    var visLayers = Mapbender.source[source.type].changeOptions(source, scale, toChangeOpts);
                    if (visLayers.layers.length > 0) {
                        var prevLayers = layer.olLayer.params.LAYERS;
                        layer.olLayer.params.LAYERS = visLayers.layers;

                        var opacity = sources[i].configuration.options.opacity;
                        var lyrConf = Mapbender.source[sources[i].type].getPrintConfig(layer.olLayer, widget.map.map.olMap.getExtent(), sources[i].configuration.options.proxy);
                        lyrConf.opacity = opacity;

                        $.merge(fields, $('<input />', {
                            type: 'hidden',
                            name: 'layers[' + lyrCount + ']',
                            value: JSON.stringify(lyrConf),
                            weight: widget.map.map.olMap.getLayerIndex(layer.olLayer)
                        }));
                        layer.olLayer.params.LAYERS = prevLayers;
                        lyrCount++;

                        if (sources[i].type === 'wms') {
                            var ll = _getLegends(sources[i].configuration.children[0]);
                            if (ll) {
                                legends.push(ll);
                            }
                        }
                    }
                }
            }

            //legend
            if($('input[name="printLegend"]',form).prop('checked')){
                $.merge(fields, $('<input />', {
                    type: 'hidden',
                    name: 'legends',
                    value: JSON.stringify(legends)
                }));
            }
            
            // Iterating over all vector layers, not only the ones known to MapQuery
            var geojsonFormat = new OpenLayers.Format.GeoJSON();
            for(var i = 0; i < widget.map.map.olMap.layers.length; i++) {
                var layer = widget.map.map.olMap.layers[i];
                if('OpenLayers.Layer.Vector' !== layer.CLASS_NAME || widget.layer === layer) {
                    continue;
                }

                var geometries = [];
                for(var idx = 0; idx < layer.features.length; idx++) {
                    var feature = layer.features[idx];
                    if (!feature.onScreen(true)) continue
                    
                    if(widget.feature.geometry.intersects(feature.geometry)){
                        var geometry = geojsonFormat.extract.geometry.apply(geojsonFormat, [feature.geometry]);

                        if(feature.style !== null){
                            geometry.style = feature.style;
                        }else{
                            geometry.style = layer.styleMap.createSymbolizer(feature,feature.renderIntent);
                        }
                        // only visible features
                        if(geometry.style.fillOpacity > 0 && geometry.style.strokeOpacity > 0){                            
                            geometries.push(geometry);
                        } else if (geometry.style.label !== undefined){
                            geometries.push(geometry);
                        }
                    }
                }

                var lyrConf = {
                    type: 'GeoJSON+Style',
                    opacity: 1,
                    geometries: geometries
                };

                $.merge(fields, $('<input />', {
                    type: 'hidden',
                    name: 'layers[' + (lyrCount + i) + ']',
                    value: JSON.stringify(lyrConf),
                    weight: widget.map.map.olMap.getLayerIndex(layer)
                }));
            }

            // overview map
            var ovMap = widget.map.map.olMap.getControlsByClass('OpenLayers.Control.OverviewMap')[0],
            count = 0;
            if (undefined !== ovMap){
                for(var i = 0; i < ovMap.layers.length; i++) {
                    var url = ovMap.layers[i].getURL(ovMap.map.getExtent());
                    var extent = ovMap.map.getExtent();
                    var mwidth = extent.getWidth();
                    var size = ovMap.size;
                    var width = size.w;
                    var res = mwidth / width;
                    var scale = Math.round(OpenLayers.Util.getScaleFromResolution(res,'m'));

                    var overview = {};
                    overview.url = url;
                    overview.scale = scale;

                    $.merge(fields, $('<input />', {
                        type: 'hidden',
                        name: 'overview[' + count + ']',
                        value: JSON.stringify(overview)
                    }));
                    count++;
                }
            }

            $('div#layers', form).empty();
            fields.appendTo(form.find('div#layers'));

            // Post in neuen Tab (action bei form anpassen)
            var url =  widget.elementUrl + 'print';


            form.get(0).setAttribute('action', url);
            form.attr('target', '_blank');
            form.attr('method', 'post');

            if (lyrCount === 0){
                Mapbender.info(Mapbender.trans('mb.core.printclient.info.noactivelayer'));
            }else{
                console.log('fdsjg');
                // we click a hidden submit button to check the required fields
                form.find('input[type="submit"]').click();
            }

            if(widget.options.autoClose){
                widget.popup.close();
            }
        },

        _getTemplateSize: function() {

            var widget = this;

            var template = $('select[name="template"]', widget.element).val();

            widget.query("getTemplateSize",{template: template}).success(function(data){
                widget.width = data.width;
                widget.height = data.height;
                widget._updateGeometry();
            });
        },

        /**
         * Ajax query to element
         *
         * @param uri
         * @param request
         * @param type
         * @returns {*}
         */
        query: function(uri,request, type) {

            var widget = this;

            return $.ajax({
                url:      widget.elementUrl + uri,
                type:     type ? type : 'GET',
                data:     request,
                dataType: "json"
            });
        },

        /**
         *
         */
        ready: function(callback) {

            var widget = this;

            if(widget.readyState === true) {
                callback();
            } else {
                widget.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function() {

            var widget = this;

            for(callback in widget.readyCallbacks) {
                callback();
                delete(widget.readyCallbacks[callback]);
            }
            widget.readyState = true;
        }
    });

})(jQuery);
