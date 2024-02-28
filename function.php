<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use GuzzleHttp\Client;

function createTimestamps($dateString, $dateFormat='d-m-Y', $outputFormat='Y-m-d H:i:s')
{
    $carbonDate = Carbon::createFromFormat($dateFormat, $dateString);
    $formattedDate = $carbonDate->format($outputFormat);
    return $formattedDate;
}

function customDate($dateString)
{
    return createTimestamps($dateString, 'Y-m-d H:i:s', 'd-m-Y');
}

function getItemValue($item, $column)
{
    $keys = explode('.', $column);
    $value = $item;
    foreach ($keys as $key) {
        $value = $value[$key] ?? null;
    }
    return $value;
}

function createExcelRowDocument($item)
{
    $defaultColumns = ["no_surat", "kode_surat", "order_date", "unitpln.name", "menerima", "normal_id", "quantity", "merk", "pelanggan", "pekerjaan.name", "kendaraan.name", "nopol", "movement.type", "vendor.name", "petugas.name", "penerima.name", "satpam.name", "pemberi.name"];
    $outputItem = [];
    foreach ($defaultColumns as $key => $column) {
        $value = getItemValue($item, $column);
        if( $column === 'order_date' && $value ) {
            $value = createTimestamps($value, 'Y-m-d H:i:s', 'd-m-Y H:i:s');
        }
        $outputItem[] = $value;
    }
    return $outputItem;
}
function getModelIdName($jobName)
{
    $baseModel  = 'App\\Models\\';
    $modelName  = $baseModel . ucfirst($jobName);
    if (in_array($jobName, ['penerima', 'pemberi', 'petugas'])) {
        $modelName .= "Surat";
    }
    if($jobName === 'unitpln') {
        $modelName = $baseModel . 'UnitPln';
    }
    return $modelName;
}

function getItemsByIndex($array, $startIndex, $endIndex) {
    if (empty($array)) {
        return [];
    }
    $resultArray = array_slice($array, $startIndex, $endIndex - $startIndex + 1, true);
    $resultArray = array_combine(range($startIndex, $endIndex), $resultArray);
    return $resultArray;
}

function parseNoSurat($inputString)
{
    if (empty($inputString)) {
        return [
            "no_surat" => null,
            "kode_surat" => null,
        ];
    }

    $splitString = explode('.', $inputString);
    $noSurat = $splitString[0] ?? null;
    $kodeSurat = $splitString[1] ?? null;
    if( is_numeric($noSurat) && $kodeSurat ) {
        $outputArray = [
            "no_surat" => $noSurat,
            "kode_surat" => $kodeSurat,
        ];
        return $outputArray;
    }
    return [
        "no_surat" => null,
        "kode_surat" => null,
    ];
}

function formatDate($tanggalISO8601) {
    $tanggal = Carbon::parse($tanggalISO8601)->setTimezone('Asia/Jakarta');
    $formattedDate = $tanggal->format('d F Y, H:i:s T');
    return $formattedDate;
}

function formatRouteMethod($nama) {
    $formattedNama = ucfirst($nama);

    if (strpos($formattedNama, '-') !== false) {
        $formattedNama = str_replace('-', '', $formattedNama);
        $formattedNama = preg_replace_callback('/-([a-z])/', function($matches) {
            return strtoupper($matches[1]);
        }, $formattedNama);
    }

    return $formattedNama;
}

function getDateInput($input) {
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $input)) {
        return $input;
    }
    $datetime = new DateTime($input);
    $tanggal = $datetime->format('d');
    $bulan = $datetime->format('m');
    $tahun = $datetime->format('Y');
    $output = "$tanggal-$bulan-$tahun";
    return $output;
}

function customPrintOrderDate($tanggalAwal) {
    $tanggalCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $tanggalAwal);
    $kamusBulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];
    $bulanIndonesia = $kamusBulan[$tanggalCarbon->month];
    $tanggalFormatBaru = $tanggalCarbon->format('d') . ' ' . $bulanIndonesia . ' ' . $tanggalCarbon->format('Y');
    return $tanggalFormatBaru;
}

function ss($data)
{
    try {
        dd($data->toArray());
    } catch (\Throwable $th) {
        dd($data);
    }
}

function generateSocketId()
{
    $socketId = null;
    if (auth()->check()) {
        $userId = auth()->user()->id;
        $sessionId = session()->getId();
        $socketId = md5("{$userId}-{$sessionId}");
    }
    return $socketId;
}

function uuid()
{
    return Str::uuid();
}

function format_phone($nohp)
{
    $nohp = preg_replace('/[^0-9]/', '', $nohp);
    $nohp = preg_replace('/^08/', '628', $nohp);
    return $nohp;
}

function format_currency($number)
{
    $formattedNumber = number_format($number, 2, ',', '.');
    return $formattedNumber;
}
function get_current_time()
{
    return Carbon::now();
}

function getDateBefore($currentDate, $subDays=2, $format='d-m-Y')
{
    $dateCarbon = Carbon::createFromFormat('d-m-Y', $currentDate);
    $dateBefore = $dateCarbon->subDays($subDays);
    $dateBefore = $dateBefore->format($format);
    return $dateBefore;
}

function is_clean_area_name($string)
{
    $keywords = "/(timur|utara|selatan|barat|tengah|tenggara)/i";
    if (strpos($string, ' ') !== false) {
        if (preg_match($keywords, $string)) {
            return false;
        }
    }
    return true;
}

function compareSubmitWaybill($a, $b)
{
    $waybillA = $a['jnt_log']['waybill'] ?? null;
    $waybillB = $b['billCode'] ?? null;
    if ($waybillA === null && $waybillB !== null) {
        return -1;
    } elseif ($waybillA !== null && $waybillB === null) {
        return 1;
    }
    return strcmp(strval($waybillA), strval($waybillB));
}

