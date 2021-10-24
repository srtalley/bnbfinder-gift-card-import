<?php
/*
Plugin Name: bnbfinder Gift Card Import
Description: Imports the totals and balances for the Gift Cards
Author: Maia Consulting
Author URI: https://www.maiainternetconsulting.com/
Version: 1.1.7
Text Domain: bnbfinder
License: GPLv2
*/

class BNBGiftCards {

    public function __construct() {
        add_action( 'admin_menu', array($this, 'gc_import_data_menu') );
        add_action( 'wp_ajax_import_gc_amounts', array($this, 'import_gc_amounts_ajax') );
        add_action( 'wp_ajax_import_gc_totals', array($this, 'import_gc_totals_ajax') );
        add_action( 'admin_init', array($this, 'gc_download_redemptions') );
    } // end function 

    /**
     * Admin menu 
     */

    public function gc_import_data_menu() {
        // add_menu_page( 'My Top Level Menu Example', 'Top Level Menu', 'manage_options', 'myplugin/myplugin-admin-page.php', 'myplguin_admin_page', 'dashicons-tickets', 6  );
        add_options_page('Import Gift Card Data', 'Import GC Data', 'manage_options', 'import-gc-data', array($this,'gc_import_data_page'));
    }

    public function gc_import_data_page() {
        echo '<h2>Imports the GC data</h2>';
        echo '<div style="margin-bottom: 20px;"><button id="import-gc-balances1">Import Gift Card Balances Part 1</button><div id="import-gc-balances-result1"></div></div>';
        echo '<div style="margin-bottom: 20px;"><button id="import-gc-balances2">Import Gift Card Balances Part 2</button><div id="import-gc-balances-result2"></div></div>';
        echo '<div style="margin-bottom: 20px;"><button id="import-gc-balances3">Import Gift Card Balances Part 3</button><div id="import-gc-balances-result3"></div></div>';
        echo '<div style="margin-bottom: 20px;"><button id="import-gc-totals1">Import Gift Card Totals Part 1</button><div id="import-gc-totals-result1"></div></div>';
        echo '<div style="margin-bottom: 20px;"><button id="import-gc-totals2">Import Gift Card Totals Part 2</button><div id="import-gc-totals-result2"></div></div>';
        echo '<div style="margin-bottom: 20px;"><a href="' . admin_url('options-general.php?page=import-gc-data&action=download_csv&part=1') . '" id="export-gc-redemptions-csv">Export Gift Cards with Redemptions Part 1</a></div>';
        echo '<div style="margin-bottom: 20px;"><a href="' . admin_url('options-general.php?page=import-gc-data&action=download_csv&part=2') . '" id="export-gc-redemptions-csv">Export Gift Cards with Redemptions Part 2</a></div>';
        echo '<div style="margin-bottom: 20px;"><a href="' . admin_url('options-general.php?page=import-gc-data&action=download_csv&part=3') . '" id="export-gc-redemptions-csv">Export Gift Cards with Redemptions Part 3</a></div>';
        echo '<div style="margin-bottom: 20px;"><a href="' . admin_url('options-general.php?page=import-gc-data&action=download_csv&part=4') . '" id="export-gc-redemptions-csv">Export Gift Cards with Redemptions Part 4</a></div>';
        echo '<div style="margin-bottom: 20px;"><a href="' . admin_url('options-general.php?page=import-gc-data&action=download_csv&part=5') . '" id="export-gc-redemptions-csv">Export Gift Cards with Redemptions Part 5</a></div>';
        echo '<div style="margin-bottom: 20px;"><a href="' . admin_url('options-general.php?page=import-gc-data&action=download_csv&part=6') . '" id="export-gc-redemptions-csv">Export Gift Cards with Redemptions Part 6</a></div>';

        ?>



        <script type="text/javascript">
            jQuery(function($) {

                $('document').ready(function() {
                    $('#import-gc-balances1').on('click', function() {
                        update_gc_amounts(1);
                    });
                    $('#import-gc-balances2').on('click', function() {
                        update_gc_amounts(2);
                    });
                    $('#import-gc-balances3').on('click', function() {
                        update_gc_amounts(3);
                    });
                    $('#import-gc-totals1').on('click', function() {
                        update_gc_totals(1);
                    });
                    $('#import-gc-totals2').on('click', function() {
                        update_gc_totals(2);
                    });

                });
                function update_gc_amounts(csv_number) {
                    $('#import-gc-balances-result' + csv_number).html('Importing...');

                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
                        data: {
                            'action': 'import_gc_amounts',
                            'csv_number': csv_number
                        },
                        success: function(data) {
                            console.log(data);
                            $('#import-gc-balances-result' + csv_number).html(data.updated_post_ids.join(","));

                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.log(jqXHR + ' :: ' + textStatus + ' :: ' + errorThrown);
                        }
                    });

                }
                function update_gc_totals(csv_number) {
                    $('#import-gc-totals-result' + csv_number).html('Importing...');

                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
                        data: {
                            'action': 'import_gc_totals',
                            'csv_number': csv_number
                        },
                        success: function(data) {
                            console.log(data);
                            $('#import-gc-totals-result' + csv_number).html(data.updated_post_ids.join(","));

                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.log(jqXHR + ' :: ' + textStatus + ' :: ' + errorThrown);
                        }
                    });

                }
            });
        </script>
        <?php

    }

    /**
     * Import the GC amounts - this is the purchase amount of the card
     */
    public function import_gc_amounts_ajax() {

        $updated_post_ids = array();

        $gc_amounts = $this->read_csv('gc-amounts' . $_POST['csv_number'] . '.csv');

        foreach($gc_amounts as $gc_amount) {

            // ignore the first row
            if($gc_amount[0] != 'post_id') {

                update_post_meta( $gc_amount[0], '_ywgc_amount_total', round($gc_amount[2],2));
                $updated_post_ids[] = $gc_amount[0];
            }
        }

        $return_arr = array(
            'updated_post_ids' => $updated_post_ids,
        );

        wp_send_json($return_arr);

    }

    /**
     * Import the GC totals
     * Originally used the info from the CSV, but now we're just using the post IDs
     * and adding up the redemptions in the database
     */
    public function import_gc_totals_ajax() {

        $gc_totals = $this->read_csv('gc-totals' . $_POST['csv_number'] . '.csv');

        $updated_post_ids = array();
        foreach($gc_totals as $gc_total) {

            if($gc_total[0] != 'post_id') {

                // set the amount from the CSV as the balance
                $gc_total_balance = round($gc_total[2], 2);

                // see if there were already recorded redemptions and potentially use that calculated
                // amount instead of the amount in the CSV.
                $redemptions = get_field( 'gc_redemption_information', $gc_total[0]); 

                if(is_array($redemptions)) {
                    $total_amt_redeemed = 0;

                    foreach($redemptions as $redemption) {
                        if(is_numeric(round($redemption['_expgc_redeem_amt'], 2))) {
                            $total_amt_redeemed += round($redemption['_expgc_redeem_amt'], 2);
                        }
                    }
    
                    // get the original amount
                    $gc_original_value = get_post_meta( $gc_total[0], '_ywgc_amount_total', true);
    
                    $calculated_gc_balance = round($gc_original_value - $total_amt_redeemed, 2);
                    
                    if($calculated_gc_balance != $gc_total_balance) {
                        $gc_total_balance = $calculated_gc_balance;
                        $this->wl('Found a different calculation');
                        $this->wl($gc_total[0]);
                        $this->wl($gc_total_balance);
                        $this->wl($calculated_gc_balance);
                    }
                }

                update_post_meta( $gc_total[0], $gc_total[1], $gc_total_balance);

                $updated_post_ids[] = $gc_total[0];
            }
        }

        $return_arr = array(
            'updated_post_ids' => $updated_post_ids,
        );

        wp_send_json($return_arr);

        
    }

    /**
     * Download link
     */
    public function gc_download_redemptions() {
        if ( isset($_GET['action'] ) && $_GET['action'] == 'download_csv' )  {
            $this->export_gc_redemptions_to_csv($_GET['part']);
        }
    }
    /**
     * Export the required info as a CSV
     */

    public function export_gc_redemptions_to_csv($part) {

        // Check for current user privileges 
        if( !current_user_can( 'manage_options' ) ){ return false; }

        // Check if we are in WP-Admin
        if( !is_admin() ){ return false; }

        // Nonce Check
        // $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
        // if ( ! wp_verify_nonce( $nonce, 'download_csv' ) ) {
        //     die( 'Security check error' );
        // }
        $gc_query = new \WP_Query(
            array(
                'post_type'      => 'gift_card',
                'post_status' => 'publish',
                'paged' => $part,
                'posts_per_page' => 6500,
                'orderby' => 'ID',
                'fields' => 'ids',

                // 'post__in'      => array(136774,223820,136843, 118131)
            )
        );

        $gc_redemptions = array();
        if($gc_query->have_posts()) : 

            // while ( $gc_query->have_posts() ) : $gc_query->the_post(); 
            foreach($gc_query->posts as $post_id) {
                $redemptions = get_field( 'gc_redemption_information', $post_id); 
                foreach($redemptions as $redemption) {
                    if($redemption['_expgc_redeem_amt'] != '' && $redemption['_expgc_redeem_date'] != '') {
                        $gc_redemptions[] = array(
                            'gift_card_id' => $post_id,
                            'date' => $redemption['_expgc_redeem_date'],
                            'amount' => $redemption['_expgc_redeem_amt'],
                            'inn_id' => $redemption['_expgc_redeem_inn_id'],
                            'inn_name' => $redemption['_expgc_redeem_inn_name'],
                            'payment_type' => $redemption['_expgc_payment_type'],
                            'account_number' => $redemption['_expgc_account_number']
                        );
                    }
                    
                }
            }

        endif; // end of the loop. 
        
        ob_start();

        $filename = 'bnbfinder-gc-redemptions-part-' . $part . '-' . time() . '.csv';
        
        $header_row = array(
            'Gift Card ID',
            'Redeem Date',
            'Amount Redeemed',
            'Redeeming Inn Id',
            'Redeeming Inn Name',
            'Payment Type',
            'Account Number'
        );
        $data_rows = array();
        global $wpdb;
        $sql = 'SELECT * FROM ' . $wpdb->users;
        $users = $wpdb->get_results( $sql, 'ARRAY_A' );
        foreach ( $gc_redemptions as $gc_redemption ) {
            $row = array(
                $gc_redemption['gift_card_id'],
                $gc_redemption['date'],
                $gc_redemption['amount'],
                $gc_redemption['inn_id'],
                $gc_redemption['inn_name'],
                $gc_redemption['payment_type'],
                $gc_redemption['account_number']
            );
            $data_rows[] = $row;
        }
        ob_end_clean();
        $fh = @fopen( 'php://output', 'w' );
        fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-type: text/csv' );
        header( "Content-Disposition: attachment; filename={$filename}" );
        header( 'Expires: 0' );
        header( 'Pragma: public' );
        fputcsv( $fh, $header_row );
        foreach ( $data_rows as $data_row ) {
            fputcsv( $fh, $data_row );
        }
        fclose( $fh );
        
        ob_end_flush();
        
        exit();
    }

    private function read_csv($file_handle, $row = 0) {
        $csv_array = array();
      
        if (($handle = fopen(dirname(__DIR__) . '/bnbfinder-gc-import/' . $file_handle, 'r')) !== FALSE) { // Check the resource is valid
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) { // Check opening the file is OK!
                $num = count($data);

                for ($c=0; $c < $num; $c++) {
                    $csv_array[$row][$c] = $data[$c];
                }

                $row++;

            }
            fclose($handle);
        }
        return $csv_array;
    }
        // Logging function 
        private  function wl ( $log )  {
            if ( true === WP_DEBUG ) {
                if ( is_array( $log ) || is_object( $log ) ) {
                    error_log( print_r( $log, true ) );
                } else {
                    error_log( $log );
                }
            }
        } // end private function wl 
} // end class BNBGiftCards

