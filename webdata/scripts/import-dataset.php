<?php

include(__DIR__ . '/../init.inc.php');

function get_csv_from_url($url, $cache = true)
{
    if (preg_match('#https://github.com/([^/]*)/([^/]*)/blob/(.*)#', $url, $matches)) {
        $url = "https://raw.githubusercontent.com/{$matches[1]}/{$matches[2]}/{$matches[3]}";
    } elseif (preg_match('#https://docs.google.com/spreadsheets/d/([^/]*)/edit[\#?]gid=([0-9]*)#', $url, $matches)) {
        $url = "https://docs.google.com/spreadsheets/d/{$matches[1]}/export?gid={$matches[2]}&format=csv";
    } elseif (preg_match('#https://docs.google.com/spreadsheets/d/([^/]*)/edit#', $url, $matches)) {
        $url = "https://docs.google.com/spreadsheets/d/{$matches[1]}/export?format=csv";
    } elseif (preg_match('#https://sheethub.com#', $url)) {
        $url = $url . '?format=csv';
    } else {
        throw new Exception("unknown url: $url");
    }

    $hash = crc32($url);
    error_log($hash);
    if (!file_exists("tmp/{$hash}.csv") or !$cache) {
        file_put_contents("tmp/{$hash}.csv", file_get_contents($url));
    }
    return fopen("tmp/{$hash}.csv", 'r');
}

// 先從舊資料拉
$list_urls = array(
    'https://docs.google.com/spreadsheets/d/1Injr3_jajH9Ygr7NVzx_nguJSdBwblg2_jio1lo3Eew/edit#gid=2064726005',
    'https://docs.google.com/spreadsheets/d/1Injr3_jajH9Ygr7NVzx_nguJSdBwblg2_jio1lo3Eew/edit?gid=1643567694',
);


foreach ($list_urls as $list_url) {
    // [0] => 2014/1/19
    // [1] => https://github.com/nansenat16/LSA-CSV/blob/master/00/101_Q3.csv
    // [2] => 基隆市
    // [3] => 2014/1/19
    // [4] => 101_Q3.csv
    // [5] => http://www.klcg.gov.tw/social/home.jsp?mserno=200709080001&serno=200709080009&menudata=SocialMenu&contlink=ap/news_view.jsp&dataserno=201111220003
    // [6] => https://www.evernote.com/pub/view/nansenat16/no-LSA/f9618bb0-3fba-4cf9-b8ba-deb2c4a44ccf?locale=zh_TW#b=58467a7a-2e6a-4327-ad9b-3f90780787e1&st=p&n=80fbccd5-54b7-4fc5-b0d6-5ad70447ff6f
    $list_fp = get_csv_from_url($list_url, false);
    $columns = fgetcsv($list_fp);
    $columns = array('created_at', 'data_url', 'county', 'published_at', 'data_title', 'origin_url', 'snapshot_url');
    while ($rows = fgetcsv($list_fp)) {
        $values = array_combine($columns, $rows);
        $values['created_at'] = strtotime($values['created_at']);
        $values['published_at'] = strtotime($values['published_at']);

        DataSet::insert(array(
            'data' => json_encode($values),
        ));
    }
}

