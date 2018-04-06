<?php
/**
 * @package PreventClick
 */
/*
Plugin Name: Prevent Click
Plugin URI: https://preventclick.com/
Description: Prevent Click is used to prevent click on any html element. Just provide the id of the tag and maximum clicks you want.
Version: 1.0
Author: Aleem Tahir
License: GPLv2 or later
Text Domain: akismet
*/

/*********
GLOBALS
*********/
$wp_pclick_options = get_option('PreventClick_settings');
 
register_activation_hook( __FILE__, 'jal_install' );
register_uninstall_hook(__FILE__, 'on_uninstall');


function PreventClick_setting() {
	register_setting('PreventClick_group','PreventClick_settings');
}
add_action('admin_init','PreventClick_setting');

function PreventClick_admin_actions() {
	add_options_page('PreventClick','PreventClick', 'manage_options', __FILE__, 'PreventClick_admin');
}
add_action('admin_menu','PreventClick_admin_actions');

function PreventClick_admin()
{
	global $wp_pclick_options;
	ob_start(); ?>

	<div class="wrap">
	<h2> A More Interesting Hello World Plugin</h2>
	<h3> Prevent Click is used to prevent click on any html element. Just provide the id of the tag and maximum clicks you want</h3>
	<br/>
	<form action="options.php" method="POST">
		<?php settings_fields('PreventClick_group'); ?>

		Element ID  
		<input type="text" name="PreventClick_settings[id]" value="<?php echo $wp_pclick_options['id']; ?>" /> 
		<br/>
		Max. Count  
		<input type="text" name="PreventClick_settings[count]" value="<?php echo $wp_pclick_options['count']; ?>"> <br/>
		Prevention Time  
		<input type="text" name="PreventClick_settings[p_time]" value="<?php echo $wp_pclick_options['p_time']; ?>">&nbsp Minutes <br/><br/>
		<input type="submit" name="add_input" value="Submit" class="button-primary" />
		<input type="submit" name="reset_usr_clk" value="Reset Clicks" class="button-primary" />
		<input type="submit" name="dlt_prv_record" value="Truncate" class="button-primary" />
		<br/><br/>
		<h3>Note</h3>
		<li><strong>Element id:</strong> Id of html element on which you want to control clicks.</li>
		<li><strong>Max. Count:</strong> Maximum number of counts on html element.</li>
		<li><strong>Prevention Time:</strong> Duration of Prevention.</li>
		

	</form>

	</div>
<?php
echo ob_get_clean();
}
ini_set("display_errors", 0);

if (isset($_POST['reset_usr_clk']) ) {
    reset_usr_clk();
}
if (isset($_POST['dlt_prv_record']) ) {
    dlt_prv_record();
}


function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function ajax_enqueuescripts() {
	global $wp_pclick_options;
	$img_id = $wp_pclick_options['id'];

    wp_enqueue_script('ajaxloadpost', plugins_url().'/PreventClick/js/my-ajax.js', array('jquery'));
    wp_localize_script( 'ajaxloadpost', 'ajax_postajax', array( 
    	'ajaxurl' => admin_url('admin-ajax.php'),
    	'id' => $img_id 
    ) );

    wp_enqueue_style( 'my-style', plugins_url( '/css/my-style.css', __FILE__ ), false, '1.0', 'all' ); // Inside a plugin
}

add_action('wp_enqueue_scripts', 'ajax_enqueuescripts');
add_action('wp_ajax_nopriv_get_rows', 'get_rows' );
add_action('wp_ajax_get_rows', 'get_rows' );
add_action('wp_ajax_nopriv_update_row', 'update_row' );
add_action('wp_ajax_update_row', 'update_row' );

