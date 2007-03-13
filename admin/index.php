<?php
// Ladies and Gentlemen, start your timers
define('APP_START', microtime(true));

// include the paths and define a theme path
include '../paths.php';
define('THEME_DIR', ADMIN_DIR.DIRECTORY_SEPARATOR.'themes');

require_once '../core.php';

#############################################
# HERE IS WHERE WE SET THE ADMIN SWITCH
#############################################
$request->setParam('admin', true);
#############################################
# END ADMIN SWITCH
#############################################

#############################################
# CHECKING TO SEE IF THE USER IS LOGGED IN IS HANDLED BY
# THE Kea_Controller_Action::preDispatch() method
#############################################

#############################################
# DISPATCH THE REQUEST, AND DO SOMETHING WITH THE OUTPUT
#############################################
try{
	$front->dispatch();
}catch(Exception $e) {
	include BASE_DIR.DIRECTORY_SEPARATOR.'404.php';
}

if ((boolean) $config->debug->timer) {
	echo microtime(true) - APP_START;
}
// We're done here.
?>