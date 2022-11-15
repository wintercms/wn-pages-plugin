<div data-control="page-preview">
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

        $object = (Request::ajax() && Request::has('objectType'))
            ? $this->getObjectFromRequest()
            : $formModel;

        Session::put($sessionKey, $object->toArray());
        $url = "/winter.pages/preview/$formAlias";
        $this->addJs('/plugins/winter/pages/assets/js/page-preview.js', 'Winter.Pages');
        $this->addCss('/plugins/winter/pages/assets/css/page-preview.css', 'Winter.Pages');
    ?>
    <ul class="devices">
        <li><a href="javascript:;" class="active" data-preview-device="Desktop" title="Desktop"><span class="icon-desktop"></span></a></li>
        <li><a href="javascript:;" data-preview-device="Tablet" title="Tablet"><span class="icon-tablet"></span></a></li>
        <li><a href="javascript:;" data-preview-device="Mobile" title="Mobile"><span class="icon-mobile"></span></a></li>
    </ul>
    <iframe
        width="100%"
        scrolling="no"
        class="preview-desktop"
        onload="this.style.height = this.contentWindow.document.documentElement.scrollHeight + 'px';"
        src="<?= Url::to($url); ?>"
    ></iframe>
</div>