function compareRealWaybill($a, $b)
{
    if (isset($a['billCode'])) {
        $waybillA = strval($a['billCode']);
    } else {
        $waybillA = null;
    }

    if ($b['jnt_log'] ?? null !== null && isset($b['jnt_log']['waybill'])) {
        $waybillB = strval($b['jnt_log']['waybill']);
    } else {
        $waybillB = null;
    }

    return strcmp($waybillA, $waybillB);
}

function getDataStatistic($sales_list)
{

    $statsOrderBy = [];
    $statsCourier = [];
    $statsCompanyName = [];
    $statsByStatus = [];
    $status_list = ["ready", "success", "error"];

    foreach ($sales_list as $sales) {
        $orderBy = strtolower($sales['order_by']);
        $courier = strtolower($sales['courier']);
        $companyName = strtolower($sales['company_name']);

        if (is_marketplace_sales($courier)) {
            $courier = 'MARKETPLACE';
        }

        if (is_transfer_sales($orderBy)) {
            $orderBy = 'TRANSFER';
        }
        foreach ($status_list as $index => $status) {
            if (!isset($statsByStatus[$status])) {
                $statsByStatus[$status] = ['value' => $index, 'text' => strtoupper($status), 'count' => 0];
            }
        }

        if (isset($sales['status'])) {
            switch ($sales['status']) {
                case 1:
                    $statsByStatus['success']['count']++;
                    break;
                case 2:
                    $statsByStatus['error']['count']++;
                    break;
                default:
                    $statsByStatus['ready']['count']++;
                    break;
            }
        } else {
            $statsByStatus['ready']['count']++;
        }

        if (!isset($statsOrderBy[$orderBy])) {
            $statsOrderBy[$orderBy] = ['value' => strtoupper($orderBy), 'text' => strtoupper($orderBy), 'count' => 1];
        } else {
            $statsOrderBy[$orderBy]['count']++;
        }

        if (!isset($statsCourier[$courier])) {
            $statsCourier[$courier] = ['value' => strtoupper($courier), 'text' => strtoupper($courier), 'count' => 1];
        } else {
            $statsCourier[$courier]['count']++;
        }

        if (!isset($statsCompanyName[$companyName])) {
            $statsCompanyName[$companyName] = ['value' => strtoupper($companyName), 'text' => strtoupper($companyName), 'count' => 1];
        } else {
            $statsCompanyName[$companyName]['count']++;
        }
    }

    $statsOrderByResult = array_values($statsOrderBy);
    $statsCourierResult = array_values($statsCourier);
    $statsCompanyNameResult = array_values($statsCompanyName);
    $statsByStatus = array_values($statsByStatus);
    sort($statsOrderByResult);
    sort($statsCourierResult);
    sort($statsCompanyNameResult);
    sort($statsByStatus);

    $statistic = [
        'stats' => [
            'status' => $statsByStatus,
            'order_by' => $statsOrderByResult,
            'courier' => $statsCourierResult,
            'company_name' => $statsCompanyNameResult
        ]
    ];
    return $statistic;
}

function parseMappingArea($string, $all = null)
{
    $parts = explode('/', $string);
    $trimmedParts = array_map('trim', $parts);
    $keys = ['province', 'city', 'district'];
    try {
        return array_combine($keys, $trimmedParts);
    } catch (\ValueError $e) {
    }
}

function get_mapping_area($mapping)
{
    $splittedMapping = explode('/', $mapping);
    $mappingName = trim(end($splittedMapping));
    return $mappingName;
}

function clean_area($string)
{
    $string = preg_replace('/^kec(\.? |amatan )/i', '', $string);
    return trim($string);
}

function is_array_list($arr)
{
    if (!is_array($arr)) {
        return false;
    }
    foreach ($arr as $element) {
        if (!is_array($element) || array_keys($element) !== range(0, count($element) - 1)) {
            return false;
        }
    }
    return true;
}

function sortMappingArea($a, $b)
{
    $mappingComparison = (empty($a['mapping']) ? 0 : 1) - (empty($b['mapping']) ? 0 : 1);
    if ($mappingComparison !== 0) {
        return $mappingComparison;
    }

    $userDefinedA = $a['user_defined'] ?? false;
    $userDefinedB = $b['user_defined'] ?? false;

    if ($userDefinedA && !$userDefinedB) {
        return -1;
    } elseif (!$userDefinedA && $userDefinedB) {
        return 1;
    }

    $autoMatchA = $a['auto_match'] ?? false;
    $autoMatchB = $b['auto_match'] ?? false;

    if ($autoMatchA && !$autoMatchB) {
        return -1;
    } elseif (!$autoMatchA && $autoMatchB) {
        return 1;
    }

    return strcasecmp($a['district'], $b['district']);
}

function sortMappingCs($a, $b)
{
    $nullComparisonA = is_null($a['mapping'] ?? null);
    $nullComparisonB = is_null($b['mapping'] ?? null);

    // Urutkan terlebih dahulu berdasarkan mapping null
    if ($nullComparisonA && !$nullComparisonB) {
        return -1; // $a has null mapping, so $a comes first
    } elseif (!$nullComparisonA && $nullComparisonB) {
        return 1; // $b has null mapping, so $b comes first
    }

    // Kedua-duanya memiliki mapping atau tidak memiliki mapping
    $csTypeComparison = strcmp($a['cs_type'], $b['cs_type']);
    if ($csTypeComparison === 0) {
        // Jika cs_type sama, urutkan berdasarkan cs_name
        return strcasecmp($a['cs_name'], $b['cs_name']);
    }

    return $csTypeComparison;
}

