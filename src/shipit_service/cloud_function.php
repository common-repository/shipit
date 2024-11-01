<?php
  class CloudFunction {
    public $headers = '';
    public $base = '';

    public function __construct() {
      $this->base = 'https://us-central1-csshipit.cloudfunctions.net';
      $this->headers = array(
        'Content-Type' => 'application/json'
      );
    }


    function storeData($data = array()) {
      $client = new HttpClient($this->base . '/woocommerce-stores-data', $this->headers);
      $response = $client->post($data);
      $response_decode = array();
      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
      } else {
        $response_decode = json_decode($response['body']);
      }
      return $response_decode;
    }
  }
?>
