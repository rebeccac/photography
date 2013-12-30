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

/**** IMPLEMENTATION OF WEB SERVICE METHODS ***********************************/

/**
 * Event handler for method invocation security check. Should return a PwgError
 * if the preconditions are not satifsied for method invocation.
 */
function ws_isInvokeAllowed($res, $methodName, $params)
{
  global $conf;

  if ( strpos($methodName,'reflection.')===0 )
  { // OK for reflection
    return $res;
  }

  if ( !is_autorize_status(ACCESS_GUEST) and
      strpos($methodName,'pwg.session.')!==0 )
  {
    return new PwgError(401, 'Access denied');
  }

  return $res;
}

/**
 * returns a "standard" (for our web service) array of sql where clauses that
 * filters the images (images table only)
 */
function ws_std_image_sql_filter( $params, $tbl_name='' )
{
  $clauses = array();
  if ( is_numeric($params['f_min_rate']) )
  {
    $clauses[] = $tbl_name.'rating_score>='.$params['f_min_rate'];
  }
  if ( is_numeric($params['f_max_rate']) )
  {
    $clauses[] = $tbl_name.'rating_score<='.$params['f_max_rate'];
  }
  if ( is_numeric($params['f_min_hit']) )
  {
    $clauses[] = $tbl_name.'hit>='.$params['f_min_hit'];
  }
  if ( is_numeric($params['f_max_hit']) )
  {
    $clauses[] = $tbl_name.'hit<='.$params['f_max_hit'];
  }
  if ( isset($params['f_min_date_available']) )
  {
    $clauses[] = $tbl_name."date_available>='".$params['f_min_date_available']."'";
  }
  if ( isset($params['f_max_date_available']) )
  {
    $clauses[] = $tbl_name."date_available<'".$params['f_max_date_available']."'";
  }
  if ( isset($params['f_min_date_created']) )
  {
    $clauses[] = $tbl_name."date_creation>='".$params['f_min_date_created']."'";
  }
  if ( isset($params['f_max_date_created']) )
  {
    $clauses[] = $tbl_name."date_creation<'".$params['f_max_date_created']."'";
  }
  if ( is_numeric($params['f_min_ratio']) )
  {
    $clauses[] = $tbl_name.'width/'.$tbl_name.'height>='.$params['f_min_ratio'];
  }
  if ( is_numeric($params['f_max_ratio']) )
  {
    $clauses[] = $tbl_name.'width/'.$tbl_name.'height<='.$params['f_max_ratio'];
  }
  if (is_numeric($params['f_max_level']) )
  {
    $clauses[] = $tbl_name.'level <= '.$params['f_max_level'];
  }
  return $clauses;
}

/**
 * returns a "standard" (for our web service) ORDER BY sql clause for images
 */
function ws_std_image_sql_order( $params, $tbl_name='' )
{
  $ret = '';
  if ( empty($params['order']) )
  {
    return $ret;
  }
  $matches = array();
  preg_match_all('/([a-z_]+) *(?:(asc|desc)(?:ending)?)? *(?:, *|$)/i',
    $params['order'], $matches);
  for ($i=0; $i<count($matches[1]); $i++)
  {
    switch ($matches[1][$i])
    {
      case 'date_created':
        $matches[1][$i] = 'date_creation'; break;
      case 'date_posted':
        $matches[1][$i] = 'date_available'; break;
      case 'rand': case 'random':
        $matches[1][$i] = DB_RANDOM_FUNCTION.'()'; break;
    }
    $sortable_fields = array('id', 'file', 'name', 'hit', 'rating_score',
      'date_creation', 'date_available', DB_RANDOM_FUNCTION.'()' );
    if ( in_array($matches[1][$i], $sortable_fields) )
    {
      if (!empty($ret))
        $ret .= ', ';
      if ($matches[1][$i] != DB_RANDOM_FUNCTION.'()' )
      {
        $ret .= $tbl_name;
      }
      $ret .= $matches[1][$i];
      $ret .= ' '.$matches[2][$i];
    }
  }
  return $ret;
}

/**
 * returns an array map of urls (thumb/element) for image_row - to be returned
 * in a standard way by different web service methods
 */
function ws_std_get_urls($image_row)
{
  $ret = array();

  $ret['page_url'] = make_picture_url( array(
            'image_id' => $image_row['id'],
            'image_file' => $image_row['file'],
          )
        );

  $src_image = new SrcImage($image_row);

  if ( $src_image->is_original() )
  {// we have a photo
    global $user;
    if ($user['enabled_high'])
    {
      $ret['element_url'] = $src_image->get_url();
    }
  }
  else
  {
    $ret['element_url'] = get_element_url($image_row);
  }

  $derivatives = DerivativeImage::get_all($src_image);
  $derivatives_arr = array();
  foreach($derivatives as $type=>$derivative)
  {
    $size = $derivative->get_size();
    $size != null or $size=array(null,null);
    $derivatives_arr[$type] = array('url' => $derivative->get_url(), 'width'=>$size[0], 'height'=>$size[1] );
  }
  $ret['derivatives'] = $derivatives_arr;;
  return $ret;
}

/**
 * returns an array of image attributes that are to be encoded as xml attributes
 * instead of xml elements
 */
function ws_std_get_image_xml_attributes()
{
  return array(
    'id','element_url', 'page_url', 'file','width','height','hit','date_available','date_creation'
    );
}

function ws_getMissingDerivatives($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(403, 'Forbidden');
  }

  if ( empty($params['types']) )
  {
    $types = array_keys(ImageStdParams::get_defined_type_map());
  }
  else
  {
    $types = array_intersect(array_keys(ImageStdParams::get_defined_type_map()), $params['types']);
    if (count($types)==0)
    {
      return new PwgError(WS_ERR_INVALID_PARAM, "Invalid types");
    }
  }

  if ( ($max_urls = intval($params['max_urls'])) <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid max_urls");
  }

  list($max_id, $image_count) = pwg_db_fetch_row( pwg_query('SELECT MAX(id)+1, COUNT(*) FROM '.IMAGES_TABLE) );

  if (0 == $image_count)
  {
    return array();
  }

  $start_id = intval($params['prev_page']);
  if ($start_id<=0)
  {
    $start_id = $max_id;
  }

  $uid = '&b='.time();
  global $conf;
  $conf['question_mark_in_urls'] = $conf['php_extension_in_urls'] = true;
  $conf['derivative_url_style']=2; //script

  $qlimit = min(5000, ceil(max($image_count/500, $max_urls/count($types))));
  $where_clauses = ws_std_image_sql_filter( $params, '' );
  $where_clauses[] = 'id<start_id';
  if ( !empty($params['ids']) )
  {
    $where_clauses[] = 'id IN ('.implode(',',$params['ids']).')';
  }

  $query_model = 'SELECT id, path, representative_ext, width,height,rotation
  FROM '.IMAGES_TABLE.'
  WHERE '.implode(' AND ', $where_clauses).'
  ORDER BY id DESC
  LIMIT '.$qlimit;

  $urls=array();
  do
  {
    $result = pwg_query( str_replace('start_id', $start_id, $query_model));
    $is_last = pwg_db_num_rows($result) < $qlimit;
    while ($row=pwg_db_fetch_assoc($result))
    {
      $start_id = $row['id'];
      $src_image = new SrcImage($row);
      if ($src_image->is_mimetype())
        continue;
      foreach($types as $type)
      {
        $derivative = new DerivativeImage($type, $src_image);
        if ($type != $derivative->get_type())
          continue;
        if (@filemtime($derivative->get_path())===false)
        {
          $urls[] = $derivative->get_url().$uid;
        }
      }
      if (count($urls)>=$max_urls && !$is_last)
        break;
    }
    if ($is_last)
    {
      $start_id = 0;
    }
  }while (count($urls)<$max_urls && $start_id);

  $ret = array();
  if ($start_id)
  {
    $ret['next_page']=$start_id;
  }
  $ret['urls']=$urls;
  return $ret;
}

/**
 * returns PWG version (web service method)
 */
function ws_getVersion($params, &$service)
{
  global $conf;
  if ($conf['show_version'] or is_admin() )
    return PHPWG_VERSION;
  else
    return new PwgError(403, 'Forbidden');
}

/**
 * returns general informations (web service method)
 */
function ws_getInfos($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(403, 'Forbidden');
  }

  $infos['version'] = PHPWG_VERSION;

  $query = 'SELECT COUNT(*) FROM '.IMAGES_TABLE.';';
  list($infos['nb_elements']) = pwg_db_fetch_row(pwg_query($query));

  $query = 'SELECT COUNT(*) FROM '.CATEGORIES_TABLE.';';
  list($infos['nb_categories']) = pwg_db_fetch_row(pwg_query($query));

  $query = 'SELECT COUNT(*) FROM '.CATEGORIES_TABLE.' WHERE dir IS NULL;';
  list($infos['nb_virtual']) = pwg_db_fetch_row(pwg_query($query));

  $query = 'SELECT COUNT(*) FROM '.CATEGORIES_TABLE.' WHERE dir IS NOT NULL;';
  list($infos['nb_physical']) = pwg_db_fetch_row(pwg_query($query));

  $query = 'SELECT COUNT(*) FROM '.IMAGE_CATEGORY_TABLE.';';
  list($infos['nb_image_category']) = pwg_db_fetch_row(pwg_query($query));

  $query = 'SELECT COUNT(*) FROM '.TAGS_TABLE.';';
  list($infos['nb_tags']) = pwg_db_fetch_row(pwg_query($query));

  $query = 'SELECT COUNT(*) FROM '.IMAGE_TAG_TABLE.';';
  list($infos['nb_image_tag']) = pwg_db_fetch_row(pwg_query($query));

  $query = 'SELECT COUNT(*) FROM '.USERS_TABLE.';';
  list($infos['nb_users']) = pwg_db_fetch_row(pwg_query($query));

  $query = 'SELECT COUNT(*) FROM '.GROUPS_TABLE.';';
  list($infos['nb_groups']) = pwg_db_fetch_row(pwg_query($query));

  $query = 'SELECT COUNT(*) FROM '.COMMENTS_TABLE.';';
  list($infos['nb_comments']) = pwg_db_fetch_row(pwg_query($query));

  // first element
  if ($infos['nb_elements'] > 0)
  {
    $query = 'SELECT MIN(date_available) FROM '.IMAGES_TABLE.';';
    list($infos['first_date']) = pwg_db_fetch_row(pwg_query($query));
  }

  // unvalidated comments
  if ($infos['nb_comments'] > 0)
  {
    $query = 'SELECT COUNT(*) FROM '.COMMENTS_TABLE.' WHERE validated=\'false\';';
    list($infos['nb_unvalidated_comments']) = pwg_db_fetch_row(pwg_query($query));
  }

  foreach ($infos as $name => $value)
  {
    $output[] = array(
      'name' => $name,
      'value' => $value,
    );
  }

  return array('infos' => new PwgNamedArray($output, 'item'));
}

