/**
 * @file
 * Provides admin utilities.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.accessUnpublishedAdmin = {
    attach: function (context) {

      new Clipboard('.clipboard-button');

      $('.clipboard-button').click(function (event) {
        event.preventDefault();
      });
    }
  };
})(jQuery, Drupal);