function get_rows() {
	global $wpdb,$wp_pclick_options; 

	$params = array();
	$img_id = $wp_pclick_options['id'];
	$count = $wp_pclick_options['count'];
	$p_time = $wp_pclick_options['p_time'];

	//get rows
	$cookie = $_COOKIE['site_stats'];
	$row  = $wpdb->get_row(
		"SELECT * FROM {$wpdb->prefix}click_ads 
		 WHERE uid = '$cookie'
		 AND dtime >= NOW() - INTERVAL $p_time MINUTE;
		",
		'ARRAY_A'
	);

	//delete previous record if timeout
	if(empty($row))
	{
		$wpdb->query(
			"DELETE FROM {$wpdb->prefix}click_ads
				WHERE uid = '$cookie'
				AND dtime < NOW() - interval $p_time MINUTE ;"	
		);
	}
	
	//send response
	$params['img_id'] = $img_id;
	$params['count'] = $count; 
	if($row)
		$params['clk_count'] = $row['clk_count'];
	else
		$params['clk_count'] = 0;
	

	ob_clean();
	echo json_encode($params); 

	wp_die(); // this is required to terminate immediately and return a proper response
}

function update_row() {
	global $wpdb,$wp_pclick_options;

	$ip = getRealIpAddr();
	$cookie = $_COOKIE['site_stats'];
	$img_id = $wp_pclick_options['id'];
	$count = $wp_pclick_options['count'];
	$p_time = $wp_pclick_options['p_time'];
	$date = date('Y-m-d');

	//update row
	$wpdb->query(
			"INSERT INTO {$wpdb->prefix}click_ads (date_added, uid, clk_count, user_ip)
				VALUES ('$date', '$cookie',1,'$ip')
				ON DUPLICATE KEY UPDATE clk_count=clk_count+1
			"	
	);

	//get row
	$row  = $wpdb->get_row(
		"SELECT * FROM {$wpdb->prefix}click_ads 
		 WHERE uid = '$cookie'
		 AND dtime >= NOW() - INTERVAL $p_time MINUTE;
		",
		'ARRAY_A'
	);
	
	//send response
	$params['img_id'] = $img_id;
	$params['count'] = $count; 
	if($row)
		$params['clk_count'] = $row['clk_count'];
	else
		$params['clk_count'] = 0;

	ob_clean();
	echo json_encode($params); 

	wp_die(); // this is required to terminate immediately and return a proper response
}

function reset_usr_clk() {
	global $wpdb,$wp_pclick_options; 

	$date = date('Y-m-d');
	$cookie = $_COOKIE['site_stats'];

	$wpdb->query(
			"UPDATE {$wpdb->prefix}click_ads SET clk_count = 0
				WHERE uid = '$cookie' and date_added = '$date' ;"	
	);
}

function dlt_prv_record() {
	global $wpdb,$wp_pclick_options; 
	$p_time = $wp_pclick_options['p_time'];

	$wpdb->query(
			"DELETE FROM {$wpdb->prefix}click_ads
				WHERE dtime < NOW() - interval $p_time HOUR ;"	
	);
}

/*------------------------------------
			Set cookie
------------------------------------*/
add_action('init', 'stats_cookie');
function stats_cookie()
{
    if(!isset($_COOKIE['site_stats']))
    { 
        $current = current_time( 'timestamp', 1);
        setcookie('site_stats', $current, time() + (86400 * 30), "/"); // 86400 = 1 day 
    }
}


/*-----------------------------------*/
//		Activate or Uninstall
/*----------------------------------*/
function jal_install() {
	global $wpdb;
	$table_name = $wpdb->prefix . "click_ads"; 

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
	  date_added date DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  dtime timestamp,
	  uid varchar(55) NOT NULL,
	  clk_count int(11) NOT NULL,
	  user_ip varchar(55) NOT NULL,
	  PRIMARY KEY  (date_added,uid)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}
function on_uninstall() {
	global $wpdb;
	$table_name = $wpdb->prefix . "click_ads"; 

    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);

    //delete options
    delete_option('PreventClick_settings');

}

?>