function ws_caddie_add($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }
  $params['image_id'] = array_map( 'intval',$params['image_id'] );
  if ( empty($params['image_id']) )
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid image_id");
  }
  global $user;
  $query = '
SELECT id
  FROM '.IMAGES_TABLE.' LEFT JOIN '.CADDIE_TABLE.' ON id=element_id AND user_id='.$user['id'].'
  WHERE id IN ('.implode(',',$params['image_id']).')
    AND element_id IS NULL';
  $datas = array();
  foreach ( array_from_query($query, 'id') as $id )
  {
    array_push($datas, array('element_id'=>$id, 'user_id'=>$user['id']) );
  }
  if (count($datas))
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    mass_inserts(CADDIE_TABLE, array('element_id','user_id'), $datas);
  }
  return count($datas);
}

/**
 * returns images per category (web service method)
 */
function ws_categories_getImages($params, &$service)
{
  global $user, $conf;

  $images = array();

  //------------------------------------------------- get the related categories
  $where_clauses = array();
  foreach($params['cat_id'] as $cat_id)
  {
    $cat_id = (int)$cat_id;
    if ($cat_id<=0)
      continue;
    if ($params['recursive'])
    {
      $where_clauses[] = 'uppercats '.DB_REGEX_OPERATOR.' \'(^|,)'.$cat_id.'(,|$)\'';
    }
    else
    {
      $where_clauses[] = 'id='.$cat_id;
    }
  }
  if (!empty($where_clauses))
  {
    $where_clauses = array( '('.
    implode('
    OR ', $where_clauses) . ')'
      );
  }
  $where_clauses[] = get_sql_condition_FandF(
        array('forbidden_categories' => 'id'),
        NULL, true
      );

  $query = '
SELECT id, name, permalink, image_order
  FROM '.CATEGORIES_TABLE.'
  WHERE '. implode('
    AND ', $where_clauses);
  $result = pwg_query($query);
  $cats = array();
  while ($row = pwg_db_fetch_assoc($result))
  {
    $row['id'] = (int)$row['id'];
    $cats[ $row['id'] ] = $row;
  }

  //-------------------------------------------------------- get the images
  if ( !empty($cats) )
  {
    $where_clauses = ws_std_image_sql_filter( $params, 'i.' );
    $where_clauses[] = 'category_id IN ('
      .implode(',', array_keys($cats) )
      .')';
    $where_clauses[] = get_sql_condition_FandF( array(
          'visible_images' => 'i.id'
        ), null, true
      );

    $order_by = ws_std_image_sql_order($params, 'i.');
    if ( empty($order_by)
          and count($params['cat_id'])==1
          and isset($cats[ $params['cat_id'][0] ]['image_order'])
        )
    {
      $order_by = $cats[ $params['cat_id'][0] ]['image_order'];
    }
    $order_by = empty($order_by) ? $conf['order_by'] : 'ORDER BY '.$order_by;

    $query = '
SELECT i.*, GROUP_CONCAT(category_id) AS cat_ids
  FROM '.IMAGES_TABLE.' i
    INNER JOIN '.IMAGE_CATEGORY_TABLE.' ON i.id=image_id
  WHERE '. implode('
    AND ', $where_clauses).'
GROUP BY i.id
'.$order_by.'
LIMIT '.(int)$params['per_page'].' OFFSET '.(int)($params['per_page']*$params['page']);

    $result = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result))
    {
      $image = array();
      foreach ( array('id', 'width', 'height', 'hit') as $k )
      {
        if (isset($row[$k]))
        {
          $image[$k] = (int)$row[$k];
        }
      }
      foreach ( array('file', 'name', 'comment', 'date_creation', 'date_available') as $k )
      {
        $image[$k] = $row[$k];
      }
      $image = array_merge( $image, ws_std_get_urls($row) );

      $image_cats = array();
      foreach ( explode(',', $row['cat_ids']) as $cat_id )
      {
        $url = make_index_url(
                array(
                  'category' => $cats[$cat_id],
                  )
                );
        $page_url = make_picture_url(
                array(
                  'category' => $cats[$cat_id],
                  'image_id' => $row['id'],
                  'image_file' => $row['file'],
                  )
                );
        array_push( $image_cats,  array(
              WS_XML_ATTRIBUTES => array (
                  'id' => (int)$cat_id,
                  'url' => $url,
                  'page_url' => $page_url,
                )
            )
          );
      }

      $image['categories'] = new PwgNamedArray(
            $image_cats,'category', array('id','url','page_url')
          );
      array_push($images, $image);
    }
  }

  return array( 'images' =>
    array (
      WS_XML_ATTRIBUTES =>
        array(
            'page' => $params['page'],
            'per_page' => $params['per_page'],
            'count' => count($images)
          ),
       WS_XML_CONTENT => new PwgNamedArray($images, 'image',
          ws_std_get_image_xml_attributes() )
      )
    );
}


/**
 * create a tree from a flat list of categories, no recursivity for high speed
 */
function categories_flatlist_to_tree($categories)
{
  $tree = array();
  $key_of_cat = array();

  foreach ($categories as $key => &$node)
  {
    $key_of_cat[$node['id']] = $key;

    if (!isset($node['id_uppercat']))
    {
      $tree[$key] = &$node;
    }
    else
    {
      if (!isset($categories[ $key_of_cat[ $node['id_uppercat'] ] ]['sub_categories']))
      {
        $categories[ $key_of_cat[ $node['id_uppercat'] ] ]['sub_categories'] = array();
      }

      $categories[ $key_of_cat[ $node['id_uppercat'] ] ]['sub_categories'][$key] = &$node;
    }
  }

  return $tree;
}

/**
 * returns a list of categories (web service method)
 */
function ws_categories_getList($params, &$service)
{
  global $user,$conf;

  if ($params['tree_output'])
  {
    if (!isset($_GET['format']) or !in_array($_GET['format'], array('php', 'json')))
    {
      // the algorithm used to build a tree from a flat list of categories
      // keeps original array keys, which is not compatible with
      // PwgNamedArray.
      //
      // PwgNamedArray is useful to define which data is an attribute and
      // which is an element in the XML output. The "hierarchy" output is
      // only compatible with json/php output.

      return new PwgError(405, "The tree_output option is only compatible with json/php output formats");
    }
  }

  $where = array('1=1');
  $join_type = 'INNER';
  $join_user = $user['id'];

  if (!$params['recursive'])
  {
    if ($params['cat_id']>0)
      $where[] = '(id_uppercat='.(int)($params['cat_id']).'
    OR id='.(int)($params['cat_id']).')';
    else
      $where[] = 'id_uppercat IS NULL';
  }
  else if ($params['cat_id']>0)
  {
    $where[] = 'uppercats '.DB_REGEX_OPERATOR.' \'(^|,)'.
      (int)($params['cat_id'])
      .'(,|$)\'';
  }

  if ($params['public'])
  {
    $where[] = 'status = "public"';
    $where[] = 'visible = "true"';

    $join_user = $conf['guest_id'];
  }
  elseif (is_admin())
  {
    // in this very specific case, we don't want to hide empty
    // categories. Function calculate_permissions will only return
    // categories that are either locked or private and not permitted
    //
    // calculate_permissions does not consider empty categories as forbidden
    $forbidden_categories = calculate_permissions($user['id'], $user['status']);
    $where[]= 'id NOT IN ('.$forbidden_categories.')';
    $join_type = 'LEFT';
  }

  $query = '
