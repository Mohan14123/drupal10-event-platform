/**
 * @file
 * JavaScript for the registration form.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Registration form behaviors.
   */
  Drupal.behaviors.eventRegistrationForm = {
    attach: function (context, settings) {
      // Initialize form enhancements.
      $(once('registration-form', '#event-registration-form', context)).each(function () {
        var $form = $(this);
        
        // Add loading indicators for AJAX.
        $form.find('select[name="category"]').on('change', function () {
          $form.find('#event-date-wrapper').addClass('ajax-loading');
          $form.find('#event-name-wrapper').addClass('ajax-loading');
        });
        
        $form.find('select[name="event_date"]').on('change', function () {
          $form.find('#event-name-wrapper').addClass('ajax-loading');
        });
        
        // Remove loading class after AJAX completes.
        $(document).ajaxComplete(function () {
          $form.find('.ajax-loading').removeClass('ajax-loading');
        });
      });
    }
  };

})(jQuery, Drupal);
