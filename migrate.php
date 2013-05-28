<?php
    function strip_selected_tag($text, $tags = array())
    {
        $cnt = 1;
        $args = func_get_args();
        $text = array_shift($args);
        $tags = func_num_args() > 2 ? array_diff($args,array($text))  : (array)$tags;
        foreach ($tags as $tag){
            if(preg_match_all('/<'.$tag.'[^>]*>(.*)<\/'.$tag.'>/iU', $text, $found)){
                $text = str_replace($found[0],$found[1],$text, $cnt);
            break;
          }
        }

        return $text;
    }
// Bootstrap Drupal
define('DRUPAL_ROOT', getcwd());
require 'includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

 $db_host = "localhost";
 $db_user = "ian_community";
 $db_pass = "cim89trd";
 $db_name = "kkiCMS";
 $link = mysql_connect($db_host, $db_user, $db_pass, $db_name);   
 $nodes_created = array();

if (!$link) 
{
   printf("Can't connect to MySQL Server. Errorcode: %s\n", mysql_error());
   exit;
}
mysql_select_db($db_name, $link);

//	$query = "SELECT * from blocks WHERE class_name='HtmlBlock' && block_status='PUBLISHED' && current_version IS NULL;	
//$query = "SELECT  block.body, page.custom_url from blocks as block, pages as page, connectors as conn 
//    WHERE (block.class_name='HtmlBlock' && block.block_status='PUBLISHED' && block.current_version IS NULL) && (page.id != 1550 && (page.page_status='PUBLISHED' || page.page_status='HIDDEN') && page.current_version IS NULL) && (conn.`block_id`=block.id && conn.`page_id`=page.id && conn.`container_name`='content' && conn.`date_removed` IS NULL)";
$query = "SELECT block.`id` AS block_id, block.body AS block, page.name AS page_name, sections.name AS section_name, page.id AS page_id, page.`custom_url`, page.created_date, block.last_updated, conn.`connector_order` as c_order 
    FROM blocks as block, pages as page, connectors as conn, sections
    WHERE (block_id > 3 && block.class_name='HtmlBlock' && block.block_status='PUBLISHED' && block.current_version IS NULL) && (page.id != 1550  && (page.page_status='PUBLISHED' || page.page_status='HIDDEN') && page.current_version IS NULL) && (conn.`block_id`=block.id && conn.`page_id`=page.id && conn.`container_name`='content' && conn.`date_removed` IS NULL)  && (sections.id = page.section_id)";
//	echo($query);
	$result = mysql_query($query, $link);
	if (!$result)
		die( " Trouble with mysql. Error is: ".mysql_error());
//    echo(mysql_num_rows($result));

    $cnt = 0; $cnt2=1; $cnt3=0;
    $new_tid = 1;
    $tags = array('h1','h2','h3');
    $unwanted_sections = array('system', 'Community', 'Newsletters', 'Events');
    while ($row = mysql_fetch_object($result))
    {
        $cnt3++;
//        if ($row->page_id != 5479)
//            continue;
        if (in_array($row->section_name, $unwanted_sections))
            continue;
        $block = $row->block;
        // remove spurious leading empty paragraphs
//        if (stripos($block,'<p') != 0)
//            $block = strip_selected_tag($block, array('p'));
        // content blocks start with a header tag
//        if (stripos($block,'<h') != 0)
//            continue;
        $node = new stdClass();
        $title = $row->page_name;
        foreach ($tags as $tag)
        {
            if (preg_match('/<'.$tag.'[^>]*>(.*)<\/'.$tag.'>/iU', $block, $found))
            {
                if (stripos($block,'<h') == 0)
                {
                    $block = str_replace($found[0],"",$block, $cnt2);     
                    $title = strip_tags($found[1]);
                }
                break; // just create one node, but need to check for any header level to be title

            }
        }
        $node->type = 'article';   // Your specified content type
        node_object_prepare($node);
        $node->title = html_entity_decode($title);

//                $node->body = $row->body;//substr($raw_data, strlen($node->title)+9);
        $node->language = LANGUAGE_NONE;

        $node->body[$node->language][0]['value']   = $block;
//        $node->body[$node->language][0]['summary'] = text_summary($block);
        $node->body[$node->language][0]['format']  = 'full_html';
                 
        $date = new DateTime($row->created_date);
        $node->created = $date->getTimestamp();
        $date = new DateTime($row->last_updated);
        $node->changed = $date->getTimestamp();
        $node->status = 1;
        $node->promote = 0;
        $node->sticky = 0;
        $node->comment = 0;
        $node->uid = 0;          // UID of content owner
         //       $node->path['pathauto']=1;
         //       $node->author = "anonymous";
//$node->field_tags[LANGUAGE_NONE][] = array (
//    'vid' => 1,
//    'tid' => 'autocreate',
//    'name' => $row->section_name,
//    'vocabulary_machine_name' => 'tags'
//  );
$item = array (
    'vid' => 1,
    'tid' => 'autocreate',
    'name' => $row->section_name,
    'vocabulary_machine_name' => 'tags'
  );
//    if( $item['tid'] == 'autocreate' ) {
//      $existing = taxonomy_get_term_by_name($item['name']);
//      if( isset($existing) ) {
//        $item['tid'] = $existing[380]->tid;
//      }
//      else {
//          $item['tid'] = $new_tid++;
//      }
//    }

          $tree = taxonomy_get_tree(1);
          if (isset($tree))
          {
    foreach ($tree as $term) {
      if ($term->name == $item['name']) {
        $item['tid'] = $term->tid;
      }
    }
          }
      $node->field_tags[LANGUAGE_NONE][] = $item;

        $path = 'cs/'.trim($row->custom_url, "/");
      
        if ($row->page_name == "Overview")
            $linkTitle = $row->section_name;
        else
            $linkTitle = $row->page_name;

//        if (in_array($row->page_id, $nodes_created))
        if ($row->c_order != 0)
        {
            $path = $path.'-order-'.$row->c_order;
            $linkTitle = $linkTitle.'-order-'.$row->c_order;
         }
        $node->path = array('alias' => $path);
       node_save($node);
     // Create a menu entry for our new node.
      $link = array();
      $link['menu_name'] = 'main-menu';
      $link['link_title'] = $linkTitle;
      $link['link_path'] = "node/$node->nid";
      $link['options']['attributes']['title'] = "Old Section: $row->section_name";
      menu_link_save($link);
//      $node->menu = $link;
//      $node->menu['link_title'] = $linkTitle;
      
      // a few pages are in their more than once but the secondary part has
      // a non-zero c_order, which will be dealt with the next time we hit the page
//       if ($row->c_order == 0)
//        $nodes_created[] = $row->page_id;
        
       
      $cnt++;
//                if ($cnt > 30)
//                    break;
    } // end while fetch
    echo '<br/>Import is finished. Rows returned is '.$cnt3.' Number imported is '.$cnt;

?>