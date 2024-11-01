jQuery(document).ready(function(e) {
 
 referal = jQuery('#woocommerce_bwalletpay1_referral').parents( 'tr' ).eq( 0 );

 referal.hide();
//jQuery("#woocommerce_bwalletpay1_referral").hide();

//jQuery().change();
jQuery( "#woocommerce_bwalletpay1_ref_id" ).checked = false;
jQuery( "#woocommerce_bwalletpay1_ref_id" ).change(function() {
if ( jQuery(this).is( ':checked' ) ) {

					referal.show();
				} else {
					referal.hide();
					 
				}
});
});
 





 