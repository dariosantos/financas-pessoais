<?php

// http://www.i18nguy.com/unicode/language-identifiers.html

require_once __DIR__ . '/LangBase.php';
require_once __DIR__ . '/File.php';

class LangXML extends LangBase {

    static protected $_lang_file = null;
    static protected $_lang_file_xml = null;

    static public function COPY($copy_name, $default_value = '') {
        $instance = new LangXML();
        return $instance->getCOPY($copy_name, $default_value);
    }

    protected function getCopyValue($copy_name) {
        $copy_value = null;

        if (self::checkLangFile()) {

            // http://www.earthinfo.org/xpaths-with-php-by-example/

            $copys = self::$_lang_file_xml->xpath('//copy[@name="' . $copy_name . '"]');
            if (is_array($copys) && count($copys) > 0) {
                $copy_value = $copys[0];

                if (count($copys) > 1) {
                    // WARNING
                    Log::newEntry('WARNING: LangXML::getCopyValue() found more than one match in lang file', '$copy_name (' . $copy_name . ')', $copys, self::$_lang, self::$_lang_file);
                }
            }
        }

        return $copy_value;
    }

    static public function checkLangFile() {
        if (self::$_lang_file === NULL || self::$_lang_file_xml === NULL) {
            $lang_files = self::getLangFiles(self::$_lang);
            if (is_array($lang_files) && count($lang_files) > 0) {

                self::$_lang_file = (is_array($lang_files) && count($lang_files) > 0 ? $lang_files[0] : null);
                self::$_lang_file_xml = self::loadXML(self::$_lang_file);

                return true;
            }
            // ERROR
            Log::newEntry('Could not find the lang file to ' . self::$_lang, 'NOTE: Could be because the session cache in the File::getSubDirectories()');
            return false;
        }

        return true;
    }

    static protected function getLangFiles($lang = null) {

        $filter = null;
        if (!empty($lang)) {
            $filter = $lang;
        }

        // Adds the site langs
        $lang_files = File::getSubDirectories('langs', true, 'xml');

        if (!empty($filter)) {
            $lang_files = (is_array($lang_files) ? self::filterPaths($lang_files, $filter) : $lang_files);
        }

        $lang_files = (!empty($lang_files) ? $lang_files : false);

        return $lang_files;
    }

    static protected function filterPaths($paths, $filter) {
        $filtered_path = array();
        foreach ($paths as $path) {
            if (basename($path, '.xml') == $filter) {
                $filtered_path[] = $path;
            }
        }
        $paths = $filtered_path;

        return $filtered_path;
    }

    static protected function loadXML($file) {
        if (!empty($file)) {
            return simplexml_load_file($file);
        }
        return null;
    }

}

?>