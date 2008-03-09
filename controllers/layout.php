<?php /* ex: set tabstop=4 expandtab: */


class NpcLayoutController {

    var $params = array();

    function drawFrame($params) {

        $config = $params['config'];

        include_once($config['include_path'] . "/top_graph_header.php");

    ?>

        <style type="text/css">
            iframe {border: 0px;}
        </style>

        <iframe src="<?php echo $config["url_path"]; ?>plugins/npc/npc.php?module=layout&action=drawLayout" frameborder="0" 
                border="0" width="100%" height="100%" marginwidth="0" marginheight="0"></iframe>

    <?php

        include_once($config["include_path"] . "/bottom_footer.php");

    } // end drawFrame


    function drawLayout($params) {

        $config = $params['config'];
    ?>

        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html>
        <head>
          <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/resources/css/ext-all.css" />
          <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/resources/css/xtheme-slate.css" />
          <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/css/main.css" />
          <!-- <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/resources/css/xtheme-darkgray.css" /> -->
          <!-- <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/resources/css/xtheme-gray.css" /> -->

          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/adapter/ext/ext-base.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/ext-all-debug.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/plugins.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/overrides.js"></script>

          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/dashboard/Portal.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/dashboard/PortalColumn.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/dashboard/Portlet.js"></script>

          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/npc.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/n2c.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/hosts.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/hostgroupGrid.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/services.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/serviceDetail.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/servicegroupGrid.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/servicegroupOverview.js"></script>
          
          <script type="text/javascript">

            // Add some properties to the params array
            npc.app.params.npc_portlet_refresh = "<?php echo read_config_option('npc_portlet_refresh'); ?>";
            npc.app.params.npc_portlet_rows    = "<?php echo read_config_option('npc_portlet_rows'); ?>";
            npc.app.params.npc_date_format     = "<?php echo read_config_option('npc_date_format'); ?>";
            npc.app.params.npc_time_format     = "<?php echo read_config_option('npc_time_format'); ?>";
            npc.app.params.npc_nagios_url      = "<?php echo read_config_option('npc_nagios_url'); ?>";
            npc.app.params.userName            = "<?php echo db_fetch_cell('SELECT username FROM user_auth WHERE id = ' . $_SESSION['sess_user_id']); ?>";

            <?php $state = unserialize(db_fetch_cell('SELECT settings FROM npc_settings WHERE user_id = ' . $_SESSION['sess_user_id'])); ?>
            var ExtState = Ext.decode('<?php echo json_encode($state); ?>');

            // Launch the app
            Ext.onReady(npc.app.init, npc.app);

            Ext.onReady(function() {
                //Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
                Ext.state.Manager.setProvider(new Ext.state.HttpProvider({url: 'npc.php?module=settings&action=save'}));
                Ext.QuickTips.init();
            });
          </script>

          <!-- Portlets -->
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/dashboard/host-summary-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/dashboard/service-summary-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/dashboard/eventlog-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/dashboard/service-problems-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/dashboard/monitoring-performance-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/dashboard/servicegroup-service-status-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/dashboard/servicegroup-host-status-portlet.js"></script>

          <script type="text/javascript">
            Ext.onReady(function() {
              //console.log(Ext.state.Manager.get('serviceProblems'));
              npc.app.initPortlets();
            });
          </script>

        </head>

         <body>

          <div id="msg-div"></div>

          <div id="west">

            <div id="west-monitoring"></div>

            <div id="west-config"></div>

          </div>

          <div id="props-panel" style="width:200px;height:200px;overflow:hidden;"></div>

          <div id="south"></div>

         </body>
        </html>

    <?php

    } // end drawLayout
}
