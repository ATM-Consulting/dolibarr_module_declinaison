<?php
	class TDeclinaison extends TObjetStd {
		function __construct() { /* declaration */
			global $langs;
			
			parent::set_table(MAIN_DB_PREFIX.'declinaison');
			parent::add_champs('fk_parent,fk_declinaison','type=entier;index;');
			parent::add_champs('up_to_date','type=entier;index;');
			parent::add_champs('ref_added','type=chaine;');
            parent::add_champs('more_price,more_percent','type=chaine;');
            
			parent::_init_vars();
			parent::start();
			
			$this->up_to_date=1;
		}
		
		function save(&$PDOdb) {
			
			parent::save($PDOdb);
			
		}
	}
?>