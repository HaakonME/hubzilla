<?php /** @file */

function system_down() {
header('HTTP/1.0 503 Service Unavailable');
echo <<< EOT
<html>
<head><title>System Unavailable</title></head>
<body>
Apologies but this site is unavailable at the moment. Please try again later.
</body>
</html>
EOT;
}