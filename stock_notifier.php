<?php
/*
Plugin Name: WooCommerce Stock Notifier
Plugin URI:
Description: A plugin email Notifier when product out of stock.
Version: 1
Author: Ravi P
Author URI: 
*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) || class_exists( 'WooCommerce' )) {


class RPD_Stock_Notifier {
/**
	* __construct
	* class constructor will set the needed filter and action hooks
	*/
	public function __construct(){
		if (is_admin()){
			//CSS
			add_action( 'admin_init', array($this,'load_custom_css_scripts'));
			//JS
			add_action( 'admin_enqueue_scripts',array($this,'load_custom_js_scripts'));
			//add settings tab
			 add_filter( 'woocommerce_settings_tabs_array', array($this,'woocommerce_settings_tabs_array'), 51 );
			//show settings tab
            add_action( 'woocommerce_settings_tabs_rpd', array($this,'show_settings_tab' ));
			//save settings tab
            add_action( 'woocommerce_update_options_rpd', array($this,'update_settings_tab' ));
			//table
			add_action('woocommerce_settings_tabs_rpd',  array($this,'show_table_records' ), 52 );
			// Ajax request handling.
			add_action('wp_ajax_mail_send_to', array($this,'mail_send_to' ));
			add_action('wp_ajax_nopriv_mail_send_to',array($this,'mail_send_to' ));
			// Ajax delete.
			add_action('wp_ajax_delete_record', array($this,'delete_record' ));
			add_action('wp_ajax_nopriv_delete_record',array($this,'delete_record' ));
			
		}	
		add_action( 'init',array($this,'load_front_custom_js'));
		add_filter('woocommerce_stock_html',array(__CLASS__, 'woocommerce_stock_html_mod'), 10, 1);		
	}
	
	
	function load_front_custom_js(){
		wp_register_script('jquery.1.12.0', 'https://code.jquery.com/jquery-1.12.0.min.js', false, '1.12.0',true);
		wp_enqueue_script('jquery.1.12.0');
		wp_register_script('rpd_front', plugins_url('/assets/js/rpd_front.js', __FILE__ ),false,'1.0',true);
		wp_enqueue_script('rpd_front');
	}
	
/**
	 * Load Style CSS
	 * Used to display the Newslater Talbe List
	 * @return void
	 */
	 
function load_custom_css_scripts() { 
	wp_register_style( 'dataTables.min.css', 'https://cdn.datatables.net/1.10.11/css/jquery.dataTables.min.css', false, '1.12.0','all');
	wp_enqueue_style( 'dataTables.min.css' );
	wp_register_style('rpd_style.css', plugins_url('/assets/css/rpd_style.css',__FILE__ ));
	wp_enqueue_style( 'rpd_style.css' );
}

/**
	 * Load JS
	 * Used to display datatable structure
	 * @return void
	 */ 

function load_custom_js_scripts() {
	wp_register_script('jquery.1.12.0', 'https://code.jquery.com/jquery-1.12.0.min.js', false, '1.12.0',true);
	wp_enqueue_script('jquery.1.12.0');
	wp_register_script('jquery.dataTables', 'https://cdn.datatables.net/1.10.11/js/jquery.dataTables.min.js', false, '1.10.11', true);
    wp_enqueue_script('jquery.dataTables');
	wp_register_script( 'rpd_custom', plugins_url('/assets/js/rpd_custom.js', __FILE__ ),false,'1.0',true);
	wp_localize_script('rpd_custom','plugin_ajax',array('ajaxurl'=>admin_url('admin-ajax.php')));
	wp_enqueue_script('rpd_custom');	
}

/**
	 * woocommerce_settings_tabs_array
	 * Used to add a WooCommerce settings tab
	 * @param  array $settings_tabs
	 * @return array
	 */
    function woocommerce_settings_tabs_array( $settings_tabs ) {
        $settings_tabs['rpd'] = __('RPD Stock Notifier','woocommerce' );
        return $settings_tabs;
    }

/**
	 * show_settings_tab
	 * Used to display the WooCommerce settings tab content
	 * @return void
	 */
    function show_settings_tab(){
        woocommerce_admin_fields($this->get_settings());		
    }	

