<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based photo gallery                                    |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2008-2013 Piwigo Team                  http://piwigo.org |
// | Copyright(C) 2003-2008 PhpWebGallery Team    http://phpwebgallery.net |
// | Copyright(C) 2002-2003 Pierrick LE GALL   http://le-gall.net/pierrick |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

// +-----------------------------------------------------------------------+
// |                               functions                               |
// +-----------------------------------------------------------------------+


/**
 * Encodes a string using Q form if required (RFC2045)
 * mail headers MUST contain only US-ASCII characters
 */
function encode_mime_header($str)
{
  $x = preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
  if ($x==0)
  {
    return $str;
  }
  // Replace every high ascii, control =, ? and _ characters
  $str = preg_replace('/([\000-\011\013\014\016-\037\075\077\137\177-\377])/e',
                  "'='.sprintf('%02X', ord('\\1'))", $str);

  // Replace every spaces to _ (more readable than =20)
  $str = str_replace(" ", "_", $str);

  global $lang_info;
  return '=?'.get_pwg_charset().'?Q?'.$str.'?=';
}

/*
 * Returns the name of the mail sender :
 *
 * @return string
 */
function get_mail_sender_name()
{
  global $conf;

  return (empty($conf['mail_sender_name']) ? $conf['gallery_title'] : $conf['mail_sender_name']);
}

/*
 * Returns an array of mail configuration parameters :
 *
 * - mail_options
 * - send_bcc_mail_webmaster
 * - default_email_format
 * - alternative_email_format
 * - use_smtp
 * - smtp_host
 * - smtp_user
 * - smtp_password
 * - boundary_key
 * - email_webmaster
 * - formated_email_webmaster
 *
 * @return array
 */
function get_mail_configuration()
{
  global $conf;

  $conf_mail = array(
    'mail_options' => $conf['mail_options'],
    'send_bcc_mail_webmaster' => $conf['send_bcc_mail_webmaster'],
    'default_email_format' => $conf['default_email_format'],
    'alternative_email_format' => $conf['alternative_email_format'],
    'use_smtp' => !empty($conf['smtp_host']),
    'smtp_host' => $conf['smtp_host'],
    'smtp_user' => $conf['smtp_user'],
    'smtp_password' => $conf['smtp_password'],
    'boundary_key' => generate_key(32),
    );

  // we have webmaster id among user list, what's his email address ?
  $conf_mail['email_webmaster'] = get_webmaster_mail_address();

  // name of the webmaster is the title of the gallery
  $conf_mail['formated_email_webmaster'] =
    format_email(get_mail_sender_name(), $conf_mail['email_webmaster']);

  return $conf_mail;
}

/**
 * Returns an email address with an associated real name
 *
 * @param string name
 * @param string email
 */
function format_email($name, $email)
{
  // Spring cleaning
  $cvt_email = trim(preg_replace('#[\n\r]+#s', '', $email));
  $cvt_name = trim(preg_replace('#[\n\r]+#s', '', $name));

  if ($cvt_name!="")
  {
    $cvt_name = encode_mime_header(
              '"'
              .addcslashes($cvt_name,'"')
              .'"');
    $cvt_name .= ' ';
  }

  if (strpos($cvt_email, '<') === false)
  {
    return $cvt_name.'<'.$cvt_email.'>';
  }
  else
  {
    return $cvt_name.$cvt_email;
  }
}

/**
 * Returns an email address list with minimal email string
 *
 * @param string with email list (email separated by comma)
 */
function get_strict_email_list($email_list)
{
  $result = array();
  $list = explode(',', $email_list);
  foreach ($list as $email)
  {
    if (strpos($email, '<') !== false)
    {
       $email = preg_replace('/.*<(.*)>.*/i', '$1', $email);
    }
    $result[] = trim($email);
  }

  return implode(',', array_unique($result));
}


/**
 * Return an new mail template
 *
 * @param string email_format: mail format, text/html or text/plain
 * @param string theme: theme to use [default get_default_theme()]
 */
