<?
	class TDeclinaison extends TObjetStd {
		function __construct() { /* declaration */
			global $langs;
			
			parent::set_table(MAIN_DB_PREFIX.'declinaison');
			parent::add_champs('fk_parent,fk_declinaison','type=entier;index;');
			parent::add_champs('up_to_date','type=entier;index;');
			
			parent::_init_vars();
			parent::start();
			
			$this->up_to_date=1;
		}
	}
?>