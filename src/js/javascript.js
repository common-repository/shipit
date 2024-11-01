jQuery(document).ready(function() {
  var name = (jQuery('label[for="billing_state"]').text() == 'Municipio o Delegación *') ? 'Municipio o Delegación':'Comunas';
  jQuery('label[for="billing_state"]').text(name).append('&nbsp; <abbr class="required" title="obligatorio">*</abbr>');
  function explode() {
    jQuery('label[for="billing_state"]').text(name).append('&nbsp; <abbr class="required" title="obligatorio">*</abbr>');
  }
  setTimeout(explode, 6000);
});