function sortOrderStatisticJnt($statistic)
{
    usort($statistic, function ($a, $b) {
        $order = [1, 2, 0, -1];
        $aStatus = array_search($a['jnt_token'], $order);
        $bStatus = array_search($b['jnt_token'], $order);

        if ($aStatus === false) {
            $aStatus = count($order);
        }
        if ($bStatus === false) {
            $bStatus = count($order);
        }

        // Menambahkan perbandingan berdasarkan jumlah 'mapping_area'
        $countA = count($a['mapping_area']);
        $countB = count($b['mapping_area']);

        if ($aStatus === $bStatus) {
            return $countB - $countA;
        }

        return $aStatus - $bStatus;
    });

    return $statistic;
}

function sortOrderStatisticKledo($statistic)
{
    usort($statistic, function ($a, $b) {
        $order = [1, 2, 0, -1];
        $aStatus = array_search($a['kledo_token'], $order);
        $bStatus = array_search($b['kledo_token'], $order);

        $aMappingCount = count($a['mapping_product'] ?? []);
        $bMappingCount = count($b['mapping_product'] ?? []);

        if ($aStatus === false) {
            $aStatus = count($order);
        }
        if ($bStatus === false) {
            $bStatus = count($order);
        }

        // Urutkan berdasarkan kledo_token terlebih dahulu
        $statusDiff = $aStatus - $bStatus;
        if ($statusDiff !== 0) {
            return $statusDiff;
        }

        // Jika kledo_token sama, urutkan berdasarkan mapping_product dari yang terbesar
        return $bMappingCount - $aMappingCount;
    });

    return $statistic;
}


function sortMappingProduct($a, $b)
{

    $patterns = config('triva.pattern.quantity');

    $aNumber = getNumberFromName($a['product_name'], $patterns);
    $bNumber = getNumberFromName($b['product_name'], $patterns);

    $aQuantity = $a['quantity'];
    $bQuantity = $b['quantity'];

    $aValid = array_key_exists('valid', $a) ? $a['valid'] : false;
    $bValid = array_key_exists('valid', $b) ? $b['valid'] : false;

    $aMappingCount = count($a['mapping'] ?? []);
    $bMappingCount = count($b['mapping'] ?? []);


    if ($aValid !== $bValid) {
        return $aValid ? 1 : -1;
    }

    if ($aMappingCount !== $bMappingCount) {
        return $aMappingCount - $bMappingCount;
    }

    if ($aQuantity !== $bQuantity) {
        return $bQuantity - $aQuantity;
    }

    if ($aNumber !== null && $bNumber !== null) {
        if ($aNumber !== $bNumber) {
            return $bNumber - $aNumber;
        }
    } elseif ($aNumber !== null) {
        return -1;
    } elseif ($bNumber !== null) {
        return 1;
    }

}

function sortMappingWeight($a, $b)
{
    if (!array_key_exists('weight', $a)) {
        return -1;
    } elseif (!array_key_exists('weight', $b)) {
        return 1;
    }

    $patterns = config('triva.pattern.quantity');

    $aWeight = $a['weight'];
    $bWeight = $b['weight'];

    $aNumber = getNumberFromName($a['product_name'], $patterns);
    $bNumber = getNumberFromName($b['product_name'], $patterns);

    $aQuantity = $a['quantity'];
    $bQuantity = $b['quantity'];

    if ($aQuantity !== $bQuantity) {
        return $bQuantity - $aQuantity;
    }

    if ($aNumber !== null && $bNumber !== null) {
        if ($aNumber !== $bNumber) {
            return $bNumber - $aNumber;
        }
    } elseif ($aNumber !== null) {
        return -1;
    } elseif ($bNumber !== null) {
        return 1;
    }

    return $bWeight - $aWeight;
}

function filterDuplicatesMappingWeight($data)
{
    $filtered = [];
    $encountered = [];

    foreach ($data as $item) {
        $productName = strtolower($item['product_name']);
        $quantity = $item['quantity'];

        $key = $productName . '_' . $quantity;

        if (!isset($encountered[$key])) {
            $filtered[] = $item;
            $encountered[$key] = 1;
        } else {
            $encountered[$key]++;
        }
    }

    // Menambahkan key 'count' pada filtered data
    foreach ($filtered as &$item) {
        $productName = strtolower($item['product_name']);
        $quantity = $item['quantity'];

        $key = $productName . '_' . $quantity;

        $item['count'] = $encountered[$key];
    }

    return $filtered;
}

function filterDuplicatesMappingProduct($mappingData)
{
    $uniqueData = [];
    $duplicateCheck = [];

    foreach ($mappingData as $product) {
        $productName = strtolower($product['product_name']);
        $productPrice = $product['product_price'];
        $productQuantity = $product['quantity'];
        $productCs = $product['cs_type'];
        $key = $productName . '_' . $productPrice . '_' . $productQuantity . '_' . $productCs;

        if (!isset($duplicateCheck[$key])) {
            $duplicateCheck[$key] = 1;
            $product['count'] = 1;
            $uniqueData[] = $product;
        } else {
            $duplicateCheck[$key]++;
            foreach ($uniqueData as &$item) {
                $existingProductName = strtolower($item['product_name']);
                if ($existingProductName === $productName && $item['product_price'] === $productPrice && $item['quantity'] === $productQuantity && $item['cs_type'] === $productCs) {
                    $item['count'] = $duplicateCheck[$key];
                    break;
                }
            }
        }
    }

    return $uniqueData;
}

function getNumberFromName($name, $patterns)
{
    foreach ($patterns as $pattern => $value) {
        if (preg_match($pattern, $name, $matches)) {
            return $value;
        }
    }
    return null;
}

