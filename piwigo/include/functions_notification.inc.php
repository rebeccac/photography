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

/*
 * get standard sql where in order to
 * restict an filter caregories and images
 *
 * IMAGE_CATEGORY_TABLE muste named ic in the query
 *
 * @param none
 *
 * @return string sql where
 */
function get_std_sql_where_restrict_filter($prefix_condition, $img_field='ic.image_id', $force_one_condition = false)
{
  return get_sql_condition_FandF
          (
            array
              (
                'forbidden_categories' => 'ic.category_id',
                'visible_categories' => 'ic.category_id',
                'visible_images' => $img_field
              ),
            $prefix_condition,
            $force_one_condition
          );
}

/*
 * Execute custom notification query
 *
 * @param string action ('count' or 'info')
 * @param string type of query ('new_comments', 'unvalidated_comments', 'new_elements', 'updated_categories', 'new_users')
 * @param string start (mysql datetime format)
 * @param string end (mysql datetime format)
 *
 * @return integer for action count
 *         array for info
 */
function custom_notification_query($action, $type, $start, $end)
{
  global $user;

  switch($type)
  {
    case 'new_comments':
      $query = '
  FROM '.COMMENTS_TABLE.' AS c
     , '.IMAGE_CATEGORY_TABLE.' AS ic
  WHERE c.image_id = ic.image_id';
      if (!empty($start))
      {
	$query .= '
    AND c.validation_date > \''.$start.'\'';
      }
      if (!empty($end))
      {
	$query .= '
    AND c.validation_date <= \''.$end.'\'';
      }
      $query .= get_std_sql_where_restrict_filter('AND').'
;';
      break;
    case 'unvalidated_comments':
      $query = '
  FROM '.COMMENTS_TABLE.'
  WHERE 1=1';
      if (!empty($start))
      {
	$query .= ' AND date> \''.$start.'\'';
      }
      if (!empty($end))
      {
	$query .= ' AND date <= \''.$end.'\'';
      }
      $query .= ' AND validated = \'false\'
;';
      break;
    case 'new_elements':
      $query = '
  FROM '.IMAGES_TABLE.' INNER JOIN '.IMAGE_CATEGORY_TABLE.' AS ic ON image_id = id
  WHERE 1=1';
      if (!empty($start))
      {
	$query .= ' AND date_available > \''.$start.'\'';
      }
      if (!empty($end))
      {
	$query .= ' AND date_available <= \''.$end.'\'';
      }
      $query .= get_std_sql_where_restrict_filter('AND', 'id').'
;';
      break;
    case 'updated_categories':
      $query = '
  FROM '.IMAGES_TABLE.' INNER JOIN '.IMAGE_CATEGORY_TABLE.' AS ic ON image_id = id
  WHERE 1=1';
      if (!empty($start))
      {
	$query .= ' AND date_available > \''.$start.'\'';
      }
      if (!empty($end))
      {
	$query .= ' AND date_available <= \''.$end.'\'';
      }
      $query .= get_std_sql_where_restrict_filter('AND', 'id').'
;';
      break;
    case 'new_users':
      $query = '
  FROM '.USER_INFOS_TABLE.'
  WHERE 1=1';
      if (!empty($start))
      {
	$query .= ' AND registration_date > \''.$start.'\'';
      }
      if (!empty($end))
      {
	$query .= ' AND registration_date <= \''.$end.'\'';
      }
      $query .= '
;';
      break;
    default:
      // stop this function and return nothing
      return;
      break;
  }

  switch($action)
  {
    case 'count':
      switch($type)
      {
        case 'new_comments':
          $field_id = 'c.id';
          break;
        case 'unvalidated_comments':
          $field_id = 'id';
          break;
        case 'new_elements':
          $field_id = 'image_id';
          break;
        case 'updated_categories':
          $field_id = 'category_id';
          break;
        case 'new_users':
          $field_id = 'user_id';
          break;
    }
    $query = 'SELECT count(distinct '.$field_id.') as CountId
'.$query;
    list($count) = pwg_db_fetch_row(pwg_query($query));
    return $count;

    break;
    case 'info':
      switch($type)
      {
        case 'new_comments':
          $fields = array('c.id');
          break;
        case 'unvalidated_comments':
          $fields = array('id');
          break;
        case 'new_elements':
          $fields = array('image_id');
          break;
        case 'updated_categories':
          $fields = array('category_id');
          break;
        case 'new_users':
          $fields = array('user_id');
          break;
      }

    $query = 'SELECT distinct '.implode(', ', $fields).'
'.$query;
    $result = pwg_query($query);

    $infos = array();

    while ($row = pwg_db_fetch_assoc($result))
    {
      array_push($infos, $row);
    }

    return $infos;

    break;
  }

  //return is done on previous switch($action)
}

