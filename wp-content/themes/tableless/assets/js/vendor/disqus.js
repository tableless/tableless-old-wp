    var disqus_config = function () {
      this.page.url = '<?php echo get_permalink(); ?>';
      this.page.identifier = '<?php echo dsq_identifier_for_post($post); ?>'; 
  };

   (function() {  // DON'T EDIT BELOW THIS LINE
        var d = document, s = d.createElement('script');
        
        s.src = '//tableless.disqus.com/embed.js';
        
        s.setAttribute('data-timestamp', +new Date());
        (d.head || d.body).appendChild(s);
    })();
/* * * CONFIGURATION VARIABLES * * */
//var disqus_shortname = 'tableless';

/* * * DON'T EDIT BELOW THIS LINE * * */
//(function() {
 //   var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
 //   dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
 //   (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
//})();
