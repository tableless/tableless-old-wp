class Tableless {

  //
  // Initialize
  //
  init() {
    this.showFeaturedPosts();
    this.prettyPrintHighlight();
    this.openCloseSearch();
    this.searchAjax();
  }

  //
  // Start PrettyPrint
  //
  preHighlight() {
    let $pre = document.querySelectorAll('pre');

    for (let i = 0; i < $pre.length; i++) {
      $pre[i].classList.add('prettyprint', 'linenums');
    }
  }

  prettyPrintHighlight() {
    let $root = document.querySelector('html');
    if ($root.classList.contains('single')) {
      this.preHighlight();
      prettyPrint();
    }
  }

  ///
  /// Open Search Box
  ///
  openCloseSearch() {
    $('.tb-search-btn, .tb-close-search').on('click', (e) => {
      e.preventDefault();
      $('.tb-search-box').toggleClass('tb-is-active');
      $('#s').focus();
    });
  }

  ///
  // Ajax call to show search results
  ///
  searchAjax() {
    $('#searchsubmit').on('click', (e) => {
      e.preventDefault();
      let searchTerm = $('#s').val();
      $.ajax({
        url: `http://tableless.com.br/?s=${searchTerm}`,
        type : 'get',
        success: (data) => {
          let dataContent = $('<div class="tb-data-box">').html(data);
          dataContent = dataContent.find('.tb-search-content').html();

          $('.tb-search-results-list').html( dataContent );
        }
      });

    });
  }

  ///
  // Featured posts in Home
  ///
  showFeaturedPosts() {
    $('.tb-featured-post:first').addClass('tb-is-active');

    $('.tb-thumb-box').on('click', (e) => {
      e.preventDefault();
      let $target = $(this).data('target');
      let $targetPost = $(`#${$target}`);
      $('.tb-featured-post').removeClass('tb-is-active');
      $targetPost.addClass('tb-is-active');
      console.log($targetPost);
    });
  }
}

const tableless = new Tableless();
tableless.init();