/**
	 * show_table_records
	 * Used to display the Newslater Records list
	 * @return void
	 */	
	function show_table_records()
	{ 
		global $wpdb;
		$rpd_records = $wpdb->get_results("SELECT * FROM $wpdb->postmeta where meta_key = 'subscrib_product_data'");
		echo '<h2>Newslater Records</h2></br>'; ?>
		<table id="example" class="display nowrap" cellspacing="0" width="100%">
			<thead>
				<tr>
					<th><?php _e( 'Product ID', 'woocommerce' ); ?></th>
					<th><?php _e( 'Product Name', 'woocommerce' ); ?></th>
					<th><?php _e( 'Email', 'woocommerce' ); ?></th>
					<th><?php _e( 'Status', 'woocommerce' ); ?></th>
					<th><?php _e( 'Action', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody> <?php 
				foreach($rpd_records as $rpd_newslater_records)
				{
					$rpd_newslater_value = maybe_unserialize($rpd_newslater_records->meta_value); //json unserialize
					$rpd_stock_status = get_post_meta($rpd_newslater_value['post_id'],'_stock_status',false);	?>
					<tr>
						<input type="hidden" class="rpd_meta_id" value="<?php echo $rpd_newslater_records->meta_id; ?>">
						<td><?php echo $rpd_newslater_value['product_id']; ?></td>
						<td><?php echo $rpd_newslater_value['product_title']; ?></td>
						<td><?php echo $rpd_newslater_value['user_email']; ?></td>
						<td><?php echo $rpd_stock_status[0]; ?> </td>
						<td> <div class="send_mail_btn" style="display: inline;">
							<?php  if($rpd_newslater_value['email_status'] == 0): ?>
							<a href="javascript:void(0)" class="rpd_send_it">Send</a></div>
							<?php else: ?>
							<a href="javascript:void(0)" class="rpd_send_it">ReSend</a></div>
							<?php endif; ?>
							<div class="delete_btn" style="display: inline;"><a href="javascript:void(0)">Delete</a></div>
						</td>
					</tr><?php
				} ?>	
			</tbody>
		</table><?php
	}
/**
	 * Load Ajax
	 * Used to Send Email
	 * @return void
	 */ 
function mail_send_to(){
		global $wpdb;
		$rpd_records = $wpdb->get_row("SELECT * FROM $wpdb->postmeta where meta_id = '".$_POST['meta_id']."'");
	    $rpd_newslater_value = maybe_unserialize($rpd_records->meta_value);
		$product_permalink = $rpd_newslater_value['product_id'];
        
		$message_txt = get_option('wc_rpd_out_of_stock_note');
		$post_id = $rpd_newslater_value['post_id'];
		$subject_txt = get_option('wc_rpd_subject');
		
		$headers = 'From: Order In Stock <'.get_option( 'wc_rpd_sender_email' ).'>';
			
		$to_email = $rpd_newslater_value['user_email'];
		
		$subject = str_replace('[product_url]', get_permalink($post_id), str_replace('[product_title]', get_the_title($post_id), str_replace('[site_title]', get_option('blogname'), $subject_txt)));
		$message = str_replace('[product_url]', get_permalink($post_id) , str_replace('[product_title]', get_the_title($post_id), str_replace('[site_title]', get_option('blogname'),$message_txt)));
	
		if(wp_mail( $to_email,$subject,$message,$headers))
			{
				$rpd_newslater_value['email_status'] = 1;
				update_post_meta($post_id,'subscrib_product_data',$rpd_newslater_value); 
			}
			
		die();
	}
	
/**
	 * Load Ajax
	 * Used to Delete Records
	 * @return void
	 */
	 
	function delete_record(){
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_id = %d",$_POST['meta_id_del'] ) );		
		die();
	}
	
 /**
     * update_settings_tab
     * Used to save the WooCommerce settings tab values
     * @return void
     */
    function update_settings_tab(){
        woocommerce_update_options($this->get_settings());
    }
	
/**
     * get_settings
     * Used to define the WooCommerce settings tab fields
     * @return void
     */
    function get_settings(){
		$settings = array(
            'section_title' => array(
                'name'     => __('RPD Stock Notifier','woocommerce' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_rpd_section_title'
            ),
			'Subject' => array(
                'name'     => __( 'Subject','woocommerce' ),
                'type'     => 'text',
                'desc'     => __( 'Subject Message','woocommerce' ),
                'desc_tip' => true,
                'id'       => 'wc_rpd_subject'
            ),	
			'Global' => array(
					'name' => __( 'Body Message', 'woocommerce' ),
					'desc' 		=> __( 'Email Boday Message', 'woocommerce' ),
					'id' 		=> 'wc_rpd_out_of_stock_note',
					'css' 		=> 'width:60%; height: 125px;',
					'type' 		=> 'textarea',
					'desc_tip' => true,
			),
			'Textholder' => array(
                'name'     => __( 'Newslater Place Holder','woocommerce' ),
                'type'     => 'text',
                'desc'     => __( 'Newslater Textholder Message','woocommerce' ),
                'desc_tip' => true,
                'id'       => 'wc_rpd_txtholder'
            ),	
			'BtnTextholder' => array(
                'name'     => __( 'Newslater Button Text','woocommerce' ),
                'type'     => 'text',
                'desc'     => __( 'Newslater Button Text Message','woocommerce' ),
                'desc_tip' => true,
                'id'       => 'wc_rpd_btntxtmessage'
            ),	
            'title' => array(
                'name'     => __( 'Enter Email Address','woocommerce' ),
                'type'     => 'email',
                'desc'     => __( 'This is sender email address','woocommerce' ),
                'desc_tip' => true,
                'default'  => '',
                'id'       => 'wc_rpd_sender_email'
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id'   => 'wc_rpd_section_end'
            )
        );
		return apply_filters( 'wc_rpd_settings', $settings );
	}
	
	static function woocommerce_stock_html_mod($availability_html){
		global $product;		
		global $wpdb;
		$post_id = get_the_ID();
		$user_id = get_current_user_id();
		$check_email = true;
		if(isset($_POST['submit_me']) && $_POST['subscriber_email']){
				$email = $_POST['subscriber_email'];
				$objs = new RPD_Stock_Notifier();
				$check_email = $objs->check_email_exist($email);
				$rpd_product_name_records = $wpdb->get_row("SELECT post_title,ID FROM $wpdb->posts where ID = '".$post_id."'");
				$temp = array(
					'user_id' => $user_id,
					'user_email' => $_POST['subscriber_email'],
					'product_id' => $rpd_product_name_records->ID,
					'product_title' => $rpd_product_name_records->post_title,
					'post_id' => $post_id,
                    'email_status' => 0					
				);
				//$product_data = wp_json_encode($temp);
				$key_metas = 'subscrib_product_data';
				
				if($check_email == true){
					add_post_meta($post_id,$key_metas,$temp,false);
				}	
		}	
		$exist_user = get_post_meta($post_id,'subscrib_product_data',true);
		
		// Change Out of Stock Text
		if ( $product->stock_status == 'outofstock' && (empty($exist_user) || $user_id == 0) ) {
			$current_user = wp_get_current_user();
			
			$availability_html .= "<form id='rpdnotifiedform' action='' method='post' onsubmit = 'return false;'>";
			$current_user = ($current_user) ? $current_user->user_email : "";
			$availability_html .= "<p class = 'rpdnotifytag'><input type = 'text' id = 'subscriber_email'  name = 'subscriber_email' placeholder = '" . get_option('wc_rpd_txtholder') . "' value = '".$current_user."'/>
			<div class='rpd_result' style='display:none;'>Invalid Email Address</div>";
			if($check_email == true){
				//echo 'Email Already Exists';
			}
			$availability_html .="<input type = 'submit' class = 'notifyme' id = 'submit_me' name = 'submit_me' value = '" . get_option('wc_rpd_btntxtmessage') . "'/></p>";
			$availability_html .= "</form>";		
		}
		else if(!empty($exist_user) && $user_id != 0){
			echo 'Email Already Submitted';
		}	
		return $availability_html;
	}
	
		function check_email_exist($email){
			global $wpdb;
			$rpd_emails_records = $wpdb->get_results("SELECT * FROM $wpdb->postmeta where meta_key = 'subscrib_product_data'");
			//echo '<pre>';
			//print_r($rpd_emails_records);
			$check_mail = true;
			foreach($rpd_emails_records as $rpd_email_record)
				{
					$rpd_email_value = maybe_unserialize($rpd_email_record->meta_value);
					if($rpd_newslater_value['user_email'] == $email){
						$check_mail = false;
					}
				}
				return $check_mail;
		}
	
} // End Class RPD_Stock_Notifier

new RPD_Stock_Notifier();

}
else { 

	add_action( 'admin_notices', 'sample_admin_notice__error' );
	function sample_admin_notice__error() {
		$class = 'notice notice-error';
		$message = __( 'Required Woocoomerce Plugin', 'sample-text-domain' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
		}
}