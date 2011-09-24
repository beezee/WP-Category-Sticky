jQuery(document).ready(function($){
  $.each(elements, function(index, value)  
  {  
    $(value).multiselect({sortable: false, searchable: true});
  });
});