SELECT id, name, permalink, uppercats, global_rank, id_uppercat,
    comment,
    nb_images, count_images AS total_nb_images,
    representative_picture_id, user_representative_picture_id, count_images, count_categories,
    date_last, max_date_last, count_categories AS nb_categories
  FROM '.CATEGORIES_TABLE.'
   '.$join_type.' JOIN '.USER_CACHE_CATEGORIES_TABLE.' ON id=cat_id AND user_id='.$join_user.'
  WHERE '. implode('
    AND ', $where);

  $result = pwg_query($query);

  // management of the album thumbnail -- starts here
  $image_ids = array();
  $categories = array();
  $user_representative_updates_for = array();
  // management of the album thumbnail -- stops here

  $cats = array();
  while ($row = pwg_db_fetch_assoc($result))
  {
    $row['url'] = make_index_url(
        array(
          'category' => $row
          )
      );
    foreach( array('id','nb_images','total_nb_images','nb_categories') as $key)
    {
      $row[$key] = (int)$row[$key];
    }

    if ($params['fullname'])
    {
      $row['name'] = strip_tags(get_cat_display_name_cache($row['uppercats'], null, false));
    }
    else
    {
      $row['name'] = strip_tags(
        trigger_event(
          'render_category_name',
          $row['name'],
          'ws_categories_getList'
          )
        );
    }

    $row['comment'] = strip_tags(
      trigger_event(
        'render_category_description',
        $row['comment'],
        'ws_categories_getList'
        )
      );

    // management of the album thumbnail -- starts here
    //
    // on branch 2.3, the algorithm is duplicated from
    // include/category_cats, but we should use a common code for Piwigo 2.4
    //
    // warning : if the API method is called with $params['public'], the
    // album thumbnail may be not accurate. The thumbnail can be viewed by
    // the connected user, but maybe not by the guest. Changing the
    // filtering method would be too complicated for now. We will simply
    // avoid to persist the user_representative_picture_id in the database
    // if $params['public']
    if (!empty($row['user_representative_picture_id']))
    {
      $image_id = $row['user_representative_picture_id'];
    }
    else if (!empty($row['representative_picture_id']))
    { // if a representative picture is set, it has priority
      $image_id = $row['representative_picture_id'];
    }
    else if ($conf['allow_random_representative'])
    {
      // searching a random representant among elements in sub-categories
      $image_id = get_random_image_in_category($row);
    }
    else
    { // searching a random representant among representant of sub-categories
      if ($row['count_categories']>0 and $row['count_images']>0)
      {
        $query = '
  SELECT representative_picture_id
    FROM '.CATEGORIES_TABLE.' INNER JOIN '.USER_CACHE_CATEGORIES_TABLE.'
    ON id = cat_id and user_id = '.$user['id'].'
    WHERE uppercats LIKE \''.$row['uppercats'].',%\'
      AND representative_picture_id IS NOT NULL'
          .get_sql_condition_FandF
          (
            array
            (
              'visible_categories' => 'id',
              ),
            "\n  AND"
            ).'
    ORDER BY '.DB_RANDOM_FUNCTION.'()
    LIMIT 1
  ;';
        $subresult = pwg_query($query);
        if (pwg_db_num_rows($subresult) > 0)
        {
          list($image_id) = pwg_db_fetch_row($subresult);
        }
      }
    }

    if (isset($image_id))
    {
      if ($conf['representative_cache_on_subcats'] and $row['user_representative_picture_id'] != $image_id)
      {
        $user_representative_updates_for[ $user['id'].'#'.$row['id'] ] = $image_id;
      }

      $row['representative_picture_id'] = $image_id;
      array_push($image_ids, $image_id);
      array_push($categories, $row);
    }
    unset($image_id);
    // management of the album thumbnail -- stops here


    array_push($cats, $row);
  }
  usort($cats, 'global_rank_compare');

  // management of the album thumbnail -- starts here
  if (count($categories) > 0)
  {
    $thumbnail_src_of = array();
    $new_image_ids = array();

    $query = '
SELECT id, path, representative_ext, level
  FROM '.IMAGES_TABLE.'
  WHERE id IN ('.implode(',', $image_ids).')
;';
    $result = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result))
    {
      if ($row['level'] <= $user['level'])
      {
        $thumbnail_src_of[$row['id']] = DerivativeImage::thumb_url($row);
      }
      else
      {
        // problem: we must not display the thumbnail of a photo which has a
        // higher privacy level than user privacy level
        //
        // * what is the represented category?
        // * find a random photo matching user permissions
        // * register it at user_representative_picture_id
        // * set it as the representative_picture_id for the category

        foreach ($categories as &$category)
        {
          if ($row['id'] == $category['representative_picture_id'])
          {
            // searching a random representant among elements in sub-categories
            $image_id = get_random_image_in_category($category);

            if (isset($image_id) and !in_array($image_id, $image_ids))
            {
              array_push($new_image_ids, $image_id);
            }

            if ($conf['representative_cache_on_level'])
            {
              $user_representative_updates_for[ $user['id'].'#'.$category['id'] ] = $image_id;
            }

            $category['representative_picture_id'] = $image_id;
          }
        }
        unset($category);
      }
    }

    if (count($new_image_ids) > 0)
    {
      $query = '
SELECT id, path, representative_ext
  FROM '.IMAGES_TABLE.'
  WHERE id IN ('.implode(',', $new_image_ids).')
;';
      $result = pwg_query($query);
      while ($row = pwg_db_fetch_assoc($result))
      {
        $thumbnail_src_of[$row['id']] = DerivativeImage::thumb_url($row);
      }
    }
  }

  // compared to code in include/category_cats, we only persist the new
  // user_representative if we have used $user['id'] and not the guest id,
  // or else the real guest may see thumbnail that he should not
  if (!$params['public'] and count($user_representative_updates_for))
  {
    $updates = array();

    foreach ($user_representative_updates_for as $user_cat => $image_id)
    {
      list($user_id, $cat_id) = explode('#', $user_cat);

      array_push(
        $updates,
        array(
          'user_id' => $user_id,
          'cat_id' => $cat_id,
          'user_representative_picture_id' => $image_id,
          )
        );
    }

    mass_updates(
      USER_CACHE_CATEGORIES_TABLE,
      array(
        'primary' => array('user_id', 'cat_id'),
        'update'  => array('user_representative_picture_id')
        ),
      $updates
      );
  }

  foreach ($cats as &$cat)
  {
    foreach ($categories as $category)
    {
      if ($category['id'] == $cat['id'] and isset($category['representative_picture_id']))
      {
        $cat['tn_url'] = $thumbnail_src_of[$category['representative_picture_id']];
      }
    }
    // we don't want them in the output
    unset($cat['user_representative_picture_id']);
    unset($cat['count_images']);
    unset($cat['count_categories']);
  }
  unset($cat);
  // management of the album thumbnail -- stops here

  if ($params['tree_output'])
  {
    return categories_flatlist_to_tree($cats);
  }
  else
  {
    return array(
      'categories' => new PwgNamedArray(
        $cats,
        'category',
        array(
          'id',
          'url',
          'nb_images',
          'total_nb_images',
          'nb_categories',
          'date_last',
          'max_date_last',
          )
        )
      );
  }
}

/**
 * returns the list of categories as you can see them in administration (web
 * service method).
 *
 * Only admin can run this method and permissions are not taken into
 * account.
 */
function ws_categories_getAdminList($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  $query = '
SELECT
    category_id,
    COUNT(*) AS counter
  FROM '.IMAGE_CATEGORY_TABLE.'
  GROUP BY category_id
;';
  $nb_images_of = simple_hash_from_query($query, 'category_id', 'counter');

  $query = '
SELECT
    id,
    name,
    comment,
    uppercats,
    global_rank
  FROM '.CATEGORIES_TABLE.'
;';
  $result = pwg_query($query);
  $cats = array();

  while ($row = pwg_db_fetch_assoc($result))
  {
    $id = $row['id'];
    $row['nb_images'] = isset($nb_images_of[$id]) ? $nb_images_of[$id] : 0;
    $row['name'] = strip_tags(
      trigger_event(
        'render_category_name',
        $row['name'],
        'ws_categories_getAdminList'
        )
      );
    $row['comment'] = strip_tags(
      trigger_event(
        'render_category_description',
        $row['comment'],
        'ws_categories_getAdminList'
        )
      );
    array_push($cats, $row);
  }

  usort($cats, 'global_rank_compare');
  return array(
    'categories' => new PwgNamedArray(
      $cats,
      'category',
      array(
        'id',
        'nb_images',
        'name',
        'uppercats',
        'global_rank',
        )
      )
    );
}

/**
 * returns detailed information for an element (web service method)
 */
function ws_images_addComment($params, &$service)
{
  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }
  $params['image_id'] = (int)$params['image_id'];
  $query = '
SELECT DISTINCT image_id
  FROM '.IMAGE_CATEGORY_TABLE.' INNER JOIN '.CATEGORIES_TABLE.' ON category_id=id
  WHERE commentable="true"
    AND image_id='.$params['image_id'].
    get_sql_condition_FandF(
      array(
        'forbidden_categories' => 'id',
        'visible_categories' => 'id',
        'visible_images' => 'image_id'
      ),
      ' AND'
    );
  if ( !pwg_db_num_rows( pwg_query( $query ) ) )
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid image_id");
  }

  $comm = array(
    'author' => trim( $params['author'] ),
    'content' => trim( $params['content'] ),
    'image_id' => $params['image_id'],
   );

  include_once(PHPWG_ROOT_PATH.'include/functions_comment.inc.php');

  $comment_action = insert_user_comment(
      $comm, $params['key'], $infos
    );

  switch ($comment_action)
  {
    case 'reject':
      array_push($infos, l10n('Your comment has NOT been registered because it did not pass the validation rules') );
      return new PwgError(403, implode("; ", $infos) );
    case 'validate':
    case 'moderate':
      $ret = array(
          'id' => $comm['id'],
          'validation' => $comment_action=='validate',
        );
      return new PwgNamedStruct(
          'comment',
          $ret,
          null, array()
        );
    default:
      return new PwgError(500, "Unknown comment action ".$comment_action );
  }
}

/**
 * returns detailed information for an element (web service method)
 */
function ws_images_getInfo($params, &$service)
{
  global $user, $conf;
  $params['image_id'] = (int)$params['image_id'];
  if ( $params['image_id']<=0 )
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid image_id");
  }

  $query='
SELECT * FROM '.IMAGES_TABLE.'
  WHERE id='.$params['image_id'].
    get_sql_condition_FandF(
      array('visible_images' => 'id'),
      ' AND'
    ).'
LIMIT 1';

  $image_row = pwg_db_fetch_assoc(pwg_query($query));
  if ($image_row==null)
  {
    return new PwgError(404, "image_id not found");
  }
  $image_row = array_merge( $image_row, ws_std_get_urls($image_row) );

  //-------------------------------------------------------- related categories
  $query = '
SELECT id, name, permalink, uppercats, global_rank, commentable
  FROM '.IMAGE_CATEGORY_TABLE.'
    INNER JOIN '.CATEGORIES_TABLE.' ON category_id = id
  WHERE image_id = '.$image_row['id'].
  get_sql_condition_FandF(
      array( 'forbidden_categories' => 'category_id' ),
      ' AND'
    ).'
;';
  $result = pwg_query($query);
  $is_commentable = false;
  $related_categories = array();
  while ($row = pwg_db_fetch_assoc($result))
  {
    if ($row['commentable']=='true')
    {
      $is_commentable = true;
    }
    unset($row['commentable']);
    $row['url'] = make_index_url(
        array(
          'category' => $row
          )
      );

    $row['page_url'] = make_picture_url(
        array(
          'image_id' => $image_row['id'],
          'image_file' => $image_row['file'],
          'category' => $row
          )
      );
    $row['id']=(int)$row['id'];
    array_push($related_categories, $row);
  }
  usort($related_categories, 'global_rank_compare');
  if ( empty($related_categories) )
  {
    return new PwgError(401, 'Access denied');
  }

  //-------------------------------------------------------------- related tags
  $related_tags = get_common_tags( array($image_row['id']), -1 );
  foreach( $related_tags as $i=>$tag)
  {
    $tag['url'] = make_index_url(
        array(
          'tags' => array($tag)
          )
      );
    $tag['page_url'] = make_picture_url(
        array(
          'image_id' => $image_row['id'],
          'image_file' => $image_row['file'],
          'tags' => array($tag),
          )
      );
    unset($tag['counter']);
    $tag['id']=(int)$tag['id'];
    $related_tags[$i]=$tag;
  }
  //------------------------------------------------------------- related rates
	$rating = array('score'=>$image_row['rating_score'], 'count'=>0, 'average'=>null);
	if (isset($rating['score']))
	{
		$query = '
SELECT COUNT(rate) AS count
     , ROUND(AVG(rate),2) AS average
  FROM '.RATE_TABLE.'
  WHERE element_id = '.$image_row['id'].'
;';
		$row = pwg_db_fetch_assoc(pwg_query($query));
		$rating['score'] = (float)$rating['score'];
		$rating['average'] = (float)$row['average'];
		$rating['count'] = (int)$row['count'];
	}

  //---------------------------------------------------------- related comments
  $related_comments = array();

  $where_comments = 'image_id = '.$image_row['id'];
  if ( !is_admin() )
  {
    $where_comments .= '
    AND validated="true"';
  }

  $query = '
