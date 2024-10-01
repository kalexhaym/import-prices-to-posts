<?php

use Kalexhaym\ImportPrices\IFile;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="prices.xls"');
header('Cache-Control: max-age=0');

(new IFile())->create($_GET['post_type'] ?? null);