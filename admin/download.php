<?php

use Kalexhaym\ImportRates\IPrice;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="prices.xls"');
header('Cache-Control: max-age=0');

(new IPrice())->download();