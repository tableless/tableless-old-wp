<div class="sendgrid-filters-container <?php echo ( version_compare( get_bloginfo("version"), '3.7.10', '>' ) ? "wordpress-new" : "" ); ?>">
  <div class="sendgrid-filters">
    <input type="hidden" id="sendgrid-statistics-type" name="sendgrid-statistics-type" value="wordpress" />
    <div class="pull-left">
      <label for="sendgrid-start-date">Start date</label>
      <input type="text" id="sendgrid-start-date" name="sendgrid_start_date" />
    </div>
    <div class="pull-left">
      <label for="sendgrid-end-date">End date</label>
      <input type="text" id="sendgrid-end-date" name="sendgrid_end_date" />
    </div>
    <a href="#" id="sendgrid-apply-filter" data-filter="<?php if ( isset( $_GET['page'] ) and "sendgrid-statistics" == sanitize_text_field( $_GET['page'] ) ) { ?>sendgrid-statistics<?php } else { ?>dashboard<?php } ?>" class="button button-primary">Apply</a>
  </div>
  <div class="loading"><img src="<?php echo plugin_dir_url( __FILE__ ); ?>../images/loader.gif" style="width: 15px; height: 15px;" /></div>
</div>
<br class="clearfix-clear"/>
<div class="sendgrid-container 
  <?php echo ( ( version_compare( get_bloginfo( "version" ), '3.7.10', '>' ) and ! isset( $_GET['page'] ) ) ? "wordpress-dashboard-new" : "" ); ?>
  <?php echo ( version_compare( get_bloginfo( "version" ), '3.7.10', '>' ) ? "wordpress-new" : ""); ?>" style="position:relative;">
  <div class="widget others" id="deliveries">	
    <div class="widget-top">
      <div class="widget-title"><h4>Deliveries</h4></div>
    </div>
    <div class="widget-inside">
      <div class="row clearfix">
        <div class="pull-left">
          <span class="square" style="background-color: rgb(50,135,1);"></span><span>Requests</span>
        </div>
        <div id="requests" class="pull-right">0</div>
      </div>
      <div class="row clearfix">
        <div class="pull-left">
          <span class="square" style="background-color: rgb(188,213,22);"></span><span>Drop</span>
        </div>
        <div id="drop" class="pull-right">0%</div>
      </div>
      <div class="row clearfix">
        <div class="pull-left">
          <span class="square" style="background-color: rgb(251,166,23);"></span><span>Delivered</span>
        </div>
        <div id="delivered" class="pull-right">0%</div>
      </div>
    </div>
  </div>
  
  <div class="widget others" id="compliance">	
    <div class="widget-top">
      <div class="widget-title"><h4>Compliance</h4></div>
    </div>
    <div class="widget-inside">
      <div class="row clearfix">
        <div class="pull-left">
          <span class="square" style="background-color: rgb(251,229,0);"></span><span>Spam Reports</span>
        </div>
        <div id="spam-reports" class="pull-right">0%</div>
      </div>
      <div class="row clearfix">
        <div class="pull-left">
          <span class="square" style="background-color: rgb(17,133,193);"></span><span>Bounces</span>
        </div>
        <div id="bounces" class="pull-right">0%</div>
      </div>
      <div class="row clearfix">
        <div class="pull-left">
          <span class="square" style="background-color: rgb(188,208,209);"></span><span>Blocks</span>
        </div>
        <div id="blocks" class="pull-right">0%</div>
      </div>
    </div>
  </div>
  
  <div class="widget others" id="engagement">	
    <div class="widget-top">
      <div class="widget-title"><h4>Engagement</h4></div>
    </div>
    <div class="widget-inside">
      <div class="row clearfix">
        <div class="pull-left">
          <span class="square" style="background-color: rgb(62,68,192);"></span><span>Unsubscribes</span>
        </div>
        <div id="unsubscribes" class="pull-right">0%</div>
      </div>
      <div class="row clearfix">
        <div class="pull-left">
          <span class="square" style="background-color: rgb(255,0,224);"></span><span>Unique Opens</span>
        </div>
        <div id="unique-opens" class="pull-right">0%</div>
      </div>
      <div class="row clearfix">
        <div class="pull-left">
          <span class="square" style="background-color: rgb(224,68,40);"></span><span>Opens</span>
        </div>
        <div id="opens" class="pull-right">0%</div>
      </div>
      <div class="row clearfix">
        <div class="pull-left">
          <span class="square" style="background-color: rgb(50,135,1);"></span><span>Clicks</span>
        </div>
        <div id="clicks" class="pull-right">0%</div>
      </div>
    </div>
  </div>
  <br class="clearfix-clear"/>
  
  <?php if ( ! isset ($_GET['page'] ) or "sendgrid-statistics" != sanitize_text_field( $_GET['page'] ) ) { ?>
    <a href="index.php?page=sendgrid-statistics" class="more-statistics">See charts</a>
    <br class="clearfix-clear"/>
  <?php } ?>
</div>
