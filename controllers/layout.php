<?php

class NpcLayoutController extends Controller {
	var $params = array();

	function drawFrame($params) {
		$config = $params['config'];

		general_header();

		$npc_base = $config['url_path'] . 'plugins/npc/';

		$current_tab = 'dashboard';
		if (isset_request_var('tab')) {
			$current_tab = get_request_var('tab');
		}

		$tabs = array(
			'dashboard'  => __('Dashboard', 'npc'),
			'hosts'      => __('Hosts', 'npc'),
			'services'   => __('Services', 'npc'),
			'comments'   => __('Comments', 'npc'),
			'downtime'   => __('Downtime', 'npc'),
			'eventlog'   => __('Event Log', 'npc'),
			'process'    => __('Process Info', 'npc'),
		);

		/* draw the tabs */
		print "<div class='tabs' style='margin-bottom:0px;'><nav><ul role='tablist'>\n";

		foreach ($tabs as $tab_short => $tab_name) {
			print "<li role='tab' tabindex='0'><a " .
				(($tab_short == $current_tab) ? "class='selected'" : '') .
				" href='" . html_escape($npc_base . 'npc.php?module=layout&action=drawFrame&tab=' . $tab_short) . "'>" .
				html_escape($tab_name) . '</a></li>';
		}

		print "</ul></nav></div>\n";

		/* pass config to JS */
		?>
		<script type='text/javascript'>
		var npcConfig = {
			baseUrl:          '<?php echo $npc_base; ?>',
			npcUrl:           '<?php echo $npc_base; ?>npc.php',
			portletRefresh:   <?php echo (int) read_config_option('npc_portlet_refresh'); ?>,
			dateFormat:       '<?php echo addslashes(read_config_option('npc_date_format')); ?>',
			timeFormat:       '<?php echo addslashes(read_config_option('npc_time_format')); ?>',
			nagiosUrl:        '<?php echo addslashes(read_config_option('npc_nagios_url')); ?>',
			userName:         '<?php echo addslashes(db_fetch_cell_prepared('SELECT username FROM user_auth WHERE id = ?', array($_SESSION['sess_user_id']))); ?>',
			hostIcons:        '<?php echo read_config_option('npc_host_icons'); ?>',
			serviceIcons:     '<?php echo read_config_option('npc_service_icons'); ?>',
			currentTab:       '<?php echo addslashes($current_tab); ?>',
			csrfToken:        '<?php echo csrf_get_tokens(); ?>'
		};
		</script>
		<script type='text/javascript' src='<?php echo $npc_base; ?>js/npc.js'></script>
		<?php

		print "<div id='npc-content'></div>\n";

		bottom_footer();
	}
}
