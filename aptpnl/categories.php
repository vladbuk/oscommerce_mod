<?php
/*
  $Id: categories.php,v 1.146 2003/07/11 14:40:27 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');
  // include the breadcrumb class and start the breadcrumb trail
  require(DIR_WS_CLASSES . 'breadcrumb.php');
  $breadcrumb = new breadcrumb;

  $breadcrumb->add('Top', tep_href_link(FILENAME_CATEGORIES));

// add category names to the breadcrumb trail
  if (isset($cPath_array)) {
    for ($i=0, $n=sizeof($cPath_array); $i<$n; $i++) {
      $categories_query = tep_db_query("select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id = '" . (int)$cPath_array[$i] . "' and language_id = '" . (int)$languages_id . "'");
      if (tep_db_num_rows($categories_query) > 0) {
        $categories = tep_db_fetch_array($categories_query);
        $breadcrumb->add($categories['categories_name'], tep_href_link(FILENAME_CATEGORIES, 'cPath=' . implode('_', array_slice($cPath_array, 0, ($i+1)))));
      } else {
        break;
      }
    }
  }

  require_once (DIR_WS_FUNCTIONS . 'header_tags_catalog.php');
  require_once (DIR_WS_FUNCTIONS . 'seo_catalog.php');
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  $page = (isset($_GET['page']) ? $_GET['page'] : '1');



// BOF: KategorienAdmin / OLISWISS
  	$admin_access_query = tep_db_query("select admin_groups_id, admin_cat_access, admin_right_access from " . TABLE_ADMIN . " where admin_id=" . $login_id);
	$admin_access_array = tep_db_fetch_array($admin_access_query);
	$admin_groups_id = $admin_access_array['admin_groups_id'];
	$admin_cat_access = $admin_access_array['admin_cat_access'];
	$admin_cat_access_array_cats = explode(",",$admin_cat_access);
	$admin_right_access = $admin_access_array['admin_right_access'];
// EOF: KategorienAdmin / OLISWISS
    if (tep_not_null($action)) {
    // ULTIMATE Seo Urls 5 PRO by FWR Media
    // If the action will affect the cache entries
    if ( $action == 'insert' || $action == 'update' || $action == 'setflag' ) {
      tep_reset_cache_data_usu5( 'reset' );
    }
  
    switch ($action) {
      case 'setflag':
        if ( ($_GET['flag'] == '0') || ($_GET['flag'] == '1') ) {
          if (isset($_GET['pID'])) {
            tep_set_product_status($_GET['pID'], $_GET['flag']);
          }
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $_GET['cPath'] . '&pID=' . $_GET['pID']));
        break;

      case 'setflag_cat':
        if ( ($_GET['flag'] == '0') || ($_GET['flag'] == '1') ) {
          if (isset($_GET['cID'])) {
            tep_set_categories_status($_GET['cID'], $_GET['flag']);
          }
        }

	   tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $_GET['cPath'] . '&cID=' . $_GET['cID']));
	   break;

      case 'setxml':
        if ( ($_GET['flagxml'] == '0') || ($_GET['flagxml'] == '1') ) {
          if (isset($_GET['pID'])) {
            tep_set_product_xml($_GET['pID'], $_GET['flagxml']);
          }
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $_GET['cPath'] . '&pID=' . $_GET['pID']));
        break;

      case 'insert_category':
      case 'update_category':
        if (isset($_POST['categories_id'])) $categories_id = tep_db_prepare_input($_POST['categories_id']);
        $sort_order = tep_db_prepare_input($_POST['sort_order']);

        $categories_status = tep_db_prepare_input($_POST['categories_status']);
        $sql_data_array = array('sort_order' => (int)$sort_order, 'categories_status' => $categories_status);

        if ($action == 'insert_category') {
          $insert_sql_data = array('parent_id' => $current_category_id,
                                   'date_added' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

          tep_db_perform(TABLE_CATEGORIES, $sql_data_array);

          $categories_id = tep_db_insert_id();
// BOF: KategorienAdmin / OLI
	    if (in_array("ALL",$admin_cat_access_array_cats)== false) {
	    array_push($admin_cat_access_array_cats,$categories_id);
	    $admin_cat_access = implode(",",$admin_cat_access_array_cats);
        $sql_data_array = array('admin_cat_access' => tep_db_prepare_input($admin_cat_access));
        tep_db_perform(TABLE_ADMIN, $sql_data_array, 'update', 'admin_id = \'' . $login_id . '\'');
        }
// EOF: KategorienAdmin / OLI
        } elseif ($action == 'update_category') {
          $update_sql_data = array('last_modified' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $update_sql_data);

          tep_db_perform(TABLE_CATEGORIES, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "'");
        }

        $languages = tep_get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $categories_name_array = $_POST['categories_name'];
          $categories_seo_url_array = $_POST['categories_seo_url'];
          if (isset($_POST['categories_image']) && $_POST['categories_image']>'') $categories_image = $_POST['categories_image'];
          //HTC BOC
          $categories_htc_title_array = $_POST['categories_htc_title_tag'];
          $categories_htc_desc_array = $_POST['categories_htc_desc_tag'];
          $categories_htc_keywords_array = $_POST['categories_htc_keywords_tag'];
          //HTC EOC
          // - START - Category Descriptions
          $categories_heading_title_array = $_POST['categories_heading_title'];
          $categories_description_array = $_POST['categories_description'];
	      // --- END - Category Descriptions
          $language_id = $languages[$i]['id'];

          $sql_data_array = array('categories_name' => tep_db_prepare_input($categories_name_array[$language_id]),
           'categories_seo_url' => tep_db_prepare_input($categories_seo_url_array[$language_id]),
           //HTC BOC
           'categories_htc_title_tag' => (tep_not_null($categories_htc_title_array[$language_id]) ? tep_db_prepare_input($categories_htc_title_array[$language_id]) :  tep_db_prepare_input($categories_name_array[$language_id])),
           'categories_htc_desc_tag' => (tep_not_null($categories_htc_desc_array[$language_id]) ? tep_db_prepare_input($categories_htc_desc_array[$language_id]) :  tep_db_prepare_input($categories_name_array[$language_id])),
           'categories_htc_keywords_tag' => (tep_not_null($categories_htc_keywords_array[$language_id]) ? tep_db_prepare_input($categories_htc_keywords_array[$language_id]) :  tep_db_prepare_input($categories_name_array[$language_id])),
          //HTC EOC
          // - START - Category Descriptions
           'categories_heading_title' =>  tep_db_prepare_input($categories_heading_title_array[$language_id]),
           'categories_description' =>  tep_db_prepare_input($categories_description_array[$language_id]));
          // --- END - Category Descriptions

          if ($action == 'insert_category') {
            $insert_sql_data = array('categories_id' => $categories_id,
                                     'language_id' => $languages[$i]['id']);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array);
          } elseif ($action == 'update_category') {
            tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'");
          }
        }

		if ((isset($categories_image)) && (tep_not_null($categories_image))) tep_db_query("update " . TABLE_CATEGORIES . " set categories_image = '" . tep_db_input($categories_image) . "' where categories_id = '" . (int)$categories_id . "'");

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $categories_id));
        break;
        case 'delete_category_confirm':
        if (isset($_POST['categories_id'])) {
          $categories_id = tep_db_prepare_input($_POST['categories_id']);

          $categories = tep_get_category_tree($categories_id, '', '0', '', '',true);

          $products = array();
          $products_delete = array();

          for ($i=0, $n=sizeof($categories); $i<$n; $i++) {
            $product_ids_query = tep_db_query("select products_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where categories_id = '" . (int)$categories[$i]['id'] . "'");

            while ($product_ids = tep_db_fetch_array($product_ids_query)) {
              $products[$product_ids['products_id']]['categories'][] = $categories[$i]['id'];
            }
          }

          reset($products);
          while (list($key, $value) = each($products)) {
            $category_ids = '';

            for ($i=0, $n=sizeof($value['categories']); $i<$n; $i++) {
              $category_ids .= "'" . (int)$value['categories'][$i] . "', ";
            }
            $category_ids = substr($category_ids, 0, -2);

            $check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$key . "' and categories_id not in (" . $category_ids . ")");
            $check = tep_db_fetch_array($check_query);
            if ($check['total'] < '1') {
              $products_delete[$key] = $key;
            }
          }

// removing categories can be a lengthy process
          tep_set_time_limit(0);
          for ($i=0, $n=sizeof($categories); $i<$n; $i++) {
            tep_remove_category($categories[$i]['id']);
          }

          reset($products_delete);
          while (list($key) = each($products_delete)) {
            tep_remove_product($key);
          }
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath));
        break;

      case 'delete_product_confirm':
        if (isset($_POST['products_id']) && isset($_POST['product_categories']) && is_array($_POST['product_categories'])) {
          $product_id = tep_db_prepare_input($_POST['products_id']);
          $product_categories = $_POST['product_categories'];

          for ($i=0, $n=sizeof($product_categories); $i<$n; $i++) {
            tep_db_query("delete from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "' and categories_id = '" . (int)$product_categories[$i] . "'");
          }

          $product_categories_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "'");
          $product_categories = tep_db_fetch_array($product_categories_query);

          if ($product_categories['total'] == '0') {
            tep_remove_product($product_id);
            // START: Extra Fields Contribution
            tep_db_query("delete from " . TABLE_PRODUCTS_TO_PRODUCTS_EXTRA_FIELDS . " where products_id = " . (int)$product_id);
            // END: Extra Fields Contribution
          }
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $cPath));
        break;

      case 'move_category_confirm':
        if (isset($_POST['categories_id']) && ($_POST['categories_id'] != $_POST['move_to_category_id'])) {
          $categories_id = tep_db_prepare_input($_POST['categories_id']);
          $new_parent_id = tep_db_prepare_input($_POST['move_to_category_id']);

          $path = explode('_', tep_get_generated_category_path_ids($new_parent_id));

          if (in_array($categories_id, $path)) {
            $messageStack->add_session(ERROR_CANNOT_MOVE_CATEGORY_TO_PARENT, 'error');

            tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $categories_id));
          } else {
            tep_db_query("update " . TABLE_CATEGORIES . " set parent_id = '" . (int)$new_parent_id . "', last_modified = now() where categories_id = '" . (int)$categories_id . "'");

            tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $new_parent_id . '&cID=' . $categories_id));
          }
        }

        break;

      case 'move_product_confirm':
        $products_id = tep_db_prepare_input($_POST['products_id']);
        $new_parent_id = tep_db_prepare_input($_POST['move_to_category_id']);

        $duplicate_check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$new_parent_id . "'");
        $duplicate_check = tep_db_fetch_array($duplicate_check_query);
        if ($duplicate_check['total'] < 1) tep_db_query("update " . TABLE_PRODUCTS_TO_CATEGORIES . " set categories_id = '" . (int)$new_parent_id . "' where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$current_category_id . "'");

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $new_parent_id . '&pID=' . $products_id));
        break;

      case 'insert_product':
      case 'update_product':
        if (isset($_POST['edit_x']) || isset($_POST['edit_y'])) {
          $action = 'new_product';
        } else {
          if (isset($_GET['pID'])) $products_id = tep_db_prepare_input($_GET['pID']);
          $products_date_available = tep_db_prepare_input($_POST['products_date_available']);
          $products_sort_order = tep_db_prepare_input($_POST['products_sort_order']);
          $products_date_available = (date('Y-m-d') < $products_date_available) ? $products_date_available : 'null';
          $products_sort_order = (isset($_POST['products_sort_order']) && tep_not_null($_POST['products_sort_order'])) ? $products_sort_order : 'null';

          $sql_data_array = array('products_quantity' => (int)tep_db_prepare_input($_POST['products_quantity']),
                                  'products_model' => tep_db_prepare_input($_POST['products_model']),
                                  'products_price' => tep_db_prepare_input($_POST['products_price']),
                                  'products_date_available' => $products_date_available,
                                  'products_weight' => (float)tep_db_prepare_input($_POST['products_weight']),
                                  'products_height' => tep_db_prepare_input($_POST['products_height']),
	                              'products_length' => tep_db_prepare_input($_POST['products_length']),
	                              'products_width' => tep_db_prepare_input($_POST['products_width']),
	                              'products_ready_to_ship' => tep_db_prepare_input($_POST['products_ready_to_ship']),
                                  'products_status' => tep_db_prepare_input($_POST['products_status']),
                                  'products_to_xml' => tep_db_prepare_input($_POST['products_to_xml']),
								  'products_sort_order' => $products_sort_order,
                                  'products_tax_class_id' => tep_db_prepare_input($_POST['products_tax_class_id']),
                                  'minorder' => tep_db_prepare_input($_POST['minorder']),
                                  'manufacturers_id' => (int)tep_db_prepare_input($_POST['manufacturers_id']));

          //TotalB2B start
		  $prices_num = tep_xppp_getpricesnum();
          for ($i=2; $i<=$prices_num; $i++) {
              if (tep_db_prepare_input($_POST['checkbox_products_price_' . $i]) != "true")
			  $sql_data_array['products_price_' . $i] = 'null';
              else
			  $sql_data_array['products_price_' . $i] = tep_db_prepare_input($_POST['products_price_' . $i]);
	      }
         //TotalB2B end

          if (isset($_POST['products_image']) && tep_not_null($_POST['products_image']) && ($_POST['products_image'] != 'none')) {
            $sql_data_array['products_image'] = tep_db_prepare_input($_POST['products_image']);
          }
          if (isset($_POST['delete_file']) && ($_POST['delete_file'] == 'on')) {
              $file_query = tep_db_query("select products_pdfupload from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
              $file = tep_db_fetch_array($file_query);
              $file_location = DIR_FS_CATALOG_PDFDOCS . $file['products_pdfupload'];
              if (file_exists($file_location)) @unlink($file_location);
              $sql_data_array['products_pdfupload'] = 'null';
          }
          if (isset($_POST['products_pdfupload']) && tep_not_null($_POST['products_pdfupload']) && ($_POST['products_pdfupload'] != 'none') && !isset($_POST['delete_file']) && ($_POST['delete_file'] != 'on')) {
            $sql_data_array['products_pdfupload'] = tep_db_prepare_input($_POST['products_pdfupload']);
          }
          if ($action == 'insert_product') {
            $insert_sql_data = array('products_date_added' => 'now()');

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
            $products_id = tep_db_insert_id();

            tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$products_id . "', '" . (int)$current_category_id . "')");
          } elseif ($action == 'update_product') {
            $update_sql_data = array('products_last_modified' => 'now()');

            $sql_data_array = array_merge($sql_data_array, $update_sql_data);

            tep_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "'");
          }

          /** osc@kangaroopartners.com - AJAX Attribute Manager  **/
          require_once('attributeManager/includes/attributeManagerUpdateAtomic.inc.php');
          /** osc@kangaroopartners.com - AJAX Attribute Manager  end **/

          $languages = tep_get_languages();
          for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $language_id = $languages[$i]['id'];

           //HTC BOC
            $sql_data_array = array('products_name' => tep_db_prepare_input($_POST['products_name'][$language_id]),
                                    'products_description' => tep_db_prepare_input($_POST['products_description'][$language_id]),
                                    'products_url' => tep_db_prepare_input($_POST['products_url'][$language_id]),
                                    'products_seo_url' => tep_db_prepare_input($_POST['products_seo_url'][$language_id]),
                                    'products_head_title_tag' => ((tep_not_null($_POST['products_head_title_tag'][$language_id])) ? tep_db_prepare_input($_POST['products_head_title_tag'][$language_id]) : tep_db_prepare_input($_POST['products_name'][$language_id])),
                                    'products_head_desc_tag' => ((tep_not_null($_POST['products_head_desc_tag'][$language_id])) ? tep_db_prepare_input($_POST['products_head_desc_tag'][$language_id]) : tep_db_prepare_input($_POST['products_name'][$language_id])),
                                    'products_head_keywords_tag' => ((tep_not_null($_POST['products_head_keywords_tag'][$language_id])) ? tep_db_prepare_input($_POST['products_head_keywords_tag'][$language_id]) : tep_db_prepare_input($_POST['products_name'][$language_id])));
           //HTC EOC

            if ($action == 'insert_product') {
              $insert_sql_data = array('products_id' => $products_id,
                                       'language_id' => $language_id);

              $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

              tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array);
            } elseif ($action == 'update_product') {
              tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "' and language_id = '" . (int)$language_id . "'");
            }
          }
           // START: Extra Fields Contribution
          $extra_fields_query = tep_db_query("SELECT * FROM " . TABLE_PRODUCTS_TO_PRODUCTS_EXTRA_FIELDS . " WHERE products_id = " . (int)$products_id);
          while ($products_extra_fields = tep_db_fetch_array($extra_fields_query)) {
            $extra_product_entry[$products_extra_fields['products_extra_fields_id']] = $products_extra_fields['products_extra_fields_value'];
          }

          if ($_POST['extra_field']) { // Check to see if there are any need to update extra fields (fix).
            foreach ($_POST['extra_field'] as $key=>$val) {
              if (isset($extra_product_entry[$key])) { // an entry exists
                if ($val == '') tep_db_query("DELETE FROM " . products_to_products_extra_fields . " where products_id = " . (int)$products_id . " AND  products_extra_fields_id = " . $key);
                else tep_db_query("UPDATE " . products_to_products_extra_fields . " SET products_extra_fields_value = '" . tep_db_input($val) . "' WHERE products_id = " . (int)$products_id . " AND  products_extra_fields_id = " . $key);
              }
              else { // an entry does not exist
	            if ($val != '') tep_db_query("INSERT INTO " . products_to_products_extra_fields . " (products_id, products_extra_fields_id, products_extra_fields_value) VALUES ('" . (int)$products_id . "', '" . $key . "', '" . tep_db_input($val) . "')");
              }
            }
          } // Check to see if there are any need to update extra fields.
          // END: Extra Fields Contribution
          tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products_id));
        }
        break;

      case 'copy_to_confirm':
        if (isset($_POST['products_id']) && isset($_POST['categories_id'])) {
          $products_id = tep_db_prepare_input($_POST['products_id']);
          $categories_id = tep_db_prepare_input($_POST['categories_id']);

          if ($_POST['copy_as'] == 'link') {
            if ($categories_id != $current_category_id) {
              $check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$categories_id . "'");
              $check = tep_db_fetch_array($check_query);
              if ($check['total'] < '1') {
                tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$products_id . "', '" . (int)$categories_id . "')");
              }
            } else {
              $messageStack->add_session(ERROR_CANNOT_LINK_TO_SAME_CATEGORY, 'error');
            }
          } elseif ($_POST['copy_as'] == 'duplicate') {

            //TotalB2B start
			$products_price_list = tep_xppp_getpricelist("");
            $product_query = tep_db_query("select products_quantity, products_pdfupload, products_model, products_image, ". $products_price_list . ", products_date_available, products_weight, products_length, products_width, products_height, products_ready_to_ship, products_tax_class_id, manufacturers_id, products_sort_order from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
            //TotalB2B end

            $product = tep_db_fetch_array($product_query);

            //TotalB2B start
            $prices_num = tep_xppp_getpricesnum();
			for($i=2; $i<=$prices_num; $i++) {
			   if ($product['products_price_' . $i] == NULL) $products_instval .= "NULL, ";
			   else $products_instval .= "'" . tep_db_input($product['products_price_' . $i]) . "', ";
			}
			$products_instval .= "'" . tep_db_input($product['products_price']) . "' ";
            tep_db_query("insert into " . TABLE_PRODUCTS . " (products_quantity,products_pdfupload, products_model, products_image, ". $products_price_list . ", products_date_added, products_date_available, products_weight, products_length, products_width, products_height, products_ready_to_ship, products_status, products_tax_class_id, manufacturers_id,  products_sort_order) values ('" . tep_db_input($product['products_quantity']) . "', '" . tep_db_input($product['products_pdfupload']) . "', '" . tep_db_input($product['products_model']) . "', '" . tep_db_input($product['products_image']) . "', " . $products_instval . ",  now(), '" . tep_db_input($product['products_date_available']) . "', '" . tep_db_input($product['products_weight']) . "', '" . $product['products_length'] . "', '" . $product['products_width'] . "', '" . $product['products_height']. "', '" . $product['products_ready_to_ship'] . "','0', '" . (int)$product['products_tax_class_id'] . "', '" . (int)$product['manufacturers_id'] . "', '" . (int)$product['products_sort_order'] . "')");
            //TotalB2B end

            $dup_products_id = tep_db_insert_id();

           //HTC BOC
            $description_query = tep_db_query("select language_id, products_name, products_seo_url, products_description, products_head_title_tag, products_head_desc_tag, products_head_keywords_tag, products_url from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int)$products_id . "'");
            while ($description = tep_db_fetch_array($description_query)) {
              tep_db_query("insert into " . TABLE_PRODUCTS_DESCRIPTION . " (products_id, language_id, products_name, products_seo_url, products_description, products_head_title_tag, products_head_desc_tag, products_head_keywords_tag, products_url, products_viewed) values ('" . (int)$dup_products_id . "', '" . (int)$description['language_id'] . "', '" . tep_db_input($description['products_name']) . "', '" . tep_db_input($description['products_seo_url']) . "','" . tep_db_input($description['products_description']) . "', '" . tep_db_input($description['products_head_title_tag']) . "', '" . tep_db_input($description['products_head_desc_tag']) . "', '" . tep_db_input($description['products_head_keywords_tag']) . "', '" . tep_db_input($description['products_url']) . "', '0')");
            }
           //HTC EOC

            tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$dup_products_id . "', '" . (int)$categories_id . "')");
            $products_id = $dup_products_id;
          }
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $categories_id . '&pID=' . $products_id));
        break;

      case 'new_product_preview':
