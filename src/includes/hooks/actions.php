<?php

add_action('woocommerce_order_status_changed', 'shipit_method', 9, 1);
add_action('woocommerce_shipping_init', 'shipit_method');
add_action('woocommerce_order_status_changed', 'dispatch_to_shipit', 10, 1);
add_action( 'upgrader_process_complete', 'sendShipitSettings',10, 2);
add_action('admin_head', 'my_custom_dashicons_css');
add_action('admin_footer', 'hide_shipit_shipping_menu');

function hide_shipit_shipping_menu() {
    echo '<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        var links = document.querySelectorAll("a");
        links.forEach(function(link) {
            if (link.innerText === "Shipit" && link.href.includes("wc-settings&tab=shipping&section=shipit")) {
                link.style.display = "none";
            }
        });
    });
    </script>';
}

function dispatch_to_shipit($orderId) {
  if (!$orderId) return;

  $shipitUser = get_option('shipit_user');
  $core = new Core($shipitUser['shipit_user'], $shipitUser['shipit_token'], 'v4');
  $order = wc_get_order($orderId);

  if (!$order) return;

  $destinyId = (int) filter_var($order->get_shipping_state(), FILTER_SANITIZE_NUMBER_INT);
  $validStatuses = ['cancelled', 'failed', 'on-hold', 'refunded', 'pending', 'pending payment'];

  if (in_array($order->get_status(), $validStatuses)) {
      $core->mixPanelLogger('La orden no se importo a Shipit porque el estado no cumple la condicion', $order->get_data());
      $order->add_order_note('No pudo ser enviado a Shipit porque el pedido todavía no está confirmado o está fallido');
  } else {
      $integration = new Integration($shipitUser['shipit_user'], $shipitUser['shipit_token']);
      $company = $core->administrative();
      $skus = $company->service->name == 'fulfillment' ? $core->skus() : [];
      $insuranceSetting = $core->insurance();
      $sellerSetting = $integration->setting();
      $request = createShipment($company, $skus, $sellerSetting, $insuranceSetting, $order, $destinyId);

      if ($request) {
          $order->add_order_note('El pedido se ha enviado a Shipit correctamente');
      } else {
          $order->add_order_note('El pedido no pudo ser enviado a Shipit: ');
          $core->mixPanelLogger('La orden no se importo a Shipit', json_decode(json_encode($request), true));
      }
  }

  $order->update_meta_data('_thankyou_action_done', true);
  $order->save();
}


function sendShipitSettings( $upgrader_object, $options ) {
  $current_plugin_path_name = plugin_basename( __FILE__ );

  if ($options['action'] == 'update' && $options['type'] == 'plugin' ) {
     foreach($options['plugins'] as $each_plugin) {
        if ($each_plugin==$current_plugin_path_name) {
          getShipitConfiguration();

        }
     }
  }
  
}

