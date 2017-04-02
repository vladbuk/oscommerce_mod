<?php
/**
 * [curRate description]
 * @param  string $currencyCode currency shortcode (USD)
 * @return float               [NBU rate on current day, by default - USD rate]
 */
 function quote_nbu_currency($code) {
     $codeRate = json_decode(file_get_contents("https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?valcode={$code}&json"), true);
    //  var_dump($codeRate);
     print_r($codeRate);

     if (!empty($codeRate)) {
         return sprintf("%.6f", $codeRate[0]["rate"]);
     }
     return false;
 }

// if (curRate("USD")) {
//     echo curRate("USD");
// } else {
//     echo "Курс неизвестен или код валюты введен не верно";
// }


function quote_cbr_currency($code, $base = DEFAULT_CURRENCY) {
  global $quote_cbr_cashed;
  if (sizeof($quote_cbr_cash)==0){
    $quote_cbr_cash = array();
    $quote_cbr_cash['RUB'] = 1.00;
    $quote_cbr_cash['RUR'] = 1.00;
    $page = file('http://www.cbr.ru/scripts/XML_daily.asp');
    if (!is_array($page)){ // ���-�� �� ��� � ��� � ���
      return false;
    }
    $page = implode('', $page);
    preg_match_all("|<CharCode>(.*?)</CharCode>|is", $page, $m);
    preg_match_all("|<Value>(.*?)</Value>|is", $page, $c);
    foreach ($m[1] as $kv => $mv){
      $quote_cbr_cash[$mv]=preg_replace('/,/', '/./', $c[1][$kv]);
    }
  }
  if (isset($quote_cbr_cash[$code]) && isset($quote_cbr_cash[$base])) {
    $retval = round($quote_cbr_cash[$base]/$quote_cbr_cash[$code],4) / 10;
    settype($retval,"string");
    return $retval;
  } else {
    return false;
  }
}



function quote_xe_currency($to, $from = DEFAULT_CURRENCY) {
  $page = file('http://www.xe.net/ucc/convert.cgi?Amount=1&From=' . $from . '&To=' . $to);

  $match = array();

  preg_match('/[0-9.]+\s*' . $from . '\s*=\s*([0-9.]+)\s*' . $to . '/', implode('', $page), $match);

  if (sizeof($match) > 0) {
    return $match[1];
  } else {
    return false;
  }
}

// echo quote_cbr_currency("USD", "UAH") . PHP_EOL;
echo quote_nbu_currency("UAH");
// echo quote_xe_currency("USD", "UAH");
//
// echo 1 / 1;
