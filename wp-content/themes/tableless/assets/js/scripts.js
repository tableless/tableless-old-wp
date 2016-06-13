var tableless = (function() {
  'use strict';

  function init(){
    showFeaturedPosts();
    prettyPrintHighlight();
    openCloseSearch();
    searchAjax();
    showMenu();

    if(isMobile()){
      initCarousel();
    }

  }

  function initCarousel(){
    $('.tb-featured-post').addClass('tb-is-active');
    $('.tb-is-active').css('position', 'relative');
    $('.owl-wrapper-outer').css('height', '100%');
    $('.tb-featured-inner').owlCarousel({
      slideSpeed : 300,
      paginationSpeed : 400,
      singleItem: true,
      autoPlay : true,
      transitionStyle: 'goDown' // fade, backSlide, goDown and scaleUp
    });
  }

  function screenWidth(){
    return Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
  }

  function screenHeight(){
    return Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
  }

  function isMobile(){
    return (screenWidth() <= 995);
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
