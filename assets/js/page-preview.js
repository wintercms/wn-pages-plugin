/*
 * PagePreview plugin
 *
 * Data attributes:
 * - data-control="page-preview" - enables the plugin on an element
 *
 * JavaScript API:
 * $('div#someElement').pagePreview()
 */

+function ($) { "use strict";

    // PREVIEW CLASS DEFINITION
    // ============================

    var Base = $.wn.foundation.base,
        BaseProto = Base.prototype

    var PagePreview = function(element, options) {
        this.options   = options
        this.$el       = $(element)

        $.wn.foundation.controlUtils.markDisposable(element)
        Base.call(this)

        // Init
        this.init()
    }

    PagePreview.DEFAULTS = {
    }

    PagePreview.prototype = Object.create(BaseProto)
    PagePreview.prototype.constructor = PagePreview

    /**
     * Initialise the widget.
     */
    PagePreview.prototype.init = function() {
        this.$deviceSelector = $('.devices', this.$el)
        this.$iframe = $('iframe', this.$el)
        this.$iframeTabContainer = this.$iframe.parent('.control-tabs')

        // Set up events on various elements
        this.$deviceSelector.on('click', 'a', this.proxy(this.onDeviceClick))
    }

    /**
     * Disposes the element.
     */
    PagePreview.prototype.dispose = function () {
        this.$deviceSelector.off('click', 'a', this.proxy(this.onDeviceClick))

        this.$deviceSelector = null
        this.$iframe = null
        this.$iframeTabContainer = null
        this.$el = null
        BaseProto.dispose.call(this)
    }

    /**
     * Resize the preview based on the selected device
     * @param {Event} event
     */
    PagePreview.prototype.onDeviceClick = function (event) {
        const $clicked = $(event.currentTarget)

        // Remove active class from other options
        this.$deviceSelector.find('a').removeClass('active')

        // Add active class to the clicked option
        $clicked.addClass('active')

        // Remove all device classes from the preview
        this.$el.removeClass('preview-desktop preview-tablet preview-mobile')

        // Add the device class to the preview
        this.$el.addClass('preview-' + $clicked.data('preview-device').toLowerCase())
    }

    // PagePreview PLUGIN DEFINITION
    // ============================

    var old = $.fn.PagePreview

    $.fn.PagePreview = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('wn.PagePreview')
            var options = $.extend({}, PagePreview.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('wn.PagePreview', (data = new PagePreview(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.PagePreview.Constructor = PagePreview

    // PagePreview NO CONFLICT
    // =================

    $.fn.PagePreview.noConflict = function () {
        $.fn.PagePreview = old
        return this
    }

    // PagePreview DATA-API
    // ===============

    $(document).render(function() {
        $('[data-control="page-preview"]').PagePreview()
    })

}(window.jQuery);