function & get_mail_template($email_format, $theme='')
{
  if (empty($theme))
  {
    $theme = get_default_theme();
  }

  $mail_template = new Template(PHPWG_ROOT_PATH.'themes', $theme, 'template/mail/'.$email_format);

  return $mail_template;
}

/**
 * Return string email format (text/html or text/plain)
 *
 * @param string format
 */
function get_str_email_format($is_html)
{
  return ($is_html ? 'text/html' : 'text/plain');
}

/*
 * Switch language to param language
 * All entries are push on language stack
 *
 * @param string language
 */
function switch_lang_to($language)
{
  global $switch_lang, $user, $lang, $lang_info;

  // explanation of switch_lang
  // $switch_lang['language'] contains data of language
  // $switch_lang['stack'] contains stack LIFO
  // $switch_lang['initialisation'] allow to know if it's first call

  // Treatment with current user
  // Language of current user is saved (it's considered OK on firt call)
  if (!isset($switch_lang['initialisation']) and !isset($switch_lang['language'][$user['language']]))
  {
    $switch_lang['initialisation'] = true;
    $switch_lang['language'][$user['language']]['lang_info'] = $lang_info;
    $switch_lang['language'][$user['language']]['lang'] = $lang;
  }

  // Change current infos
  $switch_lang['stack'][] = $user['language'];
  $user['language'] = $language;

  // Load new data if necessary
  if (!isset($switch_lang['language'][$language]))
  {
    // Re-Init language arrays
    $lang_info = array();
    $lang  = array();

    // language files
    load_language('common.lang', '', array('language'=>$language) );
    // No test admin because script is checked admin (user selected no)
    // Translations are in admin file too
    load_language('admin.lang', '', array('language'=>$language) );
    trigger_action('loading_lang');
    load_language('lang', PHPWG_ROOT_PATH.PWG_LOCAL_DIR,
      array('language'=>$language, 'no_fallback'=>true, 'local'=>true)
    );

    $switch_lang['language'][$language]['lang_info'] = $lang_info;
    $switch_lang['language'][$language]['lang'] = $lang;
  }
  else
  {
    $lang_info = $switch_lang['language'][$language]['lang_info'];
    $lang = $switch_lang['language'][$language]['lang'];
  }
}

/*
 * Switch back language pushed with switch_lang_to function
 *
 * @param: none
 */
function switch_lang_back()
{
  global $switch_lang, $user, $lang, $lang_info;

  if (count($switch_lang['stack']) > 0)
  {
    // Get last value
    $language = array_pop($switch_lang['stack']);

    // Change current infos
    if (isset($switch_lang['language'][$language]))
    {
      $lang_info = $switch_lang['language'][$language]['lang_info'];
      $lang = $switch_lang['language'][$language]['lang'];
    }
    $user['language'] = $language;
  }
}

/**
 * Returns email of all administrator
 *
 * @return string
 */
/*
 * send en notification email to all administrators
 * if a administrator is doing action,
 * he's be removed to email list
 *
 * @param:
 *   - keyargs_subject: mail subject on l10n_args format
 *   - keyargs_content: mail content on l10n_args format
 *   - send_technical_details: send user IP and browser
 *
 * @return boolean (Ok or not)
 */
