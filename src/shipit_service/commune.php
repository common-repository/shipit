<?php
  class Commune {
    public $commune_id;
    public $region_id;
    public $name;

    public function __constructor($commune_id, $region_id, $name) {
      $this->$commune_id = $commune_id;
      $this->$region_id = $region_id;
      $this->$name = $name;
    }

    static function setCommunes($commune_id, $region_id, $name) {
      global $wpdb;
      $wpdb->insert("{$wpdb->prefix}shipit_communes", array(
        'commune_id' => $commune_id,
        'region_id' => $region_id,
        'name' => $name,
        'created_at' => date("Y-m-d H:i:s"),
      ));
    }

    static function massivePopulateCommunes($commune_service, $country_id) {
      global $wpdb;
      $table_name = "{$wpdb->prefix}shipit_communes";
      $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
  
      if ($count == 0 || $count > 1000) {
        // delete all records
          $wpdb->query("TRUNCATE TABLE $table_name");
          $communes = $commune_service->communes($country_id);
          foreach ($communes as $key => $value) {
              SELF::setCommunes($value['id'], $value['id'], $value['name']);
          }
      }
  }

    static function getCommunes() {
      global $wpdb;
      $communes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}shipit_communes" );
      return $communes;
    }

    function getCommuneId() {
      return $this->commune_id;
    }

    function getRegionId() {
      return $this->region_id;
    }

    function getName() {
      return $this->name;
    }

  }
