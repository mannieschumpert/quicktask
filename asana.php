<?php
/**
 * QuickTask: The Alfred Workflow for Asana
 *
 * Author: Mannie Schumpert http://mannieschumpert.com
 * Version: 2.1
 */
require('workflows.php');

class Asana {

	private $w; // workflow object
	private $file; // path to settings file
	private $settings;

	public function __construct(){

		$this->w = new Workflows();
		$this->file = $this->w->data().'/asana.json';
		$this->settings = $this->w->read( $this->file, true );

	}

	/** ==============================================================================
	 * Public functions
	 * Functions called directly from the Alfred scripts
	 ================================================================================= */

	/**
	 * Keyword: 'akey'
	 *
	 * User enters API key, and all initial data is saved
	 */
	public function key($apikey){

		exec("curl -u ".$apikey.": https://app.asana.com/api/1.0/users/me",$return);
		$return = json_decode($return[0],true);

		if ($return['errors']):
			echo $this->errors['bad_key'];
			exit;
		endif;

		$settings = $this->settings;
		if ( !$settings ):
			exec( 'touch "'.$this->file.'"' );
			$settings = array();
		endif;

		$settings['apikey'] = $apikey;
		$settings['default'] = $return['data']['email'];

		// Get workspaces
		$workspaces = self::get_workspaces($apikey);
		$settings['workspaces'] = $workspaces;
		// Get projects
		$projects = self::get_projects($workspaces,$apikey);
		$settings['projects'] = $projects;

		$this->w->write( $settings, $this->file );
		echo "Asana data saved.";
	}

	/**
	 * Keyword 'aget'
	 *
	 * Gets workspaces and projects
	 * Only needed if user has added workspaces or projects to Asana since running akey
	 */
	public function get(){

		$settings = self::check_data('apikey');
		$apikey = $settings['apikey'];

		// Get workspaces
		$workspaces = self::get_workspaces($apikey);
		$settings['workspaces'] = $workspaces;
		// Get projects
		$projects = self::get_projects($workspaces,$apikey);
		$settings['projects'] = $projects;


		$this->w->write( $settings, $this->file );
		echo "Workspaces and projects saved.";
	}

	/**
	 * Keyword 'amail'
	 *
	 * Saves default assignee email address
	 * Only needed if user changes Asana-associated email or if needs to set different default assignee
	 */
	public function mail($query = NULL){

		$settings = self::check_data();

		// User entered an email address, so save it as default
		if($query != NULL):
			$settings['default'] = $query;
			$this->w->write( $settings, $this->file );
			echo "Default assignee saved.";
			exit;
		endif;

		$apikey = $settings['apikey'];
		if (!$apikey):
			echo $this->errors['no_apikey'];
			exit;
		endif;

		exec("curl -u ".$apikey.": https://app.asana.com/api/1.0/users/me",$return);
		$return = json_decode($return[0],true);
		$settings['default'] = $return['data']['email'];

		$this->w->write( $settings, $this->file );
		echo "Default assignee saved.";

	}

	/**
	 * Keyword 'aspace'
	 *
	 * Retrieves workspaces for choosing as target
	 */
	public function workspace_filter($query){

		$settings = self::check_data('workspaces',true);
		$workspaces = $settings['workspaces'];

		// workspace_filter is used in the new_project function
		// this checks if that's the current context
		if ( $settings['new_project_space'] ){

			// return $query;
			// $this->w->result( $workspace, $workspace, $workspace, '', 'icon.png', 'yes' );
			if ( $query === '' ):
				$this->w->result( '', '', 'New project: ', '', 'icon.png', 'yes' );
			else:
				$this->w->result( $query, $query, 'New project: '.$query, '', 'icon.png', 'yes' );
			endif;

		} else {

			foreach ( $workspaces as $workspace => $workspace_id ){
				if ($query && strpos(strtolower($workspace),strtolower($query)) === false) continue;
				$this->w->result( $workspace, $workspace, $workspace, '', 'icon.png', 'yes' );
			}

		}

		echo $this->w->toxml();
	}

