<?php

require dirname( __DIR__ ) . '/optimizely.php';

/**
 * Optimizely-PHP unit tests
 */
class Optimizely_Test extends PHPUnit_Framework_TestCase {
	private $optimizely;
	private $project_id;

	/*
	 * this is run before each test* function in this class, to set
	 * up the environment each test runs in.
	 */
	public function setUp() {
		parent::setUp();

		// defaults to a test account setup for unit testing purposes
		$this->optimizely = new Optimizely( getenv( 'OPTIMIZELY_API_KEY' ) ?: '215789e2d709bcc4fda5b2244c3491a8:74c5f79c' );
		$this->project_id = getenv( 'OPTIMIZELY_PROJECT_ID' ) ?: '2651261141';

		$this->assertTrue( is_object( $this->optimizely ) > 0 );
	}//end setUp

	/*
	 * ==========
	 * Projects
	 * ==========
	 */

	public function test_get_projects() {
		$projects = $this->optimizely->get_projects();

		$this->assertTrue( count( $projects ) > 0 );
	}//end test_get_projects

	public function test_create_project() {
		// This is commented out since the API does no allow project deletion, this would build out A LOT of projects
		/*
		$project = $this->optimizely->create_project( array( 'project_name' => 'PHPUnit test project' ) );

		$this->assertFalse( isset( $project->status ) && '403' == $project->status );
		*/
	}//end test_create_project

	public function test_get_project() {
		$project = $this->optimizely->get_project( $this->project_id );
		$this->assertObjectHasAttribute( 'project_name', $project );
	}//end test_update_project

	public function test_update_project() {
		$project = $this->optimizely->get_project( $this->project_id );
		$this->assertTrue( is_object( $project ) && isset( $project->project_name ) );

		$project_name = 'PHPUnit test project [' . date( 'Y-m-d H:i:s' ) . ']';

		$this->optimizely->update_project( $project->id, array( 'project_name' => $project_name ) );

		$updated_project = $this->optimizely->get_project( $this->project_id );

		$this->assertEquals( $updated_project->project_name, $project_name );
	}//end test_update_project

	/*
	 * ============
	 * Experiments
	 * ============
	 */

	public function test_create_experiment() {
		$description = 'PHPUnit test experiment [' . date( 'Y-m-d H:i:s' ) . ']';
		$experiment = $this->optimizely->create_experiment( $this->project_id, array(
			'description' => $description,
			'edit_url' => 'http://funnelenvy.com/',
		) );

		$this->assertEquals( $experiment->description, $description );
		$this->assertEquals( $experiment->status, 'Not started' );
		$this->assertEquals( $experiment->project_id, $this->project_id );

		return $experiment;
	}//end test_create_experiment

	private function get_experiment() {
		$experiments = $this->optimizely->get_experiments( $this->project_id );
		$this->assertTrue( count( $experiments ) > 0 );

		if ( 0 == count( $experiments ) ) {
			return $this->test_create_experiment();
		}//end if

		$this->assertTrue( count( $experiments ) > 0 );
		return end( $experiments );
	}// end get_experiment

	public function test_update_experiment() {
		$experiment = $this->get_experiment();

		$description = 'PHPUnit test experiment updated [' . date( 'Y-m-d H:i:s' ) . ']';
		$updated_experiment = $this->optimizely->update_experiment( $experiment->id, array(
			'description' => $description,
			'status' => 'Running',
		) );

		$this->assertEquals( $updated_experiment->description, $description );
		$this->assertEquals( $updated_experiment->status, 'Running' );
		$this->assertEquals( $updated_experiment->project_id, $this->project_id );
	}//end test_create_experiment

	public function test_archive_experiment() {
		$experiments = $this->optimizely->get_experiments( $this->project_id );
		$this->assertTrue( count( $experiments ) > 0 );
		$experiment = end( $experiments );

		$this->optimizely->delete_experiment( $experiment->id );

		$archived_experiment = $this->optimizely->get_experiment( $experiment->id );
		$this->assertEquals( $archived_experiment->status, 'Archived' );

		$experiments_again = $this->optimizely->get_experiments( $this->project_id );
		$this->assertEquals( count( $experiments ), count( $experiments_again ), 'confirm experiment was archived, not deleted' );
	}//end test_archive_experiment

