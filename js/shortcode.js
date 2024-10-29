(function() {
    tinymce.PluginManager.add('ak_sc_button', function( editor, url ) {

		function vlContent(shortcode, more = '') {

			if(more) { more = ' ' + more; }

			if(editor.selection.getContent() != ''){

				editor.selection.setContent('[' + shortcode + more + ']' + editor.selection.getContent() + '[/' + shortcode + ']');
			} else if(editor.selection.getContent() == '') {
				// editor.selection.setContent('[' + shortcode + more + ']');
			}

		}

    function CleanText() {

      var content = editor.getContent();
      var new_content = content.replace(/\[(\w+)[^\]]*](.*?)\[\/\1]/g, '$2');
      editor.setContent(new_content);

    }

        editor.addButton( 'ak_sc_button', {
            type: 'menubutton',
            text: 'SecureArea',
			      icon: 'icon ak_sc_dashicons ak_sc_dashicons-secure',
            menu: [
              {
				text: 'Geschützter Bereich',
				onclick: function() {
				  vlContent('ak_secure_area');
				}
			  },
              {
                text: 'Kurzer Benachrichtigungstext über dem geschützten Bereich',
                onclick: function() {
                  vlContent('ak_secure_message');
                }
              },
              {
                text: 'Alle Shortcodes entfernen',
                onclick: function() {
                  CleanText();
                }
              }
           ]
        });

    });
})();