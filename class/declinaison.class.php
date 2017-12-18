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
		
		static function getParent($fk_product) {
			global $db;
			
			dol_include_once('/product/class/product.class.php');
			$res = $db->query("SELECT fk_parent FROM ".MAIN_DB_PREFIX."declinaison WHERE fk_declinaison=".(int)$fk_product);
			if($res!== false && $obj = $db->fetch_object($res)) {
				
				
				
				$p=new Product($db);
				if($p->fetch($obj->fk_parent)) return $p;
				
			}
			
			return false;
			
		}
	}
?>