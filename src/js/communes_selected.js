jQuery(document).ready(function() {
  if (jQuery('select[multiple]').length) {
    jQuery('select[multiple]').multiselect({
      columns: 1,
      search: true,
      selectAll: true,
      texts: {
          placeholder: 'Seleccione comunas',
          search: 'Seleccione comunas'
      }
    });
  }
  
  jQuery('#ms-list-1, #ms-list-2, #ms-list-3').css('width', '300px');
  
  if (jQuery("li a:contains('Shipit')").length === 2) {
    jQuery("li a:contains('Shipit')").eq(1).remove();
  }

});