// copy image only if modified
//        $products_image = new upload('products_image');
        $products_image = $_POST['products_image'];
        if ($products_image==''){
	        $products_image = $_POST['products_previous_image'];
        }

//        $products_image->set_destination(DIR_FS_CATALOG_IMAGES);
//        if ($products_image->parse() && $products_image->save()) {
//		  $products_image_name = $products_image->filename;
		$products_image_name = $products_image  ;
//        } else {
//          $products_image_name = (isset($_POST['products_previous_image']) ? $_POST['products_previous_image'] : '');
//        }
        $products_pdfupload = new upload('products_pdfupload');
        $products_pdfupload->set_destination(DIR_FS_CATALOG_PDFDOCS);

        if ($products_pdfupload->parse() && $products_pdfupload->save()) {
          $products_pdfupload_name = $products_pdfupload->filename;
        } else {
          $products_pdfupload_name = (isset($_POST['products_previous_pdfupload']) ? $_POST['products_previous_pdfupload'] : '');
        }
        break;
    }
  }

// check if the catalog image directory exists
  if (is_dir(DIR_FS_CATALOG_IMAGES)) {
    if (!is_writeable(DIR_FS_CATALOG_IMAGES)) $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
  } else {
    $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
  }

  $query_lang = tep_db_query("SELECT code FROM ". TABLE_LANGUAGES ." WHERE languages_id='" . (int)$languages_id . "'");
  $lang = tep_db_fetch_array($query_lang);
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="includes/general.js"></script>
<link type="text/css" rel="stylesheet" href="includes/javascript/tabpane/css/luna/tab.css">
<script type="text/javascript" src="includes/javascript/tabpane/js/tabpane.js"></script>
<script type="text/javascript">
<!--
function chooseThat(whatForm,whatField) {
	window.open('<?php echo FILENAME_IMAGE_MANAGER;?>' + '?whatForm='+whatForm+'&whatField='+whatField, '_Addimage', 'HEIGHT=500,resizable=yes,scrollbars=yes, width=700');
}
-->
</script>
<!-- osc@kangaroopartners.com - AJAX Attribute Manager  -->
<?php require_once('attributeManager/includes/attributeManagerHeader.inc.php');?>
<!-- osc@kangaroopartners.com - AJAX Attribute Manager  end -->
<!--START tinyMCE//-->
<?php //include "includes/javascript/tiny_mce/tiny_mce.inc.php";?>
<script type="text/javascript" src="ckeditor/ckeditor.js"></script>
<script type="text/javascript"><!--
function popupPropertiesWindow(url) {
  window.open(url,'popupPropertiesWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=800,height=600,screenX=112,screenY=112,top=70,left=112')
}
//--></script>
<?php
  if ( ($action == 'new_product') ) {
?>
<style type="text/css">@import url('includes/javascript/jscalendar/calendar-win2k-1.css');</style>
<script type="text/javascript" src="includes/javascript/jscalendar/calendar.js"></script>
<script type="text/javascript" src="includes/javascript/jscalendar/lang/calendar-<?php echo $lang['code'];?>.js"></script>
<script type="text/javascript" src="includes/javascript/jscalendar/calendar-setup.js"></script>
<?php
  }
