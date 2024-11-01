<?php
  class Opit {
    public $email = '';
    public $token = '';
    public $headers = '';
    public $base = '';
    public $core = '';

    public function __construct($email, $token) {
      $this->base = 'http://api.shipit.cl/v';
      $this->email = $email;
      $this->token = $token;
      $this->core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
      $this->headers = array(
        'Content-Type' => 'application/json',
        'X-Shipit-Email' => $email,
        'X-Shipit-Access-Token' => $token,
        'Accept' => 'application/vnd.shipit.v4'
      );
    }

    function setting() {
      $client = new HttpClient($this->base . '/settings/1', $this->headers);
      $response = $client->get();
      $setting = array();

      if (is_wp_error($response)) {
        echo 'Error al conectar con API.';
        $requestToArray = json_decode(json_encode($response), true);
        $this->core->mixPanelLogger('opit setting error', $requestToArray);
      } else {
        $setting = json_decode($response['body'])->configuration->opit;
      }
      return $setting;
    }
  }
?>
