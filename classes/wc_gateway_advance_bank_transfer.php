<?php
/**
 * Advance Direct Offline Payment Gateway
 *
 * @class   ABPT_Gateway_Advance_Bank_Payment_Offline
 * @extends	WC_Payment_Gateway
 */

class ABPT_Gateway_Advance_Bank_Payment_Offline extends WC_Payment_Gateway
{
	/**
	 * Array of locales
	 *
	 * @var array
	 */
	public $locale;
	public $domain;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
		$this->domain             = 'woocommerce-pix';
        $this->id                 = 'pix_gateway';
        $this->icon               = apply_filters('woocommerce_offline_icon', '');
        $this->has_fields         = false;
        $this->method_title       = __('Pix', $this->domain);
        $this->method_description = __('Take payments in person via Pix.', $this->domain);

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title            = $this->get_option('title');
        $this->description      = $this->get_option('description');
        $this->instructions     = $this->get_option('instructions');

        // BACS account fields shown on the checkout page and in admin configuration tab.
		$this->account_details = get_option(
			'woocommerce_bacs_accountss',
			array(
				array(
					'account_name'   => $this->get_option( 'account_name' ),
					'account_number' => $this->get_option( 'account_number' ),
					'sort_code'      => $this->get_option( 'sort_code' ),
					'bank_name'      => $this->get_option( 'bank_name' ),
					'iban'           => $this->get_option( 'iban' ),
					'bic'            => $this->get_option( 'bic' ),
				),
			)
		);

        // Actions
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_account_details' ) );
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

