<?php

namespace Kalexhaym\ImportRates;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class CreateFile
{
    public function __construct()
    {
        global $wpdb;

        $sql = "
            SELECT 
                posts.ID, posts.post_title, prices.price
            FROM 
                {$wpdb->posts} as posts
            LEFT JOIN 
                " . $wpdb->prefix . IPRICES_PRICES_TABLE_NAME . " as prices ON posts.ID = prices.post_id
            ORDER BY 
                posts.ID;
        ";

        $data = $wpdb->get_results($sql);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $i = 1;

        foreach ($data as $row) {
            $sheet->setCellValue("A{$i}", $row->ID);
            $sheet->setCellValue("B{$i}", $row->post_title);
            $sheet->setCellValue("C{$i}", $row->price ?? 0);
            $i++;
        }

        $writer = new Xls($spreadsheet);

        $writer->save('php://output');
    }
}