	/**
	 * Keyword 'aproject'
	 *
	 * Retrieves projects for choosing as target
	 */
	public function project_filter($query){
		
		$settings = self::check_data('projects',true);
		$projects = $settings['projects'];

		foreach ( $projects as $project => $project_data ){
			if ($query && strpos(strtolower($project),strtolower($query)) === false) continue;
			$this->w->result( $project, $project, $project, $project_data['workspace'], 'icon.png', 'yes' );
		}

		echo $this->w->toxml();
	}

	/**
	 * Saves task target
	 *
	 * Triggered from 'aspace' and 'aproject' keywords
	 */
	public function save_target($query,$project = false){

		$settings = self::check_data();
		unset($settings['target']);

		if($project):
			$projects = $settings['projects'];
			$settings['target']['project'] = $query;
			$settings['target']['project_id'] = $projects[$query]['id'];
			$settings['target']['workspace'] = $projects[$query]['workspace'];
			$settings['target']['workspace_id'] = $projects[$query]['workspace_id'];
		else:
			$workspaces = $settings['workspaces'];
			$settings['target']['workspace'] = $query;
			$settings['target']['workspace_id'] = $workspaces[$query];
		endif;

		$this->w->write( $settings, $this->file );
		echo $query." set as task target.";
	}

	/**
	 * Keyword: 'aperson'
	 *
	 * Saves nickname used for adding assignee to task
	 *
	 * Example: "aperson me=me@mysite.com"
	 */
	public function person($query){

		$settings = self::check_data();

		// parse query
		$person = explode( "=", $query);

		if ( count($person) !== 2 ):
			echo $this->errors['bad_syntax'];
			exit;
		endif;

		$settings['people'][$person[0]] = $person[1];
		$this->w->write( $settings, $this->file );

		echo "Person saved.";
	}

	/**
	 * Keyword 'apeople'
	 *
	 * displays a list of saved assignees
	 */
	public function people(){

		$settings = self::check_data('people',true);
		$people = $settings['people'];

		foreach ($people as $person => $email){

			$person_data = $person." = ".$email;
			$this->w->result( $person_data, $person_data, $person_data, '', 'icon.png', 'yes' );
			
		}

		echo $this->w->toxml();
	}

	/**
	 * Keyword 'azone'
	 *
	 * Filters timezone choices
	 */
	public function zone_filter($query){

		$settings = self::check_data(NULL,true);
		$timezones = 'timezones.json';
		$zones = $this->w->read( $timezones, true );
		$set_region = $settings['region'];

		if ( $set_region ){

			foreach ( $zones[$set_region] as $zone ){
				if ($query && strpos(strtolower($zone),strtolower($query)) === false) continue;
				$this->w->result( $zone, $zone, $zone, $zone, 'icon.png', 'yes' );
			}			

		} else {
			
			foreach ( $zones as $region => $zone ){
				if ($query && strpos(strtolower($region),strtolower($query)) === false) continue;
				$this->w->result( $region, $region, basename( $region ), $region, 'icon.png', 'yes' );
			}
			
		}

		echo $this->w->toxml();
	}

	/**
	 * Keyword 'azone' (continues from previous)
	 *
	 * Saves zone info
	 */
	public function zone_save($query){

		$settings = $this->settings;
		$set_region = $settings['region'];

		if ( $set_region ){

			$timezone = $set_region.'/'.$query;
			$settings['timezone'] = $timezone;
			unset($settings['region']);
			$this->w->write( $settings, $this->file );
			echo 'Timezone set as '.$timezone;

		} else {
			
			$settings['region'] = $query;
			$this->w->write( $settings, $this->file );

		}

	}

	/**
	 * Shows the current target (just a reminder)
	 *
	 * Keyword: 'atarget'
	 */
	public function target(){

		$settings = $this->settings;
		if (!$settings):
			$this->w->result( 'ERROR', 'ERROR', $this->errors['no_settings'], '', 'icon.png', 'yes' );
			echo $this->w->toxml();
			exit;
		endif;

		$target = $settings['target'];
		if (!$target):
			$this->w->result( 'ERROR', 'ERROR', $this->errors['no_target'], '', 'icon.png', 'yes' );
			echo $this->w->toxml();
			exit;
		endif;

		if ($target['project']):
			$set_target = "Project: ".$target['project'];
		else:
			$set_target = "Workspace: ".$target['workspace'];
		endif;

		$this->w->result( $set_target, $set_target, $set_target, '', 'icon.png', 'yes' );
		echo $this->w->toxml();

	}

