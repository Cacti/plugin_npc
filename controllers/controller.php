<?php

declare(strict_types=1);
/**
 * Base controller class
 *
 * @filesource
 * @author              Billy Gunn <billy@gunn.org>
 * @copyright           Copyright (c) 2007
 * @link                http://trac2.assembla.com/npc
 * @package             npc
 * @subpackage          npc.controllers
 * @since               NPC 2.0
 * @version             $Id$
 */

/**
 * Base controller class
 *
 * @package     npc
 * @subpackage  npc.controllers
 */
class Controller {
    var $conn = null;

    /**
     * The default state to query
     *
     * @var string
     * @access public
     */
    var $state = 'any';

    /**
     * String to state mapping
     *
     * @var array
     * @access public
     */
    var $stringToState = [];

    /**
     * The starting row for fetching results
     *
     * @var integer
     * @access public
     */
    var $start = 0;

    /**
     * The number of rows to fetch
     *
     * @var integer
     * @access public
     */
    var $limit = 25;

	/**
	 * The field to sort on
	 *
	 * @var string
	 * @access public
	 */
	var $sort = null;

	/**
	 * The sort direction
	 *
	 * @var string
	 * @access public
	 */
	var $dir = null;

    /**
     * The current page to fetch results for
     *
     * @var integer
     * @access public
     */
    var $currentPage = 1;

    /**
     * The host and service config type
     *
     * @var integer
     * @access public
     */
    var $config_type = 1;

    /**
     * The total number of records from
     * the last query.
     *
     * @var integer
     * @access public
     */
    var $numRecords = null;

    /**
     * The ID of the requested record
     *
     * @var integer
     * @access public
     */
    var $id = null;

    /**
     * The search string passed in from the client
     *
     * @var string
     * @access public
     */
    var $searchString = null;

    /**
     * json encoded list of fields to search
     *
     * @var string
     * @access public
     */
    var $searchFields = null;

    /**
     * Maps a hosts current_state
     *
     * @var array
     * @access public
     */
    var $hostState = [];

    /**
     * Maps a services current_state
     *
     * @var array
     * @access public
     */
    var $serviceState = [];

    /**
     * Holds all params passed and named.
     *
     * @var mixed
     * @access public
     */
    var $passedArgs = [];

    /**
     * Column aliases
     *
     * @var array
     * @access public
     */
    var $columnAlias = [];

    /**
     * Constructor.
     *
     */
    function __construct() {
        // Get the config type. Default to 1 if not found.
        $config_type = read_config_option('npc_config_type');
        $this->config_type =  ?? 1;
    }

    function jsonOutput($results=[]) {
        if (!$this->numRecords) {
            $this->numRecords = count($results);
        }

        if (count($results) && !isset($results[0])) {
            $results = [];
        }

        // Setup the output array:
        $output = [];

        return(json_encode($output));
    }

    function csvOutput($results=[]) {
        header('Content-type: text/csv');
        header('Cache-Control: no-store, no-cache');
        header('Content-Disposition: attachment; filename="filename.csv"');

        $outstream = fopen('php://output','w');

        foreach( $test_data as $row ) {
            fputcsv($outstream, $row, ',', '"');
        }

        fclose($outstream);
        exit;
    }

    /**
     * flattenArray
     *
     * Flattens the 1st level of nesting
     *
     * @return array  list of all services with status
     */
    function flattenArray($array=[]) {

        $newArray = [];

        for ($i = 0; $i < count($array); $i++) {
            foreach ($array[$i] as $key => $val) {
                if (is_[]) {
                    foreach ($val as $k => $v) {
                        $newArray[$i][$k] = $v;
                    }
                } else {
                    $newArray[$i][$key] = $val;
                }
            }
        }

        return($newArray);
    }

    /**
     * searchClause
     *
     * Appends search parameters to the passed in where clause
     * @param string $where  An existing where clause
     * @param array $fieldMap  Maps passed in field names
     * @return string  The appended where clasue
     */
    function searchClause($where, $fieldMap) {

        if (!$where) {
            $where = ' ( ';
        } else {
            $where .= ' AND ( ';
        }

        $fields = json_decode(stripslashes($this->searchFields));
        $count = count($fields);

        $x = 1;
        foreach ($fields as $field) {
            if (isset($fieldMap[$field])) {
                $where .= $fieldMap[$field] . " LIKE '%" . $this->searchString . "%' ";
                if ($x < $count) {
                    $where .= ' OR ';
                }
                $x++;
            } else {
                $count = $count - 1;
            }
        }

        $where .= ' ) ';

        return($where);
    }

    function flattenNestedArray($array) {

        $results = [];

        $x = 0;
        for ($i = 0; $i < count($array); $i++) {
            foreach ($array[$i] as $key => $val) {
                if (is_[]) {
                    $t[0] = $val;
                    $v = $this->flattenArray($t);
                    unset($array[$i][$key]);
                    foreach ($array[$i] as $key => $val) {
                        if (!is_[]) {
                            $a[$key] = $val;
                        }
                    }
                    $results[$x] = array_merge($a, $v[0]);
                    $x++;
                }
            }
        }

        return($results);
    }

    /**
     * getTimer
     *
     * Returns time in seconds used for debug timing
     *
     * @return string  -
     */
    function getTime() {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        return($mtime);
    }

    /**
     * logger
     *
     * A utility method to wrap the Cacti logging mechanism
     *
     * @param  string $level     The log level of the message (error, warn, etc.)
     * @param  string $class     The calling class
     * @param  string $method    The calling method
     * @param  string $message   The log message
     * @return string  - On error a json encoded error message is returned to the client.
     */
    function logger($level, $class, $method, $message) {
        $logLevelConf = read_config_option('npc_log_level');

        $logLevels = [];

        if ($logLevels[$level] <= $logLevelConf) {
            $message = strtoupper($level) . " [$class] ($method) - $message";
            cacti_log($message, false, 'NPC');
        }

        // If this was an error send a generic response to the client
        if ($level == 'error') {
            return(json_encode(array('success' => false, 'msg' => __('An error occurred in %s. See error logs for detail.', $class -> $method, 'npc'))));
        }
    }

    /**
     * drawCommonFrame
     *
     * Draw a nice iFrame for NPC Content
     *
     * @param  string $file      The file to draw for content
     * @param  array $params     Custom Parameters for the form
     */
    function drawCommonFrame($file, $params) {
        $config = $params['config'];

		general_header();

		html_start_box('', '100%', '', '100%', '', '');

    	?>
		<tr>
			<td>
		        <style type='text/css'>
					iframe {
						border: 0px;
					}
				</style>
				<iframe class='cactiTable' id='npc' src='<?php print $file;?>'></iframe>
				<script>
				var csrfTimeout = null;

				function npcSize() {
					var myHeight = $('#navigation_right').height();
					$('#npc, #npc1').height(myHeight);
				}

				function getMagicToken() {
					return csrfMagicToken;
				}

				function setMagicToken(token) {
					csrfMagicToken = token;
				}

				function updateCsrf() {
					$.post('npc.php', { action : 'csrf', __csrf_magic : csrfMagicToken }).done(function(data) {
						setMagicToken(data);
						csrfTimeout = setTimeout(updateCsrf, 60000);
					});
				}

				$(function() {
					npcSize();
					$(window).resize(function() {
						npcSize();
					}).resize();

					csrfTimeout = setTimeout(updateCsrf, 60000);
				});
				</script>
			</td>
		</tr>
		<?php

		html_end_box();

		bottom_footer();
    } // end drawFrame
}

