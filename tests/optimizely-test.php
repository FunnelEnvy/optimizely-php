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

	/*
	@TODO: Add eventually:

	$schedules = $optimizely->get_schedules( $experiments[43]->id );
	print_r( $schedules );
	$schedule = $optimizely->get_schedule( $schedules[0]->id );
	print_r( $schedule );
	// */

	/*
	$variations = $optimizely->get_variations( $experiments[43]->id );
	print_r( $variations );
	$variation = $optimizely->get_variation( $variations[0]->id );
	print_r( $variation );
	// */

	/*
	$goals = $optimizely->get_goals( $project->id );
	print_r( $goals );
	$goal = $optimizely->get_goal( $goals[0]->id );
	print_r( $goal );
	// */

	/*
	$dimensions = $optimizely->get_dimensions( $project->id );
	print_r( $dimensions );
	$dimension = $optimizely->get_dimension( $dimensions[0]->id );
	print_r( $dimension );
	// */

	/*
	$audiences = $optimizely->get_audiences( $project->id );
	print_r( $audiences );
	$audience = $optimizely->get_audience( $audiences[0]->id );
	print_r( $audience );
	// */

}// end class