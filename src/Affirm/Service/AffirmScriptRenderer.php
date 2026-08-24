<?php

namespace Concrete\Package\CommunityStoreAffirm\Affirm\Service;

use Concrete\Core\Support\Facade\Config as ConfigFacade;
use Concrete\Package\CommunityStoreAffirm\Affirm\Config;

class AffirmScriptRenderer
{
    public function render(): string
    {
        $publicApiKey = (string) ConfigFacade::get(Config::PUBLIC_API_KEY);
        if ($publicApiKey === '') {
            return '';
        }

        $testMode = (bool) ConfigFacade::get(Config::MODE);
        $scriptBase = $testMode ? Config::SANDBOX_SCRIPT_BASE : Config::LIVE_SCRIPT_BASE;

        $affirmConfig = json_encode([
            'public_api_key' => $publicApiKey,
            'script' => $scriptBase . '/js/v2/affirm.js',
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return <<<HTML
<!-- Affirm -->
<script>
_affirm_config = {$affirmConfig};
(function(l,g,m,e,a,f,b){var d,c=l[m]||{},h=document.createElement(f),n=document.getElementsByTagName(f)[0],k=function(a,b,c){return function(){a[b]._.push([c,arguments])}};c[e]=k(c,e,"set");d=c[e];c[a]={};c[a]._=[];d._=[];c[a][b]=k(c,a,b);a=0;for(b="set add save post open empty reset on off trigger ready setProduct".split(" ");a<b.length;a++)d[b[a]]=k(c,e,b[a]);a=0;for(b=["get","token","url","items"];a<b.length;a++)d[b[a]]=function(){};h.async=!0;h.src=g[f];n.parentNode.insertBefore(h,n);delete g[f];d(g);l[m]=c})(window,_affirm_config,"affirm","checkout","ui","script","ready");
</script>
<!-- End Affirm -->
HTML;
    }
}
