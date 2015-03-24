<?php

require dirname( __DIR__ ) . '/optimizely.php';

define( 'OPTIMIZELY_API_KEY', getenv( 'OPTIMIZELY_API_KEY' ) ?: '215789e2d709bcc4fda5b2244c3491a8:74c5f79c' );

//define( 'OPTIMIZELY_PROJECT_ID', getenv( 'OPTIMIZELY_PROJECT_ID' ) );
define( 'OPTIMIZELY_PROJECT_ID', 2651261141 );

//define( 'OPTIMIZELY_EXPERIMENT_ID', getenv( 'OPTIMIZELY_EXPERIMENT_ID' ) );
define( 'OPTIMIZELY_EXPERIMENT_ID', 2679600449 );

