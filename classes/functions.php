<?php

  class alterskontrolle_dePlugin_func {

    var $auth_id;
    var $options;

    function __construct() {

      if(is_active_widget('', '', 'ak_widget_akde' )) {
          add_shortcode('ak_widget_plugin_code', array($this, 'aktor_shortcode'));
          add_shortcode('ak_plugin_code', array($this, 'aktor_shortcode_null'));
      } else {
          add_shortcode('ak_plugin_code', array($this, 'aktor_shortcode'));
      }

      /* Preview Tor */
      add_shortcode('ak_plugin_code_preview', array($this, 'aktor_shortcode_preview'));

      /* Filter Content*/
  		add_filter('the_content', array($this, 'filterContent'));

      /* Shortcodes */
      add_shortcode('ak_secure_area', array($this, 'ak_secure_area_shortcode'));
      add_shortcode('ak_secure_message', array($this, 'ak_secure_message_shortcode'));

      /* Login / Logout for Alterskontrolle.de Tor */
      add_action('wp_login', array($this, 'ak_on_login'), 10, 2);
      add_action('wp_logout', array($this, 'ak_on_logout'), 10, 2);

      add_action('init', array($this, 'agecheck'), 10, 2);

      $this->options = json_decode(get_option('alterskontrolle_de_options'), false, 512, JSON_UNESCAPED_UNICODE);

      $this->getCryptPasswort();
      $this->ak_logout_session();

    }

    function ak_logout_session() {

      if(isset($_GET['logout']) && $_GET['logout'] == 'check') {
        $this->ak_clean_session();
	      $url=strtok($_SERVER["REQUEST_URI"],'?');
	      header("Location: ".home_url($url));
        exit();
      }

    }

    function getCryptPasswort() {

      if(isset($_POST) && $_POST && isset($_POST['get_ak_Cpw']) && $_POST['get_ak_Cpw']) {

        $pw = $_POST['get_ak_Cpw'];
        if($pw) {
          echo crypt($pw, substr($pw, 0, 2));
        }
        exit();

      }

    }

    function aktor_shortcode($atts) {
      global $wp;
      $params = (object) shortcode_atts( array(), $atts );

      if(is_active_widget('', '', 'ak_widget_akde' ) && $this->ak_secure_check()) {
        if(!is_user_logged_in() && !$this->ak_check_is_bot()) {
          return '<div class="ak_11055d29911457d69ea70ef9517a1c94"><a class="ak_logout" href="'.home_url($wp->request.'/?logout=check').'">Ausloggen?</a></div>';
        } else {
          return '';
        }
      }

      return $this->aktor_render($params, $this->options);

    }

    function aktor_shortcode_preview($atts) {
      global $wp;
      $params = (object) shortcode_atts( array(), $atts );

      return $this->aktor_render($params, $this->options);

    }

    function aktor_shortcode_null() {

      return '';

    }

    function aktor_render($params, $options) {
      global $wp, $post;

      $post_id = (isset($post->ID) && $post->ID) ? $post->ID : '';
      $user_id = get_current_user_id();

      $message = $this->agecheck_status_message($this->auth_id);

      ob_start();

      switch ($options->ak_torauswahl) {
          case 'aktor':
              include(alterskontrolle_dePlugin_PATH.'templates/ak_tor.php');
          break;
          case 'abo':
              include(alterskontrolle_dePlugin_PATH.'templates/abo_tor.php');
          break;
          case 'tpl':
              include(alterskontrolle_dePlugin_PATH.'templates/tpl_tor.php');
          break;
      }

      $content = ob_get_contents();
      ob_end_clean();

      return $content;

    }

    function ak_secure_area_shortcode($atts,$content) {
      global $wp, $post;

      $output = '';

      if($content && $this->ak_secure_check()) {
        $output = '<div class="ak_plugin_secure_area">';
        $output .= $content;
        $output .= '</div>';
      } else {
        $output = do_shortcode('[ak_plugin_code]');
      }

      return $output;

    }

    function ak_secure_message_shortcode($atts,$content) {

      $output = '';

      if($content && !$this->ak_secure_check()) {
        $output = '<div class="ak_11055d29911457d69ea70ef9517a1c94"><div class="ak_plugin_message">';
        $output .= $content;
        $output .= '</div></div>';
      }

      return $output;

    }

    function filterContent($content) {

      return $this->ak_secure_control($content);

    }

    function ak_secure_control($content) {
      global $wp, $post;

      $logout = '';
      $meta = get_post_meta($post->ID, '_ak_meta', TRUE);
      $mc_check = (isset($meta['check']) && $meta['check']) ? $meta['check'] : '';

      if($mc_check && !$this->ak_secure_check()) {
        $output = '<div class="ak_11055d29911457d69ea70ef9517a1c94"><div class="ak_plugin_message">'.$this->options->ak_tor_message.'</div></div>';
        $output .= do_shortcode('[ak_plugin_code]');
        return $output;
      } elseif($mc_check && $this->ak_secure_check()) {
        if(!is_user_logged_in() && !$this->ak_check_is_bot() && !is_active_widget('', '', 'ak_widget_akde' )) {
          $logout = '<div class="ak_11055d29911457d69ea70ef9517a1c94"><a class="ak_logout" href="'.home_url($wp->request.'/?logout=check').'">Ausloggen?</a></div>';
        }
        return $content.$logout;
      }

      if(!$mc_check) {

        if(!is_user_logged_in() && !$this->ak_check_is_bot() && !is_active_widget('', '', 'ak_widget_akde' ) && $this->ak_secure_check()) {
          $logout = '<div class="ak_11055d29911457d69ea70ef9517a1c94"><a class="ak_logout" href="'.home_url($wp->request.'/?logout=check').'">Ausloggen?</a></div>';
        }
        return $content.$logout;
      }

    }

    function ak_secure_check() {
      global $post;

      $status = false;

      if($this->options->ak_programm_type == 'aktor') {

        if(($this->ak_check_is_bot()) || ($this->ak_session_check()) || ($this->auth_id == 100 && isset($_POST['pid']) && $post->ID == $_POST['pid'])) {
          $status = true;
        }

      } else {

        if(($this->ak_check_is_bot()) || ($this->ak_session_check())) {
          $status = true;
        }

      }

      return $status;

    }

    function ak_session_check() {

      $val = md5(basename(home_url()).$_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
      $ak_check = 'ak_check_'.$val;

      if(isset($_SESSION[$ak_check]) && $_SESSION[$ak_check] == $val) {
        return true;
      } else {
        return false;
      }

    }

    function ak_create_session() {

      $val = md5(basename(home_url()).$_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
      $ak_check = 'ak_check_'.$val;

      $_SESSION[$ak_check] = $val;

    }

    function ak_clean_session() {

      $val = md5(basename(home_url()).$_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
      $ak_check = 'ak_check_'.$val;

      unset($_SESSION[$ak_check]);
      session_destroy();

    }

    function curlsupported() {
  		if (in_array('curl', get_loaded_extensions())) {
  			return true;
  		} else {
  			return false;
  		}
  	}

  	function agecheck($userid = NULL , $pw = NULL) {

  		if(!$userid && !$pw) {
    		$userid = (isset($_POST) && $_POST && isset($_POST['userid']) && $_POST['userid']) ? $_POST['userid'] : '';
    		$pw = (isset($_POST) && $_POST && isset($_POST['pw']) && $_POST['pw']) ? $_POST['pw'] : '';
      }

  		$sid = (isset($_POST) && $_POST && isset($_POST['sid']) && $_POST['sid']) ? "&sid=".$_POST['sid'] : '';

  		if($userid && $pw) {

  			// $pw = crypt($pw, substr($pw, 0, 2));

  			$check = "http://api.alterskontrolle.de/?apikey=".$this->options->ak_apikey."&wid=".$this->options->ak_wid."&userid=".$userid."&pw=".$pw.$sid."&aktion=akwp";

  			if (!$this->curlsupported()) {
  				$get_check = file_get_contents($check);
  			} else {
  				$ch = curl_init($check);
  				curl_setopt($ch, CURLOPT_URL, $check);
  				curl_setopt($ch, CURLOPT_HEADER, 0);
  				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  				$get_check = curl_exec($ch);
  				curl_close($ch);
  			}

        $this->auth_id = $get_check;

        if($this->options->ak_programm_type == 'abo' && $get_check == 100) {
          $this->ak_create_session();
        }

        //if user is logged in and first akdor login.
        if($get_check == 100 && is_user_logged_in()) {
          $user_id = get_current_user_id();

          $ak_userdata_userid = esc_attr( get_the_author_meta('ak_userdata_userid', $user_id));

          if($ak_userdata_userid == '') {

            update_usermeta($user_id, 'ak_userdata_userid', $userid);
            update_usermeta($user_id, 'ak_userdata_password', $pw);

            $this->ak_create_session();

          }

        }

  			return $get_check;

  		}

  	}

    function agecheck_status_message($ak_id) {

      $message = '';

      switch ($ak_id) {
        case '100':
          $message = '';
        break;
        case '200':
          $message = 'Ihr Alter wurde noch nicht verifiziert.';
        break;
        case '300':
          $message = 'Sie sind leider nicht freigeschaltet.';
        break;
        case '400':
          $message = 'Einen Nutzer mit diesen Zugangsdaten schein es nicht zu geben.';
        break;
        case '990':
          // $message = 'Der API-KEY ist falsch eingetragen.';
        break;
        case '999':
          // $message = 'Es wurden beim Aufruf der API nicht alle notwendigen Felder übergeben.';
        break;
      }

      return $message;

    }

    function ak_on_login($user_login, $user) {

      if($this->options->ak_programm_type == 'aktor') {

        $ak_userdata_userid = esc_attr( get_the_author_meta( 'ak_userdata_userid', $user->ID ) );
        $ak_userdata_password = esc_attr( get_the_author_meta( 'ak_userdata_password', $user->ID ) );

        if($this->agecheck($ak_userdata_userid,$ak_userdata_password) == 100) {
          $this->ak_create_session();
        }

      }

    }

    function ak_on_logout() {
      $this->ak_clean_session();
    }

    function ak_check_is_bot($user_agent = NULL) {

  	if(is_null($user_agent)) $user_agent = $_SERVER['HTTP_USER_AGENT'];
  	$ROBOT_USER_AGENTS= array (
  	'check_http',
  	'nagios',
  	'slurp',
  	'archive',
  	'crawl',
  	'bot',
  	'spider',
  	'search',
  	'find',
  	'rank',
  	'java',
  	'wget',
  	'curl',
  	'Commons-HttpClient',
  	'Python-urllib',
  	'libwww',
  	'httpunit',
  	'nutch',
  	'teoma',
  	'webmon',
  	'httrack',
  	'convera',
  	'biglotron',
  	'grub.org',
  	'speedy',
  	'fluffy',
  	'bibnum.bnf',
  	'findlink',
  	'panscient',
  	'IOI',
  	'ips-agent',
  	'yanga',
  	'yandex',
  	'Voyager',
  	'CyberPatrol',
  	'page2rss',
  	'linkdex',
  	'ezooms',
  	'mail.ru',
  	'heritrix',
  	'Aboundex',
  	'summify',
  	'facebookexternalhit',
  	'yeti',
  	'RetrevoPageAnalyzer',
  	'sogou',
  	'wotbox',
  	'ichiro',
  	'drupact',
  	'coccoc',
  	'integromedb',
  	'siteexplorer.info',
  	'proximic',
  	'changedetection',
  	'ZmEu',
  	'Novalnet',
  	'COMODO',
  	'Drupal',
  	'facebook',
  	'analytics',
  	'PayPal',
  	'revolt',
  	);

  	$returnval = FALSE;
  	foreach($ROBOT_USER_AGENTS as $needle) {
  		$pos = stripos($user_agent, $needle);
  		if ($pos !== false) {
  			$returnval = TRUE;
  		}
  	}
  	return $returnval;
  }

  }

?>