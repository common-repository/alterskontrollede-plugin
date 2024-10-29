<?php
/*
 Plugin Name: Alterskontrolle.de Plugin
 Plugin URI: http://wordpress.org/plugins/alterskontrollede-plugin/
 Description: Sichern Sie Ihre Inhalte mit dem Wordpress-Plugin Altersverifikationssystem (<a target="_blank" href="http://www.alterskontrolle.de/?wid=4820">Alterskontrolle.de</a>) von <a target="_blank" href="http://www.wondocash.com/?wid=4820&sid=0&aktion=p4sub">WondoCash.com</a>.
 Version: 1.7
 Author: WondoCash.com
 Author URI: http://www.wondocash.com/?wid=4820&sid=0&aktion=p4sub
 */ 

 define('alterskontrolle_dePlugin_PATH', dirname(__FILE__) .'/');

/* functions */
 include(alterskontrolle_dePlugin_PATH.'classes/inc.php');

 include(alterskontrolle_dePlugin_PATH.'widget.php');

 class alterskontrolle_dePlugin {

 	function __construct() {

    if ($this->is_session() === false) { session_start(); }

 		register_activation_hook( __FILE__, array($this, 'activate') );
 		register_deactivation_hook( __FILE__, array($this, 'deactivate') );

	    $this->setData();

		/* Text Widget do Shortcode */
		add_filter('widget_text', 'do_shortcode');

		/* Nested Shortcodes */
		add_filter('the_content', 'do_shortcode');

		/* Add Adminpage */
		add_action('admin_menu', array($this, 'add_menu'));

		/* init post page question */
		add_action('admin_init', array($this, 'ak_meta_init'));

		/* Add Custom Style */
		add_action('wp_head', array($this,'add_new_style'));
		add_action( 'admin_enqueue_scripts', array($this,'add_new_admin_style'));

		/* Add Shortcode Button */
		add_filter( 'mce_external_plugins', array($this,'ak_sc_mce_external_plugins'));
		add_filter( 'mce_buttons_2', array($this,'ak_sc_mce_buttons_2'));

		/* Add Settings Link */
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ));

		/* Shortcode and Tor init */
		$akpf = new alterskontrolle_dePlugin_func();

		/* User Data */
		add_action( 'show_user_profile', array($this,'wh_uf_func') );
		add_action( 'edit_user_profile', array($this,'wh_uf_func') );
		add_action( 'wp_create_user', array($this,'wh_uf_func') );
		add_action( 'user_register', array($this,'save_wh_uf_func') );

		/* Save User-Fields */
		add_action( 'personal_options_update', array($this,'save_wh_uf_func') );
		add_action( 'edit_user_profile_update', array($this,'save_wh_uf_func') );

 	}

  function setData() {

    $data = array('ak_wid'=>'', 'ak_apikey'=>'', 'ak_programm_type'=>'aktor', 'ak_linktyp'=>'aksite', 'ak_tor_message'=>'Nach dem erfolgreichen Login steht der versteckte Inhalt zu verfügung.', 'abo_id'=>'', 'abo_title'=>'', 'ak_torauswahl'=>'aktor', 'ak_tpl_before'=>'', 'ak_tpl_after'=>'');

      if(!get_option('alterskontrolle_de_options') || get_option('alterskontrolle_de_options') == '') {
        update_option('alterskontrolle_de_options', json_encode($data, JSON_UNESCAPED_UNICODE));
      }

  }

 	function activate() {

		$this->setData();
    
 	}

 	function deactivate() {

 	}

  //user field function

  function wh_uf_func($user) {

  ?>
  <h3>Alterskontrolle.de Logindaten hinterlegen</h3>
  <table class="form-table">
  	<tr>
  		<th><label for="ak_userdata_userid">User-ID</label></th>
  		<td><?php $ak_userdata_userid = esc_attr( get_the_author_meta( 'ak_userdata_userid', $user->ID ) ); ?>
  			<input type="text" name="ak_userdata_userid" id="ak_userdata_userid" value="<?php echo $ak_userdata_userid; ?>" /><br />
        <span class="description">Ihre User-ID bekommen Sie von Alterskontrolle.de übermittelt.</span>
  		</td>
  	</tr>
    <tr>
  		<th><label for="ak_userdata_password">Passwort</label></th>
  		<td><?php $ak_userdata_password = esc_attr( get_the_author_meta( 'ak_userdata_password', $user->ID ) ); ?>
  			<input type="password" class="regular-text" name="ak_userdata_password" id="ak_userdata_password" placeholder="<?php if($ak_userdata_password) { echo 'Es ist bereits ein Passwort vorhanden'; } else { echo 'Bitte Passwort eingeben'; } ?>" value="" /><br />
        <span class="description">Ihre Passwort wird verschlüsselt gespeichert, so sind Sie und Ihre Daten geschützt.</span>
  		</td>
  	</tr>
  </table>
  <?php

  }

  //save user field
  function save_wh_uf_func($user_id) {

    $pw = (isset($_POST['ak_userdata_password']) && $_POST['ak_userdata_password']) ? $_POST['ak_userdata_password'] : '';

    update_usermeta( $user_id, 'ak_userdata_userid', $_POST['ak_userdata_userid'] );

    if($pw) {
      $pw = crypt($pw, substr($pw, 0, 2));
      update_usermeta( $user_id, 'ak_userdata_password', $pw);
    }

    $akpf = new alterskontrolle_dePlugin_func();

    $ak_userdata_userid = esc_attr( get_the_author_meta( 'ak_userdata_userid', $user_id ) );
    $ak_userdata_password = esc_attr( get_the_author_meta( 'ak_userdata_password', $user_id ) );

    if($akpf->agecheck($ak_userdata_userid,$ak_userdata_password) == 100) {
      $akpf->ak_create_session();
    }

  }

  function aktor_update_options() {

    if(isset($_POST) && $_POST && isset($_POST['alterskontrolle_de_options']) && $_POST['alterskontrolle_de_options']) {
      update_option('alterskontrolle_de_options',json_encode($_POST['akdata'], JSON_UNESCAPED_UNICODE));
    }

  }

  function aktor_option_page() {
    $this->aktor_update_options();

    $options = json_decode(get_option('alterskontrolle_de_options'), false, 512, JSON_UNESCAPED_UNICODE);

    ?>

<div class="wrap ak-plugin">
<h1>Einstellungen › Alterkontrolle.de</h1>

<div class="table">
<div class="tr">
<div class="td" style="padding-right:30px;">

<h3>Willkommen auf der Alterskontrolle Plugin Konfigurationsseite.</h3>

<div class="ak_plugin_message" style="font-size:16px;">Um Alterskontrolle.de als Webmaster zu nutzen benötigst du einen <a target="_blank" href="http://www.wondocash.com/?wid=4820&sid=0&aktion=p4sub">WondoCash.com</a> account.
<a target="_blank" href="http://www.wondocash.com/?wid=4820&sid=0&aktion=p4sub">Melde Dich hier an.</a><br />
Nach Deiner Anmeldung findest du alles weitere auf der <a target="_blank" href="http://login.wondocash.com/login/?mod=avssys&func=akdeapi">Alterskontrolle.de API Seite</a>.
</div>

<form method="post" action="/wp-admin/options-general.php?page=alterskontrolle.de">
  <input type="hidden" name="alterskontrolle_de_options" value="checkpost" />

  <h3>Allgemeine Daten</h3>

  <table class="wp-list-table widefat" style="background:#F7F7F7;">
    <tr>
      <td>
        <table class="form-table">
          <tr>
            <th>Webmaster-ID</th>
            <td>
              <input type="text" class="regular-text" name="akdata[ak_wid]" value="<?php echo $options->ak_wid; ?>" />
              <p class="description">Gib hier bitte die WondoCash.com Webmaster-ID ein.</p>
            </td>
          </tr>
          <tr>
            <th>Alterkontrolle.de-Apikey</th>
            <td>
              <input type="text" class="regular-text" name="akdata[ak_apikey]" value="<?php echo $options->ak_apikey; ?>" />
              <p class="description">Gib hier bitte den WondoCash.com Alterkontrolle.de Apikey ein. (<a target="_blank" href="http://login.wondocash.com/login/?mod=avssys&func=akdeapi">Hier zu finden</a>)</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <h3>Konfiguration</h3>

  <table class="wp-list-table widefat" style="background:#F7F7F7;">
    <tr>
      <td>
        <table class="form-table">
          <tr>
            <th>Programmtyp</th>
            <td>
              <select class="ak_programm_type" name="akdata[ak_programm_type]">
                <option value=""<?php if($options->ak_programm_type == '') { echo ' selected="selected"'; } ?>>Bitte auswählen</option>
                <option value="aktor"<?php if($options->ak_programm_type == 'aktor') { echo ' selected="selected"'; } ?>>Alterskontrolle.de</option>
                <option value="abo"<?php if($options->ak_programm_type == 'abo') { echo ' selected="selected"'; } ?>>Abo-Seite</option>
              </select>
              <br />
              <p class="description">Wähle den Programmtyp aus.</p>
            </td>
          </tr>
          <tr>
            <th>Alterskontrolle.de Linktyp</th>
            <td>
              <select name="akdata[ak_linktyp]">
                <option value=""<?php if($options->ak_linktyp == '') { echo ' selected="selected"'; } ?>>Bitte auswählen</option>
                <option value="aksite"<?php if($options->ak_linktyp == 'aksite') { echo ' selected="selected"'; } ?>>Alterskontrolle.de</option>
                <option value="payment"<?php if($options->ak_linktyp == 'payment') { echo ' selected="selected"'; } ?>>Paymentseite</option>
              </select>
              <br />
              <p class="description">Wähle den Linktyp für die Werbelinks des Alterskontrolle.de Tors aus. Entwerder wird der Kunde direkt zur Paymentseite geleitet oder auf die Alterskontrolle.de Webseite.</p>
            </td>
          </tr>
          <tr>
            <th>Alterskontrolle.de Standard-Nachricht</th>
            <td>
              <input type="text" class="large-text" name="akdata[ak_tor_message]" value="<?php echo $options->ak_tor_message; ?>" />
              <br />
              <p class="description">Platzhaltertext für die Standard-Nachricht über dem Alterskontrolle-Tor, wenn bei Seite oder Beitrag "Schützen mit Alterskontrolle.de" angehakt wurde.</p>
            </td>
          </tr>
          <tr class="abo-show">
            <th>Abo-Programm-ID</th>
            <td>
              <input type="text" class="regular-text" name="akdata[abo_id]" value="<?php echo $options->abo_id; ?>" />
              <p class="description">Wenn der Programmtyp der Abo-Seite entspricht, wird die Programm-ID benötigt. Diese wird nach Absprache von WondoCash.com bereitgestellt.</p>
            </td>
          </tr>
          <tr class="abo-show">
            <th>Programm-Titel</th>
            <td>
              <input type="text" class="large-text" name="akdata[abo_title]" value="<?php echo $options->abo_title; ?>" />
              <p class="description">Wenn der Programmtyp der Abo-Seite entspricht, wird der Programm-Titel benötigt. Dieser wird im Tor angezeigt.</p>
            </td>
          </tr>
          <tr>
            <th>Torauswahl</th>
            <td>
              <select class="ak_torauswahl" name="akdata[ak_torauswahl]">
                <option value=""<?php if($options->ak_torauswahl == '') { echo ' selected="selected"'; } ?>>Bitte auswählen</option>
                <option value="aktor"<?php if($options->ak_torauswahl == 'aktor') { echo ' selected="selected"'; } ?>>Alterskontrolle.de Standard Tor</option>
                <option value="abo"<?php if($options->ak_torauswahl == 'abo') { echo ' selected="selected"'; } ?>>Abo Standard Tor</option>
                <option value="tpl"<?php if($options->ak_torauswahl == 'tpl') { echo ' selected="selected"'; } ?>>Eigenes Layout (CSS / Grafik)</option>
              </select>
              <br />
              <p class="description">Wähle das passende Tor aus.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <div class="tor-tpl">
    <h3>Optionales Tor-Template</h3>

    <table class="wp-list-table widefat" style="background:#F7F7F7;">
      <tr>
        <td>
          <table class="form-table">
            <tr>
              <th><p>Inhaltsbereich vor dem User-ID-Feld</p>
                <img src="<?php echo plugins_url('/alterskontrolle.de/pics/before.png'); ?>" alt="" />
              </th>
              <td>
                <?php
                  wp_editor( wp_kses_stripslashes($options->ak_tpl_before) , 'ak_tpl_before', array(
                      'wpautop'       => true,
                      'media_buttons' => true,
                      'textarea_name' => 'akdata[ak_tpl_before]',
                      'editor_class'  => '',
                      'textarea_rows' => 10
                  ) );
                ?>
                <p class="description">Hier ist auch HTML erlaubt.</p>
              </td>
            </tr>
            <th><p>Inhaltsbereich nach dem dem Passwort-Feld</p>
              <img src="<?php echo plugins_url('/alterskontrolle.de/pics/after.png'); ?>" alt="" /></th>
              <td>
                <?php
                  wp_editor( wp_kses_stripslashes($options->ak_tpl_after) , 'ak_tpl_after', array(
                      'wpautop'       => true,
                      'media_buttons' => true,
                      'textarea_name' => 'akdata[ak_tpl_after]',
                      'editor_class'  => '',
                      'textarea_rows' => 10
                  ) );
                ?>
                <p class="description">Hier ist auch HTML erlaubt.</p>
              </td>
            </tr>
            <tr>
              <th>
                Weitere Anpassungen
              </th>
              <td>
                  <span class="description">Das Aussehen des eigenen Layouts für das Tor, lässt sich unter Anderem aber auch durch Anpassungen per CSS individuell verändern.</span>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </div>

  <p class="submit">
    <input id="submit" class="button button-primary" name="submit" value="Änderungen übernehmen" type="submit" />
  </p>
</form>

</div><!-- td -->
<div class="td ak-plugin-second" style="width:386px;">

  <div style="height:470px; margin-top:-230px; position:fixed; top:50%; right:20px; z-index:100;">
    <h3>Tor Vorschau</h3>

    <table class="wp-list-table widefat" style="background:#F7F7F7;">
      <tr>
        <td>
          <?php echo do_shortcode('[ak_plugin_code_preview]'); ?>
        </td>
      </tr>
    </table>
  </div>

</div><!-- td -->
</div><!-- tr -->
</div><!-- table -->

<script>
  jQuery(document).ready(function($) {

    // ak_programm_type = ABO

    $('.abo-show').hide();

    var ak_programm_type = $('.ak_programm_type').val();
    if(ak_programm_type == 'abo') {
      $('.abo-show').show();
    }

    $('.ak_programm_type').on('change',function(){

      var ak_programm_type = $('.ak_programm_type').val();
      if(ak_programm_type == 'abo') {
        $('.abo-show').show();
      } else {
        $('.abo-show').hide();
      }

    });

    // ak_torauswahl = TPL

    $('.tor-tpl').hide();

    var ak_torauswahl = $('.ak_torauswahl').val();
    if(ak_torauswahl == 'tpl') {
      $('.tor-tpl').show();
    }

    $('.ak_torauswahl').on('change',function(){

      var ak_torauswahl = $('.ak_torauswahl').val();
      if(ak_torauswahl == 'tpl') {
        $('.tor-tpl').show();
      } else {
        $('.tor-tpl').hide();
      }

    });

    $('.ak_11055d29911457d69ea70ef9517a1c94 form').on('submit',function(e){
      e.preventDefault();
    });



  });


</script>

</div>

<?php }

  //add admin page
  function add_menu() {
      add_options_page('Alterkontrolle.de - Plugin Konfiguration', 'Alterskontrolle.de', 'manage_options', 'alterskontrolle.de', array($this,'aktor_option_page'));
  }

  function ak_meta_init() {

      $options = json_decode(get_option('alterskontrolle_de_options'), false, 512, JSON_UNESCAPED_UNICODE);

      if($options->ak_programm_type == 'aktor') {
        $title = 'Alterskontrolle / Site-ID';
      } else {
        $title = 'Abo schützen';
      }

        foreach (array('post', 'page') as $type) {
            add_meta_box('ak_all_meta', $title, array($this,'ak_meta_setup'), $type, 'normal', 'high');
        }

        add_action('save_post', array($this,'ak_meta_save'));

        if(isset($_POST['alterskontrolle_de_options']) && $_POST['alterskontrolle_de_options']) {
          header('Location: '.home_url('/wp-admin/options-general.php?page=alterskontrolle.de'));
        }

  }

  function ak_meta_setup() {
      global $post;

      // using an underscore, prevents the meta variable
      // from showing up in the custom fields section
      $meta = get_post_meta($post->ID, '_ak_meta', TRUE);

      // instead of writing HTML here, lets do an include
      $this->ak_meta_box();

      // create a custom nonce for submit verification later
      echo '<input type="hidden" name="ak_meta_noncename" value="'.wp_create_nonce(__FILE__).'" />';
  }

  function ak_meta_save($post_id) {
      // authentication checks

      // make sure data came from our meta box
      if (isset($_POST['ak_meta_noncename']) && !wp_verify_nonce($_POST['ak_meta_noncename'], __FILE__))
          return $post_id;

      // check user permissions
      if (isset($_POST['post_type']) && $_POST['post_type'] == 'page') {
          if (!current_user_can('edit_page', $post_id))
              return $post_id;
      } else {
          if (!current_user_can('edit_post', $post_id))
              return $post_id;
      }

      // authentication passed, save data
      $current_data = get_post_meta($post_id, '_ak_meta', TRUE);

      $new_data = (isset($_POST['_ak_meta'])) ? $_POST['_ak_meta'] : '';

	  if($new_data) {

		  $this->ak_meta_clean($new_data);

		  if ($current_data) {
			  if (is_null($new_data))
				  delete_post_meta($post_id, '_ak_meta');
			  else
				  update_post_meta($post_id, '_ak_meta', $new_data);
		  } elseif (!is_null($new_data)) {
			  add_post_meta($post_id, '_ak_meta', $new_data, TRUE);
		  }

	  }

      return $post_id;
  }

  function ak_meta_clean(&$arr) {
      if (is_array($arr)) {
          foreach ($arr as $i=>$v) {
              if (is_array($arr[$i])) {
                  ak_meta_clean($arr[$i]);

                  if (!count($arr[$i])) {
                      unset($arr[$i]);
                  }
              } else {
                  if (trim($arr[$i]) == '') {
                      unset($arr[$i]);
                  }
              }
          }

          if (!count($arr)) {
              $arr = NULL;
          }
      }
  }

  function ak_meta_box() {
      global $post;
      $meta = get_post_meta($post->ID, '_ak_meta', TRUE);

      $mc_check = (isset($meta['check']) && $meta['check']) ? $meta['check'] : 'no';
      $mc_sid = (isset($meta['sid']) && $meta['sid']) ? $meta['sid'] : '';

      $options = json_decode(get_option('alterskontrolle_de_options'), false, 512, JSON_UNESCAPED_UNICODE);

      if($options->ak_programm_type == 'aktor') {
        $show = true;
      } else {
        $show = false;
      }

  ?>

  <div class="ak_meta_control">

    <?php if($show) { ?>
    <p>Den kompletten Inhalt mit Alterskontrolle.de schützen? <input type="checkbox" name="_ak_meta[check]" value="1"<?php if ($mc_check == "1") echo 'checked="checked"'; ?>/></p>

    Alterskontrolle.de Site-ID<br />

    <input type="text" class="normal-text" name="_ak_meta[sid]" value="<?php echo $mc_sid; ?>" />
    <p class="description">Site-ID des angelegten alterskontrolle.de Tores.</p>
    <?php } else { ?>
      <p>Den kompletten Inhalt mit schützen? <input type="checkbox" name="_ak_meta[check]" value="1"<?php if ($mc_check == "1") echo 'checked="checked"'; ?>/></p>

    <?php } ?>

  </div>

  <?php
  }

  /* Add Shortcode Button */
	function ak_sc_mce_external_plugins( $plugin_array ) {
		$plugin_array['ak_sc_button'] = plugins_url( '/js/shortcode.js' , __FILE__ );
		return $plugin_array;
	}

	function ak_sc_mce_buttons_2( $buttons ) {
		if ( ! $pos = array_search( 'Redo', $buttons ) ) {
			array_push( $buttons, 'ak_sc_button' );
			return $buttons;
		}

		$buttons = array_merge( array_slice( $buttons, 0, $pos ), array( 'ak_sc_button' ), array_slice( $buttons, $pos ) );

		return $buttons;
	}

  /* Style */
 	function add_new_style() {
    wp_enqueue_style( 'dashicons' );
 	?>
<link rel="stylesheet" type="text/css" href="<?php echo plugins_url( 'css/base.css' , __FILE__ ); ?>" />
<script src="<?php echo plugins_url( 'js/js.js' , __FILE__ ); ?>"></script>
 	<?php
 	}

  function add_new_admin_style() {

    wp_enqueue_style( 'ak-plugin-base', plugins_url( '/css/base.css' , __FILE__ ) );
    wp_enqueue_style( 'ak-plugin-icon', plugins_url( '/css/icon.css' , __FILE__ ) );
    wp_enqueue_script( 'ak-plugin-js', plugins_url( '/js/js.js' , __FILE__ ) );

  }

  /* Add Link to Settingspage */
  function plugin_settings_link($links) {
	$links['settings'] = '<a href="/wp-admin/options-general.php?page=alterskontrolle.de">'.__('Settings').'</a>';
      return $links;
  }

  /* Check if Session is already started */
  function is_session()
  {
      if ( php_sapi_name() !== 'cli' ) {
          if ( version_compare(phpversion(), '5.4.0', '>=') ) {
              return session_status() === PHP_SESSION_ACTIVE ? true : false;
          } else {
              return session_id() === '' ? false : true;
          }
      }
      return false;
  }

 }

 $alterskontrolle_dePlugin = new alterskontrolle_dePlugin();