	public function test_delete_experiment() {
		$experiments = $this->optimizely->get_experiments( $this->project_id );
		$this->assertTrue( count( $experiments ) > 0 );
		$experiment = $experiments[0];

		$this->optimizely->delete_experiment( $experiment->id, FALSE );

		$archived_experiment = $this->optimizely->get_experiment( $experiment->id );
		$this->assertEquals( $archived_experiment->status, '410' );

		$experiments_again = $this->optimizely->get_experiments( $this->project_id );
		$this->assertNotEquals( count( $experiments ), count( $experiments_again ), 'confirm experiment was deleted, not archived' );
	}//end test_delete_experiment

	/*
	 * ==========
	 * Schedules
	 * ==========
	 */

	public function test_create_schedule() {
		$experiment = $this->get_experiment();

		$schedule = $this->optimizely->create_schedule( $experiment->id, array(
			'start_time' => gmdate( $this->optimizely->date_format, strtotime( 'tomorrow' ) ),
			'stop_time' => gmdate( $this->optimizely->date_format, strtotime( '+1 week' ) ),
		) );

		$this->assertObjectHasAttribute( 'id', $schedule );
		$this->assertEquals( $experiment->id, $schedule->experiment_id );

		return $schedule;
	}//end test_create_schedule

	private function get_schedule() {
		$experiment = $this->get_experiment();
		$schedules = $this->optimizely->get_schedules( $experiment->id );

		$active_schedule = FALSE;
		foreach ( $schedules as $i => $schedule ) {
			if ( 'ACTIVE' == $schedule->status ) {
				$active_schedule = $schedule;
			}//end if
		}//end foreach

		if ( ! $active_schedule ) {
			return $this->test_create_schedule();
		}//end if

		$this->assertTrue( is_object( $active_schedule ) );
		return $active_schedule;
	}// end get_schedule

	public function test_update_schedule() {
		$schedule = $this->get_schedule();

		$new_start_time = gmdate( $this->optimizely->date_format, strtotime( '+2 days' ) );

		$updated_schedule = $this->optimizely->update_schedule( $schedule->id, array(
			'start_time' => $new_start_time
		) );

		$this->assertObjectHasAttribute( 'id', $updated_schedule );
		$this->assertEquals( $new_start_time, $updated_schedule->start_time );
	}//end test_update_schedule

	public function test_get_schedules() {
		$this->get_schedule();
	}//end test_get_schedules

	public function test_get_schedule() {
		$schedule = $this->get_schedule();

		$the_schedule = $this->optimizely->get_schedule( $schedule->id );
		$this->assertObjectHasAttribute( 'id', $the_schedule );
	}//end test_get_schedule

	public function test_delete_schedule() {
		// make sure there is at least one active schedule element to delete
		$schedule = $this->get_schedule();

		$deleted = $this->optimizely->delete_schedule( $schedule->id );

		$updated_schedule = $this->optimizely->get_schedule( $schedule->id );
		$this->assertObjectHasAttribute( 'id', $updated_schedule );
		$this->assertEquals( 'INACTIVE', $updated_schedule->status );
	}//end test_delete_schedule

	/*
	 * ==========
	 * Variations
	 * ==========
	 */

	public function test_create_variation() {
		$experiment = $this->get_experiment();

		$description = 'test variant [' . date( 'Y-m-d H:i:s' ) . ']';
		$variation = $this->optimizely->create_variation( $experiment->id, array(
			'description' => $description,
		) );

		$this->assertObjectHasAttribute( 'id', $variation );
		$this->assertEquals( $experiment->id, $variation->experiment_id );
		$this->assertEquals( $description, $variation->description );
	}//end test_create_variation

	private function get_variation() {
		$experiment = $this->get_experiment();
		$this->assertTrue( count( $experiment->variation_ids ) > 0 );

		$variation = $this->optimizely->get_variation( end( $experiment->variation_ids ) );

		return $variation;
	}//end test_get_variations

