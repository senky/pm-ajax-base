<?php
/**
*
* @package phpbb_ajax_base
* @version $Id: ajax_base.php 2010-07-21 14:24:00Z Senky $
* @copyright (c) 2010 Senky
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

define('IN_PHPBB', true);

$phpbb_root_path = './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();

$mode = request_var('mode', '');
$id = request_var('id', 0);

if( $mode == 'stats' )
{
  $l_total_user_s = ($config['num_users'] == 0) ? 'TOTAL_USERS_ZERO' : 'TOTAL_USERS_OTHER';
  $l_total_post_s = ($config['num_posts'] == 0) ? 'TOTAL_POSTS_ZERO' : 'TOTAL_POSTS_OTHER';
  $l_total_topic_s = ($config['num_topics'] == 0) ? 'TOTAL_TOPICS_ZERO' : 'TOTAL_TOPICS_OTHER';

  echo sprintf($user->lang[$l_total_post_s], $config['num_posts']) . ' &bull; ';
  echo sprintf($user->lang[$l_total_topic_s], $config['num_topics']) . ' &bull; ';
  echo sprintf($user->lang[$l_total_user_s], $config['num_users']) . ' &bull; ';
  echo sprintf($user->lang['NEWEST_USER'], get_username_string('full', $config['newest_user_id'], $config['newest_username'], $config['newest_user_colour']));
}
else if( $mode == 'liul' )
{
  $online_users = obtain_users_online(0, 'forum');
  $user_online_strings = obtain_users_online_string($online_users, 0, 'forum');

  echo $user_online_strings['online_userlist'];
}
else if( $mode == 'total' )
{
  $online_users = obtain_users_online(0, 'forum');
  $user_online_strings = obtain_users_online_string($online_users, 0, 'forum');

  echo $user_online_strings['l_online_users'];
}
else if( $mode == 'record' )
{
  echo sprintf($user->lang['RECORD_ONLINE_USERS'], $config['record_online_users'], $user->format_date($config['record_online_date'], false, true));
}
else if( $mode == 'ctime' )
{
  echo sprintf($user->lang['CURRENT_TIME'], $user->format_date(time(), false, true));
}
?>