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

switch($mode)
{
	case 'who_is_online':

		$online_users = obtain_users_online(0, 'forum');
  	$user_online_strings = obtain_users_online_string($online_users, 0, 'forum');

		$template->assign_vars(array(
			'TOTAL_USERS_ONLINE'	=> $user_online_strings['l_online_users'],
			'RECORD_USERS'		=> $user->lang('RECORD_ONLINE_USERS', $config['record_online_users'], $user->format_date($config['record_online_date'], false, true)),
			'LOGGED_IN_USER_LIST'	=> $user_online_strings['online_userlist'],
		));

		/*
		 * output
		 */
		page_header('');

		$template->set_filenames(array(
			'body' => 'ajax_base/who_is_online.html')
		);

		page_footer();

	break;

	case 'statistics':

	  $l_total_user_s = ($config['num_users'] == 0) ? 'TOTAL_USERS_ZERO' : 'TOTAL_USERS_OTHER';
	  $l_total_post_s = ($config['num_posts'] == 0) ? 'TOTAL_POSTS_ZERO' : 'TOTAL_POSTS_OTHER';
	  $l_total_topic_s = ($config['num_topics'] == 0) ? 'TOTAL_TOPICS_ZERO' : 'TOTAL_TOPICS_OTHER';

	  $template->assign_vars(array(
			'TOTAL_POSTS'	=> $user->lang($l_total_post_s, $config['num_posts']),
			'TOTAL_TOPICS'		=> $user->lang($l_total_topic_s, $config['num_topics']),
			'TOTAL_USERS'	=> $user->lang($l_total_user_s, $config['num_users']),
			'NEWEST_USER'	=> $user->lang('NEWEST_USER', get_username_string('full', $config['newest_user_id'], $config['newest_username'], $config['newest_user_colour'])),
		));

		/*
		 * output
		 */
		page_header('');

		$template->set_filenames(array(
			'body' => 'ajax_base/statistics.html')
		);

		page_footer();

	break;

	case 'post_preview':

		include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
		include($phpbb_root_path . 'includes/bbcode.' . $phpEx);
		include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
		include($phpbb_root_path . 'includes/message_parser.' . $phpEx);

		$user->add_lang(array('posting', 'viewtopic'));

		$error = array();
		$min_msg_length_informed = false;

		/*
		 * grab all data sent
		 */
		$forum_id = request_var('f', 0);

		// basic stuff
		$subject = utf8_normalize_nfc(request_var('subject', '', true));
		$message = utf8_normalize_nfc(request_var('message', '', true));

		// options
		$bbcode = (request_var('disable_bbcode', '') === 'true') ? false : true;
		$smilies = (request_var('disable_smilies', '') === 'true') ? false : true;
		$magic_url = (request_var('disable_magic_url', '') === 'true') ? false : true;
		$signature = (request_var('signature', '') === 'true') ? true : false;

		// attachments
		$attachments = request_var('attachment_data', array(0 => array(
			'attach_id'	=> '',
			'is_orphan'	=> '',
			'real_filename'	=> '',
			'attach_comment'	=> ''
		)), true);

		// poll
		$poll = array(
			'poll_delete'	=> (request_var('poll_delete', '') === 'true') ? true : false,
			'poll_title'	=> utf8_normalize_nfc(request_var('poll_title', '', true)),
			'poll_option_text'	=> utf8_normalize_nfc(request_var('poll_option_text', '', true)),
			'poll_max_options'	=> request_var('poll_max_options', 0),
			'poll_length'	=> request_var('poll_length', 0),
			'poll_vote_change'	=> (request_var('poll_vote_change', '') === 'true') ? true : false,
		);


		/*
		 * calculations, more assignments, ...
		 */
		$message_length = utf8_strlen($message);

		$poll['poll_options'] = ($poll['poll_option_text'] == '') ? array() : explode("\n", $poll['poll_option_text']);


		/*
		 * errors
		 * $message errors are displayed via $message_parser->warn_msg
		 * $poll errors are displayed via $message_parser->warn_msg
		 */
		// Minimum subject length check.
		if (utf8_clean_string($subject) === '')
		{
			$error[] = $user->lang['EMPTY_SUBJECT'];
		}


		/*
		 * parse message
		 */
		$message_parser = new parse_message($message);
		$message_parser->parse($bbcode, $magic_url, $smilies);
		$message = $message_parser->format_display($bbcode, $magic_url, $smilies, false);
		// no unset($message_parser), because it is used in polls

		// message parser errors
		if (sizeof($message_parser->warn_msg))
		{
			$error[] = implode('<br />', $message_parser->warn_msg);
		}


		/*
		 * parse signature, if needed
		 */
		if ($signature && $config['allow_sig'] && $user->data['user_sig'] && $auth->acl_get('f_sigs', $forum_id))
		{
			$parse_sig = new parse_message($user->data['user_sig']);
			$parse_sig->bbcode_uid = $user->data['user_sig_bbcode_uid'];
			$parse_sig->bbcode_bitfield = $user->data['user_sig_bbcode_bitfield'];

			$parse_sig->format_display($config['allow_sig_bbcode'], $config['allow_sig_links'], $config['allow_sig_smilies']);

			$sig = $parse_sig->message;

			unset($parse_sig);
		}
		else
		{
			$sig = false;
		}

		/*
		 * parse poll
		 */
		if($poll['poll_option_text'])
		{
			$parse_poll = new parse_message($poll['poll_title']);

			$poll = array(
				'poll_title'		=> $poll['poll_title'],
				'poll_length'		=> $poll['poll_length'],
				'poll_max_options'	=> $poll['poll_max_options'],
				'poll_option_text'	=> $poll['poll_option_text'],
				'poll_start'		=> time(),
				'poll_last_vote'	=> 0,
				'poll_vote_change'	=> true,
				'enable_bbcode'		=> $bbcode,
				'enable_urls'		=> $magic_url,
				'enable_smilies'	=> $smilies,
				'img_status'		=> ($bbcode && $auth->acl_get('f_img', $forum_id)) ? true : false
			);
			$parse_poll->parse_poll($poll);

			// poll parser errors
			if (sizeof($parse_poll->warn_msg))
			{
				$error[] = implode('<br />', $parse_poll->warn_msg);
			}

			if ($poll['poll_length'])
			{
				$poll_end = ($poll['poll_length'] * 86400) + time();
			}

			$template->assign_vars(array(
				'S_HAS_POLL_OPTIONS'	=> (sizeof($poll['poll_options'])),
				'S_IS_MULTI_CHOICE'		=> ($poll['poll_max_options'] > 1) ? true : false,

				'POLL_QUESTION'		=> $parse_poll->message,

				'L_POLL_LENGTH'		=> ($poll['poll_length']) ? $user->lang('POLL_RUN_TILL', $user->format_date($poll_end)) : '',
				'L_MAX_VOTES'		=> ($poll['poll_max_options'] == 1) ? $user->lang['MAX_OPTION_SELECT'] : $user->lang('MAX_OPTIONS_SELECT', $poll['poll_max_options']))
			);

			$parse_poll->message = implode("\n", $poll['poll_options']);
			$parse_poll->format_display($bbcode, $magic_url, $smilies);
			$preview_poll_options = explode('<br />', $parse_poll->message);
			unset($parse_poll);

			foreach ($preview_poll_options as $key => $option)
			{
				$template->assign_block_vars('poll_option', array(
					'POLL_OPTION_CAPTION'	=> $option,
					'POLL_OPTION_ID'		=> $key + 1)
				);
			}
			unset($preview_poll_options);
		}


		/*
		 * parse attachments
		 */
		if (sizeof($message_parser->attachment_data))
		{
			$template->assign_var('S_HAS_ATTACHMENTS', true);

			$update_count = array();
			$attachment_data = $message_parser->attachment_data;

			parse_attachments($forum_id, $preview_message, $attachment_data, $update_count, true);

			foreach ($attachment_data as $i => $attachment)
			{
				$template->assign_block_vars('attachment', array(
					'DISPLAY_ATTACHMENT'	=> $attachment)
				);
			}
			unset($attachment_data);
		}

		/*
		 * display!
		 */
		if (sizeof($error))
		{
			// seems to be the best HTTP code
			header('HTTP/1.1 412 Precondition Failed');
			die( implode('<br />', $error) );
		}
		else
		{
			/*
			 * assign template variables
			 */
			$template->assign_vars(array(
				'PREVIEW_SUBJECT'	=> censor_text($subject),
				'PREVIEW_MESSAGE'	=> $message,
				'PREVIEW_SIGNATURE'		=> $sig,
			));

			/*
			 * output
			 */
			page_header('');

			$template->set_filenames(array(
				'body' => 'ajax_base/preview.html')
			);

			page_footer();
		}

	break;
}