?>
</head>
<body onLoad="goOnLoad();">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->
<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>

  <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
      <!-- left_navigation //-->
      <?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
      <!-- left_navigation_eof //-->
    </table></td>
  <!-- body_text //-->
  <td width="100%" valign="top">

  <?php
  if ($action == 'new_product') {
    $parameters = array('products_name' => '',
                       'products_description' => '',
                       'products_url' => '',
                       'products_seo_url' => '',
                       'products_id' => '',
                       'products_quantity' => '',
                       'products_model' => '',
                       'products_image' => '',
                       'products_price' => '',
                       'products_weight' => '',
                       'products_length' => '',
                       'products_width' => '',
                       'products_height' => '',
                       'products_ready_to_ship' => '',
                       'products_date_added' => '',
                       'products_last_modified' => '',
                       'products_date_available' => '',
                       'products_status' => '',
                       'products_to_xml' => '',
                       'products_tax_class_id' => '',
                       'minorder' => '',
                       'manufacturers_id' => '',
                       'products_sort_order' => '' );

    //TotalB2B start
	$prices_num = tep_xppp_getpricesnum();
    for ($i=2; $i<=$prices_num; $i++) {
	  $parameters['products_price_' . $i] = '';
	}
    //TotalB2B start

    $pInfo = new objectInfo($parameters);

   //HTC BOC
    if (isset ($_GET['pID']) && (!$_POST) ) {

      //TotalB2B start
      $products_price_list = tep_xppp_getpricelist("p");
      $product_query = tep_db_query("select pd.products_name, pd.products_seo_url, pd.products_description, pd.products_url, p.products_id, p.products_quantity,pd.products_head_title_tag, pd.products_head_desc_tag, pd.products_head_keywords_tag, p.products_model, p.products_image, p.products_pdfupload, " . $products_price_list . ", p.products_weight, products_length, products_width, products_height, products_ready_to_ship, p.products_date_added, p.products_last_modified, date_format(p.products_date_available, '%Y-%m-%d') as products_date_available, p.products_status,  p.products_to_xml, p.products_tax_class_id, p.manufacturers_id, p.minorder, p.products_sort_order from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$_GET['pID'] . "' and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'");
      //TotalB2B end

      $product = tep_db_fetch_array($product_query);
   //HTC EOC
      // START: Extra Fields Contribution
      $products_extra_fields_query = tep_db_query("SELECT * FROM " . TABLE_PRODUCTS_TO_PRODUCTS_EXTRA_FIELDS . " WHERE products_id=" . (int)$_GET['pID']);
      while ($products_extra_fields = tep_db_fetch_array($products_extra_fields_query)) {
        $extra_field[$products_extra_fields['products_extra_fields_id']] = $products_extra_fields['products_extra_fields_value'];
      }
	  $extra_field_array=array('extra_field'=>$extra_field);
	  $pInfo->objectInfo($extra_field_array);
      // END: Extra Fields Contribution

      $pInfo->objectInfo($product);
    } elseif (tep_not_null($_POST)) {
      $pInfo->objectInfo($_POST);
      $products_name = $_POST['products_name'];
      $products_description = $_POST['products_description'];
      $products_head_title_tag = $_POST['products_head_title_tag'];
      $products_head_desc_tag = $_POST['products_head_desc_tag'];
      $products_head_keywords_tag = $_POST['products_head_keywords_tag'];
      $products_url = $_POST['products_url'];
      $products_seo_url = $_POST['products_seo_url'];
      $products_sort_order = $_POST['products_sort_order'];
    }

    $manufacturers_array = array(array('id' => '', 'text' => TEXT_NONE));
    $manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS_INFO . " where languages_id = " . (int)$languages_id . " order by manufacturers_name");
    while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
      $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'],
                                     'text' => $manufacturers['manufacturers_name']);
    }

    $tax_class_array = array(array('id' => '0', 'text' => TEXT_NONE));
    $tax_class_query = tep_db_query("select tax_class_id, tax_class_title from " . TABLE_TAX_CLASS . " order by tax_class_title");
    while ($tax_class = tep_db_fetch_array($tax_class_query)) {
      $tax_class_array[] = array('id' => $tax_class['tax_class_id'],
                                 'text' => $tax_class['tax_class_title']);
    }

    $languages = tep_get_languages();

    if (!isset($pInfo->products_status)) $pInfo->products_status = '1';
    switch ($pInfo->products_status) {
      case '0': $in_status = false; $out_status = true; break;
      case '1':
      default: $in_status = true; $out_status = false;
    }

    if (!isset($pInfo->products_to_xml)) $pInfo->products_to_xml = '1';
    switch ($pInfo->products_to_xml) {
      case '0': $in_xml = false; $out_xml = true; break;
      case '1':
      default: $in_xml = false; $out_xml = true;
    }
?>
  <script language="javascript"><!--
var tax_rates = new Array();
<?php
    for ($i=0, $n=sizeof($tax_class_array); $i<$n; $i++) {
      if ($tax_class_array[$i]['id'] > 0) {
        echo 'tax_rates["' . $tax_class_array[$i]['id'] . '"] = ' . tep_get_tax_rate_value($tax_class_array[$i]['id']) . ';' . "\n";
      }
    }
?>

function doRound(x, places) {
  return Math.round(x * Math.pow(10, places)) / Math.pow(10, places);
}

function getTaxRate() {
  var selected_value = document.forms["new_product"].products_tax_class_id.selectedIndex;
  var parameterVal = document.forms["new_product"].products_tax_class_id[selected_value].value;

  if ( (parameterVal > 0) && (tax_rates[parameterVal] > 0) ) {
    return tax_rates[parameterVal];
  } else {
    return 0;
  }
}

//TotalB2B start
function updateGross(products_price_t) {
  var taxRate = getTaxRate(products_price_t);

  var grossValue = document.forms["new_product"].elements[products_price_t].value;

  if (taxRate > 0) {
    grossValue = grossValue * ((taxRate / 100) + 1);
  }

  var products_price_gross_t = products_price_t + "_gross";

  document.forms["new_product"].elements[products_price_gross_t].value = doRound(grossValue, 4);
}

function updateNet(products_price_t) {
  var taxRate = getTaxRate();
  var products_price_gross_t = products_price_t + "_gross";
  var netValue = document.forms["new_product"].elements[products_price_gross_t].value;

  if (taxRate > 0) {
    netValue = netValue / ((taxRate / 100) + 1);
  }

  document.forms["new_product"].elements[products_price_t].value = doRound(netValue, 4);
}
//TotalB2B end

