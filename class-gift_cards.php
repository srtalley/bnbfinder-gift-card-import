<?php
/*
Plugin Name: bnbfinder Gift Card Import
Description: Imports the totals and balances for the Gift Cards
Author: Maia Consulting
Author URI: https://www.maiainternetconsulting.com/
Version: 1.0
Text Domain: bnbfinder
License: GPLv2
*/

class BNBGiftCards {

    public function __construct() {
        add_action( 'admin_menu', array($this, 'gc_import_data_menu') );
        add_action( 'wp_ajax_import_gc_balances', array($this, 'import_gc_balances_ajax') );
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
        echo '<div style="margin-bottom: 20px;"><button id="import-gc-balances">Import Gift Card Balances</button><div id="import-gc-balances-result"></div></div>';
        echo '<div><button id="import-gc-totals">Import Gift Card Totals</button><div id="import-gc-totals-result"></div></div>';

        ?>



        <script type="text/javascript">
            jQuery(function($) {

                $('document').ready(function() {
                    $('#import-gc-balances').on('click', function() {
                        update_gc_balances();
                    });
                    $('#import-gc-totals').on('click', function() {
                        update_gc_totals();
                    });
                });
                function update_gc_balances(object) {
                    $('#import-gc-balances-result').html('Importing...');

                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
                        data: {
                            'action': 'import_gc_balances',
                        },
                        success: function(data) {
                            console.log(data);
                            $('#import-gc-balances-result').html(data.updated_post_ids.join(","));

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
     * Import the GC balances
     */
    public function import_gc_balances_ajax() {
        $gc_balances = $this->read_csv('gc-balances.csv');

        $updated_post_ids = array();
        foreach($gc_balances as $gc_balance) {

            if($gc_balance[0] != 'post_id') {
                update_post_meta( $gc_balance[0], $gc_balance[1], $gc_balance[2]);
                $updated_post_ids[] = $gc_balance[0];
            }
        }

        $return_arr = array(
            'updated_post_ids' => $updated_post_ids,
        );

        wp_send_json($return_arr);

    }

    /**
     * Import the GC totals
     */
    public function import_gc_totals_ajax() {
        $gc_totals = $this->read_csv('gc-totals.csv');

        $updated_post_ids = array();
        foreach($gc_totals as $gc_total) {

            if($gc_total[0] != 'post_id') {
                update_post_meta( $gc_total[0], $gc_total[1], $gc_total[2]);
                $updated_post_ids[] = $gc_total[0];
            }
        }

        $return_arr = array(
            'updated_post_ids' => $updated_post_ids,
        );

        wp_send_json($return_arr);

        
    }


    private function read_csv($file_handle) {
        $csv_array = array();
        $row = 0;
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
