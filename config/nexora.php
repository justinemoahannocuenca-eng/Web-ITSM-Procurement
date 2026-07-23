<?php

return [
    // Temporary QA switch: permits authenticated root admins to open module
    // routes without impersonating a client employee. It defaults to enabled
    // during the integration phase; set the variable to false to turn it off.
    'root_admin_module_testing' => env('ROOT_ADMIN_MODULE_TESTING', true),
];
