<?php

require('config.php');
global $db;

if(!empty($_REQUEST['fk_product']) && $_REQUEST['action'] == 'is_up_to_date') {
	$sql = "SELECT fk_parent, up_to_date FROM ".MAIN_DB_PREFIX."declinaison WHERE fk_declinaison=".$_REQUEST['fk_product'];
	$resql=$db->query($sql);
	$objp = $db->fetch_object($resql);
	if($objp->fk_parent > 0) {
		echo json_encode($objp->up_to_date);
	} else {
		echo json_encode(0);
	}
}