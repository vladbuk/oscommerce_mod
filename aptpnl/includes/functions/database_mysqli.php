<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2007 osCommerce

  Released under the GNU General Public License
*/

  function tep_db_connect($server = DB_SERVER, $username = DB_SERVER_USERNAME, $password = DB_SERVER_PASSWORD, $database = DB_DATABASE, $link = 'db_link') {
    global $$link;

    if (USE_PCONNECT == 'true') {
        $$link = mysqli_pconnect($server, $username, $password, $database);
     } else {
         $$link = mysqli_connect($server, $username, $password, $database);
     }

    //if (!$$link) die("Can not access database");

    mysqli_query($$link, "set character_set_client='utf8'");
    mysqli_query($$link, "set character_set_results='utf8'");
    mysqli_query($$link, "set collation_connection='utf8_general_ci'");

    return $$link;
  }

  function tep_db_close($link = 'db_link') {
    global $$link;

    return mysqli_close($$link);
  }

  function tep_db_error($query, $errno, $error) {
    die('<font color="#000000"><strong>' . $errno . ' - ' . $error . '<br /><br />' . $query . '<br /><br /><small><font color="#ff0000">[TEP STOP]</font></small><br /><br /></strong></font>');
  }

  function tep_db_query($query, $link = 'db_link') {
    global $$link;

    if (!$$link) return;

    if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true')) {
      $debugs = debug_backtrace();
      $debug = isset($debugs[1]) ?  $debugs[1] : null;
      if ($debug) {
          if (tep_not_null($debug['function'])) {
              error_log('CALLEE ' . $debug['file'] . ' (line: '.$debug['line'].' function: '.$debug['function'].') ' . "\n", 3, STORE_PAGE_PARSE_TIME_LOG);
          } else {
              error_log('CALLEE ' . $debug['file'] . ' (line: '.$debug['line'].') ' . "\n", 3, STORE_PAGE_PARSE_TIME_LOG);
          }
      }
      error_log('QUERY ' . $query . "\n", 3, STORE_PAGE_PARSE_TIME_LOG);
    }

    $result = mysqli_query($$link, $query) or tep_db_error($query, mysqli_errno(), mysqli_error());

    if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true')) {
       $result_error = mysqli_error();
       error_log('RESULT ' . $result . ' ' . $result_error . "\n", 3, STORE_PAGE_PARSE_TIME_LOG);
    }

    return $result;
  }

  function tep_db_multi_query($query, $link = 'db_link') {
    global $$link;

    if (!$$link) return;
    if (!is_array($query)) return tep_db_query($query, $link);

    $query_text = is_array($query)? implode(";", $query) : $query;

    if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true')) {
      error_log('QUERY ' . $query_text . "\n", 3, STORE_PAGE_PARSE_TIME_LOG);
    }

    $results = array();
    if (mysqli_multi_query($$link, $query_text)) {
        do {
            /* store first result set */
            if ($result = mysqli_store_result($$link)) {
                $results[] = $result;
            }
        } while (mysqli_more_results($$link) && mysqli_next_result($$link));
    } else {
        tep_db_error($query_text, mysqli_errno(), mysqli_error());
    }

    if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true')) {
       $result_error = mysqli_error();
       error_log('RESULT ' . $result . ' ' . $result_error . "\n", 3, STORE_PAGE_PARSE_TIME_LOG);
    }

    return $results;
  }

  function tep_db_perform($table, $data, $action = 'insert', $parameters = '', $link = 'db_link') {
    reset($data);
    if ($action == 'insert') {
      $query = 'insert into ' . $table . ' (';
      while (list($columns, ) = each($data)) {
        $query .= $columns . ', ';
      }
      $query = substr($query, 0, -2) . ') values (';
      reset($data);
      while (list(, $value) = each($data)) {
        switch ((string)$value) {
          case 'now()':
            $query .= 'now(), ';
            break;
          case 'null':
            $query .= 'null, ';
            break;
          default:
            $query .= '\'' . tep_db_input($value) . '\', ';
            break;
        }
      }
      $query = substr($query, 0, -2) . ')';
    } elseif ($action == 'update') {
      $query = 'update ' . $table . ' set ';
      while (list($columns, $value) = each($data)) {
        switch ((string)$value) {
          case 'now()':
            $query .= $columns . ' = now(), ';
            break;
          case 'null':
            $query .= $columns .= ' = null, ';
            break;
          default:
            $query .= $columns . ' = \'' . tep_db_input($value) . '\', ';
            break;
        }
      }
      $query = substr($query, 0, -2) . ' where ' . $parameters;
    }

    return tep_db_query($query, $link);
  }

  function tep_db_fetch_array($db_query, $type = MYSQLI_ASSOC) {
    return mysqli_fetch_array($db_query, $type);
  }

  function tep_db_num_rows($db_query) {
    return mysqli_num_rows($db_query);
  }

  function tep_db_data_seek($db_query, $row_number) {
    return mysqli_data_seek($db_query, $row_number);
  }

  function tep_db_insert_id($link = 'db_link') {
    global $$link;
    return mysqli_insert_id($$link);
  }

  function tep_db_free_result($db_query) {
    return @mysqli_free_result($db_query);
  }

  function tep_db_fetch_fields($db_query) {
    return mysqli_fetch_field($db_query);
  }

  function tep_db_output($string) {
    return htmlspecialchars($string);
  }

  function tep_db_input($string, $link = 'db_link') {
    global $$link;
    if (!$$link) return tep_db_prepare_input($string);

    if (function_exists('mysqli_real_escape_string')) {
      return mysqli_real_escape_string($$link, $string);
    } elseif (function_exists('mysqli_escape_string')) {
      return mysqli_escape_string($$link, $string);
    }

    return addslashes($string);
  }

  function tep_db_prepare_input($string) {
    if (is_string($string)) {
      return trim(tep_sanitize_string(stripslashes($string)));
    } elseif (is_array($string)) {
      reset($string);
      while (list($key, $value) = each($string)) {
        $string[$key] = tep_db_prepare_input($value);
      }
      return $string;
    } else {
      return $string;
    }
  }
?>
