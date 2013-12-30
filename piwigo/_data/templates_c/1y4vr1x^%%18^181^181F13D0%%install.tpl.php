<?php /* Smarty version 2.6.26, created on 2013-12-19 02:55:52
         compiled from install.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'combine_css', 'install.tpl', 13, false),array('function', 'get_combined_scripts', 'install.tpl', 22, false),array('function', 'combine_script', 'install.tpl', 25, false),array('function', 'html_options', 'install.tpl', 215, false),array('block', 'footer_script', 'install.tpl', 143, false),array('modifier', 'translate', 'install.tpl', 154, false),array('modifier', 'htmlspecialchars', 'install.tpl', 283, false),array('modifier', 'nl2br', 'install.tpl', 283, false),array('modifier', 'sprintf', 'install.tpl', 283, false),)), $this); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">
<html lang="<?php echo $this->_tpl_vars['lang_info']['code']; ?>
" dir="<?php echo $this->_tpl_vars['lang_info']['direction']; ?>
">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $this->_tpl_vars['T_CONTENT_ENCODING']; ?>
">
<meta http-equiv="Content-script-type" content="text/javascript">
<meta http-equiv="Content-Style-Type" content="text/css">
<link rel="shortcut icon" type="image/x-icon" href="<?php echo $this->_tpl_vars['ROOT_URL']; ?>
<?php echo $this->_tpl_vars['themeconf']['icon_dir']; ?>
/favicon.ico">

<?php echo '<!-- COMBINED_CSS -->' ?>
<?php $_from = $this->_tpl_vars['themes']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['theme']):
?>
<?php if ($this->_tpl_vars['theme']['load_css']): ?>
<?php echo $this->_plugins['function']['combine_css'][0][0]->func_combine_css(array('path' => "admin/themes/".($this->_tpl_vars['theme']['id'])."/theme.css",'order' => -10), $this);?>

<?php endif; ?>
<?php endforeach; endif; unset($_from); ?>

<!--[if IE 7]>
  <link rel="stylesheet" type="text/css" href="<?php echo $this->_tpl_vars['ROOT_URL']; ?>
admin/themes/default/fix-ie7.css">
<![endif]-->

<!-- BEGIN get_combined_scripts -->
<?php echo $this->_plugins['function']['get_combined_scripts'][0][0]->func_get_combined_scripts(array('load' => 'header'), $this);?>

<!-- END get_combined_scripts -->

<?php echo $this->_plugins['function']['combine_script'][0][0]->func_combine_script(array('id' => 'jquery','path' => 'themes/default/js/jquery.min.js'), $this);?>

<?php echo '
<script type="text/javascript">
$(function() {
$(document).ready(function() {
  $("a.externalLink").click(function() {
    window.open($(this).attr("href"));
    return false;
  });

  $("#admin_mail").keyup(function() {
    $(".adminEmail").text($(this).val());
  });
});

</script>

<style type="text/css">
body {
  font-size:12px;
}

.content {
 width: 800px;
 margin: auto;
 text-align: center;
 padding:0;
 background-color:transparent !important;
 border:none;
}

#theHeader {
  display: block;
  background:url("admin/themes/clear/images/piwigo_logo_big.png") no-repeat scroll center 20px transparent;
  height:100px;
}

fieldset {
  margin-top:20px;
  background-color:#f1f1f1;
}

legend {
  font-weight:bold;
  letter-spacing:2px;
}

.content h2 {
  display:block;
  font-size:20px;
  text-align:center;
  /* margin-top:5px; */
}

table.table2 {
  width: 100%;
  border:0;
}

table.table2 td {
  text-align: left;
  padding: 5px 2px;
}

table.table2 td.fieldname {
  font-weight:normal;
}

table.table2 td.fielddesc {
  padding-left:10px;
  font-style:italic;
}

input[type="submit"], input[type="button"], a.bigButton {
  font-size:14px;
  font-weight:bold;
  letter-spacing:2px;
  border:none;
  background-color:#666666;
  color:#fff;
  padding:5px;
  -moz-border-radius:5px;
  -webkit-border-radius:5px;
  border-radius:5px;
}

input[type="submit"]:hover, input[type="button"]:hover, a.bigButton:hover {
  background-color:#ff7700;
  color:white;
}

input[type="text"], input[type="password"], select {
  background-color:#ddd;
  border:2px solid #ccc;
  -moz-border-radius:5px;
  -webkit-border-radius:5px;
  border-radius:5px;
  padding:2px;
}

input[type="text"]:focus, input[type="password"]:focus, select:focus {
  background-color:#fff;
  border:2px solid #ff7700;
}

.sql_content, .infos a {
  color: #ff3363;
}

.errors {
  padding-bottom:5px;
}

