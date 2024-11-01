<?php
  class Boxify {
    public $url = '';
    public $email = '';
    public $token = '';
    public $headers = '';
    public $base = '';
    public $core = '';
    public $cubone = array();

    public function __construct() {
      $this->base = 'https://api.shipit.cl';
      $this->core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
      $this->headers = array(
        'Content-Type' => 'application/json',
        'X-Shipit-Email' => get_option('shipit_user')['shipit_user'],
        'X-Shipit-Access-Token' => get_option('shipit_user')['shipit_token'],
        'Accept' => 'application/vnd.shipit.v4'
      );
    }

    function calculate($shipment = array()) {
      $client = new HttpClient($this->base . '/v/cubone/pack', $this->headers);
      $response = $client->post($shipment);
      $data = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->core->mixPanelLogger('boxify calculate error', $requestToArray);
      } else {
        $data = json_decode($response['body']);
      }


      $this->cubone['length'] = $data->packing_measures->length;
      $this->cubone['width'] = $data->packing_measures->width;
      $this->cubone['height'] = $data->packing_measures->height;
      $this->cubone['weight'] = $data->packing_measures->weight;
      $this->cubone['cubication_id'] = $data->cubication->id;


      return $this->cubone;
    }
  }
?>
