<?php

namespace App\Helpers;

class MobileNetworkHelper
{


    public static function getNetworkByPrefix($mobileNumber)
    {
        $normalizedNumber = self::normalizeMobileNumber($mobileNumber);
        $prefixes = [
            env('PAY_METHOD_HALOTEL') => ['61', '62'],
            env('PAY_METHOD_TIGO') => ['65', '67', '71', '77'],
            env('PAY_METHOD_AIRTEL') => ['68', '69', '78'],
            env('PAY_METHOD_VODA') => ['74', '75', '76', '79']
        ];
        foreach ($prefixes as $network => $networkPrefixes) {
            foreach ($networkPrefixes as $prefix) {
                if (strpos($normalizedNumber, $prefix) === 0) {
                    return $network;
                }
            }
        }
        return null;
    }

    public static function normalizeMobileNumber($mobileNumber)
    {
        $mobileNumber = preg_replace('/\D/', '', $mobileNumber);
        if (strpos($mobileNumber, '255') === 0) {
            return substr($mobileNumber, 3); // Remove the country code
        } elseif (strpos($mobileNumber, '0') === 0) {
            return substr($mobileNumber, 1); // Remove the leading 0
        } elseif (strpos($mobileNumber, '+255') === 0) {
            return substr($mobileNumber, 4); // Remove the +255 country code
        }

        return $mobileNumber;
    }





}