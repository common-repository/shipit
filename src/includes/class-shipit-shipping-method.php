<?php
include_once('../../../woocommerce/woocommerce.php');
include_once('../../../woocommerce/classes/abstracts/abstract-wc-shipping-method.php');

if (!class_exists('Shipit_Shipping')) {
    class Shipit_Shipping extends WC_Shipping_Method {
        public function __construct() {
            $this->id = 'shipit';
            $this->method_title = __('Shipit');
            $this->method_description = __('Shipit Cotizador');
            $this->init();
        }

        function init() {
            $this->init_form_fields();
            $this->init_settings();
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        function init_form_fields() {
            $shipit_country_settings = ShipitCountryHelper::getCountrySettings();
            $label_name = $shipit_country_settings['label_name'];
            $woocommerce_default_country = $shipit_country_settings['woocommerce_default_country'];
            $this->form_fields = array(
              'enabled' => array(
                'title' => __('Activar', 'dc_raq'),
                'type' => 'checkbox',
                'description' => __('Activar el m&eacute;todo de envío Shipit', 'dc_raq'),
                'default' => 'yes',
              ),
              'time_despach' => array(
                'title'  => __('Tiempo de entrega', 'dc_raq'),
                'type' => 'checkbox',
                'description' => __('Mostrar el tiempo de envío de Shipit', 'dc_raq'),
                'default' => 'yes'
              ),
              'type_packing' => array(
                'title' => 'Tipo empaque',
                'description' => 'Elige el tipo de empaque que tendría tu envio',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => array(
                  'Sin empaque' => 'Sin empaque',
                  'Caja de cartón' => 'Caja de cartón',
                  'Film plástico' => 'Film plástico',
                  'Caja + Burbuja' => 'Caja + Burbuja',
                  'Papel kraft' => 'Papel kraft',
                  'Bolsa Courier + Burbuja' => 'Bolsa Courier + Burbuja',
                  'Bolsa Courier' => 'Bolsa Courier'
                )
              ),
              'packing_set' => array(
                'title' => 'Establecer dimensiones del producto',
                'description' => 'Configure una dimensión predefinida para sus productos al momento de la cotización. Deje en blanco o &quot;0&quot; para omitir.',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default' => 'Sí, cuando falten las dimensiones del producto o no estén configuradas',
                'options' => array(
                  '2' => 'Sí, cuando falten las dimensiones del producto o no estén configuradas',
                  '1' => 'Sí, utilizar siempre las dimensiones especificadas',
                  '3' => 'Sí, cuando las dimensiones del producto sean menores que las especificadas',
                  '4' => 'Sí, cuando las dimensiones del producto sean mayores que las especificadas',
                )
              ),
              'width' => array(
                'title' => __('Ancho', 'woocommerce'),
                'type' => 'number',
                'description' => __('CM.', 'woocommerce'),
                'css'      => 'max-width:150px;',
                'default' => __('10', 'woocommerce')
              ),
              'height' => array(
                'title' => __('Alto', 'woocommerce'),
                'type' => 'number',
                'description' => __('CM.', 'woocommerce'),
                'css'      => 'max-width:150px;',
                'default' => __('10', 'woocommerce')
              ),
              'length' => array(
                'title' => __('Largo', 'woocommerce'),
                'type' => 'number',
                'description' => __('CM.', 'woocommerce'),
                'css'      => 'max-width:150px;',
                'default' => __('10', 'woocommerce')
              ),
              'weight_set' => array(
                'title' => 'Establecer peso del producto',
                'description' => 'Configure un peso predefinido para sus productos al momento de la cotización.',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default'     => 'Sí, cuando el peso del producto falte o no esté configurado',
                'options' => array(
                  '2' => 'Sí, cuando el peso del producto falte o no esté configurado',
                  '1' => 'Sí, utilizar siempre el peso especificado',
                  '3' => 'Sí, cuando el peso del producto sea menor que el especificado',
                  '4' => 'Sí, cuando el peso del producto sea mayor que el especificado'
                )
              ),
              'weight' => array(
                'title' => __('Peso', 'woocommerce'),
                'type' => 'number',
                'description' => __('KG.', 'woocommerce'),
                'css'      => 'max-width:150px;',
                'default' => __('1', 'woocommerce'),
              ),
              'active-setup-price' => array(
                'title' => __('Activar precio definido', 'dc_raq'),
                'type' => 'checkbox',
                'description' => __('Activar precio preconfigurado', 'dc_raq'),
                'default' => 'yes'
              ),
              'all_communes' => array(
                'title' => __("¿Todas las {$label_name} ?", 'dc_raq'),
                'label' => 'Activar',
                'type' => 'checkbox',
                'description' => __("Seleccionar todas las {$label_name}  en el campo de {$label_name} específicas siguiente.", 'dc_raq'),
                'default' => 'no'
              ),
              'communes' => array(
                'title' => __("{$label_name} espec&iacute;ficas", 'woocommerce'),
                'type' => 'multiselect',
                'description' => "Configure {$label_name} para valor detallado.",
                'class' => 'wc-enhanced-select',
                'options' => WC()->countries->get_states($woocommerce_default_country),
                'custom_attributes' => array(
                  'data-placeholder' => __("Seleccione {$label_name}", 'woocommerce'),
                )
              ),
              'price-setup' => array(
                'title' => __('Subvencionar precio de envíos ', 'woocommerce'),
                'type' => 'number',
                'description' => __("Configure su valor de las {$label_name} por %. '100% = Gratis'", 'woocommerce'),
                'css' => 'max-width:200px;',
              ),
              'all_free_communes' => array(
                'title' => __("¿Todas las {$label_name}?", 'dc_raq'),
                'label' => 'Activar',
                'type' => 'checkbox',
                'description' => __("Seleccionar todas las {$label_name} en el campo de {$label_name} específicas siguiente.", 'dc_raq'),
                'default' => 'no'
              ),
              'free_communes' => array(
                'title' => __("{$label_name} especificas", 'woocommerce'),
                'type' => 'multiselect',
                'description' => "Configure {$label_name} con despacho gratis.",
                'class' => 'wc-enhanced-select',
                'options' => WC()->countries->get_states($woocommerce_default_country),
                'custom_attributes' => array(
                  'data-placeholder' => __("Seleccione {$label_name}", 'woocommerce'),
                ),
              ),
              'price' => array(
                'title' => __('Env&iacute;os gratis a partir:', 'woocommerce'),
                'type' => 'number',
                'description' => __('Configure el valor de m&iacute;nimo de orden para despachos.', 'woocommerce'),
                'css' => 'max-width:200px;',
              ),
              'free_communes_for_price' => array(
                'title' => __("{$label_name} con despacho gratis segun valor:", 'woocommerce'),
                'type' => 'multiselect',
                'description' => "Configure {$label_name} con despacho gratis si el valor del producto es mayor.",
                'class' => 'wc-enhanced-select',
                'options' => WC()->countries->get_states($woocommerce_default_country),
                'custom_attributes' => array(
                  'data-placeholder' => __("Seleccione {$label_name}", 'woocommerce'),
                ),
              ),
              'cron' => array(
                'title' => __('Importación complementaria para pedidos no enviados a Shipit', 'cron'),
                'label' => 'Activar',
                'type' => 'checkbox',
                'description' => __('Revisar automáticamente los envíos no importados según la frecuencia configurada.', 'cron'),
                'default' => 'no',
              ),
              'frequency' => array(
                'title' => 'Frecuencia',
                'description' => 'Definir la frecuencia de revisión de envíos no importados a Shipit (recomendado: 1 hora)',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => array(
                  '900' => '15 minutos',
                  '1800' => '30 minutos',
                  '3600' => '1 hora',
                  '21600' => '6 horas',
                  '86400' => '24 horas'
                )
                ),
            );
          }

          public function calculate_shipping($package = array()) {
            getShipitConfiguration();
            if (!get_option('shipit_seccond_setting'))
            {
              sendSecondShipitSetting() ;
            }
            global $woocommerce;
            if (WC()->cart->get_cart_contents_count() === 0) return;
            $cart = strpos( wc_get_cart_url(), $_SERVER['REQUEST_URI']) == false ? false : true;
            $destinyId = (int)filter_var($package["destination"]['state'], FILTER_SANITIZE_NUMBER_INT);
            $integration = new Integration(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
            $sellerSetting = $integration->setting();
            $prices = array();
            $rate = new Rate(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
            if ($sellerSetting->show_shipit_checkout === true && $destinyId != null) {
              $rate->setParcel(getMeasures(), $cart, WC()->cart->cart_contents_total);
              $rate->setDestinyId($destinyId);
              $rate->setMultiCourierEnabled(true);
              $prices = $rate->calculate();
            }
            $prices = count($prices) > 0 ? $prices : array();
            if (is_object($prices) && $prices->state == 'error' || !$prices) {
            } else {
              global $shows;
              $shipit_country_settings = ShipitCountryHelper::getCountrySettings();
              $label_name = $shipit_country_settings['label_name'];
              $woocommerce_default_country = $shipit_country_settings['woocommerce_default_country'];
              $shows = new Shipit_Shipping();
              $showCarrierPrice = false;
              if (is_array($prices) || is_object($prices)) {
                $i = 0;
                foreach ($prices as $price) {
                  $rateDescription = array($price->name);
                  $carrierName = $price->courier->name;
                  $priceToDisplay = $rate->getRate($this->id.'-'.$i, $price, $carrierName, $rateDescription, 0, 0, 0);
                  $i++;
                  $this->add_rate($priceToDisplay);
                }
              }
            }
          }
    }
}