SELECT COUNT(id) AS nb_comments
  FROM '.COMMENTS_TABLE.'
  WHERE '.$where_comments;
  list($nb_comments) = array_from_query($query, 'nb_comments');
  $nb_comments = (int)$nb_comments;

  if ( $nb_comments>0 and $params['comments_per_page']>0 )
  {
    $query = '
SELECT id, date, author, content
  FROM '.COMMENTS_TABLE.'
  WHERE '.$where_comments.'
  ORDER BY date
  LIMIT '.(int)$params['comments_per_page'].
    ' OFFSET '.(int)($params['comments_per_page']*$params['comments_page']);

    $result = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result))
    {
      $row['id']=(int)$row['id'];
      array_push($related_comments, $row);
    }
  }

  $comment_post_data = null;
  if ($is_commentable and
      (!is_a_guest()
        or (is_a_guest() and $conf['comments_forall'] )
      )
      )
  {
    $comment_post_data['author'] = stripslashes($user['username']);
    $comment_post_data['key'] = get_ephemeral_key(2, $params['image_id']);
  }

  $ret = $image_row;
  foreach ( array('id','width','height','hit','filesize') as $k )
  {
    if (isset($ret[$k]))
    {
      $ret[$k] = (int)$ret[$k];
    }
  }
  foreach ( array('path', 'storage_category_id') as $k )
  {
    unset($ret[$k]);
  }

  $ret['rates'] = array( WS_XML_ATTRIBUTES => $rating );
  $ret['categories'] = new PwgNamedArray($related_categories, 'category', array('id','url', 'page_url') );
  $ret['tags'] = new PwgNamedArray($related_tags, 'tag', array('id','url_name','url','name','page_url') );
  if ( isset($comment_post_data) )
  {
    $ret['comment_post'] = array( WS_XML_ATTRIBUTES => $comment_post_data );
  }
  $ret['comments'] = array(
     WS_XML_ATTRIBUTES =>
        array(
          'page' => $params['comments_page'],
          'per_page' => $params['comments_per_page'],
          'count' => count($related_comments),
          'nb_comments' => $nb_comments,
        ),
     WS_XML_CONTENT => new PwgNamedArray($related_comments, 'comment', array('id','date') )
      );

  return new PwgNamedStruct('image',$ret, null, array('name','comment') );
}


/**
 * rates the image_id in the parameter
 */
function ws_images_Rate($params, &$service)
{
  $image_id = (int)$params['image_id'];
  $query = '
SELECT DISTINCT id FROM '.IMAGES_TABLE.'
  INNER JOIN '.IMAGE_CATEGORY_TABLE.' ON id=image_id
  WHERE id='.$image_id
  .get_sql_condition_FandF(
    array(
        'forbidden_categories' => 'category_id',
        'forbidden_images' => 'id',
      ),
    '    AND'
    ).'
    LIMIT 1';
  if ( pwg_db_num_rows( pwg_query($query) )==0 )
  {
    return new PwgError(404, "Invalid image_id or access denied" );
  }
  $rate = (int)$params['rate'];
  include_once(PHPWG_ROOT_PATH.'include/functions_rate.inc.php');
  $res = rate_picture( $image_id, $rate );
  if ($res==false)
  {
    global $conf;
    return new PwgError( 403, "Forbidden or rate not in ". implode(',',$conf['rate_items']));
  }
  return $res;
}


/**
 * returns a list of elements corresponding to a query search
 */
function ws_images_search($params, &$service)
{
  global $page;
  $images = array();
  include_once( PHPWG_ROOT_PATH .'include/functions_search.inc.php' );

  $where_clauses = ws_std_image_sql_filter( $params, 'i.' );
  $order_by = ws_std_image_sql_order($params, 'i.');

  $super_order_by = false;
  if ( !empty($order_by) )
  {
    global $conf;
    $conf['order_by'] = 'ORDER BY '.$order_by;
    $super_order_by=true; // quick_search_result might be faster
  }

  $search_result = get_quick_search_results($params['query'],
      $super_order_by,
      implode(' AND ', $where_clauses)
    );

  $image_ids = array_slice(
      $search_result['items'],
      $params['page']*$params['per_page'],
      $params['per_page']
    );

  if ( count($image_ids) )
  {
    $query = '
SELECT * FROM '.IMAGES_TABLE.'
  WHERE id IN ('.implode(',', $image_ids).')';

    $image_ids = array_flip($image_ids);
    $result = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result))
    {
      $image = array();
      foreach ( array('id', 'width', 'height', 'hit') as $k )
      {
        if (isset($row[$k]))
        {
          $image[$k] = (int)$row[$k];
        }
      }
      foreach ( array('file', 'name', 'comment', 'date_creation', 'date_available') as $k )
      {
        $image[$k] = $row[$k];
      }
      $image = array_merge( $image, ws_std_get_urls($row) );
      $images[$image_ids[$image['id']]] = $image;
    }
    ksort($images, SORT_NUMERIC);
    $images = array_values($images);
  }


  return array( 'images' =>
    array (
      WS_XML_ATTRIBUTES =>
        array(
            'page' => $params['page'],
            'per_page' => $params['per_page'],
            'count' => count($images)
          ),
       WS_XML_CONTENT => new PwgNamedArray($images, 'image',
          ws_std_get_image_xml_attributes() )
      )
    );
}

function ws_images_setPrivacyLevel($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }
  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }
  $params['image_id'] = array_map( 'intval',$params['image_id'] );
  if ( empty($params['image_id']) )
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid image_id");
  }
  global $conf;
  if ( !in_array( (int)$params['level'], $conf['available_permission_levels']) )
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid level");
  }

  $query = '
UPDATE '.IMAGES_TABLE.'
  SET level='.(int)$params['level'].'
  WHERE id IN ('.implode(',',$params['image_id']).')';
  $result = pwg_query($query);
  $affected_rows = pwg_db_changes($result);
  if ($affected_rows)
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    invalidate_user_cache();
  }
  return $affected_rows;
}

function ws_images_setRank($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }

  // is the image_id valid?
  $params['image_id'] = (int)$params['image_id'];
  if ($params['image_id'] <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid image_id");
  }

  // is the category valid?
  $params['category_id'] = (int)$params['category_id'];
  if ($params['category_id'] <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid category_id");
  }

  // is the rank valid?
  $params['rank'] = (int)$params['rank'];
  if ($params['rank'] <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid rank");
  }

  // does the image really exist?
  $query='
SELECT
    *
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$params['image_id'].'
;';

  $image_row = pwg_db_fetch_assoc(pwg_query($query));
  if ($image_row == null)
  {
    return new PwgError(404, "image_id not found");
  }

  // is the image associated to this category?
  $query = '
SELECT
    image_id,
    category_id,
    rank
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE image_id = '.$params['image_id'].'
    AND category_id = '.$params['category_id'].'
;';
  $category_row = pwg_db_fetch_assoc(pwg_query($query));
  if ($category_row == null)
  {
    return new PwgError(404, "This image is not associated to this category");
  }

  // what is the current higher rank for this category?
  $query = '
SELECT
    MAX(rank) AS max_rank
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE category_id = '.$params['category_id'].'
;';
  $result = pwg_query($query);
  $row = pwg_db_fetch_assoc($result);

  if (is_numeric($row['max_rank']))
  {
    if ($params['rank'] > $row['max_rank'])
    {
      $params['rank'] = $row['max_rank'] + 1;
    }
  }
  else
  {
    $params['rank'] = 1;
  }

  // update rank for all other photos in the same category
  $query = '
UPDATE '.IMAGE_CATEGORY_TABLE.'
  SET rank = rank + 1
  WHERE category_id = '.$params['category_id'].'
    AND rank IS NOT NULL
    AND rank >= '.$params['rank'].'
;';
  pwg_query($query);

  // set the new rank for the photo
  $query = '
UPDATE '.IMAGE_CATEGORY_TABLE.'
  SET rank = '.$params['rank'].'
  WHERE image_id = '.$params['image_id'].'
    AND category_id = '.$params['category_id'].'
;';
  pwg_query($query);

  // return data for client
  return array(
    'image_id' => $params['image_id'],
    'category_id' => $params['category_id'],
    'rank' => $params['rank'],
    );
}

function ws_images_add_chunk($params, &$service)
{
  global $conf;

  // data
  // original_sum
  // type {thumb, file, high}
  // position

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }

  foreach ($params as $param_key => $param_value) {
    if ('data' == $param_key) {
      continue;
    }

    ws_logfile(
      sprintf(
        '[ws_images_add_chunk] input param "%s" : "%s"',
        $param_key,
        is_null($param_value) ? 'NULL' : $param_value
        )
      );
  }

  $upload_dir = $conf['upload_dir'].'/buffer';

  // create the upload directory tree if not exists
  if (!mkgetdir($upload_dir, MKGETDIR_DEFAULT&~MKGETDIR_DIE_ON_ERROR))
  {
    return new PwgError(500, 'error during buffer directory creation');
  }

  $filename = sprintf(
    '%s-%s-%05u.block',
    $params['original_sum'],
    $params['type'],
    $params['position']
    );

  ws_logfile('[ws_images_add_chunk] data length : '.strlen($params['data']));

  $bytes_written = file_put_contents(
    $upload_dir.'/'.$filename,
    base64_decode($params['data'])
    );

  if (false === $bytes_written) {
    return new PwgError(
      500,
      'an error has occured while writting chunk '.$params['position'].' for '.$params['type']
      );
  }
}

