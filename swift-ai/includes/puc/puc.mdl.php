<?php

require dirname(__FILE__) . '/Puc/v4/Autoloader.php';
new Puc_v4_Autoloader();

//Register classes defined in this file with the factory.
Puc_v4_Factory::addVersion('Plugin_UpdateChecker', 'Puc_v4_Plugin_UpdateChecker', '4.0');

Puc_v4_Factory::buildUpdateChecker(
      SWIFT3_API_URL . 'update/info',
      SWIFT3_FILE,
      SWIFT3_SLUG
);

?>