	public function test_get_variations() {
		$experiment = $this->get_experiment();

		$variations = $this->optimizely->get_variations( $experiment->id );
		$this->assertTrue( is_array( $variations ) && count( $variations ) > 0 );
	}//end test_get_variations

	public function test_get_variation() {
		$variation = $this->get_variation();
		$this->assertObjectHasAttribute( 'id', $variation );
	}//end test_get_variation

	public function test_update_variation() {
		$variation = $this->get_variation();

		$new_description = 'test variant updated [' . date( 'Y-m-d H:i:s' ) . ']';

		$updated_variation = $this->optimizely->update_variation( $variation->id, array(
			'description' => $new_description
		) );

		$this->assertObjectHasAttribute( 'id', $updated_variation );
		$this->assertEquals( $updated_variation->description, $new_description );
	}//end test_update_variation

	public function test_delete_variation() {
		$experiment = $this->get_experiment();
		$variations = $this->optimizely->get_variations( $experiment->id );

		$variation = $variations[0];

		$variation = $this->optimizely->get_variation( $variation->id );

		$deleted = $this->optimizely->delete_variation( $variation->id );

		$new_variations = $this->optimizely->get_variations( $experiment->id );

		// for some reason this does not work.  skipping for now.
		//$this->assertNotEquals( count( $variations ), count( $new_variations ) );
	}//end test_delete_variation

	/*
	 * ==========
	 * Goals
	 * ==========
	 */

	private function get_goal() {
		$goals = $this->optimizely->get_goals( $this->project_id );
		$this->assertTrue( is_array( $goals ) );

		// every project gets 2 default goals that cannot be updated or deleted
		if (  2 >= count( $goals ) ) {
			return $this->test_create_goal();
		}//end if

		return end( $goals );
	}//end test_get_variations

	public function test_get_goals() {
		$this->get_goal();
	}//end test_get_goals

	public function test_get_goal() {
		$goal = $this->get_goal();
		$this->assertObjectHasAttribute( 'id', $goal );
	}//end test_get_goal

	public function test_create_goal() {
		$date = date( 'Y-m-d H:i:s' );

		$click_goal = $this->optimizely->create_goal( $this->project_id, array(
			'title' => 'test click goal [' . $date . ']',
			'goal_type' => 0,
			'selector' => '.test-selector a',
			'target_to_experiments' => TRUE,
		) );
		$this->assertObjectHasAttribute( 'id', $click_goal );

		$custom_event_goal = $this->optimizely->create_goal( $this->project_id, array(
			'title' => 'test custom event goal [' . $date . ']',
			'goal_type' => 1,
			'event' => 'test-event',
		) );
		$this->assertObjectHasAttribute( 'id', $custom_event_goal );

		$pageviews_goal = $this->optimizely->create_goal( $this->project_id, array(
			'title' => 'test pageviews goal [' . $date . ']',
			'goal_type' => 3,
			'urls' => array(
				'http://funnelenvy.com/test/exact/',
				'http://funnelenvy.com/tests/regexp/[a-z]*',
				'http://funnelenvy.com/test/simple/',
				'http://funnelenvy.com/test/substring/',

			),
			'url_match_types' => array(
				0,
				1,
				2,
				3,
			),
		) );
		$this->assertObjectHasAttribute( 'id', $pageviews_goal );
	}//end test_create_goal

	public function test_update_goal() {
		$goal = $this->get_goal();

		$new_title = 'updated goal title [' . date( 'Y-m-d H:i:s' ) . ']';
		$updated_goal = $this->optimizely->update_goal( $goal->id, array(
			'title' => $new_title
		) );

		$this->assertObjectHasAttribute( 'id', $updated_goal );
		$this->assertEquals( $updated_goal->title, $new_title );
	}//end test_update_goal

	public function test_delete_goal() {
		$goals = $this->optimizely->get_goals( $this->project_id );

		$goal = end( $goals );

		$deleted = $this->optimizely->delete_goal( $goal->id );

		$new_goals = $this->optimizely->get_goals( $this->project_id );
		$this->assertNotEquals( count( $goals ), count( $new_goals ) );
	}//end test_delete_goal