        // Customer Emails
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
    }


    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields()
    {

        $this->form_fields = apply_filters('wc_offline_form_fields', array(

            'enabled' => array(
                'title'   => __('Enable/Disable', $this->domain),
                'type'    => 'checkbox',
                'label'   => __('Enable Payment', $this->domain),
                'default' => 'yes'
            ),

            'title' => array(
                'title'       => __('Title', $this->domain),
                'type'        => 'text',
                'description' => __('This controls the title for the payment method the customer sees during checkout.', $this->domain),
                'default'     => __('Advance Bank Payment', $this->domain),
                'desc_tip'    => true,
            ),

            'description' => array(
                'title'       => __('Description', $this->domain),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', $this->domain),
                'default'     => __('Make your payment directly into our bank account first on below details.And please Upload the Bank Payment Receipt use and your Order ID as the payment reference. Your order will not be shipped until the funds have cleared in our account.', $this->domain),
                'desc_tip'    => true,
            ),

            'instructions' => array(
                'title'       => __('Instructions', $this->domain),
                'type'        => 'textarea',
                'description' => __('Instructions', $this->domain),
                'default'     => '',
                'desc_tip'    => true,
            ),

            'account_details' => array(
				'type' => __('account_details', $this->domain),
			),
        ));
    }

	/**
	 * Save account details table.
	 */
	public function save_account_details() {

		$accounts = array();

		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification -- Nonce verification already handled in WC_Admin_Settings::save()
		if ( isset( $_POST['bacs_account_name'] ) && isset( $_POST['bacs_account_number'] ) && isset( $_POST['bacs_bank_name'] )
			 && isset( $_POST['bacs_sort_code'] ) && isset( $_POST['bacs_iban'] ) && isset( $_POST['bacs_bic'] ) ) {

			$account_names   = wc_clean( wp_unslash( $_POST['bacs_account_name'] ) );
			$account_numbers = wc_clean( wp_unslash( $_POST['bacs_account_number'] ) );
			$bank_names      = wc_clean( wp_unslash( $_POST['bacs_bank_name'] ) );
			$sort_codes      = wc_clean( wp_unslash( $_POST['bacs_sort_code'] ) );
			$ibans           = wc_clean( wp_unslash( $_POST['bacs_iban'] ) );
			$bics            = wc_clean( wp_unslash( $_POST['bacs_bic'] ) );

			foreach ( $account_names as $i => $name ) {
				if ( ! isset( $account_names[ $i ] ) ) {
					continue;
				}

				$accounts[] = array(
					'account_name'   => $account_names[ $i ],
					'account_number' => $account_numbers[ $i ],
					'bank_name'      => $bank_names[ $i ],
					'sort_code'      => $sort_codes[ $i ],
					'iban'           => $ibans[ $i ],
					'bic'            => $bics[ $i ],
				);
			}
		}

		update_option( 'woocommerce_bacs_accountss', $accounts );
	}

    /**
	 * Get country locale if localized.
	 *
	 * @return array
	 */
	public function get_country_locale() {

		if ( empty( $this->locale ) ) {

			// Locale information to be used - only those that are not 'Sort Code'.
			$this->locale = apply_filters(
				'woocommerce_get_bacs_locale',
				array(
					'AU' => array(
						'sortcode' => array(
							'label' => __( 'BSB', 'wc-gateway-offline' ),
						),
					),
					'CA' => array(
						'sortcode' => array(
							'label' => __( 'Bank transit number', 'wc-gateway-offline' ),
						),
					),
					'IN' => array(
						'sortcode' => array(
							'label' => __( 'IFSC', 'wc-gateway-offline' ),
						),
					),
					'IT' => array(
						'sortcode' => array(
							'label' => __( 'Branch sort', 'wc-gateway-offline' ),
						),
					),
					'NZ' => array(
						'sortcode' => array(
							'label' => __( 'Bank code', 'wc-gateway-offline' ),
						),
					),
					'SE' => array(
						'sortcode' => array(
							'label' => __( 'Bank code', 'wc-gateway-offline' ),
						),
					),
					'US' => array(
						'sortcode' => array(
							'label' => __( 'Routing number', 'wc-gateway-offline' ),
						),
					),
					'ZA' => array(
						'sortcode' => array(
							'label' => __( 'Branch code', 'wc-gateway-offline' ),
						),
					),
				)
			);

		}

		return $this->locale;

	}

    /**
	 * Generate account details html.
	 *
	 * @return string
	 */
	public function generate_account_details_html() {

		ob_start();

		$country = WC()->countries->get_base_country();
		$locale  = $this->get_country_locale();

		// Get sortcode label in the $locale array and use appropriate one.
		$sortcode = isset( $locale[ $country ]['sortcode']['label'] ) ? $locale[ $country ]['sortcode']['label'] : __( 'Sort code', 'wc-gateway-offline' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Account details:', 'wc-gateway-offline' ); ?></th>
			<td class="forminp" id="bacs_accounts">
				<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
							<tr>
								<th class="sort">&nbsp;</th>
								<th><?php esc_html_e( 'Account name', 'wc-gateway-offline' ); ?></th>
								<th><?php esc_html_e( 'Account number', 'wc-gateway-offline' ); ?></th>
								<th><?php esc_html_e( 'Bank name', 'wc-gateway-offline' ); ?></th>
								<th><?php echo esc_html( $sortcode ); ?></th>
								<th><?php esc_html_e( 'IBAN', 'wc-gateway-offline' ); ?></th>
								<th><?php esc_html_e( 'BIC / Swift', 'wc-gateway-offline' ); ?></th>
							</tr>
						</thead>
						<tbody class="accounts">
							<?php
							$i = -1;
							if ( $this->account_details ) {
								foreach ( $this->account_details as $account ) {
									$i++;

									echo '<tr class="account">
										<td class="sort"></td>
										<td><input type="text" value="' . esc_attr( wp_unslash( $account['account_name'] ) ) . '" name="bacs_account_name[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $account['account_number'] ) . '" name="bacs_account_number[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( wp_unslash( $account['bank_name'] ) ) . '" name="bacs_bank_name[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $account['sort_code'] ) . '" name="bacs_sort_code[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $account['iban'] ) . '" name="bacs_iban[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $account['bic'] ) . '" name="bacs_bic[' . esc_attr( $i ) . ']" /></td>
									</tr>';
								}
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="7"><a href="#" class="add button"><?php esc_html_e( '+ Add account', 'wc-gateway-offline' ); ?></a> <a href="#" class="remove_rows button"><?php esc_html_e( 'Remove selected account(s)', 'wc-gateway-offline' ); ?></a></th>
							</tr>
						</tfoot>
					</table>
				</div>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#bacs_accounts').on( 'click', 'a.add', function(){

							var size = jQuery('#bacs_accounts').find('tbody .account').length;

							jQuery('<tr class="account">\
									<td class="sort"></td>\
									<td><input type="text" name="bacs_account_name[' + size + ']" /></td>\
									<td><input type="text" name="bacs_account_number[' + size + ']" /></td>\
									<td><input type="text" name="bacs_bank_name[' + size + ']" /></td>\
									<td><input type="text" name="bacs_sort_code[' + size + ']" /></td>\
									<td><input type="text" name="bacs_iban[' + size + ']" /></td>\
									<td><input type="text" name="bacs_bic[' + size + ']" /></td>\
								</tr>').appendTo('#bacs_accounts table tbody');

							return false;
						});
					});
				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();

	}

	/**
	 * Get bank details and place into a list format.
	 *
	 * @param int $order_id Order ID.
	 */
	private function bank_details( $order_id = '' ) {

		if ( empty( $this->account_details ) ) {
			return;
		}

		// Get order and store in $order.
		$order = wc_get_order( $order_id );

		$bacs_accounts = apply_filters( 'woocommerce_bacs_accountss', $this->account_details );

		if ( ! empty( $bacs_accounts ) ) {
			$account_html = '';
			$has_details  = false;

			foreach ( $bacs_accounts as $bacs_account ) {
				$bacs_account = (object) $bacs_account;

				if ( $bacs_account->account_name ) {
					$account_html .= '<p class="wc-bacs-bank-details-account-name"><u>' . wp_kses_post( wp_unslash( $bacs_account->account_name ) ) . '</u>:</p>' . PHP_EOL;
				}

				$account_html .= '<ul class="wc-bacs-bank-details order_details bacs_details">' . PHP_EOL;

				// BACS account fields shown on the Checkout page.
				$account_fields = apply_filters(
					'woocommerce_bacs_account_fields',
					array(
						'bank_name'      => array(
							'label' => __( 'Bank', 'woocommerce' ),
							'value' => $bacs_account->bank_name,
						),
						'account_number' => array(
							'label' => __( 'Account number', 'woocommerce' ),
							'value' => $bacs_account->account_number,
						),
						'sort_code'      => array(
							'label' => __( 'IFSC', 'woocommerce' ),
							'value' => $bacs_account->sort_code,
						),
						'iban'           => array(
							'label' => __( 'IBAN', 'woocommerce' ),
							'value' => $bacs_account->iban,
						),
						'bic'            => array(
							'label' => __( 'BIC', 'woocommerce' ),
							'value' => $bacs_account->bic,
						),
					),
					$order_id
				);

				foreach ( $account_fields as $field_key => $field ) {
					if ( ! empty( $field['value'] ) ) {
						$account_html .= '<li class="' . esc_attr( $field_key ) . '">' . wp_kses_post( $field['label'] ) . ': <strong>' . wp_kses_post( wptexturize( $field['value'] ) ) . '</strong></li>' . PHP_EOL;
						$has_details   = true;
					}
				}

				$account_html .= '</ul>';
			}

			if ( $has_details ) {
				echo '<section class="woocommerce-bacs-bank-details"><h2 class="wc-bacs-bank-details-heading">' . esc_html__( 'Our bank details', 'woocommerce' ) . '</h2>' . wp_kses_post( PHP_EOL . $account_html ) . '</section>';
			}
		}

	}

	public function payment_fields(){

		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		$this->bank_details();
		?>
		<br>
		<div id="custom_input">
			<p class="form-row form-row-wide">
				<label for="bank_payment_receipt" class=""><?php _e('Upload the bank payment receipt', $this->domain); ?></label>
				<input type="file" name="bank_payment_receipt" class="bank_payment_receipt">
				<input type="hidden" name="attach_id" class="attach_id">
			</p>
		</div>
		<script>
			jQuery(document).ready( function($) {
				$(".bank_payment_receipt").change( function() {
					var fd = new FormData();
					fd.append('file', $('.bank_payment_receipt')[0].files[0]);
					fd.append('action', 'invoice_response');

					$.ajax({
						type: 'POST',
						url: the_ajax_script.ajaxurl,
						data: fd,
						contentType: false,
						processData: false,
						success: function(response){
							if(response=='0'){
								alert('Invalid File, please upload correct file');
								$('.attach_id').val('');
							}else{
								$('.attach_id').val(response);
							}
						}
					});
				});
			});
		</script>
		<?php
	}

	public function validate_fields(){
		if(!isset($_POST['attach_id']) || empty( $_POST['attach_id']) ) {
			wc_add_notice(__('<strong>Bank Payment Receipt</strong> is a required field.'), 'error');
			return false;
		}
		return true;
	}

    /**
     * Output for the order received page.
     */
    public function thankyou_page()
    {
        if ($this->instructions) {
            echo wpautop(wptexturize($this->instructions));
        }
    }


    /**
     * Add content to the WC emails.
     *
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false)
    {

        if ($this->instructions && !$sent_to_admin && $this->id === $order->payment_method && $order->has_status('on-hold')) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }


    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {

        $order = wc_get_order($order_id);

        // Mark as on-hold (we're awaiting the payment)
        $order->update_status('on-hold', __('Awaiting offline payment', $this->domain));

        // Reduce stock levels
        $order->reduce_order_stock();

        // Remove cart
        WC()->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result'     => 'success',
            'redirect'    => $this->get_return_url($order)
        );
    }
}

/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'abpt_custom_payment_update_order_meta' );
function abpt_custom_payment_update_order_meta( $order_id ) {
    if($_POST['payment_method'] != 'pix_gateway')
        return;

    update_post_meta( $order_id, 'attach_id', sanitize_text_field( $_POST['attach_id'] ) );
}


/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_order_details', 'abpt_custom_checkout_field_display_admin_order_meta', 10, 1 );
function abpt_custom_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->id, '_payment_method', true );
    if($method != 'pix_gateway')
        return;

    $attach_id = get_post_meta( $order->id, 'attach_id', true );
	$src=wp_get_attachment_url($attach_id, 'full');
    echo '<p><strong>'.__( 'Bank Payment Invoice' ).':</strong> <a href="'.$src.'"><img src="'.$src.'" height="50"/></a></p>';
}