</style>
'; ?>


<?php echo $this->_plugins['function']['combine_script'][0][0]->func_combine_script(array('id' => 'jquery.cluetip','load' => 'async','require' => 'jquery','path' => 'themes/default/js/plugins/jquery.cluetip.js'), $this);?>


<?php $this->_tag_stack[] = array('footer_script', array('require' => 'jquery.cluetip')); $_block_repeat=true;$this->_plugins['block']['footer_script'][0][0]->block_footer_script($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
jQuery().ready(function(){
	jQuery('.cluetip').cluetip({
		width: 300,
		splitTitle: '|',
		positionBy: 'bottomTop'
	});
});
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['footer_script'][0][0]->block_footer_script($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>


<title>Piwigo <?php echo $this->_tpl_vars['RELEASE']; ?>
 - <?php echo Template::mod_translate('Installation'); ?>
</title>
</head>

<body>
<div id="the_page">
<div id="theHeader"></div>
<div id="content" class="content">

<h2><?php echo Template::mod_translate('Version'); ?>
 <?php echo $this->_tpl_vars['RELEASE']; ?>
 - <?php echo Template::mod_translate('Installation'); ?>
</h2>

<?php if (isset ( $this->_tpl_vars['config_creation_failed'] )): ?>
<div class="errors">
  <p style="margin-left:30px;">
    <strong><?php echo Template::mod_translate('Creation of config file local/config/database.inc.php failed.'); ?>
</strong>
  </p>
  <ul>
    <li>
      <p><?php echo Template::mod_translate('You can download the config file and upload it to local/config directory of your installation.'); ?>
</p>
      <p style="text-align:center">
          <input type="button" value="<?php echo Template::mod_translate('Download the config file'); ?>
" onClick="window.open('<?php echo $this->_tpl_vars['config_url']; ?>
');">
      </p>
    </li>
    <li>
      <p><?php echo Template::mod_translate('An alternate solution is to copy the text in the box above and paste it into the file "local/config/database.inc.php" (Warning : database.inc.php must only contain what is in the textarea, no line return or space character)'); ?>
</p>
      <textarea rows="15" cols="70"><?php echo $this->_tpl_vars['config_file_content']; ?>
</textarea>
    </li>
  </ul>
</div>
<?php endif; ?>

<?php if (isset ( $this->_tpl_vars['errors'] )): ?>
<div class="errors">
  <ul>
<?php $_from = $this->_tpl_vars['errors']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['error']):
?>
    <li><?php echo $this->_tpl_vars['error']; ?>
</li>
<?php endforeach; endif; unset($_from); ?>
  </ul>
</div>
<?php endif; ?>

<?php if (isset ( $this->_tpl_vars['infos'] )): ?>
<div class="infos">
  <ul>
<?php $_from = $this->_tpl_vars['infos']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['info']):
?>
    <li><?php echo $this->_tpl_vars['info']; ?>
</li>
<?php endforeach; endif; unset($_from); ?>
  </ul>
</div>
<?php endif; ?>

<?php if (isset ( $this->_tpl_vars['install'] )): ?>
<form method="POST" action="<?php echo $this->_tpl_vars['F_ACTION']; ?>
" name="install_form">

<fieldset>
  <legend><?php echo Template::mod_translate('Basic configuration'); ?>
</legend>

  <table class="table2">
    <tr>
      <td style="width: 30%"><?php echo Template::mod_translate('Default gallery language'); ?>
</td>
      <td>
    <select name="language" onchange="document.location = 'install.php?language='+this.options[this.selectedIndex].value;">
    <?php echo smarty_function_html_options(array('options' => $this->_tpl_vars['language_options'],'selected' => $this->_tpl_vars['language_selection']), $this);?>

    </select>
      </td>
    </tr>
  </table>
</fieldset>

<fieldset>
  <legend><?php echo Template::mod_translate('Database configuration'); ?>
</legend>

  <table class="table2">
    <tr>
      <td style="width: 30%;" class="fieldname"><?php echo Template::mod_translate('Host'); ?>
</td>
      <td><input type="text" name="dbhost" value="<?php echo $this->_tpl_vars['F_DB_HOST']; ?>
"></td>
      <td class="fielddesc"><?php echo Template::mod_translate('localhost, sql.multimania.com, toto.freesurf.fr'); ?>
</td>
    </tr>
    <tr>
      <td class="fieldname"><?php echo Template::mod_translate('User'); ?>
</td>
      <td><input type="text" name="dbuser" value="<?php echo $this->_tpl_vars['F_DB_USER']; ?>
"></td>
      <td class="fielddesc"><?php echo Template::mod_translate('user login given by your host provider'); ?>
</td>
    </tr>
    <tr>
      <td class="fieldname"><?php echo Template::mod_translate('Password'); ?>
</td>
      <td><input type="password" name="dbpasswd" value=""></td>
      <td class="fielddesc"><?php echo Template::mod_translate('user password given by your host provider'); ?>
</td>
    </tr>
    <tr>
      <td class="fieldname"><?php echo Template::mod_translate('Database name'); ?>
</td>
      <td><input type="text" name="dbname" value="<?php echo $this->_tpl_vars['F_DB_NAME']; ?>
"></td>
      <td class="fielddesc"><?php echo Template::mod_translate('also given by your host provider'); ?>
</td>
    </tr>
    <tr>
      <td class="fieldname"><?php echo Template::mod_translate('Database table prefix'); ?>
</td>
      <td><input type="text" name="prefix" value="<?php echo $this->_tpl_vars['F_DB_PREFIX']; ?>
"></td>
      <td class="fielddesc"><?php echo Template::mod_translate('database tables names will be prefixed with it (enables you to manage better your tables)'); ?>
</td>
    </tr>
  </table>

</fieldset>
<fieldset>
  <legend><?php echo Template::mod_translate('Admin configuration'); ?>
</legend>

  <table class="table2">
    <tr>
      <td style="width: 30%;" class="fieldname"><?php echo Template::mod_translate('Webmaster login'); ?>
</td>
      <td><input type="text" name="admin_name" value="<?php echo $this->_tpl_vars['F_ADMIN']; ?>
"></td>
      <td class="fielddesc"><?php echo Template::mod_translate('It will be shown to the visitors. It is necessary for website administration'); ?>
</td>
    </tr>
    <tr>
      <td class="fieldname"><?php echo Template::mod_translate('Webmaster password'); ?>
</td>
      <td><input type="password" name="admin_pass1" value=""></td>
      <td class="fielddesc"><?php echo Template::mod_translate('Keep it confidential, it enables you to access administration panel'); ?>
</td>
    </tr>
    <tr>
      <td class="fieldname"><?php echo Template::mod_translate('Password [confirm]'); ?>
</td>
      <td><input type="password" name="admin_pass2" value=""></td>
      <td class="fielddesc"><?php echo Template::mod_translate('verification'); ?>
</td>
    </tr>
    <tr>
      <td class="fieldname"><?php echo Template::mod_translate('Webmaster mail address'); ?>
</td>
      <td><input type="text" name="admin_mail" id="admin_mail" value="<?php echo $this->_tpl_vars['F_ADMIN_EMAIL']; ?>
"></td>
      <td class="fielddesc"><?php echo Template::mod_translate('Visitors will be able to contact site administrator with this mail'); ?>
</td>
    </tr>
    <tr>
      <td><?php echo Template::mod_translate('Options'); ?>
</options>
      <td colspan="2">
        <label>
          <input type="checkbox" name="newsletter_subscribe"<?php if ($this->_tpl_vars['F_NEWSLETTER_SUBSCRIBE']): ?> checked="checked"<?php endif; ?>>
          <span class="cluetip" title="<?php echo Template::mod_translate('Piwigo Announcements Newsletter'); ?>
|<?php echo smarty_modifier_nl2br(htmlspecialchars(Template::mod_translate('Keep in touch with Piwigo project, subscribe to Piwigo Announcement Newsletter. You will receive emails when a new release is available (sometimes including a security bug fix, it\'s important to know and upgrade) and when major events happen to the project. Only a few emails a year.'))); ?>
"><?php echo sprintf(Template::mod_translate('Subscribe %s to Piwigo Announcements Newsletter'), $this->_tpl_vars['EMAIL']); ?>
</span>
        </label>
        <br>
        <label>
          <input type="checkbox" name="send_password_by_mail" checked="checked">
          <?php echo Template::mod_translate('Send my connection settings by email'); ?>

        </label>
      </td>
    </tr>
  </table>

</fieldset>

  <div style="text-align:center; margin:20px 0 10px 0">
    <input class="submit" type="submit" name="install" value="<?php echo Template::mod_translate('Start Install'); ?>
">
  </div>
</form>
<?php else: ?>
<p>
  <a class="bigButton" href="index.php"><?php echo Template::mod_translate('Visit Gallery'); ?>
</a>
</p>
<?php endif; ?>
</div> <div style="text-align: center"><?php echo $this->_tpl_vars['L_INSTALL_HELP']; ?>
</div>
</div> 
<!-- BEGIN get_combined_scripts -->
<?php echo $this->_plugins['function']['get_combined_scripts'][0][0]->func_get_combined_scripts(array('load' => 'footer'), $this);?>

<!-- END get_combined_scripts -->

</body>
</html>