function getFreeProduct($productName)
{
    $name = trim($productName);
    if (stripos($name, 'gratis') !== false) {
        $parts = explode('gratis', strtolower($name));
        if (count($parts) > 1) {
            $result = trim($parts[1]);
            if (preg_match('/\d+\s*(\w+)/', $result, $matches)) {
                return $matches[1];
            }

            return $result;
        }
    }
    return $productName;
}

function sort_array_by_key($data, $key)
{
    usort($data, function ($a, $b) use ($key) {
        return strcmp(strtolower($a[$key]), strtolower($b[$key]));
    });
    return $data;
}

function get_value($dataArray)
{
    $firstResult = reset($dataArray);
    return ($firstResult !== false) ? $firstResult : null;
}

function get_address_area($alamat, $truncate = true)
{
    $provinces = config('triva.island');

    if ($truncate) {
        $truncateAlamat = substr(preg_replace("/[^a-zA-Z]/", "", $alamat), -100);
    } else {
        $truncateAlamat = $alamat;
    }

    $matchedProvince = null;
    foreach ($provinces as $province => $cities) {
        foreach ($cities as $city) {
            if (strpos(strtolower($truncateAlamat), strtolower($city)) !== false) {
                $matchedProvince = $province;
                break 2;
            }
        }
    }

    if (!$matchedProvince && $truncate) {
        return get_address_area($alamat, false);
    }

    return $matchedProvince;
}

function get_sales_customer_phone($phone)
{
    if (preg_match('/\d+(?=\D|$)/', $phone, $matches)) {
        return format_phone($matches[0]);
    }
    return;
}


function generate_sales_tag($sales)
{
    $tag_list = [];

    // get cs name
    $cs_name = trim(strtolower($sales['cs_name']));
    $order_state = '';
    $words = explode(' ', $cs_name);
    if (stripos($cs_name, 'crm') === 0 || stripos($cs_name, 'cs') === 0) {
        if (stripos($cs_name, 'crm') === 0) {
            $order_state = 'ro';
        }
        if (stripos($cs_name, 'cs') === 0) {
            $order_state = 'baru';
        }
        $cs_name = $words[1];
    } else if (stripos($cs_name, 'kantor') === 0) {
        $cs_name = 'kantor';
    } else {
        $cs_name = $words[0];
    }

    $tag_list[] = $cs_name;

    $order_by = trim(strtolower($sales['order_by']));
    $courier = trim(strtolower($sales['courier']));

    if (is_cod_sales($order_by)) {
        $tag_list[] = $order_by;
        $tag_list[] = $courier;
    } else if (is_transfer_sales($order_by)) {
        $tag_list[] = $order_by;
        $tag_list[] = $courier;
    } else if (is_marketplace_sales($courier)) {
        $tag_list[] = $courier;
        $tag_list[] = $order_by;
        $order_state = 'mp';
    }
    $tag_list[] = get_address_area($sales['shipping_address']);
    // $tag_list[]         = $sales['shipping_address'];

    if (empty($order_state)) {
        $order_state = 'cs';
    }
    $tag_list[] = $order_state;

    return $tag_list;
}

function generate_sales_items($product_mapping = [])
{
    $sales_items = [];

    foreach ($product_mapping as $key => $mapping) {
        $price = $mapping['custom_price'] ?? $mapping['price'];
        if (isset($mapping['is_free']) && $mapping['is_free']) {
            $price = 0;
        }
        $qty = $mapping['quantity'];
        $item = [
            "finance_account_id" => $mapping['id'],
            "qty" => $qty,
            "tax_id" => "",
            "desc" => null,
            "unit_id" => $mapping['unit_id'],
            "price" => $price,
            "amount" => $price * $qty,
            "discount_amount" => 0
        ];
        $sales_items[] = $item;
    }
    return $sales_items;
}

function hasNullOrEmpty($arr)
{
    foreach ($arr as $element) {
        if ($element === null || $element === '') {
            return true;
        }
    }
    return false;
}

function getItemFinance($financeName, $financeAccounts)
{
    $financeName = trim($financeName);
    $hasItem = array_filter($financeAccounts, function ($item) use ($financeName) {
        return strcasecmp($item['name'], $financeName) === 0;
    });
    if (empty($hasItem)) {
        return null;
    }
    return get_value($hasItem);
}

function getItemCompany($company_name, $mapping_company)
{
    $hasItem = array_filter($mapping_company, function ($mapping) use ($company_name) {
        return strcasecmp($mapping['company'], $company_name) === 0;
    });
    if (empty($hasItem)) {
        return null;
    }
    return get_value($hasItem);
}

function getItemArea($salesID, $mapping_area, $checkEmpty = true)
{
    $hasItem = array_filter($mapping_area, function ($mapping) use ($salesID, $checkEmpty) {
        if ($checkEmpty) {
            return $mapping['sales_id'] == $salesID && !empty($mapping['mapping']);
        }
        return $mapping['sales_id'] == $salesID;
    });

    return empty($hasItem) ? null : get_value($hasItem);
}

function getItemWeight($product_name, $quantity, $mapping_list)
{
    $hasItem = array_filter($mapping_list, function ($mapping) use ($product_name, $quantity) {
        return strcasecmp($mapping['product_name'], $product_name) === 0
            && $mapping['quantity'] == $quantity
            && !empty($mapping['weight']);
    });
    if (empty($hasItem)) {
        return null;
    }
    return get_value($hasItem);
}

