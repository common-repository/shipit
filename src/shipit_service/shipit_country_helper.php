<?php
class ShipitCountryHelper {
  static function getCountrySettings() {
    $shipit_account_country = get_option('shipit_account_country');
    $shipit_country_settings = array();
    switch ($shipit_account_country) {
      case 2 :
        $shipit_country_settings['label_name'] = 'Municipio o Delegación';
        $shipit_country_settings['woocommerce_default_country'] = 'MX';
        break;
      default :
        $shipit_country_settings['label_name'] = 'Comunas';
        $shipit_country_settings['woocommerce_default_country'] = 'CL';
    }
    return $shipit_country_settings;
  }
}
?>
