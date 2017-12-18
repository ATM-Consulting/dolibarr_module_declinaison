<?php
class ActionsDeclinaison
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
      */
      
	function beforePDFCreation($parameters, &$object, &$action, $hookmanager) {
		
		global $conf;
		
		if(!empty($conf->global->DECLINAISON_SHOW_PARENT_IN_EXPEDITION)) {
		
			dol_include_once('/declinaison/class/declinaison.class.php');
			
			foreach($object->lines as &$line) {
				
				$parent = TDeclinaison::getParent($line->fk_product);
			
				if($parent!==false) {
					
					$line->fk_product = $parent->id;
					$line->product_ref= $parent->ref;
					$line->product_label= $parent->label;
					$line->product_desc= $parent->desc;
					
				}
				
			}
			
		}
		
	}
	
    function formObjectOptions($parameters, &$object, &$action, $hookmanager) 
    {  
      	global $langs,$db;

		if (in_array('productcard',explode(':',$parameters['context'])) && $action == 'view') 
		{
			$resql=$db->query("SELECT fk_parent FROM ".MAIN_DB_PREFIX."declinaison WHERE fk_declinaison=".$object->id);
			$objp = $db->fetch_object($resql);
			if($objp->fk_parent > 0) {
				?>
				<script type="text/javascript">
					$(document).ready(function() {
						$('a.butAction, span.butAction').parent('div').remove();
					});
				</script>
				<?php
			}
		}
		
		return 0;
	}
     
    function formEditProductOptions($parameters, &$object, &$action, $hookmanager) 
    {
		
    	if (in_array('invoicecard',explode(':',$parameters['context'])))
        {
        	
        }
		
        return 0;
    }

	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {
		
		global $db;
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	
        }

		return 0;
	}

	function printObjectLine ($parameters, &$object, &$action, $hookmanager){
		
		global $db;
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	
        }

		return 0;
	}
}