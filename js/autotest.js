(function ($, Drupal, window) {

  'use strict';

  var global_timer = 0;
  var time_left = 0;

  function time () {
    return Math.floor(new Date().getTime() / 1000);
  }

  function pad(num, size) {
    var s = "000000000" + num;
    return s.substr(s.length-size);
  }

  function autotest_timer() {
    var timer = time() - global_timer;
    timer = time_left - timer;
    if(timer <= 0) {
      location.reload(true);
    }
    var minutes = pad(Math.floor(timer / 60), 2);
    var seconds = pad(timer % 60, 2);
    
    $('.timer').text(minutes + ':' + seconds);
    
    setTimeout(autotest_timer, 200);
  }

  
  Drupal.behaviors.trackerHistory = {
    attach: function (context, settings) {
      global_timer = time();
      time_left = settings.autotest.time_left;
      autotest_timer();
    }
  }
})(jQuery, Drupal, window);
