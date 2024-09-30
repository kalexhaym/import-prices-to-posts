<?php

namespace Kalexhaym\ImportRates;

use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

class ParseFile
{
    /**
     * @var array
     */
    private array $data = [];

    /**
     * @param $file
     *
     * @throws Exception
     */
    public function __construct($file)
    {
        global $wpdb;

        $reader = new Xls();
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
     * @param $sheet
     * @param int $startRow
     *
     * @return void
     */
    private function parseTable($sheet, int $startRow = 1): void
    {
        global $wpdb;

        $i = $startRow;

        while (true) {
            $post_id = $sheet->getCell("A{$i}")->getValue();
            $new_price = $sheet->getCell("C{$i}")->getValue();

            if (empty($new_price) && empty($post_id)) {
                break;
            }

            $old_price = (float) $wpdb->get_var( "SELECT price FROM " . $wpdb->prefix . IPRICES_PRICES_TABLE_NAME . " WHERE post_id = " . $post_id . ";" );
            $status = empty(
            $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE ID = " . $post_id))
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