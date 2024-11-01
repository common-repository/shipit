<?php
  function shipit_json_basic_auth_handler($user) {
    global $wp_json_basic_auth_error;
    $wp_json_basic_auth_error = null;
    if (!empty($user)) return $user;
    if (!isset($_SERVER['PHP_AUTH_USER'])) return $user;

    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    remove_filter('determine_current_user', 'shipit_json_basic_auth_handler', 20);
    $user = wp_authenticate($username, $password);

    add_filter('determine_current_user', 'shipit_json_basic_auth_handler', 20);
    if (is_wp_error($user)) {
      $wp_json_basic_auth_error = $user;
      return null;
    }
    $wp_json_basic_auth_error = true;
    return $user->ID;
  }
  add_filter('determine_current_user', 'shipit_json_basic_auth_handler', 20);

  function shipit_json_basic_auth_error($error) {
    if (!empty($error)) return $error;

    global $wp_json_basic_auth_error;
    return $wp_json_basic_auth_error;
  }

  add_filter('rest_authentication_errors', 'shipit_json_basic_auth_error');
  add_filter('woocommerce_states', 'destinies');

  function destinies($states) {
    $communes = Commune::getCommunes();
	  $communes_list = array();
	  foreach ($communes as $key => $value) {
		  $communes_list[getDefaultcountry().$value->region_id] = $value->name;
	  }
    $states[getDefaultcountry()] = $communes_list;
	  return $states;
  }

  add_filter('woocommerce_checkout_fields' , 'checkoutShipitFields', 9999);
  function checkoutShipitFields($fields) {
    $fields['billing']['billing_state']['label'] = getLabelByCountry();
    $fields['shipping']['shipping_state']['label'] = getLabelByCountry();
    if (getDefaultcountry() == 'CL') {
      unset($fields['billing']['billing_postcode']);
      unset($fields['shipping']['shipping_postcode']);
    }

    return $fields;
  }

  add_filter('woocommerce_get_country_locale', 'wc_change_state_label_locale');
  function wc_change_state_label_locale($locale) {
    $locale[getDefaultcountry()]['state']['label'] = __(getLabelByCountry(), 'woocommerce');
    return $locale;
  }

  function getDefaultcountry() {
    $shipit_country_settings = ShipitCountryHelper::getCountrySettings();
    return $shipit_country_settings['woocommerce_default_country'];
  }

  function getLabelByCountry() {
    $shipit_country_settings = ShipitCountryHelper::getCountrySettings();
    return $shipit_country_settings['label_name'];
  }

  add_action( 'woocommerce_after_checkout_validation', 'validate_checkout', 10, 2);
  function validate_checkout( $data, $errors ){
      if (  ! preg_match('/[0-9]/', $data[ 'billing_address_1' ] ) ){
          $errors->add( 'address', 'Lo sentimos, pero su dirección no contiene números.' );
      }
  }

  add_action('wp_head', 'shipit_woocommerce_tip');
  function shipit_woocommerce_tip() {
      ?>
      <script type="text/javascript">
          jQuery(document).ready(function($) {
              var preventCheckoutUpdate = true;
  
              jQuery('label[for="billing_state"]').text('<?php echo getLabelByCountry(); ?>');
  
              $('#billing_state').change(function() {
                  preventCheckoutUpdate = false;
                  jQuery('body').trigger('update_checkout');
              });

              $('#billing_address_1').keydown(function() {
                  preventCheckoutUpdate = true;
              });

              $('#billing_address_2').keydown(function() {
                  preventCheckoutUpdate = true;
              });
              
              $('#billing_city').keydown(function() {
                  preventCheckoutUpdate = true;
              });

              jQuery('body').on('update_checkout', function(event) {
                  if (preventCheckoutUpdate) {
                      preventCheckoutUpdate = false;
                      event.preventDefault();
                      event.stopImmediatePropagation();
                  }
              });
          });
      </script>
      <?php
  }
?>