//--></script>
<?php
    if ($_GET['search']) {
     $trueEditPath1 =  'page='.$page. '&search=' . $_GET['search'];
    }else{
     $trueEditPath1 =  'page='.$page.'&cPath=' . $cPath;
    }
    echo tep_draw_form('new_product', FILENAME_CATEGORIES, $trueEditPath1 . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '') . '&action=new_product_preview', 'post', 'enctype="multipart/form-data"'); ?>
  <table border="0" width="100%" cellspacing="0" cellpadding="2">
    <tr>
      <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo sprintf(TEXT_NEW_PRODUCT, tep_output_generated_category_path($current_category_id)); ?></td>
            <td class="pageHeading" align="right"><?php echo tep_href_manual(FILENAME_CATEGORIES); ?></td>
          </tr>
        </table></td>
    </tr>
    <tr>
      <td class="main" align="right"><?php echo tep_draw_hidden_field('products_date_added', (tep_not_null($pInfo->products_date_added) ? $pInfo->products_date_added : date('Y-m-d'))) . tep_image_submit('button_preview.gif', IMAGE_PREVIEW) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, $trueEditPath1 . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
    </tr>
    <tr>

    <td>
    <div class="tab-pane" id="tabPane">
      <script type="text/javascript">tp1 = new WebFXTabPane( document.getElementById( "tabPane" ) );</script>
      <div class="tab-page" id="General">
        <h2 class="tab"><?php echo TEXT_TAB_GENERAL; ?></h2>
        <script type="text/javascript">tp1.addTabPage( document.getElementById( "General" ) );</script>
        <table>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_STATUS; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_radio_field('products_status', '1', $in_status) . '&nbsp;' . TEXT_PRODUCT_AVAILABLE . '&nbsp;' . tep_draw_radio_field('products_status', '0', $out_status) . '&nbsp;' . TEXT_PRODUCT_NOT_AVAILABLE; ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_DATE_AVAILABLE; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;'; ?>
              <?php echo tep_draw_input_field('products_date_available', $pInfo->products_date_available,'size="10" id="products_date_available"'); ?><?php echo tep_image(DIR_WS_ICONS . 'calendar.gif', 'Calendar', '', '', 'id="calendarTrigger"'); ?>
             <script type="text/javascript">Calendar.setup( { inputField: "products_date_available", ifFormat: "%Y/%m/%d", button:"calendarTrigger" } );</script>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_MANUFACTURER; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('manufacturers_id', $manufacturers_array, $pInfo->manufacturers_id); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_TO_XML; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_radio_field('products_to_xml', '1', $out_xml) . '&nbsp;' . TEXT_PRODUCT_AVAILABLE_TO_XML . '&nbsp;' . tep_draw_radio_field('products_to_xml', '0', $in_xml) . '&nbsp;' . TEXT_PRODUCT_NOT_AVAILABLE_TO_XML; ?></td>
			</tr>
          <tr>
            <td class="main"><?php echo TEXT_EDIT_SORT_ORDER; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_sort_order', $pInfo->products_sort_order, 'size="2"'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_QUANTITY; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_quantity', $pInfo->products_quantity); ?></td>
          </tr>
<?php
          if (MINIMUM_ORDERS == 'true') {
?>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_MINIMUM; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('minorder', $pInfo->minorder); ?></td>
          </tr>
<?php
          }
?>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_MODEL; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_model', $pInfo->products_model); ?></td>
          </tr>
          <?php
// START: Extra Fields Contribution (chapter 1.4)
      // Sort language by ID
	  for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
	    $languages_array[$languages[$i]['id']]=$languages[$i];
	  }
      $extra_fields_query = tep_db_query("SELECT * FROM " . TABLE_PRODUCTS_EXTRA_FIELDS . " ORDER BY products_extra_fields_order");
      while ($extra_fields = tep_db_fetch_array($extra_fields_query)) {
	  // Display language icon or blank space
        if ($extra_fields['languages_id']==0) {
	      $m=tep_draw_separator('pixel_trans.gif', '24', '15');
	    } else $m= tep_image(DIR_WS_CATALOG_LANGUAGES . $languages_array[$extra_fields['languages_id']]['directory'] . '/images/' . $languages_array[$extra_fields['languages_id']]['image'], $languages_array[$extra_fields['languages_id']]['name']);
?>
          <tr>
            <td class="main"><?php echo $extra_fields['products_extra_fields_name']; ?>:</td>
            <td class="main"><?php echo $m . '&nbsp;' . tep_draw_input_field("extra_field[".$extra_fields['products_extra_fields_id']."]", $pInfo->extra_field[$extra_fields['products_extra_fields_id']]); ?></td>
          </tr>
          <?php
}
// END: Extra Fields Contribution
?>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_PRODUCTS_IMAGE; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '5') . '&nbsp;' . tep_draw_input_field('products_image') .'<a href="Javascript:chooseThat(\'new_product\',\'products_image\')">' . tep_image_button('button_insert.gif', IMAGE_INSERT) . '</a>' .'<br>' . tep_draw_separator('pixel_trans.gif', '24', '5') . '&nbsp;/images/' . $pInfo->products_image . tep_draw_hidden_field('products_previous_image', $pInfo->products_image); ?></td>
            <!--//<td class="main"><?php //echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_file_field('products_image') . '<br>' . tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . $pInfo->products_image . tep_draw_hidden_field('products_previous_image', $pInfo->products_image); ?></td>//-->
          </tr>
          <tr>
             <td class="main" valign="top"><?php echo TEXT_PRODUCTS_PDFUPLOAD; ?></td>
             <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '5') . '&nbsp;' . tep_draw_file_field('products_pdfupload') . '<br>' . tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;/docs/' . $pInfo->products_pdfupload . tep_draw_hidden_field('products_previous_pdfupload', $pInfo->products_pdfupload) . ' &nbsp; ' . (tep_not_null($pInfo->products_pdfupload) ? tep_draw_checkbox_field('delete_file', '', false) . ' ' . TEXT_DELETE_FILE : '');?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_WEIGHT; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '5') . '&nbsp;' . tep_draw_input_field('products_weight', $pInfo->products_weight); ?></td>
          </tr>
          <?php
          if (MODULE_SHIPPING_UPSXML_RATES_STATUS == 'True') {
?>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_LENGTH; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '5') . '&nbsp;' . tep_draw_input_field('products_length', $pInfo->products_length); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_WIDTH; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '5') . '&nbsp;' . tep_draw_input_field('products_width', $pInfo->products_width); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_HEIGHT; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '5') . '&nbsp;' . tep_draw_input_field('products_height', $pInfo->products_height); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_READY_TO_SHIP; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '5') . '&nbsp;' . tep_draw_checkbox_field('products_ready_to_ship', '1', (($product['products_ready_to_ship'] == '1') ? true : false)); ?></td>
          </tr>
          <?php


         if (isset($pInfo->products_id) && tep_not_null($pInfo->products_id)) {
?>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_SPLIT_PRODUCT; ?></td>
            <?php $check_split_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_SPLIT . " where products_id = '" . $pInfo->products_id . "'");
      $check_split = tep_db_fetch_array($check_split_query);
?>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '5') . '&nbsp;' . ($check_split['total'] < 1 ? 'no' : $check_split['total']); ?>&nbsp;&nbsp;<?php echo '<a href="javascript:void(0)" onmouseover="window.status=\'' . TEXT_MOUSE_OVER_SPLIT_PRODUCTS . '\';return true;" onmouseout="window.status=\'\'; return true;" onclick="window.open(\'' . tep_href_link(FILENAME_SPLIT_PRODUCT, 'pid=' . $pInfo->products_id, 'NONSSL') . '\',\'' . NAME_WINDOW_SPLIT_PRODUCTS_POPUP . '\',\'menubar=yes,resizable=yes,scrollbars=yes,status=no,location=no,width=650,height=350\');return false">' . tep_image_button('button_edit.gif', IMAGE_UPDATE, 'style="vertical-align: middle;"'); ?></a></td>
          </tr>
          <?php
         } // end if (isset($pInfo->products_id)) ...
         }
?>
          <tr>
            <td>&nbsp;</td>
            <td class="main"><?php echo TEXT_PRODUCTS_WEIGHT_NULL;?> </td>
          </tr>
        </table>
      </div>
      <div class="tab-page" id="Price">
        <h2 class="tab"><?php echo TEXT_TAB_PRICE; ?></h2>
        <script type="text/javascript">tp1.addTabPage( document.getElementById( "Price" ) );</script>
        <table>
          <!--TotalB2B start-->
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_TAX_CLASS; ?></td>
            <td class="main"><?php
			  $prices_num = tep_xppp_getpricesnum();
		      $gross_update = 'updateGross(\'products_price\');';
              for ($i=2; $i<=$prices_num; $i++)
				  $gross_update .= 'updateGross(\'products_price_'. $i . '\');';
		      echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('products_tax_class_id', $tax_class_array, $pInfo->products_tax_class_id, 'onchange="' . $gross_update .'"'); ?></td>
          </tr>
          <tr>
            <td class="main" colspan="2"><br>
              <?php echo ENTRY_PRODUCTS_PRICE . " 1";?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_PRICE_NET; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_price', $pInfo->products_price, 'onKeyUp="updateGross(\'products_price\')"'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_PRICE_GROSS; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_price_gross', $pInfo->products_price, 'OnKeyUp="updateNet(\'products_price\')"'); ?></td>
          </tr>
          <?php
			  $prices_num = tep_xppp_getpricesnum();
              for ($i=2; $i<=$prices_num; $i++) {?>
          <tr>
            <td class="main" colspan="2"><br>
              <?php echo ENTRY_PRODUCTS_PRICE . " " . $i;?>&nbsp;
              <input type="checkbox" name="<?php echo "checkbox_products_price_" . $i;?>" <?php
			    $products_price_X = "products_price_" . $i;
			    if ($pInfo->$products_price_X != NULL) echo " checked "; ?> value="true" onClick="if (!<?php echo "products_price_" . $i;?>.disabled) { <?php echo "products_price_" . $i;?>.disabled = true;  <?php echo "products_price_". $i . "_gross";?>.disabled = true; } else { <?php echo "products_price_" . $i;?>.disabled = false;  <?php echo "products_price_". $i . "_gross";?>.disabled = false; } "></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_PRICE_NET; ?></td>
            <td class="main"><?php
				$products_price_X = "products_price_" . $i;
			    if ($pInfo->$products_price_X == NULL) {
				  echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_price_' . $i, $pInfo->$products_price_X, 'onKeyUp="updateGross(\'products_price_' . $i .'\')", disabled');
				} else {
				  echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_price_' . $i, $pInfo->$products_price_X, 'onKeyUp="updateGross(\'products_price_' . $i .'\')"');
				} ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_PRICE_GROSS; ?></td>
            <td class="main"><?php
				$products_price_X = "products_price_" . $i;
			    if ($pInfo->$products_price_X == NULL) {
				  echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_price_'. $i . '_gross', $pInfo->$products_price_X, 'OnKeyUp="updateNet(\'products_price_' . $i .'\')", disabled');
				} else {
				  echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_price_'. $i . '_gross', $pInfo->$products_price_X, 'OnKeyUp="updateNet(\'products_price_' . $i .'\')"');
				} ?>
            </td>
          </tr>
          <?php } ?>
          <script language="javascript">
updateGross('products_price');
<?php
    $prices_num = tep_xppp_getpricesnum();
    for ($i=2; $i<=$prices_num; $i++) echo 'updateGross(\'products_price_' . $i . '\');';
?>
</script>
        </table>
      </div>
      <?php
    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
