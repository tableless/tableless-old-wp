var tableless = (function() {
  'use strict';

  function init(){
    prettyPrintHighlight();
  }

  //
  // Start PrettyPrint
  //
  function prettyPrintHighlight(){
    var $root = document.querySelector('html');
    if ($root.classList.contains('single')) {
      preHighlight();
      prettyPrint();
    }
  }

  function preHighlight() {
    var $pre = document.querySelectorAll('pre');

    for (var i = 0; i < $pre.length; i++) {
      $pre[i].classList.add('prettyprint', 'linenums');
    }
  }

  return {
    init: init,
    preHighlight: preHighlight
  }

}());

(function(){
  tableless.init();
}());
