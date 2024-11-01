<?php

function setCourierImage($label, $methods) {
  $shipping = $methods->get_method_id();
  $base_img = plugin_dir_url(__FILE__);
  $base = plugin_dir_path(__FILE__);
  $base = substr($base, 0, -15);
  $base_img = substr($base_img, 0, -15);
  //if exist image in path
  if (($shipping == 'shipit') && (file_exists($base . 'images/'.strtolower($methods->get_label()).'.png'))) {
    $label = (number_format($methods->get_cost()) == 0) ? '<img style="display:inline;max-width: 75px;vertical-align: middle;" class="shipit_icon" id="img" src="'. $base_img . 'images/'.$methods->get_label().'.png"> <span class="woocommerce-Price-amount amount"> GRATIS</span><br><span class="text-mute">'.$methods->meta_data[0].'</span>' : '<img style="display:inline;max-width: 75px;vertical-align: middle;" class="shipit_icon" id="img" src="'.$base_img . 'images/'.strtolower($methods->get_label()).'.png"> <span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#36;</span>'.number_format($methods->get_cost()).'</span><br><span class="text-mute">'.$methods->meta_data[0].'</span>';
  } else if ($shipping == 'shipit') {
    $label = (number_format($methods->get_cost()) == 0) ? 
    '<img style="display:inline;max-width: 75px;vertical-align: middle;" class="shipit_icon" id="img" src="'. $base_img 
    . 'images/shipit.png"> <span class="woocommerce-Price-amount amount"> GRATIS</span>.-'.$methods->get_label().'<br><span class="text-mute">'.$methods->meta_data[0].'</span>' : 
    '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#36;</span>'.number_format($methods->get_cost()).'</span>.-'
    .$methods->get_label().'<br><span class="text-mute">'.$methods->meta_data[0].'</span>';
  }
  return $label;
}

  function wp_add_one_hour_cron_schedule( $schedules ) {
    $schedules['every_one_hour'] = array(
        'interval' => get_option('woocommerce_shipit_settings')['frequency'] ,
        'display'  => __( 'Every hour' ),
    );

    return $schedules;
}

  add_filter('woocommerce_cart_shipping_method_full_label', 'setCourierImage', 10, 2);
  if(isset(get_option('woocommerce_shipit_settings')['frequency']))  add_filter( 'cron_schedules', 'wp_add_one_hour_cron_schedule' );