function getItemProduct($product_name, $product_price, $quantity, $cs_type, $mapping_product)
{
    $hasItem = array_filter($mapping_product, function ($mapping) use ($product_name, $quantity, $cs_type, $product_price) {
        return strcasecmp($mapping['product_name'], $product_name) === 0
            && (int) $mapping['product_price'] === (int) $product_price
            && (int) $mapping['quantity'] === (int) $quantity
            && $mapping['cs_type'] === $cs_type;
    });
    if (empty($hasItem)) {
        return null;
    }
    return get_value($hasItem);
}

function getItemStatistic($company_name, $list_company)
{
    $hasItem = array_filter($list_company, function ($mapping) use ($company_name) {
        return strcasecmp($mapping['company_name'], $company_name) === 0;
    });
    if (empty($hasItem)) {
        return null;
    }
    return get_value($hasItem);
}

function getItemImport($filePath, $orderList)
{
    $fileName = basename($filePath);
    $hasItem = array_filter($orderList, function ($item) use ($fileName) {
        return strcasecmp($item['fileName'], $fileName) === 0;
    });
    if (empty($hasItem)) {
        return null;
    }
    return get_value($hasItem);
}

function getItemCompanyImport($dataRows)
{
    $companyName = null;

    foreach ($dataRows as $key => $value) {
        foreach ($value as $dataKey => $dataValue) {
            if (containsWordIgnoreCase($dataKey, 'klien') && containsWordIgnoreCase($dataKey, 'pengirim')) {
                $companyName = $dataValue;
                break 2;
            }
        }
    }

    return $companyName;
}

function getValueByKey($array, $keyContains, $blacklistContains = [])
{
    $valueFound = null;
    foreach ($array as $key => $value) {
        $pattern = '/' . implode('|', array_map('preg_quote', $keyContains)) . '/';
        $containsFound = preg_match($pattern, $key);
        $blackFound = false;
        if (count($blacklistContains) > 0) {
            $patternBlack = '/' . implode('|', array_map('preg_quote', $blacklistContains)) . '/';
            $blackFound = preg_match($patternBlack, $key);
        }
        if ($containsFound && !$blackFound) {
            $valueFound = $value;
        }
    }
    return $valueFound;
}

function getSalesIdFromNote($orderNote)
{
    $orderId = null;
    if (strpos($orderNote, '/') !== false) {
        $sections = explode('/', $orderNote);
        $last_section = trim(end($sections));
        if (ctype_digit($last_section)) {
            $orderId = (int) $last_section;
        }
    }
    return $orderId;
}

function isEqualImportRecord($sales, $orderRecord)
{
    $sales_id = $sales['id'];
    $salesRecordId = getSalesIdFromNote($orderRecord['remark'] ?? '');
    $salesRecordId = $salesRecordId ?? $orderRecord['electricityNumber'] ?? $orderRecord['ecOrderNo'] ?? '';
    // cocokkan menurut sales_id
    if (strval($sales_id) === strval($salesRecordId)) {
        return true;
    }
    // cocokkan menurut receiverName + codFee + receiverPhone
    $customer_name = $sales['customer_name'];
    $total_price = $sales['total_price'];
    $customer_phone = get_sales_customer_phone($sales['customer_phone']);
    if (containsWordIgnoreCase($orderRecord['receiverName'], $customer_name)) {
        if ((int) $total_price === (int) $orderRecord['codFee']) {
            if (substr(strval($customer_phone), -4) === substr(strval($orderRecord['receiverPhone']), -4)) {
                return true;
            }
        }
    }
    return false;
}

function zeroFill($number, $length = 4)
{
    $number = substr((string) $number, 0, $length);
    return str_pad($number, $length, '0', STR_PAD_LEFT);
}

function is_marketplace_sales($marker)
{
    if (preg_match('/^(?:' . config('triva.pattern.marketplace') . ')$/i', $marker)) {
        return true;
    }
    return false;
}

function is_transfer_sales($marker)
{
    if (preg_match('/^(?:' . config('triva.pattern.transfer') . ')$/i', $marker)) {
        return true;
    }
    return false;
}

function is_cod_sales($marker)
{
    if (preg_match('/^(?:' . config('triva.pattern.cod') . ')$/i', $marker)) {
        return true;
    }
    return false;
}

function get_cs_type($cs_name)
{
    $cs_name = trim(strtolower($cs_name));
    $patterns = [
        'crm' => 'crm',
        'kantor' => 'kantor',
        'cs' => 'cs'
    ];
    foreach ($patterns as $pattern => $type) {
        if (stripos($cs_name, $pattern) === 0) {
            return $type;
        }
    }
    return 'cs';
}

function sumArrayKey($arr, $key)
{
    return array_reduce($arr, function ($carry, $item) use ($key) {
        if (is_array($item) && array_key_exists($key, $item)) {
            return $carry + $item[$key];
        }
        return $carry;
    }, 0);
}

function isEqualString($string1, $string2)
{
    return strcasecmp($string1, $string2) === 0;
}

function isEqualStringAbsolute($string1, $string2)
{
    $string1WithoutSpaces = str_replace(' ', '', $string1);
    $string2WithoutSpaces = str_replace(' ', '', $string2);
    return strcasecmp($string1WithoutSpaces, $string2WithoutSpaces) === 0;
}

function getValueCompanyName($name, $company_list)
{
    $searchName = strtolower(preg_replace('/\s+/', '', $name));
    foreach ($company_list as $company) {
        $formattedCompany = strtolower(preg_replace('/\s+/', '', $company));
        if ($searchName === $formattedCompany) {
            return $company;
        }
    }
    return ucwords($name);
}

