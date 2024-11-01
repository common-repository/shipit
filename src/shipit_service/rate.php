<?php
  class Rate {
    public $email = '';
    public $token = '';
    public $cart = '';
    public $checkout_price = 0;
    public $multiCourierEnabled = false;
    public $parcel = array();
    public $destinyId = 0;
    public $success_responses_code = array(200,201,202,203,204);
    public $core = '';
    public $base = '';
    public $headers = array();


    public function __construct($email, $token) {
      $this->base = 'http://api.shipit.cl/v';
      $this->email = $email;
      $this->token = $token;
      $this->core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
      $this->headers = array(
        'Accept' => 'application/vnd.shipit.v4',
        'Content-Type' => 'application/json',
        'X-Shipit-Email' => $email,
        'X-Shipit-Access-Token' => $token
      );
    }

    function calculate() {
      return $this->getMultiCourierEnabled() ? $this->rates() : $this->prices();
    }

    function prices() {
      $client = new HttpClient($this->base . '/rates', $this->headers);
	    global $wpdb;
      $response = $client->post($this->getParcel());
      $prices = array();
	    if (!in_array(wp_remote_retrieve_response_code($response), $this->success_responses_code)) {
        if (!isset($response->errors)) {
          $jsonBody = json_decode($response['body']);
          if (isset($jsonBody->debtor)) return $prices = [];
        }
        $requestToArray = json_decode(json_encode($response), true);
        $this->core->mixPanelLogger('rate prices error', $requestToArray);
        if(wp_remote_retrieve_response_code($response) == 400) {
          return $prices = [];
        }
        $emergencyRate = new EmergencyRate();
        //UNCOMMENT AFTER TESTING
        //$emergencyRate->saveRequest($wpdb, $this->getParcel());
        $rates = $emergencyRate->getEmergencyRate($wpdb, $this->getDestinyId());
        return json_decode($rates)->prices;
      } else {
        $prices = json_decode($response['body'])->prices;
      }
      return $prices;
    }

    function rates() {
      global $wpdb;
      $client = new HttpClient($this->base . '/rates', $this->headers);
      $response = $client->post($this->getParcel());
      $prices = array();
      $data = array();
      if (!in_array(wp_remote_retrieve_response_code($response), $this->success_responses_code)) {
        if (!isset($response->errors)) {
          $jsonBody = json_decode($response['body']);
          if (isset($jsonBody->debtor)) return $prices = [];
        }
        $requestToArray = json_decode(json_encode($response), true);
        $this->core->mixPanelLogger('rate rates error', $requestToArray);
        if(wp_remote_retrieve_response_code($response) == 400) {
          return $prices = [];
        }
        $emergencyRate = new EmergencyRate();
        //UNCOMMENT AFTER TESTING
        //$emergencyRate->saveRequest($wpdb, $this->getParcel());
        $rates = $emergencyRate->getEmergencyRate($wpdb, $this->getDestinyId());
        return json_decode($rates)->prices;
      } else {
        $prices = json_decode($response['body'])->prices;
        $couriers = array_unique(array_map(function($price) {
          return $price->original_courier;
        }, $prices));
        for ($index = 0; $index < count($couriers); $index++) {
          foreach ($prices as $price) {
            if ($price->original_courier == $couriers[$index]) {
              if ($price->price == 0 && $price->courier->name == 'Despacho normal a domicilio') {
                $data[] = json_decode($response['body'])->lower_price;
                $data[0]->courier->name = 'shipit';
                return $data;
              }
              $couriers = array_slice($couriers, $index + 1);
              $data[] = $price;
              if (count($couriers) === 0) break;
            }
          }
        }
      }
      return $data;
    }

    function getDestinyId() {
      return $this->destinyId;
    }

    function setDestinyId($destinyId) {
      $this->destinyId = $destinyId;
    }

    function getMultiCourierEnabled() {
      return $this->multiCourierEnabled;
    }

    function setMultiCourierEnabled($multiCourierEnabled = false) {
      $this->multiCourierEnabled = $multiCourierEnabled;
    }

    function getsessionInfo() {
      $ci = new ClientSession();
      return $ci->get_info();
    }

    function getParcel() {
      return array(
        'parcel' => array(
          'height' => $this->parcel['height'],
          'width' => $this->parcel['width'],
          'length' => $this->parcel['length'],
          'weight' => $this->parcel['weight'],
          'destiny_id' => $this->getDestinyId(),
          'type_of_destiny' => 'domicilio',
          'rate_from' => 'woocommerce',
          'from_cart' => $this->cart,
          'checkout_price' => (float)$this->checkout_price,
          'client_info' => $this->getsessionInfo()
        )
      );
    }

    function setParcel($parcel = array(), $cart = false, $checkout_price = 0) {
      $this->parcel = $parcel;
      $this->cart = $cart;
      $this->checkout_price = $checkout_price;
    }

    function getRateDescription($checkout, $description, $woocommerceSetting) {
      return array($description);
    }

    function getFreeShipment($freeShipmentByTotalOrderPrice, $woocommerceSetting) {
      $shipit_country_settings = ShipitCountryHelper::getCountrySettings();
      $woocommerce_default_country = $shipit_country_settings['woocommerce_default_country'];
      $freeDestinies = [];
      $freeDestiniesByPrice = [];
      // RETURN NON FREE PRICE IF FREE DESTINIES OR FREE DESTINIES BY PRICES IS EMPTY
      if ($freeDestinies == '' && $freeDestiniesByPrice == '') return false;
      // RETURN FREE SHIPMENT PRICE BASED ON SPECIFIC COMMUNES OR TOTAL CART PRICE OR SPECIFIC COMMUNES WITH PRICE
      if ($freeDestinies != '') {
        return in_array($woocommerce_default_country.strval($this->getDestinyId()), $freeDestinies, TRUE);
      } elseif ($freeDestiniesByPrice != '' && $freeShipmentByTotalOrderPrice == true) {
        return in_array($woocommerce_default_country.strval($this->getDestinyId()), $freeDestiniesByPrice, TRUE);
      } else {
        return false;
      }
    }

    function getRate($id, $price, $carrierName, $rateDescription, $specificDestinyPrice, $freeShipment, $woocommerceSetting) {
      $rate = $price->price;

      return array(
        'id'    => $id,
        'label' => $carrierName,
        'cost'  => $rate,
        'meta_data' => $rateDescription
      );
    }

    function executeRateDequeue() {
      global $wpdb;
      $emergencyRate = new EmergencyRate();
      $emergencyRate->dequeue($wpdb, 5000, $this->headers, $this->base, $this->success_responses_code, 'woocommerce') ;
    }
  }
?>
