<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/liste.php
 *  \ingroup    produit
 *  \brief      Page to list products and services
 */

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/declinaison/class/declinaison.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("declinaison@declinaison");

$action = GETPOST('action');
$sref=GETPOST("sref");
$sbarcode=GETPOST("sbarcode");
$snom=GETPOST("snom");
$sall=GETPOST("sall");
$type=GETPOST("type","int");
$search_sale = GETPOST("search_sale");
$search_categ = GETPOST("search_categ",'int');
$tosell = GETPOST("tosell");
$tobuy = GETPOST("tobuy");
$fourn_id = GETPOST("fourn_id",'int');
$catid = GETPOST('catid','int');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="p.fk_parent, p.ref";
if (! $sortorder) $sortorder="ASC";

$limit = $conf->liste_limit;

$fk_product = GETPOST('fk_product', 'int');
if($fk_product==0) exit('Erreur fk_product missing');

$product=new Product($db);
$product->fetch($fk_product);

$resql=$db->query("SELECT fk_parent FROM ".MAIN_DB_PREFIX."declinaison WHERE fk_declinaison=".$fk_product);
$objp = $db->fetch_object($resql);
if($objp->fk_parent==0) {
	$is_declinaison_master=true;
	$fk_parent_declinaison = $fk_product;
}
else {
	$is_declinaison_master=false;
	$fk_parent_declinaison = $objp->fk_parent;
}

if($action=='create_declinaison' && ($user->rights->produit->creer || $user->rights->service->creer) ) {
	
	$dec = new Product($db);
	$dec->fetch($fk_parent_declinaison);
	$dec->fetch_optionals($dec->id);
	
	$dec->libelle .=' (Déclinaison)';
	$dec->ref=GETPOST('reference_dec'); 
    $dec->id = null;
	if ($dec->check()){
               
		$id_clone = $dec->create($user);
		//$dec->clone_associations($fk_parent_declinaison, $id_clone);
		
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
		{
			$result=$dec->insertExtraFields();
		}
		
		if($id_clone>0) {
			
			/*//$resql=$db->query("UPDATE ".MAIN_DB_PREFIX."product SET fk_parent=".$fk_parent_declinaison." WHERE rowid=".$id_clone);
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."declinaison (fk_parent, fk_declinaison, up_to_date) VALUES(".$_REQUEST['fk_product'].", ".$dec->id.", 0)";
			$resql2=$db->query($sql);*/
			
			$TPDOdb = new TPDOdb;
			
			$newDeclinaison = new TDeclinaison;
			$newDeclinaison->fk_parent = $_REQUEST['fk_product'];
			$newDeclinaison->fk_declinaison = $dec->id;
			$newDeclinaison->up_to_date = 1;
			
			$newDeclinaison->save($TPDOdb);
						
/*		    header("Location: ".dol_buildpath('/product/fiche.php', 1)."?action=edit&id=".$id_clone);
		    exit;
*/
		}
		else {
			if($id_clone==-1) {
				setEventMessage($langs->trans('ErrorProductAlreadyExists', $dec->ref), 'errors');
			}
			else {
					
				print 'clone:'.(int)$id_clone.'<br />';
				dol_print_error($db,$dec->error);
				
			}
			
		}
	}
		
	else {
		print "check : ";
		dol_print_error($db,$dec->error);
	}
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
//$object->getCanvas($id);
$canvas=GETPOST("canvas");
$objcanvas='';
if (! empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db,$action);
    $objcanvas->getCanvas('product','list',$canvas);
}

// Security check
if ($type=='0') $result=restrictedArea($user,'produit','','','','','',$objcanvas);
else if ($type=='1') $result=restrictedArea($user,'service','','','','','',$objcanvas);
else $result=restrictedArea($user,'produit|service','','','','','',$objcanvas);


/*
 * Actions
 */

if (isset($_POST["button_removefilter_x"]))
{
	$sref="";
	$sbarcode="";
	$snom="";
	$search_categ=0;
}


