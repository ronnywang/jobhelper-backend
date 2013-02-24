<?php

include(__DIR__ . '/../init.inc.php');

$curl = curl_init();
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($curl, CURLOPT_URL, 'http://www.bola.taipei.gov.tw/ct.asp?xItem=41223990&ctNode=62846&mp=116003');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$ret = curl_exec($curl);

$doc = new DOMDocument();
@$doc->loadHTML($ret);

foreach ($doc->getElementsByTagName('table') as $table_dom) {
    if ($table_dom->getAttribute('summary') == '臺北市違反勞動基準法事業單位事業主公布一覽表') {
        break;
    }
}

foreach ($table_dom->getElementsByTagName('tr') as $tr_dom) {
    if (!$tr_dom->getElementsByTagName('td')->length) {
        continue;
    }
    $td_doms = $tr_dom->getElementsByTagName('td');
    $name = $td_doms->item(1)->nodeValue;
    $violation = $td_doms->item(2)->nodeValue;
    $number = $td_doms->item(3)->nodeValue;
    $date = $td_doms->item(4)->nodeValue;

    if (!$no = CompanyService::getCompanyByName($name)) {
        // TODO: 找不到
        continue;
    }

    if (!preg_match('#(.*)年(.*)月(.*)日#', $date, $matches)) {
        continue;
    }

    $time = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1] + 1911);

    CompanyEvent::insert(array(
        'company_no' => $no,
        'time' => $time,
        'from' => 'http://www.bola.taipei.gov.tw/ct.asp?xItem=41223990&ctNode=62846&mp=116003',
        'snapshot' => '',
        'message' => "於 {$date} 違反 {$violation}, 文號: {$number}",
    ));
}