function shipit_method() {
    //wp_enqueue_script('woocommerce_communes_selected', plugin_dir_url(__FILE__) . '../js/communes_selected.js', array('jquery'));
    if (!class_exists('Shipit_Shipping')) {
        class Shipit_Shipping extends WC_Shipping_Method {
            public function __construct() {
                $this->id = 'shipit';
                $this->method_title = __('Shipit');
                $this->method_description = __('Shipit Cotizador');
                $this->init();
            }

            function init() {
                $this->init_form_fields();
                $this->init_settings();
                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            }

            function init_form_fields() {
                $shipit_country_settings = ShipitCountryHelper::getCountrySettings();
                $label_name = $shipit_country_settings['label_name'];
                $woocommerce_default_country = $shipit_country_settings['woocommerce_default_country'];
                $this->form_fields = array(
                  'enabled' => array(
                    'title' => __('Activar', 'dc_raq'),
                    'type' => 'checkbox',
                    'description' => __('Activar el m&eacute;todo de envío Shipit', 'dc_raq'),
                    'default' => 'yes',
                  ),
                  'time_despach' => array(
                    'title'  => __('Tiempo de entrega', 'dc_raq'),
                    'type' => 'checkbox',
                    'description' => __('Mostrar el tiempo de envío de Shipit', 'dc_raq'),
                    'default' => 'yes'
                  ),
                  'type_packing' => array(
                    'title' => 'Tipo empaque',
                    'description' => 'Elige el tipo de empaque que tendría tu envio',
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'options' => array(
                      'Sin empaque' => 'Sin empaque',
                      'Caja de cartón' => 'Caja de cartón',
                      'Film plástico' => 'Film plástico',
                      'Caja + Burbuja' => 'Caja + Burbuja',
                      'Papel kraft' => 'Papel kraft',
                      'Bolsa Courier + Burbuja' => 'Bolsa Courier + Burbuja',
                      'Bolsa Courier' => 'Bolsa Courier'
                    )
                  ),
                  'packing_set' => array(
                    'title' => 'Establecer dimensiones del producto',
                    'description' => 'Configure una dimensión predefinida para sus productos al momento de la cotización. Deje en blanco o &quot;0&quot; para omitir.',
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'default' => 'Sí, cuando falten las dimensiones del producto o no estén configuradas',
                    'options' => array(
                      '2' => 'Sí, cuando falten las dimensiones del producto o no estén configuradas',
                      '1' => 'Sí, utilizar siempre las dimensiones especificadas',
                      '3' => 'Sí, cuando las dimensiones del producto sean menores que las especificadas',
                      '4' => 'Sí, cuando las dimensiones del producto sean mayores que las especificadas',
                    )
                  ),
                  'width' => array(
                    'title' => __('Ancho', 'woocommerce'),
                    'type' => 'number',
                    'description' => __('CM.', 'woocommerce'),
                    'css'      => 'max-width:150px;',
                    'default' => __('10', 'woocommerce')
                  ),
                  'height' => array(
                    'title' => __('Alto', 'woocommerce'),
                    'type' => 'number',
                    'description' => __('CM.', 'woocommerce'),
                    'css'      => 'max-width:150px;',
                    'default' => __('10', 'woocommerce')
                  ),
                  'length' => array(
                    'title' => __('Largo', 'woocommerce'),
                    'type' => 'number',
                    'description' => __('CM.', 'woocommerce'),
                    'css'      => 'max-width:150px;',
                    'default' => __('10', 'woocommerce')
                  ),
                  'weight_set' => array(
                    'title' => 'Establecer peso del producto',
                    'description' => 'Configure un peso predefinido para sus productos al momento de la cotización.',
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'default'     => 'Sí, cuando el peso del producto falte o no esté configurado',
                    'options' => array(
                      '2' => 'Sí, cuando el peso del producto falte o no esté configurado',
                      '1' => 'Sí, utilizar siempre el peso especificado',
                      '3' => 'Sí, cuando el peso del producto sea menor que el especificado',
                      '4' => 'Sí, cuando el peso del producto sea mayor que el especificado'
                    )
                  ),
                  'weight' => array(
                    'title' => __('Peso', 'woocommerce'),
                    'type' => 'number',
                    'description' => __('KG.', 'woocommerce'),
                    'css'      => 'max-width:150px;',
                    'default' => __('1', 'woocommerce'),
                  ),
                  'active-setup-price' => array(
                    'title' => __('Activar precio definido', 'dc_raq'),
                    'type' => 'checkbox',
                    'description' => __('Activar precio preconfigurado', 'dc_raq'),
                    'default' => 'yes'
                  ),
                  'all_communes' => array(
                    'title' => __("¿Todas las {$label_name} ?", 'dc_raq'),
                    'label' => 'Activar',
                    'type' => 'checkbox',
                    'description' => __("Seleccionar todas las {$label_name}  en el campo de {$label_name} específicas siguiente.", 'dc_raq'),
                    'default' => 'no'
                  ),
                  'communes' => array(
                    'title' => __("{$label_name} espec&iacute;ficas", 'woocommerce'),
                    'type' => 'multiselect',
                    'description' => "Configure {$label_name} para valor detallado.",
                    'class' => 'wc-enhanced-select',
                    'options' => WC()->countries->get_states($woocommerce_default_country),
                    'custom_attributes' => array(
                      'data-placeholder' => __("Seleccione {$label_name}", 'woocommerce'),
                    )
                  ),
                  'price-setup' => array(
                    'title' => __('Subvencionar precio de envios ', 'woocommerce'),
                    'type' => 'number',
                    'description' => __("Configure su valor de las {$label_name} por %. '100% = Gratis'", 'woocommerce'),
                    'css' => 'max-width:200px;',
                  ),
                  'all_free_communes' => array(
                    'title' => __("¿Todas las {$label_name}?", 'dc_raq'),
                    'label' => 'Activar',
                    'type' => 'checkbox',
                    'description' => __("Seleccionar todas las {$label_name} en el campo de {$label_name} específicas siguiente.", 'dc_raq'),
                    'default' => 'no'
                  ),
                  'free_communes' => array(
                    'title' => __("{$label_name} especificas", 'woocommerce'),
                    'type' => 'multiselect',
                    'description' => "Configure {$label_name} con despacho gratis.",
                    'class' => 'wc-enhanced-select',
                    'options' => WC()->countries->get_states($woocommerce_default_country),
                    'custom_attributes' => array(
                      'data-placeholder' => __("Seleccione {$label_name}", 'woocommerce'),
                    ),
                  ),
                  'price' => array(
                    'title' => __('Env&iacute;os gratis a partir:', 'woocommerce'),
                    'type' => 'number',
                    'description' => __('Configure el valor de m&iacute;nimo de orden para despachos.', 'woocommerce'),
                    'css' => 'max-width:200px;',
                  ),
                  'free_communes_for_price' => array(
                    'title' => __("{$label_name} con despacho gratis segun valor:", 'woocommerce'),
                    'type' => 'multiselect',
                    'description' => "Configure {$label_name} con despacho gratis si el valor del producto es mayor.",
                    'class' => 'wc-enhanced-select',
                    'options' => WC()->countries->get_states($woocommerce_default_country),
                    'custom_attributes' => array(
                      'data-placeholder' => __("Seleccione {$label_name}", 'woocommerce'),
                    ),
                  ),
                  'cron' => array(
                    'title' => __('Importación complementaria para pedidos no enviados a Shipit', 'cron'),
                    'label' => 'Activar',
                    'type' => 'checkbox',
                    'description' => __('Revisar automáticamente los envíos no importados según la frecuencia configurada.', 'cron'),
                    'default' => 'no',
                  ),
                  'frequency' => array(
                    'title' => 'Frecuencia',
                    'description' => 'Definir la frecuencia de revisión de envíos no importados a Shipit (recomendado: 1 hora)',
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'options' => array(
                      '900' => '15 minutos',
                      '1800' => '30 minutos',
                      '3600' => '1 hora',
                      '21600' => '6 horas',
                      '86400' => '24 horas'
                    )
                    ),
                );
              }

              public function calculate_shipping($package = array()) {
                getShipitConfiguration();
                if (!get_option('shipit_seccond_setting'))
                {
                  sendSecondShipitSetting() ;
                }
                global $woocommerce;
                if (WC()->cart->get_cart_contents_count() === 0) return;
                $cart = strpos( wc_get_cart_url(), $_SERVER['REQUEST_URI']) == false ? false : true;
                $destinyId = (int)filter_var($package["destination"]['state'], FILTER_SANITIZE_NUMBER_INT);
                $integration = new Integration(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
                $sellerSetting = $integration->setting();
                $prices = array();
                $rate = new Rate(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
                if ($sellerSetting->show_shipit_checkout === true && $destinyId != null) {
                  $rate->setParcel(getMeasures(), $cart, WC()->cart->cart_contents_total);
                  $rate->setDestinyId($destinyId);
                  $rate->setMultiCourierEnabled(true);
                  $prices = $rate->calculate();
                }
                $prices = count($prices) > 0 ? $prices : array();
                if (is_object($prices) && $prices->state == 'error' || !$prices) {
                } else {
                  global $shows;
                  $shipit_country_settings = ShipitCountryHelper::getCountrySettings();
                  $label_name = $shipit_country_settings['label_name'];
                  $woocommerce_default_country = $shipit_country_settings['woocommerce_default_country'];
                  $shows = new Shipit_Shipping();
                  $showCarrierPrice = false;
                  if (is_array($prices) || is_object($prices)) {
                    $i = 0;
                    foreach ($prices as $price) {
                      $rateDescription = array($price->name);
                      $carrierName = $price->courier->name;
                      $priceToDisplay = $rate->getRate($this->id.'-'.$i, $price, $carrierName, $rateDescription, 0, 0, 0);
                      $i++;
                      $this->add_rate($priceToDisplay);
                    }
                  }
                }
              }
        }
    }
}

function my_custom_dashicons_css() {
  echo '
  <style>
  .dashicons-loading {
      display: inline-block;
      animation: spin 2s linear infinite;
  }

  @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
  }
  </style>
  ';
}

function add_shipit_method($methods) {
    $methods[] = 'Shipit_Shipping';
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_shipit_method');

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
  if (class_exists('Shipit_Shipping')) add_action('woocommerce_before_cart', 'refreshShippingRates');
  function refreshShippingRates() {
    global $woocommerce;
    $shop_page_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
    $packages =  $woocommerce->cart->get_shipping_packages();
    $shipping = new Shipit_Shipping();
    if($_SERVER['HTTP_REFERER'] == $shop_page_url) $shipping->calculate_shipping($packages[0]);
  }

}


// Hook into that action that'll fire every_one_hour
add_action( 'wp_every_one_hour_cron_action', 'wp_cron_send_orders_to_shipit' );

function wp_cron_send_orders_to_shipit() {
  configureIntegrationSetting();

  if (get_option('woocommerce_shipit_settings')['cron'] == 'yes') {
    $order_ids = wc_get_orders(array('date_paid' => '>' . ( time() - get_option('woocommerce_shipit_settings')['frequency'] * 3 ), 'status' => 'completed', 'limit' => -1, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'ids'));
    process_orders_pending_shipit($order_ids);
  }
}

add_action('admin_init', 'shipit_redirect_to_settings');
function shipit_redirect_to_settings() {
    if (get_option('shipit_do_activation_redirect', false)) {
        delete_option('shipit_do_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            wp_redirect(admin_url('admin.php?page=settings_api'));
            exit;
        }
    }
}

function process_orders_pending_shipit($post_ids) {

  $logger = wc_get_logger();
  $logger->info( '--------INI Ejecutando cron job de ordenes pendientes enviadas a shipit process_orders_pending_shipit INI-------------' );
  if (empty($post_ids) ) return false;

  global $attach_download_dir, $attach_download_file;
  $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
  $integration = new Integration(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
  $opit = new Opit(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
  $measuresCollection = new MeasureCollection();
  $helper = new WoocommerceSettingHelper(get_option('woocommerce_weight_unit'), get_option('woocommerce_dimension_unit'));

  $measureConversion = $helper->getMeasureConversion();
  $weightConversion = $helper->getWeightConversion();
  $company = $core->administrative();
  $skus = array();
  if ($company->service->name == 'fulfillment') {
    $skus = $core->skus();
  }

  $insuranceSetting = $core->insurance();
  $opitSetting = $opit->setting();

  $i = 0;
  foreach ($post_ids as $post_id) {
    $i++;
    $order = wc_get_order($post_id);
    $same_day = Courier::sameDayCourier($order->get_shipping_method(), get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
    $shipit = getDeliveryType($order);
    if ($order->is_paid()){
      $notes = wc_get_order_notes(array( 'order_id' => $post_id, 'order_by' => 'date_created', 'type' => 'internal', 'order' => 'DESC' ));
      if( ( $shipit == false) && ( $notes[0]->content == 'El estado del pedido cambió de Procesando a Completado.' || $notes[0]->content == 'El estado del pedido cambió de Pendiente de pago a Procesando.' || $notes[0]->content == 'El estado del pedido cambió de Fallido a Procesando.' )){
        $logger->info( '------------------ENVIANDO DESDE NUEVA FUNCIONALIDAD ORDER : ' . $post_id . '------------------------');
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
          $parcel = $measuresCollection->calculate();
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
              $measure = new Measure((float)$skuObject['height'], (float)$skuObject['width'], (float)$skuObject['length'], (float)$skuObject['weight'], (int)$cartItem['qty'], $parcel['cubication_id']);
              $measuresCollection->setMeasures($measure->buildBoxifyRequest());
            }
          } else {
            $height = $helper->packingSetting($product->get_height(), $forms->settings, 'height', 'packing_set', $measureConversion);
            $width = $helper->packingSetting($product->get_width(), $forms->settings, 'width', 'packing_set', $measureConversion);
            $length = $helper->packingSetting($product->get_length(), $forms->settings, 'length', 'packing_set', $measureConversion);
            $weight = $helper->packingSetting($product->get_weight(), $forms->settings, 'weight', 'weight_set', $weightConversion);

            $measure = new Measure((float)$height, (float)$width, (float)$length, (float)$weight, (int)$cartItem['quantity'], $parcel['cubication_id']);
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
        $order_payload = create_order($order, $parcel, $company, $inventory, $communeName, (int)filter_var($order->get_shipping_state(), FILTER_SANITIZE_NUMBER_INT), $productCategories, $insuranceSetting );
        if ($same_day) {
          $same_day_request_payload[] = $order_payload->build();
        } else {
          $request_payload[] = $order_payload->build();
        }
        $request_payload[] = $order_payload->build();
        $processed_ids[] = $post_id;
     }
   }
  }

  $sellerSetting = $integration->setting();
  $core_cli = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
  $core_cli_v2 = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v2');
  if ($same_day) {
    ($sellerSetting->same_day->automatic_delivery) ? $core_cli->massiveShipments(['shipments' => $same_day_request_payload]) : $integration->massiveOrders(['orders' => $same_day_request_payload]);
  } else {
    ($sellerSetting->automatic_delivery) ? $core_cli->massiveShipments(['shipments' => $request_payload]) : $integration->massiveOrders(['orders' => $request_payload]);
  }
  ($company->platform_version == 2) ? $core_cli_v2->massivePackages(['packages' => $request_payload]) : $integration->massiveOrders(['orders' => $request_payload]);

  foreach ($processed_ids as $key => $order_id) {
    $order = new WC_Order($order_id);
    $order->add_order_note('Se ha enviado a Shipit correctamente mediante acción complementaria');
  }

  $logger->info( '--------FIN cron job de ordenes pendientes enviadas a shipit process_orders_pending_shipit FIN-------------' );
  //wp_clear_scheduled_hook('every_one_hour');

}