function merge_chunks($output_filepath, $original_sum, $type)
{
  global $conf;

  ws_logfile('[merge_chunks] input parameter $output_filepath : '.$output_filepath);

  if (is_file($output_filepath))
  {
    unlink($output_filepath);

    if (is_file($output_filepath))
    {
      return new PwgError(500, '[merge_chunks] error while trying to remove existing '.$output_filepath);
    }
  }

  $upload_dir = $conf['upload_dir'].'/buffer';
  $pattern = '/'.$original_sum.'-'.$type.'/';
  $chunks = array();

  if ($handle = opendir($upload_dir))
  {
    while (false !== ($file = readdir($handle)))
    {
      if (preg_match($pattern, $file))
      {
        ws_logfile($file);
        array_push($chunks, $upload_dir.'/'.$file);
      }
    }
    closedir($handle);
  }

  sort($chunks);

  if (function_exists('memory_get_usage')) {
    ws_logfile('[merge_chunks] memory_get_usage before loading chunks: '.memory_get_usage());
  }

  $i = 0;

  foreach ($chunks as $chunk)
  {
    $string = file_get_contents($chunk);

    if (function_exists('memory_get_usage')) {
      ws_logfile('[merge_chunks] memory_get_usage on chunk '.++$i.': '.memory_get_usage());
    }

    if (!file_put_contents($output_filepath, $string, FILE_APPEND))
    {
      return new PwgError(500, '[merge_chunks] error while writting chunks for '.$output_filepath);
    }

    unlink($chunk);
  }

  if (function_exists('memory_get_usage')) {
    ws_logfile('[merge_chunks] memory_get_usage after loading chunks: '.memory_get_usage());
  }
}

/**
 * Function introduced for Piwigo 2.4 and the new "multiple size"
 * (derivatives) feature. As we only need the biggest sent photo as
 * "original", we remove chunks for smaller sizes. We can't make it earlier
 * in ws_images_add_chunk because at this moment we don't know which $type
 * will be the biggest (we could remove the thumb, but let's use the same
 * algorithm)
 */
function remove_chunks($original_sum, $type)
{
  global $conf;

  $upload_dir = $conf['upload_dir'].'/buffer';
  $pattern = '/'.$original_sum.'-'.$type.'/';
  $chunks = array();

  if ($handle = opendir($upload_dir))
  {
    while (false !== ($file = readdir($handle)))
    {
      if (preg_match($pattern, $file))
      {
        array_push($chunks, $upload_dir.'/'.$file);
      }
    }
    closedir($handle);
  }

  foreach ($chunks as $chunk)
  {
    unlink($chunk);
  }
}

function ws_images_addFile($params, &$service)
{
  ws_logfile(__FUNCTION__.', input :  '.var_export($params, true));
  // image_id
  // type {thumb, file, high}
  // sum -> not used currently (Piwigo 2.4)

  global $conf;
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  $params['image_id'] = (int)$params['image_id'];
  if ($params['image_id'] <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid image_id");
  }

  //
  // what is the path and other infos about the photo?
  //
  $query = '
SELECT
    path,
    file,
    md5sum,
    width,
    height,
    filesize
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$params['image_id'].'
;';
  $image = pwg_db_fetch_assoc(pwg_query($query));

  if ($image == null)
  {
    return new PwgError(404, "image_id not found");
  }

  // since Piwigo 2.4 and derivatives, we do not take the imported "thumb"
  // into account
  if ('thumb' == $params['type'])
  {
    remove_chunks($image['md5sum'], $type);
    return true;
  }

  // since Piwigo 2.4 and derivatives, we only care about the "original"
  $original_type = 'file';
  if ('high' == $params['type'])
  {
    $original_type = 'high';
  }

  $file_path = $conf['upload_dir'].'/buffer/'.$image['md5sum'].'-original';

  merge_chunks($file_path, $image['md5sum'], $original_type);
  chmod($file_path, 0644);

  include_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');

  // if we receive the "file", we only update the original if the "file" is
  // bigger than current original
  if ('file' == $params['type'])
  {
    $do_update = false;

    $infos = pwg_image_infos($file_path);

    foreach (array('width', 'height', 'filesize') as $image_info)
    {
      if ($infos[$image_info] > $image[$image_info])
      {
        $do_update = true;
      }
    }

    if (!$do_update)
    {
      unlink($file_path);
      return true;
    }
  }

  $image_id = add_uploaded_file(
    $file_path,
    $image['file'],
    null,
    null,
    $params['image_id'],
    $image['md5sum'] // we force the md5sum to remain the same
    );
}

function ws_images_add($params, &$service)
{
  global $conf, $user;
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  foreach ($params as $param_key => $param_value) {
    ws_logfile(
      sprintf(
        '[pwg.images.add] input param "%s" : "%s"',
        $param_key,
        is_null($param_value) ? 'NULL' : $param_value
        )
      );
  }

  $params['image_id'] = (int)$params['image_id'];
  if ($params['image_id'] > 0)
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

    $query='
SELECT *
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$params['image_id'].'
;';

    $image_row = pwg_db_fetch_assoc(pwg_query($query));
    if ($image_row == null)
    {
      return new PwgError(404, "image_id not found");
    }
  }

  // does the image already exists ?
  if ($params['check_uniqueness'])
  {
    if ('md5sum' == $conf['uniqueness_mode'])
    {
      $where_clause = "md5sum = '".$params['original_sum']."'";
    }
    if ('filename' == $conf['uniqueness_mode'])
    {
      $where_clause = "file = '".$params['original_filename']."'";
    }

    $query = '
SELECT
    COUNT(*) AS counter
  FROM '.IMAGES_TABLE.'
  WHERE '.$where_clause.'
;';
    list($counter) = pwg_db_fetch_row(pwg_query($query));
    if ($counter != 0) {
      return new PwgError(500, 'file already exists');
    }
  }

  // due to the new feature "derivatives" (multiple sizes) introduced for
  // Piwigo 2.4, we only take the biggest photos sent on
  // pwg.images.addChunk. If "high" is available we use it as "original"
  // else we use "file".
  remove_chunks($params['original_sum'], 'thumb');

  if (isset($params['high_sum']))
  {
    $original_type = 'high';
    remove_chunks($params['original_sum'], 'file');
  }
  else
  {
    $original_type = 'file';
  }

  $file_path = $conf['upload_dir'].'/buffer/'.$params['original_sum'].'-original';

  merge_chunks($file_path, $params['original_sum'], $original_type);
  chmod($file_path, 0644);

  include_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');

  $image_id = add_uploaded_file(
    $file_path,
    $params['original_filename'],
    null, // categories
    isset($params['level']) ? $params['level'] : null,
    $params['image_id'] > 0 ? $params['image_id'] : null,
    $params['original_sum']
    );

  $info_columns = array(
    'name',
    'author',
    'comment',
    'date_creation',
    );

  $update = array();

  foreach ($info_columns as $key)
  {
    if (isset($params[$key]))
    {
      $update[$key] = $params[$key];
    }
  }

  if (count(array_keys($update)) > 0)
  {
    single_update(
      IMAGES_TABLE,
      $update,
      array('id' => $image_id)
      );
  }

  $url_params = array('image_id' => $image_id);

  // let's add links between the image and the categories
  if (isset($params['categories']))
  {
    ws_add_image_category_relations($image_id, $params['categories']);

    if (preg_match('/^\d+/', $params['categories'], $matches)) {
      $category_id = $matches[0];

      $query = '
SELECT id, name, permalink
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$category_id.'
;';
      $result = pwg_query($query);
      $category = pwg_db_fetch_assoc($result);

      $url_params['section'] = 'categories';
      $url_params['category'] = $category;
    }
  }

  // and now, let's create tag associations
  if (isset($params['tag_ids']) and !empty($params['tag_ids']))
  {
    set_tags(
      explode(',', $params['tag_ids']),
      $image_id
      );
  }

  invalidate_user_cache();

  return array(
    'image_id' => $image_id,
    'url' => make_picture_url($url_params),
    );
}

function ws_images_addSimple($params, &$service)
{
  global $conf;
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }

  if (!isset($_FILES['image']))
  {
    return new PwgError(405, "The image (file) parameter is missing");
  }

  $params['image_id'] = (int)$params['image_id'];
  if ($params['image_id'] > 0)
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

    $query='
SELECT *
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$params['image_id'].'
;';

    $image_row = pwg_db_fetch_assoc(pwg_query($query));
    if ($image_row == null)
    {
      return new PwgError(404, "image_id not found");
    }
  }

  // category
  $params['category'] = (int)$params['category'];
  if ($params['category'] <= 0 and $params['image_id'] <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid category_id");
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');

  $image_id = add_uploaded_file(
    $_FILES['image']['tmp_name'],
    $_FILES['image']['name'],
    $params['category'] > 0 ? array($params['category']) : null,
    8,
    $params['image_id'] > 0 ? $params['image_id'] : null
    );

  $info_columns = array(
    'name',
    'author',
    'comment',
    'level',
    'date_creation',
    );

  foreach ($info_columns as $key)
  {
    if (isset($params[$key]))
    {
      $update[$key] = $params[$key];
    }
  }

  if (count(array_keys($update)) > 0)
  {
    $update['id'] = $image_id;

    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    mass_updates(
      IMAGES_TABLE,
      array(
        'primary' => array('id'),
        'update'  => array_diff(array_keys($update), array('id'))
        ),
      array($update)
      );
  }


  if (isset($params['tags']) and !empty($params['tags']))
  {
    $tag_ids = array();
    if (is_array($params['tags']))
    {
      foreach ($params['tags'] as $tag_name)
      {
        $tag_id = tag_id_from_tag_name($tag_name);
        array_push($tag_ids, $tag_id);
      }
    }
    else
    {
      $tag_names = preg_split('~(?<!\\\),~', $params['tags']);
      foreach ($tag_names as $tag_name)
      {
        $tag_id = tag_id_from_tag_name(preg_replace('#\\\\*,#', ',', $tag_name));
        array_push($tag_ids, $tag_id);
      }
    }

    add_tags($tag_ids, array($image_id));
  }

  $url_params = array('image_id' => $image_id);

  if ($params['category'] > 0)
  {
    $query = '
SELECT id, name, permalink
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$params['category'].'
;';
    $result = pwg_query($query);
    $category = pwg_db_fetch_assoc($result);

    $url_params['section'] = 'categories';
    $url_params['category'] = $category;
  }

  // update metadata from the uploaded file (exif/iptc), even if the sync
  // was already performed by add_uploaded_file().

  require_once(PHPWG_ROOT_PATH.'admin/include/functions_metadata.php');
  sync_metadata(array($image_id));

  return array(
    'image_id' => $image_id,
    'url' => make_picture_url($url_params),
    );
}

