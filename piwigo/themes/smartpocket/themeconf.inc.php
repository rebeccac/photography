<?php
/*
Theme Name: Smart Pocket
Version: 2.5.1
Description: Mobile theme.
Theme URI: http://piwigo.org/ext/extension_view.php?eid=599
Author: P@t
Author URI: http://piwigo.org
*/

$themeconf = array(
  'mobile' => true,
);

// Redirect if page is not compatible with mobile theme
if (!in_array(script_basename(), array('index', 'register', 'profile', 'identification', 'ws', 'admin')))
  redirect(duplicate_index_url());

//Retrive all pictures on thumbnails page
add_event_handler('loc_index_thumbnails_selection', 'sp_select_all_thumbnails');

function sp_select_all_thumbnails($selection)
{
  global $page, $template;

  $template->assign('page_selection', array_flip($selection));

  return $page['items'];
}

// Retrive all categories on thumbnails page
add_event_handler('loc_end_index_category_thumbnails', 'sp_select_all_categories');

function sp_select_all_categories($selection)
{
  global $tpl_thumbnails_var;
  return $tpl_thumbnails_var;
}

// Get better derive parameters for screen size
$type = IMG_LARGE;
if (!empty($_COOKIE['screen_size']))
{
  $screen_size = explode('x', $_COOKIE['screen_size']);
  $derivative_params = new ImageStdParams;
  $derivative_params->load_from_db();

  foreach ($derivative_params->get_all_type_map() as $type => $map)
  {
    if (max($map->sizing->ideal_size) >= max($screen_size) and min($map->sizing->ideal_size) >= min($screen_size))
      break;
  }
}

$this->assign('picture_derivative_params', ImageStdParams::get_by_type($type));
$this->assign('thumbnail_derivative_params', ImageStdParams::get_by_type(IMG_SQUARE));

?>