/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
	$objcanvas->assign_values('list');       // This must contains code to load data (must call LoadListDatas($limit, $offset, $sortfield, $sortorder))
    $objcanvas->display_canvas('list');  	 // This is code to show template
}
else
{
	$title=$langs->trans("ProductsAndServices");

	if (isset($type))
	{
		if ($type==1)
		{
			$texte = $langs->trans("Services");
		}
		else
		{
			$texte = $langs->trans("Products");
		}
	}
	else
	{
		$texte = $langs->trans("ProductsAndServices");
	}

    $sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,';
    $sql.= ' p.fk_product_type, p.tms as datem,';
    $sql.= ' p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte,';
    $sql.= ' MIN(pfp.unitprice) as minsellprice';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
    if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
   	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."declinaison as d ON d.fk_declinaison = p.rowid";
    $sql.= ' WHERE p.entity IN ('.getEntity('product', 1).') AND (d.fk_parent='.$fk_parent_declinaison." OR p.rowid=$fk_parent_declinaison)";
    if ($sall)
    {
        $sql.= " AND (p.ref LIKE '%".$db->escape($sall)."%' OR p.label LIKE '%".$db->escape($sall)."%' OR p.description LIKE '%".$db->escape($sall)."%' OR p.note LIKE '%".$db->escape($sall)."%'";
        if (! empty($conf->barcode->enabled))
        {
            $sql.= " OR p.barcode LIKE '%".$db->escape($sall)."%'";
        }
        $sql.= ')';
    }
    // if the type is not 1, we show all products (type = 0,2,3)
    if (dol_strlen($type))
    {
    	if ($type == 1) $sql.= " AND p.fk_product_type = '1'";
    	else $sql.= " AND p.fk_product_type <> '1'";
    }
    if ($sref)     $sql.= " AND p.ref LIKE '%".$sref."%'";
    if ($sbarcode) $sql.= " AND p.barcode LIKE '%".$sbarcode."%'";
    if ($snom)     $sql.= " AND p.label LIKE '%".$db->escape($snom)."%'";
    if (isset($tosell) && dol_strlen($tosell) > 0) $sql.= " AND p.tosell = ".$db->escape($tosell);
    if (isset($tobuy) && dol_strlen($tobuy) > 0)   $sql.= " AND p.tobuy = ".$db->escape($tobuy);
    if (dol_strlen($canvas) > 0)                    $sql.= " AND p.canvas = '".$db->escape($canvas)."'";
    if ($catid > 0)    $sql.= " AND cp.fk_categorie = ".$catid;
    if ($catid == -2)  $sql.= " AND cp.fk_categorie IS NULL";
    if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
    if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
    if ($fourn_id > 0) $sql.= " AND pfp.fk_soc = ".$fourn_id;
    $sql.= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,";
    $sql.= " p.fk_product_type, p.tms,";
    $sql.= " p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte";
    //if (GETPOST("toolowstock")) $sql.= " HAVING SUM(s.reel) < p.seuil_stock_alerte";    // Not used yet
    $sql.= $db->order($sortfield,$sortorder);
    $sql.= $db->plimit($limit + 1, $offset);
	
    dol_syslog("sql=".$sql);
    $resql = $db->query($sql);
    if ($resql)
    {
    	$num = $db->num_rows($resql);

    	$i = 0;


    	$helpurl='';
    	if (isset($type))
    	{
    		if ($type == 0)
    		{
    			$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
    		}
    		else if ($type == 1)
    		{
    			$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
    		}
    	}

    	$title=$langs->trans("Declinaison");
        llxHeader('',$title,$helpurl,'');
		
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'declinaison', $titre, 0, $picto);
		
		$prod = new Product($db);
		$prod->fetch($_REQUEST['fk_product']);		
		
		?>
			<table class="border" width="100%">
				<tr>
					<td><?= $langs->trans("Ref")?></td>
					<td><?= $prod->ref?></td>
				</tr>
				<tr>
					<td><?= $langs->trans("Label")?></td>
					<td><?= $prod->libelle?></td>
				</tr>
			</table><br />		
		<?
    	// Displays product removal confirmation
    	if (GETPOST('delprod'))	dol_htmloutput_mesg($langs->trans("ProductDeleted",GETPOST('delprod')));

    	$param="&fk_product=".$fk_product."&amp;sref=".$sref.($sbarcode?"&amp;sbarcode=".$sbarcode:"")."&amp;snom=".$snom."&amp;sall=".$sall."&amp;tosell=".$tosell."&amp;tobuy=".$tobuy;
    	$param.=($fourn_id?"&amp;fourn_id=".$fourn_id:"");
    	$param.=($search_categ?"&amp;search_categ=".$search_categ:"");
    	$param.=isset($type)?"&amp;type=".$type:"";
    	print_barre_liste('', $page, "liste.php", $param, $sortfield, $sortorder,'',$num);

    	if (! empty($catid))
    	{
    		print "<div id='ways'>";
    		$c = new Categorie($db);
    		$ways = $c->print_all_ways(' &gt; ','product/liste.php');
    		print " &gt; ".$ways[0]."<br>\n";
    		print "</div><br>";
    	}

    	if (! empty($canvas) && file_exists(DOL_DOCUMENT_ROOT.'/product/canvas/'.$canvas.'/actions_card_'.$canvas.'.class.php'))
    	{
    		$fieldlist = $object->field_list;
    		$datas = $object->list_datas;
    		$picto='title.png';
    		$title_picto = img_picto('',$picto);
    		$title_text = $title;

    		// Default templates directory
    		$template_dir = DOL_DOCUMENT_ROOT . '/product/canvas/'.$canvas.'/tpl/';
    		// Check if a custom template is present
    		if (file_exists(DOL_DOCUMENT_ROOT . '/theme/'.$conf->theme.'/tpl/product/'.$canvas.'/list.tpl.php'))
    		{
    			$template_dir = DOL_DOCUMENT_ROOT . '/theme/'.$conf->theme.'/tpl/product/'.$canvas.'/';
    		}

    		include $template_dir.'list.tpl.php';	// Include native PHP templates
    	}
    	else
    	{
    		
			if($is_declinaison_master && ($user->rights->produit->creer || $user->rights->service->creer) ) {
			/* c'est la déclinaison parente */	
				?>
				<p>
					<input type="text" name="reference_dec" id="reference_dec" value="<?=$product->ref.chr(65+$num) ?>" />
					<a href="#" onclick="document.location.href='liste.php?action=create_declinaison&fk_parent_declinaison=<?=$fk_parent_declinaison ?>&fk_product=<?=$fk_product ?>&reference_dec='+$('#reference_dec').val();" class="butAction">Créer une nouvelle déclinaison</a>
				<br />
				<br />
				</p>
				<?
				
			}
			elseif(!$is_declinaison_master){
				if(isset($_REQUEST['maintientAJour'])) {
					//Le produit est une déclinaison
					//echo($_REQUEST['up_to_date']);
					//if($_REQUEST['up_to_date'] == "Oui") {
					$sql = "UPDATE ".MAIN_DB_PREFIX."declinaison";
					$sql.= " SET up_to_date = ";
					$_REQUEST['up_to_date'] == "Oui"?$sql.="1":$sql.="0";
					$sql.= " WHERE fk_declinaison = ".$_REQUEST['fk_product'];

					$db->query($sql);
					
					setEventMessage("Modification enregistrée avec succès");
				}				
							
				?>
				
					<form name="priceUpToDate" method="POST" action="" />
						<p>
							
							<?					
								//On récupère la valeur actuelle du champ "up_to_date" pour cette déclinaison
								$sql = "SELECT up_to_date";
								$sql.= " FROM ".MAIN_DB_PREFIX."declinaison";
								$sql.= " WHERE fk_declinaison = ".$_REQUEST['fk_product'];
								$result = $db->query($sql);
								$re = $db->fetch_object($result);
							?>
							Maintenir les prix à jour avec le parent ?
							<table>
								<tr><td>Oui</td><td><input type="radio" name="up_to_date" value="Oui" <?if ($re->up_to_date){?>checked="checked"<?}?>/></td></tr>
								<tr><td>Non</td><td><input type="radio" name="up_to_date" value="Non" <?if(!$re->up_to_date){?>checked="checked"<?}?>/></td></tr>
<tr><td colspan="2" align="center">
							<input type="submit" name="maintientAJour" value="Valider" />
</td></tr>
							</table>
							<!--<?print $form->selectyesno("sync_price_dec",$object->public,1);?>-->
							
						<br />
						</p>
					</form>
				<?
			}
			
			
    		print '<form action="liste.php" method="post" name="formulaire">';
    		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    		print '<input type="hidden" name="action" value="list">';
    		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    		print '<input type="hidden" name="type" value="'.$type.'">';
    		print '<input type="hidden" name="fk_product" value="'.$fk_product.'">';

    		print '<table id="listDeclinaison" class="liste" width="100%">';

    		// Filter on categories
    	 	$moreforfilter='';
    		if (! empty($conf->categorie->enabled))
    		{
    		 	$moreforfilter.=$langs->trans('Categories'). ': ';
    			$moreforfilter.=$htmlother->select_categories(0,$search_categ,'search_categ',1);
    		 	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
    		}
    	 	if ($moreforfilter)
    		{
    			print '<tr class="liste_titre">';
    			print '<td class="liste_titre" colspan="9">';
    		    print $moreforfilter;
    		    print '</td></tr>';
    		}

    		// Lignes des titres
    		print "<tr class=\"liste_titre\">";
    		print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.ref",$param,"","",$sortfield,$sortorder);
    		print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"], "p.label",$param,"","",$sortfield,$sortorder);
    		if (! empty($conf->barcode->enabled)) print_liste_field_titre($langs->trans("BarCode"), $_SERVER["PHP_SELF"], "p.barcode",$param,'','',$sortfield,$sortorder);
    		print_liste_field_titre($langs->trans("DateModification"), $_SERVER["PHP_SELF"], "p.tms",$param,"",'align="center"',$sortfield,$sortorder);
    		if (! empty($conf->service->enabled) && $type != 0) print_liste_field_titre($langs->trans("Duration"), $_SERVER["PHP_SELF"], "p.duration",$param,"",'align="center"',$sortfield,$sortorder);
    		if (empty($conf->global->PRODUIT_MULTIPRICES)) print_liste_field_titre($langs->trans("SellingPrice"), $_SERVER["PHP_SELF"], "p.price",$param,"",'align="right"',$sortfield,$sortorder);
    		print '<td class="liste_titre" align="right">'.$langs->trans("BuyingPriceMinShort").'</td>';
    		if (! empty($conf->stock->enabled) && $user->rights->stock->lire && $type != 1) print '<td class="liste_titre" align="right">'.$langs->trans("PhysicalStock").'</td>';
    		print_liste_field_titre($langs->trans("Sell"), $_SERVER["PHP_SELF"], "p.tosell",$param,"",'align="right"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("Buy"), $_SERVER["PHP_SELF"], "p.tobuy",$param,"",'align="right"',$sortfield,$sortorder);
    		print "</tr>\n";

    		// Lignes des champs de filtre
    		print '<tr class="liste_titre">';
    		print '<td class="liste_titre" align="left">';
    		print '<input class="flat" type="text" name="sref" size="8" value="'.$sref.'">';
    		print '</td>';
    		print '<td class="liste_titre" align="left">';
    		print '<input class="flat" type="text" name="snom" size="12" value="'.$snom.'">';
    		print '</td>';
    		if (! empty($conf->barcode->enabled))
    		{
    			print '<td class="liste_titre">';
    			print '<input class="flat" type="text" name="sbarcode" size="6" value="'.$sbarcode.'">';
    			print '</td>';
    		}
    		print '<td class="liste_titre">';
    		print '&nbsp;';
    		print '</td>';

    		// Duration
    		if (! empty($conf->service->enabled) && $type != 0)
    		{
    			print '<td class="liste_titre">';
    			print '&nbsp;';
    			print '</td>';
    		}

    		// Sell price
            if (empty($conf->global->PRODUIT_MULTIPRICES))
            {
        		print '<td class="liste_titre">';
        		print '&nbsp;';
        		print '</td>';
            }

    		// Minimum buying Price
    		print '<td class="liste_titre">';
    		print '&nbsp;';
    		print '</td>';

    		// Stock
    		if (! empty($conf->stock->enabled) && $user->rights->stock->lire && $type != 1)
    		{
    			print '<td class="liste_titre">';
    			print '&nbsp;';
    			print '</td>';
    		}

    		print '<td class="liste_titre">';
            print '&nbsp;';
            print '</td>';

    		print '<td class="liste_titre" align="right">';
    		print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    		print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    		print '</td>';
    		print '</tr>';


    		$product_static=new Product($db);
    		$product_fourn =new ProductFournisseur($db);

    		$var=true;
    		while ($i < min($num,$limit))
    		{
    			$objp = $db->fetch_object($resql);

    			// Multilangs
    			if (! empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
    			{
    				$sql = "SELECT label";
    				$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
    				$sql.= " WHERE fk_product=".$objp->rowid;
    				$sql.= " AND lang='". $langs->getDefaultLang() ."'";
    				$sql.= " LIMIT 1";

    				$result = $db->query($sql);
    				if ($result)
    				{
    					$objtp = $db->fetch_object($result);
    					if (! empty($objtp->label)) $objp->label = $objtp->label;
    				}
    			}

    			$var=!$var;
    			print '<tr '.$bc[$var].'>';

    			// Ref
    			print '<td nowrap="nowrap">';
    			$product_static->id = $objp->rowid;
    			$product_static->ref = $objp->ref;
    			$product_static->type = $objp->fk_product_type;
    			print $product_static->getNomUrl(1,'',24);
    			print "</td>\n";

    			// Label
    			print '<td>'.dol_trunc($objp->label,40);
			if($is_declinaison_master) print ' <a href="javascript:quickEditProduct('.$objp->rowid.')">edit</a></td>';

    			// Barcode
    			if (! empty($conf->barcode->enabled))
    			{
    				print '<td>'.$objp->barcode.'</td>';
    			}

    			// Date
    			print '<td align="center">'.dol_print_date($db->jdate($objp->datem),'day')."</td>\n";

    			// Duration
    			if (! empty($conf->service->enabled) && $type != 0)
    			{
    				print '<td align="center">';
    				if (preg_match('/([0-9]+)y/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationYear");
    				elseif (preg_match('/([0-9]+)m/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationMonth");
    				elseif (preg_match('/([0-9]+)w/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationWeek");
    				elseif (preg_match('/([0-9]+)d/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationDay");
    				else print $objp->duration;
    				print '</td>';
    			}

    			// Sell price
    			if (empty($conf->global->PRODUIT_MULTIPRICES))
    			{
    			    print '<td align="right">';
        			if ($objp->price_base_type == 'TTC') print price($objp->price_ttc).' '.$langs->trans("TTC");
        			else print price($objp->price).' '.$langs->trans("HT");
        			print '</td>';
    			}

    			// Better buy price
                print  '<td align="right">';
                if ($objp->minsellprice != '')
                {
                    //print price($objp->minsellprice).' '.$langs->trans("HT");
        			if ($product_fourn->find_min_price_product_fournisseur($objp->rowid) > 0)
        			{
        			    if ($product_fourn->product_fourn_price_id > 0)
        			    {
        			        $htmltext=$product_fourn->display_price_product_fournisseur();
                            if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire) print $form->textwithpicto(price($product_fourn->fourn_unitprice).' '.$langs->trans("HT"),$htmltext);
                            else print price($product_fourn->fourn_unitprice).' '.$langs->trans("HT");
        			    }
        			}
                }
                print '</td>';

    			// Show stock
    			if (! empty($conf->stock->enabled) && $user->rights->stock->lire && $type != 1)
    			{
    				if ($objp->fk_product_type != 1)
    				{
    					$product_static->id = $objp->rowid;
    					$product_static->load_stock();
    					print '<td align="right">';
                        if ($product_static->stock_reel < $objp->seuil_stock_alerte) print img_warning($langs->trans("StockTooLow")).' ';
        				print $product_static->stock_reel;
    					print '</td>';
    				}
    				else
    				{
    					print '<td>&nbsp;</td>';
    				}
    			}

    			// Status (to buy)
    			print '<td align="right" nowrap="nowrap">'.$product_static->LibStatut($objp->tosell,5,0).'</td>';

                // Status (to sell)
                print '<td align="right" nowrap="nowrap">'.$product_static->LibStatut($objp->tobuy,5,1).'</td>';

                print "</tr>\n";
    			$i++;
    		}

    		$db->free($resql);

    		print "</table>";
    		print '</div>';
    		print '</form>';
			
    	}
    }
    else
    {
    	dol_print_error($db);
    }
}
?>
<script type="text/javascript">
function quickEditProduct(fk_product) {

	if($('#quickEditProduct').length==0) {
		$('body').append('<div id="quickEditProduct" title="Edition rapide"></div>');
	}

	$.get("<?=dol_buildpath('/product/fiche.php?action=edit&id=',1) ?>"+fk_product, function(data) {
		var html = $(data).find('div.fiche').html();


		$('#quickEditProduct').html(html);
		$('#quickEditProduct input[name=cancel]').remove();

		$('#quickEditProduct form').submit(function() {

			$.post($(this).attr('action'), $( this ).serialize() );

			$('#quickEditProduct').dialog("close");
			$.jnotify('Modifications enregistr&eacute;s', "ok");   

			$.get(document.location.href, function(data) {
				$('#listDeclinaison').replaceWith( $(data).find('#listDeclinaison'));
			});

			return false;
		});

		$('#quickEditProduct').dialog({
			modal:true,
			width:'80%'
		});

	});

}
<?
	if(!empty($id_clone) && $id_clone>0) {
		?>
		quickEditProduct(<?=$id_clone ?>);
		<?
	}
?>
</script>

<?php

llxFooter();
$db->close();
