<?php
namespace Concrete\Package\CommunityStoreAffirm;

use Core;
use Package;
use View;
use Config;
use Route;
use Whoops\Exception\ErrorException;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_affirm';
    protected $appVersionRequired = '5.7.5';
    protected $pkgVersion = '1.0.0';

    public function getPackageDescription()
    {
        return t("Affirm Method for Community Store");
    }

    public function getPackageName()
    {
        return t("Affirm Payment Method");
    }
    
    public function install()
    {
        $installed = Package::getInstalledHandles();
        if(!(is_array($installed) && in_array('community_store',$installed)) ) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        } else {
            $pkg = parent::install();
            $pm = new PaymentMethod();
            $pm->add('community_store_affirm','Affirm',$pkg);
        }
    }

    public function on_start() {
        
        // link affirm JS library
        $mode = Config::get('community_store_affirm.mode');
        $script = ($mode ? 'https://cdn1-sandbox.affirm.com' : 'https://api.affirm.com');
        $html = Core::make('helper/html');
        $view = View::getInstance();
        $view->addHeaderItem('<!-- Affirm -->
<script>
 _affirm_config = {
   public_api_key:  "' . Config::get('community_store_affirm.publicApiKey') . '",
   script:          "' . $script . '/js/v2/affirm.js"
   //session_id:      "YOUR_VISITOR_SESSION_ID"
 };
 (function(l,g,m,e,a,f,b){var d,c=l[m]||{},h=document.createElement(f),n=document.getElementsByTagName(f)[0],k=function(a,b,c){return function(){a[b]._.push([c,arguments])}};c[e]=k(c,e,"set");d=c[e];c[a]={};c[a]._=[];d._=[];c[a][b]=k(c,a,b);a=0;for(b="set add save post open empty reset on off trigger ready setProduct".split(" ");a<b.length;a++)d[b[a]]=k(c,e,b[a]);a=0;for(b=["get","token","url","items"];a<b.length;a++)d[b[a]]=function(){};h.async=!0;h.src=g[f];n.parentNode.insertBefore(h,n);delete g[f];d(g);l[m]=c})(window,_affirm_config,"affirm","checkout","ui","script","ready");
// Use your live public API Key and https://cdn1.affirm.com/js/v2/affirm.js script to point to Affirm production environment.
</script>
<!-- End Affirm -->');
        
        // enable checkout route after affirm payment
        Route::register('/checkout/affirmresponse','\Concrete\Package\CommunityStoreAffirm\Src\CommunityStore\Payment\Methods\CommunityStoreAffirm\CommunityStoreAffirmPaymentMethod::validateCompletion');
    }
    
    public function on_before_render_handler(){
        
    }

    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('community_store_affirm');
        if ($pm) {
            $pm->delete();
        }
        $pkg = parent::uninstall();
    }

}
?>