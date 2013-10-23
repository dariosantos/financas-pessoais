<?php

require_once __DIR__ . '/File.php';

class Log {

    static public function newEntry() {

        $num_params = func_num_args();

        if ($num_params > 0) {

            $log_text = '';
            for ($i = 0; $i < $num_params; $i++) {
                $param = func_get_arg($i);

                $output = var_export($param, true);
                $output = str_replace("\\\\", "\\", $output); // Replace \\ with \
                $output = str_replace("\\'", "'", $output); // Replace \' with '
                $log_text .= "\n" . $output;
            }

            if (USE_DATABASE) {
                DB::insertLog($log_text);
                $data = array(
                    'database_id_log' => DB::$last_inserted_id,
                    'database_last_error' => DB::$last_error_message,
                    'data' => $log_text
                );
            } else {
                $data = array(
                    'USE_DATABASE' => USE_DATABASE,
                    'data' => $log_text
                );
            }

            $output = var_export($data, true);
            $output = str_replace("\\\\", "\\", $output); // Replace \\ with \
            $output = str_replace("\\'", "'", $output); // Replace \' with '
            File::insertLog($output);
        }
    }

}

?>