function selectRangeArray($array, $startIndex, $endIndex)
{
    $startIndex--;
    $endIndex--;
    $arrayLength = count($array);
    if ($endIndex >= $arrayLength) {
        $endIndex = $arrayLength - 1;
    }
    if ($startIndex < 0 || $startIndex >= $arrayLength || $startIndex > $endIndex) {
        return [];
    }
    return array_slice($array, $startIndex, $endIndex - $startIndex + 1);
}

function containsWordIgnoreCase($string, $word)
{
    return stripos($string, $word) !== false;
}

// function getMappingCompanyGroup($mapping_company, $company_names) {

//     $listMappingCompany 	= [];
//     $unListMappingCompany   = [];
//     $inGroupCompanyIds      = [];
//     foreach ($mapping_company as $mapping) {
//         $companyId 			= $mapping['mapping']['id'];
//         $salesCompany 		= $mapping['company'];
//         if( !in_array($salesCompany, $company_names)) {
//             continue;
//         }
//         if(!in_array($companyId, $inGroupCompanyIds)) {
//             $inGroupCompanyIds[] = $companyId;
//         }
//         $found = false;
//         foreach ($listMappingCompany as &$item) {
//             if ($item['company_id'] === $companyId) {
//                 $found = true;
//                 $item['sales_companies'][] = $salesCompany;
//                 break;
//             }
//         }
//         if (!$found) {
//             $listMappingCompany[] = [
//                 'company_id' => $companyId,
//                 'sales_companies' => [$salesCompany],
//             ];
//         }
//     }

//     foreach ($mapping_company as $mapping) {
//         $companyId      = $mapping['mapping']['id'];
//         $salesCompany 	= $mapping['company'];

//         if( !in_array($companyId, $inGroupCompanyIds)) {
//             continue;
//         }
//         $found      = false;
//         foreach ($unListMappingCompany as &$item) {
//             if ($item['company_id'] === $companyId) {
//                 $found = true;
//                 $item['sales_companies'][] = $salesCompany;
//                 break;
//             }
//         }
//         if (!$found) {
//             $unListMappingCompany[] = [
//                 'company_id' => $companyId,
//                 'sales_companies' => [$salesCompany],
//             ];
//         }
//     }

//     return [ $listMappingCompany, $unListMappingCompany];

// }

function mergeMappingCompany($assignMapping, $mapping_company)
{
    $mergedData = [];

    foreach ($assignMapping as $assignItem) {
        $companyExistsInMappingCompany = false;

        foreach ($mapping_company as $mappingItem) {
            if (isEqualString($assignItem['company'], $mappingItem['company'])) {
                $companyExistsInMappingCompany = true;
                $mergedData[] = [
                    'company' => $assignItem['company'],
                    'mapping' => $assignItem['mapping'],
                ];
                break;
            }
        }

        if (!$companyExistsInMappingCompany && isset($assignItem['mapping'])) {
            $mergedData[] = [
                'company' => $assignItem['company'],
                'mapping' => $assignItem['mapping'],
            ];
        }
    }

    foreach ($mapping_company as $mappingItem) {
        $companyExistsInAssignMapping = false;

        foreach ($assignMapping as $assignItem) {
            if (isEqualString($assignItem['company'], $mappingItem['company'])) {
                $companyExistsInAssignMapping = true;
                break;
            }
        }

        if (!$companyExistsInAssignMapping) {
            $mergedData[] = [
                'company' => $mappingItem['company'],
                'mapping' => $mappingItem['mapping'],
            ];
        }
    }

    return $mergedData;
}

function sortMappingPayout($array)
{
    usort($array, function ($a, $b) {
        $salesIdA = isset($a['sales_id']) ? $a['sales_id'] : null;
        $salesIdB = isset($b['sales_id']) ? $b['sales_id'] : null;

        if ($salesIdA !== $salesIdB) {
            $statusA = isset($a['status']) ? $a['status'] : 0;
            $statusB = isset($b['status']) ? $b['status'] : 0;
            return $statusA - $statusB;
        }

        $assignSalesA = isset($a['assign_sales_id']) ? $a['assign_sales_id'] : null;
        $assignSalesB = isset($b['assign_sales_id']) ? $b['assign_sales_id'] : null;

        if ($assignSalesA !== $assignSalesB) {
            return $assignSalesA - $assignSalesB;
        }

        $statusA = isset($a['status']) ? $a['status'] : 0;
        $statusB = isset($b['status']) ? $b['status'] : 0;

        return $statusA - $statusB;
    });

    return $array;
}


function sortSalesById($a, $b)
{
    return $a['id'] - $b['id'];
}

function detectNullMappingCompanies($data)
{
    $companiesWithNullMapping = [];

    foreach ($data as $item) {
        if ($item['mapping'] === null) {
            $companiesWithNullMapping[] = $item['company'];
        }
    }

    if (!empty($companiesWithNullMapping)) {
        return [
            'error' => true,
            'company' => $companiesWithNullMapping
        ];
    }

    return false;
}


function sortWaybillByStatus($a, $b)
{
    $orderStatusOrder = ['PENDING', 'GOT', 'CANCEL_ORDER'];
    $aOrder = array_search($a['orderStatus'], $orderStatusOrder);
    $bOrder = array_search($b['orderStatus'], $orderStatusOrder);
    return $aOrder - $bOrder;
}

function getJntAreaName($areaName)
{
    $pattern = "/^(.*?)(?:-\w{3})?$/";
    preg_match($pattern, $areaName, $matches);
    return $matches[1];
}

