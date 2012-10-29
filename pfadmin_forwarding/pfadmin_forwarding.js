/* Forwarding interface (tab) */

if (window.rcmail) {
    rcmail.addEventListener('init', function(evt) {
    var tab = $('<span>').attr('id', 'settingstabpluginforwarding').addClass('tablink');
    
    var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.pfadmin_forwarding').html(rcmail.gettext('Aliasy')).appendTo(tab);
                
    button.bind('click', function(e){ return rcmail.command('plugin.pfadmin_forwarding', this) });
    
    rcmail.add_element(tab, 'tabs');
    
    rcmail.register_command('plugin.pfadmin_forwarding', function() { rcmail.goto_url('plugin.pfadmin_forwarding') }, true);
    
    rcmail.register_command('plugin.pfadmin_forwarding-save', function() { 
    var input_address = $("[name='_forwardingaddress']");
    var input_enabled = rcube_find_object('_forwardingenabled');   
    document.forms.forwardingform.submit();      
    }, true);
  })
}
