<?php
    return [
        'type'                          => "hosting",
        'access-hash'                   => true,
        'server-info-checker'           => true,
        'server-info-port'              => true,
        'server-info-not-secure-port'   => 2020,
        'server-info-secure-port'       => 2020,
        'supported' => [
            'disk-bandwidth-usage',
            'change-password',
        ],
        'configurable-option-params'    => [
            'radio_name',
        ],
    ];