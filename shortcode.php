<?php
if(!defined('ABSPATH')) exit;


function form() {
	if ( isset( $_POST['submitform'] ) ){

		global $wpdb;		
		$tablename = $wpdb->prefix . 'pwv_investors';
		$tablekyc  = $wpdb->prefix . 'kyc';


		$wallet_exists   = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tablename WHERE wallet_id = %s", $_POST['wallet_id'] ) );
		$email_exists    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tablekyc WHERE email = %s", $_POST['customer_email'] ) );
		$customer_name   = $_POST['customer_name'];
		$customer_email  = $_POST['customer_email'];
		$wallet_id       = $_POST['wallet_id'];
		$compare         = strncmp("$wallet_id","0x",2);
		$liquid_holdings = $_POST['proof_liquid_holding'];

	
		if (!empty ($_POST['wallet_id']) ) {
			
			if ( $compare == 0 ) {

				if ( ! $wallet_exists ) {

					if (! $email_exists ) {

						$data = array( 
							'customer_name'     => $_POST['customer_name'], 
							'customer_email'    => $_POST['customer_email'],
							'pwv_quantity'      => $_POST['pwv_quantity'],
							'wallet_id'	        => $_POST['wallet_id'],
							'liquid_holdings'   => '',
						);

						$format = array('%s','%s','%s','%s','%s');
						$wpdb->insert( $tablename, $data, $format);

						session_start();
						$_SESSION["name"]  = $customer_name;
						$_SESSION["email"] = $customer_email;

						//header("Location: https://peoplewaveico.io/ppl-kyc");
						wp_redirect( 'https://peoplewaveico.io/ppl-kyc' );

					} else {

						$data = array( 
							'customer_name'     => $_POST['customer_name'], 
							'customer_email'    => $_POST['customer_email'],
							'pwv_quantity'      => $_POST['pwv_quantity'],
							'wallet_id'	        => $_POST['wallet_id'],
							'liquid_holdings'   => '',
						);

						$format = array('%s','%s','%s','%s','%s');
						$wpdb->insert( $tablename, $data, $format);

						echo "<div class='alert alert-success'><strong>Details has been sent successfully!</strong></div>";	
						wp_redirect('https://peoplewaveico.io/?page_id=981');								
								
					}	

				} else {	

					echo "<div class='alert alert-danger'><strong>Wallet ID duplicate!</strong></div>";		

				}		

			} else {

				echo "<div class='alert alert-danger'><strong>Please enter a correct Wallet ID! First two characters should be 0x</small></div>";	

			}		

		}  elseif (!empty ($_POST['proof_liquid_holding']) ) {

			if (! $email_exists ) {

				$uploads     = wp_upload_dir();
        		$target_path = $uploads['basedir'].'/ppl/'; 
        		$filename    = str_replace(' ','_',$customer_name) .'-'. $_FILES['liquid_holding']['name'];

				$source      = $_FILES['liquid_holding']['tmp_name'];
				$destination = $target_path . $filename;
				move_uploaded_file( $source, $destination );

				$data = array( 
					'customer_name'     => $_POST['customer_name'], 
					'customer_email'    => $_POST['customer_email'],
					'pwv_quantity'      => $_POST['pwv_quantity'],
					'wallet_id'	        => '',
					'liquid_holdings'   => str_replace(' ','_',$customer_name) .'-'. $_POST['proof_liquid_holding'],
				);

				$format = array('%s','%s','%s','%s','%s');
				$wpdb->insert( $tablename, $data, $format);

				session_start();
				$_SESSION["name"]  = $customer_name;
				$_SESSION["email"] = $customer_email;

				//header("Location: https://peoplewaveico.io/ppl-kyc");
				wp_redirect( 'https://peoplewaveico.io/ppl-kyc' );					

			} else {

				$uploads     = wp_upload_dir();
        		$target_path = $uploads['basedir'].'/ppl/'; 
        		$filename    = str_replace(' ','_',$customer_name) .'-'. $_FILES['liquid_holding']['name'];

				$source      = $_FILES['liquid_holding']['tmp_name'];
				$destination = $target_path . $filename;
				move_uploaded_file( $source, $destination );

				$data = array( 
					'customer_name'     => $_POST['customer_name'], 
					'customer_email'    => $_POST['customer_email'],
					'pwv_quantity'      => $_POST['pwv_quantity'],
					'wallet_id'	        => '',
					'liquid_holdings'   => str_replace(' ','_',$customer_name) .'-'. $_POST['proof_liquid_holding'],
				);

				$format = array('%s','%s','%s','%s','%s');
				$wpdb->insert( $tablename, $data, $format);

				echo "<div class='alert alert-success'><strong>Details has been sent successfully!</strong></div>";	
				wp_redirect('https://peoplewaveico.io/?page_id=981');		

			}

		}	
	
	}		


	$form  = '<div class="row">
				<div class="col-md-12">
					<form id="pwv-form" action="" method="post" enctype="multipart/form-data">					
						<div class="form-group">
							<label for="customer_name">Name</label>
							<input type="text" class="form-control" name="customer_name" value="'.$_POST['customer_name'].'" required>
						</div>
						<div class="form-group">
							<label for="customer_email">Email</label>
							<input type="email" class="form-control" name="customer_email"  pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" value="'.$_POST['customer_email'].'" required>
							<small>Used to communicate with you for the PWV to PPL transfer.</small>
						</div>	
						<div class="form-group">
							<label for="pwv_quantity">PWV Quantity</label>
							<input type="number" class="form-control" name="pwv_quantity" min="1"  value="'.$_POST['pwv_quantity'].'" required>
							<small>Please enter the your current PWV total holding.</small>
						</div>									
						<div class="form-group">
							<label for="wallet_id">Wallet ID</label>
							<input type="text" class="form-control" name="wallet_id" maxlength="255"  value="'.$_POST['wallet_id'].'" required>
							<small>Please enter the Wallet ID that holds your current PWV for validation purposes and to receive your PPL.</small>
						</div>	
					    <div class="form-group uploadfilecontainer liquid-screenshot">
					        <div class="row pt-4">
					            <div class="col-md-12">
					                <label><input type="radio" name="lhp" id="radioBtn" onclick="proof(this)" class="ppllabel" />&nbsp; Existing PWV on Quoine/Liquid</label><br>
					            </div>
					            <div class="col-md-12">                                    
					                <div class="lqp-display">					                
					                    <label for="liquid_holding" class="ppllabel">Please upload a screenshot of your Quoine/Liquid holding.</label>
					                    <input id="proof_liquid_holding" readonly name="proof_liquid_holding" type="text" class="fileinputtext" size="100">
					                    <label id="liquid_holding_label" for="liquid_holding" class="filecontainer pull-right">Browse</label>
					                    <input type="file" class="form-control-file" id="liquid_holding" name="liquid_holding"> 
					                </div> 
					            </div>                                
					            <div class="col-md-12">					            	
					                <div class="lqp-display">					 
					                	<p>Sample Quoine /Liquid PWV Holding Screenshot</p>                	
					                 	<a target="_blank" href="'. plugins_url() .'/pwv-investors-monitoring/images/liquid-screenshot.png" class="thickbox">
					                 		<img src="'. plugins_url() .'/pwv-investors-monitoring/images/liquid-screenshot.png">
					                 	</a>
					                </div>
					            </div>                               
					        </div>
					    </div>						
						<button type="submit" class="btn mt-3 float-right btn-md" name="submitform">Submit</button>
					</form>
				</div>	
			</div>
			<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
			<style type="text/css">
				#pwv-form label  { font-weight: 600; }
				#pwv-form button { background-color: #424242; color: #fff; padding: 0 30px; letter-spacing: 2px; }
				#pwv-form button:hover { background-color: #616161; }
				#pwv-form input[type=number]::-webkit-inner-spin-button, 
				#pwv-forminput[type=number]::-webkit-outer-spin-button { 
				  -webkit-appearance: none; 
				  margin: 0; 
				}	
				#pwv-form small { color: #9e9e9e; display: block; line-height: 1.5; }
				#pwv-form .fileinputtext,
				#pwv-form .fileinputtext:hover,
				#pwv-form .fileinputtext:focus,
				#pwv-form .fileinputtext:active {
				    border:1 #ccc;
				    background:#fff !important;
				    width: 100% !important;
				    margin-right:20px !important;
				    display:inline-block !important;
				}

				.screen-reader-text { display: none; }
				.tb-close-icon { color: #fff; }
				#TB_closeWindowButton { top: -35px; }
				.lqp-display p { color: #fff; }
			</style>
			<script type="text/javascript">
			    jQuery(".lqp-display").hide();
			    var radioState = false;

			    function proof(element){                           

			        if(radioState == false) {
			            check();
			            radioState = true;
			            jQuery(".lqp-display").show();
			        }else{
			            uncheck();
			            radioState = false;
			            jQuery(".lqp-display").hide();
			        }
			    }

			    function check() {
			        document.getElementById("radioBtn").checked = true;  
			        jQuery("input[name=wallet_id]").prop("disabled", true).attr("required", false);   
			        jQuery("input[name=liquid_holding]").attr("required", true);
			    }

			    function uncheck() {
			        document.getElementById("radioBtn").checked = false;   
			        jQuery("input[name=wallet_id]").prop("disabled", false).attr("required", true); 
			        jQuery("input[name=liquid_holding]").attr("required", false);
			    }


				jQuery("#liquid_holding_label").on("click", function(e) {
					e.preventDefault();
					jQuery("#liquid_holding").click();
				});	
				
				jQuery("#liquid_holding").on("change", function(){
					jQuery("#proof_liquid_holding").val(document.getElementById("liquid_holding").files[0].name);
				});


			</script>
	';

	return $form;
}
add_shortcode( 'form-pwv-monitoring', 'form' );
