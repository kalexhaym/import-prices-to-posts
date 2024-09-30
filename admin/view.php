<?php

use Kalexhaym\ImportRates\IPrice;

if(isset($_POST['but_submit_upload'])) {
    echo (new IPrice())->upload($_FILES);
}

if(isset($_POST['but_submit_update'])) {
    (new IPrice())->update();
}

if(isset($_POST['but_submit_clear'])) {
    (new IPrice())->clear();
}

global $wpdb;

$updates_data = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . IPRICES_UPDATES_TABLE_NAME . " ORDER BY status DESC;");
$current_data = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . IPRICES_PRICES_TABLE_NAME . " ORDER BY post_id;");
$post_types = $wpdb->get_results("SELECT post_type FROM {$wpdb->posts} GROUP BY post_type;");

?>

<div class="wrap" id="iprices_page">

    <h1 class="wp-heading-inline">Import Prices to Posts</h1>
    <hr/>

    <h2>Upload File</h2>
    <form method='POST' name='iprices_upload' enctype='multipart/form-data'>
        <table>
            <tr>
                <td><input type='file' name='file'></td>
                <td><input type='submit' name='but_submit_upload' value='Upload' class="button action"></td>
            </tr>
        </table>
    </form>
    <hr/>

    <?php if (!empty($updates_data)) { ?>
        <h2>Pending Updates</h2>
        <table class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
                <th>Post</th>
                <th>Old Price</th>
                <th>New Price</th>
                <th>Status</th>
            </thead>
            <tbody>
                <?php foreach($updates_data as $item) { ?>
                    <tr <?php if ($item->status == IPRICES_STATUS_NOT_FOUND) { echo 'class="iprices-not-found"'; } elseif ($item->status == IPRICES_STATUS_NO_CHANGES) { echo 'class="iprices-no-changes"'; } else { echo 'class="iprices-pending-update"'; } ?>>
                        <td>
                            <?php if (!empty($item->post_id)) {
                                if ($item->status != IPRICES_STATUS_NOT_FOUND) { ?>
                                    <a href="/wp-admin/post.php?post=<?php echo $item->post_id; ?>&action=edit"><?php echo $item->post_id; ?> - <?php echo get_the_title($item->post_id); ?></a>
                                <?php } else {
                                    echo $item->post_id;
                                }
                            } ?>
                        </td>
                        <td><?php echo $item->old_price; ?></td>
                        <td><?php echo $item->new_price; ?></td>
                        <td>
                            <?php
                                if ($item->status == IPRICES_STATUS_NOT_FOUND) {
                                    echo 'Not Found';
                                } elseif ($item->status == IPRICES_STATUS_NO_CHANGES) {
                                    echo 'No Changes';
                                } else {
                                    echo 'Pending Update';
                                }
                            ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <div class="iprices-update-buttons">
            <form method='POST' name='iprices_update' enctype='multipart/form-data'>
                <input type='submit' name='but_submit_update' value='Update' class="button action iprices-update-button">
            </form>
            <form method='POST' name='iprices_clear' enctype='multipart/form-data'>
                <input type='submit' name='but_submit_clear' value='Clear' class="button action iprices-clear-button">
            </form>
        </div>
        <hr/>
    <?php } ?>

    <h2>Current Prices</h2>
    <?php if (!empty($current_data)) { ?>
        <table class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
                <th>Post</th>
                <th>Price</th>
            </thead>
            <tbody>
                <?php foreach($current_data as $item) { ?>
                    <tr>
                        <td>
                            <a href="/wp-admin/post.php?post=<?php echo $item->post_id; ?>&action=edit"><?php echo $item->post_id; ?> - <?php echo get_the_title($item->post_id); ?></a>
                        </td>
                        <td><?php echo $item->price; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>
    <div class="iprices-update-buttons">
        <form method='GET' action="/wp-json/iprices/download" name='iprices_download' enctype='multipart/form-data'>
            <table>
                <tr>
                    <td>
                        <select name="post_type">
                            <option label="All" value=""></option>
                            <?php foreach ($post_types as $option) { ?>
                                <option label="<?php echo $option->post_type; ?>" value="<?php echo $option->post_type; ?>"></option>
                            <?php } ?>
                        </select>
                    </td>
                    <td><input type='submit' name='but_submit_download' value='Download' class="button action iprices-update-button"></td>
                </tr>
            </table>
        </form>
    </div>
    <hr/>

    <h2>Price Code</h2>
    <span>Paste this code into your template to get the price:</span>
    <code>
        &lt;?php Kalexhaym\ImportRates\IPrice::getPrice(get_the_id()); ?&gt;
    </code>

</div>