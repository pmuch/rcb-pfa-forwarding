<?php

/**
 * Change Postfixadmin Forwarding
 *
 * Plugin that gives access to Forwarding using Postfixadmin database
 *
 * @version 1.0 - 10.09.2009
 * @author Pawel Muszynski
 * @licence GNU GPL
 *
 * Requirements: Postfixadmin
 *
 * 
 * Changelog
 *
 * 2.0 - 2012.10.29 - Larry skin compatible
 * 1.0 - 2009.07.31 - Initial Release
 **/
 
/** USAGE
 *
 * #1- Configure "pfadmin_forwarding/config/config.inc.php".
 * #2- Register plugin ("./config/main.inc.php ::: $rcmail_config['plugins']").
 *
 **/

require_once('plugins/pfadmin_forwarding/pfadmin_functions.php');

class pfadmin_forwarding extends rcube_plugin
{
  public $task = 'settings';
  public $EMAIL_ADDRESS_PATTERN = '([a-z0-9][a-z0-9\-\.\+\_]*@[a-z0-9]([a-z0-9\-][.]?)*[a-z0-9]\\.[a-z]{2,5})';
  private $sql_select = 'SELECT * FROM alias WHERE address = %u LIMIT 1;';
  private $sql_update = 'UPDATE alias SET goto = %a  WHERE address = %u LIMIT 1;';
         
  function init()
  {
    $this->_load_config();
    $this->register_action('plugin.pfadmin_forwarding', array($this, 'pfadmin_forwarding_init'));
    $this->register_action('plugin.pfadmin_forwarding-save', array($this, 'pfadmin_forwarding_save'));
    $this->register_handler('plugin.pfadmin_forwarding_form', array($this, 'pfadmin_forwarding_form'));
    $this->include_script('pfadmin_forwarding.js');
  }
  
  function _load_config()
  {
    $rcmail = rcmail::get_instance();
    $config = "plugins/pfadmin_forwarding/config/config.inc.php";
    if(file_exists($config))
      include $config;
    if(is_array($rcmail_config)){
      $arr = array_merge($rcmail->config->all(),$rcmail_config);
      $rcmail->config->merge($arr);
    }
  }
   
  function pfadmin_forwarding_init()
  {
    $this->add_texts('localization/');
    $this->register_handler('plugin.body', array($this, 'pfadmin_forwarding_form'));

    $rcmail = rcmail::get_instance();
    $rcmail->output->set_pagetitle($this->gettext('forwarding')); 
    $rcmail->output->send('plugin');
    
  }
  
  function pfadmin_forwarding_save()
  {
    
    $rcmail = rcmail::get_instance();
    $user = strtolower($rcmail->user->data['username']);
    
    $keepcopies   = get_input_value('_keepcopies', RCUBE_INPUT_POST);
    if(!$keepcopies)
      $keepcopies = 0;    
    $address      = strtolower(get_input_value('_forwardingaddress', RCUBE_INPUT_POST));
    $order   = array("\r\n", "\n", "\r");
    $address = str_replace($order,",", $address);
    $address_a = array();
    $address_a = explode(",",$address);
    if ($keepcopies) {
      $address_a[] = $user;
    }
    
    $this->add_texts('localization/');    
    if (!($res = $this->_save($user,$keepcopies,$address_a))) {
      if(isset($_SESSION['dnsblacklisted']) && $_SESSION['dnsblacklisted'] != 'pass'){
	$this->add_texts('../dnsbl/localization/');
        $rcmail->output->command('display_message',sprintf(rcube_label('dnsblacklisted', 'pfadmin_forwarding'),$_SESSION['clientip']),'error');        
      }
      else{
          $rcmail->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
        }
      }

      if (!$rcmail->config->get('db_persistent')) {
        if ($dsn = $rcmail->config->get('db_dsnw')) {
          $rcmail->db = new rcube_mdb2($dsn, '', FALSE);    
        }
      }
    $this->pfadmin_forwarding_init();
  
  }

