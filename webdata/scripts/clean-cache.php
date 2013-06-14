<?php

include(__DIR__ . '/../init.inc.php');

Pix_Table::getDefaultDb()->query("DELETE FROM `cache` WHERE `updated_at` < " . (time() - 7200));
Pix_Table::getDefaultDb()->query("OPTIMIZE TABLE `cache`");
