<?php
  defined('ABSPATH') or die("Bye bye");
  if (!class_exists('Shipit_Settings_Admin')) {
    class Shipit_Settings_Admin  {
      private $settings_api;
      public $installation_errors = array();
      public $title_color;
      public $checkout_active_fields = true;

      function __construct() {
        $this->settings_api = new Shipit_Settings;
        add_action('admin_init', array($this, 'admin_init'));
        add_action( 'admin_menu', array($this, 'admin_menu') );
        add_filter( 'admin_footer_text', '__return_empty_string', 11 );
        add_filter( 'update_footer',     '__return_empty_string', 11 );
     
      }

      function admin_init() {
        //wp_enqueue_script('multiselect', plugin_dir_url(__FILE__) . 'js/jquery.multiselect.js', array('jquery'));
        //wp_enqueue_style('multiselect', plugin_dir_url(__FILE__) . 'css/jquery.multiselect.css');
        wp_enqueue_script('woocommerce_communes_selected', plugin_dir_url(__FILE__) . 'js/communes_selected.js', array('jquery'));
        wp_enqueue_style('modal_style', plugin_dir_url(__FILE__) . 'css/modal.css'); // Nuevo estilo para el modal
        wp_enqueue_script('modal_script', plugin_dir_url(__FILE__) . 'js/modal.js', array('jquery')); // Nuevo script para el modal

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
              Esto lo hicimos para centralizar tu gestión en una sola plataforma. Además, ahí tendrás la opción de hablar con nuestros especialistas
              en el caso de que tengas algún problema o quieras configurar algo y no sepas como.
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
            'description' => 'Elige el tipo de empaque que tendría tu envío',
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
        $database_create_table = $this->validate_database_create_table();
        $database_table_name = $this->validate_database_table_name();
        $database_content_table = $this->validate_database_content_table();
        if (isset(get_option('shipit_user')['shipit_user']))
        {
          $credentials = $this->validate_credentials();
        } else {
          $credentials = false;
        }
        
       // $checkout_active_fields = $this->validate_checkout_active_fields();
        $checkout_visible_fields = $this->validate_checkout_visible_fields();
        $webhook_integration = $this->validate_webhook_integration();
        $this->validate_checkout_active_fields_v3();


        echo '<div class="wrap">';
        echo '<h2>Inicio de sesión de usuario</h2> Bienvenido a la configuración de plugin Shipit para Woocommerce </div>';
        echo '<form id="token-form" method = "post" action = "options.php"> ';
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();
        echo '</form>';
        echo '<br>';
        echo '<a href="https://shipitcl.zendesk.com/hc/es-419/articles/360016135074--C%C3%B3mo-integrar-mi-tienda-de-WooCommerce-con-Shipit" target="_blank">Ver instructivo de instalación</a>';
        echo '</div>';
        shipit_admin_add_foobar();

        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
    
          echo '<div id="myModal" class="modal">';
          echo '<div class="modal-content">';
          echo '<span class="close">&times;</span>';
          echo '<h2 class="modal-title" style="color: '.$this->installation_header_title_color().'">' . $this->installation_header_title() . '</h2>';
      
          echo '<div class="modal-columns">';
      
          echo '<div class="modal-column">';
          echo '<i class="dashicons dashicons-database"></i>';
          echo '<br>';
          $icon = $database_create_table ? 'dashicons-yes' : 'dashicons-no';
          echo '<p><span class="dashicons ' . $icon . '"></span>Creación de tabla en base de datos</p>';
          $icon = $database_table_name ? 'dashicons-yes' : 'dashicons-no';
          echo '<p><span class="dashicons ' . $icon . '"></span>Nombre de tabla en base de datos</p>';
          $icon = $database_content_table ? 'dashicons-yes' : 'dashicons-no';
          echo '<p><span class="dashicons ' . $icon . '"></span>Contenido de tablas en base de datos</p>';
          echo '</div>';
      
          echo '<div class="modal-column">';
          echo '<i class="dashicons dashicons-admin-network"></i>';
          echo '<br>';
          if(get_option('shipit_db_tables_verify ') == true){
            $icon = $credentials ? 'dashicons-yes' : 'dashicons-no';
          }else{
            $icon = 'dashicons-update dashicons-loading';
          }
          echo '<p><span class="dashicons ' . $icon . '"></span>Validación de credenciales</p>';
          if(get_option('shipit_db_tables_verify ') == true){
            $icon = $webhook_integration ? 'dashicons-yes' : 'dashicons-no';
          }else{
            $icon = 'dashicons-update dashicons-loading';
          }
          echo '<p><span class="dashicons ' . $icon . '"></span>Envío de información a shipit</p>';
          echo '</div>';
      
          echo '<div class="modal-column">';
          echo '<i class="dashicons dashicons-cart"></i>';
          echo '<br>';
          if(get_option('shipit_auth') == true){
            $icon = $this->checkout_active_fields ? 'dashicons-yes' : 'dashicons-no';
          }else{
            $icon = 'dashicons-update dashicons-loading';
          }
          echo '<p><span class="dashicons ' . $icon . '"></span>Validación de campos activos de checkout</p>';
          if(get_option('shipit_auth') == true){
            $icon = $checkout_visible_fields ? 'dashicons-yes' : 'dashicons-no';
          }else{
            $icon = 'dashicons-update dashicons-loading';
          }
          echo '<p><span class="dashicons ' . $icon . '"></span>Validación de campos visibles de checkout</p>';
          echo '</div>';
      
          echo '</div>';
      
          if (!empty($this->installation_errors)) {
              echo '<div class="modal-errors">';
              echo '<h3>Errores de Instalación:</h3>';
              echo '<ul>';
              foreach ($this->installation_errors as $error) {
                  echo '<li>' . $error . '</li>';
              }
              echo '</ul>';
              echo '</div>';
          }
      
          echo '</div>';
          echo '</div>';
      }
    }
    
    function validate_database_create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipit';
        $table_name_communes = $wpdb->prefix . 'shipit_communes';
        $table_name_emergency_rates = $wpdb->prefix . 'shipit_emergency_rates';
        $table_name_rates_request = $wpdb->prefix . 'shipit_rates_request';
        $table_name_user_shipit = $wpdb->prefix . 'user_shipit';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $this->installation_errors[] = 'No se ha encontrado la tabla ' . $table_name;
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_communes'") != $table_name_communes) {
            $this->installation_errors[] = 'No se ha encontrado la tabla ' . $table_name_communes;
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_emergency_rates'") != $table_name_emergency_rates) {
            $this->installation_errors[] = 'No se ha encontrado la tabla ' . $table_name_emergency_rates;
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_rates_request'") != $table_name_rates_request) {
            $this->installation_errors[] = 'No se ha encontrado la tabla ' . $table_name_rates_request;
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_user_shipit'") != $table_name_user_shipit) {
            $this->installation_errors[] = 'No se ha encontrado la tabla ' . $table_name_user_shipit;
        }

        if (count($this->installation_errors) > 0) {
            update_option('shipit_db_tables_verify', false);
            return false;
        }
        update_option('shipit_db_tables_verify', true);
        return true;
    }
    
    function validate_database_table_name() {
        return true;
    }
    
    function validate_database_content_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipit';
        $table_name_communes = $wpdb->prefix . 'shipit_communes';
        $table_name_emergency_rates = $wpdb->prefix . 'shipit_emergency_rates';
        $table_name_rates_request = $wpdb->prefix . 'shipit_rates_request';
        $table_name_user_shipit = $wpdb->prefix . 'user_shipit';
    
