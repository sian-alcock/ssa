<?php

/*
***
Cron Task
***
*/

// function that registers new custom schedule
function pinpoint_cron_schedule($schedules)
{
  if (!isset($schedules["5min"])) {
    $schedules["5min"] = array(
      'interval' => 300,
      'display' => __('Every 5 minutes')
    );
  }
  return $schedules;
}

// function that schedules custom event
add_action('init', function () {
  // adding the cron_schedule here makes sure it is run before wp_schedule_event()
  add_filter('cron_schedules', 'pinpoint_cron_schedule');

  // add the function that will run on schedule
  add_action('pinpoint_cron', 'update_jobs');
  add_action('pinpoint_cron', 'delete_jobs');

  // run the schedule
  if (!wp_next_scheduled('pinpoint_cron')) {
    wp_schedule_event(strtotime('03:00:00'), '5min',  'pinpoint_cron');
  }
});



/*
***
Getting the data from the REST json
***
*/

function get_jobs_from_api()
{

  require __DIR__ . '/../vendor/autoload.php';
  $client = new \GuzzleHttp\Client();


  // ACF Options value to set env  prod/dev
  $environment = get_field('pinpoint_environment', 'option');

  if ($environment === "prod") {
    /* Production endpoint  staus: open  visibilty : external */
    $endpoint = 'https://gsttfoundation.pinpointhq.com/api/v1/jobs?filter[status]=open&filter[visibility]=external&include=location,custom_attributes&locale=en&page[number]=1&page[size]=100';
  } else {
    // Development endpoint - shows all jobs
    $endpoint = 'https://gsttfoundation.pinpointhq.com/api/v1/jobs?locale=en&page[number]=1&page[size]=100&include=location,custom_attributes';
  }


  $response = $client->request('GET', $endpoint, [
    'headers' => [
      'X-API-KEY' => 'Wxt2yGs9U43gzpTaHUAZ9SJm',
      'accept' => 'application/vnd.api+json',
    ],
  ]);

  $body = $response->getBody();

  // for development
  echo $body;

  return $body;
}



