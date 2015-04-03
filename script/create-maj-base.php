<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */

if(defined('INC_FROM_DOLIBARR'))dol_include_once('/declinaison/config.php');
else require('../config.php');

dol_include_once('/declinaison/class/declinaison.class.php');

$PDOdb=new TPDOdb;
//$PDOdb->db->debug=true;

$o=new TDeclinaison;
$o->init_db_by_vars($PDOdb);