function getDbMatchingArea($dbResults, $shipping_address)
{

    $sinonim = [
        "BANGKABELITUNG" => ["BELITUNG"],
        "DIYOGYAKARTA" => ["YOGYAKARTA", "JOGJA", "DIY"],
        "DKIJAKARTA" => ["JAKARTA"],
        "JAWABARAT" => ["JAWABARAT", "JABAR", "JAWA"],
        "JAWATENGAH" => ["JAWATENGAH", "JATENG", "JAWA"],
        "JAWATIMUR" => ["JAWATIMUR", "JATIM", "JAWA"],
        "KALIMANTANBARAT" => ["KALIMANTANBARAT", "KALBAR", "KALIMANTAN"],
        "KALIMANTANSELATAN" => ["KALIMANTANSELATAN", "KALSEL", "KALIMANTAN"],
        "KALIMANTANTENGAH" => ["KALIMANTANTENGAH", "KALTENG", "KALIMANTAN"],
        "KALIMANTANTIMUR" => ["KALIMANTANTIMUR", "KALTIM", "KALIMANTAN"],
        "KALIMANTANUTARA" => ["KALIMANTANUTARA", "KALIMANTAN"],
        "KEPULAUANRIAU" => ["RIAU"],
        "MALUKUTARA" => ["MALUKU"],
        "NANGGROEACEHDARUSSALAM" => ["ACEH", "NAD"],
        "NUSATENGGARABARAT" => ["NTB"],
        "NUSATENGGARATIMUR" => ["NTT"],
        "PAPUABARAT" => ["PAPUA"],
        "SULAWESIBARAT" => ["SULAWESIBARAT", "SULBAR", "SULAWESI"],
        "SULAWESISSELATAN" => ["SULAWESISELATAN", "SULSEL", "SULAWESI"],
        "SULAWESITENGAH" => ["SULAWESITENGAH", "SULTENG", "SULAWESI"],
        "SULAWESITENGGARA" => ["SULAWESITENGGARA", "SULTENG", "SULAWESI"],
        "SULAWESIUTARA" => ["SULAWESIUTARA", "SULUT", "SULTRA", "SULAWESI"],
        "SUMATERABARAT" => ["SUMATERABARAT", "SUMBAR", "SUMATERA", "SUMATRA"],
        "SUMATERASELATAN" => ["SUMATERA SELATAN", "SUMSEL", "SUMATERA", "SUMATRA"],
        "SUMATERAUTARA" => ["SUMATERAUTARA", "SUMUT", "SUMATERA", "SUMATRA"],
    ];

    $dbResult = null;
    foreach ($dbResults as $row) {
        $rowArea = getJntAreaName($row->area);
        $rowCity = $row->city;
        $rowProvince = $row->province;

        $rowArea = preg_replace('/[^a-z]/i', '', $rowArea);
        $rowCity = preg_replace('/[^a-z]/i', '', $rowCity);
        $rowProvince = preg_replace('/[^a-z]/i', '', $rowProvince);

        if (isset($sinonim[$rowProvince])) {
            $sinonimPola = implode('|', $sinonim[$rowProvince]);
            $searchPattern = "/$rowArea.*$rowCity.*($rowProvince|$sinonimPola)/i";
        } else {
            $searchPattern = "/$rowArea.*$rowCity.*$rowProvince/i";
        }

        if (preg_match($searchPattern, $shipping_address)) {
            $dbResult = $row;
            break;
        }
    }
    return $dbResult;
    // foreach ($dbResults as $row) {
    //     $rowArea = getJntAreaName($row->area);
    //     $rowCity = $row->city;
    //     $rowProvince = $row->province;

    //     $provinceSynonim = [
    //         "sapipagi" => ["ntb"]
    //     ];

    //     $clean_shipping_address = preg_replace('/[^a-z]+/i', '', $shipping_address);
    //     $rowArea        = preg_replace('/[^a-z]+/i', '', $rowArea);
    //     $rowCity        = preg_replace('/[^a-z]+/i', '', $rowCity);
    //     $rowProvince    = preg_replace('/[^a-z]+/i', '', $rowProvince);
    //     $searchPattern  = "/(?=.*$rowArea)(?=.*$rowCity)(?=.*$rowProvince)/i";

    //     if (preg_match($searchPattern, $clean_shipping_address)) {
    //         $dbMatch = $row;
    //         return $dbMatch;
    //     }
    // }    
    // return null;
}

function convertWhatsAppToTelegramHTML($text)
{
    $text = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $text);
    $text = preg_replace('/_([^_]+)_/', '<i>$1</i>', $text);
    $text = preg_replace('/~([^~]+)~/', '<s>$1</s>', $text);
    $text = preg_replace('/``([^`]+)``/', '<code>$1</code>', $text);
    return $text;
}



function convertTelegramHTMLToWhatsApp($text)
{
    $text = preg_replace('/<code>(.*?)<\/code>/', '``$1``', $text);
    $text = preg_replace('/<s>(.*?)<\/s>/', '~$1~', $text);
    $text = preg_replace('/<i>(.*?)<\/i>/', '_$1_', $text);
    $text = preg_replace('/<em>(.*?)<\/em>/', '_$1_', $text);
    $text = preg_replace('/<b>(.*?)<\/b>/', '*$1*', $text);
    return $text;
}

function telegramBroadcastHelper()
{
    $todayDate = Carbon::now()->format('d-m-Y');
    $messageReply = "";
    $messageReply .= "🔴 <b>TRIVAKU BROADCAST</b> 🔴\n\n";
    $messageReply .= "Silakan ketik pesan dengan format berikut :\n\n";
    $messageReply .= "◾️ <b>/help</b> :  Melihat bantuan pesan\n\n";
    $messageReply .= "◾️ <b>/user</b> :  View Info User Telegram\n";
    $messageReply .= "◾️ <b>/subscribe</b> :  Save Info User Telegram ke TrivaKU\n";
    $messageReply .= "◾️ <b>/mapping</b> :  Get Info Mapping CS/CRM\n";
    $messageReply .= "◾️ <b>/resi</b> :  Get data resi hari ini\n";
    $messageReply .= "◾️ <b>/resi dd-mm-yyyy</b> :  Get data resi sesuai tanggal\n\n";
    $messageReply .= "contoh : <b>/resi $todayDate</b>";
    return $messageReply;
}

