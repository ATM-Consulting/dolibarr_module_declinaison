<?php
class ActionsDeclinaison
{
     /** Overloading the doActions function : replacing the parent's function with the one below
      *  @param      parameters  meta datas of the hook (context, etc...)
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
      *  @param      action             current action (if set). Generally create or edit or null
      *  @return       void
      */

	function doActions($parameters, &$object, &$action, $hookmanager) {

		if($action==='builddoc' && (
				(!empty($object->element)
					&& !empty($conf->global->DECLINAISON_OBJECT_USURPATION)
					&& in_array( $object->element, explode(',', $conf->global->DECLINAISON_OBJECT_USURPATION) )
					)
				|| (empty($conf->global->DECLINAISON_OBJECT_USURPATION)
						&& ($object->element=='propal' || $object->element=='facture' || $object->element=='commande' || $object->element=='shipping'))
			)
		) {

		//	return $this->dec_replace_line($parameters, $object, $action, $hookmanager);

		}

	}

	function afterPDFCreation($parameters, &$object, &$action, $hookmanager) {

		global $conf;
/*
		if(!empty($conf->global->DECLINAISON_SHOW_PARENT_INSTEAD_OF_CHILD_INTO_PDF)) {

			if(
					(!empty($object->element)
							&& !empty($conf->global->DECLINAISON_OBJECT_USURPATION)
							&& in_array( $object->element, explode(',', $conf->global->DECLINAISON_OBJECT_USURPATION) )
							)
					|| empty($conf->global->DECLINAISON_OBJECT_USURPATION)
					) {
						if(!empty($conf->global->DECLINAISON_COMPACT_LINES)) {

							$object->fetch($object->id);exit('la');

						}
					}
		}*/
	}

	function beforePDFCreation($parameters, &$object, &$action, $hookmanager) {

		return $this->dec_replace_line($parameters, $object, $action, $hookmanager);
	}

	function dec_replace_line($parameters, &$object, &$action, $hookmanager) {

		global $conf;

		if(!empty($conf->global->DECLINAISON_SHOW_PARENT_INSTEAD_OF_CHILD_INTO_PDF)) {

			if(
				(!empty($object->element)
						&& !empty($conf->global->DECLINAISON_OBJECT_USURPATION)
						&& in_array( $object->element, explode(',', $conf->global->DECLINAISON_OBJECT_USURPATION) )
				)
				|| empty($conf->global->DECLINAISON_OBJECT_USURPATION)
			) {


				define('INC_FROM_DOLIBARR',true);
				dol_include_once('/declinaison/config.php');
				dol_include_once('/declinaison/class/declinaison.class.php');

				foreach($object->lines as &$line) {

					if($line->product_type>1) continue;

					$parent = TDeclinaison::getParent($line->fk_product);

					if($parent!==false) {

						$line->fk_product = $parent->id;
						$line->product_ref= $parent->ref;
						$line->product_label = $line->label = $parent->label;
						$line->desc=$line->description=$line->product_desc= $parent->desc; //TODO description might be customed... check if different before override

					}

				}

				if(!empty($conf->global->DECLINAISON_COMPACT_LINES)) {

					$fk_product = -1;$line_k=array();
					foreach($object->lines as $k=>&$line) {

						if($line->product_type>1 && !empty($conf->global->DECLINAISON_COMPACT_LINES_BREAK_ON_TITLE)) {
							$line_k=array();
						}

						if($line->fk_product > 0) {

							//&& ($line->fk_product!=$fk_product || $fk_product == -1)) {

							if(!isset($line_k[$line->fk_product])) {
								if(!empty($conf->global->DECLINAISON_COMPACT_LINES_BREAK_OTHER_PRODUCT))$line_k=array();
								$line_k[$line->fk_product]= $k;
							}

						}

						if (isset($line_k[$line->fk_product]) && $k!=$line_k[$line->fk_product]){
							$object->lines[$line_k[$line->fk_product]]->qty+=$line->qty;

							if(isset($line->qty_shipped))$object->lines[$line_k[$line->fk_product]]->qty_shipped+=$line->qty_shipped;
							if(isset($line->qty_asked))$object->lines[$line_k[$line->fk_product]]->qty_asked+=$line->qty_asked;

							$object->lines[$line_k[$line->fk_product]]->total_ht+=$line->total_ht;
							$object->lines[$line_k[$line->fk_product]]->total+=$line->total;
							$object->lines[$line_k[$line->fk_product]]->desc = $object->lines[$line_k[$line->fk_product]]->label= $object->lines[$line_k[$line->fk_product]]->description = ''; // Sinon utilise la description de la ligne dans laquelle on groupe les qtés, donc celle d'une déclinaison.

							$object->lines[$k]->special_code = 3;

							if(!empty($object->commande->expeditions)) {
								$fk_origin_line1 = $object->lines[$line_k[$line->fk_product]]->fk_origin_line;
								$object->commande->expeditions[$fk_origin_line1]+=$object->commande->expeditions[$line->fk_origin_line];

								//var_dump($line->fk_origin_line, $object->commande->expeditions);exit;

							}
							//unset($object->lines[$k]);
							//var_dump($fk_product,$line_k,$k,$object->lines[$line_k]->qty);exit;
						}

					}

					ksort($object->lines);

				}
			}
		}

	}

	function dec_return_null($parameters, &$object, &$action, $hookmanager) {
		global $conf;
		if(!empty($conf->global->DECLINAISON_COMPACT_LINES)) {
			$line = &$object->lines[$parameters['i']];
			if($line->special_code == 3) return 1;
		}
		return 0;
	}


	function pdf_getlinetotalexcltax($parameters, &$object, &$action, $hookmanager) {
		return $this->dec_return_null($parameters, $object, $action, $hookmanager);
	}
	function pdf_getlineremisepercent($parameters, &$object, &$action, $hookmanager) {
		return $this->dec_return_null($parameters, $object, $action, $hookmanager);
	}
	function pdf_getlinevatrate($parameters, &$object, &$action, $hookmanager) {
		return $this->dec_return_null($parameters, $object, $action, $hookmanager);
	}
	function pdf_getlineupexcltax($parameters, &$object, &$action, $hookmanager) {
		return $this->dec_return_null($parameters, $object, $action, $hookmanager);
	}
	function pdf_getlineqty($parameters, &$object, &$action, $hookmanager) {
		return $this->dec_return_null($parameters, $object, $action, $hookmanager);
	}
	function pdf_getlineunit($parameters, &$object, &$action, $hookmanager) {
		return $this->dec_return_null($parameters, $object, $action, $hookmanager);
	}
	function pdf_writelinedesc($parameters, &$object, &$action, $hookmanager) {
		return $this->dec_return_null($parameters, $object, $action, $hookmanager);
	}
	function pdf_getlineref($parameters, &$object, &$action, $hookmanager) {
		return $this->dec_return_null($parameters, $object, $action, $hookmanager);

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