function update_jobs()
{
  // delete all cuurent posts... each cron task run a fresh scrape off the endpoints and creates new posts, this is a way to cover any updates or out of date potst
  $allposts = get_posts(array('post_type' => 'job', 'numberposts' => -1));
  foreach ($allposts as $eachpost) {
    wp_delete_post($eachpost->ID, true);
  }

  // Get the results
  $results = get_jobs_from_api();
  $results = json_decode($results, true);

  // Fall back endpoint that does not require key but returns less results
  // $results = wp_remote_retrieve_body(wp_remote_get('https://gsttfoundation.pinpointhq.com/postings.json'));

  if (!is_array($results) || empty($results)) {
    return false;
  }

  $jobs = $results['data'];
  $included = $results['included'];
  $pinpoint_ids = array();

  // ACF option to pick what jobs get shown
  $site_organisation = get_field('pinpoint_organisation', 'option');


  foreach ($jobs as $job) {

    $pinpoint_id = $job['id'];
    array_push($pinpoint_ids, $pinpoint_id);
    $job_title = "{$job['attributes']['title']} {$job['id']} ";
    $department = $job['attributes']['department']['name'];
    $custom_attr_ids = $job['relationships']['custom_attributes']['data'];

    $organisation = 'N/A';
    $location_id = $job['relationships']['location']['data']['id'];
    $location = 'N/A - ' . $location_id;




    foreach ($custom_attr_ids as $attr_id) {


      foreach ($included as $include) {

        // Check for location attr

        if ($include['id'] === $location_id) {
          echo "type  " .  $include['type'] . "</br>";

          if ($include['type'] === "locations") {

            //echo "YES!!";
            $location = $include['attributes']['name'];
          }
        }


        // Check for custom attributes
        if ($include['id'] ===  $attr_id['id']) {
          if ($include['attributes']['field_name'] === "Organisation") {
            $organisation = $include['attributes']['value'];
          }


          if ($include['attributes']['field_name'] === "JobPack") {
            $job_pack = $include['attributes']['value'];
          }

          if ($include['attributes']['field_name'] === "Video") {
            $video = $include['attributes']['value'];
          }
        }
      }
    }

    // the two ACF components we want to input the jobs data to
    $job_hero = [
      'field_657c6e4a91335' => 'title',
      'field_657c6e5091336' => 'compensation',
      'field_657c6f1b134eb' => 'deadline_at',
      'field_657c6a7047f33' => 'employment_type_text',
      'field_657c65e8c597f' => 'location',
      'field_657c6a3747f31' => 'workplace_type_text',
      'field_657c6a5047f32' => 'job_pack',
    ];

    $job_content_block = [
      'field_657c637a37e04' => 'visibility',
      'field_657c636b37e03' => 'status',
      'field_657c634137e01' => 'pinpoint_id',
      'field_657c635837e02' => 'organisation',
      'field_657c638537e05' => 'updated_at',
      'field_657c639f37e07' => 'description',
      'field_657c63ab37e08' => 'key_responsibilities_header',
      'field_657c63c537e09' => 'key_responsibilities',
      'field_657c63cd37e0a' => 'skills_knowledge_expertise_header',
      'field_657c63e637e0b' => 'skills_knowledge_expertise',
      'field_657c63ee37e0c' => 'benefits_header',
      'field_657c63fc37e0d' => 'benefits',
      'field_657c639037e06' => 'url',
      'field_657c640f37e0e' => 'video',
      'field_657c642537e0f' => 'job_pack',
    ];

    // check if post already exists, this was an old check which might not be needed now but could be useful for debugging
    // $existing_job  = get_page_by_path($job_title, 'OBJECT', 'job');

    // check if the organisation field matches the website
    if (str_contains($organisation, $site_organisation)) {

      $new_job = wp_insert_post([
        'post_name' => $job_title,
        'post_title' => $job_title,
        'post_type' => 'job',
        'post_status' => 'publish',
      ]);

      if (is_wp_error($new_job)) {
        continue;
      }

      foreach ($job_hero as $key => $name) {
        if ($name === 'department') {
          update_field($key, $department, $new_job);
        } elseif ($name === 'location') {
          update_field($key, $location, $new_job);
        } elseif ($name === 'job_pack') {
          update_field($key, $job_pack, $new_job);
        } else {
          update_field($key, $job['attributes'][$name], $new_job);
        }
      }

      foreach ($job_content_block as $key => $name) {
        if ($name === 'pinpoint_id') {
          update_field($key, $pinpoint_id, $new_job);
        } elseif ($name === 'organisation') {
          update_field($key, $organisation, $new_job);
        } elseif ($name === 'video') {
          update_field($key, $video, $new_job);
        } elseif ($name === 'job_pack') {
          update_field($key, $job_pack, $new_job);
        } else {
          update_field($key, $job['attributes'][$name], $new_job);
        }
      }
    }
  }
}


/*
***
// FOR DEVELOPMENT... a button to run update_jobs, shows when ACF option
***
*/

// Test button "Get Results" will show in wp-admin single jobs page
$environment = get_field('pinpoint_environment', 'option');
if ($environment === "dev") {
  add_action('add_meta_boxes', 'jobs_data');
}

function jobs_data()
{
  add_meta_box(
    'jobs_data_id',   // Unique ID
    'For Dev - jobs data',      // Box title
    'tpx_render_jobs_data',  // Content callback, must be of type callable
    'job', // Post type
  );
}




//Render the input
function tpx_render_jobs_data()
{
?>
  <div style="margin-top: 20px">

    <input style="margin-right: 6px; display: none" type="text" name="tpx-image-id" id="tpx-image-id" value="<?php echo get_the_ID();  ?>">
    <button id="tpx-submit" name="tpx-submit" class="button-primary active" value="<?php _e('Get Results',  'tpx');  ?>">Get Results</button>
    <img style="margin: 0 0 -5px 5px; display: none" src="wp-includes/images/spinner.gif" id="tpx-loader-image">

    <code id="tpx-results">
      <!-- results added here -->
    </code>
  </div>
<?php
}


//Load the jquery script we need to listen to the "Get Results" button
function tpx_load_scripts($hook)
{
  wp_enqueue_script('pinpoint-ajax', get_template_directory_uri() . '/functions/js/pinpoint-ajax.js', array('jquery'));
}
add_action('admin_enqueue_scripts', 'tpx_load_scripts');

add_action('wp_ajax_tpx_get_results', 'update_jobs');

//update_jobs();