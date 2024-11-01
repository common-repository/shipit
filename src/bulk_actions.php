<?php
  add_action('current_screen', 'show_current_screen_id');

  function shipit_bulk_actions_edit_product($actions) {
    $actions['send_orders'] = __('Enviar a Shipit', 'woocommerce');
    return $actions;
  }

  function show_current_screen_id() {
    $screen = get_current_screen();
    if ($screen) {
      add_filter('bulk_actions-'.$screen->id, 'shipit_bulk_actions_edit_product', 20, 1);
      add_filter('handle_bulk_actions-'.$screen->id, 'shipit_handle_bulk_action_edit_shop_order', 10, 3);
    }
  }

  function getSelectedProduct($cartItem) {
    return $cartItem['variation_id'] != '0' && $cartItem['variation_id'] != '' && isset($cartItem['variation_id']) ? wc_get_product($cartItem['variation_id']) : wc_get_product($cartItem['product_id']);
  }

  function shipit_handle_bulk_action_edit_shop_order($redirect_to, $action, $post_ids) {
    if ($action !== 'send_orders') return $redirect_to;

    global $attach_download_dir, $attach_download_file;
    $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
    $integration = new Integration(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
    $opit = new Opit(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
    $helper = new WoocommerceSettingHelper(get_option('woocommerce_weight_unit'), get_option('woocommerce_dimension_unit'));

    $measureConversion = $helper->getMeasureConversion();
    $weightConversion = $helper->getWeightConversion();
    $company = $core->administrative();
    $integrationseller = $integration->setting();
    $skus = array();
    if ($company->service->name == 'fulfillment') {
      $skus = $core->skus();
    }

    $insuranceSetting = $core->insurance();
    $opitSetting = $opit->setting();

    $i = 0;
    foreach ($post_ids as $post_id) {
      $i++;
      $measuresCollection = new MeasureCollection();
      $order = wc_get_order($post_id);
      $country = $order->get_shipping_country();
      $state = $order->get_shipping_state();
      $communeName = WC()->countries->get_states($country)[$state];
      $paid = $order->is_paid() ? __('yes') : __('no');
      $forms = get_option('woocommerce_shipit_settings');
      $inventory = array();
      $productCategories = "";
      foreach ($order->get_items() as $cartItem) {
        $product = getSelectedProduct($cartItem);
        $terms = get_the_terms($product->get_id(), 'product_cat');
        foreach ($terms as $term) {
          $productCategories = $productCategories.' '.$term->slug;
        }
        if (!empty($skus)) {
          $sku = $product->get_sku() != '' ? $product->get_sku() : $product->get_id();
          foreach ($skus as $skuObject) {
            # here find sku from product at store
            if (strtolower($skuObject['name']) == strtolower($sku)) {
              array_push($inventory, [
                'sku_id' => $skuObject['id'],
                'amount' => $cartItem['qty'],
                'description' => $skuObject['description'],
                'warehouse_id' => $skuObject['warehouse_id']
              ]);
            }
            $measure = new Measure((float)$skuObject['height'], (float)$skuObject['width'], (float)$skuObject['length'], (float)$skuObject['weight'], (int)$cartItem['qty']);
            $measuresCollection->setMeasures($measure->buildBoxifyRequest());
          }
        } else {
          $height = $helper->packingSetting($product->get_height(), $forms, 'height', 'packing_set', $measureConversion);
          $width = $helper->packingSetting($product->get_width(), $forms, 'width', 'packing_set', $measureConversion);
          $length = $helper->packingSetting($product->get_length(), $forms, 'length', 'packing_set', $measureConversion);
          $weight = $helper->packingSetting($product->get_weight(), $forms, 'weight', 'weight_set', $weightConversion);

          $measure = new Measure((float)$height, (float)$width, (float)$length, (float)$weight, (int)$cartItem['quantity']);
          $measuresCollection->setMeasures($measure->buildBoxifyRequest());
        }
      }

      foreach ($order->get_items('shipping')as $shipping_id => $shipping_item_obj){
        $shipping_item_data = $shipping_item_obj->get_data()['method_id'];
      }

      $shipit = false;
      $testStreets = array();
      $testStreets[] = $order->get_shipping_address_1();
      for ($i = 0, $totalTestStreets = count($testStreets); $i < $totalTestStreets; $i++) {
        $address = split_street($testStreets[$i]);
      }
      $parcel = $measuresCollection->calculate();

      $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
      $company = $core->administrative();

      if ($integrationseller->automatic_delivery) {
        $request_payload[] = create_order($order, $parcel, $company, $inventory, $communeName, (int)filter_var($order->get_shipping_state(), FILTER_SANITIZE_NUMBER_INT), $productCategories, $insuranceSetting );
        $processed_ids[] = $post_id;
      } else {
        $destiny = new Destiny(
          $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
          $order->get_billing_phone(),
          $order->get_billing_email(),
          ($address['street'] != '') ? $address['street'] : $order->get_shipping_address_1(),
          $address['number'],
          $order->get_shipping_address_2(),
          (int)filter_var($order->get_shipping_state(), FILTER_SANITIZE_NUMBER_INT),
          $communeName,
          'home_delivery',
          $order->get_billing_postcode()
        );
        $seller = new Seller($order->get_id(), $order->get_date_created(), get_site_url(), $order->get_status());
        $courier = new Courier($order->get_shipping_method(), $order->get_shipping_method(), $opitSetting->algorithm, $opitSetting->algorithm_days, false);
        $price = new Price((int)$order->get_shipping_total(), (int)$order->get_shipping_total(), 0, (int)$order->get_cart_tax(), 0);
        $payment = new Payment((int)$order->get_total(), 0, 0, '', 0, 0, '', false);
        $measure = new Measure($parcel['height'], $parcel['width'], $parcel['length'], $parcel['weight']);
        $insurance = new Insurance(
          ((int)$order->get_total() - (int)$order->get_shipping_total()),
          $order->get_id(),
          ltrim($productCategories),
          ($insuranceSetting->active && (((int)$order->get_total() - (int)$order->get_shipping_total()) > $insuranceSetting->amount))
        );

        $order_payload = new Order($company->id, '#'.$post_id, $order->get_item_count(), $company->service->name, false, 1, $destiny->getDestiny(), $seller->getSeller(), $inventory, $courier->getCourier(), $price->getPrice(), $payment->getPayment(), $measure->getMeasure(), $insurance->getInsurance());

        $request_payload[] = $order_payload->build();
        $processed_ids[] = $post_id;
      }
    }

    if ($integrationseller->automatic_delivery) {
      $core_cli = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
      $response = $core_cli->massiveShipments(['shipments' => $request_payload]);
    } else {
      $response = $integration->massiveOrders(['orders' => $request_payload]);
    }
    $core->mixPanelLogger('envio por accion masiva', mixPanelObject($integrationseller, $response, $request_payload, $order));

    foreach ($processed_ids as $key => $order_id) {
      $order = new WC_Order($order_id);
      $order->add_order_note('Se ha enviado a Shipit correctamente mediante acción masiva');
    }

    return $redirect_to = add_query_arg(array(
      'send_orders' => '1',
      'processed_count' => count($processed_ids),
      'processed_ids' => implode(',', $processed_ids),
    ), $redirect_to);
  }

  add_action('admin_notices', 'shipit_bulk_action_admin_notice');
  function shipit_bulk_action_admin_notice() {
    if (empty($_REQUEST['send_orders'])) return;

    $count = intval($_REQUEST['processed_count']);
    $class = 'notice notice-success is-dismissible';
    $message = __('Se enviaron a Shipit '.$count.' ordenes.', 'sample-text-domain');
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
  }
?>
