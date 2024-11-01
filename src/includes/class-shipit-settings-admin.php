<?php
  defined('ABSPATH') or die("Bye bye");
  if (!class_exists('Shipit_Settings_Admin')) {
    class Shipit_Settings_Admin  {
      private $settings_api;

      function __construct() {
        $this->settings_api = new Shipit_Settings;
        add_action('admin_init', array($this, 'admin_init'));
        add_action( 'admin_menu', array($this, 'admin_menu') );
        add_filter( 'admin_footer_text', '__return_empty_string', 11 );
        add_filter( 'update_footer',     '__return_empty_string', 11 );
        
      }

      function admin_init() {
        wp_enqueue_script('multiselect', plugin_dir_url(__FILE__) . 'js/jquery.multiselect.js', array('jquery'));
        wp_enqueue_style('multiselect', plugin_dir_url(__FILE__) . 'css/jquery.multiselect.css');
        wp_enqueue_script('woocommerce_communes_selected', plugin_dir_url(__FILE__) . 'js/communes_selected.js', array('jquery'));

        wp_enqueue_script('jquery');
        $this->settings_api->set_sections($this->get_settings_sections());
        $this->settings_api->set_fields($this->get_settings_fields());
        $this->settings_api->admin_init();
      }

      function admin_menu() {
        add_menu_page('Shipit', 'Shipit', 'delete_posts', 'settings_api', array($this, 'plugin_page'), plugin_dir_url(__FILE__) . 'images/favicon.png');
       

      }
      

      function get_settings_sections() {
        $sections = array(
          array(
            'id'    => 'shipit_user',
            'title' => __('Credenciales', 'shipit')
          ),
          array(
            'id'    => 'woocommerce_shipit_settings',
            'title' => __('Configuración', 'woocommerce_shipit_settings')
          )
        );
        return $sections;
      }

      function get_settings_fields() {
        $shipit_country_settings = ShipitCountryHelper::getCountrySettings();
        $label_name = $shipit_country_settings['label_name'];
        $woocommerce_default_country = $shipit_country_settings['woocommerce_default_country'];

        $woocommerce_shipit_settings = get_option('woocommerce_shipit_settings');
        $woocommerce_shipit_settings = maybe_unserialize($woocommerce_shipit_settings);
        $communes_values = isset($woocommerce_shipit_settings['communes']) ? $woocommerce_shipit_settings['communes'] : array();
        $settings_fields = array(
          'shipit_user' => array(
            array(
              'name' => 'shipit_user',
              'label' => __('Email Shipit', 'shipit'),
              'desc' => __('Email Shipit description', 'shipit'),
              'placeholder' => __('Email Shipit placeholder', 'shipit'),
              'type' => 'text',
              'default' => get_option('shipit_user'),
              'sanitize_callback' => 'sanitize_text_field'
            ),
            array(
              'name' => 'shipit_token',
              'label' => __('Token', 'shipit'),
              'desc' => __('Token description', 'shipit'),
              'type' => 'password',
              'default' => get_option('shipit_token')
            )
            ),
        'woocommerce_shipit_settings' => array(
          'warning' => array(
            'name' => 'warning',
            'title' => __('Advertencia', 'woocommerce'),
            'type' => 'warning',
            'desc' => __( '
              Hola!, migramos la configuración de tus pedidos a la <a href="https://app.shipit.cl" target="_blank">Suite de Shipit</a>
              <br>
              No te preocupes, todo está ahí.
              <br>
              Esto lo hicimos para centralizar  tu gestión en una sola plataforma. Además, ahí tendrás la opción de hablar con nuestros especialistas
              en el caso de que tengas algún problema o quieras configura algo y no sepas como.
              <br>
              De todas formas, te dejamos aquí instructivos para lograr hacer algunas cosas que antes hacías acá:
                <br>
                1.- <a href="https://shipitcl.zendesk.com/hc/es-419/articles/23096481406747-Activar-Desactivar-promesa-de-entrega-en-checkout-Woocommerce" target="_blank">Activar y desactivar promesa de entrega</a>
                <br>
                2.- <a href="https://shipitcl.zendesk.com/hc/es-419/articles/21700171137051--Como-crear-reglas-de-envio-en-mi-Carrito-de-compras" target="_blank">Configurar envío gratis, subvencionar precios de envíos, tarifas planas y otras reglas de checkout</a>   
              ', 'woocommerce' ),
          ),
          'type_packing' => array(
            'name' => 'type_packing',
            'label' => 'Tipo empaque', // 'Tipo empaque'
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
            'name' => 'packing_set',
            'label' => 'Establecer empaque del producto',
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
            'name' => 'width',
            'label' => __('Ancho', 'woocommerce'),
            'title' => __('Ancho', 'woocommerce'),
            'type' => 'number',
            'description' => __('CM.', 'woocommerce'),
            'css'      => 'max-width:150px;',
            'default' => __('10', 'woocommerce')
          ),
          'height' => array(
            'name' => 'height',
            'label' => __('Alto', 'woocommerce'),
            'title' => __('Alto', 'woocommerce'),
            'type' => 'number',
            'description' => __('CM.', 'woocommerce'),
            'css'      => 'max-width:150px;',
            'default' => __('10', 'woocommerce')
          ),
          'length' => array(
            'name' => 'length',
            'label' => __('Largo', 'woocommerce'),
            'title' => __('Largo', 'woocommerce'),
            'type' => 'number',
            'description' => __('CM.', 'woocommerce'),
            'css'      => 'max-width:150px;',
            'default' => __('10', 'woocommerce')
          ),
          'weight_set' => array(
            'name' => 'weight_set',
            'label' => 'Establecer peso del producto',
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
            'name' => 'weight',
            'label' => __('Peso', 'woocommerce'),
            'title' => __('Peso', 'woocommerce'),
            'type' => 'number',
            'description' => __('KG.', 'woocommerce'),
            'css'      => 'max-width:150px;',
            'default' => __('1', 'woocommerce'),
          ),
          'cron' => array(
            'name' => 'cron',
            'label' => 'Importación complementaria para pedidos no enviados a Shipit',
            'title' => __('Importación complementaria para pedidos no enviados a Shipit', 'cron'),
            'type' => 'checkbox',
            'desc' => __('Revisar automáticamente los envíos no importados según la frecuencia configurada.', 'dc_raq')
          ),
          'frequency' => array(
            'name' => 'frequency',
            'label' => 'Frecuencia',
            'title' => 'Frecuencia',
            'desc' => 'Definir la frecuencia de revisión de envíos no importados a Shipit (recomendado: 1 hora)',
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
        )
        );
        return $settings_fields;
      }

      function plugin_page() {
        echo '<div class="wrap">';
        echo'<h2>Inicio de sesi&oacute;n de usuario</h2> Bienvenido a la configuración de plugin Shipit para Woocommerce </div>';
        echo '<form id="token-form" method = "post" action = "options.php"> ';
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();
        echo '</form>';
        echo '</div>';
        shipit_admin_add_foobar();
      }

      function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ($pages) {
          foreach ($pages as $page) {
            $pages_options[$page->ID] = $page->post_title;
          }
        }
        return $pages_options;
      }
    }
  }
?>
