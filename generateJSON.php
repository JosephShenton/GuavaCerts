<?php

    // Functions

    if (!function_exists("str_contains")) {
        function str_contains($needle, $haystack) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            } else {
                return false;
            }

            return false;
        }
    }

    function getDirContents($dir, &$results = array()) {
        $files = scandir($dir);
    
        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                getDirContents($path, $results);
                $results[] = $path;
            }
        }
    
        return $results;
    }

    function strright($str, $separator) {
        if (intval($separator)) {
            return substr($str, -$separator);
        } elseif ($separator === 0) {
            return $str;
        } else {
            $strpos = strpos($str, $separator);
    
            if ($strpos === false) {
                return $str;
            } else {
                return substr($str, -$strpos + 1);
            }
        }
    }
    
    function strleft($str, $separator) {
        if (intval($separator)) {
            return substr($str, 0, $separator);
        } elseif ($separator === 0) {
            return $str;
        } else {
            $strpos = strpos($str, $separator);
    
            if ($strpos === false) {
                return $str;
            } else {
                return substr($str, 0, $strpos);
            }
        }
    }

    // Load Certificates

    $folderContents = getDirContents("certificates/");
    $certs = array();

    foreach ($folderContents as $item) {
        if (str_contains(".p12", $item) || str_contains(".mobileprovision", $item) || str_contains("password.txt", $item)) {
            $path_parts = pathinfo($item);
            $passFile = false;

            if (!$cert_store = file_get_contents($item)) {
                echo "Error: Unable to read the cert file\n";
                exit;
            }

            $password = str_replace(".".$path_parts['extension'], "", $path_parts['basename']);
            $p12 = strstr($path_parts['dirname'], "certificates/")."/".urlencode(str_replace(".".$path_parts['extension'], ".p12", $path_parts['basename']));
            $mobileprovision = strstr($path_parts['dirname'], "certificates/")."/".urlencode(str_replace(".".$path_parts['extension'], ".mobileprovision", $path_parts['basename']));
            
            if (openssl_pkcs12_read($cert_store, $cert_info, $password)) {
                // echo "Certificate Information\n";
                $certdata = openssl_x509_parse($cert_info['cert'],0);
                // print_r($certdata);
        
                $validFrom = date('Y-m-d H:i:s', $certdata['validFrom_time_t']);
                $validTo = date('Y-m-d H:i:s', $certdata['validTo_time_t']);
                $displayName = $certdata['subject']['organizationName'];
                $commonName = $certdata['subject']['commonName'];
        
                $certdata = array("validFrom" => $validFrom, "validTo" => $validTo, "displayName" => $displayName, "commonName" => $commonName);
                
                $p12URL = "https://github.com/JosephShenton/420Certs/blob/master/".$p12."?raw=true";
                $mobileprovisionURL = "https://github.com/JosephShenton/420Certs/blob/master/".$mobileprovision."?raw=true";
                $certs["certificates"][] = array(
                    "information" => $certdata,
                    "p12" => $p12URL,
                    "mobileprovision" => $mobileprovisionURL,
                    "password" => $password
                );

            } else {
                // echo "Error: Unable to read the cert store.\n";
                $certdata = array("validFrom" => "ERROR", "validTo" => "ERROR", "displayName" => "ERROR", "commonName" => "ERROR");
                // exit;
                // $p12URL = "https://github.com/JosephShenton/420Certs/blob/master/".urlencode($p12)."?raw=true";
                // $certs["certificates"][] = array(
                //     "information" => $certdata,
                //     "p12" => $p12
                // );
            }
        } else {

        }
    }

    header("Content-Type: application/json");
    echo json_encode($certs, JSON_PRETTY_PRINT);

?>