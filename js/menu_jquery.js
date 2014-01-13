$(document).ready(function(){

$('#mobnav > ul > li > a').click(function() {
  $('#mobnav li').removeClass('active');
  $(this).closest('li').addClass('active');
  var checkElement = $(this).next();
  if((checkElement.is('ul')) && (checkElement.is(':visible'))) {
    $(this).closest('li').removeClass('active');
    checkElement.slideToggle('normal');
  }
  if((checkElement.is('ul')) && (!checkElement.is(':visible'))) {
    $('#mobnav ul ul:visible').slideToggle('normal');
    checkElement.slideToggle('normal');
  }
  if($(this).closest('li').find('ul').children().length === 0) {
    return true;
  } else {
    return false;
  }
});

});

 $(function() {
         var pull       = $('#pull');
            menu     = $('#mobnav > ul');
            $(menu).on('load').hide();
            menuHeight  = menu.height();
         $(pull).on('click', function(e) {
            e.preventDefault();
            menu.slideToggle();
         });

         $(window).resize(function(){
            var w = $(window).width();
            if(w > 320 && menu.is(':hidden')) {
               menu.removeAttr('style');
            }
         });
      });