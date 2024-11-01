<?php
  class Integration {
    public $email = '';
    public $token = '';
    public $headers = '';
    public $base = '';
    public $core = '';

    public function __construct($email, $token) {
      $this->base = 'https://orders.shipit.cl/v';
      $this->email = $email;
      $this->token = $token;
      $this->core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
      $this->headers = array(
        'Content-Type' => 'application/json',
        'X-Shipit-Email' => $email,
        'X-Shipit-Access-Token' => $token,
        'Accept' => 'application/vnd.orders.v1'
      );
    }

    function setting() {
      $client = new HttpClient($this->base . '/integrations/seller/woocommerce', $this->headers);
      $response = $client->get();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->core->mixPanelLogger('integration setting error', $requestToArray);
      }
      elseif (property_exists(json_decode($response['body']), 'configuration'))
        return json_decode($response['body'])->configuration;
      elseif (property_exists(json_decode($response['body']), 'error'))
        echo json_decode($response['body'])->error;
    }

    function configure($setting = array()) {
      $client = new HttpClient($this->base . '/integrations/configure', $this->headers);
      $response = $client->put($setting);
      $setting = array();

      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->core->mixPanelLogger('integration configure error', $requestToArray);
      } else {
        $setting = json_decode($response['body']);
      }
      return $setting;
    }

    function orders($order = array()) {
      $client = new HttpClient($this->base . '/orders', $this->headers);
      $reference = $order['order']['reference'];
      $response = $client->post($order);
      $order = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->core->mixPanelLogger('integration order error', $requestToArray);
      } else {
        $order = json_decode($response['body']);
      }

      return $order;
    }

    function massiveOrders($order = array()) {
      $client = new HttpClient($this->base . '/orders/massive', $this->headers);
      $response = $client->post($order);
      $order = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->core->mixPanelLogger('integration massive orders error', $requestToArray);
      } else {
        $order = json_decode($response['body']);
      }
      return $order;
    }
  }
?>
