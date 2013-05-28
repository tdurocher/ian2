<?php
// Bootstrap Drupal
define('DRUPAL_ROOT', getcwd());
require 'includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

 $db_host = "localhost";
 $db_user = "ian_community";
 $db_pass = "cim89trd";
 $db_name = "kkiCMS";
 $link = mysql_connect($db_host, $db_user, $db_pass, $db_name);     

if (!$link) 
{
   printf("Can't connect to MySQL Server. Errorcode: %s\n", mysql_error());
   exit;
}
mysql_select_db($db_name, $link);

$nodes = node_load_multiple(array(), array('type' => 'page', 'type' => 'article'));
foreach ($nodes as $node) 
 node_delete($node->nid);    
 //        echo '<br/>Import is finished. Number imported is '.$cnt;
menu_delete_links('main-menu');
menu_delete_links('menu-main-menu-2');
$terms = taxonomy_get_tree(1);
foreach ($terms as $term)
    $res = taxonomy_term_delete($term->tid);

?>