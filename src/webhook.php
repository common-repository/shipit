<?php
  function configureIntegrationSetting() {
    global $wpdb;
    $integration = new Integration(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
    $hashToken = $wpdb->get_var("SELECT user_pass FROM {$wpdb->prefix}users WHERE user_email = 'hola@shipit.cl' ORDER BY id DESC LIMIT 1");
    $setting = $integration->setting();
    if ($setting == null) return;
    $shipit_setting = Setting::getSetting();
    $cloud_function = new CloudFunction();
    $cloud_function->storeData([
      'name' => 'woocommerce',
      'configuration' => [
        'store_name' => get_bloginfo('name'),
        'show_shipit_checkout' => $setting->show_shipit_checkout,
        'store_local_settings' => $shipit_setting,
        'automatic_delivery' => $setting->automatic_delivery,
        'checkout' => $setting->checkout,
        'same_day' => $setting->same_day
      ]
    ]);

    $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
    update_option( 'shipit_account_country', $core->account()->company->country_id );
    $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
    $country_id = get_option('shipit_account_country');
    Commune::massivePopulateCommunes($core, $country_id);
    $integration = new Integration(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
    $setting = $integration->setting();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $bugsnagToken = isset($setting->bugsnag_token) ? $setting->bugsnag_token : '';
    $encodedToken = base64_encode($bugsnagToken);
    $insert_user = "UPDATE {$wpdb->prefix}user_shipit set bt = '{$encodedToken}' ORDER BY created_at DESC LIMIT 1";
    dbDelta($insert_user);

    return $integration->configure([
      'name' => 'woocommerce',
      'configuration' => [
        'client_id'   => str_replace(' ', '_', get_bloginfo('name')).'_shipit',
        'client_secret' => $hashToken,
        'plugin_url' => get_plugin_data( WP_PLUGIN_DIR.'/shipit/shipit.php' )['Version'],
        'show_shipit_checkout' => $setting->show_shipit_checkout,
        'store_name' => get_bloginfo('name'),
        'automatic_delivery' => $setting->automatic_delivery,
        'checkout' => $setting->checkout,
        'same_day' => $setting->same_day
      ]
    ]);



  }

  function configureWebhookSetting() {
    global $wpdb;
    $baseEncodeUserShipit = $wpdb->get_var("SELECT temp FROM {$wpdb->prefix}user_shipit ORDER BY id DESC LIMIT 1");
    $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
    return $core->setWebhook([
      'webhook' => [
        'package' => [
          'url' => get_site_url().'/wp-json/shipit/orders/',
          'options' => [
            'sign_body' => [
              'required' => false,
              'token' => ''
            ],
            'authorization' => [
              'required' => true,
              'kind' => 'Basic',
              'token' => $baseEncodeUserShipit
            ]
          ]
        ]
      ]
    ]);
  }

  function getShipitConfiguration() {
    if (!get_option('shipit_migration'))
      {
        $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
        $shipitSettings = ["woocommerce_settings_params" => get_option('woocommerce_shipit_settings')];
        $setting = $core->sendLocalSettings($shipitSettings);
      }
  }

  function sendSecondShipitSetting() {
    if (get_option('woocommerce_shipit_settings') == '')
    {
      update_option('woocommerce_shipit_settings', [
        'type_packing' => 'Sin empaque',
        'packing_set' => '2',
        'width' => '10',
        'height' => '10',
        'length' => '10',
        'weight_set' => '2',
        'weight' => '1',
        'cron' => 'no',
        'frequency' => '900'
      ]);
    }

    $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
    $payload = get_option('woocommerce_shipit_settings');
    $payload['rate_from'] = 'woocommerce';
    $payload['packing_set'] = packingSet($payload['packing_set']);
    $payload['weight_set'] = weightSet($payload['weight_set']);
    $payload['frequency'] = frequency($payload['frequency']);
    $core->sendSettingsIntegration($payload);
  
  }

  add_action('admin_post_add_foobar', 'shipit_admin_add_foobar');
  function shipit_admin_add_foobar() {
    global $wpdb;
    if(isset(get_option('shipit_user')['shipit_user']))
    {
      $webhookResponse = configureWebhookSetting();
      $integrationResponse = configureIntegrationSetting();
      if (isset($webhookResponse->webhook) && isset($integrationResponse->configuration)) {
       // shipit_admin_notice__success();
        add_option('shipit_webhook_integration', true);
        getShipitConfiguration();
        sendSecondShipitSetting();  
      } else {
        add_option('shipit_webhook_integration', false);
      //  shipit_admin_notice__error($webhookResponse);
      }
  }
  }

  function packingSet($option) {
    switch ($option) {
      case '1':
        return [1 => 'Sí, utilizar siempre las dimensiones especificadas'];
        break;
      case '2':
        return [2 => 'Sí, cuando falten las dimensiones del producto o no estén configuradas'];
        break;
      case '3':
        return [3 => 'Sí, cuando las dimensiones del producto sean menores que las especificadas'];
        break;
      case '4':
        return [4 => 'Sí, cuando las dimensiones del producto sean mayores que las especificadas'];
        break;
    }
  }

  function weightSet($option) {
    switch ($option) {
      case '1':
        return [1 => 'Sí, utilizar siempre el peso especificado'];
        break;
      case '2':
        return [2 => 'Sí, cuando falte el peso del producto o no esté configurado'];
        break;
      case '3':
        return [3 => 'Sí, cuando el peso del producto sea menor que el especificado'];
        break;
      case '4':
        return [4 => 'Sí, cuando el peso del producto sea mayor que el especificado'];
        break;
    }
  }
  function frequency($option) {
    switch ($option) {
      case '900':
        return [900 => '15 minutos'];
        break;
      case '1800':
        return [1800 => '30 minutos'];
        break;
      case '3600':
        return [3600 => '1 hora'];
        break;
      case '21600':
        return [21600 => '6 horas'];
        break;
      case '86400':
        return [86400 => '24 horas'];
        break;
    }
  }

  add_action('admin_head', 'styling_admin_order_list');
  function styling_admin_order_list() {
    $order_status = 'status-invoiced';
    ?>
    <style>
      .order-status.status-in_preparation {background-color: #58b5f4;color: #fff;}
      .order-status.status-in_route {background-color: #f4cf58;color: #fff;}
      .order-status.status-ready_to_dispatch {background-color: #1f97e7;color: #fff;}
      .order-status.status-dispatched {background-color: #0f7cc5;color: #fff;}
      .order-status.status-failed {background-color: #dd7272;color: #fff;}
      .order-status.status-other {background-color: #484a7d;color: #fff;}
      .order-status.status-by_retired {background-color: #00c2de;color: #fff;}
      .order-status.status-pending {background-color: #cc0000;color: #fff;}
      .order-status.status-at_shipit {background-color: #00c2de;color: #fff;}
      .order-status.status-indemnify{background-color: #484a7d;color: #fff;}
      .order-status.status-delivered {background-color: #04c778;color: #fff;}
      .order-status.status-withdrawn {background-color: #E0B339;color: #fff;}
      .order-status.status-other_shipit {background-color: #3973E0;color: #fff;}
      .order-status.status-cancelled_shipit {background-color: #dd7272;color: #fff;}
      .order-status.status-first_closed_address {background-color: #f4b867;color: #fff;}
      .order-status.status-second_closed_address {background-color: #dd7272;color: #fff;}
      .order-status.status-created {background-color: #58b5f4;color: #fff;}
      .order-status.status-requested {background-color: #3b98e7;color: #fff;}
      .order-status.status-received_for_courier {background-color: #f4cf58;color: #fff;}
      .order-status.status-damaged {background-color: #dd7272;color: #fff;}
      .order-status.status-strayed {background-color: #dd7272;color: #fff;}
      .order-status.status-unreachable_destiny {background-color: #dd7272;color: #fff;}
      .order-status.status-unkown_destinatary {background-color: #dd7272;color: #fff;}
      .order-status.status-reused_by_destinatary {background-color: #dd7272;color: #fff;}
      .order-status.status-incomplete_address {background-color: #dd7272;color: #fff;}
      .order-status.status-unexisting_address {background-color: #dd7272;color: #fff;}
      .order-status.status-back_in_route {background-color: #dd7272;color: #fff;}
      .order-status.status-indemnify_out_of_date {background-color: #dd7272;color: #fff;}
      .order-status.status-retired_by {background-color: #3b98e7;color: #fff;}
      .order-status.status-in_transit {background-color: #f4cf58;color: #fff;}
      .order-status.status-delayed {background-color: #c56b6b;color: #fff;}
      .order-status.status-canceled {background-color: #484a7d;color: #fff;}
      .order-status.status-objected {background-color: #c56b6b;color: #fff;}
    </style>
    <?php
  }

  add_action('init', 'shipit_register_my_new_order_statuses');
  function shipit_register_my_new_order_statuses() {
    register_post_status('wc-in_preparation', array(
      'label' => _x('in_preparation', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('En preparaci&oacute;n <span class="count">(%s)</span>', 'En preparaci&oacute;n<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-in_route', array(
      'label' => _x('in_route', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('En ruta <span class="count">(%s)</span>', 'En ruta<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-delivered', array(
      'label' => _x('delivered', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Entregado <span class="count">(%s)</span>', 'Entregado<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-failed', array(
      'label' => _x('failed', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Fallido <span class="count">(%s)</span>', 'Fallido<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-by_retired', array(
      'label' => _x('by_retired', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Para retiro <span class="count">(%s)</span>', 'Para retiro<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-other', array(
      'label' => _x('other', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop( 'Otros <span class="count">(%s)</span>', 'Otros<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-slope', array(
      'label' => _x('pending', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Pendiente <span class="count">(%s)</span>', 'Pendiente<span class="count">(%s)</span>', 'woocommerce')
    ));
    register_post_status('wc-to_marketplace', array(
      'label' => _x('to_marketplace', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Hacia comercio <span class="count">(%s)</span>', 'Hacia comercio<span class="count">(%s)</span>', 'woocommerce')
    ));
    register_post_status('wc-indemnify', array(
      'label' => _x('indemnify', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('indemnizar <span class="count">(%s)</span>', 'indemnizar<span class="count">(%s)</span>', 'woocommerce')
    ));
    register_post_status('wc-ready_to_dispatch', array(
      'label' => _x('ready_to_dispatch', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('listo para despacho <span class="count">(%s)</span>', 'listo para despacho<span class="count">(%s)</span>', 'woocommerce')
    ));
    register_post_status( 'wc-dispatched', array(
      'label' => _x('dispatched', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Despachado <span class="count">(%s)</span>', 'Despachado<span class="count">(%s)</span>', 'woocommerce')
    ));
    register_post_status('wc-at_shipit', array(
      'label' => _x('at_shipit', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Hacia Shipit <span class="count">(%s)</span>', 'Hacia Shipit<span class="count">(%s)</span>', 'woocommerce')
    ));
    register_post_status('wc-returned', array(
      'label' => _x('returned', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Devuelto <span class="count">(%s)</span>', 'Devuelto<span class="count">(%s)</span>', 'woocommerce')
    ));
    register_post_status('wc-withdrawn', array(
      'label' => _x('withdrawn', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('El env&iacute;o no fue retirado <span class="count">(%s)</span>', 'El env&iacute;o no fue retirado<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-shipit_cancelled', array(
      'label' => _x('shipit_cancelled', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Cancelado shipit <span class="count">(%s)</span>', 'Cancelado shipit<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-shipit_other', array(
      'label' => _x('shipit_other', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Otro shipit <span class="count">(%s)</span>', 'Otro shipit<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-created', array(
      'label' => _x('created', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Envio creado <span class="count">(%s)</span>', 'Envio creado<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-requested', array(
      'label' => _x('requested', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Retiro Solicitado <span class="count">(%s)</span>', 'Retiro Solicitado<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-received_for_courier', array(
      'label' => _x('received_for_courier', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Recibido por courier <span class="count">(%s)</span>', 'Recibido por courier<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-first_closed_address', array(
      'label' => _x('first_closed_address', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Primer domicilio cerrado <span class="count">(%s)</span>', 'Primer domicilio cerrado<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-damaged', array(
      'label' => _x('damaged', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Dañado <span class="count">(%s)</span>', 'Dañado<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-strayed', array(
      'label' => _x('strayed', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Extraviado <span class="count">(%s)</span>', 'Extraviado<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-unreachable_destiny', array(
      'label' => _x('unreachable_destiny', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Destino sin cobertura <span class="count">(%s)</span>', 'Destino sin cobertura<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-unkown_destinatary', array(
      'label' => _x('unkown_destinatary', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Destinatario Desconocido <span class="count">(%s)</span>', 'Destinatario Desconocido<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-reused_by_destinatary', array(
      'label' => _x('reused_by_destinatary', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Rehusado por Destinatario <span class="count">(%s)</span>', 'Rehusado por Destinatario<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-incomplete_address', array(
      'label' => _x('incomplete_address', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Dirección Incompleta <span class="count">(%s)</span>', 'Dirección Incompleta<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-second_closed_address', array(
      'label' => _x('second_closed_address', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('2do domicilio cerrado <span class="count">(%s)</span>', '2do domicilio cerrado<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-back_in_route', array(
      'label' => _x('back_in_route', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('En proceso de devolución <span class="count">(%s)</span>', 'En proceso de devolución<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-indemnify_out_of_date', array(
      'label' => _x('indemnify_out_of_date', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Reembolso fuera de plazo <span class="count">(%s)</span>', 'Reembolso fuera de plazo<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-retired_by', array(
      'label' => _x('retired_by', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Retirado por shipit <span class="count">(%s)</span>', 'Retirado por shipit<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-in_transit', array(
      'label' => _x('in_transit', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Courier está traslando el paquete <span class="count">(%s)</span>', 'Courier está traslando el paquete<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-in_transit', array(
      'label' => _x('in_transit', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Courier está traslando el paquete <span class="count">(%s)</span>', 'Courier está traslando el paquete<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-delayed', array(
      'label' => _x('delayed', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Atrasado <span class="count">(%s)</span>', 'Atrasado<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-canceled', array(
      'label' => _x('canceled', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Cancelado por shipit <span class="count">(%s)</span>', 'Cancelado por shipit<span class="count">(%s)</span>', 'woocommerce')
    ));

    register_post_status('wc-returned_pending', array(
      'label' => _x('returned_pending', 'Order status', 'woocommerce'),
      'public' => true,
      'exclude_from_search' => false,
      'show_in_admin_all_list' => true,
      'show_in_admin_status_list' => true,
      'label_count' => _n_noop('Devolución por confirmar <span class="count">(%s)</span>', 'Devolución por confirmar<span class="count">(%s)</span>', 'woocommerce')
    ));

  }

  add_filter('wc_order_statuses', 'shipit_my_new_wc_order_statuses');

  function shipit_my_new_wc_order_statuses($order_statuses) {
    $order_statuses['wc-in_preparation'] = _x('En preparacion', 'Order status', 'woocommerce');
    $order_statuses['wc-in_route'] = _x('En ruta', 'Order status', 'woocommerce');
    $order_statuses['wc-delivered'] = _x('Entregado', 'Order status', 'woocommerce');
    $order_statuses['wc-failed'] = _x('Fallido', 'Order status', 'woocommerce');
    $order_statuses['wc-by_retired'] = _x('Para Retiro', 'Order status', 'woocommerce');
    $order_statuses['wc-other'] = _x('Otro', 'Order status', 'woocommerce');
    $order_statuses['wc-slope'] = _x('Pendiente', 'Order status', 'woocommerce');
    $order_statuses['wc-to_marketplace'] = _x('Hacia comercio', 'Order status', 'woocommerce');
    $order_statuses['wc-indemnify'] = _x('indemnizar', 'Order status', 'woocommerce');
    $order_statuses['wc-ready_to_dispatch'] = _x('listo para despacho', 'Order status', 'woocommerce');
    $order_statuses['wc-dispatched'] = _x('Despachado', 'Order status', 'woocommerce');
    $order_statuses['wc-at_shipit'] = _x('Hacia Shipit', 'Order status', 'woocommerce');
    $order_statuses['wc-returned'] = _x('Devolucion', 'Order status', 'woocommerce');
    $order_statuses['wc-withdrawn'] = _x('El envío no fue retirado', 'Order status', 'woocommerce');
    $order_statuses['wc-shipit_cancelled'] = _x('Cancelado shipit', 'Order status', 'woocommerce');
    $order_statuses['wc-shipit_other'] = _x('Otro shipit', 'Order status', 'woocommerce');
    $order_statuses['wc-created'] = _x('Envio Creado', 'Order status', 'woocommerce');
    $order_statuses['wc-requested'] = _x('Retiro Solicitado', 'Order status', 'woocommerce');
    $order_statuses['wc-damaged'] = _x('Dañado', 'Order status', 'woocommerce');
    $order_statuses['wc-strayed'] = _x('Extraviado', 'Order status', 'woocommerce');
    $order_statuses['wc-back_in_route'] = _x('En proceso de devolución', 'Order status', 'woocommerce');
    $order_statuses['wc-retired_by'] = _x('Retirado por shipit', 'Order status', 'woocommerce');
    $order_statuses['wc-in_transit'] = _x('Courier está traslando el paquete', 'Order status', 'woocommerce');
    $order_statuses['wc-delayed'] = _x('Atrasado', 'Order status', 'woocommerce');
    $order_statuses['wc-canceled'] = _x('Cancelado por shipit', 'Order status', 'woocommerce');
    $order_statuses['wc-returned_pending'] = _x('Devolución por confirmar', 'Order status', 'woocommerce');
    $order_statuses['wc-received_for_courier'] = _x('Recibido por courier', 'Order status', 'woocommerce');
    $order_statuses['wc-first_closed_address'] = _x('Primer domicilio cerrado', 'Order status', 'woocommerce');
    $order_statuses['wc-second_closed_address'] = _x('2do domicilio cerrado', 'Order status', 'woocommerce');
    $order_statuses['wc-unreachable_destiny'] = _x('Destino sin cobertura', 'Order status', 'woocommerce');
    $order_statuses['wc-unkown_destinatary'] = _x('Destinatario Desconocido', 'Order status', 'woocommerce');
    $order_statuses['wc-reused_by_destinatary'] = _x('Rehusado por Destinatario', 'Order status', 'woocommerce');
    $order_statuses['wc-incomplete_address'] = _x('Dirección Incompleta', 'Order status', 'woocommerce');
    $order_statuses['wc-indemnify_out_of_date'] = _x('Reembolso fuera de plazo', 'Order status', 'woocommerce');
    return $order_statuses;
  }

  add_action('rest_api_init', 'my_register_route');
  function my_register_route() {
    register_rest_route('shipit', 'orders', array(
      'methods' => 'POST, PUT, PATCH',
      'callback' => 'shipit_action_woocommerce_update_order' ,
      'permission_callback' => function() {
        return current_user_can('edit_others_posts');
      }
    ));
  }

  function shipit_action_woocommerce_update_order(WP_REST_Request $request) {
    $custom_status['pending'] = 'withdrawn';
    $custom_status['other'] = 'shipit_other';
    $custom_status['cancelled'] = 'shipit_cancelled';
    $param = $request->get_body();
    $json = json_decode($param);
    $status = in_array($json->status, array('pending','cancelled','other')) ? $custom_status[$json->status] : $status = $json->status;
    $int = (int)preg_replace('/\D/ui','',$json->reference);
    $order = new WC_Order($int);
    $statuses = wc_get_order_statuses();
    if (isset($statuses['wc-'.$status])) $order->update_status($status, 'Estado actualizado por Shipit');
    return rest_ensure_response($int);
  }

  add_action('rest_api_init', 'my_register_route_update_email');
  function my_register_route_update_email() {
    register_rest_route('shipit', 'email', array(
      'methods' => 'POST, PUT, PATCH',
      'callback' => 'shipit_action_woocommerce_update_email' ,
      'permission_callback' => function() {
        return current_user_can('edit_others_posts');
      }
    ));
  }

  function shipit_action_woocommerce_update_email(WP_REST_Request $request) {
    $param = $request->get_body();
    $json = json_decode($param);
    $email =  $json->email;
    $shipit_user_data = get_option('shipit_user');

    if (is_array($shipit_user_data)) {
        $shipit_user_data['shipit_user'] = $email;
        update_option('shipit_user', $shipit_user_data);
    } 
    return rest_ensure_response($email);
  }


  add_action('rest_api_init', 'my_register_emergency_rates_route');

  function my_register_emergency_rates_route() {
    register_rest_route('shipit', 'emergency_rates', array(
      'methods' => 'POST, PUT, PATCH',
      'callback' => 'shipit_action_update_emergency_rates' ,
      'permission_callback' => function() {
        return current_user_can('edit_others_posts');
      }
    ));
  }

  function shipit_action_update_emergency_rates(WP_REST_Request $request) {
    global $wpdb;
    $response = json_decode($request->get_body());
    $emergency_rates_response = $response->configuration->rates->zones;
    EmergencyRate::deleteRates($wpdb);
    foreach ($emergency_rates_response as $key => $value) {
      $emergency_rate = new EmergencyRate($key, $value);
      $emergency_rate->saveRate($wpdb);
    }
  }

  add_action('rest_api_init', 'my_register_local_setting');

  function my_register_local_setting() {
    register_rest_route('shipit', 'local_setting', array(
      'methods' => 'GET',
      'callback' => 'shipit_action_get_local_setting' ,
      'permission_callback' => function() {
        return current_user_can('edit_others_posts');
      }
    ));
  }

  function shipit_action_get_local_setting(WP_REST_Request $request) {
    $integration = new Integration(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
    $setting = $integration->setting();
    $shipit_setting = Setting::getSetting();
    return json_encode([
      'name' => 'woocommerce',
      'configuration' => [
        'store_name' => get_bloginfo('name'),
        'store_local_settings' => $shipit_setting,
        'checkout' => $setting->checkout
      ]
    ]);
  }


  function shipit_admin_notice__success() {
    ?>
    <div class="notice notice-success is-dismissible">
    <p><?php _e('Credenciales enviadas correctamente a Shipit', 'sample-text-domain'); ?></p>
    </div>
    <?php
  }

  function shipit_admin_notice__error($webhookResponse) {
	 $errorMessage = 'Hubo un error con el envio de las credenciales';
	 if (isset($webhookResponse->state)) {
		 if ($webhookResponse->state == 'error' ) {
			 $errorMessage = $webhookResponse->message;
		 }
	 }
    ?>
    <div class="notice notice-error is-dismissible">
    <p><?php _e($errorMessage, 'sample-text-domain'); ?></p>
    </div>
    <?php
  }

  register_activation_hook(__FILE__, 'shipit_install_cleancache');

  function shipit_install_cleancache() {
    wp_cache_delete("clp_usd_shipit", "shipit");
  }

  function add_clp_paypal_valid_currency($currencies) {
    array_push($currencies, 'CLP');
    return $currencies;
  }

  add_filter('woocommerce_paypal_supported_currencies', 'add_clp_paypal_valid_currency');

  function convert_clp_to_usd($paypal_args) {
    $shipit_group = "shipit";
    $shipit_expire = 604800;
    if ($paypal_args['currency_code'] == 'CLP') {
      $currency_value = wp_cache_get('clp_usd_shipit', $shipit_group);
      if ($currency_value === false) {
        $json = wp_remote_get('https://free.currconv.com/api/v7/convert?q=USD_CLP&compact=ultra&apiKey=1379232bb33f7020ad47', $args = array());
        $exchangeRates = json_decode($json['body']);
        $currency_value = (int)$exchangeRates->USD_CLP;
      }
      wp_cache_set('clp_usd_shipit', $currency_value, $shipit_group, $shipit_expire);
      $convert_rate = $currency_value;
      $paypal_args['currency_code'] = 'USD';
      $i = 1;
      while (isset($paypal_args['amount_' . $i])) {
        $paypal_args['amount_' . $i] = round($paypal_args['amount_' . $i] / $convert_rate, 2);
        ++$i;
      }
    }
    return $paypal_args;
  }

  add_filter('woocommerce_paypal_args', 'convert_clp_to_usd');
?>