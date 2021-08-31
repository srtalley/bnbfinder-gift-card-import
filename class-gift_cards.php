<?php
/*
Plugin Name: bnbfinder Gift Card Import
Description: Imports the totals and balances for the Gift Cards
Author: Maia Consulting
Author URI: https://www.maiainternetconsulting.com/
Version: 1.1.3
Text Domain: bnbfinder
License: GPLv2
*/

class BNBGiftCards {

    public function __construct() {
        add_action( 'admin_menu', array($this, 'gc_import_data_menu') );
        add_action( 'wp_ajax_import_gc_amounts', array($this, 'import_gc_amounts_ajax') );
        add_action( 'wp_ajax_import_gc_totals', array($this, 'import_gc_totals_ajax') );

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
        echo '<div><button id="import-gc-totals">Import Gift Card Totals</button><div id="import-gc-totals-result"></div></div>';

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
                    $('#import-gc-totals').on('click', function() {
                        update_gc_totals();
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
                function update_gc_totals(object) {
                    $('#import-gc-totals-result').html('Importing...');

                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
                        data: {
                            'action': 'import_gc_totals',
                        },
                        success: function(data) {
                            console.log(data);
                            $('#import-gc-totals-result').html(data.updated_post_ids.join(","));

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

        $gc_totals = $this->read_csv('gc-totals.csv');

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
