# optimizely-php
PHP Wrapper for the Optimizely REST API

Example uses:
```php
// setup the object
$optimizely = new Optimizely( 'API KEY' );

// get projects
$projects = $optimizely->get_projects();

// get experiments
$experiments = $optimizely->get_experiments( $projects[0]->id );

// create an experiment
$experiment = $optimizely->create_experiment( $projects[0]->id, array(
  'description' => 'Amazing new experiment',
	'edit_url' => 'http://funnelenvy.com/',
) );
```
