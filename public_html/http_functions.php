<?php
    function error_die($string)
    {
        http_response_code(500);
        usleep(500000);
        die($string);
    }
    function redirect($loc)
    {
        http_response_code(302);
        header('Location: ' . $loc);
    }

    function error_die_with_code($code, $string)
    {
        http_response_code($code);
        usleep(500000);
        die($string);
    }

?>