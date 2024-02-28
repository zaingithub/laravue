<?php

use Illuminate\Support\Str;
use Carbon\Carbon;

if (!function_exists('createTimestamps')) {
    function createTimestamps($dateString, $inputFormat = 'd-m-Y', $outputFormat = 'Y-m-d H:i:s')
    {
        $carbonDate = Carbon::createFromFormat($inputFormat, $dateString);
        $formattedDate = $carbonDate->format($outputFormat);
        return $formattedDate;
    }
}

if (!function_exists('customDate')) {
    function customDate($dateString)
    {
        return createTimestamps($dateString, 'Y-m-d H:i:s', 'd-m-Y');
    }
}

if (!function_exists('customModel')) {
    function customModel($modelName)
    {
        $baseModel = 'App\\Models\\' . $modelName;
        if (class_exists($baseModel)) {
            $baseModel = app($modelName);
            return $modelName;
        }
        return;
    }
}

if (!function_exists('getItemsByRangeIndex')) {
    function getItemsByRangeIndex($array, $startIndex, $endIndex)
    {
        if (empty($array)) {
            return [];
        }
        $resultArray = array_slice($array, $startIndex, $endIndex - $startIndex + 1, true);
        $resultArray = array_combine(range($startIndex, $endIndex), $resultArray);
        return $resultArray;
    }
}

if (!function_exists('formatExcelDate')) {
    function formatExcelDate($tanggalISO8601)
    {
        $tanggal = Carbon::parse($tanggalISO8601)->setTimezone('Asia/Jakarta');
        $formattedDate = $tanggal->format('d F Y, H:i:s T');
        return $formattedDate;
    }
}

if (!function_exists('formatRouteMethod')) {
    function formatRouteMethod($nama)
    {
        $formattedNama = ucfirst($nama);
        if (strpos($formattedNama, '-') !== false) {
            $formattedNama = str_replace('-', '', $formattedNama);
            $formattedNama = preg_replace_callback('/-([a-z])/', function ($matches) {
                return strtoupper($matches[1]);
            }, $formattedNama);
        }
        return $formattedNama;
    }
}

if (!function_exists('getDateInput')) {
    function getDateInput($input)
    {
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
}

if (!function_exists('ss')) {
    function ss($data)
    {
        try {
            dd($data->toArray());
        } catch (\Throwable $th) {
            dd($data);
        }
    }
}
if (!function_exists('generateSocketId')) {
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
}

if (!function_exists('uuid')) {
    function uuid()
    {
        return Str::uuid();
    }
}

if (!function_exists('formatPhone')) {
    function formatPhone($nohp)
    {
        $nohp = preg_replace('/[^0-9]/', '', $nohp);
        $nohp = preg_replace('/^08/', '628', $nohp);
        return $nohp;
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($number)
    {
        $formattedNumber = number_format($number, 2, ',', '.');
        return $formattedNumber;
    }
}

if (!function_exists('getDateBefore')) {
    function getDateBefore($currentDate, $subDays = 2, $format = 'd-m-Y')
    {
        $dateCarbon = Carbon::createFromFormat($format, $currentDate);
        $dateBefore = $dateCarbon->subDays($subDays);
        $dateBefore = $dateBefore->format($format);
        return $dateBefore;
    }
}

if (!function_exists('zeroFill')) {
    function zeroFill($number, $length = 4)
    {
        $number = substr((string) $number, 0, $length);
        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }
}
if (!function_exists('isEqualString')) {
    function isEqualString($string1, $string2)
    {
        return strcasecmp($string1, $string2) === 0;
    }
}

if (!function_exists('isEqualStringAbsolute')) {
    function isEqualStringAbsolute($string1, $string2)
    {
        $string1WithoutSpaces = str_replace(' ', '', $string1);
        $string2WithoutSpaces = str_replace(' ', '', $string2);
        return strcasecmp($string1WithoutSpaces, $string2WithoutSpaces) === 0;
    }
}

if (!function_exists('containsWordIgnoreCase')) {
    function containsWordIgnoreCase($string, $word)
    {
        return stripos($string, $word) !== false;
    }
}

if (!function_exists('convertWhatsAppToTelegramHTML')) {
    function convertWhatsAppToTelegramHTML($text)
    {
        $text = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $text);
        $text = preg_replace('/(?<!\*)_(?!_)([^_]+)_(?<!\*)/', '<i>$1</i>', $text);
        $text = preg_replace('/~([^~]+)~/', '<s>$1</s>', $text);
        $text = preg_replace('/``([^`]+)``/', '<code>$1</code>', $text);
        return $text;
    }
}

if (!function_exists('convertTelegramHTMLToWhatsApp')) {
    function convertTelegramHTMLToWhatsApp($text)
    {
        $text = preg_replace('/<code>(.*?)<\/code>/', '``$1``', $text);
        $text = preg_replace('/<s>(.*?)<\/s>/', '~$1~', $text);
        $text = preg_replace('/<i>(.*?)<\/i>/', '_$1_', $text);
        $text = preg_replace('/<em>(.*?)<\/em>/', '_$1_', $text);
        $text = preg_replace('/<b>(.*?)<\/b>/', '*$1*', $text);
        return $text;
    }
}

if (!function_exists('waiting')) {
    function waiting($seconds = 1)
    {
        usleep($seconds * 1000000);
    }
}

if (!function_exists('randomBetween')) {
    function randomBetween($min, $max, $step = 1)
    {
        $numSteps = ($max - $min) / $step;
        $randomStep = rand(0, $numSteps);
        $randomNumber = $min + ($randomStep * $step);
        return $randomNumber;
    }
}

if (!function_exists('isDevelopment')) {
    function isDevelopment()
    {
        $currentDomain = $_SERVER['HTTP_HOST'];
        $endsWithTest = substr($currentDomain, -5) === '.test';
        return $endsWithTest;
    }
}

if (!function_exists('printJson')) {
    function printJson($data, $pretty = true)
    {
        header('Content-Type: application/json');
        $options = $pretty ? JSON_PRETTY_PRINT : 0;
        echo json_encode($data, $options);
        exit;
    }
}

if (!function_exists('unBreakString')) {
    function unBreakString($inputString)
    {
        $resultString = preg_replace("/\\\n/", " ", $inputString);
        $resultString = preg_replace("/\s+/", " ", $resultString);
        return $resultString;
    }
}

if (!function_exists('inArray')) {
    function inArray($needle, $haystack)
    {
        $haystackLower = array_map('strtolower', $haystack);
        $needleLower = strtolower($needle);
        return in_array($needleLower, $haystackLower, true);
    }
}
