<?php declare (strict_types=1);

function parseParameters(array $params): array
{
    $parameters = [];

    foreach ($params as $param) {
        if (str_contains($param, "=")) {
            $parameters[] = explode("=", $param);
        } else {
            $parameters[] = [$param, true];
        }
    }
    
    return $parameters;
}