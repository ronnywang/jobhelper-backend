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
    if ($max_published_at <= $package->package_time) {
        error_log("跳過 {$choose_county}, 目前系統內資料時間為" . date('Y/m/d', $package->package_time) . ', 匯入資料時間為' . date('Y/m/d', $max_published_at));
        continue;
    }

    foreach ($datasets as $dataset) {
        if (!$dataset->data_url) {
            continue;
        }
        error_log("dataset: {$dataset->county} {$dataset->data_title} {$dataset->data_url}");
        $records = ImportLib::get_records_from_url($dataset->data_url);
        foreach ($records as $record) {
            $outputs = array(
                str_replace('(股)公司', '股份有限公司', str_replace(' ', '', $record->{'事業單位'})), // 名稱
                ImportLib::parse_date($record->{'處分日期'}), // 日期
                str_replace("\n", "", ($record->{'違反條款'} . $record->{'違反法規內容'} . '(' . $record->{'處分字號'} . ')')), // 說明
                $dataset->origin_url,
                $dataset->snapshot_url,
                $dataset->data_title,
            );
            $sig = md5(implode('', array_slice($outputs, 0, 3)));
            if (array_key_exists($sig, $showed)) {
                continue;
            }
            $showed[$sig] = true;

            $contents[] = $outputs;
        }
    }
    $package->updateContent($contents, $max_published_at);
}

error_log("以下資料集未使用到: " . implode(', ', array_keys($county_datasets)));