function pwg_mail_notification_admins($keyargs_subject, $keyargs_content, $send_technical_details=true)
{
  global $conf, $user;
  
  // Check arguments
  if (empty($keyargs_subject) or empty($keyargs_content))
  {
    return false;
  }

  $return = true;

  $admins = array();

  $query = '
SELECT
    u.'.$conf['user_fields']['username'].' AS username,
    u.'.$conf['user_fields']['email'].' AS mail_address
  FROM '.USERS_TABLE.' AS u
    JOIN '.USER_INFOS_TABLE.' AS i ON i.user_id =  u.'.$conf['user_fields']['id'].'
  WHERE i.status in (\'webmaster\',  \'admin\')
    AND '.$conf['user_fields']['email'].' IS NOT NULL
    AND i.user_id <> '.$user['id'].'
  ORDER BY username
;';

  $datas = pwg_query($query);
  if (!empty($datas))
  {
    while ($admin = pwg_db_fetch_assoc($datas))
    {
      if (!empty($admin['mail_address']))
      {
        array_push($admins, format_email($admin['username'], $admin['mail_address']));
      }
    }
  }

  if (count($admins) > 0)
  {
    switch_lang_to(get_default_language());

    $content = l10n_args($keyargs_content)."\n";
    if ($send_technical_details)
    {
      $keyargs_content_admin_info = array(
        get_l10n_args('Connected user: %s', stripslashes($user['username'])),
        get_l10n_args('IP: %s', $_SERVER['REMOTE_ADDR']),
        get_l10n_args('Browser: %s', $_SERVER['HTTP_USER_AGENT'])
        );
      
      $content.= "\n".l10n_args($keyargs_content_admin_info)."\n";
    }

    $return = pwg_mail(
      implode(', ', $admins),
      array(
        'subject' => '['.$conf['gallery_title'].'] '.l10n_args($keyargs_subject),
        'content' => $content,
        'content_format' => 'text/plain',
        'email_format' => 'text/plain',
        )
      );
    
    switch_lang_back();
  }

  return $return;
}

/*
 * send en email to user's group
 *
 * @param:
 *   - group_id: mail are sent to group with this Id
 *   - email_format: mail format
 *   - keyargs_subject: mail subject on l10n_args format
 *   - tpl_shortname: short template name without extension
 *   - assign_vars: array used to assign_vars to mail template
 *   - language_selected: send mail only to user with this selected language
 *
 * @return boolean (Ok or not)
 */
function pwg_mail_group(
  $group_id, $email_format, $keyargs_subject,
  $tpl_shortname,
  $assign_vars = array(), $language_selected = '')
{
  // Check arguments
  if
    (
      empty($group_id) or
      empty($email_format) or
      empty($keyargs_subject) or
      empty($tpl_shortname)
    )
  {
    return false;
  }

  global $conf;
  $return = true;

  $query = '
SELECT
  distinct language, theme
FROM
  '.USER_GROUP_TABLE.' as ug
  INNER JOIN '.USERS_TABLE.' as u  ON '.$conf['user_fields']['id'].' = ug.user_id
  INNER JOIN '.USER_INFOS_TABLE.' as ui  ON ui.user_id = ug.user_id
WHERE
        '.$conf['user_fields']['email'].' IS NOT NULL
    AND group_id = '.$group_id;

  if (!empty($language_selected))
  {
    $query .= '
    AND language = \''.$language_selected.'\'';
  }

    $query .= '
;';

  $result = pwg_query($query);

  if (pwg_db_num_rows($result) > 0)
  {
    $list = array();
    while ($row = pwg_db_fetch_assoc($result))
    {
      $list[] = $row;
    }

    foreach ($list as $elem)
    {
      $query = '
SELECT
  u.'.$conf['user_fields']['username'].' as username,
  u.'.$conf['user_fields']['email'].' as mail_address
FROM
  '.USER_GROUP_TABLE.' as ug
  INNER JOIN '.USERS_TABLE.' as u  ON '.$conf['user_fields']['id'].' = ug.user_id
  INNER JOIN '.USER_INFOS_TABLE.' as ui  ON ui.user_id = ug.user_id
WHERE
        '.$conf['user_fields']['email'].' IS NOT NULL
    AND group_id = '.$group_id.'
    AND language = \''.$elem['language'].'\'
    AND theme = \''.$elem['theme'].'\'
;';

      $result = pwg_query($query);

      if (pwg_db_num_rows($result) > 0)
      {
        $Bcc = array();
        while ($row = pwg_db_fetch_assoc($result))
        {
          if (!empty($row['mail_address']))
          {
            array_push($Bcc, format_email(stripslashes($row['username']), $row['mail_address']));
          }
        }

        if (count($Bcc) > 0)
        {
          switch_lang_to($elem['language']);

          $mail_template = get_mail_template($email_format, $elem['theme']);
          $mail_template->set_filename($tpl_shortname, $tpl_shortname.'.tpl');

          $mail_template->assign(
            trigger_event('mail_group_assign_vars', $assign_vars));

          $return = pwg_mail
          (
            '',
            array
            (
              'Bcc' => $Bcc,
              'subject' => l10n_args($keyargs_subject),
              'email_format' => $email_format,
              'content' => $mail_template->parse($tpl_shortname, true),
              'content_format' => $email_format,
              'theme' => $elem['theme']
            )
          ) and $return;

          switch_lang_back();
        }
      }
    }
  }

  return $return;
}

/*
 * sends an email, using Piwigo specific informations
 *
 * @param:
 *   - to: receiver(s) of the mail (list separated by comma).
 *   - args: function params of mail function:
 *       o from: sender [default value webmaster email]
 *       o Cc: array of carbon copy receivers of the mail. [default value empty]
 *       o Bcc: array of blind carbon copy receivers of the mail. [default value empty]
 *       o subject  [default value 'Piwigo']
 *       o content: content of mail    [default value '']
 *       o content_format: format of mail content  [default value 'text/plain']
 *       o email_format: global mail format  [default value $conf_mail['default_email_format']]
 *       o theme: template to use [default get_default_theme()]
 *
 * @return boolean (Ok or not)
 */
function pwg_mail($to, $args = array())
{
  global $conf, $conf_mail, $lang_info, $page;

  if (empty($to) and empty($args['Cc']) and empty($args['Bcc']))
  {
    return true;
  }

  if (!isset($conf_mail))
  {
    $conf_mail = get_mail_configuration();
  }

  if (empty($args['email_format']))
  {
    $args['email_format'] = $conf_mail['default_email_format'];
  }

  // Compute root_path in order have complete path
  set_make_full_url();

  if (empty($args['from']))
  {
    $args['from'] = $conf_mail['formated_email_webmaster'];
  }
  else
  {
    $args['from'] = format_email('', $args['from']);
  }

  if (empty($args['subject']))
  {
    $args['subject'] = 'Piwigo';
  }
  // Spring cleaning
  $cvt_subject = trim(preg_replace('#[\n\r]+#s', '', $args['subject']));
  // Ascii convertion
  $cvt_subject = encode_mime_header($cvt_subject);

  if (!isset($args['content']))
  {
    $args['content'] = '';
  }

  if (empty($args['content_format']))
  {
    $args['content_format'] = 'text/plain';
  }

  if ($conf_mail['send_bcc_mail_webmaster'])
  {
    $args['Bcc'][] = $conf_mail['formated_email_webmaster'];
  }

  if (empty($args['theme']))
  {
    $args['theme'] = get_default_theme();
  }

  $headers = 'From: '.$args['from']."\n";
  $headers.= 'Reply-To: '.$args['from']."\n";

  if (!empty($args['Cc']))
  {
    $headers.= 'Cc: '.implode(',', $args['Cc'])."\n";
  }

  if (!empty($args['Bcc']))
  {
    $headers.= 'Bcc: '.implode(',', $args['Bcc'])."\n";
  }

  $headers.= 'Content-Type: multipart/alternative;'."\n";
  $headers.= '  boundary="---='.$conf_mail['boundary_key'].'";'."\n";
  $headers.= '  reply-type=original'."\n";
  $headers.= 'MIME-Version: 1.0'."\n";
  $headers.= 'X-Mailer: Piwigo Mailer'."\n";

  // List on content-type
  $content_type_list[] = $args['email_format'];
  if (!empty($conf_mail['alternative_email_format']))
  {
    $content_type_list[] = $conf_mail['alternative_email_format'];
  }

  $content = '';

  foreach (array_unique($content_type_list) as $content_type)
  {
    // key compose of indexes witch allow ti cache mail data
    $cache_key = $content_type.'-'.$lang_info['code'].'-'.$args['theme'];

    if (!isset($conf_mail[$cache_key]))
    {
      if (!isset($conf_mail[$cache_key]['theme']))
      {
        $conf_mail[$cache_key]['theme'] = get_mail_template($content_type, $args['theme']);
      }

      $conf_mail[$cache_key]['theme']->set_filename('mail_header', 'header.tpl');
      $conf_mail[$cache_key]['theme']->set_filename('mail_footer', 'footer.tpl');

      $conf_mail[$cache_key]['theme']->assign(
        array(
          //Header
          'BOUNDARY_KEY' => $conf_mail['boundary_key'],
          'CONTENT_TYPE' => $content_type,
          'CONTENT_ENCODING' => get_pwg_charset(),

          // Footer
          'GALLERY_URL' => get_gallery_home_url(),
          'GALLERY_TITLE' =>
            isset($page['gallery_title']) ?
                  $page['gallery_title'] : $conf['gallery_title'],
          'VERSION' => $conf['show_version'] ? PHPWG_VERSION : '',
          'PHPWG_URL' => PHPWG_URL,

          'TITLE_MAIL' => urlencode(l10n('A comment on your site')),
          'MAIL' => get_webmaster_mail_address()
          ));

      if ($content_type == 'text/html')
      {
        if ($conf_mail[$cache_key]['theme']->smarty->template_exists('global-mail-css.tpl'))
        {
          $conf_mail[$cache_key]['theme']->set_filename('css', 'global-mail-css.tpl');
          $conf_mail[$cache_key]['theme']->assign_var_from_handle('GLOBAL_MAIL_CSS', 'css');
        }

        $file = PHPWG_ROOT_PATH.'themes/'.$args['theme'].'/mail-css.tpl';
        if (is_file($file))
        {
          $conf_mail[$cache_key]['theme']->set_filename('css', realpath($file));
          $conf_mail[$cache_key]['theme']->assign_var_from_handle('MAIL_CSS', 'css');
        }
      }

      // what are displayed on the header of each mail ?
      $conf_mail[$cache_key]['header'] =
        $conf_mail[$cache_key]['theme']->parse('mail_header', true);

      // what are displayed on the footer of each mail ?
      $conf_mail[$cache_key]['footer'] =
        $conf_mail[$cache_key]['theme']->parse('mail_footer', true);
    }

    // Header
    $content.= $conf_mail[$cache_key]['header'];

    // Content
    if (($args['content_format'] == 'text/plain') and ($content_type == 'text/html'))
    {
      $content.= '<p>'.
                  nl2br(
                    preg_replace("/(http:\/\/)([^\s,]*)/i",
                                 "<a href='$1$2' class='thumblnk'>$1$2</a>",
                                 htmlspecialchars($args['content']))).
                  '</p>';
    }
    else if (($args['content_format'] == 'text/html') and ($content_type == 'text/plain'))
    {
      // convert html text to plain text
      $content.= strip_tags($args['content']);
    }
    else
    {
      $content.= $args['content'];
    }

    // Footer
    $content.= $conf_mail[$cache_key]['footer'];

  // Close boundary
  $content.= "\n".'-----='.$conf_mail['boundary_key'].'--'."\n";
  }

  //~ // Close boundary
  //~ $content.= "\n".'-----='.$conf_mail['boundary_key'].'--'."\n";

   // Undo Compute root_path in order have complete path
  unset_make_full_url();

  return
    trigger_event('send_mail',
      false, /* Result */
      trigger_event('send_mail_to', get_strict_email_list($to)),
      trigger_event('send_mail_subject', $cvt_subject),
      trigger_event('send_mail_content', $content),
      trigger_event('send_mail_headers', $headers),
      $args
    );
}

/*
 * pwg sendmail
 *
 * @param:
 *   - result of other sendmail
 *   - to: Receiver or receiver(s) of the mail.
 *   - subject  [default value 'Piwigo']
 *   - content: content of mail
 *   - headers: headers of mail
 *
 * @return boolean (Ok or not)
 */
function pwg_send_mail($result, $to, $subject, $content, $headers)
{
  if (!$result)
  {
    global $conf_mail;

    if ($conf_mail['use_smtp'])
    {
      include_once( PHPWG_ROOT_PATH.'include/class_smtp_mail.inc.php' );
      $smtp_mail = new smtp_mail(
        $conf_mail['smtp_host'], $conf_mail['smtp_user'], $conf_mail['smtp_password'],
        $conf_mail['email_webmaster']);
      return $smtp_mail->mail($to, $subject, $content, $headers);
    }
    else
    {
      if ($conf_mail['mail_options'])
      {
        $options = '-f '.$conf_mail['email_webmaster'];
        return mail($to, $subject, $content, $headers, $options);
      }
      else
      {
        return mail($to, $subject, $content, $headers);
      }
    }
  }
  else
  {
    return $result;
  }
}

function move_ccs_rules_to_body($content)
{
  // We search all css rules in style tags
  preg_match('#<style>(.*?)</style>#s', $content, $matches);

  if (!empty($matches[1]))
  {
    preg_match_all('#([^\n]*?)\{(.*?)\}#s', $matches[1], $matches);

    $selectors = array();
    $unknow_selectors = '';

    foreach ($matches[1] as $key => $value)
    {
      $selects = explode(',', $value);
      $style = trim($matches[2][$key], ' ;');

      foreach($selects as $select)
      {
        $select = trim($select);
        $selectors[$select][] = $style;
      }
    }

    foreach ($selectors as $selector => $style)
    {
      if (!preg_match('/^(#|\.|)([A-Za-z0-9_-]*)$/', $selector, $matches))
      {
        $unknow_selectors .= $selector.' {'.implode(";\n", $style).";}\n";
      }
      else switch ($matches[1])
      {
        case '#':
          $content = preg_replace('|id="'.$matches[2].'"|', 'id="'.$matches[2].'" style="'.implode(";\n", $style).";\"\n", $content);
          break;
        case '.':
          $content = preg_replace('|class="'.$matches[2].'"|', 'class="'.$matches[2].'" style="'.implode(";\n", $style).";\"\n", $content);
          break;
        default:
          $content = preg_replace('#<'.$matches[2].'( |>)#', '<'.$matches[2].' style="'.implode(";\n", $style).";\"\n$1", $content);
          break;
      }
    }

    // Keep unknow tags in page head
    if (!empty($unknow_selectors))
    {
      $content = preg_replace('#<style>.*?</style>#s', "<style type=\"text/css\">\n$unknow_selectors</style>", $content);
    }
    else
    {
      $content = preg_replace('#<style>.*?</style>#s', '', $content);
    }
  }
  return $content;
}

/*Testing block*/
function pwg_send_mail_test($result, $to, $subject, $content, $headers, $args)
{
    global $conf, $user, $lang_info;
    $dir = PHPWG_ROOT_PATH.$conf['data_location'].'tmp';
    if ( mkgetdir( $dir,  MKGETDIR_DEFAULT&~MKGETDIR_DIE_ON_ERROR) )
    {
      $filename = $dir.'/mail.'.stripslashes($user['username']).'.'.$lang_info['code'].'.'.$args['theme'].'-'.date('YmdHis');
      if ($args['content_format'] == 'text/plain')
      {
        $filename .= '.txt';
      }
      else
      {
        $filename .= '.html';
      }
      $file = fopen($filename, 'w+');
      fwrite($file, $to ."\n");
      fwrite($file, $subject ."\n");
      fwrite($file, $headers);
      fwrite($file, $content);
      fclose($file);
    }
    return $result;
}
if ($conf['debug_mail'])
  add_event_handler('send_mail', 'pwg_send_mail_test', EVENT_HANDLER_PRIORITY_NEUTRAL+10, 6);


add_event_handler('send_mail', 'pwg_send_mail', EVENT_HANDLER_PRIORITY_NEUTRAL, 5);
add_event_handler('send_mail_content', 'move_ccs_rules_to_body');
trigger_action('functions_mail_included');

?>
