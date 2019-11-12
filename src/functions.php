<?php
function debug($message)
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $trace = array_shift($trace);
    $info = sprintf("File %s [Line:%s]", $trace['file'], $trace['line']);

    if (php_sapi_name() == "cli") {
        echo sprintf("\n--- [debug] %s ---\n", $info);
        var_dump($message);
        echo "---\n";
    } else {
        echo '<pre class="debug" style="background-color: #CCC; color: green">';
        echo '<span style="font-weight: bold;">' . $info . '</span><br />';
        //var_dump($message);
        $var = var_export($message, true);
        echo h($var);
        echo '</pre>';
    }

}

function h($string)
{
    if (func_num_args() > 1) {
        $args = func_get_args();
        $string = call_user_func_array('sprintf', $args);
    }

    return htmlentities($string, null, 'UTF-8');
}

function print_routes($routes)
{
    $html = "<table style='width: 100%;'><tr><th>Name</th><th>Route</th><th>Compiled</th></tr>";
    /* @var \Flow\Router\Route $route */
    foreach ($routes as $route) {
        $html .= sprintf(
            "<tr><td>%s</td><td>%s</td><td>%s</td></tr>",
            $route->getName(),
            $route->getRoute(),
            $route->getCompiled()
        );
    }

    echo $html;
}
