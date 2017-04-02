<?php
/*
  $Id: orders.php,v 1.2 2007/06/29 20:50:52 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $orders_statuses = array();
  $orders_status_array = array();
// Ajout pour order_status_default_comment_v.1
  $orders_default_comment_array = array();
// Fin ajout pour order_status_default_comment_v.1
  $orders_status_query = tep_db_query("select orders_status_id, orders_status_name, orders_status_default_comment from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int)$languages_id . "'");
  while ($orders_status = tep_db_fetch_array($orders_status_query)) {
    $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                               'text' => $orders_status['orders_status_name']);
    $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
// Ajout pour order_status_default_comment_v.1
    $orders_default_comment_array[$orders_status['orders_status_id']] = addslashes($orders_status['orders_status_default_comment']);
// Fin ajout pour order_status_default_comment_v.1
  }

  $action = (isset($_GET['action']) ? $_GET['action'] : '');
// Start Batch Update Status
  if (isset($_POST['submit'])){
     if (($_POST['submit'] == BUS_SUBMIT)&&(isset($_POST['new_status']))&&(!isset($_POST['delete_orders']))){ // Fair enough, let's update ;)
       $status = tep_db_prepare_input($_POST['new_status']);
      if ($status == '') {
       tep_redirect(tep_href_link(FILENAME_ORDERS),tep_get_all_get_params());
      }
      foreach ($_POST['update_oID'] as $order_id){
        $order_updated = false;
        $check_status_query = tep_db_query("select customers_name, customers_email_address, orders_status, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
        $check_status = tep_db_fetch_array($check_status_query);
        //$comments = "Batch status update";
        if ($check_status['orders_status'] != $status) {
        tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . (int)$order_id . "'");
          $customer_notified ='0';
          if (isset($_POST['notify'])) {
            $notify_comments = '';
            $email = STORE_NAME . "\n" . STORE_ADDRESS_A . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $order_id . "\n" . (($customer_id > 0) ? EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $order_id, 'SSL') : "") . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]) . "\n\n" . $notify_comments;
            //tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT, $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS); // старая версия
            $status_subject = sprintf(EMAIL_TEXT_SUBJECT, $order_id, $orders_status_array[$status]);
            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], $status_subject, $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            $customer_notified = '1';
          }
          tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . (int)$order_id . "', '" . tep_db_input($status) . "', now(), '" . tep_db_input($customer_notified) . "', '" . tep_db_input($comments)  . "')");
          $order_updated = true;
        }
        if ($order_updated == true) {
         $messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
        } else {
          $messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
        }
      }
    }



    // Создание бланка оптового заказа поставщику
    if (($_POST['submit'] == WHOLESALEFORM)&&(!isset($_POST['delete_orders']))){

        date_default_timezone_set('Europe/Kiev');
        define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

        require(DIR_WS_CLASSES . 'PHPExcel.php');

        //wholesale_order_form

       //$order_data = false;
       $i=0;
       foreach ($_POST['update_oID'] as $order_id){
           $orders_list[$i] = (int)$order_id;
           $i++;
      }

        $where_in = implode(',', $orders_list);
        $get_order_wholesale_query = mysql_query("SELECT GROUP_CONCAT(o.orders_id) AS '№№', op.products_model AS 'Код', op.products_name AS 'Наименование', SUM(op.products_quantity) as 'Кол-во', op.products_price_2 AS 'Опт', SUM(op.products_quantity) * op.products_price_2 AS 'Сумма опт', op.products_price AS 'Розница', SUM(op.products_quantity) * op.products_price as 'Сумма розница' from " . TABLE_ORDERS_PRODUCTS . " as op,  " . TABLE_ORDERS . " as o where o.orders_id = op.orders_id and o.orders_status IN (2,7) and op.orders_id IN ($where_in) GROUP BY op.products_name ORDER BY GROUP_CONCAT(o.orders_id) ASC");

        // сбор всей таблицы (построчно) в один массив
        while($row = mysql_fetch_assoc($get_order_wholesale_query)){
            $json[] = $row;
        }

         // error_log( print_R($json, TRUE) ); //Выгрузка итогового массива в лог
         // var_dump($json); // то же но на экран

          //открытие готового эксель шаблона и запись в него данных массива
          $objPHPExcel = PHPExcel_IOFactory::load('wholesale_order_form.xlsx');

          $c = 2;
      for ($i = 0, $n = sizeof($json); $i < $n; $i++) {
      $objPHPExcel->getActiveSheet()
            ->setCellValue('A' . $c, $json[$i]["№№"])
            ->setCellValue('B' . $c, $json[$i]["Код"])
            ->setCellValue('C' . $c, $json[$i]["Наименование"])
            ->setCellValue('D' . $c, $json[$i]["Кол-во"])
            ->setCellValue('E' . $c, $json[$i]["Опт"])
            ->setCellValue('F' . $c, $json[$i]["Сумма опт"])
            ->setCellValue('G' . $c, $json[$i]["Розница"])
            ->setCellValue('H' . $c, $json[$i]["Сумма розница"]);
        $c++;
    }

           //заполняем последню строку итоговыми суммами
          $cc = $c+1;
          $objPHPExcel->getActiveSheet()
            ->setCellValue('C' . $cc, 'Всего:')
            ->setCellValue('D' . $cc, '=SUM(D2:D' . $c . ')')
           // ->setCellValue('E' . $cc, '=SUM(E2:E' . $c . ')')
            ->setCellValue('F' . $cc, '=SUM(F2:F' . $c . ')')
           // ->setCellValue('G' . $cc, '=SUM(G2:G' . $c . ')')
            ->setCellValue('H' . $cc, '=SUM(H2:H' . $c . ')');
           $objPHPExcel->getActiveSheet()
            ->getStyle('C' . $cc . ':H' .  $cc)->getFont()->setBold(true);



          // Redirect output to a client’s web browser (Excel2007)


header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="wholesale_order_form.xlsx"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;



    } // конец WHOLESALEFORM




    if (($_POST['submit'] == BUS_SUBMIT)&&(isset($_POST['delete_orders']))){
      foreach ($_POST['update_oID'] as $order_id){
        $orders_deleted = false;
	    tep_db_query("delete from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
	    tep_db_query("delete from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$order_id . "'");
	    tep_db_query("delete from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . (int)$order_id . "'");
	    tep_db_query("delete from " . TABLE_ORDERS_PRODUCTS_DOWNLOAD . " where orders_id = '" . (int)$order_id . "'");
	    tep_db_query("delete from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int)$order_id . "'");
	    tep_db_query("delete from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$order_id . "'");
        $orders_deleted = true;
        if ($orders_deleted == true) {
         $messageStack->add_session(BUS_DELETE_SUCCESS, 'success');
        } else {
          $messageStack->add_session(BUS_DELETE_WARNING, 'warning');
        }
      }
    }
  // tep_redirect(tep_href_link(FILENAME_ORDERS), tep_get_all_get_params()); //закомментировано чтобы не перебрасывало на главную страницу заказов при массовой смене статусов заказов vladbuk 16.03.2013
   //tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action'))));
   tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action')) . 'action=edit'));

  }
// End Batch Update Status
// BOF: WebMakers.com Added: Downloads Controller
 if($_GET['listing']=="customers") { $sort_by = "o.customers_name"; }
 elseif($_GET['listing']=="customers-desc") { $sort_by = "o.customers_name DESC"; }
 elseif($_GET['listing']=="ottotal") { $sort_by = "order_total"; }
 elseif($_GET['listing']=="ottotal-desc") { $sort_by = "order_total DESC"; }
 elseif($_GET['listing']=="id-asc") { $sort_by = "o.orders_id"; }
 elseif($_GET['listing']=="id-desc") { $sort_by = "o.orders_id DESC"; }
 elseif($_GET['listing']=="status-asc") { $sort_by = "o.orders_status"; }
 elseif($_GET['listing']=="status-desc") { $sort_by = "o.orders_status DESC"; }
 else { $sort_by = "o.orders_id DESC"; }
// EOF: WebMakers.com Added: Downloads Controller

  if (tep_not_null($action)) {
    switch ($action) {
      case 'update_order':
        $oID = tep_db_prepare_input($_GET['oID']);
        $status = tep_db_prepare_input($_POST['status']);
        $comments = tep_db_prepare_input($_POST['comments']);

        $order_updated = false;
        $check_status_query = tep_db_query("select customers_name, customers_email_address, orders_status, date_purchased, ipaddy from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
        $check_status = tep_db_fetch_array($check_status_query);
// BOF: WebMakers.com Added: Downloads Controller
// always update date and time on order_status
        if ( ($check_status['orders_status'] != $status) || $comments != '' || ($status == DOWNLOADS_ORDERS_STATUS_UPDATED_VALUE) ) {
// EOF: WebMakers.com Added: Downloads Controller
          tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . (int)$oID . "'");
// BOF: WebMakers.com Added: Downloads Controller
          $check_status_query2 = tep_db_query("select customers_name, customers_email_address, orders_status, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
          $check_status2 = tep_db_fetch_array($check_status_query2);
          if ( $check_status2['orders_status'] == DOWNLOADS_ORDERS_STATUS_UPDATED_VALUE ) {
            tep_db_query("update " . TABLE_ORDERS_PRODUCTS_DOWNLOAD . " set download_maxdays = '" . DOWNLOAD_MAX_DAYS . "', download_count = '" . DOWNLOAD_MAX_COUNT . "' where orders_id = '" . (int)$oID . "'");
          }
// EOF: WebMakers.com Added: Downloads Controller

          $customer_notified = '0';
          if (isset($_POST['notify']) && ($_POST['notify'] == 'on')) {
            $notify_comments = '';
            if (isset($_POST['notify_comments']) && ($_POST['notify_comments'] == 'on')) {
              $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n\n";
            }

            $email = STORE_NAME . "\n" . STORE_ADDRESS_A . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . (($customer_id > 0) ? EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') : "") . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]) . "\n\n" . $notify_comments;

            //tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT, $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS); //старая версия
            $status_subject = sprintf(EMAIL_TEXT_SUBJECT, $oID, $orders_status_array[$status]);
            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], $status_subject, $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

            $customer_notified = '1';
          }

          tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . (int)$oID . "', '" . tep_db_input($status) . "', now(), '" . tep_db_input($customer_notified) . "', '" . tep_db_input($comments)  . "')");

          $order_updated = true;
        }

        if ($order_updated == true) {
         $messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
        } else {
          $messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
        }

        tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action')) . 'action=edit'));
        break;
      case 'deleteconfirm':
        $oID = tep_db_prepare_input($_GET['oID']);

        tep_remove_order($oID, $_POST['restock']);

        tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action'))));
        break;
    }
  }

  if (($action == 'edit') && isset($_GET['oID'])) {
    $oID = tep_db_prepare_input($_GET['oID']);

    $orders_query = tep_db_query("select orders_id from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
    $order_exists = true;
    if (!tep_db_num_rows($orders_query)) {
      $order_exists = false;
      $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');
    }
  }

  include(DIR_WS_CLASSES . 'order.php');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="includes/general.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?php
  require(DIR_WS_INCLUDES . 'header.php');
?>
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
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
        <?php
  if (($action == 'edit') && ($order_exists == true)) {
    $order = new order($oID);
?>
        <tr>
          <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>

                <td class="pageHeading" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_ORDERS_EDIT, 'oID=' . $_GET['oID']) . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS_INVOICE, 'oID=' . $_GET['oID']) . '" TARGET="_blank">' . tep_image_button('button_invoice.gif', IMAGE_ORDERS_INVOICE) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS_PACKINGSLIP, 'oID=' . $_GET['oID']) . '" TARGET="_blank">' . tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a> '; ?></td>
                <td class="pageHeading" align="right"><?php echo tep_href_manual(FILENAME_ORDERS); ?></td>
              </tr>
            </table></td>
        </tr>


        <tr>
          <td><table border="0" cellspacing="0" cellpadding="2">
              <?php
// BOF: WebMakers.com Added: Show Order Info
?>
              <!-- add Order # // -->
              <tr>
                <td class="mainbig"><?php echo TEXT_INFO_DELETE_DATA_OID; ?></td>
                <td class="mainbig"><b><?php echo tep_db_input($oID); ?></b></td>
              </tr>
              <!-- add date/time // -->
              <tr>
                <td class="main"><b><?php echo TEXT_DATE_ORDER_CREATED; ?></b></td>
                <td class="main"><?php echo tep_datetime_short($order->info['date_purchased']); ?></td>
              </tr>
              <?php
// EOF: WebMakers.com Added: Show Order Info
?>
              <tr>
                <td class="main"><b><?php echo ENTRY_PAYMENT_METHOD; ?></b></td>
                <td class="main"><?php echo $order->info['payment_method']; ?></td>
              </tr>
              <?php
    if (tep_not_null($order->info['cc_type']) || tep_not_null($order->info['cc_owner']) || tep_not_null($order->info['cc_number'])) {
?>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo ENTRY_CREDIT_CARD_TYPE; ?></td>
                <td class="main"><?php echo $order->info['cc_type']; ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo ENTRY_CREDIT_CARD_OWNER; ?></td>
                <td class="main"><?php echo $order->info['cc_owner']; ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo ENTRY_CREDIT_CARD_NUMBER; ?></td>
                <td class="main"><?php echo $order->info['cc_number']; ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo ENTRY_CREDIT_CARD_EXPIRES; ?></td>
                <td class="main"><?php echo $order->info['cc_expires']; ?></td>
              </tr>
              <?php
    }
?>
            </table></td>
        </tr>

        <tr>
          <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
        </tr>

        <tr>
          <td><table width="100%" border="0" cellspacing="0" cellpadding="2">
              <tr>
                <td colspan="3"><?php echo tep_draw_separator(); ?></td>
              </tr>
              <tr>
                <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="2">
                    <?php
             if (SIMPLE_REGISTRATION == 'off') {
?>
                    <tr>
                      <td class="main" valign="top"><b><?php echo ENTRY_CUSTOMER; ?></b></td>
                      <td class="main"><?php echo tep_address_format($order->customer['format_id'], $order->customer, 1, '', '<br>'); ?></td>
                    </tr>
                    <tr>
                      <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
                    </tr>
                    <tr>
                      <td class="main"><b><?php echo ENTRY_TELEPHONE_NUMBER; ?></b></td>
                      <td class="main"><?php echo $order->customer['telephone']; ?></td>
                    </tr>
                    <?php
             }else {
?>
                    <tr>
                      <td class="main" width="13%"><b><?php echo ENTRY_CUSTOMER; ?></b></td>
                      <td class="main"><?php echo stripslashes($order->customer['name']); ?></td>
                    </tr>
                    <?php
             }
?>
                    <tr>
                      <td class="main"><b><?php echo ENTRY_EMAIL_ADDRESS; ?></b></td>
                      <td class="main"><?php echo '<a href="mailto:' . $order->customer['email_address'] . '"><u>' . $order->customer['email_address'] . '</u></a>'; ?></td>
                    </tr>
                    <tr>
                      <td class="main"><b><?php echo ENTRY_IPADDRESS; ?></b></td>
                      <td class="main"><?php echo $order->customer['ipaddy']; ?></td>
                    </tr>
                    <tr>
                      <td class="main"><b><?php echo TEXT_REFERER; ?></b></td>
                      <td class="main"><a href="<?php echo $order->info['customers_ref_url'];  ?>" target="_blank"><?php echo parse_url($order->info['customers_ref_url'], PHP_URL_HOST); ?></a></td>
                    </tr>
                  </table></td>
                <?php
            if (SIMPLE_REGISTRATION == 'off') {
?>
                <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="2">
                    <tr>
                      <!-- <td class="main" valign="top"><b><?php //echo ENTRY_SHIPPING_ADDRESS; ?></b></td> -->
                      <td class="main"><b><?php echo ENTRY_SHIPPING_ADDRESS; ?></b><br><br>
                          <?php echo tep_address_format($order->delivery['format_id'], $order->delivery, 1, '', '<br>'); ?></td>
                    </tr>
                  </table></td>
                <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="2">
                    <tr>
                      <!-- <td class="main" valign="top"><b><?php //echo ENTRY_BILLING_ADDRESS; ?></b></td> -->
                      <td class="main"><b><?php echo ENTRY_BILLING_ADDRESS; ?></b><br><br>
                          <?php echo tep_address_format($order->billing['format_id'], $order->billing, 1, '', '<br>'); ?></td>
                    </tr>
                  </table></td>
                <?php
            }
?>
              </tr>
            </table></td>
        </tr>


        <tr>
          <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
        </tr>
        <tr>
          <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" colspan="2"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TAX; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_PRICE_EXCLUDING_TAX; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_PRICE_INCLUDING_TAX; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_EXCLUDING_TAX; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_INCLUDING_TAX; ?></td>
              </tr>
              <?php
    for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
      echo '          <tr class="dataTableRow">' . "\n" .
           '            <td class="dataTableContent" valign="top" align="right">' . $order->products[$i]['qty'] . '&nbsp;x</td>' . "\n" .
           '            <td class="dataTableContent" valign="top">' . $order->products[$i]['name'];

      if (isset($order->products[$i]['attributes']) && (sizeof($order->products[$i]['attributes']) > 0)) {
        for ($j = 0, $k = sizeof($order->products[$i]['attributes']); $j < $k; $j++) {
          echo '<br><nobr><small>&nbsp;<i> - ' . $order->products[$i]['attributes'][$j]['option'] . ': ' . $order->products[$i]['attributes'][$j]['value'];
          if ($order->products[$i]['attributes'][$j]['price'] != '0') echo ' (' . $order->products[$i]['attributes'][$j]['prefix'] . $currencies->format($order->products[$i]['attributes'][$j]['price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . ')';
          echo '</i></small></nobr>';
        }
      }

      echo '            </td>' . "\n" .
           '            <td class="dataTableContent" valign="top">' . $order->products[$i]['model'] . '</td>' . "\n" .
           '            <td class="dataTableContent" align="right" valign="top">' . tep_display_tax_value($order->products[$i]['tax']) . '%</td>' . "\n" .
           '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($order->products[$i]['final_price'], true, $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n" .
           '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']), true, $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n" .
           '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($order->products[$i]['final_price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n" .
           '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']) * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n";
      echo '          </tr>' . "\n";
    }
?>
              <tr>
                <td align="right" colspan="8"><table border="0" cellspacing="0" cellpadding="2">
                    <?php
    for ($i = 0, $n = sizeof($order->totals); $i < $n; $i++) {
      echo '              <tr>' . "\n" .
           '                <td align="right" class="smallText">' . $order->totals[$i]['title'] . '</td>' . "\n" .
           '                <td align="right" class="smallText">' . $order->totals[$i]['text'] . '</td>' . "\n" .
           '              </tr>' . "\n";
    }
?>
                  </table></td>
              </tr>
            </table></td>
        </tr>
        <tr>
          <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
        </tr>
        <tr>
          <td class="main"><table border="1" cellspacing="0" cellpadding="5">
              <tr>
                <td class="smallText" align="center"><b><?php echo TABLE_HEADING_DATE_ADDED; ?></b></td>
                <td class="smallText" align="center"><b><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></b></td>
                <td class="smallText" align="center"><b><?php echo TABLE_HEADING_STATUS; ?></b></td>
                <td class="smallText" align="center"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></td>
              </tr>
              <?php
    $orders_history_query = tep_db_query("select orders_status_id, date_added, customer_notified, comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . tep_db_input($oID) . "' order by date_added");
    if (tep_db_num_rows($orders_history_query)) {
      while ($orders_history = tep_db_fetch_array($orders_history_query)) {
        echo '          <tr>' . "\n" .
             '            <td class="smallText" align="center">' . tep_datetime_short($orders_history['date_added']) . '</td>' . "\n" .
             '            <td class="smallText" align="center">';
        if ($orders_history['customer_notified'] == '1') {
          echo tep_image(DIR_WS_ICONS . 'tick.gif', ICON_TICK) . "</td>\n";
        } else {
          echo tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS) . "</td>\n";
        }
        echo '            <td class="smallText">' . $orders_status_array[$orders_history['orders_status_id']] . '</td>' . "\n" .
             '            <td class="smallText">' . nl2br(tep_db_output($orders_history['comments'])) . '&nbsp;</td>' . "\n" .
             '          </tr>' . "\n";
      }
    } else {
        echo '          <tr>' . "\n" .
             '            <td class="smallText" colspan="5">' . TEXT_NO_ORDER_HISTORY . '</td>' . "\n" .
             '          </tr>' . "\n";
    }
?>
              <!-- Ajout pour order_status_default_comment_v.1 //-->
              <script type="text/javascript"><!--
var comment_array = new Array();
<?php
for ($i=0, $n=sizeof($orders_statuses); $i<$n; $i++) {
    if ($orders_default_comment_array[$orders_statuses[$i]['id']] <> '') {
    // if ($orders_default_comment_array[$orders_statuses[$i]['id']]['comment'] <> '') { // локально с этим не работает
        echo 'comment_array["' . $orders_statuses[$i]['id'] . '"] = "' . $orders_default_comment_array[ $orders_statuses[$i]['id'] ] . '";' . "\n";
    } else {
        echo 'comment_array["' . $orders_statuses[$i]['id'] . '"] = "";' . "\n";
    }
}
?>
function updateDefaultComment() {
var selected_value = document.forms["status"].status.options[document.forms["status"].status.selectedIndex].value;
var trackNumber = document.getElementById("track_number").value;
var newComment = comment_array[selected_value].replace(/49098/, trackNumber);
document.forms["status"].comments.value = newComment;
}
//--></script>
              <?php
      $defaultComment = $orders_default_comment_array[ $order->info['orders_status'] ];
?>
              <!-- Fin ajout pour order_status_default_comment_v.1 //-->
            </table></td>
        </tr>
        <tr>
          <td class="main"><br>
            <b><?php echo TABLE_HEADING_COMMENTS; ?></b></td>
        </tr>
        <tr>
          <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
        </tr>
        <tr><?php echo tep_draw_form('status', FILENAME_ORDERS, tep_get_all_get_params(array('action')) . 'action=update_order'); ?>
          <!-- Ancien code pour order_status_default_comment_v.1 <td class="main"><?php //echo tep_draw_textarea_field('comments', 'soft', '60', '5'); ?></td>
<!-- Nouveau code pour order_status_default_comment_v.1 //-->
          <td class="main"><?php echo tep_draw_textarea_field('comments', 'soft', '60', '5', $defaultComment); ?></td>
          <!-- Fin nouveau code pour order_status_defaults_comment_v.1 //-->
        </tr>
        <tr>
          <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
        </tr>
        <tr>
          <td class="main"><b>Трек-код: </b> <?php echo tep_draw_input_field('track_number', '', "placeholder='Введите трек-код посылки' size='25' id='track_number'"); ?></td>
        </tr>
        <tr>
          <td><table border="0" cellspacing="0" cellpadding="2">
              <tr>
                <td><table border="0" cellspacing="0" cellpadding="2">
                    <tr>
                      <!-- Ancien code pour order_status_default_comment_v.1 <td class="main"><b><?php echo ENTRY_STATUS; ?></b> <?php echo tep_draw_pull_down_menu('status', $orders_statuses, $order->info['orders_status']); ?></td> //-->
                      <!-- Nouveau code pour order_status_default_comment_v.1 //-->
                      <td class="main"><b><?php echo ENTRY_STATUS; ?></b> <?php echo tep_draw_pull_down_menu('status', $orders_statuses, $order->info['orders_status'], 'onchange="updateDefaultComment()"'); ?></td>
                      <!-- Fin nouveau code pour order_statu_defaults_comment_v.1 //-->
                    </tr>
                    <tr>
                      <td class="main"><b><?php echo ENTRY_NOTIFY_CUSTOMER; ?></b> <?php echo tep_draw_checkbox_field('notify', '', true); ?></td>
                      <td class="main"><b><?php echo ENTRY_NOTIFY_COMMENTS; ?></b> <?php echo tep_draw_checkbox_field('notify_comments', '', true); ?></td>
                    </tr>
                  </table></td>
                <td valign="top"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
              </tr>
            </table></td>
          </form>
        </tr>
        <tr>
          <td colspan="2" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_FINOTCHET, 'oID=' . $_GET['oID']) . '" TARGET="_blank">' . tep_image_button('button_edit.gif', Финотчёт) . '</a><a href="' . tep_href_link(FILENAME_ORDERS_EDIT, 'oID=' . $_GET['oID']) . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS_INVOICE, 'oID=' . $_GET['oID']) . '" TARGET="_blank">' . tep_image_button('button_invoice.gif', IMAGE_ORDERS_INVOICE) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS_PACKINGSLIP, 'oID=' . $_GET['oID']) . '" TARGET="_blank">' . tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a> '; ?></td>
        </tr>
        <?php
  } else {
?>
        <tr>
          <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
                <td align="right"><table border="0" width="100%" cellspacing="0" cellpadding="0">
                    <tr><?php echo tep_draw_form('orders', FILENAME_ORDERS, '', 'get'); ?>
                      <td class="smallText" align="right"><?php echo HEADING_TITLE_SEARCH . ' ' . tep_draw_input_field('oID', '', 'size="12"') . tep_draw_hidden_field('action', 'edit'); ?>
                      </form>
                      <?php echo tep_draw_form('status', FILENAME_ORDERS, '', 'get'); ?>
                      &nbsp;<?php echo HEADING_TITLE_STATUS . ' ' . tep_draw_pull_down_menu('status', array_merge(array(array('id' => '', 'text' => TEXT_ALL_ORDERS)), $orders_statuses), '', 'onChange="this.form.submit();"'); ?></td>
                      </form>
                    </tr>
                  </table></td>
                  <td class="pageHeading" align="right"><?php echo tep_href_manual(FILENAME_ORDERS); ?></td>
              </tr>
            </table></td>
        </tr>
        <!-- Start Batch Update Status v0.4 -->
        <tr>
          <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td valign="top">
                <?php echo tep_draw_form('UpdateStatus', FILENAME_ORDERS,tep_get_all_get_params()); ?>
<script language="javascript">
function checkAll(){
  var el = document.getElementsByName('update_oID[]')
  for(i=0;i<el.length;i++){
    el[i].checked = true;
  }
}
function uncheckAll(){
  var el = document.getElementsByName('update_oID[]')
  for(i=0;i<el.length;i++){
    el[i].checked = false;
  }
}

</script>
                  <table border="0" width="100%" cellspacing="0" cellpadding="2">
                    <tr class="dataTableHeadingRow">
                      <td></td>
                      <?php
// BOF: WebMakers.com modified : arrange sort order
?>
                      <td class="dataTableHeadingContent"><a href="<?php echo "$PHP_SELF?listing=customers"; ?>"><?php echo tep_image(DIR_WS_ICONS . 'icon_up.gif',  TABLE_HEADING_SORT .' '. TABLE_HEADING_CUSTOMERS . ' --> A-B-C  '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=customers-desc"; ?>"><?php echo tep_image(DIR_WS_ICONS . 'icon_down.gif', TABLE_HEADING_SORT .' '. TABLE_HEADING_CUSTOMERS . ' --> Z-X-Y  '); ?></a><br>
                        <?php echo TABLE_HEADING_CUSTOMERS; ?></td>
                      <td class="dataTableHeadingContent" align="center"><a href="<?php echo "$PHP_SELF?listing=id-asc"; ?>"><?php echo tep_image(DIR_WS_ICONS . 'icon_up.gif', TABLE_HEADING_SORT .' '. TABLE_HEADING_ORDER_ID . ' --> 1-2-3  '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=id-desc"; ?>"><?php echo tep_image(DIR_WS_ICONS . 'icon_down.gif', TABLE_HEADING_SORT .' '. TABLE_HEADING_ORDER_ID . ' --> 3-2-1  '); ?></a><br>
                        <?php echo TABLE_HEADING_ORDER_ID; ?></td>
                      <td class="dataTableHeadingContent" align="center"><a href="<?php echo "$PHP_SELF?listing=ottotal"; ?>"><?php echo tep_image(DIR_WS_ICONS . 'icon_up.gif', TABLE_HEADING_SORT .' '. TABLE_HEADING_ORDER_TOTAL . ' --> 1-2-3  '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=ottotal-desc"; ?>"><?php echo tep_image(DIR_WS_ICONS . 'icon_down.gif', TABLE_HEADING_SORT .' '. TABLE_HEADING_ORDER_TOTAL . ' --> 3-2-1  '); ?></a><br>
                        <?php echo TABLE_HEADING_ORDER_TOTAL; ?></td>
                      <td class="dataTableHeadingContent" align="center"><a href="<?php echo "$PHP_SELF?listing=id-asc"; ?>"><?php echo tep_image(DIR_WS_ICONS . 'icon_up.gif', TABLE_HEADING_SORT .' '. TABLE_HEADING_DATE_PURCHASED . ' --> 1-2-3  '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=id-desc"; ?>"><?php echo tep_image(DIR_WS_ICONS . 'icon_down.gif', TABLE_HEADING_SORT .' '. TABLE_HEADING_DATE_PURCHASED . ' --> 3-2-1  '); ?></a><br>
                        <?php echo TABLE_HEADING_DATE_PURCHASED; ?></td>
                      <td class="dataTableHeadingContent" align="right"><a href="<?php echo "$PHP_SELF?listing=status-asc"; ?>"><?php echo tep_image(DIR_WS_ICONS . 'icon_up.gif', TABLE_HEADING_SORT .' '. TABLE_HEADING_STATUS . ' --> 1-2-3  '); ?></a>&nbsp;<a href="<?php echo "$PHP_SELF?listing=status-desc"; ?>"><?php echo tep_image(DIR_WS_ICONS . 'icon_down.gif', TABLE_HEADING_SORT .' '. TABLE_HEADING_STATUS . ' --> 3-2-1  '); ?></a><br>
                        <?php echo TABLE_HEADING_STATUS; ?></td>
                      <td class="dataTableHeadingContent" align="right"><br>
                        <?php echo TABLE_HEADING_ACTION; ?></td>
                      <?php
// EOF: WebMakers.com modified : arrange sort order
?>
                    </tr>
                    <?php
    if (isset($_GET['cID'])) {
      $cID = tep_db_prepare_input($_GET['cID']);
      $orders_query_raw = "select o.orders_id, o.customers_name, o.customers_id, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.customers_id = '" . (int)$cID . "' and o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and ot.class = 'ot_total' order by " . $sort_by;
    } elseif (isset($_GET['status']) && is_numeric($_GET['status']) && ($_GET['status'] > 0)) {
      $status = tep_db_prepare_input($_GET['status']);
      $orders_query_raw = "select o.orders_id, o.customers_name, o.customers_id, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and s.orders_status_id = '" . (int)$status . "' and ot.class = 'ot_total' order by " . $sort_by;
    } else {
      $orders_query_raw = "select o.orders_id, o.customers_name, o.customers_id, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and ot.class = 'ot_total' order by " . $sort_by;
    }
    $orders_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS * 2, $orders_query_raw, $orders_query_numrows);
    $orders_query = tep_db_query($orders_query_raw);
    while ($orders = tep_db_fetch_array($orders_query)) {
    if ((!isset($_GET['oID']) || (isset($_GET['oID']) && ($_GET['oID'] == $orders['orders_id']))) && !isset($oInfo)) {
        $oInfo = new objectInfo($orders);
    }
      // Start Batch Update Status
      if (isset($oInfo) && is_object($oInfo) && ($orders['orders_id'] == $oInfo->orders_id)) {
        echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" >' . "\n";
        $onclick = 'onclick="document.location.href=\'' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id . '&action=edit') . '\'"';
      } else {
        echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" >' . "\n";
        $onclick = 'onclick="document.location.href=\'' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID')) . 'oID=' . $orders['orders_id']) . '\'"';
      }
?>
                    <td class="dataTableContent"><input type="checkbox" name="update_oID[]" value="<?php echo $orders['orders_id'];?>"></td>
                      <td class="dataTableContent" <?php echo $onclick; ?>><?php echo '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $orders['orders_id'] . '&action=edit') . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW) . '</a>&nbsp;' . $orders['customers_name'].' ['.$orders['customers_id'].']'; ?></td>
                      <td class="dataTableContent" align="center" <?php echo $onclick; ?>><?php echo strip_tags($orders['orders_id']); ?></td>
                      <td class="dataTableContent" <?php echo $onclick; ?>><?php echo strip_tags($orders['order_total']); ?></td>
                      <td width="15%" class="dataTableContent" align="center" <?php echo $onclick; ?>><?php echo tep_datetime_short($orders['date_purchased']); ?></td>
                      <td width="15%" class="dataTableContent" align="right" <?php echo $onclick; ?>><?php echo $orders['orders_status_name']; ?></td>
                      <td width="10%" class="dataTableContent" align="right" <?php echo $onclick; ?>><?php if (isset($oInfo) && is_object($oInfo) && ($orders['orders_id'] == $oInfo->orders_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID')) . 'oID=' . $orders['orders_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>
                        &nbsp;</td>
                    </tr>
                    <?php
    }
?>
                    <tr>
                     <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
                    <?php
    echo '<tr class="dataTableContent"><td colspan="4">' . BUS_HEADING_TITLE  . tep_draw_pull_down_menu('new_status', array_merge(array(array('id' => '', 'text' => BUS_TEXT_NEW_STATUS)), $orders_statuses), '', '');
    echo '</td><td colspan="3" width="72%">' . tep_draw_checkbox_field('notify','1',false) . ' ' . BUS_NOTIFY_CUSTOMERS . '</td></tr>';
    echo '<tr class="dataTableContent" align="left"><td colspan="7" nobr="nobr" align="left">' .
    TEXT_ORDERS_DELETE  . tep_draw_checkbox_field('delete_orders','1') . '</td></tr>';
    echo '<tr class="dataTableContent" align="center"><td colspan="7" nobr="nobr" align="left">' .
    tep_draw_input_field('select_all',BUS_SELECT_ALL,'onclick="checkAll(); return false;"','','submit') .'&nbsp;&nbsp;'.
    tep_draw_input_field('select_none',BUS_SELECT_NONE,'onclick="uncheckAll(); return false;"','','submit') .'&nbsp;&nbsp;'.
    tep_draw_input_field('submit',BUS_SUBMIT,'','','submit') . ' &nbsp;&nbsp;'.
    tep_draw_input_field('submit',WHOLESALEFORM,'','','submit') . '</td></tr>';
    echo '<tr><td colspan="7">' . tep_black_line() . '</td></tr>';
// End Batch Update Status
?>
                    </form>
                    <tr>
                      <td colspan="7"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                          <tr>
                            <td class="smallText" valign="top"><?php echo $orders_split->display_count($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS * 2, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_ORDERS); ?></td>
                            <td class="smallText" align="right"><?php echo $orders_split->display_links($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS * 2, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], tep_get_all_get_params(array('page', 'oID', 'action'))); ?></td>
                          </tr>
                        </table></td>
                    </tr>
                  </table></td>
                <?php
  $heading = array();
  $contents = array();

    switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_ORDER . '</b>');

      $contents = array('form' => tep_draw_form('orders', FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO . '<br><br><b>' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname . '</b>');
      $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('restock') . ' ' . TEXT_INFO_RESTOCK_PRODUCT_QUANTITY);
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (isset($oInfo) && is_object($oInfo)) {
        $heading[] = array('text' => '<b>[' . $oInfo->orders_id . ']&nbsp;&nbsp;' . tep_datetime_short($oInfo->date_purchased) . '</b>');

      $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id . '&action=edit') . '">' . tep_image_button('button_details.gif', IMAGE_DETAILS) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');
      $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_ORDERS_INVOICE, 'oID=' . $oInfo->orders_id) . '" TARGET="_blank">' . tep_image_button('button_invoice.gif', IMAGE_ORDERS_INVOICE) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS_PACKINGSLIP, 'oID=' . $oInfo->orders_id) . '" TARGET="_blank">' . tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP) . '</a> <a href="' . tep_href_link(FILENAME_POST_ADDRESS, 'oID=' . $oInfo->orders_id) . '" TARGET="_blank">' . tep_image_button('button_address.gif', IMAGE_POST_ADDRESS) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS_EDIT, 'oID=' . $oInfo->orders_id) . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a>');
        if (tep_not_null($oInfo->last_modified)) $contents[] = array('text' => TEXT_DATE_ORDER_LAST_MODIFIED . ' ' . tep_date_short($oInfo->last_modified));
        $contents[] = array('text' => '<br>' . TEXT_INFO_PAYMENT_METHOD . ' '  . $oInfo->payment_method);
      }
      break;
    }

    if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
       echo '<td width="25%" valign="top">' . "\n";
       $box = new box;
       echo $box->infoBox($heading, $contents);
       echo '</td>' . "\n";
    }
?>
              </tr>
            </table></td>
        </tr>
        <?php
  }
?>
      </table></td>
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
