<?php

/*
 * Version 0.2
 */

class Optimizely {
	/**
	 * contains info from the all the HTTP requests
	 */
	public $request_info = array();

	/**
	 * contains latest HTTP status code
	 */
	public $request_http_code;

	/**
	 * contains latest requested URL
	 */
	public $request_url;

	/**
	 * contains latest raw response
	 */
	public $request_response;

	/**
	 * user agent to provide on requests
	 */
	protected $useragent = 'PHP Optimizely v0.2';

	/**
	 * default timeout
	 */
	protected $timeout = 30;

	/**
	 * default connect timeout
	 */
	protected $connecttimeout = 30;

	/**
	 * Verify peer SSL Certificate?
	 */
	protected $ssl_verifypeer = FALSE;

	/**
	 * Optimize API token
	 */
	protected $api_token;

	/**
	 * base url for API
	 */
	protected $api_url = 'https://www.optimizelyapis.com/experiment/v1/';

	/**
	 * preferred date format for dates
	 */
	public $date_format = 'Y-m-d\TH:i:s\Z';

	/**
	 * Setup the object
	 */
	public function __construct( $api_token ) {
		$this->api_token = $api_token;
	}// end __construct

	/**
	 * allow late setting of API token
	 */
	public function set_api_token( $api_token ) {
		$this->api_token = $api_token;
	}// end set_api_token

