<script>
  /* contextual help */
  {{if $enable_context_help}}
  $('.help-content').css('top', '-' + $('#help-content').height() + 'px')
  $(document).mouseup(function (e)
  {
    e.preventDefault;

    var container = $("#help-content");

    if ((!container.is(e.target) // if the target of the click isn't the container...
            && container.has(e.target).length === 0 // ... nor a descendant of the container
            && container.hasClass('help-content-open'))
            ||
            (
                    ($('#help_nav_btn, #help_nav_btn_collapsed').is(e.target) || $('#help_nav_btn, #help_nav_btn_collapsed').has(e.target).length !== 0)
                    && container.hasClass('help-content-open')
                    )) {
      container.removeClass('help-content-open');
      $('main').removeClass('help-content-open');
      $('main').css('top', '')
    }
    else if (($('#help_nav_btn, #help_nav_btn_collapsed').is(e.target) || $('#help_nav_btn, #help_nav_btn_collapsed').has(e.target).length !== 0)
            && !container.hasClass('help-content-open')) {
      $('#help-content').addClass('help-content-open');
      $('main').removeClass('help-content-open');
      var mainTop = $('#navbar-collapse-1').height();
      if ($('#navbar-collapse-1').outerHeight(true) < $('#help-content').height()) {
        mainTop = $('#help-content').outerHeight(true);
      }

      $('main').css('top', mainTop + 'px');
    }

  });
  {{/if}}
  var contextualHelpFocus = function (target, openSidePanel) {
    if (openSidePanel) {
      $("main").addClass('region_1-on');  // Open the side panel to highlight element 
    } else {
      $("main").removeClass('region_1-on');
    }
    // Animate the page scroll to the element and then pulse the element to direct attention
    $('html,body').animate({scrollTop: $(target).offset().top - $('#navbar-collapse-1').height() - $('#help-content').height() - 50}, 'slow');
    for (i = 0; i < 3; i++) {
      $(target).fadeTo('slow', 0.1).fadeTo('slow', 1.0);
    }
  }
</script>