  function pfadmin_forwarding_form()
  {
    $rcmail = rcmail::get_instance();

    // add some labels to client
    $rcmail->output->add_label(
      'pfadmin_forwarding.forwarding',    
      'pfadmin_forwarding.invalidaddress',
      'pfadmin_forwarding.forwardingloop'
    );
    
    $rcmail->output->add_script("var settings_account=true;");  

    $settings = $this->_get();
    $address     = str_replace(',',"\n",$settings['goto']);
    $address_a = array();
    $address_a = explode("\n", $address);
    $keepcopies  = in_array($rcmail->user->data['username'], $address_a);
    
    if ($keepcopies) {
      $address_a = array_diff($address_a, array($rcmail->user->data['username']));
    }
    $address = implode("\n", $address_a);
    $rcmail->output->set_env('product_name', $rcmail->config->get('product_name'));

    // allow the following attributes to be added to the <table> tag
    $attrib_str = create_attrib_string($attrib, array('style', 'class', 'id', 'cellpadding', 'cellspacing', 'border', 'summary'));

    // return the complete edit form as table
    //
    //
    $table = new html_table(array('cols' => 2));
    $field_id = 'forwardingaddress';
    $input_forwardingaddress = new html_textarea(array('name' => '_forwardingaddress', 'id' => $field_id, 'value' => $address, 'cols' => 60, 'rows' => 5));
    
    $table->add('title', html::label($field_id, rep_specialchars_output($this->gettext('forwardingaddress'))));
    $table->add(null, $input_forwardingaddress->show($enabled));


    $field_id = 'keepcopies';
    $input_keepcopies = new html_checkbox(array('name' => '_keepcopies', 'id' => $field_id, 'value' => 1));

    $table->add('title', html::label($field_id, rep_specialchars_output($this->gettext('keepcopies'))));
    $table->add(null, $input_keepcopies->show($keepcopies));
    $out = html::div(array('class' => 'box'),
	html::div(array('id' => 'prefs-title', 'class' => 'boxtitle'), $this->gettext('forwarding')) .
	html::div(array('class' => 'boxcontent'), $table->show() .
	html::p(null,
		$rcmail->output->button(array(
			'command' => 'plugin.pfadmin_forwarding-save',
			'type' => 'input',
			'class' => 'button mainaction',
			'label' => 'save'
    	)))));
   
    $rcmail->output->add_gui_object('forwardingform', 'forwarding-form');
    
    return $rcmail->output->form_tag(array(
    	'id' => 'forwardingform',
	'name' => 'forwardingform',
	'method' => 'post',
	'action' => './?_task=settings&_action=plugin.pfadmin_forwarding-save',
    ), $out);

  }
 
  private function _get()
  {
    $rcmail = rcmail::get_instance();
      
    $sql = $this->sql_select;

    if ($dsn = $rcmail->config->get('db_pfadmin_forwarding_dsn')) {
      $db = new rcube_mdb2($dsn, '', FALSE);
      $db->set_debug((bool)$rcmail->config->get('sql_debug'));
      $db->db_connect('r');
    } else {
      die("FATAL ERROR ::: RoundCube Plugin ::: pfadmin_forwarding ::: \$rcmail_config['db_pfadmin_forwarding_dsn'] undefined !!! ==> die");
    }
    if ($err = $db->is_error())
      return $err;
      
    $sql = str_replace('%u', $db->quote($rcmail->user->data['username'],'text'), $sql);
    $res = $db->query($sql);
    if ($err = $db->is_error()){
       return $err;
    }
    $ret = $db->fetch_assoc($res);
    if (!$rcmail->config->get('db_persistent')) {
      if ($dsn = $rcmail->config->get('db_dsnw')) {
        $rcmail->db = new rcube_mdb2($dsn, '', FALSE);    
      }
    }
    return $ret;  
  }

  private function _save($user,$keepcopies,$address)
  {
    $cfg = rcmail::get_instance()->config;
    
    if ($dsn = $cfg->get('db_pfadmin_forwarding_dsn')) {
      $db = new rcube_mdb2($dsn, '', FALSE);
      $db->set_debug((bool)$cfg->get('sql_debug'));
      $db->db_connect('w');
    } else {
      die("FATAL ERROR ::: RoundCube Plugin ::: pfadmin_forwarding ::: \$rcmail_config['db_pfadmin_forwarding_dsn'] undefined !!! ==> die");
    }
    if ($err = $db->is_error())
      return $err;
    $sql = $this->sql_update;
    
    $aliasy =  removeempty2(implode(",",$address));
    $sql = str_replace('%a',  $db->quote($aliasy,'text'), $sql);            
    $sql = str_replace('%k',  $db->quote($keepcopies,'text'), $sql);    
    $sql = str_replace('%u',  $db->quote($user,'text'), $sql);
     
    $res = $db->query($sql);
    
    if ($err = $db->is_error())
      return $err;
    $res = $db->affected_rows($res);
    if ($res == 0) return $this->gettext('errorsaving');
    if ($res == 1) return FALSE; // THis is the good case - 1 row updated
    return $this->gettext('internalerror');

  }
}

?>
