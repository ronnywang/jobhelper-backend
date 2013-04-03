<?php

include(__DIR__ . '/../init.inc.php');

$curl = curl_init();
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($curl, CURLOPT_URL, 'http://www.bola.taipei.gov.tw/ct.asp?xItem=41223990&ctNode=62846&mp=116003');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$ret = curl_exec($curl);

$doc = new DOMDocument();
@$doc->loadHTML($ret);

$table_dom = $doc->getElementsByTagName('tbody')->item(0);

$fp = fopen('php://output', 'w');

fputcsv($fp, array(
    '事業名稱',
    '時間',
    '違反事由',
    '原始連結',
    '截圖連結',
));
foreach ($table_dom->getElementsByTagName('tr') as $tr_dom) {
    if (!$tr_dom->getElementsByTagName('td')->length) {
        continue;
    }
    $td_doms = $tr_dom->getElementsByTagName('td');
    $name = $td_doms->item(1)->nodeValue;
    $violation = $td_doms->item(2)->nodeValue;
    $violation_content = $td_doms->item(3)->nodeValue;
    $number = $td_doms->item(4)->nodeValue;
    $date = $td_doms->item(5)->nodeValue;

    if (!preg_match('#(.*)年(.*)月(.*)日#', $date, $matches)) {
        continue;
    }

    $time = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1] + 1911);


    fputcsv($fp, array(
        trim($name),
        date('Y/m/d', $time),
        trim('違反 勞動基準法' . $violation . ', 文號: ' . $number),
        'http://www.bola.taipei.gov.tw/ct.asp?xItem=41223990&ctNode=62846&mp=116003',
        '',
    ));
}
