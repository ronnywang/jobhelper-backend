<?php

if (preg_match('#^/static/#', $_SERVER["REQUEST_URI"])) {
    return false;    // serve the requested resource as-is.
}

include("index.php");