/**
 * new comments between two dates, according to authorized categories
 *
 * @param string start (mysql datetime format)
 * @param string end (mysql datetime format)
 * @param string forbidden categories (comma separated)
 * @return count comment ids
 */
function nb_new_comments($start, $end)
{
  return custom_notification_query('count', 'new_comments', $start, $end);
}

/**
 * new comments between two dates, according to authorized categories
 *
 * @param string start (mysql datetime format)
 * @param string end (mysql datetime format)
 * @param string forbidden categories (comma separated)
 * @return array comment ids
 */
function new_comments($start, $end)
{
  return custom_notification_query('info', 'new_comments', $start, $end);
}

/**
 * unvalidated at a precise date
 *
 * Comments that are registered and not validated yet on a precise date
 *
 * @param string start (mysql datetime format)
 * @param string end (mysql datetime format)
 * @return count comment ids
 */
function nb_unvalidated_comments($start, $end)
{
  return custom_notification_query('count', 'unvalidated_comments', $start, $end);
}


/**
 * new elements between two dates, according to authorized categories
 *
 * @param string start (mysql datetime format)
 * @param string end (mysql datetime format)
 * @param string forbidden categories (comma separated)
 * @return count element ids
 */
function nb_new_elements($start, $end)
{
  return custom_notification_query('count', 'new_elements', $start, $end);
}

/**
 * new elements between two dates, according to authorized categories
 *
 * @param string start (mysql datetime format)
 * @param string end (mysql datetime format)
 * @param string forbidden categories (comma separated)
 * @return array element ids
 */
function new_elements($start, $end)
{
  return custom_notification_query('info', 'new_elements', $start, $end);
}

/**
 * updated categories between two dates, according to authorized categories
 *
 * @param string start (mysql datetime format)
 * @param string end (mysql datetime format)
 * @param string forbidden categories (comma separated)
 * @return count element ids
 */
function nb_updated_categories($start, $end)
{
  return custom_notification_query('count', 'updated_categories', $start, $end);
}

/**
 * updated categories between two dates, according to authorized categories
 *
 * @param string start (mysql datetime format)
 * @param string end (mysql datetime format)
 * @param string forbidden categories (comma separated)
 * @return array element ids
 */
function updated_categories($start, $end)
{
  return custom_notification_query('info', 'updated_categories', $start, $end);
}

/**
 * new registered users between two dates
 *
 * @param string start (mysql datetime format)
 * @param string end (mysql datetime format)
 * @return count user ids
 */
function nb_new_users($start, $end)
{
  return custom_notification_query('count', 'new_users', $start, $end);
}

/**
 * new registered users between two dates
 *
 * @param string start (mysql datetime format)
 * @param string end (mysql datetime format)
 * @return array user ids
 */
function new_users($start, $end)
{
  return custom_notification_query('info', 'new_users', $start, $end);
}

/**
 * There are new between two dates ?
 *
 * Informations : number of new comments, number of new elements, number of
 * updated categories. Administrators are also informed about : number of
 * unvalidated comments, number of new users (TODO : number of unvalidated
 * elements)
 *
 * @param string start date (mysql datetime format)
 * @param string end date (mysql datetime format)
 *
 * @return boolean : true if exist news else false
 */
