<div data-control="page-preview" class="preview-desktop">
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

        $sessionKey = \Winter\Pages\Classes\ObjectHelper::getTypePreviewSessionCacheKey(
            \Winter\Pages\Classes\ObjectHelper::resolveClassType(get_class($formModel)),
            $formAlias
        );

        $object = (Request::ajax() && Request::has('objectType'))
            ? $this->getObjectFromRequest()
            : $formModel;

        Cache::put($sessionKey, $object->toArray());

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
        src="<?= Url::to($url); ?>"
    ></iframe>
</div>
