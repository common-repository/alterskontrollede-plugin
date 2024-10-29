<?php

class ak_widget_akde extends WP_Widget {

  function __construct() {
    parent::__construct('ak_widget_akde','Alterskontrolle.de', array( 'description' => 'Das Tor für Alterskontrolle oder Aboseiten.', ));
  }

  function widget($args, $instance) {
    if(is_single()) {
      echo do_shortcode('[ak_widget_plugin_code]');
    }
  }

  function form($instance) {

    echo '<div class="widget-content" style="padding:10px 0;">';
    echo 'Das Tor für Alterskontrolle oder Aboseiten.';
    echo '</div>';
    return 'noform';

  }

  function update( $new_instance, $old_instance ) {
      return $new_instance;
  }

}

// Register and load the widget
function ak_widget_akde_widget() {
	register_widget( 'ak_widget_akde' );
}

add_action( 'widgets_init', 'ak_widget_akde_widget' );

?>