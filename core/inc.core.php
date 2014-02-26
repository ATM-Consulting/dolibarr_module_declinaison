<?php
	if(defined('ATM_CORE_INCLUDED')) {
 		null;
		
 	}
	else {
	
		define('OBJETSTD_MASTERKEY', 'rowid');
		define('OBJETSTD_DATECREATE', 'date_cre');
		define('OBJETSTD_DATEUPDATE', 'date_maj');
		define('OBJETSTD_DATEMASK', 'date_');
		
		$l_dir = getcwd();
		chdir(__DIR__);
	
		define('CORECLASS','./includes/class/');
	   	require_once(CORECLASS.'class.pdo.db.php');
	 	require_once(CORECLASS.'class.objet_std.php');
		require_once(CORECLASS.'class.objet_std_dolibarr.php');
	
	 	require_once(CORECLASS.'class.reponse.mail.php');
	 	require_once(CORECLASS.'class.requete.core.php');
	 	require_once(CORECLASS.'class.tools.php');
	
	 	require_once(CORECLASS.'class.form.core.php');
	 	require_once(CORECLASS.'class.tbl.php');
	
	 	require_once(CORECLASS.'tbs_class.php');
	 	require_once(CORECLASS.'tbs_plugin_opentbs.php');
		require_once(CORECLASS.'plugins/tbs_plugin_bypage.php');
		require_once(CORECLASS.'plugins/tbs_plugin_navbar.php');
		require_once(CORECLASS.'class.template.tbs.php');
		require_once(CORECLASS.'class.list.tbs.php');
		
	
	/*
	 * Inclusion des fonctions
	 */
	
		require_once('./includes/fonctions-core.php');		
	
		chdir($l_dir);
		
		define('ATM_CORE_INCLUDED', true);
	
	}
		