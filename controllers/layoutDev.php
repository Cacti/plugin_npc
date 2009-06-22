<?php 
/* ex: set tabstop=4 expandtab: */

class NpcLayoutDevController extends controller {

    var $params = array();

    function drawFrame($params) {

        $config = $params['config'];

        include_once($config["base_path"]."/plugins/npc/top_graph_header.php");

    ?>

        <style type="text/css">
            iframe {border: 0px;}
        </style>

        <iframe src="<?php echo $config["url_path"]; ?>plugins/npc/npc.php?module=layoutDev&action=drawLayout" frameborder="0" 
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
          <!-- <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/resources/css/xtheme-darkgray.css" /> -->
          <!-- <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/resources/css/xtheme-gray.css" /> -->
          <!-- <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/resources/css/xtheme-gray-extend.css" /> -->

          <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/css/main.css" />
          <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/css/ext-ux-livegrid.css" />

          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/adapter/ext/ext-base.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/ext-all-debug.js"></script>

          <!-- Plugins -->
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/plugins/Ext.state.HttpProvider.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/plugins/Ext.ux.form.XCheckbox.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/plugins/Ext.ux.grid.Search.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/plugins/Ext.ux.Andrie.pPageSize.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/plugins/Ext.ux.LiveGrid.js"></script>

          <!-- Overrides -->
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/overrides/overrides.js"></script>

          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/Portal.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/PortalColumn.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/Portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/npc.js"></script>

          <!-- Host screens -->
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/hosts/hosts.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/hosts/hostDetail.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/hosts/hostgroupGrid.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/hosts/hostgroupOverview.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/hosts/hostCommandMenu.js"></script>

          <!-- Service screens -->
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/services/services.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/services/serviceDetail.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/services/servicegroupGrid.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/services/servicegroupOverview.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/services/serviceCommandMenu.js"></script>

          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/n2c.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/comments.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/downtime.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/processInfo.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/reporting.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/eventLog.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/monitoring/commandForms.js"></script>

          <script type="text/javascript">

            // Add some properties to the params array
            npc.params.npc_portlet_refresh = <?php echo read_config_option('npc_portlet_refresh'); ?>;
            npc.params.npc_date_format     = "<?php echo read_config_option('npc_date_format'); ?>";
            npc.params.npc_time_format     = "<?php echo read_config_option('npc_time_format'); ?>";
            npc.params.npc_nagios_url      = "<?php echo read_config_option('npc_nagios_url'); ?>";
            npc.params.userName            = "<?php echo db_fetch_cell('SELECT username FROM user_auth WHERE id = ' . $_SESSION['sess_user_id']); ?>";
            npc.params.npc_host_icons      = "<?php echo read_config_option('npc_host_icons'); ?>";
            npc.params.npc_service_icons   = "<?php echo read_config_option('npc_service_icons'); ?>";

            npc.params.cacti_path          = "<?php echo URL_PATH; ?>";
            var strLen = npc.params.cacti_path;
            if (npc.params.cacti_path.charAt(strLen-1) == '/') {
                npc.params.cacti_path = npc.params.cacti_path.slice(0,strLen-1);
            }

            <?php $state = unserialize(db_fetch_cell('SELECT settings FROM npc_settings WHERE user_id = ' . $_SESSION['sess_user_id'])); ?>
            var ExtState = Ext.decode('<?php echo json_encode($state); ?>');

            // Launch the app
            Ext.onReady(npc.init, npc);

            Ext.onReady(function() {
                //Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
                Ext.state.Manager.setProvider(new Ext.state.HttpProvider({url: 'npc.php?module=settings&action=save'}));
                Ext.QuickTips.init();
            });
          </script>

          <!-- Portlets -->
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/host-problems-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/eventlog-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/service-problems-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/monitoring-performance-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/servicegroup-service-status-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/servicegroup-host-status-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/hostgroup-host-status-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/hostgroup-service-status-portlet.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/hostSummaryOverview.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/serviceSummaryOverview.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/src/portlets/nagiosStatus.js"></script>

          <script type="text/javascript">
            Ext.onReady(function() {
              npc.initPortlets();
            });
          </script>

        </head>

         <body>

          <div id="north">
              <div id="msg-div"></div>
          </div>

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
