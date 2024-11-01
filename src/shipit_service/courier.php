<?php
  class Courier {
    public $client = '';
    public $entity = '';
    public $payable = false;
    public $algorithm = '1';
    public $algorithm_days = '0';

    public function __construct($client, $entity, $algorithm, $algorithm_days, $payable = false) {
      $this->client = $client;
      $this->entity = $entity;
      $this->payable = $payable;
      $this->algorithm = $algorithm;
      $this->algorithm_days = $algorithm_days;
    }

    function getCourier() {
      return array(
        'client' => $this->getClient(),
        'entity' => $this->getEntity(),
        'selected' => $this->getClient() != '',
        'payable' => $this->getPayable(),
        'shipment_type' => 'Normal',
        'algorithm' => $this->getAlgorithm(),
        'algorithm_days' => $this->getAlgorithmDays()
      );
    }

    function getClient() {
      return $this->client;
    }

    function getEntity() {
      return $this->entity;
    }

    function getPayable() {
      return $this->payable;
    }

    function getAlgorithm() {
      return $this->algorithm;
    }

    function getAlgorithmDays() {
      return $this->algorithm_days;
    }

    static function getSameDayCouriers($shipit_user, $shipit_token, $version) {
      $core = new Core($shipit_user, $shipit_token, $version);
      $couriers = $core->couriers();
      $same_day_couriers = array();
      foreach ($couriers as $key => $value) {
        if ( $value->services->same_day == true ) array_push($same_day_couriers, strtolower($value->name));
      }

      return $same_day_couriers;
    }

    static function sameDayCourier($courier, $shipit_user, $shipit_token, $version) {
      $couriers = SELF::getSameDayCouriers($shipit_user, $shipit_token, $version);
      return (in_array($courier, $couriers))  ? true : false;
    }
  }
?>
