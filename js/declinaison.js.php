<?php
	
	require('../config.php');
	$ajax = dol_buildpath('/declinaison/ajax_declinaison.php',1);
?>

$(document).ready(function() {
	if($('div.fiche div.tabs a[href*="product/fiche.php"]').length > 0) {
		var link = $('div.fiche div.tabs a[href*="product/fiche.php"]').attr('href');
		idProd = link.split('fiche.php?id=')[1];
		$.ajax({
			type: "POST"
			,url: "<?= $ajax ?>"
			,dataType: "json"
			,data: {action: 'is_up_to_date', fk_product: idProd}
			},"json").then(function(res){
				if(res == 1) {
					$('a.tab[id="price"]').parent('div').hide();
					$('a.tab[id="tabTarif1"]').parent('div').hide();
				}
			});
	}
});
