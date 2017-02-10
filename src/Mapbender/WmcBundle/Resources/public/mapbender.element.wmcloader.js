(function($){

    /**
     * WMC Loader Element
     *
     * @author Paul Schmidt
     * @author Andriy Oblivantsev
     */
    $.widget("mapbender.mbWmcLoader", {

        options:    {},
        elementUrl: null,
        popup:      null,

        _create: function() {
            var widget = this;
            var options = widget.options;
            var target = options.target;

            if(!Mapbender.checkTarget("mbWmcLoader", target)) {
                return;
            }

            Mapbender.elementRegistry.onElementReady(target, $.proxy(widget._setup, widget));
        },

        /**
         * Is the element an component?
         *
         * @param name component name
         * @returns bool
         */
        isComponent: function(name) {
            var widget = this;
            var options = widget.options;

            return _.contains(options.components, "wmcidloader");
        },

        /**
         * Is the id loader component?
         * @returns bool
         */
        isIdLoaderComponent: function() {
            return this.isComponent("wmcidloader");
        },

        /**
         * Is the element list loader component?
         * @returns bool
         */
        isListLoaderComponent: function() {
            return this.isComponent("wmclistloader");
        },

        /**
         * Is the element XML loader component?
         * @returns bool
         */
        isXmlLoaderComponent: function() {
            return this.isComponent("wmclistloader");
        },

        /**
         * Is the element URL Loader component?
         * @returns bool
         */
        isUrlLoaderComponent: function() {
            return this.isComponent("wmclistloader");
        },

        /**
         * Initializes the wmc handler
         */
        _setup: function() {
            var widget = this;
            var options = widget.options;
            var element = widget.element;
            var map = $('#' + options.target).data('mapbenderMbMap');
            var isLoadDefined = typeof loader !== 'undefined';
            var elementUrl = widget.elementUrl = Mapbender.configuration.application.urls.element + '/' + element.attr('id') + '/';

            if(isLoadDefined){
                var wmcHandlier = new Mapbender.WmcHandler(map);
                var loader = options.load;

                if(loader.wmcid) {
                    wmcHandlier.loadFromId(elementUrl + 'load', loader.wmcid);
                } else if(loader.wmcurl) {
                    wmcHandlier.loadFromUrl(widget.elementUrl + 'wmcfromurl', loader.wmcurl);
                }
            }

            widget._trigger('ready');
            widget._ready();
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.open(callback);
        },

        /**
         * closes a dialog
         */
        close: function() {
            var widget = this;

            if(widget.popup) {
                var element = widget.element;
                element.hide().appendTo($('body'));
                if(widget.popup.$element) {
                    widget.popup.destroy();
                }
                widget.popup = null;
            }

            widget.callback ? widget.callback.call() : widget.callback = null;
        },

        /**
         * Render WMC load table
         *
         * @param list
         */
        renderWmcTableView: function(list) {
            var widget = this;
            var table = $("<div class='wmc-list'/>").resultTable({
                paging:    false,
                info:      false,
                width:     "100%", // autoWidth: true,
                columns:   [{
                    title: "ID",
                    data:  function(wmc) {
                        return wmc.id;
                    }
                }, {
                    title: "Title",
                    data:  function(wmc) {
                        return wmc.state.title;
                    }
                }, {
                    title: "Description",
                    data:  function(wmc) {
                        return wmc.abstract;
                    }
                }],
                buttons:   [{
                    title:     Mapbender.trans("mb.wmc.wmcloader.view_wmc"),
                    className: 'iconView',
                    onClick:   function(wmc, ui) {
                        widget.loadFromId(wmc.id);
                    }
                }],
                oLanguage: {
                    sEmptyTable: Mapbender.trans("mb.wmc.wmcloader.no_wmc"),
                    sInfo:       "_START_ / _END_ (_TOTAL_)"
                }
            });
            // var tableWidget = table.data('visUiJsResultTable');
            var tableApi = table.resultTable('getApi');

            tableApi.clear();
            tableApi.rows.add(list);
            tableApi.draw();

            return table;
        },

        /**
         *
         * @param uri
         * @param parameters
         * @returns {*}
         */
        query: function(uri, parameters, options) {
            var widget = this;
            return $.ajax(_.extend({
                url:      widget.elementUrl + uri,
                dataType: "json",
                data:     parameters || {}
            }, (options ? options : {})));
        },

        /**
         * opens a dialog
         */
        open: function(callback){
            var widget = this;
            var element = widget.element;
            var hasPopUp = widget.popup && widget.popup.$element;

            widget.callback = callback ? callback : null;

            if(hasPopUp) {
                widget.popup.focus();
                return;
            }

            widget.query('jsonList').done(function(r) {
                var wmcList = r.list;
                var container = $("<div/>");
                var listTable = widget.renderWmcTableView(wmcList);

                if(widget.isXmlLoaderComponent()) {
                    // widget.elementUrl
                    var title = Mapbender.trans('mb.wmc.wmcloader.load_xml_wmc');
                    var loadXmlButton = $('<a href="' + widget.elementUrl + 'loadform' + '" class="iconAdd iconSmall loadWmcXml" title="' + title + '"/>');

                    loadXmlButton.on("click", function(e) {
                        widget._loadForm(e);
                        return false;
                    });

                    container.append(loadXmlButton);
                }

                container.append(listTable);

                var popup = widget.popup = new Mapbender.Popup2({
                    title:          element.attr('title'),
                    draggable:      true,
                    resizable:      true,
                    modal:          false,
                    closeButton:    false,
                    closeOnESC:     false,
                    cssClass:       'mb-wmcEditor',
                    content:        container,
                    destroyOnClose: true,
                    height:         400,
                    width:          480,
                    buttons:        {
                        'cancel': {
                            label:    Mapbender.trans("mb.wmc.element.wmcloader.popup.btn.cancel"),
                            cssClass: 'button buttonCancel critical right',
                            callback: function() {
                                widget.close();
                            }
                        },
                        'ok':     {
                            label:    Mapbender.trans("mb.wmc.element.wmcloader.popup.btn.ok"),
                            cssClass: 'button buttonYes right',
                            callback: function() {
                                $('#wmc-load input[type="submit"]', widget.popup.$element).click();
                                return false;
                            }
                        },
                        'back':   {
                            label:    Mapbender.trans("mb.wmc.element.wmcloader.popup.btn.back"),
                            cssClass: 'button left buttonBack',
                            callback: function() {
                                $(".popupSubContent").remove();
                                $(".popupSubTitle").text("");
                                $(".popup", widget.popup.$element).find(".buttonYes, .buttonBack").hide();
                                $(".popupContent", widget.popup.$element).show();
                            }
                        }
                    }
                });

                popup.$element.on('close', $.proxy(widget.close, widget));
                $(".popup", popup.$element).find(".buttonYes, .buttonBack").hide();
            });


        },

        /**
         * Loads a wmc list
         */
        _loadList: function(){
            var self = this;
            $.ajax({
                url: self.elementUrl + "list",
                type: "POST",
                success: function(data){
                    $("#popupContent").html(data);
                    // $(".loadWmcId").on("click", $.proxy(self._loadFromId, self));
                    $(".loadWmcXml").on("click", $.proxy(self._loadForm, self));
                }
            });
        },

        /**
         * Loads a form to load a wmc
         */
        _loadForm: function(e) {
            var widget = this;
            var hasPopUp = widget.popup && widget.popup.$element;
            var url = $(e.target).attr("href");

            if(!hasPopUp || !url) {
                return false;
            }

            $.ajax({
                url:      url,
                type:     "GET",
                complete: function(data) {
                    if(typeof data !== 'undefined') {
                        var popup = widget.popup;
                        var popupElement = popup.$element;
                        var pop = $(".popup", popupElement);
                        var popupContent = $(".popupContent", popupElement);
                        var contentWrapper = pop.find(".contentWrapper");

                        if(contentWrapper.get(0) == undefined) {
                            popupContent.wrap('<div class="contentWrapper"/>');
                            contentWrapper = pop.find(".contentWrapper");
                        }
                        popupContent.hide();
                        var subContent = contentWrapper.find(".popupSubContent");

                        if(subContent.get(0) == undefined) {
                            contentWrapper.append('<div class="popupSubContent"/>');
                            subContent = contentWrapper.find('.popupSubContent');
                        }
                        subContent.html(data.responseText);

                        var subTitle = subContent.find("form").attr('title');
                        $(".popupSubTitle").text(" - " + subTitle);
                        $(".popup", popupElement).find(".buttonYes, .buttonBack").show();
                        widget._ajaxForm();
                    }
                }
            });

            return false;
        },
        /**
         * ajaxform for load a wmc
         */
        _ajaxForm: function(){
            if(this.popup && this.popup.$element){
                var self = this;
                $('form#wmc-load', this.popup.$element).ajaxForm({
                    url: self.elementUrl + 'loadxml',
                    type: 'POST',
                    beforeSerialize: function(e){
                        var map = $('#' + self.options.target).data('mapbenderMbMap')
                        var state = map.getMapState();
                        $('input#wmc_state_json', self.popup.$element).val(JSON.stringify(state));
                    },
                    contentType: 'json',
                    context: self,
                    success: function(response){
                        response = $.parseJSON(response.replace(/<[^><]*>/gi, ''));
                        if(response.success){
                            $(".popupSubContent", self.popup.$element).remove();
                            $(".popupSubTitle", self.popup.$element).text("");
                            $(".buttonYes, .buttonBack", self.popup.$element).hide();
                            $(".popupContent", self.popup.$element).show();
                            for(wmc_id in response.success){
                                var map = $('#' + this.options.target).data('mapbenderMbMap');
                                var wmcHandlier = new Mapbender.WmcHandler(map, {
                                    keepExtent: self.options.keepExtent,
                                    keepSources: self.options.keepSources});
                                wmcHandlier.addToMap(wmc_id, response.success[wmc_id]);
                            }
                        }else if(response.error){
                            $(".popupSubContent", self.popup.$element).html(Mapbender.trans(response.error));
                            $(".popupSubTitle", self.popup.$element).text("ERROR");
                        }
                    },
                    error: function(response){
                        Mapbender.error(response);
                    }
                });
            }
        },

        /**
         * Loads a wmc from id (event handler)
         */
        _loadFromId: function(e){
            var wmc_id = $(e.target).parents('tr:first').attr('data-id');
            this.loadFromId(wmc_id);
        },

        /**
         * Loads a wmc from id
         */
        loadFromId: function(wmc_id){
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            var wmcHandlier = new Mapbender.WmcHandler(map, {
                keepExtent: this.options.keepExtent,
                keepSources: this.options.keepSources});
            wmcHandlier.loadFromId(this.elementUrl + 'load', wmc_id);
        },

        /**
         * Loads a wmc from id
         */
        removeFromMap: function(){
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            var wmcHandlier = new Mapbender.WmcHandler(map, {
                keepExtent: this.options.keepExtent,
                keepSources: this.options.keepSources});
            wmcHandlier.removeFromMap();
        },

        /**
         * Loads a wmc from id
         */
        wmcAsXml: function(){
            var self = this;
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            var st = JSON.stringify(map.getMapState());
            var form = $('<form method="POST" action="' + (self.elementUrl + 'wmcasxml') + '" target="_BLANK" />');
            $('<input></input>').attr('type', 'hidden').attr('name', 'state').val(st).appendTo(form);
            form.appendTo($('body'));
            form.submit();
            form.remove();
        },

        /**
         *
         */
        ready: function(callback){
            if(this.readyState === true){
                callback();
            }else{
                this.readyCallbacks.push(callback);
            }
        },

        /**
         *
         */
        _ready: function(){
            for(callback in this.readyCallbacks){
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _destroy: $.noop
    });

})(jQuery);