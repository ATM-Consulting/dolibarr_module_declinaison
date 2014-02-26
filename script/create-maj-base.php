<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */
define('INC_FROM_CRON_SCRIPT', true);

require('../config.php');
dol_include_once('/declinaison/class/declinaison.class.php');

$PDOdb=new TPDOdb;
$PDOdb->db->debug=true;

$o=new TDeclinaison;
$o->init_db_by_vars($PDOdb);
