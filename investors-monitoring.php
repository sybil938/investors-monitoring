<?php
/*
Plugin Name: Investors Monitoring
Description: Investors Monitoring
Author: Caroline Torres
Version: 0.1
*/

if(!defined('ABSPATH')) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

// INCLUDE FILES --------
include('shortcode.php');


global $pwv_investors;
$pwv_investors = new pwv_investors();

class pwv_investors {

	public function __construct() {
		register_activation_hook( __FILE__, [ $this, '_install' ] );
		add_action( 'admin_menu', [ $this, '_menu' ] );
		add_action( 'admin_head', [ $this, 'bootstrap_styles' ]);
    	add_action( 'wp_ajax_change_item', [ $this, 'change_item'] );
    	add_action( 'wp_ajax_nopriv_change_item', [ $this, 'change_item'] );
    	add_action( 'wp_ajax_remove_item', [ $this, 'remove_item'] );
    	add_action( 'wp_ajax_nopriv_remove_item', [ $this, 'remove_item'] );
    	add_action( 'wp_ajax_search_string', [ $this, 'search_string'] );
    	add_action( 'wp_ajax_nopriv_search_string', [ $this, 'search_string'] );
    	add_action('admin_init', [ $this, 'download_csv']);
	}

	//CSV EXPORT
	function download_csv() {

	  if (isset($_POST['download_csv'])) {

	    global $wpdb;
	    $table = $wpdb->prefix . "pwv_investors";
	    $sql   = "SELECT * FROM $table";
	    $rows  = $wpdb->get_results($sql, 'ARRAY_A');
	    $date  = date("m-d-Y");

	    if ($rows) {

	        $csv_fields  = array();
	        $csv_fields[] = "first_column";
	        $csv_fields[] = 'second_column';

	        $output_filename = 'pwv-investors-monitoring[' . $date . '].csv';
	        $output_handle = @fopen('php://output', 'w');

	        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	        header('Content-Description: File Transfer');
	        header('Content-type: text/csv');
	        header('Content-Disposition: attachment; filename=' . 
	        $output_filename);
	        header('Expires: 0');
	        header('Pragma: public');

	        $first = true;
	       // Parse results to csv format
	        foreach ($rows as $row) {

	       // Add table headers
	            if ($first) {

	                $titles = array();

	                foreach ($row as $key => $val) {
	                    $titles[] = $key;
	                }

	                fputcsv($output_handle, $titles);

	                $first = false;
	            }

	            $leadArray = (array) $row; // Cast the Object to an array
	            // Add row to file
	            fputcsv($output_handle, $leadArray);
	        }

	        //echo '<a href="'.$output_handle.'">test</a>';

	        // Close output file stream
	        fclose($output_handle);

	        die();
	    }
	  }
	}


	function _install() {
		global $wpdb;
		$table = $wpdb->prefix . 'pwv_investors';

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql =  "CREATE TABLE $table (
			id mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
			customer_name varchar(255) NOT NULL,
			customer_email varchar(255) NOT NULL,
			pwv_quantity varchar(255) NOT NULL,
			wallet_id varchar(255) NOT NULL,
			liquid_holdings TEXT NOT NULL,
			status varchar(255) DEFAULT 'Not Done' NOT NULL,					
			PRIMARY KEY (id)
		)";

