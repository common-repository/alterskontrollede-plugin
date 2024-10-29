jQuery( document ).ready(function($) {

  $('.ak_11055d29911457d69ea70ef9517a1c94 .ak_hide').show();

  $(".ak_11055d29911457d69ea70ef9517a1c94 form button").on('click', function (e) {

    var ak_button = $(this);

    var ak_userid = ak_button.closest("form").find("input[name=userid]").val();
    var ak_pw = ak_button.closest("form").find("input[name=pw]").val();

    if(ak_pw && ak_userid) {

      $.ajax({
        url: '/',
        data: {get_ak_Cpw: ak_pw},
        type: 'post',
        success: function(output) {
                          ak_button.closest("form").find("input[name=pw]").attr('value',output);
                          ak_button.closest("form").submit();
                        }
      });

    }

    e.preventDefault();

  });

  $('.ak_11055d29911457d69ea70ef9517a1c94 form').keydown(function(event){
    if(event.keyCode == 13) {
      event.preventDefault();
      return false;
    }
  });


});