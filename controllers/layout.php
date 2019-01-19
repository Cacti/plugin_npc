<?php

class NpcLayoutController extends controller {
    var $params = array();

    function drawFrame($params) {
        $config = $params['config'];

		$this->drawCommonFrame($config['url_path'] . 'plugins/npc/npc.php?module=layout&action=drawLayout', $params);
    }

    function drawLayout($params) {
		$config = $params['config'];
		$npc_base         = $config['url_path'] . 'plugins/npc/js/';
		$npc_ext_base     = $config['url_path'] . 'plugins/npc/js/ext/';
		$npc_css_base     = $config['url_path'] . 'plugins/npc/css/';
		?>

		<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jquery.js'></script>

		<link rel='stylesheet' type='text/css' href='<?php echo $npc_ext_base; ?>resources/css/ext-all.css' />
		<link rel='stylesheet' type='text/css' href='<?php echo $npc_ext_base; ?>resources/css/xtheme-slate.css' />
		<link rel='stylesheet' type='text/css' href='<?php echo $npc_css_base; ?>main.css' />
		<!-- <link rel="stylesheet" type="text/css" href="<?php echo $npc_ext_base; ?>resources/css/xtheme-darkgray.css" /> -->
		<!-- <link rel="stylesheet" type="text/css" href="<?php echo $npc_ext_base; ?>resources/css/xtheme-gray.css" /> -->
		<link rel='stylesheet' type='text/css' href='<?php echo $npc_css_base; ?>ext-ux-livegrid.css' />

		<script type='text/javascript' src='<?php echo $npc_ext_base; ?>adapter/ext/ext-base.js'></script>
		<script type='text/javascript' src='<?php echo $npc_ext_base; ?>ext-all.js'></script>
		<script type='text/javascript' src='<?php echo $npc_base; ?>npc-all-min.js'></script>
		<script type='text/javascript' src='<?php echo $npc_base; ?>portlets-all-min.js'></script>
		<script type='text/javascript'>

		// Add some properties to the params array
		npc.params.npc_portlet_refresh = <?php echo read_config_option('npc_portlet_refresh'); ?>;
		npc.params.npc_date_format     = '<?php echo read_config_option('npc_date_format'); ?>';
		npc.params.npc_time_format     = '<?php echo read_config_option('npc_time_format'); ?>';
		npc.params.npc_nagios_url      = '<?php echo read_config_option('npc_nagios_url'); ?>';
		npc.params.userName            = '<?php echo db_fetch_cell_prepared('SELECT username FROM user_auth WHERE id = ?', array($_SESSION['sess_user_id'])); ?>';
		npc.params.npc_host_icons      = '<?php echo read_config_option('npc_host_icons'); ?>';
		npc.params.npc_service_icons   = '<?php echo read_config_option('npc_service_icons'); ?>';

		npc.params.cacti_path          = '<?php echo URL_PATH; ?>';
		var strLen = npc.params.cacti_path;
		if (npc.params.cacti_path.charAt(strLen-1) == '/') {
			npc.params.cacti_path = npc.params.cacti_path.slice(0,strLen-1);
		}

		<?php $state = unserialize(db_fetch_cell_prepared('SELECT settings FROM npc_settings WHERE user_id = ?', array($_SESSION['sess_user_id']))); ?>
		var ExtState = Ext.decode('<?php echo json_encode($state); ?>');

		// Launch the app
		Ext.onReady(npc.init, npc);

		Ext.onReady(function() {
			Ext.state.Manager.setProvider(new Ext.state.HttpProvider({url: 'npc.php?module=settings&action=save'}));
			Ext.QuickTips.init();
			npc.initPortlets();
			window.parent.npcSize();
		});

		Ext.Ajax.on('beforerequest', function(conn, options) {
			options.params.__csrf_magic = window.parent.getMagicToken();
		});

		function readySize() {
			window.parent.npcSize();
		}

		$(function() {
			setTimeout(readySize, 350);
		});

		</script>
		<div id='north'>
			<div id='msg-div'></div>
		</div>
		<div id='west'>
			<div id='west-monitoring'></div>
			<div id='west-config'></div>
		</div>
		<div id='props-panel' style='width:200px;height:200px;overflow:hidden;'></div>
		<div id='south'></div>
		<?php
    } // end drawLayout
}

