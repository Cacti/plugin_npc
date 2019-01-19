<?php

class NpcLayoutDevController extends controller {
	var $params = array();

	function drawFrame($params) {
		$config = $params['config'];

		$this->drawCommonFrame($config['url_path'] . 'plugins/npc/npc.php?module=layoutDev&action=drawLayout', $params);
	}

	function drawLayout($params) {
		$config  = $params['config'];
		$npc_ext_base     = $config['url_path'] . 'plugins/npc/js/ext/';
		$npc_plugins_base = $config['url_path'] . 'plugins/npc/js/src/plugins/';
		$npc_monitor_base = $config['url_path'] . 'plugins/npc/js/src/monitoring/';
		$npc_common_base  = $config['url_path'] . 'plugins/npc/js/src/';
		$npc_css_base     = $config['url_path'] . 'plugins/npc/css/';
	    ?>

		<!-- jQuery -->
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jquery.js'></script>

		<!-- CSS -->
		<link rel='stylesheet' type='text/css' href='<?php echo $npc_ext_base; ?>resources/css/ext-all.css' />
		<link rel='stylesheet' type='text/css' href='<?php echo $npc_ext_base; ?>resources/css/xtheme-slate.css' />
		<!-- <link rel='stylesheet' type='text/css' href='<?php echo $npc_ext_base; ?>resources/css/xtheme-darkgray.css' /> -->
		<!-- <link rel='stylesheet' type='text/css' href='<?php echo $npc_ext_base; ?>resources/css/xtheme-gray.css' /> -->
		<!-- <link rel='stylesheet' type='text/css' href='<?php echo $npc_ext_base; ?>resources/css/xtheme-gray-extend.css' /> -->

		<link rel='stylesheet' type='text/css' href='<?php echo $npc_css_base; ?>main.css' />
		<link rel='stylesheet' type='text/css' href='<?php echo $npc_css_base; ?>ext-ux-livegrid.css' />

		<script type='text/javascript' src='<?php echo $npc_ext_base; ?>adapter/ext/ext-base.js'></script>
		<script type='text/javascript' src='<?php echo $npc_ext_base; ?>ext-all-debug.js'></script>

		<!-- Plugins -->
		<script type='text/javascript' src='<?php echo $npc_plugins_base; ?>Ext.state.HttpProvider.js'></script>
		<script type='text/javascript' src='<?php echo $npc_plugins_base; ?>Ext.ux.form.XCheckbox.js'></script>
		<script type='text/javascript' src='<?php echo $npc_plugins_base; ?>Ext.ux.grid.Search.js'></script>
		<script type='text/javascript' src='<?php echo $npc_plugins_base; ?>Ext.ux.Andrie.pPageSize.js'></script>
		<script type='text/javascript' src='<?php echo $npc_plugins_base; ?>Ext.ux.LiveGrid.js'></script>

		<!-- Overrides -->
		<script type='text/javascript' src='<?php echo $npc_common_base; ?>overrides/overrides.js'></script>

		<!-- Common -->
		<script type='text/javascript' src='<?php echo $npc_common_base; ?>Portal.js'></script>
		<script type='text/javascript' src='<?php echo $npc_common_base; ?>PortalColumn.js'></script>
		<script type='text/javascript' src='<?php echo $npc_common_base; ?>Portlet.js'></script>
		<script type='text/javascript' src='<?php echo $npc_common_base; ?>npc.js'></script>

		<!-- Host screens -->
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>hosts/hosts.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>hosts/hostDetail.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>hosts/hostgroupGrid.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>hosts/hostgroupOverview.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>hosts/hostCommandMenu.js'></script>

		<!-- Service screens -->
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>services/services.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>services/serviceDetail.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>services/servicegroupGrid.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>services/servicegroupOverview.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>services/serviceCommandMenu.js'></script>

		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>n2c.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>comments.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>downtime.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>processInfo.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>reporting.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>eventLog.js'></script>
		<script type='text/javascript' src='<?php echo $npc_monitor_base; ?>commandForms.js'></script>

		<script type='text/javascript'>

		// Add some properties to the params array
		npc.params.npc_portlet_refresh = <?php echo read_config_option('npc_portlet_refresh'); ?>;
		npc.params.npc_date_format     = '<?php echo read_config_option('npc_date_format'); ?>';
		npc.params.npc_time_format     = '<?php echo read_config_option('npc_time_format'); ?>';
		npc.params.npc_nagios_url      = '<?php echo read_config_option('npc_nagios_url'); ?>';
		npc.params.userName            = '<?php echo db_fetch_cell_prepared('SELECT username FROM user_auth WHERE id = ?', array($_SESSION['sess_user_id']));?>';
		npc.params.npc_host_icons      = '<?php echo read_config_option('npc_host_icons'); ?>';
		npc.params.npc_service_icons   = '<?php echo read_config_option('npc_service_icons'); ?>';

		npc.params.cacti_path          = '<?php echo URL_PATH; ?>';
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
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/host-problems-portlet.js'></script>
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/eventlog-portlet.js'></script>
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/service-problems-portlet.js'></script>
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/monitoring-performance-portlet.js'></script>
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/servicegroup-service-status-portlet.js'></script>
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/servicegroup-host-status-portlet.js'></script>
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/hostgroup-host-status-portlet.js'></script>
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/hostgroup-service-status-portlet.js'></script>
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/hostSummaryOverview.js'></script>
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/serviceSummaryOverview.js'></script>
		<script type='text/javascript' src='<?php echo $config['url_path']; ?>plugins/npc/js/src/portlets/nagiosStatus.js'></script>

		<script type='text/javascript'>
			Ext.onReady(function() {
				npc.initPortlets();
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
    }
}

