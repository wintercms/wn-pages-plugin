<div id="<?= $formField->getId() ?>">
    <?php
        // Find the alias of the current Form widget
        foreach ($this->widget as $alias => $widget) {
            if ($widget instanceof \Backend\Widgets\Form && !$widget->isNested) {
                $formAlias = $alias;
                break;
            }
        }
        if (empty($formAlias)) {
            return;
        }
        $prefix = \Winter\Pages\Classes\ObjectHelper::getTypePreviewSessionKeyPrefix(
            \Winter\Pages\Classes\ObjectHelper::resolveClassType(get_class($formModel))
        );
        $sessionKey = $prefix . $formAlias;
        if (!Session::has($sessionKey)) {
            Session::put($sessionKey, $formModel->toArray());
        }
        $url = "/winter.pages/preview/$formAlias";
    ?>
    <ul class="devices" id="<?= $formField->getId() ?>DeviceSelector">
        <li><a href="javascript:;" class="active" data-preview-device="Desktop" title="Desktop"><span class="icon-desktop"></span></a></li>
        <li><a href="javascript:;" data-preview-device="Tablet" title="Tablet"><span class="icon-tablet"></span></a></li>
        <li><a href="javascript:;" data-preview-device="Mobile" title="Mobile"><span class="icon-mobile"></span></a></li>
    </ul>
    <style>
        #<?= $formField->getId() ?> {
            margin: 0 auto;
            padding: 0 1em 1em 1em;
            background: #f2f2f2;
            border-radius: 5px;
        }
        #<?= $formField->getId() ?> iframe {
            background: #fff;
            box-shadow: 0px 0px 7px -3px rgb(0 0 0 / 50%);
            border-radius: 5px;
        }
        .devices {
            margin: 0;
            list-style: none;
            display: flex;
            justify-content: center;
            background: #f2f2f2;
            padding: 0.5em 1em;
        }
        .devices a {
            padding: 0.5em 0.75em;
            display: block;
            margin-right: 1em;
            border-radius: 3px;
            background: #ddd;
            transition: all .2s ease;
        }
        .devices a.active,
        .devices a:hover {
            font-weight: bold;
            background: #333;
            color: #fff;
        }
        .preview-desktop {
            width: 100%;
        }
        .preview-tablet {
            width: 768px;
        }
        .preview-mobile {
            width: 320px;
        }
    </style>
    <iframe
        width="100%"
        scrolling="no"
        class="preview-desktop"
        onload="this.style.height = this.contentWindow.document.documentElement.scrollHeight + 'px';"
        src="<?= Url::to($url); ?>"
    ></iframe>
    <script>
        var previewId = '#<?= $formField->getId(); ?>';
        var deviceSelectorId = '#<?= $formField->getId(); ?>DeviceSelector';
        $(deviceSelectorId).on('click', 'a', function () {
            var $this = $(this),
                $previewContainer = $(previewId),
                device = $this.data('preview-device');
            $(deviceSelectorId).find('a').removeClass('active');
            $this.addClass('active');
            $previewContainer.removeClass('preview-desktop preview-tablet preview-mobile');
            $previewContainer.addClass('preview-' + device.toLowerCase());
        });
        $('.control-tabs:has(' + previewId + ')').on('shown.bs.tab', function (e) {
            const tabBody = $($(e.target).attr('data-target'));
            if (!tabBody.length) {
                return
            }
            const iframe = tabBody.find(previewId + ' iframe');
            if (iframe.length) {
                iframe[0].style.height = iframe[0].contentWindow.document.documentElement.scrollHeight + 'px';
            }
        })
    </script>
</div>