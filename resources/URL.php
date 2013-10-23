<?php

class URL {

    static public function getUrl($url, $add_params = array()) {
        $url = substr_replace($url, '', strpos($url, '?')); // Remove litteral params in url

        $params = array_merge($_GET, $add_params);

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    static public function baseURL() {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";
        return $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    }

}

?>