/*         if ($wpdb->get_var("SELECT COUNT(*) FROM $table_name") <= 0) {
            $this->installation_errors[] = 'No se ha encontrado contenido en la tabla ' . $table_name;
        } */
        if ($wpdb->get_var("SELECT COUNT(*) FROM $table_name_communes") <= 0) {
            $this->installation_errors[] = 'No se ha encontrado contenido en la tabla ' . $table_name_communes;
        }
        /*
        if ($wpdb->get_var("SELECT COUNT(*) FROM $table_name_emergency_rates") <= 0) {
            $this->installation_errors[] = 'No se ha encontrado contenido en la tabla ' . $table_name_emergency_rates;
        }
        if ($wpdb->get_var("SELECT COUNT(*) FROM $table_name_rates_request") <= 0) {
            $this->installation_errors[] = 'No se ha encontrado contenido en la tabla ' . $table_name_rates_request;
        } */
        if ($wpdb->get_var("SELECT COUNT(*) FROM $table_name_user_shipit") <= 0) {
            $this->installation_errors[] = 'No se ha encontrado contenido en la tabla ' . $table_name_user_shipit;
        }

        if (count($this->installation_errors) > 0) {
            return false;
        }
        return true;
    }
    
    function validate_credentials() {
        $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
        $core->testCredential('validate_credentials', []);
        $shipit_auth = get_option('shipit_auth');
        if ($shipit_auth == false) {
            $this->installation_errors[] = 'La credenciales ingresadas no son las correctas.';
        }
        return $shipit_auth;
    }

    function validate_webhook_integration() {
        $webhook_url = get_option('shipit_webhook');
        if ($webhook_url == false) {
            $this->installation_errors[] = 'Error de conexión con los servicios de Shipit. Vuelva a intentarlo.';
        }
        return $webhook_url;
    }


    
  public function validate_checkout_active_fields_v3() {
    $billing_fields = get_option('wc_fields_billing');
    $is_checkout_field_editor_active = is_plugin_active('woo-checkout-field-editor-pro/checkout-form-designer.php');
    $is_checkout_manager_active = is_plugin_active('woocommerce-checkout-manager/woocommerce-checkout-manager.php');
    $wooccm_billing = get_option('wooccm_billing');

    // Check for WooCommerce Checkout Field Editor
    if ($billing_fields !== null && $is_checkout_field_editor_active) {
        if (
            isset($billing_fields['billing_state']['enabled']) && 
            $billing_fields['billing_state']['enabled'] == '1' &&
            isset($billing_fields['billing_address_1']['enabled']) && 
            $billing_fields['billing_address_1']['enabled'] == '1'
        ) {
            $this->checkout_active_fields = true;
        } else {
            if (!isset($billing_fields['billing_state']['enabled']) || $billing_fields['billing_state']['enabled'] != '1') {
                $this->installation_errors[] = "El campo billing_state no se encuentra activo o visible en tu checkout";
            }
            if (!isset($billing_fields['billing_address_1']['enabled']) || $billing_fields['billing_address_1']['enabled'] != '1') {
                $this->installation_errors[] = "El campo billing_address_1 no se encuentra activo o visible en tu checkout";
            }
            $this->checkout_active_fields = false;
        }
    }

    // Check for WooCommerce Checkout Manager
    if ($wooccm_billing !== null && $is_checkout_manager_active) {
        $wm_billing_state_disabled = $wooccm_billing[7]['disabled'];
        $wm_address_1_disabled = $wooccm_billing[4]['disabled'];
        
        if (!$wm_billing_state_disabled && !$wm_address_1_disabled) {
            $this->checkout_active_fields = true;
        } else {
            if ($wm_billing_state_disabled) {
                $this->installation_errors[] = "El campo billing_state no se encuentra activo o visible en tu checkout";
            }
            if ($wm_address_1_disabled) {
                $this->installation_errors[] = "El campo billing_address_1 no se encuentra activo o visible en tu checkout";
            }
            $this->checkout_active_fields = false;
        }
    }

    return;
}
  function validate_checkout_visible_fields() {
    // List of plugins to check
    $plugins_to_check = [
        'comunas-de-chile-para-woocommerce/woocoomerce-comunas.php' => 'Comunas de Chile para WooCommerce',
        'regiones-ciudades-y-comunas-de-chile/regiones-ciudades-y-comunas-de-chile.php' => 'Regiones Ciudades y Comunas de Chile',
        'mkrapel-regiones-y-ciudades-de-chile/mkrapel-regiones-y-ciudades-de-chile.php' => 'MkRapel Regiones y Ciudades de Chile',
        'woocommerce-chilean-peso-chilean-states/woocommerce-chilean-peso-chilean-states.php' => 'WooCommerce Chilean Peso + Chilean States',
        'regiones-de-chile-para-woocommerce/chile-woocommerce.php' => 'Regiones de Chile para WooCommerce',
        'woocommerce-chilean-peso-currency/woocommerce-chilean-peso.php' => 'WooCommerce Chilean Peso',
        'flexible-shipping-dhl-express/flexible-shipping-dhl-express.php' => 'Flexible Shipping DHL Express',
    ];

    // Flag to indicate if any plugin is installed and active
    $any_plugin_installed_and_active = false;

    // Load the required plugin functions if they are not already loaded
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    // Check the status of each plugin
    foreach ($plugins_to_check as $plugin => $plugin_name) {
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
            if (is_plugin_active($plugin)) {
                $any_plugin_installed_and_active = true;
                if (get_option('shipit_auth') == true) {
                    $this->installation_errors[] = "El campo billing_state se encuentra en uso por la app $plugin_name impidiendo que Shipit pueda funcionar correctamente, desactívelo.";
                }
            }
        }
    }

    return !$any_plugin_installed_and_active;
  }
    
    function installation_header_title(){
        if(count($this->installation_errors) > 0){
            $this->title_color = 'red';
            return '¡Instalación con error! 😞';
        } else {
            $this->title_color = 'green';
            return '¡Instalación Exitosa! 🎉';
        }
    }

    function installation_header_title_color(){
      if(count($this->installation_errors) > 0){
          return 'red';
      } else {
          return 'green';
      }
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
