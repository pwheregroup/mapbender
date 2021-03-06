(function ($) {
    'use strict';

    $.widget("mapbender.mbBaseSourceSwitcher", $.mapbender.mbBaseElement, {
        options: {},
        loadStarted: [],
        contextAddStart: false,

        _create: function () {
            if (!Mapbender.checkTarget("mbBaseSourceSwitcher", this.options.target)) {
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },

        _setup: function () {
            $('.basesourcesetswitch:not(.basesourcegroup)', this.element).on('click', $.proxy(this._toggleMapset, this));

            $('.basesourcegroup', this.element).on('click', function (e) {
                var bsswtch = $('.basesourcesubswitcher', $(e.currentTarget));

                $('.basesourcesubswitcher', $(this.element)).addClass('hidden');

                if (bsswtch.hasClass('hidden')) {
                    bsswtch.removeClass('hidden');
                } else {
                    bsswtch.addClass('hidden');
                }
            });

            this._hideSources();
            this._showActive();

            this.element.find('.basesourcesetswitch:first').click();

            $(document).on('mbmapcontextaddstart', $.proxy(this._onContextAddStart, this));
            $(document).on('mbmapsourceloadstart', $.proxy(this._onSourceLoadStart, this));
            $(document).on('mbmapsourceloadend', $.proxy(this._removeSourceFromLoad, this));
            $(document).on('mbmapsourceloaderror', $.proxy(this._removeSourceFromLoad, this));
        },

        _hideSources: function () {
            var sourceVisibility = false;
            this._changeSource('.basesourcesetswitch', sourceVisibility);
        },

        _showActive: function () {
            var sourceVisibility = true;
            this._changeSource('.basesourcesetswitch[data-state="active"]', sourceVisibility);
        },

        _changeSource: function (selector, visibility) {
            var $me = $(this.element),
                $map = $('#' + this.options.target).data('mapbenderMbMap'),
                model = $map.getModel(),
                source_list;

            $me.find(selector).each(function (idx, elm) {
                if (false === visibility) {
                    $(elm).attr("data-state", "");
                }

                var sourcesIds = $(elm).attr("data-sourceset").split(",");

                sourcesIds.map(function (sourcesId) {
                    if (sourcesId.length === 0) {
                        return;
                    }

                    source_list = model.findSource({origId: sourcesId});

                    if (source_list.length === 0) {
                        Mapbender.error(Mapbender.trans("mb.core.basesourceswitcher.error.sourcenotavailable")
                            .replace('%id%', sourcesId), {'id': sourcesId});
                    }

                    source_list.map(function (source) {
                        model.changeSource({
                            change: {
                                sourceIdx: {id: source.id},
                                options: {
                                    configuration: {
                                        options: {visibility: visibility}
                                    },
                                    type: 'selected'
                                }
                            }
                        });
                    });
                });
            });
        },

        _toggleMapset: function (event) {
            var $me = $(this.element),
                $currentTarget = $(event.currentTarget);

            this._hideSources();

            $me.find('.basesourcesetswitch,.basesourcegroup').not($currentTarget).attr('data-state', '');

            $currentTarget.attr('data-state', 'active');
            $currentTarget.parents('.basesourcegroup:first').attr('data-state', 'active');
            $currentTarget.parents('.basesourcesubswitcher:first').addClass('hidden');

            if ($currentTarget.hasClass('notgroup')) {
                $('.basesourcesubswitcher', $me).addClass('hidden');
            }

            this._showActive();
        },

        _onSourceLoadStart: function (event, option) {
            var position = this.loadStarted.indexOf(option.source.id);

            if (this.contextAddStart && option.source && position < 0) {
                this.loadStarted.push(option.source.id);
            }
        },

        _removeSourceFromLoad : function (event, option) {
            var position = this.loadStarted.indexOf(option.source.id);

            if (option.source && position >= 0) {
                this.loadStarted.splice(position, 1);
                this._checkReset();
            }
        },

        _onContextAddStart: function () {
            this.contextAddStart = true;

            $(document).on('mbmapcontextaddend', $.proxy(this._onContextAddEnd, this));
            $(document).on('mbmapsourceloadstart', $.proxy(this._onSourceLoadStart, this));
            $(document).on('mbmapsourceloadend', $.proxy(this._onSourceLoadEnd, this));
            $(document).on('mbmapsourceloaderror', $.proxy(this._onSourceLoadError, this));
        },

        _onContextAddEnd: function () {
            this._checkReset();
        },

        _checkReset: function () {
            if (this.loadStarted.length > 0) {
                return;
            }

            this.contextAddStart = false;

            $(document).off('mbmapcontextaddend', $.proxy(this._onContextAddEnd, this));
            $(document).off('mbmapsourceloadstart', $.proxy(this._onSourceLoadStart, this));
            $(document).off('mbmapsourceloadend', $.proxy(this._onSourceLoadEnd, this));
            $(document).off('mbmapsourceloaderror', $.proxy(this._onSourceLoadError, this));

            $('.basesourcesetswitch[data-state="active"]:not(.basesourcegroup)', this.element).click();
        }
    });

})(jQuery);