?>
      <div class="tab-page" id="tabPage_<?php echo $languages[$i]['id']; ?>">
        <h2 class="tab"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']). ' ' .$languages[$i]['name'] ; ?></h2>
        <script type="text/javascript">tp1.addTabPage( document.getElementById( "tabPage_<?php echo $languages[$i]['id']; ?>" ) );</script>
        <table>
          <tr>
            <td class="main"><?php  echo TEXT_PRODUCTS_NAME; ?></td>
            <td class="main"><?php echo  tep_draw_input_field('products_name[' . $languages[$i]['id'] . ']', (isset($products_name[$languages[$i]['id']]) ? stripslashes($products_name[$languages[$i]['id']]) : tep_get_products_name($pInfo->products_id, $languages[$i]['id']))); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_SEO_URL; ?></td>
            <td class="main"><?php echo  tep_draw_input_field('products_seo_url[' . $languages[$i]['id'] . ']', (isset($products_seo_url[$languages[$i]['id']]) ? $products_seo_url[$languages[$i]['id']] : tep_get_products_seo_url($pInfo->products_id, $languages[$i]['id']))); ?></td>
          </tr>
          <!--TotalB2B end-->
          <!-- HTC BOC //-->
          <tr>

          <td class="main" valign="top"><?php echo TEXT_PRODUCTS_DESCRIPTION; ?></td>
          <td>
          <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="main" valign="top"></td>
              <td class="main"><?php echo tep_draw_textarea_field('products_description[' . $languages[$i]['id'] . ']', 'soft', '93', '20', (isset($products_description[$languages[$i]['id']]) ? stripslashes($products_description[$languages[$i]['id']]) : tep_get_products_description($pInfo->products_id, $languages[$i]['id'])), 'class="ckeditor"'); ?>
                </td>
            </td>

            </tr>

          </table>
          </td>

          </tr>

          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_PRODUCTS_PAGE_TITLE; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td class="main" valign="top"></td>
                  <td class="main"><?php echo tep_draw_textarea_field('products_head_title_tag[' . $languages[$i]['id'] . ']', 'soft', '70', '4', (isset($products_head_title_tag[$languages[$i]['id']]) ? stripslashes($products_head_title_tag[$languages[$i]['id']]) : tep_get_products_head_title_tag($pInfo->products_id, $languages[$i]['id'])),'class="notinymce"'); ?></td>
                </tr>
              </table></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_PRODUCTS_HEADER_DESCRIPTION; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td class="main" valign="top"></td>
                  <td class="main"><?php echo tep_draw_textarea_field('products_head_desc_tag[' . $languages[$i]['id'] . ']', 'soft', '70', '4', (isset($products_head_desc_tag[$languages[$i]['id']]) ? stripslashes($products_head_desc_tag[$languages[$i]['id']]) : tep_get_products_head_desc_tag($pInfo->products_id, $languages[$i]['id'])),'class="notinymce"'); ?></td>
                </tr>
              </table></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_PRODUCTS_KEYWORDS; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td class="main" valign="top"></td>
                  <td class="main"><?php echo tep_draw_textarea_field('products_head_keywords_tag[' . $languages[$i]['id'] . ']', 'soft', '70', '4', (isset($products_head_keywords_tag[$languages[$i]['id']]) ? stripslashes($products_head_keywords_tag[$languages[$i]['id']]) : tep_get_products_head_keywords_tag($pInfo->products_id, $languages[$i]['id'])),'class="notinymce"'); ?></td>
                </tr>
              </table></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_URL . '<br><small>' . TEXT_PRODUCTS_URL_WITHOUT_HTTP . '</small>'; ?></td>
            <td class="main"><?php echo  tep_draw_input_field('products_url[' . $languages[$i]['id'] . ']', (isset($products_url[$languages[$i]['id']]) ? stripslashes($products_url[$languages[$i]['id']]) : tep_get_products_url($pInfo->products_id, $languages[$i]['id']))); ?></td>
          </tr>
        </table>
      </div>
      <?php
    }
?>
      <div class="tab-page" id="Attributes">
        <h2 class="tab"><?php echo TEXT_TAB_ATTRIBUTES; ?></h2>
        <script type="text/javascript">tp1.addTabPage( document.getElementById( "Attributes" ) );</script>
        <div align="center">
          <table border="0" width="80%" cellspacing="0" cellpadding="0">
            <tr>
              <td colspan="2"><?php require_once( 'attributeManager/includes/attributeManagerPlaceHolder.inc.php' )?></td>
            </tr>
            <!-- osc@kangaroopartners.com - AJAX Attribute Manager end -->
          </table>
        </div>
      </div>
    </div>
    </td>

    </tr>

  </table>
  </form>

  <!-- HTC BOC //-->
  <?php
  } elseif ($action == 'new_product_preview') {
    if (tep_not_null($_POST)) {
      $pInfo = new objectInfo($_POST);
      $products_name = $_POST['products_name'];
      $products_description = $_POST['products_description'];
      $products_head_title_tag = $_POST['products_head_title_tag'];
      $products_head_desc_tag = $_POST['products_head_desc_tag'];
      $products_head_keywords_tag = $_POST['products_head_keywords_tag'];
      $products_url = $_POST['products_url'];
      $products_seo_url = $_POST['products_seo_url'];
      $products_sort_order = $_POST['products_sort_order'];
    } else {
//TotalB2B start
      $products_price_list = tep_xppp_getpricelist("p");
      $product_query = tep_db_query("select p.products_id, pd.language_id, pd.products_name, pd.products_seo_url, pd.products_description, pd.products_head_title_tag, pd.products_head_desc_tag, pd.products_head_keywords_tag, pd.products_url, p.products_quantity, p.products_model, p.products_image, p.products_pdfupload, " . $products_price_list . ", p.products_weight, p.products_length, p.products_width, p.products_height, p.products_ready_to_ship, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status,  p.products_to_xml, p.manufacturers_id, p.minorder, p.products_sort_order  from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.products_id = '" . (int)$_GET['pID'] . "'");
//TotalB2B end
      $product = tep_db_fetch_array($product_query);
 // HTC EOC
      $pInfo = new objectInfo($product);
      $products_image_name = $pInfo->products_image;
    }
    $form_action = (isset($_GET['pID'])) ? 'update_product' : 'insert_product';
    echo tep_draw_form($form_action, FILENAME_CATEGORIES, 'cPath=' . $cPath . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '') . '&action=' . $form_action, 'post', 'enctype="multipart/form-data"');

    // HTC BOC
    $languages = tep_get_languages();
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
      if (isset($_GET['read']) && ($_GET['read'] == 'only')) {
        $pInfo->products_name = tep_get_products_name($pInfo->products_id, $languages[$i]['id']);
        $pInfo->products_description = tep_get_products_description($pInfo->products_id, $languages[$i]['id']);
        $pInfo->products_head_title_tag = tep_db_prepare_input($products_head_title_tag[$languages[$i]['id']]);
        $pInfo->products_head_desc_tag = tep_db_prepare_input($products_head_desc_tag[$languages[$i]['id']]);
        $pInfo->products_head_keywords_tag = tep_db_prepare_input($products_head_keywords_tag[$languages[$i]['id']]);
        $pInfo->products_url = tep_get_products_url($pInfo->products_id, $languages[$i]['id']);
        $pInfo->products_seo_url = tep_get_products_seo_url($pInfo->products_id, $languages[$i]['id']);
      } else {
        $pInfo->products_name = tep_db_prepare_input($products_name[$languages[$i]['id']]);
        $pInfo->products_description = tep_db_prepare_input($products_description[$languages[$i]['id']]);
        $pInfo->products_head_title_tag = tep_db_prepare_input($products_head_title_tag[$languages[$i]['id']]);
        $pInfo->products_head_desc_tag = tep_db_prepare_input($products_head_desc_tag[$languages[$i]['id']]);
        $pInfo->products_head_keywords_tag = tep_db_prepare_input($products_head_keywords_tag[$languages[$i]['id']]);
        $pInfo->products_url = tep_db_prepare_input($products_url[$languages[$i]['id']]);
        $pInfo->products_seo_url = tep_db_prepare_input($products_seo_url[$languages[$i]['id']]);
      }
    // HTC EOC

?>
  <table border="0" width="100%" cellspacing="0" cellpadding="2">
    <tr>
      <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . $pInfo->products_name; ?></td>
            <!--TotalB2B start-->
            <td class="pageHeading" align="right"><?php
				$prices_num = tep_xppp_getpricesnum();
			    echo ENTRY_PRODUCTS_PRICE . " 1: " . $currencies->format($pInfo->products_price);
                for ($b=2; $b<=$prices_num; $b++) {
				   $products_price_X = "products_price_" . $b;
				   echo "<br>" . ENTRY_PRODUCTS_PRICE . " " . $b. ": ";
				   if (tep_not_null($_POST)) {
					 if (tep_db_prepare_input($_POST['checkbox_products_price_' . $b]) != "true") echo ENTRY_PRODUCTS_PRICE_DISABLED;
				     else echo $currencies->format($pInfo->$products_price_X);
				   } else {
				     if ($product['products_price_' . $b] == NULL) echo ENTRY_PRODUCTS_PRICE_DISABLED;
				     else echo $currencies->format($pInfo->$products_price_X);
				   }
				}
			?></td>
            <!--TotalB2B end-->
          </tr>
        </table></td>
    </tr>
    <tr>
      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
    </tr>
    <tr>
      <td class="main">
