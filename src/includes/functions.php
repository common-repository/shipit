<?php

function shipit_script_load() {
    wp_enqueue_script('shipitjavascript', plugin_dir_url(__FILE__) . '../js/javascript.js', array('jquery'));
    wp_register_style('custom_wp_admin_css', plugin_dir_url(__FILE__) . '../css/style_shipit.css', false, '1.0.0');
    wp_enqueue_style('custom_wp_admin_css');
}
add_action('wp_head', 'shipit_script_load', 0);

function shipit_house_add_checkout_fields($fields) {
    $fields['billing_phone'] = array(
        'label' => __('Teléfono'),
        'type' => 'text',
        'class' => array('form-row-wide'),
        'placeholder' => __('+569 --------'),
        'priority' => 35,
        'required' => true
    );
    return $fields;
}
add_filter('woocommerce_billing_fields', 'shipit_house_add_checkout_fields');

function hash_password($password) {
    global $wp_hasher;
    if (empty($wp_hasher)) {
        require_once(ABSPATH . WPINC . '/class-phpass.php');
        $wp_hasher = new PasswordHash(8, true);
    }
    return $wp_hasher->HashPassword(trim($password));
}

  function getProduct($cartItem) {
    return $cartItem['variation_id'] != '0' && $cartItem['variation_id'] != '' && isset($cartItem['variation_id']) ? wc_get_product($cartItem['variation_id']) : wc_get_product($cartItem['product_id']);
  }
  // shipit_cURL_wrapper
  function getMeasures($height = 0, $width = 0, $length = 0, $weight = 0) {
    $measuresCollection = new MeasureCollection();
    $helper = new WoocommerceSettingHelper(get_option('woocommerce_weight_unit'), get_option('woocommerce_dimension_unit'));
    $cart = WC()->cart->get_cart();
    $count = WC()->cart->get_cart_contents_count();
    $forms = new Shipit_Shipping();

    $measureConversion = $helper->getMeasureConversion();
    $weightConversion = $helper->getWeightConversion();
    foreach ($cart as $cartItem) {
      $product = getProduct($cartItem);
      $height = $helper->packingSetting($product->get_height(), $forms->settings, 'height', 'packing_set', $measureConversion);
      $width = $helper->packingSetting($product->get_width(), $forms->settings, 'width', 'packing_set', $measureConversion);
      $length = $helper->packingSetting($product->get_length(), $forms->settings, 'length', 'packing_set', $measureConversion);
      $weight = $helper->packingSetting($product->get_weight(), $forms->settings, 'weight', 'weight_set', $weightConversion);

      $measure = new Measure((float)$height, (float)$width, (float)$length, (float)$weight, (int)$cartItem['quantity']);
      $measuresCollection->setMeasures($measure->buildBoxifyRequest());
    }
    return $measuresCollection->calculate();
  }

  function mixPanelObject($sellerSetting, $response, $orderPayload, $order) {
    if (empty($response->errors)) $response = successResponse();
      
    $mixPanelObject = array(
      'sellerSetting' => $sellerSetting,
      'orderPayload' => $orderPayload,
      'response' => $response,
      'shipitPluginVersion' => get_plugin_data( __FILE__ )['Version'],
      'woocommercePluginVersion' => get_plugin_data( WP_PLUGIN_DIR.'/woocommerce/woocommerce.php' )['Version'],
      'shipitSettings' => get_option('woocommerce_shipit_settings'),
      'orderData' => $order->get_data(),
      'message' => makeMixPanelMessage($response)
    );
    return $mixPanelObject;
  }

  function successResponse() {
    $jsonString = '{
      "errors": 0,
      "message": "Orden creada correctamente",
      "state": "success",
      "success": 1
  }';

    return json_decode($jsonString);
  }


  function makeMixPanelMessage($response) {
    if (empty($response->errors)) {
      $message = 'Orden creada correctamente';
    } elseif (empty($response->message)) {
      $message = 'Error controlado';
    } else {
      $message = 'Error no controlado';
    }
  
    return $message;
  }


  function split_street($streetStr) {
    $aMatch = array();
    $pattern = '/([a-z]|[!"$%&()=#,.])\s*\d{1,5}/i';
    preg_match($pattern, $streetStr, $aMatch);
    if (empty($aMatch)){
        $regex_second_try = '/.\d{2,5}/';
        preg_match($regex_second_try, $streetStr, $aMatch);
    }
    
    if (empty($aMatch)) {
        return array('street' => $streetStr, 'number' => '', 'numberAddition' => '');
    }
    
    $number = preg_replace('/\D/', '', $aMatch[0]);
    $splitedAddress = explode($number, $streetStr);
    $street = ltrim(preg_replace('/[#$%-]/', '', $splitedAddress[0]));
    $numberAddition = sizeof($splitedAddress) > 1 ? $splitedAddress[1] : "";
    
    return array('street' => $street, 'number' => $number, 'numberAddition' => $numberAddition);
}


  function getDeliveryType($order) {
    foreach ($order->get_items('shipping') as $shipping_id => $shipping_item_obj) {
      $shipping_item_data = $shipping_item_obj->get_data()['method_id'];
    }
    $shipit = $shipping_item_data == 'shipit' ? false : true;
  
    return $shipit;
  }