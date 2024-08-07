<div id="app" v-cloak></div>
<script>
    {!! \ModStart\ModStart::lang([ "Select Local File" ]) !!};
    window.__selectorDialogServer = "{{$selectorDialogServer}}";
    window._data = {
        variables: {!! \ModStart\Core\Util\SerializeUtil::jsonEncode($variables) !!},
        imageConfig: {!! \ModStart\Core\Util\SerializeUtil::jsonEncode($imageConfig) !!}
    };
</script>

{{ \ModStart\ModStart::js('asset/vendor/vue.js') }}
{{ \ModStart\ModStart::js('asset/vendor/element-ui/index.js') }}
{{ \ModStart\ModStart::js('asset/entry/basic.js') }}
{{ \ModStart\ModStart::js('vendor/Vendor/entry/quickRunImageDesign.js') }}
