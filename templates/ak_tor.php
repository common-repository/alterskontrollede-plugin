<?php

  /* WERBE URLS */

  if($options->ak_linktyp == 'aksite') {
    $url = 'http://www.alterskontrolle.de/user/user_info.html?wid='.$options->ak_wid.'&amp;sid=173684';
  } elseif($options->ak_linktyp == 'payment') {
    $url = 'https://www.online-pay.net/payment/step1/?wid='.$options->ak_wid.'&pid=1#zugang';
  } else {
    $url = '';
  }

  $sid = get_post_meta($post_id, '_ak_meta', TRUE);
  $sid = (isset($sid['sid']) && $sid['sid']) ? $sid['sid'] : '';

?>
<div class="ak_11055d29911457d69ea70ef9517a1c94">
  <?php if($sid) { ?><div data-ak-tor-site-id="<?php echo $sid; ?>"></div><?php } ?>
  <div class="ak_tor">

    <div class="ak_main">
      <form method="post" action="<?php echo  home_url($wp->request.'/#post-'.$post_id); ?>">

        <?php if($sid) { ?><input type="hidden" name="sid" value="<?php echo $sid; ?>" /><?php } ?>
        <input type="hidden" name="pid" value="<?php echo $post_id; ?>" />

        <div class="ak_container">

          <div class="ak_headline">
            <?php if($url) { ?><a href="<?php echo $url; ?>" target="_blank"><?php } ?>Alterskontrolle.de - Login<?php if($url) { ?></a><?php } ?>
          </div>

          <div class="ak_content">

          <p>Mit nur einer ID, Zugriff auf Inhalte für Erwachsene!</p>

          <?php if($message) { ?><div class="ak_plugin_message"><?php echo $message; ?></div><?php } ?>

          <div class="ak_hide">
              <div class="table" style="width:100%; margin-bottom:10px;">
                <div class="tr">
                  <div class="td icon">
                    <span class="dashicons dashicons-admin-users"></span>
                  </div>
                  <div class="td">
                    <input type="text" name="userid" placeholder="User-ID" tabindex="1" />
                  </div>
                </div>
              </div>

              <div class="table" style="width:100%; margin-bottom:20px;">
                <div class="tr">
                  <div class="td icon">
                    <span class="dashicons dashicons-lock"></span>
                  </div>
                  <div class="td">
                    <input type="password" name="pw" placeholder="Passwort" tabindex="2" />
                  </div>
                </div>
              </div>
            </div>
            <noscript>
              <div class="ak_nojs_message">
                Um den vollen Funktionsumfang dieser Webseite zu erfahren, benötigen Sie JavaScript. Hier finden Sie die <a href="http://www.enable-javascript.com/de/" target="_blank">Anleitung wie Sie JavaScript in Ihrem Browser einschalten</a>.
              </div>
            </noscript>

            Diese Seite wird durch <?php if($url) { ?><a href="<?php echo $url; ?>" target="_blank"><?php } ?>Alterskontrolle.de<?php if($url) { ?></a><?php } ?> geschützt!

          </div>

          <div class="ak_hide">
            <div class="ak_bottomline"><button>einloggen</button></div>
          </div>
        </div>

      </form>
    </div>

  </div>
</div>