function news_exists($start, $end)
{
  return (
          (nb_new_comments($start, $end) > 0) or
          (nb_new_elements($start, $end) > 0) or
          (nb_updated_categories($start, $end) > 0) or
          ((is_admin()) and (nb_unvalidated_comments($start, $end) > 0)) or
          ((is_admin()) and (nb_new_users($start, $end) > 0)));
}

/**
 * Formats a news line and adds it to the array (e.g. '5 new elements')
 */
function add_news_line(&$news, $count, $singular_fmt_key, $plural_fmt_key, $url='', $add_url=false)
{
  if ($count > 0)
  {
    $line = l10n_dec($singular_fmt_key, $plural_fmt_key, $count);
    if ($add_url and !empty($url) )
    {
      $line = '<a href="'.$url.'">'.$line.'</a>';
    }
    array_push($news, $line);
  }
}

/**
 * What's new between two dates ?
 *
 * Informations : number of new comments, number of new elements, number of
 * updated categories. Administrators are also informed about : number of
 * unvalidated comments, number of new users (TODO : number of unvalidated
 * elements)
 *
 * @param string start date (mysql datetime format)
 * @param string end date (mysql datetime format)
 * @param bool exclude_img_cats if true, no info about new images/categories
 * @param bool add_url add html A link around news
 *
 * @return array of news
 */
function news($start, $end, $exclude_img_cats=false, $add_url=false)
{
  $news = array();

  if (!$exclude_img_cats)
  {
    add_news_line( $news,
      nb_new_elements($start, $end), '%d new photo', '%d new photos',
      make_index_url(array('section'=>'recent_pics')), $add_url );
  }

  if (!$exclude_img_cats)
  {
    add_news_line( $news,
      nb_updated_categories($start, $end), '%d album updated', '%d albums updated',
      make_index_url(array('section'=>'recent_cats')), $add_url );
  }

  add_news_line( $news,
      nb_new_comments($start, $end), '%d new comment', '%d new comments',
      get_root_url().'comments.php', $add_url );

  if (is_admin())
  {
    add_news_line( $news,
        nb_unvalidated_comments($start, $end), '%d comment to validate', '%d comments to validate',
        get_root_url().'admin.php?page=comments', $add_url );

    add_news_line( $news,
        nb_new_users($start, $end), '%d new user', '%d new users',
        get_root_url().'admin.php?page=user_list', $add_url );
  }

  return $news;
}

/**
 * returns information about recently published elements grouped by post date
 * @param int max_dates maximum returned number of recent dates
 * @param int max_elements maximum returned number of elements per date
 * @param int max_cats maximum returned number of categories per date
 */
function get_recent_post_dates($max_dates, $max_elements, $max_cats)
{
  global $conf, $user;

  $where_sql = get_std_sql_where_restrict_filter('WHERE', 'i.id', true);

  $query = '
SELECT date_available,
      COUNT(DISTINCT id) AS nb_elements,
      COUNT(DISTINCT category_id) AS nb_cats
  FROM '.IMAGES_TABLE.' i INNER JOIN '.IMAGE_CATEGORY_TABLE.' AS ic ON id=image_id
  '.$where_sql.'
  GROUP BY date_available
  ORDER BY date_available DESC
  LIMIT '.$max_dates.'
;';
  $result = pwg_query($query);
  $dates = array();
  while ($row = pwg_db_fetch_assoc($result))
  {
    array_push($dates, $row);
  }

  for ($i=0; $i<count($dates); $i++)
  {
    if ($max_elements>0)
    { // get some thumbnails ...
      $query = '
SELECT DISTINCT i.*
  FROM '.IMAGES_TABLE.' i INNER JOIN '.IMAGE_CATEGORY_TABLE.' AS ic ON id=image_id
  '.$where_sql.'
    AND date_available=\''.$dates[$i]['date_available'].'\'
  ORDER BY '.DB_RANDOM_FUNCTION.'()
  LIMIT '.$max_elements.'
;';
      $dates[$i]['elements'] = array();
      $result = pwg_query($query);
      while ($row = pwg_db_fetch_assoc($result))
      {
        array_push($dates[$i]['elements'], $row);
      }
    }

    if ($max_cats>0)
    {// get some categories ...
      $query = '
SELECT DISTINCT c.uppercats, COUNT(DISTINCT i.id) AS img_count
  FROM '.IMAGES_TABLE.' i INNER JOIN '.IMAGE_CATEGORY_TABLE.' AS ic ON i.id=image_id
    INNER JOIN '.CATEGORIES_TABLE.' c ON c.id=category_id
  '.$where_sql.'
    AND date_available=\''.$dates[$i]['date_available'].'\'
  GROUP BY category_id, c.uppercats
  ORDER BY img_count DESC
  LIMIT '.$max_cats.'
;';
      $dates[$i]['categories'] = array();
      $result = pwg_query($query);
      while ($row = pwg_db_fetch_assoc($result))
      {
        array_push($dates[$i]['categories'], $row);
      }
    }
  }
  return $dates;
}