$bnb_gift_cards = new BNBGiftCards();


// export functions 

function bnbf_get_gc_order_id($gc_id) {
    return get_post_meta( $gc_id, '_ywgc_order_id', true ); 
}
function bnbf_get_gc_sender_name($gc_id) {
    return bnbf_get_post_meta_or_wc_item_meta($gc_id, '_ywgc_sender_name');
}
function bnbf_get_gc_recipient_name($gc_id) {
    return bnbf_get_post_meta_or_wc_item_meta($gc_id, '_ywgc_recipient_name');
}
function bnbf_get_gc_message($gc_id) {
    return bnbf_get_post_meta_or_wc_item_meta($gc_id, '_ywgc_message');
}

function bnbf_get_post_meta_or_wc_item_meta($gc_id, $meta_key) {
    $is_digital = get_post_meta( $gc_id, '_ywgc_is_digital', true );
    if($is_digital) {
        return get_post_meta( $gc_id, $meta_key, true );
    } else {
        // get the order id
        $order_id = get_post_meta( $gc_id, '_ywgc_order_id', true ); 
        if($order_id != '') {
            // Getting an instance of the WC_Order object from a defined ORDER ID
            $order = wc_get_order( $order_id ); 

            // Iterating through each "line" items in the order      
            foreach ($order->get_items() as $item_id => $item ) {
                // check if gc codes
                $wc_meta_gc_ids = ywgc_get_order_item_giftcards($item_id);
                if(in_array($gc_id, $wc_meta_gc_ids)) {
                    return $sender_name = $item->get_meta($meta_key);
                }
            }
        }
    }
    return false;
}

function get_giftcard_codes_from_wc_item_id($item_id) {
    $gift_ids = ywgc_get_order_item_giftcards ( $item_id );
    if ( empty( $gift_ids ) ) {
        return;
    }
    $codes = array();
    foreach ( $gift_ids as $gift_id ) {

        $gc = new \YWGC_Gift_Card_Premium( array( 'ID' => $gift_id ) );
        $codes[] = $gc->get_code ();
       
    }
    return implode( ',', $codes );
}

function get_giftcard_post_ids_from_wc_item_id($item_id) {
    $gift_ids = ywgc_get_order_item_giftcards ( $item_id );
    if ( empty( $gift_ids ) ) {
        return;
    }

    return implode( ',', $gift_ids );
}
