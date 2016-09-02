<div class="tb-comments">
  <div id="disqus_thread"></div>
  <script>
    var disqus_config = function () {
      this.page.url = '<?php echo get_permalink(); ?>';
      this.page.identifier = '<?php the_ID();?>'; 
    };
    
    (function() {  // DON'T EDIT BELOW THIS LINE
        var d = document, s = d.createElement('script');
        
        s.src = '//tableless.disqus.com/embed.js';
        
        s.setAttribute('data-timestamp', +new Date());
        (d.head || d.body).appendChild(s);
    })();
  </script>

</div>