function ws_rates_delete($params, &$service)
{
  global $conf;

  if (!$service->isPost())
  {
    return new PwgError(405, 'This method requires HTTP POST');
  }

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  $user_id = (int)$params['user_id'];
  if ($user_id<=0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid user_id');
  }

  $query = '
DELETE FROM '.RATE_TABLE.'
  WHERE user_id='.$user_id;

  if (!empty($params['anonymous_id']))
  {
    $query .= ' AND anonymous_id=\''.$params['anonymous_id'].'\'';
  }

  $changes = pwg_db_changes(pwg_query($query));
  if ($changes)
  {
    include_once(PHPWG_ROOT_PATH.'include/functions_rate.inc.php');
    update_rating_score();
  }
  return $changes;
}


/**
 * perform a login (web service method)
 */
function ws_session_login($params, &$service)
{
  global $conf;

  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }
  if (try_log_user($params['username'], $params['password'],false))
  {
    return true;
  }
  return new PwgError(999, 'Invalid username/password');
}


/**
 * performs a logout (web service method)
 */
function ws_session_logout($params, &$service)
{
  if (!is_a_guest())
  {
    logout_user();
  }
  return true;
}

function ws_session_getStatus($params, &$service)
{
  global $user;
  $res = array();
  $res['username'] = is_a_guest() ? 'guest' : stripslashes($user['username']);
  foreach ( array('status', 'theme', 'language') as $k )
  {
    $res[$k] = $user[$k];
  }
  $res['pwg_token'] = get_pwg_token();
  $res['charset'] = get_pwg_charset();

  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));
  $res['current_datetime'] = $dbnow;

  return $res;
}


/**
 * returns a list of tags (web service method)
 */
function ws_tags_getList($params, &$service)
{
  $tags = get_available_tags();
  if ($params['sort_by_counter'])
  {
    usort($tags, create_function('$a,$b', 'return -$a["counter"]+$b["counter"];') );
  }
  else
  {
    usort($tags, 'tag_alpha_compare');
  }
  for ($i=0; $i<count($tags); $i++)
  {
    $tags[$i]['id'] = (int)$tags[$i]['id'];
    $tags[$i]['counter'] = (int)$tags[$i]['counter'];
    $tags[$i]['url'] = make_index_url(
        array(
          'section'=>'tags',
          'tags'=>array($tags[$i])
        )
      );
  }
  return array('tags' => new PwgNamedArray($tags, 'tag', array('id','url_name','url', 'name', 'counter' )) );
}

/**
 * returns the list of tags as you can see them in administration (web
 * service method).
 *
 * Only admin can run this method and permissions are not taken into
 * account.
 */
function ws_tags_getAdminList($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  $tags = get_all_tags();
  return array(
    'tags' => new PwgNamedArray(
      $tags,
      'tag',
      array(
        'name',
        'id',
        'url_name',
        )
      )
    );
}

/**
 * returns a list of images for tags (web service method)
 */