	/**
	 * Makes task
	 *
	 * Keyword: 'asana'
	 */
	public function task($query){

		$settings = self::check_data('apikey,target');

		$parsed_query = self::parse_task($query,$settings);
		self::create_task($parsed_query,$settings);

	}

	/**
	 * Creates new project
	 *
	 * Keyword: 'anew'
	 */
	public function new_project($query){

		$settings = self::check_data();
		$new_project_workspace = $settings['new_project_space'];

		// If new_project_space set, create new project
		if ($new_project_workspace) {

			// Unset temp setting first, to be sure it doesn't interfere with workspace_filter function if API call returns an error
			unset($settings['new_project_space']);
			$this->w->write( $settings, $this->file );

			// Add data tags
			$d = ' -d "workspace='.$settings['workspaces'][$new_project_workspace].'" -d "name='.$query.'"';

			// Send the API call
			exec( 'curl -u '.$settings['apikey'].': https://app.asana.com/api/1.0/projects' . $d, $return);
			$return = json_decode($return[0],true);

			// Check for errors
			if ($return['errors']):
				echo $return['errors'][0]['message'];
				exit;
			endif;

			$return = $return['data'];

			/**
			 * @todo: Some repeated code. Needs refactoring
			 */
			// Assemble project info from API return
			$project_name = $return['name'];
			$project_id = $return['id'];
			$workspace = $return['workspace']['name'];
			$workspace_id = $return['workspace']['id'];
			
			// Save new project to Projects array
			$new_project = array();
			$new_project['id'] = "$project_id";
			$new_project['workspace'] = "$workspace";
			$new_project['workspace_id'] = "$workspace_id";

			$settings['projects'][$project_name] = $new_project;

			$this->w->write( $settings, $this->file );

			echo $query." project created in " . $new_project_workspace . ".";

		} else {

			// Saves target workspace ID temporarily
			// $settings['new_project_space'] = $settings['workspaces'][$query];
			$settings['new_project_space'] = $query;
			$this->w->write( $settings, $this->file );
		}




	}

	/** ==============================================================================
	 * Private functions
	 * Processing functions called by public functions
	 ================================================================================= */

	/**
	 * Gets workspaces
	 */
	private function get_workspaces($apikey){

		// Get workspaces from Asana API
		exec( 'curl -u '.$apikey.': https://app.asana.com/api/1.0/workspaces', $return);
		$return = json_decode($return[0],true);
		$data = $return['data'];

		// Check if data was returned
		/**
		 * @todo: could be extended for more explicit error handling
		 */
		// if (!$data):
		// 	return $this->errors['api_error'];
		// endif;

		// Set data
		$workspaces = array();
		foreach ($data as $workspace) {
			
			$id = $workspace['id'];
			$name = $workspace['name'];
			
		// 	// Set IDs for workspaces
			$workspaces[$name] = "$id";
		}

		return $workspaces;
	}

	/**
	 * Gets projects by workspace ID
	 */
	private function get_projects($workspaces,$apikey){

		$projects = array();
		foreach($workspaces as $workspace => $workspace_id ){

			exec( 'curl -u '.$apikey.': https://app.asana.com/api/1.0/workspaces/'.$workspace_id.'/projects?archived=false', $return);
			
			$return = json_decode($return[0],true);
			$return = $return['data'];

			foreach ($return as $project){

				$name = $project['name'];
				$project_id = $project['id'];
				
				$projects[$name] = array();
				$projects[$name]['id'] = "$project_id";
				$projects[$name]['workspace'] = "$workspace";
				$projects[$name]['workspace_id'] = "$workspace_id";
				
			}
			unset($return);
		}		
		
		return $projects;
	}