<?php
          echo tep_image(DIR_WS_CATALOG_IMAGES . $products_image_name, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="right" hspace="5" vspace="5"');
           if($products_pdfupload_name != "") {
?>
        <b><?php echo TEXT_PRODUCTS_PDFUPLOADED;?></b><br>
        <br>
<?php
      }

 // START: Extra Fields Contribution (chapter 1.5)
          if ($_GET['read'] == 'only') {
            $products_extra_fields_query = tep_db_query("SELECT * FROM " . TABLE_PRODUCTS_TO_PRODUCTS_EXTRA_FIELDS . " WHERE products_id=" . (int)$_GET['pID']);
            while ($products_extra_fields = tep_db_fetch_array($products_extra_fields_query)) {
              $extra_fields_array[$products_extra_fields['products_extra_fields_id']] = $products_extra_fields['products_extra_fields_value'];
            }
          }
          else {
            $extra_fields_array = $_POST['extra_field'];
          }

          $extra_fields_names_query = tep_db_query("SELECT * FROM " . TABLE_PRODUCTS_EXTRA_FIELDS. " WHERE languages_id='0' or languages_id='".(int)$languages[$i]['id']."' ORDER BY products_extra_fields_order");
          while ($extra_fields_names = tep_db_fetch_array($extra_fields_names_query)) {
            $extra_field_name[$extra_fields_names['products_extra_fields_id']] = $extra_fields_names['products_extra_fields_name'];
			echo '<B>'.$extra_fields_names['products_extra_fields_name'].':</B>&nbsp;'.stripslashes($extra_fields_array[$extra_fields_names['products_extra_fields_id']]).'<BR>'."\n";
          }
// END: Extra Fields Contribution


          echo "<br>" . $pInfo->products_description;
?>
      </td>
    </tr>
<?php
      if ($pInfo->products_url) {
?>
    <tr>
      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
    </tr>
    <tr>
      <td class="main"><?php echo sprintf(TEXT_PRODUCT_MORE_INFORMATION, $pInfo->products_url); ?></td>
    </tr>
<?php
      }
?>
    <tr>
      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
    </tr>
<?php
      if ($pInfo->products_date_available > date('Y-m-d')) {
?>
    <tr>
      <td align="center" class="smallText"><?php echo sprintf(TEXT_PRODUCT_DATE_AVAILABLE, tep_date_long($pInfo->products_date_available)); ?></td>
    </tr>
<?php
      } else {
?>
    <tr>
      <td align="center" class="smallText"><?php echo sprintf(TEXT_PRODUCT_DATE_ADDED, tep_date_long($pInfo->products_date_added)); ?></td>
    </tr>
<?php
      }
?>
    <tr>
      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
    </tr>
<?php
    }

    if (isset($_GET['read']) && ($_GET['read'] == 'only')) {
      if (isset($_GET['origin'])) {
        $pos_params = strpos($_GET['origin'], '?', 0);
        if ($pos_params != false) {
          $back_url = substr($_GET['origin'], 0, $pos_params);
          $back_url_params = substr($_GET['origin'], $pos_params + 1);
        } else {
          $back_url = $_GET['origin'];
          $back_url_params = '';
        }
      } else {
        $back_url = FILENAME_CATEGORIES;
        $back_url_params = 'page='.$page.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id;
      }
?>
    <tr>
      <td align="right"><?php echo '<a href="' . tep_href_link($back_url, $back_url_params, 'NONSSL') . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
    </tr>
    <?php
    } else {
?>
    <tr>
      <td align="right" class="smallText"><?php
/* Re-Post all POST'ed variables */
      reset($_POST);
      while (list($key, $value) = each($_POST)) {
        if (!is_array($_POST[$key])) {
          echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
        }
      }
      // HTC BOC
      $languages = tep_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        echo tep_draw_hidden_field('products_name[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_name[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_description[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_description[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_head_title_tag[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_head_title_tag[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_head_desc_tag[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_head_desc_tag[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_head_keywords_tag[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_head_keywords_tag[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_url[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_url[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_seo_url[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_seo_url[$languages[$i]['id']])));
      }
      // HTC EOC
      // START: Extra Fields Contribution
      if ($_POST['extra_field']) { // Check to see if there are any need to update extra fields.
        foreach ($_POST['extra_field'] as $key=>$val) {
          echo tep_draw_hidden_field('extra_field['.$key.']', stripslashes($val));
        }
      } // Check to see if there are any need to update extra fields.
      // END: Extra Fields Contribution

      echo tep_draw_hidden_field('products_image', stripslashes($products_image_name));
      echo tep_draw_hidden_field('products_pdfupload', stripslashes($products_pdfupload_name));
      echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="edit"') . '&nbsp;&nbsp;';

      if ($_GET['search']) {
         $trueConfirmEditPath =  'page='.$page.'&search=' . $_GET['search'];
      }else{
         $trueConfirmEditPath =  'page='.$page.'&cPath=' . $cPath;
      }

      if (isset($_GET['pID'])) {
        echo tep_image_submit('button_update.gif', IMAGE_UPDATE);
      } else {
        echo tep_image_submit('button_insert.gif', IMAGE_INSERT);
      }
      echo '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, $trueConfirmEditPath . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>';
?></td>
    </tr>
  </table>
  </form>

<?php
    }
  } else {
?>
  <table border="0" width="100%" cellspacing="0" cellpadding="2">
    <tr>
      <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
                </tr>
                <tr>
                  <td>&nbsp;&nbsp;<?php echo $breadcrumb->trail(' &raquo; '); ?></td>
                </tr>
              </table></td>
            <td align="right"><table border="0" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td class="smallText" align="right"><?php
// BOF: KategorienAdmin / OLISWISS
    if ($admin_groups_id == 1) {
      echo tep_draw_form('search', FILENAME_CATEGORIES, '', 'get');
      echo HEADING_TITLE_SEARCH . ' ' . tep_draw_input_field('search');
      echo '</form>';
    }
// EOF: KategorienAdmin / OLISWISS
echo ' &nbsp;';
// BOF: KategorienAdmin / OLISWISS
//  echo tep_draw_form('goto', FILENAME_CATEGORIES, '', 'get');
//  echo HEADING_TITLE_GOTO . ' ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"');
//  echo '</form>';
    if (is_array($admin_cat_access_array_cats) && (in_array("ALL",$admin_cat_access_array_cats)== false) && (pos($admin_cat_access_array_cats)!= "")) {
      echo tep_draw_form('goto', FILENAME_CATEGORIES, '', 'get');
      echo HEADING_TITLE_GOTO . ' ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree('','','','',$admin_cat_access_array_cats), $current_category_id, 'onChange="this.form.submit();"');
      echo '</form>';
    } else if (in_array("ALL",$admin_cat_access_array_cats)== true) { //nur Top-ADMIN
      echo tep_draw_form('goto', FILENAME_CATEGORIES, '', 'get');
      echo HEADING_TITLE_GOTO . ' ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"');
      echo '</form>';
    }
// EOF: KategorienAdmin / OLISWISS
?>
                  </td><td class="pageHeading" align="right"><?php echo tep_href_manual(FILENAME_CATEGORIES); ?></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>
    <tr>

    <td>

    <table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td valign="top">
        <table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr class="dataTableHeadingRow">
            <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CATEGORIES_PRODUCTS; ?></td>
            <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></td>
            <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_XML; ?></td>
            <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PRODUCT_SORT; ?></td>
            <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
          </tr>
          <?php
    $categories_count = 0;
    $rows = 0;
    if (isset($_GET['search'])) {
      $search = tep_db_prepare_input($_GET['search']);
// #################### Added Categorie Enable / Disable ##################
// HTC BOC
      $categories_query = tep_db_query("select c.categories_id, cd.categories_name, cd.categories_seo_url, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.categories_status, cd.categories_htc_title_tag, cd.categories_htc_desc_tag, cd.categories_htc_keywords_tag from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and cd.categories_name like '%" . tep_db_input($search) . "%' order by c.sort_order, cd.categories_name");
    } else {
// BOF: KategorienAdmin / OLISWISS
      if ($admin_cat_access == "ALL") {
        $categories_query = tep_db_query("select c.categories_id, cd.categories_name, cd.categories_seo_url, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.categories_status, cd.categories_htc_title_tag, cd.categories_htc_desc_tag, cd.categories_htc_keywords_tag from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '" . (int)$current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' order by c.sort_order, cd.categories_name");
      } elseif ($admin_cat_access == ""){
        $categories_query = tep_db_query("");
      } else {
        $categories_query = tep_db_query("select c.categories_id, cd.categories_name, cd.categories_seo_url, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.categories_status, cd.categories_htc_title_tag, cd.categories_htc_desc_tag, cd.categories_htc_keywords_tag from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '" . (int)$current_category_id . "' and (c.parent_id or c.categories_id in (" . $admin_cat_access . ")) and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' order by c.sort_order, cd.categories_name");
      }
// EOF: KategorienAdmin / OLISWISS
// HTC EOC
// #################### End Added Categorie Enable / Disable ##################
    }
    while ($categories = tep_db_fetch_array($categories_query)) {
      $categories_count++;
      $rows++;

// Get parent_id for subcategories if search
      if (isset($_GET['search'])) $cPath= $categories['parent_id'];

      if ((!isset($_GET['cID']) && !isset($_GET['pID']) || (isset($_GET['cID']) && ($_GET['cID'] == $categories['categories_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
        $category_childs = array('childs_count' => tep_childs_in_category_count($categories['categories_id']));
        $category_products = array('products_count' => tep_products_in_category_count($categories['categories_id']));

        $cInfo_array = array_merge($categories, $category_childs, $category_products);
        $cInfo = new objectInfo($cInfo_array);
      }
// BOF: KategorienAdmin / OLISWISS
      if ($admin_groups_id == 1 || in_array($categories['categories_id'],$admin_cat_access_array_cats) || $categories['parent_id'] != 0) {
        if ($admin_groups_id == 1 || in_array($_GET['cPath'],$admin_cat_access_array_cats) || in_array($categories['categories_id'],$admin_cat_access_array_cats)) {
// EOF: KategorienAdmin / OLISWISS
          if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) {
           echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, tep_get_path($categories['categories_id'])) . '\'">' . "\n";
          } else {
           echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '\'">' . "\n";
          }
?>
          <td class="dataTableContent"><?php echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, tep_get_path($categories['categories_id'])) . '">' . tep_image(DIR_WS_ICONS . 'folder.gif', ICON_FOLDER) . '</a>&nbsp;<b>' . $categories['categories_name'] . '</b>'; ?></td>
            <!-- // ################" Added Categories Disable #############
            <td class="dataTableContent" align="center">&nbsp;</td>-->
            <td class="dataTableContent" align="center"><?php
          if ($categories['categories_status'] == '1') {
            echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setflag_cat&flag=0&cID=' . $categories['categories_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
          } else {
            echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setflag_cat&flag=1&cID=' . $categories['categories_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
          }
?>
            </td>
            <!-- // ################" End Added Categories Disable ############# -->
            <td class="dataTableContent" align="center">&nbsp;</td>
            <td class="dataTableContent" align="center"><?php echo $categories['sort_order']; ?></td>
            <td class="dataTableContent" align="right"><?php if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>
              &nbsp;</td>
          </tr>
          <?php
// BOF: KategorienAdmin / OLISWISS
        }
      }
// EOF: KategorienAdmin / OLISWISS
    }

    $products_count = 0;
    if (isset($_GET['search'])) {
      $products_query = "select p.products_id, pd.products_name, pd.products_seo_url, p.products_quantity, p.products_image, p.products_pdfupload, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status,  p.products_to_xml, p2c.categories_id, p.minorder, p.products_sort_order from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and pd.products_name like '%" . tep_db_input($search) . "%' order by pd.products_name";
    } else {
      $products_query = "select p.products_id, pd.products_name, pd.products_seo_url, p.products_quantity, p.products_image, p.products_pdfupload, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_to_xml, p.minorder, p.products_sort_order from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = '" . (int)$current_category_id . "' order by pd.products_name";
    }

 	if(($_GET['page'] =="") && ($_GET['pID'] !="")){
 	$products_total_query = tep_db_query($products_query);
 	$count=0;
 	  while ($products_total = tep_db_fetch_array($products_total_query)) {
 	    if((int)$products_total['products_id']==(int)$_GET['pID']){
 	      $pnumber=$count;
 	      break;
 	    }
 	 	$count++;
 	  }
 	  $page=ceil($pnumber/MAX_PROD_ADMIN_SIDE);
 	  $_GET['page'] = $page;
 	}

	$prod_split = new splitPageResults($_GET['page'], MAX_PROD_ADMIN_SIDE, $products_query, $prod_query_numrows);
 	$products_query = tep_db_query($products_query);

    while ($products = tep_db_fetch_array($products_query)) {
      $products_count++;
      $rows++;

// Get categories_id for product if search
      if (isset($_GET['search'])) $cPath = $products['categories_id'];

      if ( (!isset($_GET['pID']) && !isset($_GET['cID']) || (isset($_GET['pID']) && ($_GET['pID'] == $products['products_id']))) && !isset($pInfo) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
// find out the rating average from customer reviews
        $reviews_query = tep_db_query("select (avg(reviews_rating) / 5 * 100) as average_rating from " . TABLE_REVIEWS . " where products_id = '" . (int)$products['products_id'] . "'");
        $reviews = tep_db_fetch_array($reviews_query);
        $pInfo_array = array_merge($products, $reviews);
        $pInfo = new objectInfo($pInfo_array);
      }
// BOF: KategorienAdmin / OLISWISS
      if ($admin_groups_id == 1 || in_array($categories['categories_id'],$admin_cat_access_array_cats) || $cPath != 0) {
// EOF: KategorienAdmin / OLISWISS
        if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id) ) {
           echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=new_product_preview&read=only') . '\'">' . "\n";
        } elseif ($_GET['search']) {
           echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'search=' . $_GET['search'] . '&pID=' . $products['products_id']) . '\'">' . "\n";
        } else {
           echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $cPath . '&pID=' . $products['products_id']). '\'">' . "\n";
        }
