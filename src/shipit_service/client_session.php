<?php
class ClientSession
{
  public $user_id;
  public $user_token;
  public $referer;
  public $post;

  public function __construct() {
    $this->user_id = $this->getUserIP();
    $this->user_token = $this->wp_get_session_token();
    $this->referer = $this->get_referer();
    $this->post = $this->get_post();
  }

  public function get_info() {
    return array('user_ip' => $this->getUserIP()
                 ,'referer' => $this->get_referer()
                 ,'server_time' => date('d/m/Y == H:i:s')
                 ,'php_version' => phpversion()
                 ,'woocommerce_plugin_version' => $this->pluginVersion('/woocommerce/woocommerce.php')
                 ,'wordpress_version' => get_bloginfo( 'version' )
                 ,'post_data' => $this->get_post());
  }

  private function pluginVersion($plugin) {
    $plugin_dir = WP_PLUGIN_DIR . $plugin;
    $plugin_data = get_plugin_data($plugin_dir);
    return $plugin_data['Version'];
  }

  private function getUserIP() {
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
      $_SERVER['REMOTE_ADDR'] = filter_var( $_SERVER["HTTP_CF_CONNECTING_IP"] , FILTER_UNSAFE_RAW);
      $_SERVER['HTTP_CLIENT_IP'] = filter_var( $_SERVER["HTTP_CF_CONNECTING_IP"] , FILTER_UNSAFE_RAW);
    }
    $client  = filter_var( @$_SERVER['HTTP_CLIENT_IP'] , FILTER_VALIDATE_IP);
    $forward = filter_var( @$_SERVER['HTTP_X_FORWARDED_FOR'] , FILTER_UNSAFE_RAW);
    $remote  = filter_var( $_SERVER['REMOTE_ADDR'] , FILTER_UNSAFE_RAW);

    if (filter_var($client, FILTER_VALIDATE_IP)) {
      $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
      $ip = $forward;
    } else {
      $ip = $remote;
    }

    return $ip;
  }

  private function wp_get_session_token() {
    $cookie = wp_parse_auth_cookie('', 'logged_in');
    return !empty($cookie['token']) ? $cookie['token'] : '';
  }

  private function get_referer() {
    return isset($_SERVER['HTTP_REFERER']) ? filter_var( $_SERVER['HTTP_REFERER'] , FILTER_UNSAFE_RAW) : '';
  }

  private function get_post() {
    if (isset($_POST['post_data'])) {
      parse_str($_POST['post_data'], $postData);
    }
    else {
      $postData = false;
    }
    return $postData;
  }
}
