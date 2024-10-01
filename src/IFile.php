<?php

namespace Kalexhaym\ImportPrices;

use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class IFile
{
    /**
     * @param string|null $post_type
     *
     * @return void
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function create(string $post_type = null): void
    {
        global $wpdb;

        $sql = "
            SELECT 
                posts.ID, posts.post_title, prices.price
            FROM 
                {$wpdb->posts} as posts
            LEFT JOIN 
                " . $wpdb->prefix . IPRICES_PRICES_TABLE_NAME . " as prices ON posts.ID = prices.post_id
            WHERE
                post_status = 'publish'
        ";

        if (!empty($post_type)) {
            $sql .= " AND posts.post_type = '{$post_type}'";
        }

        $sql .= "
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

    /**
     * @param $file
     * @return void
     *
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function parse($file): void
    {
        global $wpdb;

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        $spreadsheet = $reader->load($file['file']);

        $sheets = $spreadsheet->getAllSheets();

        foreach ($sheets as $sheet) {
            $this->parseTable($sheet);
        }

        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . IPRICES_UPDATES_TABLE_NAME . ";");

        foreach ($this->data as $d) {
            $wpdb->query("
                INSERT INTO " . $wpdb->prefix . IPRICES_UPDATES_TABLE_NAME . " (old_price,new_price,post_id,status)
                VALUES ({$d['old_price']},{$d['new_price']},{$d['post_id']},{$d['status']})
            ");
        }

        unlink($file['file']);
    }

    /**
     * @param Worksheet $sheet
     * @param int $start_row
     *
     * @return void
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function parseTable(Worksheet $sheet, int $start_row = 1): void
    {
        global $wpdb;

        $i = $start_row;

        while (true) {
            $post_id = $sheet->getCell("A{$i}")->getValue();
            $new_price = $sheet->getCell("C{$i}")->getValue();

            if (empty($new_price) && empty($post_id)) {
                break;
            }

            $old_price = (float) $wpdb->get_var("SELECT price FROM " . $wpdb->prefix . IPRICES_PRICES_TABLE_NAME . " WHERE post_id = " . $post_id . ";");
            $status = empty($wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND ID = " . $post_id))
                ? IPRICES_STATUS_NOT_FOUND
                : (
                    $old_price == $new_price
                        ? IPRICES_STATUS_NO_CHANGES
                        : IPRICES_STATUS_PENDING_UPDATE
                );

            $this->data[] = [
                'post_id' => $post_id,
                'old_price' => $old_price,
                'new_price' => $new_price,
                'status' => $status
            ];

            $i++;
        }
    }
}