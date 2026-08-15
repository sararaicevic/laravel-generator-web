<?php

return [
    /*
     * Hard limits for submitted DSL specifications.
     */
    'max_dsl_bytes' => (int) env('GENERATOR_MAX_DSL_BYTES', 200_000),
];
