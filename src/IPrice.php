<?php

namespace Kalexhaym\ImportRates;

class IPrice
{
    /**
     * @param array $files
     *
     * @return string
     */
    public function upload(array $files): string
    {
        if(!empty($files['file']['name'])){
            $uploaded_file = $files['file'];
            $upload_overrides = array('test_form' => false);

            $move_file = wp_handle_upload($uploaded_file, $upload_overrides);

            if ($move_file && !isset($move_file['error'])) {
                try {
                    (new IFile())->parse($move_file);

                    return sprintf(
                        '<div class="notice notice-success"><p><strong>%1$s</strong>: %2$s</p></div>',
                        'Success',
                        'File uploaded'
                    );
                } catch (\Exception $e) {
                    return sprintf(
                        '<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>',
                        'Error',
                        $e->getMessage()
                    );
                }
            } else {
                return sprintf(
                    '<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>',
                    'Error',
                    $move_file['error']
                );
            }
        }

        return sprintf(
            '<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>',
            'Error',
            'File is required'
        );
    }

    /**
     * @return void
     */
    public function update(): void
    {
        global $wpdb;

        $updates = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . IPRICES_UPDATES_TABLE_NAME . " WHERE status = " . IPRICES_STATUS_PENDING_UPDATE . ";");

        foreach ($updates as $update) {
            $has_post = $wpdb->get_var( "SELECT id FROM " . $wpdb->prefix . IPRICES_PRICES_TABLE_NAME . " WHERE post_id = " . $update->post_id . ";" );

            if (!empty($has_post)) {
                $wpdb->update(
                    $wpdb->prefix . IPRICES_PRICES_TABLE_NAME,
                    [
                        'price' => $update->new_price,
                    ],
                    [
                        'post_id' => $update->post_id,
                    ],
                    array(
                        '%f',
                    ),
                    array(
                        '%d',
                    )
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . IPRICES_PRICES_TABLE_NAME,
                    [
                        'price' => $update->new_price,
                        'post_id' => $update->post_id,
                    ],
                    array(
                        '%f',
                        '%d',
                    )
                );
            }
        }

        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . IPRICES_UPDATES_TABLE_NAME . ";");
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        global $wpdb;

        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . IPRICES_UPDATES_TABLE_NAME . ";");
    }

    /**
     * @param int $post_id
     *
     * @return float
     */
    public static function getPrice(int $post_id): float
    {
        global $wpdb;

        $price = $wpdb->get_var( "SELECT price FROM " . $wpdb->prefix . IPRICES_PRICES_TABLE_NAME . " WHERE post_id = " . $post_id . ";" );

        return (float) !empty($price) ? $price : 0;
    }
}