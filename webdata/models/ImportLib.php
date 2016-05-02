<?php

class ImportLib
{
    public static function normalize_county($county)
    {
        $county = str_replace('台', '臺', $county);
        $county = str_replace('桃園縣', '桃園市', $county);
        return $county;
    }

    public static function get_csv_from_url($url)
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

        return fopen($url, 'r');
    }

    public static function parse_column($column)
    {
        switch (str_replace(' ', '', $column)) {
        case '事業單位名稱':
        case '事業單位':
        case '公司名稱':
        case '事業單位/事業主':
        case '事業單位或事業主之名稱、負責人姓名':
        case '醫療院所名稱':
        case '事業單位或事業主之名稱':
        case '事業單位名稱(負責人姓名)':
        case '事業單位或事業主':
        case '事業單位名稱（負責人姓名）':
        case '事業單位名稱、負責人(地址)':
        case '事業單位名稱/自然人姓名':
            return '事業單位';

        case '違反法令條款':
        case '違反條款':
        case '違反法條':
        case '違法條款':
        case '違反勞動基準法條款':
        case '違反法令條':
            return '違反條款';

        case '違反法規內容':
            return '違反法規內容';

        case '處分字號':
        case '處分書文號':
        case '處分文號':
        case '罰鍰文號':
        case '處分文書號':
        case '發文字號':
        case '文號':
        case '處分書日期文號':
        case '裁處書與執行命令文號':
            return '處分字號';

        case '處分日期':
        case '處分書日期':
        case '處份日期':
        case '裁處書日期':
        case '罰鍰日期':
        case '日期':
            return '處分日期';

        case '處分日期及文號':
            return 'word_and_date';

        default:
            return $column;
        }
    }

    public static function parse_date($str)
    {
        $str = str_replace(' ', '', $str);
        if (preg_match_all('#(\d*)年(\d*)月(\d*)(日|號)#', $str, $matches)) {
            $last_id = count($matches[0]) - 1;
            return (1911 + $matches[1][$last_id] ) . '/' . $matches[2][$last_id] . '/' . $matches[3][$last_id];
        }

        if (preg_match('#(.{2,3})/(\d+)/(\d+)#', $str, $matches)) {
            return (1911 + $matches[1] ) . '/' . $matches[2] . '/' . $matches[3];
        }

        if (preg_match('#(\d{3})(\d{2})(\d{2})#', $str, $matches)) {
            return (1911 + $matches[1] ) . '/' . $matches[2] . '/' . $matches[3];
        }

        if (preg_match('#(\d{3})\.(\d{1,2})\.(\d{1,2})#', $str, $matches)) {
            return (1911 + $matches[1] ) . '/' . $matches[2] . '/' . $matches[3];
        }

        throw new Exception("日期解析失敗: {$str}");
    }

    public static function get_records_from_url($url) 
    {
        $fp = self::get_csv_from_url($url);
        $column_rows = array_map(function($s) { return preg_replace('#\s+#', '', $s); }, fgetcsv($fp));

        $records = array();
        $line_no = 1;
        while ($rows = fgetcsv($fp)) {
            $line_no ++;
            if (trim(implode('', $rows)) == '') {
                continue;
            }
            $data = new StdClass;
            $data->{'違反條款'} = '';
            $data->{'違反法規內容'} = '';
            $data->{'處分字號'} = '';
            foreach ($rows as $id => $row) {
                if (!$row) {
                    continue;
                }
                if (!array_key_exists($id, $column_rows) or !$column_rows[$id]) {
                    throw new Exception("Line {$line_no} 找不到 {$id} 的欄位");
                }
                $data->{self::parse_column($column_rows[$id])} = $row;
            }
            if (property_exists($data, 'word_and_date')) {
                if (preg_match('#(\d+年\d+月\d+日)(.*)#', $data->word_and_date, $matches)) {
                    $data->{'處分日期'} = $matches[1];
                    $data->{'處分字號'} = trim($matches[2]);
                } else {
                    throw new Exception("Line {$line_no} 不正確的日期字號: {$data->word_and_date}");
                }
            }

            if (!$data->{'事業單位'}) {
                throw new Exception("Line {$line_no} 找不到 事業單位");
            }
            // # 開頭就表示已被取消
            if (strpos($data->{'事業單位'}, '#') === 0) {
                continue;
            }
            if (!$data->{'處分日期'}) {
                throw new Exception("Line {$line_no} 找不到 處分日期");
            }
            if (!$data->{'處分字號'}) {
                throw new Exception("Line {$line_no} 找不到 處分字號");
            }
            $records[] = $data;
        }

        return $records;
    }
}
