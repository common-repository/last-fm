<?php

/*
Plugin Name: Last FM
Plugin URI: https://wordpress.org/plugins/last-fm
Description: Permits the display in your sidebar of your most recent listened to tracks
Author: Kieran O'Shea
Author URI: http://www.kieranoshea.com
Version: 1.0.3
License: GPL2
Text Domain: last-fm
Domain Path: /languages
*/

/*  Copyright 2015  Kieran O'Shea  (email : kieran@kieranoshea.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Enable internationalisation
$plugin_dir = plugin_basename(dirname(__FILE__));
load_plugin_textdomain( 'last-fm',false, $plugin_dir.'/languages');

// Call out to the update code on every page load if the time limit has expired
if ( get_option('lastfm_username') != '' ) {
    if (get_option('lastfm_lastchecked')+300 < time()) {
        lastfm_update_data();
    }
}

// Add the action for the admin menu
add_action('admin_menu', 'lastfm_menu');

// The function to create the menu
function lastfm_menu() {
    // Set admin as the only one who can use last-fm for security
    $allowed_group = 'manage_options';
    if (function_exists('add_menu_page')) {
        add_menu_page(__('Last FM','last-fm'), __('Last FM','last-fm'), $allowed_group, 'last-fm', 'lastfm_admin');
    }
}

// The widget
add_action('widgets_init', 'widget_init_lastfm');
// The widget to show todays events in the sidebar
function widget_init_lastfm() {
    // Check for required functions
    if (!function_exists('wp_register_sidebar_widget'))
        return;

    function widget_lastfm($args) {
        //extract($args);
        $the_title = stripslashes(get_option('lastfm_widget_title'));
        $widget_title = empty($the_title) ? __('Last FM Recent Tracks','last-fm') : $the_title;
        $trackdata = lastfm_process_trackdata(get_option('lastfm_track_data'));
        if ($trackdata != '') {
            echo $args['before_widget'];
            echo $args['before_title'] . $widget_title . $args['after_title'];
            echo $trackdata;
            echo $args['after_widget'];
        }
    }

    function widget_lastfm_control() {
        if (isset($_POST['lastfm_widget_title'])) {
            update_option('lastfm_widget_title',strip_tags($_POST['lastfm_widget_title']), 'yes');
        }
        if (isset($_POST['lastfm_widget_count'])) {
            update_option('lastfm_widget_count',strip_tags($_POST['lastfm_widget_count']), 'yes');
        }
        if (isset($_POST['lastfm_widget_length'])) {
            update_option('lastfm_widget_length',strip_tags($_POST['lastfm_widget_length']), 'yes');
        }
        if (isset($_POST['lastfm_widget_dots'])) {
            update_option('lastfm_widget_dots','on', 'yes');
        } else if (!isset($_POST['lastfm_widget_dots']) && isset($_POST['lastfm_widget_title'])) {
            update_option('lastfm_widget_dots','off', 'yes');
        }
        if (isset($_POST['lastfm_widget_now'])) {
            update_option('lastfm_widget_now','on', 'yes');
        } else if (!isset($_POST['lastfm_widget_now']) && isset($_POST['lastfm_widget_title'])) {
            update_option('lastfm_widget_now','off', 'yes');
        }
        if (isset($_POST['lastfm_widget_covers'])) {
            update_option('lastfm_widget_covers','on', 'yes');
        } else if (!isset($_POST['lastfm_widget_covers']) && isset($_POST['lastfm_widget_title'])) {
            update_option('lastfm_widget_covers','off', 'yes');
        }
        $widget_title = stripslashes(get_option('lastfm_widget_title'));
        $show_dots = stripslashes(get_option('lastfm_widget_dots'));
        $line_length = stripslashes(get_option('lastfm_widget_length'));
        $track_count = stripslashes(get_option('lastfm_widget_count'));
        $show_now_playing = stripslashes(get_option('lastfm_widget_now'));
        $show_covers = stripslashes(get_option('lastfm_widget_covers'));
        ?>
            <p>
                <label for="lastfm_widget_title"><?php _e('Title','last-fm'); ?>:<br />
                    <input class="widefat" type="text" id="lastfm_widget_title" name="lastfm_widget_title" value="<?php echo $widget_title; ?>"/></label>
            </p>
            <p>
                <label for="lastfm_widget_count"><?php _e('Track Count','last-fm'); ?>:
                    <input class="tiny-text" step="1" min="1" size="3" type="number" id="lastfm_widget_count" name="lastfm_widget_count" value="<?php echo $track_count; ?>"/></label>
            </p>
            <p>
                <label for="lastfm_widget_length"><?php _e('Track Name Length','last-fm'); ?>:
                    <input class="tiny-text" step="1" min="1" size="3" type="number" id="lastfm_widget_length" name="lastfm_widget_length" value="<?php echo $line_length; ?>"/></label>
            </p>
            <p>
                <input <?php if ($show_dots=='on') { echo 'checked="checked"'; } ?> class="checkbox" type="checkbox" id="lastfm_widget_dots" name="lastfm_widget_dots" />
                <label for="lastfm_widget_dots"><?php _e('Show dots where track name has been shortened','last-fm'); ?></label>
            </p>
            <p>
                <input <?php if ($show_now_playing=='on') { echo 'checked="checked"'; } ?> class="checkbox" type="checkbox" id="lastfm_widget_now" name="lastfm_widget_now" />
                <label for="lastfm_widget_now"><?php _e('Show now playing indicator','last-fm'); ?></label>
            </p>
            <p>
                <input <?php if ($show_covers=='on') { echo 'checked="checked"'; } ?> class="checkbox" type="checkbox" id="lastfm_widget_covers" name="lastfm_widget_covers" />
                <label for="lastfm_widget_covers"><?php _e('Show album covers where available','last-fm'); ?></label>
            </p>
        <?php
    }

    wp_register_sidebar_widget('lastfm_recent_tracks',__('Last FM Recent Tracks','last-fm'),'widget_lastfm',array('description'=>__('A list of your recently listened to tracks via Last FM','last-fm')));
    wp_register_widget_control('lastfm_recent_tracks','lastfm_recent_tracks','widget_lastfm_control');
}

// Warn if Last FM is not properly setup
add_action( 'admin_notices', 'lastfm_setup_incomplete_warning' );
function lastfm_setup_incomplete_warning() {
    $incomplete_check = get_option('lastfm_username');
    if (empty($incomplete_check) && !(isset($_GET['page']) && $_GET['page'] == 'last-fm')) {
        $args = array( 'page' => 'last-fm');
        $url = add_query_arg( $args, admin_url( 'admin.php' ) );
        ?>
        <div class="update-nag"><p><strong><?php _e('Warning','last-fm'); ?>:</strong> <?php _e('Last FM setup incomplete. Go to the','last-fm'); echo ' <a href="'.$url.'">'; _e('Last FM plugin settings','last-fm'); echo '</a> '; _e('to complete setup.','last-fm'); ?></p></div>
        <?php
    }
}

// Function for the last fm admin page
function lastfm_admin() {
    if (isset($_POST['lastfm_username']) && isset($_POST['lastfm_api_key'])) {
        if (wp_verify_nonce($_POST['_wpnonce'],'last-fm') == false) {
            $fail = lastfm_show_error(__("Security check failure, try submitting details again",'last-fm'));
            lastfm_admin_form(get_option('lastfm_username'),get_option('lastfm_api_key'), $fail);
        } else {
            $username = $_POST['lastfm_username'];
            $apikey = $_POST['lastfm_api_key'];
            if (lastfm_validate_credentials($username, $apikey)) {
                update_option('lastfm_username', $username, 'yes');
                update_option('lastfm_api_key', $apikey, 'yes');
                update_option('lastfm_lastchecked', time(), 'yes');
                lastfm_update_data();
                $success = lastfm_show_success(__("Last FM details validated and saved successfully", 'last-fm'));
                lastfm_admin_form($username, $apikey, $success);
            } else {
                $fail = lastfm_show_error(__("Invalid Last FM username or API key! Please check and try again.", 'last-fm'));
                lastfm_admin_form($username, $apikey, $fail);
            }
        }

    } else {
        lastfm_admin_form(get_option('lastfm_username'),get_option('lastfm_api_key'));
    }
}

// Validate last fm credentials
function lastfm_validate_credentials($username, $apikey) {
    $xml = lastfm_retrieve_xml($username, $apikey);
    if ($xml->attributes()->status == 'ok') {
        return true;
    } else {
        return false;
    }
}

// Retrieve Last FM XML
function lastfm_retrieve_xml($username, $apikey) {
    $api_url = "https://ws.audioscrobbler.com/2.0/?method=user.getRecentTracks&user=".$username."&limit=10&api_key=".$apikey;
    $result = wp_remote_get($api_url);
    return simplexml_load_string($result['body']);
}

// Process raw track data
function lastfm_process_trackdata($xml) {
    $data = simplexml_load_string($xml);
    $iter=0;
    $trackdata = '<ul>';
    $show_dots = get_option('lastfm_widget_dots') == 'on' ? true : false;
    $show_covers = get_option('lastfm_widget_covers') == 'on' ? true : false;
    $show_now_playing = get_option('lastfm_widget_now') == 'on' ? true : false;
    $line_length = get_option('lastfm_widget_length') != '' ? get_option('lastfm_widget_length') : 22;
    $max_count = get_option('lastfm_widget_count') != '' ? get_option('lastfm_widget_count') : 100;
    $offset = get_option( 'gmt_offset' ) * 60 * 60;
    foreach($data->xpath("recenttracks/track") as $item) {
        if ($iter < $max_count) {
            $nowplaying = $item->attributes()->nowplaying;
            $orig_title = $item->artist . ' - ' . $item->name;
            $trun_title = mb_strimwidth($item->artist . ' - ' . $item->name, 0, $line_length);
            if (strlen($orig_title) == strlen($trun_title)) {
                $pad = '';
            } else {
                $pad = $show_dots ? ' ...' : '';
            }
            $timestamp = strtotime( $item->date );
            if ($timestamp > 0) {
                $local_timestamp = $timestamp + $offset;
                $date_time = date_i18n( 'd M Y, H:i', $local_timestamp ); // Format the same as last.fm original
            } else {
                $date_time = __("Playing now...", 'last-fm');
            }
            $image_url = $item->image != '' ? $item->image : 'https://lastfm-img2.akamaized.net/i/u/34s/c6f59c1e5e7240a4c0d427abd71f3dbb.png';
            $cover_code = $show_covers ? "background: url('".$image_url."') no-repeat left center; padding-left:40px; margin-left:-20px; list-style-type:none;" : "";
            $now_playing_code = $show_now_playing && $nowplaying ? "background: url('".plugins_url( 'images/icon_eq.gif', __FILE__ )."') no-repeat right center;" : '';
            if ($cover_code == '' && $now_playing_code != '') {
                $style_code = 'style="'.$now_playing_code.'"';
            } else if ($cover_code != '' && $now_playing_code == '') {
                $style_code = 'style="'.$cover_code.'"';
            } else if ($cover_code != '' && $now_playing_code != '') {
                $style_code = 'style="'.str_replace('background: ',str_replace(';',',',$now_playing_code),$cover_code).'"';
            } else {
                $style_code = '';
            }
            $trackdata .= '<li '.$style_code.'><a href="' . $item->url . '" title="' . $item->artist . ' - ' . $item->name . '">' . $trun_title . $pad . '</a><br />' . $date_time . '</li>';
        }
        $iter++;
    }
    $trackdata .= '</ul>';
    return $trackdata;
}

// Update last fm data
function lastfm_update_data() {
    $xml = lastfm_retrieve_xml(get_option('lastfm_username'),get_option('lastfm_api_key'));
    update_option('lastfm_track_data', $xml->asXML(), 'yes');
    update_option('lastfm_lastchecked', time(), 'yes');
}

// Last FM error display
function lastfm_show_error($error) {
    return '<div id="error" class="error notice notice-success below-h2"><p><strong>'.__('Error','last-fm').':</strong> '.$error.'</p></div>';
}

// Last FM error display
function lastfm_show_success($message) {
    return '<div id="message" class="updated notice notice-success below-h2"><p>'.$message.'</p></div>';
}

// Last FM admin form
function lastfm_admin_form($username, $apikey, $message = '') {
    ?>
    <div class="wrap">
        <h1><?php _e('Last FM','last-fm'); ?></h1>
        <?php echo $message ?>
        <form name="lastfmform" id="lastfmform" class="wrap" method="post" action="admin.php?page=last-fm">
            <?php wp_nonce_field('last-fm'); ?>
            <div id="linkadvanceddiv" class="postbox">
                <div style="float: left; width: 98%; clear: both;" class="inside">
                    <table cellpadding="5" cellspacing="5">
                        <tr>
                            <td><legend><?php _e('Last FM Username','last-fm'); ?></legend></td>
                            <td><input type="text" name="lastfm_username" class="input" size="30"
                                       value="<?php echo $username; ?>" /></td>
                        </tr>
                        <tr>
                            <td><legend><?php _e('Last FM API Key','last-fm'); ?></legend></td>
                            <td><input type="text" name="lastfm_api_key" class="input" size="30"
                                       value="<?php echo $apikey; ?>" /></td>
                        </tr>
                    </table>
                </div>
                <div style="clear:both; height:1px;">&nbsp;</div>
            </div>
            <input type="submit" name="save" class="button bold" value="<?php _e('Save','last-fm'); ?> &raquo;" />
        </form>
    </div>
    <?php
}

?>