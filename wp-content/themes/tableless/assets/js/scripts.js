var tableless = (function() {
  'use strict';

  function init(){
    showFeaturedPosts();
    prettyPrintHighlight();
    openCloseSearch();
    searchAjax();
    showMenu();
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

  ///
  /// Open Search Box
  ///
  function openCloseSearch() {
    $('.tb-search-btn, .tb-close-search').on('click', function(e){
      e.preventDefault();
      $('.tb-search-box').toggleClass('tb-is-active');
      $('#s').focus();
    });
  }

  ///
  // Ajax call to show search results
  ///
  function searchAjax(){
    $('#searchsubmit').on('click', function(e){
      e.preventDefault();
      var searchTerm = $('#s').val();
      $.ajax({
        url: "http://tableless.com.br/?s=" + searchTerm,
        type : 'get',
        success: function( data ) {
          var dataContent = $('<div class="tb-data-box">').html(data).find('.tb-search-content').html();
          $('.tb-search-results-list').html( dataContent );
        }
      });

    });
  }


  ///
  // Featured posts in Home
  ///
  function showFeaturedPosts() {
    $('.tb-featured-post:first').addClass('tb-is-active');

    $('.tb-thumb-box').on('click', function(e){
      e.preventDefault();

      var $targetPost = $('#' + $(this).data('target'));
      $('.tb-featured-post').removeClass('tb-is-active');
      $targetPost.addClass('tb-is-active');
      console.log($targetPost);
    });
  }

  function showMenu(){
    $('.tb-sandwich').on('click', function(){
      $('.tb-menu').toggleClass('tb-is-active');
    });
  }

  return {
    init: init,
    preHighlight: preHighlight
  }

}());

(function(){
  tableless.init();
}());
