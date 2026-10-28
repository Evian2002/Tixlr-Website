<?php
spl_autoload_register(function ($class) {
    $path = __DIR__ . "/classes/" . $class . ".php";
    if (file_exists($path)) {
        include $path;
    }
});
?>