function telegramBroadcastUnmappingCs($fullName)
{
    $messageReply = "Akun Telegram kamu \"$fullName\" :\n\n";
    $messageReply .= "⛔️ Status : <b>BELUM DIMAPPING</b>,\n\n";
    $messageReply .= "Hubungi Admin TrivaKU untuk mengatur mapping CS/CRM";
    return $messageReply;
}

function getTelegramWebhookUrl($id = '')
{
    $currentDomain = request()->getHttpHost();
    if (str_contains($currentDomain, '.test')) {
        $ngrokUrl = env('NGROK_URL');
        $webhookUrl = str_replace($currentDomain, $ngrokUrl, route('api.telegram.webhook', ['id' => $id]));
    } else {
        $webhookUrl = route('api.telegram.webhook', ['id' => $id]);
    }
    $parsedUrl = parse_url($webhookUrl);
    if ($parsedUrl && isset($parsedUrl['host'])) {
        $webhookUrl = 'https://' . $parsedUrl['host'];
        if (isset($parsedUrl['path'])) {
            $webhookUrl .= $parsedUrl['path'];
        }
        return $webhookUrl;
    }
    return $webhookUrl;
}

function waiting($seconds = 1)
{
    usleep($seconds * 1000000);
}

function get_sales_customer_name($nama)
{
    $namaTanpaPrefix = preg_replace('/^\d+\.\s*/', '', $nama);
    return trim($namaTanpaPrefix);
}

function randomBetween($min, $max, $step = 1)
{
    $numSteps = ($max - $min) / $step;
    $randomStep = rand(0, $numSteps);
    $randomNumber = $min + ($randomStep * $step);
    return $randomNumber;
}

function getTodayResiDate()
{
    $currentTime    = now();
    $currentTime    = Carbon::parse($currentTime);

    $morningStart   = Carbon::parse('00:01');
    $morningEnd     = Carbon::parse( env('BROADCAST_RESI_END', '08') . ':00');
    // $eveningStart    = Carbon::parse('08:00');
    // $eveningEnd      = Carbon::parse('23:59');
    if ($currentTime->isBetween($morningStart, $morningEnd)) {
        return $currentTime->subDay()->format('d-m-Y');
    } else {
        return $currentTime->format('d-m-Y');
    }
}

function isBroadcastingTime()
{
    $currentHour    = now()->format('H');
    $startTime      = env('BROADCAST_RESI_START', '20');
    $endTime        = env('BROADCAST_RESI_END', '08');
    if ($currentHour >= intval($startTime) || $currentHour < intval($endTime)) {
        return true;
    }
    return;
}

function getBroadcastRemaining($mappedSalesData) {
    $dataRemaining = [];
    foreach ($mappedSalesData as $key => $broadcast) {
        // $cs_sales       = $broadcast['cs_sales'];
        // $crm_sales      = $broadcast['crm_sales'];
        // $filtered_cs_sales = array_filter($cs_sales, function($item) {
        //     return !isset($item['sent']) || $item['sent'] === false;
        // });
        // $filtered_crm_sales = array_filter($crm_sales, function($item) {
        //     return !isset($item['sent']) || $item['sent'] === false;
        // });        
        // $dataRemaining = array_merge($dataRemaining, $filtered_cs_sales, $filtered_crm_sales);
        $broadcastSent = $broadcast['sent_date'] ?? null;
        if( ! $broadcastSent ) {
            $dataRemaining[] = $broadcast;
        }
    }

    return $dataRemaining;
}


function isDevelopment() {
    $currentDomain = $_SERVER['HTTP_HOST'];
    $endsWithTest = substr($currentDomain, -5) === '.test';
    return $endsWithTest;
}

function print_json($data, $pretty = true) {
    header('Content-Type: application/json');
    
    $options = $pretty ? JSON_PRETTY_PRINT : 0;

    echo json_encode($data, $options);
    exit;
}

function unBreakString($inputString) {
    $resultString = preg_replace("/\\\n/", " ", $inputString);
    $resultString = preg_replace("/\s+/", " ", $resultString);
    return $resultString;
}

function inArray($needle, $haystack) {
    $haystackLower = array_map('strtolower', $haystack);
    $needleLower = strtolower($needle);
    return in_array($needleLower, $haystackLower, true);
}

function sortBroadcastByCsId($remainingBroadcast){
    usort($remainingBroadcast, function ($a, $b) {
        $csIdComparison = $a['cs_id'] - $b['cs_id'];
        if ($csIdComparison === 0) {
            $salesTypeComparison = strcmp($b['sales_type'], $a['sales_type']);
            if ($salesTypeComparison === 0) {
                return $a['index'] - $b['index'];
            }
            return $salesTypeComparison;
        }
        return $csIdComparison;
    });
    return $remainingBroadcast;
}


function getMapingPriceSum($mappingData){
    $mappingPrice = array_sum(array_map(function ($item) {
        $price = isset($item['custom_price']) ? $item['custom_price'] : ($item['price'] ?? 0);
        $price = $price * $item['quantity'] ?? 1;
        return $price;
    }, $mappingData));
    return $mappingPrice;
}

function isMappingPriceEqual($productPrice, $productQty, $mappingData) {
    $mappingPrice = getMapingPriceSum($mappingData);
    if( $mappingPrice === $productPrice * $productQty ) {
        return true;
    }
    return false;
}
