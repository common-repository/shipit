<?php
  class Core {
    public $url = '';
    public $email = '';
    public $token = '';
    public $headers = '';
    public $base = '';

    public function __construct($email, $token, $version) {
      $this->base = 'https://api.shipit.cl/v';
      $this->email = $email;
      $this->token = $token;
      $this->headers = array(
        'Content-Type' => 'application/json',
        'X-Shipit-Email' => $email,
        'X-Shipit-Access-Token' => $token,
        'Accept' => 'application/vnd.shipit.' . $version
      );
    }

    function mixPanelLogger($event, $properties = array()) {
      $client = new HttpClient($this->base . '/mix_panel_logger/create', $this->headers);
      $response = $client->post(array('event' => $event, 'properties' => $properties));
      $package = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
      } else {
        $package = json_decode($response['body']);
      }
      return $package;
    }

    function packages($package = array()) {
      $client = new HttpClient($this->base . '/packages', $this->headers);
      $response = $client->post($package);
      $package = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('package error', $requestToArray);
      } else {
        $package = json_decode($response['body']);
      }
      return $package;
    }

    function massivePackages($packages = array()) {
      $client = new HttpClient($this->base . '/packages/mass_create', $this->headers);
      $reference = $packages['package']['reference'];
      $response = $client->post($packages);
      $package = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('massive package error', $requestToArray);
      } else {
        $package = json_decode($response['body']);
      }
      return $package;
    }

    function massiveShipments($packages = array()) {
      $client = new HttpClient($this->base . '/shipments/massive/import', $this->headers);
      $response = $client->post($packages);
      $package = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('massive shipments error', $requestToArray);
      } else {
        $package = json_decode($response['body']);
      }
      return $package;
    }

    function orders($order = array()) {
      $client = new HttpClient($this->base . '/orders', $this->headers);
      $reference = $order['order']['reference'];
      $response = $client->post($order);
      $order = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('massive orders error', $requestToArray);
      } else {
        $order = json_decode($response['body']);
      }
      return $order;
    }

    function shipments($shipment = array()) {
      $client = new HttpClient($this->base . '/shipments', $this->headers);
      $reference = $shipment['shipment']['reference'];
      $response = $client->post($shipment);
      $shipment = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('shipments error', $requestToArray);
      } else {
        $shipment = json_decode($response['body']);
      }
      return $shipment;
    }

    function administrative() {
      $client = new HttpClient($this->base . '/setup/administrative', $this->headers);
      $response = $client->get();
      $company = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('administrative error', $requestToArray);
      } else {
        $company = json_decode($response['body']);
      }
      return $company;
    }

    function communes($country_id) {
      $client = new HttpClient($this->base . '/communes?country_id='.$country_id, $this->headers);
      $response = $client->get();
      $communes = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('communes error', $requestToArray);
      } else {
        $communes = (array)json_decode($response['body'], true);
        if (isset($communes['state'])){
          if ($communes['state'] == 'error') {
          $communes = [];
          }		  
        }
      }
      return $communes;
    }

    function skus() {
      $client = new HttpClient($this->base . '/fulfillment/skus/all', $this->headers);
      $response = $client->get();
      $skus = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('skus error', $requestToArray);
      } else {
        $skus = (array) json_decode($response['body'], true);
      }
      return $skus;
    }

    function insurance() {
      $client = new HttpClient($this->base . '/settings/9', $this->headers);
      $response = $client->get();
      $setting = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('insurance error', $requestToArray);
      } else {
        $setting = json_decode($response['body'])->configuration->automatizations->insurance;
      }
      return $setting;
    }

    function setWebhook($webhook = array()) {
      $client = new HttpClient($this->base . '/integrations/webhook', $this->headers);
      $response = $client->patch($webhook);
      $webhook_response = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('set webhook error', $requestToArray);
      } else {       
        $webhook_response = json_decode($response['body']);
      }
      return $webhook_response;
    }

    function testCredential($event, $properties = array()) {
      $client = new HttpClient($this->base . '/mix_panel_logger/create', $this->headers);
      $response = $client->post(array('event' => $event, 'properties' => $properties));
      if($response['response']['code'] == 401) {
        update_option('shipit_auth', false);
        update_option('shipit_webhook', false);
      } else {
        if($response['response']['code'] == 200) {
          update_option('shipit_auth', true);
          update_option('shipit_webhook', true);
        }
      }
      return $response;
    }

    function couriers() {
      $client = new HttpClient($this->base . '/couriers', $this->headers);
      $response = $client->get();
      $setting = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('couriers error', $requestToArray);
      } else {
        $setting = json_decode($response['body']);
      }
      return $setting;
    }

    function settings($webhook = array()) {
      $client = new HttpClient($this->base . '/integrations/settings', $this->headers);
      $response = $client->patch($webhook);
      $webhook_response = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('settings error', $requestToArray);
      } else {
        $webhook_response = json_decode($response['body']);
      }
      return $webhook_response;
    }

    function account() {
      $client = new HttpClient($this->base . '/accounts/information', $this->headers);
      $response = $client->get();
      $setting = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('account error', $requestToArray);
      } else {
        $setting = json_decode($response['body']);
      }
      return $setting;
    }

    function sendLocalSettings($settings = array()) {
      $client = new HttpClient($this->base . '/integrations/woocommerce-settings-migration', $this->headers);
      $response = $client->post($settings);
      $webhook_response = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('settings error', $requestToArray);
      } else {
        add_option('shipit_migration', true);
        $webhook_response = json_decode($response['body']);
      }
    }

    function sendSettingsIntegration($settings = array()) {
      $client = new HttpClient($this->base . '/settings_integration/update', $this->headers);
      $response = $client->patch($settings);
      $webhook_response = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->mixPanelLogger('settings migration error', $requestToArray);
      } else {
        add_option('shipit_seccond_setting', true);
        $webhook_response = json_decode($response['body']);
      }
    }
  }
  
?>