/*
  Call function get_recent_post_dates but
  the parameters to be passed to the function, as an indexed array.

*/
function get_recent_post_dates_array($args)
{
  return
    get_recent_post_dates
    (
      (empty($args['max_dates']) ? 3 : $args['max_dates']),
      (empty($args['max_elements']) ? 3 : $args['max_elements']),
      (empty($args['max_cats']) ? 3 : $args['max_cats'])
    );
}


/**
 * returns html description about recently published elements grouped by post date
 * @param $date_detail: selected date computed by get_recent_post_dates function
 */
function get_html_description_recent_post_date($date_detail)
{
  global $conf;

  $description = '<ul>';

  $description .=
        '<li>'
        .l10n_dec('%d new photo', '%d new photos', $date_detail['nb_elements'])
        .' ('
        .'<a href="'.make_index_url(array('section'=>'recent_pics')).'">'
          .l10n('Recent photos').'</a>'
        .')'
        .'</li><br>';

  foreach($date_detail['elements'] as $element)
  {
    $tn_src = DerivativeImage::thumb_url($element);
    $description .= '<a href="'.
                    make_picture_url(array(
                        'image_id' => $element['id'],
                        'image_file' => $element['file'],
                      ))
                    .'"><img src="'.$tn_src.'"></a>';
  }
  $description .= '...<br>';

  $description .=
        '<li>'
        .l10n_dec('%d album updated', '%d albums updated',
                  $date_detail['nb_cats'])
        .'</li>';

  $description .= '<ul>';
  foreach($date_detail['categories'] as $cat)
  {
    $description .=
          '<li>'
          .get_cat_display_name_cache($cat['uppercats'])
          .' ('.
          l10n_dec('%d new photo',
                   '%d new photos', $cat['img_count']).')'
          .'</li>';
  }
  $description .= '</ul>';

  $description .= '</ul>';

  return $description;
}

/**
 * returns title about recently published elements grouped by post date
 * @param $date_detail: selected date computed by get_recent_post_dates function
 */
function get_title_recent_post_date($date_detail)
{
  global $lang;

  $date = $date_detail['date_available'];
  $exploded_date = strptime($date, '%Y-%m-%d %H:%M:%S');

  $title = l10n_dec('%d new photo', '%d new photos', $date_detail['nb_elements']);
  $title .= ' ('.$lang['month'][1+$exploded_date['tm_mon']].' '.$exploded_date['tm_mday'].')';

  return $title;
}

if(!function_exists("strptime"))
{
		function strptime($date, $fmt)
		{
			if ($fmt != '%Y-%m-%d %H:%M:%S')
				die('Invalid strptime format '.$fmt);
			list($y,$m,$d,$H,$M,$S) = preg_split('/[-: ]/', $date);
			$res = localtime( mktime($H,$M,$S,$m,$d,$y), true );
			return $res;
		}
 }
?>