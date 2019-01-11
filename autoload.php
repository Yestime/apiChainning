<?php

$classMap = [
    'apiChain\apiChain' => __DIR__ . '/inc/apiChain.php',
    'apiChain\ApiChainError' => __DIR__ . '/inc/ApiChainError.php',
    'apiChain\apiResponse' => __DIR__ . '/inc/apiResponse.php',
    'apiChain\HTTPResponse' => __DIR__ . '/inc/HTTPResponse.php',
    'apiChain\ArrayUtils' => __DIR__ . '/inc/ArrayUtils.php',
];

spl_autoload_register(function ($class) use ($classMap) {
    if ( array_key_exists($class, $classMap) ) {
        $filename = $classMap[$class];

        if ( file_exists($filename) ) {
            include_once $filename;
        }
    }
});

include_once __DIR__ . '/vendor/autoload.php';