	/**
	 * Parses task
	 *
	 * Checks task query for assignee and date parameters
	 * Saves data tags needed for API call as array
	 */
	private function parse_task($query,$settings){

		// $settings = $this->settings;
		$parsed_query = array();
		$query_arr = explode( "::", $query);

		// If there's more than two delimiters (very edge case - major user error)
		if ( count($query_arr) > 3 ){
			echo $this->errors['bad_syntax'];
			exit;
		}
		// If there's no delimeters: task only
		elseif ( count($query_arr) === 1 ) {
			// check for default assignee - maybe conditional if target is workspace or project
			if(!$settings['default']){
				echo $this->errors['no_default'];
				exit;
			}

			$parsed_query['assignee'] = $settings['default'];
			$parsed_query['name'] = $query;
		} 
		// Two delimiters: task + assignee + due date
		elseif ( count($query_arr) === 3 ) {

			$parsed_query['name'] = $query_arr[0];

			// // Set assignee
			$assignee = self::get_assignee($query_arr[1],$settings);
			$parsed_query['assignee'] = $assignee;

			// // Set due date
			$due_on = self::set_due_date($query_arr[2],$settings);
			$parsed_query['due_on'] = $due_on;
		} 
		// One delimiter: task + assignee OR task + due date
		else {

			$parsed_query['name'] = $query_arr[0];

			// Most errors here should be generic - unless it's date-formatted

			// If has numbers, check for date format
			if (preg_match('/[0-9]/', $query_arr[1])){
				
				// If date format, validate date
				if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $query_arr[1], $datebit)){

					// check that zone is set before proceeding
					if(!$settings['timezone']):
						echo $this->errors['no_zone'];
						exit;
					endif;

					date_default_timezone_set($settings['timezone']);
					$today = date('Y-m-d');

					// if valid date, set due date
					if ( checkdate($datebit[2], $datebit[3], $datebit[1]) && $query_arr[1] > $today ){
						$parsed_query['due_on'] = $query_arr[1];
					} else {
					// if invalid, return error
						echo $this->errors['bad_date'];
						exit;
					}

				// elseif maybe person with number, validate as person
				// edge case - would only be true if user used a number in a nickname, like if there were two people named Bob, so there might be bob1 and bob2
				} else {
					$assignee = $settings['people'][$query_arr[1]];
					if (!$assignee):
						echo $this->errors['bad_entry'];
						exit;
					endif;
					$parsed_query['assignee'] = $assignee;
				}

			// elseif check for day keyword
			} elseif (in_array(strtolower($query_arr[1]),$this->date_keywords)) {
				// check that zone is set before proceeding
				if(!$settings['timezone']):
					echo $this->errors['no_zone'];
					exit;
				endif;

				// set due date by day
				date_default_timezone_set($settings['timezone']);
				$today = date('Y-m-d');

				if ($query_arr[1] === 'today'){
					$due_date = $today;
				} else {
					$due_date = date('Y-m-d', strtotime($query_arr[1]));
					if ($due_date <= $today) {
						$due_date = date('Y-m-d', strtotime('next '.$query_arr[1]));
					}
				}
				$parsed_query['due_on'] = $due_date;
				$parsed_query['assignee'] = $settings['default'];
				
			} else {

				$assignee = $settings['people'][$query_arr[1]];
				if (!$assignee):
					echo $this->errors['bad_entry'];
					exit;
				endif;
				$parsed_query['assignee'] = $assignee;
			}
		}

		return $parsed_query;
	}

	/**
	 * Gets email of assignee
	 */
	private function get_assignee($name,$settings){

		$assignee = $settings['people'][$name];
		if (!$assignee):
			echo $this->errors['bad_person'];
			exit;
		endif;

		return $assignee;
	}

	/**
	 * Converts due date keyword to MYSQL-formatted date
	 */
	private function set_due_date($day,$settings){

		$timezone = $settings['timezone'];
		if(!$timezone):
			echo $this->errors['no_zone'];
			exit;
		endif;
		date_default_timezone_set($timezone);


		$today = date('Y-m-d');

		if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $day)){

		$due_date = $day;
		if ($due_date < $today) {
			echo $this->errors['not_future'];
			exit;
		}
		// is in day words array
		} elseif(in_array(strtolower($day),$this->date_keywords)) { 

			if ($day === 'today'){
				$due_date = $today;
			} else {
				$due_date = date('Y-m-d', strtotime($day));
				if ($due_date <= $today) {
					$due_date = date('Y-m-d', strtotime('next '.$day));
				}
			}
		} else {
			echo $this->errors['bad_date'];
			exit;
		}

		return $due_date;
	}

	/**
	 * Sends task to Asana
	 */
	private function create_task($data,$settings){

		$apikey = $settings['apikey'];
		$target = $settings['target'];
		$data['workspace'] = $target['workspace_id'];

		if ($target['project']){
			$data['projects[0]'] = $target['project_id'];
		}

		// Add data tags
		$d = '';
		foreach ($data as $key => $value){
			$d .= ' -d "'.$key.'='.$value.'"';
		}

		// Send the API call
		exec( 'curl -u '.$apikey.': https://app.asana.com/api/1.0/tasks' . $d, $return);
		$return = json_decode($return[0],true);

		// Check for errors
		if ($return['errors']):
			// echo $this->errors['api_error'];
			echo $return['errors'][0]['message'];
			exit;
		endif;

		echo "Task created.";
	}

	/**
	 * Data error handler for most functions
	 * 
	 */
	private function check_data($fields = NULL,$filter = NULL){

		if($fields):
			$data = array();
			$data = explode( ",", $fields);
		endif;

		if (!$filter):
			// Check settings
			$settings = $this->settings;
			if (!$settings):
				echo $this->errors['no_settings'];
				exit; // No settings. Don't proceed
			endif;
			if($data):
				// Check for data
				foreach ($data as $field){
					if(!$settings[$field]):
						echo $this->errors['no_'.$field];
						exit; // Missing data. Don't proceed
					endif;
				}
			endif;
		else:
			// Check settings
			$settings = $this->settings;
			if (!$settings):
				$this->w->result( 'ERROR', 'ERROR', $this->errors['no_settings'], '', 'icon.png', 'yes' );
				echo $this->w->toxml();
				exit; // No settings. Don't proceed
			endif;
			if($data):
				// Check for data
				foreach ($data as $field){
					if(!$settings[$field]):
						$this->w->result( 'ERROR', 'ERROR', $this->errors['no_'.$field], '', 'icon.png', 'yes' );
						echo $this->w->toxml();
						exit; // Missing data. Don't proceed
					endif;
				}
			endif;
		endif;

		return $settings;

	}


	/** ==============================================================================
	 * Variables with needed operational data
	 ================================================================================= */

	/**
	 * Error messages
	 */
	var $errors = array(
		'api_error'     => 'ERROR: Please check your API settings.',
		'bad_date'		=> 'ERROR: Invalid date.',
		'bad_entry'     => 'ERROR: Invalid entry. Please try again.',
		'bad_key'       => 'ERROR: Please check that your API key is correct.',
		'bad_syntax'    => 'ERROR: Bad syntax. Please check that you entered information properly.',
		'no_apikey'     => 'ERROR: Please enter your Asana API key.',
		'no_default'    => 'ERROR: Please run "adefault" to set a default assignee.',
		'no_people'		=> 'No assignees are stored yet.',
		'no_projects'   => 'ERROR: Please run "aget" to retrieve your workspaces and projects.',
		'no_settings'   => 'ERROR: Please run "akey" to save your Asana settings.',
		'no_target'     => 'ERROR: Please run "aspace" or "aproject" to set a task target.',
		'no_workspaces' => 'ERROR: Please run "aget" to retrieve your workspaces and projects.',
		'no_zone'   	=> 'ERROR: Please run "azone" to set your timezone.',
		'not_future'	=> 'ERROR: Please enter a date in the future.',

		);

	/**
	 * Date keywords
	 */
	var $date_keywords = array(

		'today',
		'tomorrow',
		'mon',
		'monday',
		'tues',
		'tuesday',
		'wed',
		'wednesday',
		'thur',
		'thursday',
		'fri',
		'friday',
		'sat',
		'saturday',
		'sun',
		'sunday',
		'next mon',
		'next monday',
		'next tues',
		'next tuesday',
		'next wed',
		'next wednesday',
		'next thur',
		'next thursday',
		'next fri',
		'next friday',
		'next sat',
		'next saturday',
		'next sun',
		'next sunday'

		);
}