	/**
	 * Uses curl to hit hit the API, override this function to use a different method
	 */
	protected function request( $options ) {
		if ( ! $this->api_token ) {
			return FALSE;
		}//end if

		$c = curl_init();

		/* Curl settings */
		curl_setopt( $c, CURLOPT_USERAGENT, $this->useragent );
		curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout );
		curl_setopt( $c, CURLOPT_TIMEOUT, $this->timeout );
		curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer );
		curl_setopt( $c, CURLOPT_HEADER, FALSE );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $c, CURLOPT_HTTPHEADER, array(
			'Token: ' . $this->api_token,
			'Content-Type: application/json'
		) );

		$url = $this->api_url . $options['function'];

		switch ( $options['method'] )
		{
			case 'POST':
				curl_setopt( $c, CURLOPT_POST, TRUE );
				curl_setopt( $c, CURLOPT_POSTFIELDS, json_encode( $options['data'] ) );
				break;

			case 'DELETE':
				curl_setopt( $c, CURLOPT_CUSTOMREQUEST, 'DELETE' );
				break;

			case 'PUT':
				curl_setopt( $c, CURLOPT_CUSTOMREQUEST, 'PUT' );
				curl_setopt( $c, CURLOPT_POSTFIELDS, json_encode( $options['data'] ) );
				break;

			case 'GET':
				curl_setopt( $c, CURLOPT_HTTPGET, TRUE );
				break;
		}//END switch

		curl_setopt( $c, CURLOPT_URL, $url );
		$response = curl_exec( $c );

		// the following variables are primarily for debugging purposes
		$this->request_http_code = curl_getinfo( $c, CURLINFO_HTTP_CODE );
		$this->request_info = curl_getinfo( $c );
		$this->request_url = $url;
		$this->request_response = $response;

		curl_close( $c );
		return json_decode( $response );
	}// end request

	/**
	 * Get a list of all the projects in your account, with associated metadata.
	 */
	public function get_projects() {
		return $this->request( array(
			'function' => 'projects',
			'method' => 'GET',
		) );
	}// end get_projects

	/**
	 * Get metadata for a single project.
	 */
	public function get_project( $project_id ) {
		return $this->request( array(
			'function' => 'projects/' . abs( intval( $project_id ) ) . '/',
			'method' => 'GET',
		) );
	}// end get_project

	/**
	 * Create a new project in your account. The project_name is required in the options.
	 * The other editable arguments are all optional.
	 */
	public function create_project( $options ) {

		if ( ! isset( $options['project_name'] ) ) {
			return FALSE;
		}//end if

		$defaults = array(
			'project_status' => 'Active',
			'include_jquery' => FALSE,
			'project_javascript' => null,
			'enable_force_variation' => FALSE,
			'exclude_disabled_experiments' => FALSE,
			'exclude_names' => null,
			'ip_anonymization' => FALSE,
			'ip_filter' => null,
		);

		$options = array_replace( $defaults, $options );

		return $this->request( array(
			'function' => 'projects/',
			'method' => 'POST',
			'data' => $options,
		) );
	}// end create_project

	/**
	 * update a project
	 */
	public function update_project( $project_id, $options ) {
		return $this->request( array(
			'function' => 'projects/' . abs( intval( $project_id ) ),
			'method' => 'PUT',
			'data' => $options,
		) );
	}//end update_project

	/**
	 * Get a list of all the experiments in a project.
	 */
	public function get_experiments( $project_id ) {
		return $this->request( array(
			'function' => 'projects/' . abs( intval( $project_id ) ) . '/experiments/',
			'method' => 'GET',
		) );
	}// end get_experiments

	/**
	 * Get metadata for a single experiment.
	 */
	public function get_experiment( $experiment_id ) {
		return $this->request( array(
			'function' => 'experiments/' . abs( intval( $experiment_id ) ) . '/',
			'method' => 'GET',
		) );
	}// end get_experiment

	/**
	 * Get the top-level results of an experiment, including number of visitors,
	 * number of conversions, and chance to beat baseline for each variation.
	 */
	public function get_experiment_results( $experiment_id, $options = array() ) {
		// @TODO: support options
		// @TODO: check for 503 in case this endpoint is overloaded (from docs)
		$extra = '';
		if ( $options ) {
			$extra = '?' . http_build_query( $options );
		}//end if

		return $this->request( array(
			'function' => 'experiments/' . abs( intval( $experiment_id ) ) . '/results' . $extra,
			'method' => 'GET',
		) );
	}// end get_experiment_results

	/**
	 * Creates a new experiment
	 */
	public function create_experiment( $project_id, $options ) {
		if ( ! isset( $options['description'] )
		  || ! isset( $options['edit_url'] ) ) {
			return FALSE;
		}//end if

		return $this->request( array(
			'function' => 'projects/' . abs( intval( $project_id ) ) . '/experiments/',
			'method' => 'POST',
			'data' => $options,
		) );
	}// end create_experiment

	/**
	 * Update an experiment
	 */
	public function update_experiment( $experiment_id, $options ) {
		return $this->request( array(
			'function' => 'experiments/' . abs( intval( $experiment_id ) ),
			'method' => 'PUT',
			'data' => $options,
		) );
	}// end update_experiment

	/**
	 * Delete an experiment
	 */
	public function delete_experiment( $experiment_id, $archive = TRUE ) {
		if ( $archive ) {
			return $this->update_experiment( $experiment_id, array( 'status' => 'Archived' ) );
		}//end if

		return $this->request( array(
			'function' => 'experiments/' . abs( intval( $experiment_id ) ),
			'method' => 'DELETE',
		) );
	}// end delete_experiment

	/**
	 * See a list containing the current schedule for an experiment as well as any previously
	 * created schedules. The current schedule will be marked ACTIVE and any previously created schedules will be marked INACTIVE.
	 */
	public function get_schedules( $experiment_id ) {
		return $this->request( array(
			'function' => 'experiments/' . abs( intval( $experiment_id ) ) . '/schedules',
			'method' => 'GET',
		) );
	}// end get_schedules

	/**
	 * Get data about a particular schedule, including the start time and stop time of the associated experiment.
	 */
	public function get_schedule( $schedule_id ) {
		return $this->request( array(
			'function' => 'schedules/' . abs( intval( $schedule_id ) ),
			'method' => 'GET',
		) );
	}// end get_schedule

	/**
	 * Create a schedule for an experiment.
	 */
	public function create_schedule( $experiment_id, $options ) {
		// requires either start_time or end_time
		if ( ! isset( $options['start_time'] )
		  && ! isset( $options['stop_time'] ) ) {
			return FALSE;
		}//end if

		return $this->request( array(
			'function' => 'experiments/' . abs( intval( $experiment_id ) ) . '/schedules',
			'method' => 'POST',
			'data' => $options,
		) );
	}// end create_schedule

	/**
	 * Update a schedule.
	 */
	public function update_schedule( $schedule_id, $options ) {
		// requires either start_time or end_time
		if ( ! isset( $options['start_time'] )
		  && ! isset( $options['stop_time'] ) ) {
			return FALSE;
		}//end if

		return $this->request( array(
			'function' => 'schedules/' . abs( intval( $schedule_id ) ),
			'method' => 'PUT',
			'data' => $options,
		) );
	}// end update_schedule

	/**
	 * Delete a schedule.
	 */
	public function delete_schedule( $schedule_id ) {
		return $this->request( array(
			'function' => 'schedules/' . abs( intval( $schedule_id ) ),
			'method' => 'DELETE',
		) );
	}// end update_schedule

	/**
	 * List all variations associated with the experiment.
	 */
	public function get_variations( $experiment_id ) {
		return $this->request( array(
			'function' => 'experiments/' . abs( intval( $experiment_id ) ) . '/variations/',
			'method' => 'GET',
		) );
	}// end get_variations

	/**
	 * Get metadata for a single variation.
	 */
	public function get_variation( $variation_id ) {
		return $this->request( array(
			'function' => 'variations/' . abs( intval( $variation_id ) ),
			'method' => 'GET',
		) );
	}// end get_variation

	/**
	 * Create a variation
	 */
	public function create_variation( $experiment_id, $options ) {
		if ( ! isset( $options['description'] ) ) {
			return FALSE;
		}//end if

		return $this->request( array(
			'function' => 'experiments/' . abs( intval( $experiment_id ) ) . '/variations',
			'method' => 'POST',
			'data' => $options,
		) );
	}// end create_variation

	/**
	 * Update a variation
	 */
	public function update_variation( $variation_id, $options ) {
		return $this->request( array(
			'function' => 'variations/' . abs( intval( $variation_id ) ),
			'method' => 'PUT',
			'data' => $options,
		) );
	}// end update_variation

	/**
	 * Delete a variation
	 */
	public function delete_variation( $variation_id ) {
		return $this->request( array(
			'function' => 'variations/' . abs( intval( $variation_id ) ),
			'method' => 'DELETE',
		) );
	}// end delete_variation

	/**
	 * List all goals associated with the project.
	 */
	public function get_goals( $project_id ) {
		return $this->request( array(
			'function' => 'projects/' . abs( intval( $project_id ) ) . '/goals/',
			'method' => 'GET',
		) );
	}// end get_goals

	/**
	 * Get metadata for a single goal.
	 */
	public function get_goal( $goal_id ) {
		return $this->request( array(
			'function' => 'goals/' . abs( intval( $goal_id ) ),
			'method' => 'GET',
		) );
	}// end get_goal

	/**
	 * Create a goal
	 */
	public function create_goal( $project_id, $options = array() ) {
		if ( ! isset( $options['title'] )
		  || ! isset( $options['goal_type'] ) ) {
			return FALSE;
		}//end if

		// check for additional conditional required fields
		switch ( $options['goal_type'] ) {
			case 0: // Click
				if ( ! isset( $options['selector'] )
				  || ! isset( $options['target_to_experiments'] ) ) {
					return FALSE;
				}//end if

				if ( FALSE === $options['target_to_experiments'] ) {
					if ( ! isset( $options['target_urls'] )
					  || ! isset( $options['target_url_match_types'] ) ) {
						return FALSE;
					}//end if
				}//end if
				break;

			case 1: // Custom event
				if ( ! isset( $options['event'] ) ) {
					return FALSE;
				}//end if
				break;

			case 2: // Engagement
				// no additional required fields
				break;

			case 3: // Pageviews
				if ( ! isset( $options['urls'] )
				  || ! isset( $options['url_match_types'] ) ) {
					return FALSE;
				}//end if
				break;

			case 4: // Revenue
				// no additional required fields
				break;

			default:
				return FALSE;
		}// end switch

		return $this->request( array(
			'function' => 'projects/' . abs( intval( $project_id ) ) . '/goals/',
			'method' => 'POST',
			'data' => $options,
		) );
	}// end create_goal

	/**
	 * Update a goal
	 */
	public function update_goal( $goal_id, $options ) {
		return $this->request( array(
			'function' => 'goals/' . abs( intval( $goal_id ) ),
			'method' => 'PUT',
			'data' => $options,
		) );
	}// end update_goal

	/**
	 * Delete a goal (in general, you don't want to do this)
	 * See remove_goal function for preferred approach
	 */
	public function delete_goal( $goal_id ) {
		return $this->request( array(
			'function' => 'goals/' . abs( intval( $goal_id ) ),
			'method' => 'DELETE',
		) );
	}// end delete_goal

	/**
 	 * add a goal to an experiment
 	 */
	public function add_goal( $experiment_id, $goal_id ) {
		$goal = $this->get_goal( $goal_id );

		if ( ! isset( $goal->experiment_ids ) ) {
			return FALSE;
		}//end if

		$goal->experiment_ids[] = $experiment_id;
		$goal->experiment_ids = array_unique( $goal->experiment_ids );

		return $this->update_goal( $goal_id, array( 'experiment_ids' => $goal->experiment_ids ) );
	}//end add_goal

	/**
	 * remove a goal from an experiment
	 */
	public function remove_goal( $experiment_id, $goal_id ) {
		$goal = $this->get_goal( $goal_id );

		if ( ! isset( $goal->experiment_ids ) ) {
			return FALSE;
		}//end if

		$goal->experiment_ids = array_diff( $goal->experiment_ids, array( $experiment_id ) );

		return $this->update_goal( $goal_id, array( 'experiment_ids' => $goal->experiment_ids ) );
	}//end remove_goal

	/**
	 * List all audiences associated with the project.
	 */
	public function get_audiences( $project_id ) {
		return $this->request( array(
			'function' => 'projects/' . abs( intval( $project_id ) ) . '/audiences/',
			'method' => 'GET',
		) );
	}// end get_audiences

	/**
	 * Get metadata for a single audience.
	 */
	public function get_audience( $audience_id ) {
		return $this->request( array(
			'function' => 'audiences/' . abs( intval( $audience_id ) ),
			'method' => 'GET',
		) );
	}// end get_audience

	/**
	 * Create an audience
	 */
	public function create_audience( $project_id, $options ) {
		if ( ! isset( $options['name'] ) ) {
			return FALSE;
		}// end if

		return $this->request( array(
			'function' => 'projects/' . abs( intval( $project_id ) ) . '/audiences/',
			'method' => 'POST',
			'data' => $options,
		) );
	}// end create_audience

	/**
	 * Update an audience
	 */
	public function update_audience( $audience_id, $options ) {
		return $this->request( array(
			'function' => 'audiences/' . abs( intval( $audience_id ) ),
			'method' => 'PUT',
			'data' => $options,
		) );
	}// end update_audience

	/**
	 * List all dimensions associated with the project.
	 */
	public function get_dimensions( $project_id ) {
		return $this->request( array(
			'function' => 'projects/' . abs( intval( $project_id ) ) . '/dimensions/',
			'method' => 'GET',
		) );
	}// end get_dimensions

	/**
	 * Get metadata for a single dimension.
	 */
	public function get_dimension( $dimension_id ) {
		return $this->request( array(
			'function' => 'dimensions/' . abs( intval( $dimension_id ) ),
			'method' => 'GET',
		) );
	}// end get_dimension

	/**
	 * Create a dimension
	 */
	public function create_dimension( $project_id, $options ) {
		if ( ! isset( $options['name'] ) ) {
			return FALSE;
		}// end if

		return $this->request( array(
			'function' => 'projects/' . abs( intval( $project_id ) ) . '/dimensions/',
			'method' => 'POST',
			'data' => $options,
		) );
	}// end create_dimension

	/**
	 * Update a dimension
	 */
	public function update_dimension( $dimension_id, $options ) {
		return $this->request( array(
			'function' => 'dimensions/' . abs( intval( $dimension_id ) ),
			'method' => 'PUT',
			'data' => $options,
		) );
	}// end update_dimension

	/**
	 * Delete a dimension, not generally recommended
	 */
	public function delete_dimension( $dimension_id ) {
		return $this->request( array(
			'function' => 'dimensions/' . abs( intval( $dimension_id ) ),
			'method' => 'DELETE',
		) );
	}// end delete_dimension

}// end Optimizely
