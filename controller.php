<?php

namespace Concrete\Package\CommunityStoreAffirm;

use Concrete\Core\Package\Package;
use Concrete\Core\Support\Facade\Config;
use Concrete\Core\Support\Facade\Route;
use Concrete\Core\View\View;
use Whoops\Exception\ErrorException;
use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_affirm';
    protected $appVersionRequired = '9.0';
    protected $pkgVersion = '2.0.0';
    protected $packageDependencies = ['community_store' => '2.0'];

    protected $pkgAutoloaderRegistries = [
        'src/CommunityStore' => '\Concrete\Package\CommunityStoreAffirm\Src\CommunityStore',
    ];

    public function getPackageDescription()
    {
        return t('Affirm Payment Method for Community Store');
    }

    public function getPackageName()
    {
        return t('Affirm Payment Method');
    }

    public function install()
    {
        $installed = $this->app->make('Concrete\Core\Package\PackageService')->getInstalledHandles();

        if (!(is_array($installed) && in_array('community_store', $installed))) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        }

        $pkg = parent::install();
        $pm = new PaymentMethod();
        $pm->add('community_store_affirm', 'Affirm', $pkg);
    }

    public function on_start()
    {
        $mode = (bool) Config::get('community_store_affirm.mode');
        $scriptBase = $mode ? 'https://cdn1-sandbox.affirm.com' : 'https://cdn1.affirm.com';
        $publicApiKey = (string) Config::get('community_store_affirm.publicApiKey');

        $affirmConfig = json_encode([
            'public_api_key' => $publicApiKey,
            'script' => $scriptBase . '/js/v2/affirm.js',
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        View::getInstance()->addHeaderItem(<<<HTML
<!-- Affirm -->
<script>
_affirm_config = {$affirmConfig};
(function(l,g,m,e,a,f,b){var d,c=l[m]||{},h=document.createElement(f),n=document.getElementsByTagName(f)[0],k=function(a,b,c){return function(){a[b]._.push([c,arguments])}};c[e]=k(c,e,"set");d=c[e];c[a]={};c[a]._=[];d._=[];c[a][b]=k(c,a,b);a=0;for(b="set add save post open empty reset on off trigger ready setProduct".split(" ");a<b.length;a++)d[b[a]]=k(c,e,b[a]);a=0;for(b=["get","token","url","items"];a<b.length;a++)d[b[a]]=function(){};h.async=!0;h.src=g[f];n.parentNode.insertBefore(h,n);delete g[f];d(g);l[m]=c})(window,_affirm_config,"affirm","checkout","ui","script","ready");
</script>
<!-- End Affirm -->
HTML
        );

        Route::register(
            '/checkout/affirmresponse',
            '\Concrete\Package\CommunityStoreAffirm\Src\CommunityStore\Payment\Methods\CommunityStoreAffirm\CommunityStoreAffirmPaymentMethod::validateCompletion'
        );
    }

    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('community_store_affirm');
        if ($pm) {
            $pm->delete();
        }

        parent::uninstall();
    }
}
