<?php
return [
    'everything_is_ok' => false, // true: all success is 200, false: will see the real status code
    'paths' => ['api/*', 'oauth/*'], // paths to apply the custom response class
    'response' => '\Mesak\LaravelApiResponse\Http\ApiResponse', //custom response class
    'schema' => '\Mesak\LaravelApiResponse\Http\ApiResponseSchema', //custom response schema class
    'exception_trace_limit' => 3, // if set to 0, will show all trace
];
