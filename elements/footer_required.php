<?php 

$_trackingCodePosition = Config::get('SITE_TRACKING_CODE_POSITION');
if (empty($disableTrackingCode) && (empty($_trackingCodePosition) || $_trackingCodePosition === 'bottom')) {
	echo Config::get('SITE_TRACKING_CODE');
}

?>

<?php

$mh = Loader::helper("minify", "nuevebit");
$mh->outputItems(array_merge($this->getHeaderItems(), $this->getFooterItems()), "js");
//print $this->controller->outputFooterItems();

?>