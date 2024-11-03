<?php

/*
searchwp_basic_auth_creds
  In order to get around the limitations of PHP timeouts, its indexer operates by making a series of HTTP calls to itself.
  Unfortunately HTTP Basic Authentication prevents this process from working, so if you are using HTTP Basic Authentication
  on your site, you’ll need to tell SearchWP how to log in.
*/
class MySearchWPBasicAuthCreds {

	function __construct($username, $password) {
			$this->username = $username;
			$this->password = $password;

		// Provide HTTP Basic Authentication credentials to SearchWP.
		add_filter(
			'searchwp\indexer\http_basic_auth_credentials',
			function( $credentials ) {
				return [
					'username' => $this->username,
					'password' => $this->password,
				];
			}
		);

		// Also provide HTTP Basic Authentication credentials to WP Cron.
		// This can be removed if handled elsewhere, otherwise *REQUIRED*
		add_filter( 'cron_request', function( $cron_request ) {
			if ( ! isset( $cron_request['args']['headers'] ) ) {
				$cron_request['args']['headers'] = [];
			}

			if ( isset( $cron_request['args']['headers']['Authorization'] ) ) {
				return $cron_request;
			}

			$cron_request['args']['headers']['Authorization'] = sprintf(
				'Basic %s',
				base64_encode( $this->username . ':' . $this->password )
			);
		}, 999 );
	}
}

if($_SERVER['SERVER_NAME'] == 'gstchealth.wpengine.com' ) {
    new MySearchWPBasicAuthCreds('gstchealth', 'gstchealth');
} else if ($_SERVER['SERVER_NAME'] == 'gstcstage.wpengine.com') {
	  new MySearchWPBasicAuthCreds('gstcstage', 'gstcstage');
}

if (isset($_GET["order"])) {

  // Sort SearchWP Post, Page, and Custom Post Type Results by date in DESC order.
  add_filter( 'searchwp\query\mods', function( $mods, $query ) {

    $order = htmlspecialchars($_GET["order"]);

    foreach ( $query->get_engine()->get_sources() as $source ) {
        $flag = 'post' . SEARCHWP_SEPARATOR;
        if ( 'post.' !== substr( $source->get_name(), 0, strlen( $flag ) ) ) {
        continue;
        }

        $mod = new \SearchWP\Mod( $source );

        $mod->order_by( function( $mod ) {
        return $mod->get_local_table_alias() . '.post_date';
        }, $order, 1 );

        $mods[] = $mod;
    }

  return $mods;

  }, 20, 2 );
}



