<?php

include(__DIR__ . '/../init.inc.php');

$county_datasets = array();
foreach (DataSet::search(1) as $data_set) {
    $data = json_decode($data_set->data);
    $county = ImportLib::normalize_county($data->county);

    if (!array_key_exists($county, $county_datasets)) {
        $county_datasets[$county] = array();
    }

    $county_datasets[$county][] = $data;
}
$output = fopen('dump/all.csv', 'w');
$output_dataset = fopen('dump/dataset.csv', 'w');
// county,data_title,publish_data,origin_url,data_url,snapshot_url,filename
fputs($output, "縣市,資料集名稱,事業單位,處罰日期,法條,法條內容,字號\n");
fputs($output_dataset, "縣市,資料集名稱,公布日期,原始網址,整理後資料網址,截圖網址,檔名\n");
$no = 1;

foreach (Package::search(1) as $package) {
    $choose_county = null;
    foreach ($county_datasets as $c => $datasets) {
        if (strpos($package->name, $c) === 0) {
            $choose_county = $c;
            break;
        }
    }

    if (is_null($choose_county)) {
        error_log("{$package->name} 無對應資料集");
        continue;
    }

    $datasets = $county_datasets[$choose_county];
    unset($county_datasets[$choose_county]);
    usort($datasets, function($a, $b) { return $a->published_at - $b->published_at; });
    $contents = array();
    $showed = array();

    $max_published_at = max(array_map(function($d) { return $d->data_url ? $d->published_at : 0; }, $datasets));

    foreach ($datasets as $dataset) {
        // county,data_title,publish_data,origin_url,data_url,snapshot_url,filename
        fputcsv($output_dataset, array(
            $choose_county,
            $dataset->data_title,
            date('Y/m/d', $dataset->published_at),
            $dataset->origin_url,
            $dataset->data_url,
            $dataset->snapshot_url,
            $dataset->data_url ? "{$no}.csv" : "",
        ));
        if (!$dataset->data_url) {
            continue;
        }
        $fp = ImportLib::get_csv_from_url($dataset->data_url);
        file_put_contents("dump/data/{$no}.csv", stream_get_contents($fp));
        fclose($fp);

        $no ++;
        error_log("dataset: {$dataset->county} {$dataset->data_title} {$dataset->data_url}");
        $records = ImportLib::get_records_from_url($dataset->data_url);
        foreach ($records as $record) {
            $outputs = array(
                $choose_county,
                $dataset->data_title,
                str_replace('(股)公司', '股份有限公司', str_replace(' ', '', $record->{'事業單位'})), // 名稱
                ImportLib::parse_date($record->{'處分日期'}), // 日期
                str_replace("\n", "", $record->{'違反條款'}),
                str_replace("\n", "", $record->{'違反法規內容'}),
                str_replace("\n", "", $record->{'處分字號'}),
            );

            $sig = md5(preg_replace("#\s*#", '', implode('', array_slice($outputs, 2, 5))));
            if (array_key_exists($sig, $showed)) {
                continue;
            }
            $showed[$sig] = true;

            fputcsv($output, $outputs);
        }
    }
}
fclose($output);
fclose($output_dataset);

error_log("以下資料集未使用到: " . implode(', ', array_keys($county_datasets)));
