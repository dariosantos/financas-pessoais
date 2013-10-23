<?php

define('FILE_HELPER_CACHE_SESSION_KEY', '___file_helper_cache');

class File {

    protected static $_cache_index = FILE_HELPER_CACHE_SESSION_KEY;

    static protected function getCache($directory, $only_path, $filter) {
        if (!isset($_SESSION[self::$_cache_index]) || !isset($_SESSION[self::$_cache_index][$directory . '_' . $only_path . '_' . $filter])) {
            return null;
        }

        return $_SESSION[self::$_cache_index][$directory . '_' . $only_path . '_' . $filter];
    }

    static protected function setCache($result, $directory, $only_path, $filter) {
        if (!isset($_SESSION[self::$_cache_index])) {
            $_SESSION[self::$_cache_index] = array();
        }
        $_SESSION[self::$_cache_index][$directory . '_' . $only_path . '_' . $filter] = $result;
    }

    static public function getSubDirectories($directory, $only_path = true, $filter = FALSE, $use_cache = true) {

        if ($use_cache) {
            $cached_result = self::getCache($directory, $only_path, $filter);
            if ($cached_result !== null) {
                return $cached_result;
            }
        }

        // if the path has a slash at the end we remove it here
        if (substr($directory, -1) == '/') {
            $directory = substr($directory, 0, -1);
        }

        // if the path is not valid or is not a directory ...
        if (!file_exists($directory) || !is_dir($directory)) {
            // ... we return false and exit the function
            return FALSE;

            // ... else if the path is readable
        } elseif (is_readable($directory)) {
            // initialize directory tree variable
            $directory_tree = array();

            // we open the directory
            $directory_list = opendir($directory);

            // and scan through the items inside
            while (FALSE !== ($file = readdir($directory_list))) {
                // if the filepointer is not the current directory
                // or the parent directory
                if ($file != '.' && $file != '..') {
                    // we build the new path to scan
                    $path = $directory . '/' . $file;

                    // if the path is readable
                    if (is_readable($path)) {
                        // we split the new path by directories
                        $subdirectories = explode('/', $path);


                        if (!strstr($path, '.svn')) {
                            // if the new path is a directory
                            if (is_dir($path)) {
                                // add the directory details to the file list

                                if ($only_path) {

                                    $directory_tree[] = $path;

                                    $directory_tree = array_merge($directory_tree, self::getSubDirectories($path, $only_path, $filter));
                                } else {

                                    $directory_tree[] = array(
                                        'path' => $path,
                                        'name' => end($subdirectories),
                                        'kind' => 'directory',
                                        // we scan the new path by calling this function
                                        'content' => self::getSubDirectories($path, $only_path, $filter));
                                }

                                // if the new path is a file
                            } elseif (is_file($path)) {
                                // get the file extension by taking everything after the last dot
                                $extension = end(explode('.', end($subdirectories)));

                                // if there is no filter set or the filter is set and matches
                                if ($filter === FALSE || $filter == $extension) {
                                    // add the file details to the file list

                                    if ($only_path) {

                                        $directory_tree[] = $path;
                                    } else {

                                        $directory_tree[] = array(
                                            'path' => $path,
                                            'name' => end($subdirectories),
                                            'extension' => $extension,
                                            'size' => filesize($path),
                                            'kind' => 'file');
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // close the directory
            closedir($directory_list);

            // return file list
            if ($use_cache) {
                self::setCache($directory_tree, $directory, $only_path, $filter);
            }

            return $directory_tree;

            // if the path is not readable ...
        } else {
            // ... we return false
            return FALSE;
        }
    }

    static public function insertLog($log_data) {

        $date = new DateTime();
        $fileToSave = 'siteLog__' . $date->format('Y_m_d') . '__.txt';

        $log_data = "\n" . '>>>>>>>>>> --- LOG ENTRY --- >>>>>>>>>>' . "\n" . $log_data . "\n" . '<<<<<<<<<< --- LOG ENTRY --- <<<<<<<<<<' . "\n";

        $contents = "\n" . "\n" . "\n" . "\n" . "\n" . '>>>>>>> BEGIN insertLog >>>>>>>' . "\n" . $log_data . "\n" . 'Date: ' . date('Y-m-d H:i:s') . "\n" . '<<<<<<< END insertLog <<<<<<<' . "\n";

        return file_put_contents('logs/' . $fileToSave, $contents, FILE_APPEND);
    }

    static public function createPathDirectories($path) {

        if (!file_exists($path)) {
            return mkdir($path, 0777, true); // true means recursive
        }
    }

}

?>