function ws_tags_getImages($params, &$service)
{
  global $conf;

  // first build all the tag_ids we are interested in
  $params['tag_id'] = array_map( 'intval',$params['tag_id'] );
  $tags = find_tags($params['tag_id'], $params['tag_url_name'], $params['tag_name']);
  $tags_by_id = array();
  foreach( $tags as $tag )
  {
    $tags['id'] = (int)$tag['id'];
    $tags_by_id[ $tag['id'] ] = $tag;
  }
  unset($tags);
  $tag_ids = array_keys($tags_by_id);


  $where_clauses = ws_std_image_sql_filter($params);
  if (!empty($where_clauses))
  {
    $where_clauses = implode( ' AND ', $where_clauses);
  }
  $image_ids = get_image_ids_for_tags(
    $tag_ids,
    $params['tag_mode_and'] ? 'AND' : 'OR',
    $where_clauses,
    ws_std_image_sql_order($params) );


  $image_ids = array_slice($image_ids, (int)($params['per_page']*$params['page']), (int)$params['per_page'] );

  $image_tag_map = array();
  if ( !empty($image_ids) and !$params['tag_mode_and'] )
  { // build list of image ids with associated tags per image
    $query = '
SELECT image_id, GROUP_CONCAT(tag_id) AS tag_ids
  FROM '.IMAGE_TAG_TABLE.'
  WHERE tag_id IN ('.implode(',',$tag_ids).') AND image_id IN ('.implode(',',$image_ids).')
  GROUP BY image_id';
    $result = pwg_query($query);
    while ( $row=pwg_db_fetch_assoc($result) )
    {
      $row['image_id'] = (int)$row['image_id'];
      array_push( $image_ids, $row['image_id'] );
      $image_tag_map[ $row['image_id'] ] = explode(',', $row['tag_ids']);
    }
  }

  $images = array();
  if (!empty($image_ids))
  {
    $rank_of = array_flip($image_ids);
    $result = pwg_query('
SELECT * FROM '.IMAGES_TABLE.'
  WHERE id IN ('.implode(',',$image_ids).')');
    while ($row = pwg_db_fetch_assoc($result))
    {
      $image = array();
      $image['rank'] = $rank_of[ $row['id'] ];
      foreach ( array('id', 'width', 'height', 'hit') as $k )
      {
        if (isset($row[$k]))
        {
          $image[$k] = (int)$row[$k];
        }
      }
      foreach ( array('file', 'name', 'comment', 'date_creation', 'date_available') as $k )
      {
        $image[$k] = $row[$k];
      }
      $image = array_merge( $image, ws_std_get_urls($row) );

      $image_tag_ids = ($params['tag_mode_and']) ? $tag_ids : $image_tag_map[$image['id']];
      $image_tags = array();
      foreach ($image_tag_ids as $tag_id)
      {
        $url = make_index_url(
                 array(
                  'section'=>'tags',
                  'tags'=> array($tags_by_id[$tag_id])
                )
              );
        $page_url = make_picture_url(
                 array(
                  'section'=>'tags',
                  'tags'=> array($tags_by_id[$tag_id]),
                  'image_id' => $row['id'],
                  'image_file' => $row['file'],
                )
              );
        array_push($image_tags, array(
                'id' => (int)$tag_id,
                'url' => $url,
                'page_url' => $page_url,
              )
            );
      }
      $image['tags'] = new PwgNamedArray($image_tags, 'tag',
              array('id','url_name','url','page_url')
            );
      array_push($images, $image);
    }
    usort($images, 'rank_compare');
    unset($rank_of);
  }

  return array( 'images' =>
    array (
      WS_XML_ATTRIBUTES =>
        array(
            'page' => $params['page'],
            'per_page' => $params['per_page'],
            'count' => count($images)
          ),
       WS_XML_CONTENT => new PwgNamedArray($images, 'image',
          ws_std_get_image_xml_attributes() )
      )
    );
}

function ws_categories_add($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

  $options = array();
  if (!empty($params['status']) and in_array($params['status'], array('private','public')))
  {
    $options['status'] = $params['status'];
  }
  
  if (!empty($params['visible']) and in_array($params['visible'], array('true','false')))
  {
    $options['visible'] = get_boolean($params['visible']);
  }
  
  if (!empty($params['commentable']) and in_array($params['commentable'], array('true','false')) )
  {
    $options['commentable'] = get_boolean($params['commentable']);
  }
  
  if (!empty($params['comment']))
  {
    $options['comment'] = $params['comment'];
  }
  

  $creation_output = create_virtual_category(
    $params['name'],
    $params['parent'],
    $options
    );

  if (isset($creation_output['error']))
  {
    return new PwgError(500, $creation_output['error']);
  }

  invalidate_user_cache();

  return $creation_output;
}

function ws_tags_add($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

  $creation_output = create_tag($params['name']);

  if (isset($creation_output['error']))
  {
    return new PwgError(500, $creation_output['error']);
  }

  return $creation_output;
}

function ws_images_exist($params, &$service)
{
  ws_logfile(__FUNCTION__.' '.var_export($params, true));

  global $conf;

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  $split_pattern = '/[\s,;\|]/';

  if ('md5sum' == $conf['uniqueness_mode'])
  {
    // search among photos the list of photos already added, based on md5sum
    // list
    $md5sums = preg_split(
      $split_pattern,
      $params['md5sum_list'],
      -1,
      PREG_SPLIT_NO_EMPTY
    );

    $query = '
SELECT
    id,
    md5sum
  FROM '.IMAGES_TABLE.'
  WHERE md5sum IN (\''.implode("','", $md5sums).'\')
;';
    $id_of_md5 = simple_hash_from_query($query, 'md5sum', 'id');

    $result = array();

    foreach ($md5sums as $md5sum)
    {
      $result[$md5sum] = null;
      if (isset($id_of_md5[$md5sum]))
      {
        $result[$md5sum] = $id_of_md5[$md5sum];
      }
    }
  }

  if ('filename' == $conf['uniqueness_mode'])
  {
    // search among photos the list of photos already added, based on
    // filename list
    $filenames = preg_split(
      $split_pattern,
      $params['filename_list'],
      -1,
      PREG_SPLIT_NO_EMPTY
    );

    $query = '
SELECT
    id,
    file
  FROM '.IMAGES_TABLE.'
  WHERE file IN (\''.implode("','", $filenames).'\')
;';
    $id_of_filename = simple_hash_from_query($query, 'file', 'id');

    $result = array();

    foreach ($filenames as $filename)
    {
      $result[$filename] = null;
      if (isset($id_of_filename[$filename]))
      {
        $result[$filename] = $id_of_filename[$filename];
      }
    }
  }

  return $result;
}

function ws_images_checkFiles($params, &$service)
{
  ws_logfile(__FUNCTION__.', input :  '.var_export($params, true));

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  // input parameters
  //
  // image_id
  // thumbnail_sum
  // file_sum
  // high_sum

  $params['image_id'] = (int)$params['image_id'];
  if ($params['image_id'] <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid image_id");
  }

  $query = '
SELECT
    path
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$params['image_id'].'
;';
  $result = pwg_query($query);
  if (pwg_db_num_rows($result) == 0)
  {
    return new PwgError(404, "image_id not found");
  }
  list($path) = pwg_db_fetch_row($result);

  $ret = array();

  if (isset($params['thumbnail_sum']))
  {
    // We always say the thumbnail is equal to create no reaction on the
    // other side. Since Piwigo 2.4 and derivatives, the thumbnails and web
    // sizes are always generated by Piwigo
    $ret['thumbnail'] = 'equals';
  }

  if (isset($params['high_sum']))
  {
    $ret['file'] = 'equals';
    $compare_type = 'high';
  }
  elseif (isset($params['file_sum']))
  {
    $compare_type = 'file';
  }

  if (isset($compare_type))
  {
    ws_logfile(__FUNCTION__.', md5_file($path) = '.md5_file($path));
    if (md5_file($path) != $params[$compare_type.'_sum'])
    {
      $ret[$compare_type] = 'differs';
    }
    else
    {
      $ret[$compare_type] = 'equals';
    }
  }

  ws_logfile(__FUNCTION__.', output :  '.var_export($ret, true));

  return $ret;
}

function ws_images_setInfo($params, &$service)
{
  global $conf;
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }

  $params['image_id'] = (int)$params['image_id'];
  if ($params['image_id'] <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid image_id");
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

  $query='
SELECT *
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$params['image_id'].'
;';

  $image_row = pwg_db_fetch_assoc(pwg_query($query));
  if ($image_row == null)
  {
    return new PwgError(404, "image_id not found");
  }

  // database registration
  $update = array();

  $info_columns = array(
    'name',
    'author',
    'comment',
    'level',
    'date_creation',
    );

  foreach ($info_columns as $key)
  {
    if (isset($params[$key]))
    {
      if ('fill_if_empty' == $params['single_value_mode'])
      {
        if (empty($image_row[$key]))
        {
          $update[$key] = $params[$key];
        }
      }
      elseif ('replace' == $params['single_value_mode'])
      {
        $update[$key] = $params[$key];
      }
      else
      {
        return new PwgError(
          500,
          '[ws_images_setInfo]'
          .' invalid parameter single_value_mode "'.$params['single_value_mode'].'"'
          .', possible values are {fill_if_empty, replace}.'
          );
      }
    }
  }

  if (isset($params['file']))
  {
    if (!empty($image_row['storage_category_id']))
    {
      return new PwgError(500, '[ws_images_setInfo] updating "file" is forbidden on photos added by synchronization');
    }

    $update['file'] = $params['file'];
  }

  if (count(array_keys($update)) > 0)
  {
    $update['id'] = $params['image_id'];

    mass_updates(
      IMAGES_TABLE,
      array(
        'primary' => array('id'),
        'update'  => array_diff(array_keys($update), array('id'))
        ),
      array($update)
      );
  }

  if (isset($params['categories']))
  {
    ws_add_image_category_relations(
      $params['image_id'],
      $params['categories'],
      ('replace' == $params['multiple_value_mode'] ? true : false)
      );
  }

  // and now, let's create tag associations
  if (isset($params['tag_ids']))
  {
    $tag_ids = array();

    foreach (explode(',', $params['tag_ids']) as $candidate)
    {
      $candidate = trim($candidate);

      if (preg_match(PATTERN_ID, $candidate))
      {
        $tag_ids[] = $candidate;
      }
    }

    if ('replace' == $params['multiple_value_mode'])
    {
      set_tags(
        $tag_ids,
        $params['image_id']
        );
    }
    elseif ('append' == $params['multiple_value_mode'])
    {
      add_tags(
        $tag_ids,
        array($params['image_id'])
        );
    }
    else
    {
      return new PwgError(
        500,
        '[ws_images_setInfo]'
        .' invalid parameter multiple_value_mode "'.$params['multiple_value_mode'].'"'
        .', possible values are {replace, append}.'
        );
    }
  }

  invalidate_user_cache();
}

function ws_images_delete($params, &$service)
{
  global $conf;
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }

  if (empty($params['pwg_token']) or get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  $params['image_id'] = preg_split(
    '/[\s,;\|]/',
    $params['image_id'],
    -1,
    PREG_SPLIT_NO_EMPTY
    );
  $params['image_id'] = array_map('intval', $params['image_id']);

  $image_ids = array();
  foreach ($params['image_id'] as $image_id)
  {
    if ($image_id > 0)
    {
      array_push($image_ids, $image_id);
    }
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
  delete_elements($image_ids, true);
  invalidate_user_cache();
}

function ws_add_image_category_relations($image_id, $categories_string, $replace_mode=false)
{
  // let's add links between the image and the categories
  //
  // $params['categories'] should look like 123,12;456,auto;789 which means:
  //
  // 1. associate with category 123 on rank 12
  // 2. associate with category 456 on automatic rank
  // 3. associate with category 789 on automatic rank
  $cat_ids = array();
  $rank_on_category = array();
  $search_current_ranks = false;

  $tokens = explode(';', $categories_string);
  foreach ($tokens as $token)
  {
    @list($cat_id, $rank) = explode(',', $token);

    if (!preg_match('/^\d+$/', $cat_id))
    {
      continue;
    }

    array_push($cat_ids, $cat_id);

    if (!isset($rank))
    {
      $rank = 'auto';
    }
    $rank_on_category[$cat_id] = $rank;

    if ($rank == 'auto')
    {
      $search_current_ranks = true;
    }
  }

  $cat_ids = array_unique($cat_ids);

  if (count($cat_ids) == 0)
  {
    return new PwgError(
      500,
      '[ws_add_image_category_relations] there is no category defined in "'.$categories_string.'"'
      );
  }

  $query = '
SELECT
    id
  FROM '.CATEGORIES_TABLE.'
  WHERE id IN ('.implode(',', $cat_ids).')
;';
  $db_cat_ids = array_from_query($query, 'id');

  $unknown_cat_ids = array_diff($cat_ids, $db_cat_ids);
  if (count($unknown_cat_ids) != 0)
  {
    return new PwgError(
      500,
      '[ws_add_image_category_relations] the following categories are unknown: '.implode(', ', $unknown_cat_ids)
      );
  }

  $to_update_cat_ids = array();

  // in case of replace mode, we first check the existing associations
  $query = '
SELECT
    category_id
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE image_id = '.$image_id.'
;';
  $existing_cat_ids = array_from_query($query, 'category_id');

  if ($replace_mode)
  {
    $to_remove_cat_ids = array_diff($existing_cat_ids, $cat_ids);
    if (count($to_remove_cat_ids) > 0)
    {
      $query = '
DELETE
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE image_id = '.$image_id.'
    AND category_id IN ('.implode(', ', $to_remove_cat_ids).')
;';
      pwg_query($query);
      update_category($to_remove_cat_ids);
    }
  }

  $new_cat_ids = array_diff($cat_ids, $existing_cat_ids);
  if (count($new_cat_ids) == 0)
  {
    return true;
  }

  if ($search_current_ranks)
  {
    $query = '
SELECT
    category_id,
    MAX(rank) AS max_rank
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE rank IS NOT NULL
    AND category_id IN ('.implode(',', $new_cat_ids).')
  GROUP BY category_id
;';
    $current_rank_of = simple_hash_from_query(
      $query,
      'category_id',
      'max_rank'
      );

    foreach ($new_cat_ids as $cat_id)
    {
      if (!isset($current_rank_of[$cat_id]))
      {
        $current_rank_of[$cat_id] = 0;
      }

      if ('auto' == $rank_on_category[$cat_id])
      {
        $rank_on_category[$cat_id] = $current_rank_of[$cat_id] + 1;
      }
    }
  }

  $inserts = array();

  foreach ($new_cat_ids as $cat_id)
  {
    array_push(
      $inserts,
      array(
        'image_id' => $image_id,
        'category_id' => $cat_id,
        'rank' => $rank_on_category[$cat_id],
        )
      );
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
  mass_inserts(
    IMAGE_CATEGORY_TABLE,
    array_keys($inserts[0]),
    $inserts
    );

  update_category($new_cat_ids);
}

function ws_categories_setInfo($params, &$service)
{
  global $conf;
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }

  // category_id
  // name
  // comment

  $params['category_id'] = (int)$params['category_id'];
  if ($params['category_id'] <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid category_id");
  }

  // database registration
  $update = array(
    'id' => $params['category_id'],
    );

  $info_columns = array(
    'name',
    'comment',
    );

  $perform_update = false;
  foreach ($info_columns as $key)
  {
    if (isset($params[$key]))
    {
      $perform_update = true;
      $update[$key] = $params[$key];
    }
  }

  if ($perform_update)
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    mass_updates(
      CATEGORIES_TABLE,
      array(
        'primary' => array('id'),
        'update'  => array_diff(array_keys($update), array('id'))
        ),
      array($update)
      );
  }

}

function ws_categories_setRepresentative($params, &$service)
{
  global $conf;

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }

  // category_id
  // image_id

  $params['category_id'] = (int)$params['category_id'];
  if ($params['category_id'] <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid category_id");
  }

  // does the category really exist?
  $query='
SELECT
    *
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$params['category_id'].'
;';
  $row = pwg_db_fetch_assoc(pwg_query($query));
  if ($row == null)
  {
    return new PwgError(404, "category_id not found");
  }

  $params['image_id'] = (int)$params['image_id'];
  if ($params['image_id'] <= 0)
  {
    return new PwgError(WS_ERR_INVALID_PARAM, "Invalid image_id");
  }

  // does the image really exist?
  $query='
SELECT
    *
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$params['image_id'].'
;';

  $row = pwg_db_fetch_assoc(pwg_query($query));
  if ($row == null)
  {
    return new PwgError(404, "image_id not found");
  }

  // apply change
  $query = '
UPDATE '.CATEGORIES_TABLE.'
  SET representative_picture_id = '.$params['image_id'].'
  WHERE id = '.$params['category_id'].'
;';
  pwg_query($query);

  $query = '
UPDATE '.USER_CACHE_CATEGORIES_TABLE.'
  SET user_representative_picture_id = NULL
  WHERE cat_id = '.$params['category_id'].'
;';
  pwg_query($query);
}

function ws_categories_delete($params, &$service)
{
  global $conf;
  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }

  if (empty($params['pwg_token']) or get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  $modes = array('no_delete', 'delete_orphans', 'force_delete');
  if (!in_array($params['photo_deletion_mode'], $modes))
  {
    return new PwgError(
      500,
      '[ws_categories_delete]'
      .' invalid parameter photo_deletion_mode "'.$params['photo_deletion_mode'].'"'
      .', possible values are {'.implode(', ', $modes).'}.'
      );
  }

  $params['category_id'] = preg_split(
    '/[\s,;\|]/',
    $params['category_id'],
    -1,
    PREG_SPLIT_NO_EMPTY
    );
  $params['category_id'] = array_map('intval', $params['category_id']);

  $category_ids = array();
  foreach ($params['category_id'] as $category_id)
  {
    if ($category_id > 0)
    {
      array_push($category_ids, $category_id);
    }
  }

  if (count($category_ids) == 0)
  {
    return;
  }

  $query = '
SELECT id
  FROM '.CATEGORIES_TABLE.'
  WHERE id IN ('.implode(',', $category_ids).')
;';
  $category_ids = array_from_query($query, 'id');

  if (count($category_ids) == 0)
  {
    return;
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
  delete_categories($category_ids, $params['photo_deletion_mode']);
  update_global_rank();
}

function ws_categories_move($params, &$service)
{
  global $conf, $page;

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (!$service->isPost())
  {
    return new PwgError(405, "This method requires HTTP POST");
  }

  if (empty($params['pwg_token']) or get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  $params['category_id'] = preg_split(
    '/[\s,;\|]/',
    $params['category_id'],
    -1,
    PREG_SPLIT_NO_EMPTY
    );
  $params['category_id'] = array_map('intval', $params['category_id']);

  $category_ids = array();
  foreach ($params['category_id'] as $category_id)
  {
    if ($category_id > 0)
    {
      array_push($category_ids, $category_id);
    }
  }

  if (count($category_ids) == 0)
  {
    return new PwgError(403, 'Invalid category_id input parameter, no category to move');
  }

  // we can't move physical categories
  $categories_in_db = array();

  $query = '
SELECT
    id,
    name,
    dir
  FROM '.CATEGORIES_TABLE.'
  WHERE id IN ('.implode(',', $category_ids).')
;';
  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {
    $categories_in_db[$row['id']] = $row;
    // we break on error at first physical category detected
    if (!empty($row['dir']))
    {
      $row['name'] = strip_tags(
        trigger_event(
          'render_category_name',
          $row['name'],
          'ws_categories_move'
          )
        );

      return new PwgError(
        403,
        sprintf(
          'Category %s (%u) is not a virtual category, you cannot move it',
          $row['name'],
          $row['id']
          )
        );
    }
  }

  if (count($categories_in_db) != count($category_ids))
  {
    $unknown_category_ids = array_diff($category_ids, array_keys($categories_in_db));

    return new PwgError(
      403,
      sprintf(
        'Category %u does not exist',
        $unknown_category_ids[0]
        )
      );
  }

  // does this parent exists? This check should be made in the
  // move_categories function, not here
  //
  // 0 as parent means "move categories at gallery root"
  if (!is_numeric($params['parent']))
  {
    return new PwgError(403, 'Invalid parent input parameter');
  }

  if (0 != $params['parent']) {
    $params['parent'] = intval($params['parent']);
    $subcat_ids = get_subcat_ids(array($params['parent']));
    if (count($subcat_ids) == 0)
    {
      return new PwgError(403, 'Unknown parent category id');
    }
  }

  $page['infos'] = array();
  $page['errors'] = array();
  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
  move_categories($category_ids, $params['parent']);
  invalidate_user_cache();

  if (count($page['errors']) != 0)
  {
    return new PwgError(403, implode('; ', $page['errors']));
  }
}

function ws_logfile($string)
{
  global $conf;

  if (!$conf['ws_enable_log']) {
    return true;
  }

  file_put_contents(
    $conf['ws_log_filepath'],
    '['.date('c').'] '.$string."\n",
    FILE_APPEND
    );
}

function ws_images_checkUpload($params, &$service)
{
  global $conf;

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');
  $ret['message'] = ready_for_upload_message();
  $ret['ready_for_upload'] = true;

  if (!empty($ret['message']))
  {
    $ret['ready_for_upload'] = false;
  }

  return $ret;
}

function ws_plugins_getList($params, &$service)
{
  global $conf;

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/plugins.class.php');
  $plugins = new plugins();
  $plugins->sort_fs_plugins('name');
  $plugin_list = array();

  foreach($plugins->fs_plugins as $plugin_id => $fs_plugin)
  {
    if (isset($plugins->db_plugins_by_id[$plugin_id]))
    {
      $state = $plugins->db_plugins_by_id[$plugin_id]['state'];
    }
    else
    {
      $state = 'uninstalled';
    }

    array_push(
      $plugin_list,
      array(
        'id' => $plugin_id,
        'name' => $fs_plugin['name'],
        'version' => $fs_plugin['version'],
        'state' => $state,
        'description' => $fs_plugin['description'],
        )
      );
  }

  return $plugin_list;
}

function ws_plugins_performAction($params, &$service)
{
  global $template;

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (empty($params['pwg_token']) or get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  define('IN_ADMIN', true);
  include_once(PHPWG_ROOT_PATH.'admin/include/plugins.class.php');
  $plugins = new plugins();
  $errors = $plugins->perform_action($params['action'], $params['plugin']);


  if (!empty($errors))
  {
    return new PwgError(500, $errors);
  }
  else
  {
    if (in_array($params['action'], array('activate', 'deactivate')))
    {
      $template->delete_compiled_templates();
    }
    return true;
  }
}

function ws_themes_performAction($params, &$service)
{
  global $template;

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  if (empty($params['pwg_token']) or get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  define('IN_ADMIN', true);
  include_once(PHPWG_ROOT_PATH.'admin/include/themes.class.php');
  $themes = new themes();
  $errors = $themes->perform_action($params['action'], $params['theme']);

  if (!empty($errors))
  {
    return new PwgError(500, $errors);
  }
  else
  {
    if (in_array($params['action'], array('activate', 'deactivate')))
    {
      $template->delete_compiled_templates();
    }
    return true;
  }
}

function ws_extensions_update($params, &$service)
{
  if (!is_webmaster())
  {
    return new PwgError(401, l10n('Webmaster status is required.'));
  }

  if (empty($params['pwg_token']) or get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  if (empty($params['type']) or !in_array($params['type'], array('plugins', 'themes', 'languages')))
  {
    return new PwgError(403, "invalid extension type");
  }

  if (empty($params['id']) or empty($params['revision']))
  {
    return new PwgError(null, 'Wrong parameters');
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
  include_once(PHPWG_ROOT_PATH.'admin/include/'.$params['type'].'.class.php');

  $type = $params['type'];
  $extension_id = $params['id'];
  $revision = $params['revision'];

  $extension = new $type();

  if ($type == 'plugins')
  {
    if (isset($extension->db_plugins_by_id[$extension_id]) and $extension->db_plugins_by_id[$extension_id]['state'] == 'active')
    {
      $extension->perform_action('deactivate', $extension_id);

      redirect(PHPWG_ROOT_PATH
        . 'ws.php'
        . '?method=pwg.extensions.update'
        . '&type=plugins'
        . '&id=' . $extension_id
        . '&revision=' . $revision
        . '&reactivate=true'
        . '&pwg_token=' . get_pwg_token()
        . '&format=json'
      );
    }

    $upgrade_status = $extension->extract_plugin_files('upgrade', $revision, $extension_id);
    $extension_name = $extension->fs_plugins[$extension_id]['name'];

    if (isset($params['reactivate']))
    {
      $extension->perform_action('activate', $extension_id);
    }
  }
  elseif ($type == 'themes')
  {
    $upgrade_status = $extension->extract_theme_files('upgrade', $revision, $extension_id);
    $extension_name = $extension->fs_themes[$extension_id]['name'];
  }
  elseif ($type == 'languages')
  {
    $upgrade_status = $extension->extract_language_files('upgrade', $revision, $extension_id);
    $extension_name = $extension->fs_languages[$extension_id]['name'];
  }

  global $template;
  $template->delete_compiled_templates();

  switch ($upgrade_status)
  {
    case 'ok':
      return sprintf(l10n('%s has been successfully updated.'), $extension_name);

    case 'temp_path_error':
      return new PwgError(null, l10n('Can\'t create temporary file.'));

    case 'dl_archive_error':
      return new PwgError(null, l10n('Can\'t download archive.'));

    case 'archive_error':
      return new PwgError(null, l10n('Can\'t read or extract archive.'));

    default:
      return new PwgError(null, sprintf(l10n('An error occured during extraction (%s).'), $upgrade_status));
  }
}

function ws_extensions_ignoreupdate($params, &$service)
{
  global $conf;

  define('IN_ADMIN', true);
  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

  if (!is_webmaster())
  {
    return new PwgError(401, 'Access denied');
  }

  if (empty($params['pwg_token']) or get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  $conf['updates_ignored'] = unserialize($conf['updates_ignored']);

  // Reset ignored extension
  if ($params['reset'])
  {
    if (!empty($params['type']) and isset($conf['updates_ignored'][$params['type']]))
    {
      $conf['updates_ignored'][$params['type']] = array();
    }
    else
    {
      $conf['updates_ignored'] = array(
        'plugins'=>array(),
        'themes'=>array(),
        'languages'=>array()
      );
    }
    conf_update_param('updates_ignored', pwg_db_real_escape_string(serialize($conf['updates_ignored'])));
    unset($_SESSION['extensions_need_update']);
    return true;
  }

  if (empty($params['id']) or empty($params['type']) or !in_array($params['type'], array('plugins', 'themes', 'languages')))
  {
    return new PwgError(403, 'Invalid parameters');
  }

  // Add or remove extension from ignore list
  if (!in_array($params['id'], $conf['updates_ignored'][$params['type']]))
  {
    array_push($conf['updates_ignored'][$params['type']], $params['id']);
  }
  conf_update_param('updates_ignored', pwg_db_real_escape_string(serialize($conf['updates_ignored'])));
  unset($_SESSION['extensions_need_update']);
  return true;
}

function ws_extensions_checkupdates($params, &$service)
{
  global $conf;

  define('IN_ADMIN', true);
  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
  include_once(PHPWG_ROOT_PATH.'admin/include/updates.class.php');
  $update = new updates();

  if (!is_admin())
  {
    return new PwgError(401, 'Access denied');
  }

  $result = array();

  if (!isset($_SESSION['need_update']))
    $update->check_piwigo_upgrade();

  $result['piwigo_need_update'] = $_SESSION['need_update'];

  $conf['updates_ignored'] = unserialize($conf['updates_ignored']);

  if (!isset($_SESSION['extensions_need_update']))
    $update->check_extensions();
  else
    $update->check_updated_extensions();

  if (!is_array($_SESSION['extensions_need_update']))
    $result['ext_need_update'] = null;
  else
    $result['ext_need_update'] = !empty($_SESSION['extensions_need_update']);

  return $result;
}
?>
