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

	public function test_create_experiment() {
		$description = 'PHPUnit test experiment [' . date( 'Y-m-d H:i:s' ) . ']';
		$experiment = $this->optimizely->create_experiment( $this->project_id, array(
			'description' => $description,
			'edit_url' => 'http://funnelenvy.com/',
		) );

		$this->assertEquals( $experiment->description, $description );
		$this->assertEquals( $experiment->status, 'Not started' );
		$this->assertEquals( $experiment->project_id, $this->project_id );
	}//end test_create_experiment

	private function get_experiment() {
		$experiments = $this->optimizely->get_experiments( $this->project_id );
		$this->assertTrue( count( $experiments ) > 0 );

		return $experiments[0];
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
		$experiment = $experiments[0];

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

	public function test_create_schedule() {
		$experiment = $this->get_experiment();

		$schedule = $this->optimizely->create_schedule( $experiment->id, array(
			'start_time' => gmdate( $this->optimizely->date_format, strtotime( 'tomorrow' ) ),
			'stop_time' => gmdate( $this->optimizely->date_format, strtotime( '+1 week' ) ),
		) );

		$this->assertObjectHasAttribute( 'id', $schedule );
		$this->assertEquals( $experiment->id, $schedule->experiment_id );
	}//end test_create_schedule

	private function get_schedule() {
		$experiment = $this->get_experiment();
		$schedules = $this->optimizely->get_schedules( $experiment->id );

		$this->assertTrue( count( $schedules ) > 0 );
		return $schedules[0];
	}// end get_schedule

	public function test_update_schedule() {
		$schedule = $this->get_schedule();

		$new_start_time = gmdate( $this->optimizely->date_format, strtotime( '+2 days' ) );

		$schedule = $this->optimizely->update_schedule( $experiment->id, array(
			'start_time' => $new_start_time
		) );

		$this->assertEquals( $new_start_time, $schedule->start_time );
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
		$experiment = $this->get_experiment();
		$schedules = $this->optimizely->get_schedules( $experiment->id );

		$schedule = $schedules[0];

		$deleted = $this->optimizely->delete_schedule( $schedule->id );

		$new_schedules = $this->optimizely->get_schedules( $experiment->id );
		$this->assertNotEquals( count( $schedules ), count( $new_schedules ) );
	}//end test_delete_schedule

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

		$variation = $this->optimizely->get_variation( $experiment->variation_ids[0] );

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

		$variation = $variations[ count( $variations ) - 1 ];

		$deleted = $this->optimizely->delete_variation( $variation->id );

		$new_variations = $this->optimizely->get_variations( $experiment->id );
		$this->assertNotEquals( count( $variations ), count( $new_variations ) );
	}//end test_delete_variation

	public function test_get_goals() {

	}//end test_get_goals

	public function test_get_goal() {
		/*
		$goals = $optimizely->get_goals( $project->id );
		print_r( $goals );
		$goal = $optimizely->get_goal( $goals[0]->id );
		print_r( $goal );
		// */
	}//end test_get_goal

	public function test_create_goal() {

	}//end test_create_goal

	public function test_update_goal() {

	}//end test_update_goal

	public function test_delete_goal() {

	}//end test_delete_goal

	public function test_add_goal() {

	}//end test_add_goal

	public function test_remove_goal() {

	}//end test_remove_goal

	public function test_get_audiences() {

	}//end test_get_audiences

	public function test_get_audience() {
		/*
		$audiences = $optimizely->get_audiences( $project->id );
		print_r( $audiences );
		$audience = $optimizely->get_audience( $audiences[0]->id );
		print_r( $audience );
		// */
	}//end test_get_audience

	public function test_create_audience() {

	}//end test_create_audience

	public function test_update_audience() {

	}//end test_update_audience

	public function test_get_dimensions() {

	}//end test_get_dimensions

	public function test_get_dimension() {
		/*
		$dimensions = $optimizely->get_dimensions( $project->id );
		print_r( $dimensions );
		$dimension = $optimizely->get_dimension( $dimensions[0]->id );
		print_r( $dimension );
		// */
	}//end test_get_dimension

	public function test_create_dimension() {

	}//end test_create_dimension

	public function test_update_dimension() {

	}//end test_update_dimension

	public function test_delete_dimension() {

	}//end test_delete_dimension
}// end class