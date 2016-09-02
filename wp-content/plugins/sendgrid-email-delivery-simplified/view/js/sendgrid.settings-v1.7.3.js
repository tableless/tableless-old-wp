jQuery(document).ready(function($) {

  if ( $('#auth_method').find("option:selected").val() == 'apikey' ) {
    $(".apikey").show();
    $(".credentials").hide();
    $(".send_method").show();
  } else if ( $('#auth_method').find("option:selected").val() == 'credentials' ) {
    $(".apikey").hide();
    $(".credentials").show();
    $(".send_method").show();
  }

  if ( $('#send_method').find("option:selected").val() == 'api' ) {
    $(".port").hide();
  } else if ( $('#send_method').find("option:selected").val() == 'smtp' ) {
    $(".port").show();
  }

  $('#auth_method').change(function() {
    authMethod = $(this).find("option:selected").val();
    if ( authMethod == 'apikey' ) {
      $(".apikey").show();
      $(".credentials").hide();
    } else {
      $(".apikey").hide();
      $(".credentials").show();
    }
  });

  $('#send_method').change(function() {
    sendMethod = $(this).find("option:selected").val();
    if ( sendMethod == 'api' ) {
      $(".port").hide();
    } else {
      $(".port").show();
    }
  });
});