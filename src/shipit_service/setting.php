<?php
  if ( ! function_exists( 'get_plugins' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
  }
  class Setting {
    public $active = '';
    public $delivery_time = '';
    public $packing_type = '';
    public $packing_set = '';
    public $packing_width = '';
    public $packing_height = '';
    public $packing_length = '';
    public $packing_set_weight = '';
    public $packing_weight = '';
    public $price_defined_enable = '';
    public $all_communes_enable = '';
    public $price_defined_specific_communes = '';
    public $sub_price_from = '';
    public $sub_price_enable = '';
    public $sub_price_specific_communes = '';
    public $free_shipping_from = '';
    public $free_shipping_specific_communes = '';
    public $complementary_shipping_enable = '';
    public $complementary_shipping_frequency = '';
    public $php_version = '';
    public $wordpress_version = '';
    public $plugins = '';


    public function __construct( $active
                                , $delivery_time
                                , $packing_type
                                , $packing_set
                                , $packing_width
                                , $packing_height
                                , $packing_length
                                , $packing_set_weight
                                , $packing_weight
                                , $price_defined_enable
                                , $all_communes_enable
                                , $price_defined_specific_communes
                                , $sub_price_from
                                , $sub_price_enable
                                , $sub_price_specific_communes
                                , $free_shipping_from
                                , $free_shipping_specific_communes
                                , $complementary_shipping_enable
                                , $complementary_shipping_frequency
                                , $php_version ) {
      $this->active = $active;
      $this->delivery_time = $delivery_time;
      $this->packing_type = $packing_type;
      $this->packing_set = $packing_set;
      $this->packing_width = $packing_width;
      $this->packing_height = $packing_height;
      $this->packing_length = $packing_length;
      $this->packing_set_weight = $packing_set_weight;
      $this->packing_weight = $packing_weight;
      $this->price_defined_enable = $price_defined_enable;
      $this->all_communes_enable = $all_communes_enable;
      $this->price_defined_specific_communes = $price_defined_specific_communes;
      $this->sub_price_from = $sub_price_from;
      $this->sub_price_enable = $sub_price_enable;
      $this->sub_price_specific_communes = $sub_price_specific_communes;
      $this->free_shipping_from = $free_shipping_from;
      $this->free_shipping_specific_communes = $free_shipping_specific_communes;
      $this->complementary_shipping_enable = $complementary_shipping_enable;
      $this->complementary_shipping_frequency = $complementary_shipping_frequency;
      $this->php_version = phpversion();
      $this->wordpress_version = get_bloginfo( 'version' );
      $this->plugins = get_plugins();
    }

    static function getSetting() {
      return array(
        'active' => isset(get_option('woocommerce_shipit_settings')['enabled']) ? get_option('woocommerce_shipit_settings')['enabled'] : '',
        'delivery_time' => isset(get_option('woocommerce_shipit_settings')['time_despach']) ? get_option('woocommerce_shipit_settings')['time_despach'] : '',
        'packing_type' => isset(get_option('woocommerce_shipit_settings')['type_packing']) ? get_option('woocommerce_shipit_settings')['type_packing'] : '',
        'packing_set' => isset(get_option('woocommerce_shipit_settings')['packing_set']) ? get_option('woocommerce_shipit_settings')['packing_set'] : '',
        'packing_width' => isset(get_option('woocommerce_shipit_settings')['width']) ? get_option('woocommerce_shipit_settings')['width'] : '',
        'packing_height' => isset(get_option('woocommerce_shipit_settings')['height']) ? get_option('woocommerce_shipit_settings')['height'] : '',
        'packing_length' => isset(get_option('woocommerce_shipit_settings')['length']) ? get_option('woocommerce_shipit_settings')['length'] : '',
        'packing_set_weight' => isset(get_option('woocommerce_shipit_settings')['weight_set']) ? get_option('woocommerce_shipit_settings')['weight_set'] : '',
        'packing_weight' => isset(get_option('woocommerce_shipit_settings')['weight']) ? get_option('woocommerce_shipit_settings')['weight'] : '',
        'price_defined_enable' => isset(get_option('woocommerce_shipit_settings')['active-setup-price']) ? get_option('woocommerce_shipit_settings')['active-setup-price'] : '',
        'all_communes_enable' => isset(get_option('woocommerce_shipit_settings')['all_communes']) ? get_option('woocommerce_shipit_settings')['all_communes'] : '',
        'price_defined_specific_communes' => isset(get_option('woocommerce_shipit_settings')['communes']) ? get_option('woocommerce_shipit_settings')['communes'] : '',
        'sub_price_from' => isset(get_option('woocommerce_shipit_settings')['price-setup']) ? get_option('woocommerce_shipit_settings')['price-setup'] : '',
        'sub_price_enable' => isset(get_option('woocommerce_shipit_settings')['all_free_communes']) ? get_option('woocommerce_shipit_settings')['all_free_communes'] : '',
        'sub_price_specific_communes' => isset(get_option('woocommerce_shipit_settings')['free_communes']) ? get_option('woocommerce_shipit_settings')['free_communes'] : '',
        'free_shipping_from' => isset(get_option('woocommerce_shipit_settings')['price']) ? get_option('woocommerce_shipit_settings')['price'] : '',
        'free_shipping_specific_communes' => isset(get_option('woocommerce_shipit_settings')['free_communes_for_price']) ? get_option('woocommerce_shipit_settings')['free_communes_for_price'] : '',
        'complementary_shipping_enable' => isset(get_option('woocommerce_shipit_settings')['cron']) ? get_option('woocommerce_shipit_settings')['cron'] : '',
        'complementary_shipping_frequency' => isset(get_option('woocommerce_shipit_settings')['frequency']) ? get_option('woocommerce_shipit_settings')['frequency'] : '',
        'php_version' => phpversion(),
        'wordpress_version' => get_bloginfo( 'version' ),
        'plugins' => self::getWordpressPlugins()
        );
    }

    function getActive() {
      return $this->active;
    }

    function getDeliveryTime() {
      return $this->delivery_time;
    }

    function getPackingType() {
      return $this->packing_type;
    }

    function getPackingSet() {
      return $this->packing_set;
    }

    function getPackingWidth() {
      return $this->packing_width;
    }

    function getPackingHeight() {
      return $this->packing_height;
    }

    function getPackingLength() {
      return $this->packing_length;
    }

    function getPackingSetWeight() {
      return $this->packing_set_weight;
    }

    function getPackingWeight() {
      return $this->packing_weight;
    }

    function getPriceDefinedEnable() {
      return $this->price_defined_enable;
    }

    function getAllCommunesEnable() {
      return $this->all_communes_enable;
    }

    function getPriceDefinedSpecificCommunes() {
      return $this->price_defined_specific_communes;
    }

    function getSubPriceFrom() {
      return $this->sub_price_from;
    }

    function getSubPriceEnable() {
      return $this->sub_price_enable;
    }

    function getSubPriceSpeficiCommunes() {
      return $this->sub_price_specific_communes;
    }

    function getComplementaryShippingEnable() {
      return $this->complementary_shipping_enable;
    }

    function getComplementaryShippingFrequency() {
      return $this->complementary_shipping_frequency;
    }

    function getPhpVersion() {
      return $this->php_version;
    }

    function getWordpressVersion() {
      return $this->wordpress_version;
    }

    function getWoocommerceVersion() {
      return $this->woocommerce_version;
    }

    function getPlugins() {
      return $this->plugins;
    }

    static function getWordpressPlugins() {
      $plugins = get_plugins();
      $plugins_string = '';
      foreach ($plugins as $key => $value) {
        if($plugins_string != '') $plugins_string .= ',';
        $plugins_string .= $key.'_'.$value['Version'];
      }
      return $plugins_string;
    }
  }
?>