		dbDelta( $sql );
	}  

	function _menu() {
		add_menu_page( 
			'Investors Monitoring', 
			'Investors Monitoring', 
			'manage_options', 
			'investors-monitoring', 
			[ $this,'pwv_investors_init'], 
			'dashicons-groups', 
			7
		); 
	}

	function change_item() {	

	    global $wpdb;
	    $table = $wpdb->prefix . "pwv_investors";

	    $status = $_POST["status"];
	    $id     = $_POST["id"];

	    $wpdb->update(
	            $table,
	            array( "status" => $status ),
	            array( "id"     => $id )
	    	);  
	}

	function remove_item() {
	    global $wpdb;
	    $table = $wpdb->prefix . "pwv_investors";
	    $id    = $_POST["id"];

	   	$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
	}

	function search_string() {

		global $wpdb;
		$table 		 = $wpdb->prefix . "pwv_investors";
		$search_data = $_POST['search_string'];
		$target_path = content_url() .'/uploads/ppl/';

		$results = $wpdb->get_results( 
			"
			SELECT *
			FROM wp_pwv_investors
			WHERE customer_name LIKE '%$search_data%' 
			OR customer_email   LIKE '%$search_data%'			
			"
		);

		echo "<table class='table table-striped'>
			    <tbody>
			    	<thead>
				      <tr>
				        <th>No.</th>
				        <th>Name</th>
				        <th>Email</th>
				        <th>PWV Quantity</th>
				        <th>Wallet ID</th>
				        <th>Liquid Screenshot</th>
				        <th>Status</th>
				        <th>&nbsp;</th>
				      </tr>		
				    </thead> ";
		
		foreach ($results as $data) {

			if(!empty($data->liquid_holdings)) {
						
				$filetype = wp_check_filetype($data->liquid_holdings);

				if( $filetype['ext'] == 'pdf' ) {

					$liquid_holdings = '<a target="_blank" href="'. $target_path . $data->liquid_holdings .'" target="_blank">
						    		<small>Click to open PDF file.</small>
						        </a>';
				} else {

					$liquid_holdings = '<a target="_blank" href="'. $target_path . $data->liquid_holdings .'" class="thickbox">
						    	<img src="'. $target_path . $data->liquid_holdings .'" style="max-width:100px;"></div>
						        </a>';
				}
	    
			} else {

				$liquid_holdings = '<small>--</small>';

			}


			echo "<tr>";
			echo "<td>$data->id</td>";
			echo "<td>$data->customer_name</td>";
			echo "<td>$data->customer_email</td>";
			echo '<td>'.number_format($data->pwv_quantity).'</td>';
			echo "<td>$data->wallet_id</td>";
			echo "<td class='text-center'>$liquid_holdings</td>";
			echo '<td>
					<select class="form-control" name="status" onchange="ajaxFunction(this.value, '.$data->id.')"> 	 		
						<option value='.$data->status.'>'.$data->status.'</option>
						<option value="Yes">Yes</option>
						<option value="No">No</option>
						<option value="Not Done">Not Done</option>
				  	</select>										
				   </td>';
			echo '<td><button type="button" class="btn btn-remove" onclick="ajaxRemove('.$data->id.')">remove</button></td>';
			echo "</tr>";	   
		}		
		echo "</tbody>";
		echo "</table>";
		exit;
	}



	function pwv_investors_init() {

		$script  = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>';
		$script .= '<script>

						function ajaxShowHide() {
						    $("#tablePWV tr").toggleClass("show");
						}	

						function ajaxFunction(status,id) {
							var id     = id;
							var status = status;
							var queryString = "&id="+id+"&status="+status;
							var data = {
						        "action" : "change_item",
						        "status" : status,
						        "id"     : id 
						    };

						    jQuery.post(ajaxurl, data, function(response) {
						        alert("Success! Status has been updated.");
						    })
						}

						function ajaxRemove(id) {
							var id = id;
							var queryString = "&id="+id;
							var data = {
								"action" : "remove_item",
								"id"	 : id
							}

						    jQuery.post(ajaxurl, data, function(response) {
						        window.location.reload();
						    })							    					
						}

						function ajaxSearch(search_string) {

							var search_string = search_string;							
							var keycode       = (event.keyCode ? event.keyCode : event.which);

							if(event.keyCode === 13) {	

								var queryString = "&search_string="+search_string;
								var data = {
									"action" : "search_string",
									"search_string" : search_string
								}			

							    jQuery.post(ajaxurl, data, function(response) {
							        if( data => -1) {
										jQuery("#results").html(response);
										jQuery(".default").hide();
							        } 			
							        		 				        
							    })			
							
							}
						}
					</script>';

		echo $script;

		global $wpdb;		
        $target_path    = content_url() .'/uploads/ppl/';
		$table_name     = $wpdb->prefix . "pwv_investors";
		$items_per_page = 20;
		$page           = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
		$offset         = ( $page * $items_per_page ) - $items_per_page;
		$query          = 'SELECT * FROM '.$table_name;
		$total_query    = "SELECT COUNT(1) FROM (${query}) AS combined_table";
		$total          = $wpdb->get_var( $total_query );
		$results        = $wpdb->get_results( $query.' ORDER BY id ASC LIMIT '. $offset.', '. $items_per_page, OBJECT );	
		$num            = 0;


		$dashboard  = ' <div class="container-fluid pt-3 pr-5 pb-5">
							<div class="row">
								<div class="col-md-8">
									<h3 class="mb-3">Investors Table</h3>																	
								</div>				
								<div class="col-md-4">
									<input id="hideShow" class="btn button-primary mt-2 float-right" type="button" value="Hide/Show Duplicates" onclick="ajaxShowHide()" />
								</div>
								<div class="col-md-12"><hr></div>				
							</div>	
							<div class="row">
								<div class="col-md-8">									
									<form method="post" id="download_form" action="">
										<input type="submit" name="download_csv" class="btn button-primary mb-3" value="Export to CSV" />
									</form>
								</div>	
								<div class="col-md-4">									    
					            	<input type="text" class="form-control mb-3" placeholder="search" name="search_string" onkeypress="ajaxSearch(this.value)" >
								</div>								
							</div>
							<div class="row">
								<div class="col-md-12">
									<div id="results"></div>
								</div>	
								<div class="col-md-12 default">
									<table id="tablePWV" class="table table-striped">
									    <tbody>
									    	<thead>
										      <tr>
										        <th>No.</th>
										        <th>Name</th>
										        <th>Email</th>
										        <th>PWV Quantity</th>
										        <th>Wallet ID</th>
										        <th>Liquid Screenshot</th>
										        <th>Status</th>
										        <th>&nbsp;</th>
										      </tr>		
										    </thead> 							      
						';				      

		foreach ($results as $key) { 


			if(!empty($key->liquid_holdings)) {
						
				$filetype = wp_check_filetype($key->liquid_holdings);

				if( $filetype['ext'] == 'pdf' ) {

					$liquid_holdings = '<a target="_blank" href="'. $target_path . $key->liquid_holdings .'" target="_blank">
						    		<small>Click to open PDF file.</small>
						        </a>';
				} else {

					$liquid_holdings = '<a target="_blank" href="'. $target_path . $key->liquid_holdings .'" class="thickbox">
						    	<img src="'. $target_path . $key->liquid_holdings .'" style="max-width:100px;"></div>
						        </a>';
				}
	    
			} else {

				$liquid_holdings = '<small>--</small>';

			}


			$dashboard .= 	'	<tr class="'.$key->status.'">
								<td>'.++$num.'</td>
								<td>'.$key->customer_name.'</td>
								<td>'.$key->customer_email.'</td>
								<td>'.number_format($key->pwv_quantity).'</td>
								<td>'.$key->wallet_id.'</td>
								<td class="text-center">'.$liquid_holdings.'</td>
								<td>								
								<select class="form-control" name="status" onchange="ajaxFunction(this.value, '.$key->id.')"> 	 		
									<option value='.$key->status.'>'.$key->status.'</option>
									<option value="Yes">Yes</option>
									<option value="No">No</option>
									<option value="Not Done">Not Done</option>
									<option value="Duplicate">Duplicate</option>
								</select>																	
								</td>
								<td><button type="button" class="btn btn-remove" onclick="ajaxRemove('.$key->id.')">remove</button></td>
							<tr>	
						'; 	
		}	

		$dashboard .=   '<tr>
							<td colspan="8">
								<div class="paginate">
								'.paginate_links( 
									array(
					                    'base' 		=> add_query_arg( 'cpage', '%#%' ),
					                    'format' 	=> '',
					                    'prev_text' => __('&laquo;'),
					                    'next_text' => __('&raquo;'),
					                    'total' 	=> ceil($total / $items_per_page),
					                    'current' 	=> $page
					                )).'
								</div>
							</td>
						</tr>
						';           
		$dashboard .= ' 				</tbody>
									</table>	
								</div>	
							</div>
						</div>	
						<style type="text/css">
							.btn-remove { height: auto; padding: 1px 10px; }	
							.paginate { float: right; }
							select { width: 100px !important; }		
							.hide { display: none; }		
							.screen-reader-text { display: none; }
							.tb-close-icon { color: #fff; }
							#TB_closeWindowButton { top: -35px; }	
							#form#download_form input[type="submit"] { line-height: 0; }
							.Duplicate { display: none; }
							.show { display: table-row !important; }
						</style>
						';
		echo $dashboard; 
	
	}


	function bootstrap_styles()  {
		echo '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">';
	}

}