?>
          <td class="dataTableContent"><?php echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=new_product_preview&read=only') . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW) . '</a>&nbsp;' . $products['products_name']; ?></td>
            <!--////-->



            <td class="dataTableContent" align="center"><?php

 if ($_GET['search']) {
 $truePath= '&pID=' . $products['products_id'] . '&page='.$page.'&search=' . $_GET['search'];
}else{
  $truePath =  '&pID=' . $products['products_id'] . '&cPath=' . $cPath;
}

         if ($products['products_status'] == '1') {
           echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setflag&flag=0' . $truePath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
         } else {
           echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setflag&flag=1' . $truePath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
         }
?></td>
            <td class="dataTableContent" align="center"><?php
         if ($products['products_to_xml'] == '1') {
           echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setxml&flagxml=0' . $truePath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
         } else {
           echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setxml&flagxml=1' . $truePath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
         }
?></td>
            <td class="dataTableContent" align="center"><?php echo $products['products_sort_order'];  ?></td>
            <td class="dataTableContent" align="right"><?php
	   $products_properties_query = tep_db_query("select products_id from " . TABLE_PRODUCTS_PROPERTIES . " where products_id = '" . (int)$products['products_id'] . "'");
		 while($products_properties = tep_db_fetch_array($products_properties_query)) {
			$products_properties_id = $products_properties['products_id'];
		 }
                   if ($products['products_id'] == $products_properties_id) { echo '<a href="javascript:popupPropertiesWindow(\'' . tep_href_link(FILENAME_PRODUCTS_PROPERTIES_POPUP, 'cID=' . $current_category_id . '&pID=' . $products['products_id']) . '\')">' . tep_image(DIR_WS_ICONS . 'icon_properties_change.gif', IMAGE_PROPERTIES_POPUP_ADD_CHANGE_DELETE, 13, 19) . '</a>'; } else { echo '<a href="javascript:popupPropertiesWindow(\'' . tep_href_link(FILENAME_PRODUCTS_PROPERTIES_POPUP, 'cID=' . $current_category_id . '&pID=' . $products['products_id']) . '\')">' . tep_image(DIR_WS_ICONS . 'icon_properties_add.gif', IMAGE_PROPERTIES_POPUP_ADD, 13, 19) . '</a>'; } ?>
              &nbsp;&nbsp;
              <?php if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&page='.$_GET['page'].'&pID=' . $products['products_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>
              &nbsp;</td>
          </tr>
          <?php
// BOF: KategorienAdmin / OLISWISS
      }
// EOF: KategorienAdmin / OLISWISS
    }

    $cPath_back = '';
    if (sizeof($cPath_array) > 0) {
      for ($i=0, $n=sizeof($cPath_array)-1; $i<$n; $i++) {
        if (empty($cPath_back)) {
          $cPath_back .= $cPath_array[$i];
        } else {
          $cPath_back .= '_' . $cPath_array[$i];
        }
      }
    }

    $cPath_back = (tep_not_null($cPath_back)) ? 'cPath=' . $cPath_back . '&' : '';
?>
          <tr>
            <td colspan="5"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                <tr>
                  <td class="smallText" valign="top"><?php echo $prod_split->display_count($prod_query_numrows, MAX_PROD_ADMIN_SIDE, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
                  <td class="smallText" align="right"><?php echo $prod_split->display_links($prod_query_numrows, MAX_PROD_ADMIN_SIDE, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], tep_get_all_get_params(array('page','pID'))); ?></td>
                </tr>
              </table></td>
          </td>
          <tr>
            <td colspan="5"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                <tr>
                  <?php // BOF: KategorienAdmin / OLISWISS
	if($admin_groups_id == 1){
?>
                  <td class="smallText"><?php echo TEXT_CATEGORIES . '&nbsp;' . $categories_count . '<br>' . TEXT_PRODUCTS . '&nbsp;' . $products_count; ?></td>
                  <td align="right" class="smallText"><?php if (sizeof($cPath_array) > 0) echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, $cPath_back . 'cID=' . $current_category_id) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>&nbsp;'; if (!isset($_GET['search'])) echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&action=new_category') . '">' . tep_image_button('button_new_category.gif', IMAGE_NEW_CATEGORY) . '</a>&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&action=new_product') . '">' . tep_image_button('button_new_product.gif', IMAGE_NEW_PRODUCT) . '</a>'; ?>
                    &nbsp;</td>
                  <?php
	} else {
?>
                  <td></td>
                  <td align="right" class="smallText"><?php if (sizeof($cPath_array) > 0) echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, $cPath_back . 'cID=' . $current_category_id) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>&nbsp;';
                    if (!isset($_GET['search']) && strstr($admin_right_access,"CNEW")) echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&action=new_category') . '">' . tep_image_button('button_new_category.gif', IMAGE_NEW_CATEGORY) . '</a>&nbsp;';
                    if (!isset($_GET['search']) && strstr($admin_right_access,"PNEW") && $cInfo->parent_id !='0') echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&action=new_product') . '">' . tep_image_button('button_new_product.gif', IMAGE_NEW_PRODUCT) . '</a>'; ?>
                    &nbsp;</td>
                  <?php
	}
// EOF: KategorienAdmin / OLISWISS
?>
                </tr>
              </table></td>
          </tr>
        </table>
      </td>

      <?php
    $heading = array();
    $contents = array();
    switch ($action) {
      case 'new_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_NEW_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('newcategory', FILENAME_CATEGORIES, 'action=insert_category&cPath=' . $cPath, 'post', 'enctype="multipart/form-data"'));
        $contents[] = array('text' => TEXT_NEW_CATEGORY_INTRO);

        $category_inputs_string = '';
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']');
          // HTC BOC
          $category_htc_title_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_title_tag[' . $languages[$i]['id'] . ']');
          $category_htc_desc_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_desc_tag[' . $languages[$i]['id'] . ']');
          $category_htc_keywords_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_keywords_tag[' . $languages[$i]['id'] . ']');
          // HTC EOC
        }

        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_seo_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_seo_url[' . $languages[$i]['id'] . ']');
        }

        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_NAME . $category_inputs_string);
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_SEO_URL . $category_seo_string);
        // - START - Category Descriptions
        $category_inputs_string_title = $category_inputs_string_description = '';
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_inputs_string_title .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_heading_title[' . $languages[$i]['id'] . ']');
          $category_inputs_string_description .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_textarea_field('categories_description[' . $languages[$i]['id'] . ']', 'soft', 40, 10,'','class="notinymce"');
        }
        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_HEADING_TITLE . $category_inputs_string_title);
        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_DESCRIPTION . $category_inputs_string_description);
	    // --- END - Category Descriptions
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_IMAGE . '<br>' . tep_draw_input_field('categories_image').'<a href="javascript:chooseThat(\'newcategory\',\'categories_image\')">' . tep_image_button('button_insert.gif', IMAGE_INSERT) . '</a>');
        $contents[] = array('text' => '<br>' . TEXT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', '', 'size="2"'));
        // HTC BOC
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_PAGE_TITLE . $category_htc_title_string);
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_HEADER_DESCRIPTION . $category_htc_desc_string);
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_KEYWORDS . $category_htc_keywords_string);
        // HTC EOC
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;

      case 'edit_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_EDIT_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'action=update_category&cPath=' . $cPath, 'post', 'enctype="multipart/form-data"') . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => TEXT_EDIT_INTRO);

        $category_inputs_string = '';
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']', tep_get_category_name($cInfo->categories_id, $languages[$i]['id']));
          // HTC BOC
          $category_htc_title_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_title_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_title($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_desc_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_desc_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_desc($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_keywords_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_keywords_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_keywords($cInfo->categories_id, $languages[$i]['id']));
          // HTC EOC
        }

        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_seo_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_seo_url[' . $languages[$i]['id'] . ']', tep_get_category_seo_url($cInfo->categories_id, $languages[$i]['id']));
        }

        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_NAME . $category_inputs_string);
        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_SEO_URL . $category_seo_string);
        // - START - Category Descriptions
        $cat_descriptions = array();
        $cat_description_query = tep_db_query ("select language_id,categories_heading_title,categories_description from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id = '" . $cInfo->categories_id . "'");
		while ($cat_description = tep_db_fetch_array($cat_description_query)) {
			$cat_descriptions['categories_heading_title'][$cat_description['language_id']] = $cat_description['categories_heading_title'];
			$cat_descriptions['categories_description'][$cat_description['language_id']] = $cat_description['categories_description'];
		}
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_inputs_string_title .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_heading_title[' . $languages[$i]['id'] . ']', $cat_descriptions ['categories_heading_title'][$languages[$i]['id']]);
          $category_inputs_string_description .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name'],0,0,'ALIGN="top"') . '&nbsp;' . tep_draw_textarea_field('categories_description[' . $languages[$i]['id'] . ']', 'soft', 40, 10, $cat_descriptions ['categories_description'][$languages[$i]['id']],'class="notinymce"');
		}
        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_HEADING_TITLE . $category_inputs_string_title);
        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_DESCRIPTION . $category_inputs_string_description);
	    // --- END - Category Descriptions
        $contents[] = array('text' => '<br>' . tep_image(DIR_WS_CATALOG_IMAGES . $cInfo->categories_image, $cInfo->categories_name) . '<br>' . DIR_WS_CATALOG_IMAGES . '<br><b>' . $cInfo->categories_image . '</b>');
        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_IMAGE . '<br>' . tep_draw_input_field('categories_image').'<a href="javascript:chooseThat(\'categories\',\'categories_image\')">' . tep_image_button('button_insert.gif', IMAGE_INSERT) . '</a>');
        $contents[] = array('text' => '<br>' . TEXT_EDIT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', $cInfo->sort_order, 'size="2"'));
        // HTC BOC
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_PAGE_TITLE . $category_htc_title_string);
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_HEADER_DESCRIPTION . $category_htc_desc_string);
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_KEYWORDS . $category_htc_keywords_string);
        // HTC EOC
	$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;

      case 'delete_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'action=delete_category_confirm&cPath=' . $cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => TEXT_DELETE_CATEGORY_INTRO);
        $contents[] = array('text' => '<br><b>' . $cInfo->categories_name . '</b>');
        if ($cInfo->childs_count > 0) $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_CHILDS, $cInfo->childs_count));
        if ($cInfo->products_count > 0) $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_PRODUCTS, $cInfo->products_count));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;

      case 'move_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_MOVE_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'action=move_category_confirm&cPath=' . $cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => sprintf(TEXT_MOVE_CATEGORIES_INTRO, $cInfo->categories_name));
        $contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $cInfo->categories_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_move.gif', IMAGE_MOVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;

      case 'delete_product':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_PRODUCT . '</b>');

        $contents = array('form' => tep_draw_form('products', FILENAME_CATEGORIES, 'action=delete_product_confirm&page='.$page.'&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
        $contents[] = array('text' => TEXT_DELETE_PRODUCT_INTRO);
        $contents[] = array('text' => '<br><b>' . $pInfo->products_name . '</b>');

        $product_categories_string = '';
        $product_categories = tep_generate_category_path($pInfo->products_id, 'product');
        for ($i = 0, $n = sizeof($product_categories); $i < $n; $i++) {
          $category_path = '';
          for ($j = 0, $k = sizeof($product_categories[$i]); $j < $k; $j++) {
            $category_path .= $product_categories[$i][$j]['text'] . '&nbsp;&gt;&nbsp;';
          }
          $category_path = substr($category_path, 0, -16);
          $product_categories_string .= tep_draw_checkbox_field('product_categories[]', $product_categories[$i][sizeof($product_categories[$i])-1]['id'], true) . '&nbsp;' . $category_path . '<br>';
        }
        $product_categories_string = substr($product_categories_string, 0, -4);

        $contents[] = array('text' => '<br>' . $product_categories_string);
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;

      case 'move_product':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_MOVE_PRODUCT . '</b>');

        $contents = array('form' => tep_draw_form('products', FILENAME_CATEGORIES, 'action=move_product_confirm&page='.$page.'&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
        $contents[] = array('text' => sprintf(TEXT_MOVE_PRODUCTS_INTRO, $pInfo->products_name));
        $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENT_CATEGORIES . '<br><b>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</b>');
        // BOF: KategorienAdmin / OLISWISS
        if (is_array($admin_cat_access_array_cats) && (in_array("ALL",$admin_cat_access_array_cats)== false) && (pos($admin_cat_access_array_cats)!= "")) {
           $contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $pInfo->products_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree('','','0','',$admin_cat_access_array_cats), $current_category_id));
        } else if (in_array("ALL",$admin_cat_access_array_cats)== true) { //nur Top-ADMIN
           $contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $pInfo->products_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
        }
