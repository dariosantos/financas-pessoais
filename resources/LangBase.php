<?php

require_once __DIR__ . '/Log.php';

abstract class LangBase {

    static protected $_lang = null;

    static public function updateCurrentLang() {

        $supported_langs = array(
            'pt' => 'pt_PT',
            'en' => 'en_US'
        );

        $default_language = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && is_string($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) >= 2 ? strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)) : 'en');
        $default_language = (array_key_exists($default_language, $supported_langs) ? $supported_langs[$default_language] : 'en_US');
        $default_language = (isset($_SESSION['lang']) && trim($_SESSION['lang']) != '' ? $_SESSION['lang'] : $default_language);

        // Check which language to use in the site
        if (isset($_GET['lang'])) {
            $new_lang = $_GET['lang'];
            $_SESSION['lang'] = (array_key_exists($new_lang, $supported_langs) ? $supported_langs[$new_lang] : $default_language);

            setcookie("lang", $_SESSION['lang'], time() + 60 * 60 * 24 * 30);  /* expire in 30 days */
        }

        if (!isset($_SESSION['lang'])) {
            if (isset($_COOKIE['lang'])) {
                $_SESSION['lang'] = $_COOKIE['lang'];
            } else {
                $_SESSION['lang'] = $default_language; // Default language
            }
        }
        self::setLang($_SESSION['lang']);
    }

    static public function getLang() {
        return self::$_lang;
    }

    static public function setLang($lang) {
        self::$_lang = $lang;
    }

    public function getCOPY($copy_name, $default_value = '') {
        // Get the copy value

        if (self::$_lang === NULL) {
            // WARNING
            Log::newEntry('ERROR: LangBase::COPY("' . $copy_name . '", "' . $default_value . '") has no lang set.');

            return $default_value;
        }

        $copy_value = $this->getCopyValue($copy_name);

        if ($copy_value === NULL) {
            // WARNING
            Log::newEntry('WARNING: LangBase::COPY("' . $copy_name . '", "' . $default_value . '") could NOT find a match', 'Lang (' . self::$_lang . ')');

            return $default_value;
        }

        return $copy_value;
    }

    abstract protected function getCopyValue($copy_name); /* {
      Log::newEntry("ERROR: LangBase::getCopyValue('$copy_name') is NOT overriden by a sub Class");
      return NULL;
      } */
}

?>