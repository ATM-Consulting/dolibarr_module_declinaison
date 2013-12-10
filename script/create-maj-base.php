<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */
define('INC_FROM_CRON_SCRIPT', true);

require('../config.php');
require('../class/declinaison.class.php');

$PDOdb=new TPDOdb;
$PDOdb->db->debug=true;

$o=new TDeclinaison($db);
$o->init_db_by_vars($PDOdb);