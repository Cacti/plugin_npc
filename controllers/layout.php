<?php /* ex: set tabstop=4 expandtab: */


class NpcLayoutController extends controller {

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
          <link rel="stylesheet" type="text/css" href="<?php echo $config["url_path"]; ?>plugins/npc/css/ext-ux-livegrid.css" />

          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/adapter/ext/ext-base.js"></script>
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/ext/ext-all.js"></script>

          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/npc-all-min.js"></script>
          
          <script type="text/javascript">

            // Add some properties to the params array
            npc.params.npc_portlet_refresh = 60;
            npc.params.npc_date_format     = "<?php echo read_config_option('npc_date_format'); ?>";
            npc.params.npc_time_format     = "<?php echo read_config_option('npc_time_format'); ?>";
            npc.params.npc_nagios_url      = "<?php echo read_config_option('npc_nagios_url'); ?>";
            npc.params.userName            = "<?php echo db_fetch_cell('SELECT username FROM user_auth WHERE id = ' . $_SESSION['sess_user_id']); ?>";

            <?php $state = unserialize(db_fetch_cell('SELECT settings FROM npc_settings WHERE user_id = ' . $_SESSION['sess_user_id'])); ?>
            var ExtState = Ext.decode('<?php echo json_encode($state); ?>');

            // Launch the app
            Ext.onReady(npc.init, npc);

            Ext.onReady(function() {
                Ext.state.Manager.setProvider(new Ext.state.HttpProvider({url: 'npc.php?module=settings&action=save'}));
                Ext.QuickTips.init();
            });
          </script>

          <!-- Portlets -->
          <script type="text/javascript" src="<?php echo $config["url_path"]; ?>plugins/npc/js/portlets-all-min.js"></script>

          <script type="text/javascript">
            Ext.onReady(function() {
              npc.initPortlets();
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
