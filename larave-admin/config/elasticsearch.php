<?php
return [
    'hosts' => [
        env('ELASTIC_HOST')
    ],
    'log_index' => env('ELASTIC_LOG_INDEX'),
    'log_type' => env('ELASTIC_LOG_TYPE'),
];