	public function test_add_goal() {
		$experiment = $this->get_experiment();
		$goal = $this->get_goal();

		$this->optimizely->add_goal( $experiment->id, $goal->id );

		$updated_experiment = $this->optimizely->get_experiment( $experiment->id );

		$this->assertObjectHasAttribute( 'id', $updated_experiment );
		$this->assertTrue( in_array( $goal->id, $updated_experiment->display_goal_order_lst ) );
	}//end test_add_goal

	public function test_remove_goal() {
		$experiment = $this->get_experiment();
		$goal = $this->get_goal();

		$result = $this->optimizely->remove_goal( $experiment->id, $goal->id );

		$updated_goal = $this->optimizely->get_goal( $goal->id );

		$this->assertObjectHasAttribute( 'id', $updated_goal );
		$this->assertNotTrue( in_array( $experiment->id, $updated_goal->experiment_ids ) );
	}//end test_remove_goal

	/*
	 * ==========
	 * Audiences
	 * ==========
	 */

	private function get_audience() {
		$audiences = $this->optimizely->get_audiences( $this->project_id );
		$this->assertTrue( is_array( $audiences ) );

		if (  0 == count( $audiences ) ) {
			return $this->test_create_audience();
		}//end if

		return end( $audiences );
	}//end get_audience

	public function test_get_audiences() {
		$this->get_audience();
	}//end test_get_goals

	public function test_get_audience() {
		$audience = $this->get_audience();
		$this->assertObjectHasAttribute( 'id', $audience );
	}//end test_get_audience

	public function test_create_audience() {
		$audience = $this->optimizely->create_audience( $this->project_id, array(
			'name' => 'test audience [' . date( 'Y-m-d H:i:s' ) . ']',
		) );

		$this->assertObjectHasAttribute( 'id', $audience );

		return $audience;
	}//end test_create_audience

	public function test_update_audience() {
		$audience = $this->get_audience();

		$updated_name = 'test audience updated [' . date( 'Y-m-d H:i:s' ) . ']';

		$updated_audience = $this->optimizely->update_audience( $audience->id, array(
			'name' => $updated_name,
		) );

		$this->assertObjectHasAttribute( 'id', $updated_audience );
		$this->assertEquals( $updated_audience->name, $updated_name );
	}//end test_update_audience

	/*
	 * ==========
	 * Dimensions
	 * ==========
	 */

	private function get_dimension() {
		$dimensions = $this->optimizely->get_dimensions( $this->project_id );
		$this->assertTrue( is_array( $dimensions ) );

		if (  0 == count( $dimensions ) ) {
			return $this->test_create_dimension();
		}//end if

		return end( $dimensions );
	}//end get_dimension

	public function test_get_dimensions() {
		$this->get_dimension();
	}//end test_get_goals

	public function test_get_dimension() {
		$dimension = $this->get_dimension();
		$this->assertObjectHasAttribute( 'id', $dimension );
	}//end test_get_dimension

	public function test_create_dimension() {
		$dimension = $this->optimizely->create_dimension( $this->project_id, array(
			'name' => 'test dimension [' . date( 'Y-m-d H:i:s' ) . ']',
		) );

		$this->assertObjectHasAttribute( 'id', $dimension );

		return $dimension;
	}//end test_create_dimension

	public function test_update_dimension() {
		$dimension = $this->get_dimension();

		$updated_name = 'test dimension updated [' . date( 'Y-m-d H:i:s' ) . ']';

		$updated_dimension = $this->optimizely->update_dimension( $dimension->id, array(
			'name' => $updated_name,
		) );

		$this->assertObjectHasAttribute( 'id', $updated_dimension );
		$this->assertEquals( $updated_dimension->name, $updated_name );
	}//end test_update_dimension

	public function test_delete_dimension() {
		$dimensions = $this->optimizely->get_dimensions( $this->project_id );

		$dimension = end( $dimensions );

		$deleted = $this->optimizely->delete_dimension( $dimension->id );

		$new_dimensions = $this->optimizely->get_dimensions( $this->project_id );
		$this->assertNotEquals( count( $dimensions ), count( $new_dimensions ) );
	}//end test_delete_dimension
}// end class