// EOF: KategorienAdmin / OLISWISS
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_move.gif', IMAGE_MOVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;

      case 'copy_to':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_COPY_TO . '</b>');

        $contents = array('form' => tep_draw_form('copy_to', FILENAME_CATEGORIES, 'action=copy_to_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
        $contents[] = array('text' => TEXT_INFO_COPY_TO_INTRO);
        $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENT_CATEGORIES . '<br><b>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</b>');
        // BOF: KategorienAdmin / OLISWISS
        if (is_array($admin_cat_access_array_cats) && (in_array("ALL",$admin_cat_access_array_cats)== false) && (pos($admin_cat_access_array_cats)!= "")) {
          $contents[] = array('text' => '<br>' . TEXT_CATEGORIES . '<br>' . tep_draw_pull_down_menu('categories_id', tep_get_category_tree('','','0','',$admin_cat_access_array_cats), $current_category_id));
        } else if (in_array("ALL",$admin_cat_access_array_cats)== true) { //nur Top-ADMIN
          $contents[] = array('text' => '<br>' . TEXT_CATEGORIES . '<br>' . tep_draw_pull_down_menu('categories_id', tep_get_category_tree(), $current_category_id));
        }
// EOF: KategorienAdmin / OLISWISS
        $contents[] = array('text' => '<br>' . TEXT_HOW_TO_COPY . '<br>' . tep_draw_radio_field('copy_as', 'link', true) . ' ' . TEXT_COPY_AS_LINK . '<br>' . tep_draw_radio_field('copy_as', 'duplicate') . ' ' . TEXT_COPY_AS_DUPLICATE);
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_copy.gif', IMAGE_COPY) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;

      default:
        if ($rows > 0) {
          if (isset($cInfo) && is_object($cInfo)) { // category info box contents
            $category_path_string = '';
            $category_path = tep_generate_category_path($cInfo->categories_id);
            for ($i=(sizeof($category_path[0])-1); $i>0; $i--) {
              $category_path_string .= $category_path[0][$i]['id'] . '_';
            }
            $category_path_string = substr($category_path_string, 0, -1);

            $heading[] = array('text' => '<b>' . $cInfo->categories_name . '</b>');
// BOF: KategorienAdmin / OLISWISS
	        if ($admin_groups_id == 1) {
              $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $category_path_string . '&cID=' . $cInfo->categories_id . '&action=edit_category') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $category_path_string . '&cID=' . $cInfo->categories_id . '&action=delete_category') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $category_path_string . '&cID=' . $cInfo->categories_id . '&action=move_category') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a>');
	        } else {
	            if (strstr($admin_right_access,"CEDIT")) {
	              $c_right_string .= ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=edit_category') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a>';
	            }
	            if (strstr($admin_right_access,"CDELETE")) {
	      	      $c_right_string .= ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=delete_category') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>';
	            }
	            if (strstr($admin_right_access,"CMOVE")) {
	              $c_right_string .= ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'page='.$page.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=move_category') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a>';
	            }
	          $contents[] = array('align' => 'center', 'text' => $c_right_string);
	        }
// EOF: KategorienAdmin / OLISWISS
            $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($cInfo->date_added));
            if (tep_not_null($cInfo->last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($cInfo->last_modified));
            $contents[] = array('text' => '<br>' . tep_info_image($cInfo->categories_image, $cInfo->categories_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '<br>' . $cInfo->categories_image);
            $contents[] = array('text' => '<br>' . TEXT_SUBCATEGORIES . ' ' . $cInfo->childs_count . '<br>' . TEXT_PRODUCTS . ' ' . $cInfo->products_count);
          } elseif (isset($pInfo) && is_object($pInfo)) { // product info box contents
            $heading[] = array('text' => '<b>' . tep_get_products_name($pInfo->products_id, $languages_id) . '</b>');
             if ($_GET['search']) {
               $cPath_query = "select distinct products_id, categories_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id= '" . $pInfo->products_id . "' ";
               $cPath_fetch = mysql_fetch_array(mysql_query($cPath_query));
               $cPath2 = $cPath_fetch['categories_id'];

               $truecPath =  'page='.$page.'&cPath=' . $cPath2 . '&pID=' . $pInfo->products_id;
            }else{
               $truecPath =  'page='.$page.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id;
            }
            // BOF: KategorienAdmin / OLISWISS
	        if ($admin_groups_id == 1) {
              $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CATEGORIES, $truecPath . '&action=new_product') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, $truecPath . '&action=delete_product') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, $truecPath . '&action=move_product') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, $truecPath . '&action=copy_to') . '">' . tep_image_button('button_copy_to.gif', IMAGE_COPY_TO) . '</a>');
	        } else {
	            if (strstr($admin_right_access,"PEDIT")) {
	              $p_right_string .= ' <a href="' . tep_href_link(FILENAME_CATEGORIES, $truecPath . '&action=new_product') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a>';
	            }
	            if (strstr($admin_right_access,"PDELETE")) {
	      	      $p_right_string .= ' <a href="' . tep_href_link(FILENAME_CATEGORIES, $truecPath . '&action=delete_product') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>';
	            }
	            if (strstr($admin_right_access,"PMOVE")) {
	              $p_right_string .= ' <a href="' . tep_href_link(FILENAME_CATEGORIES, $truecPath . '&action=move_product') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a>';
	            }
	            if (strstr($admin_right_access,"PCOPY")) {
	              $p_right_string .= ' <a href="' . tep_href_link(FILENAME_CATEGORIES, $truecPath . '&action=copy_to') . '">' . tep_image_button('button_copy_to.gif', IMAGE_COPY_TO) . '</a>';
	            }
	          $contents[] = array('align' => 'center', 'text' => $p_right_string);
	        }
	        $contents[] = array('align' => 'center', 'text' => '<a href="javascript:popupPropertiesWindow(\'' . tep_href_link(FILENAME_PRODUCTS_PROPERTIES, 'cID=' . $current_category_id . '&pID=' . $pInfo->products_id) . '\')">' . tep_image_button('button_properties_category.gif', IMAGE_PROPERTIES) . '</a>');
// EOF: KategorienAdmin / OLISWISS
            $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($pInfo->products_date_added));
            if (tep_not_null($pInfo->products_last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($pInfo->products_last_modified));
            if (date('Y-m-d') < $pInfo->products_date_available) $contents[] = array('text' => TEXT_DATE_AVAILABLE . ' ' . tep_date_short($pInfo->products_date_available));
            $contents[] = array('text' => '<br>' . tep_info_image($pInfo->products_image, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '<br>' . $pInfo->products_image);
            $contents[] = array('text' => '<br>' . TEXT_PRODUCTS_PRICE_INFO . ' ' . $currencies->format($pInfo->products_price) . '<br>' . TEXT_PRODUCTS_QUANTITY_INFO . ' ' . $pInfo->products_quantity);
            $contents[] = array('text' => '<br>' . TEXT_PRODUCTS_AVERAGE_RATING . ' ' . number_format($pInfo->average_rating, 2) . '%');
          }
        } else { // create category/product info
          $heading[] = array('text' => '<b>' . EMPTY_CATEGORY . '</b>');

          $contents[] = array('text' => TEXT_NO_CHILD_CATEGORIES_OR_PRODUCTS);
        }
        break;
    }

    if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
      echo '            <td width="25%" valign="top">' . "\n";
      $box = new box;
      echo $box->infoBox($heading, $contents);
      echo '            </td>' . "\n";
    }
?>
      </tr>
    </table>
    </td>
    </tr>
  </table>
  <?php
  }
?>
  </td>
  <!-- body_text_eof //-->
  </tr>
</table>
<!-- body_eof //-->
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
