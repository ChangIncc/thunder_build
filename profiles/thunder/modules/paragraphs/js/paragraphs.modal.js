/**
 * @file paragraphs.modal.js
 *
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Method to apply data on template element.
   *
   * For more information about template please take a look at documentation
   * provided in: templates/paragraphs-add-dialog.html.twig
   *
   * @param {Object} $templateElement
   *   jQuery element for template element.
   * @param {Object} data
   *   Data object used to apply on template.
   *
   * @private
   */
  var _templateApplyData = function ($templateElement, data) {
    $.each(data, function (name, value) {
      var selector = '[data-' + name + ']';

      $templateElement.find(selector)
        .addBack(selector)
        .each(function (index, childElement) {
          var $child = $(childElement);
          var attrName = $child.attr('data-' + name);
          $child.removeAttr('data-' + name);

          if (attrName === 'content') {
            $child.text(value);
          }
          else {
            $child.attr(attrName, value);
          }
        });
    });
  };

  /**
   * Method to clone template and apply data on it.
   *
   * @param {Object} $template
   *   jQuery element for template element used for cloning.
   * @param {Object} data
   *   Data object used to apply on template.
   *
   * @return {Object}
   *   Returns cloned template with applied data on it.
   *
   * @private
   */
  var _templateClone = function ($template, data) {
    var $templateClone = $template
      .clone()
      .appendTo($template.parent());

    // Adjust all CSS classes with suffix "-template".
    var classEnding = '-template';
    $.each(
      $templateClone.attr('class').toString().split(' '),
      function (index, className) {
        if (className.substr(-classEnding.length) === classEnding) {
          var newClassName = className.substr(0, className.length - classEnding.length);
          $templateClone
            .removeClass(className)
            .toggleClass(newClassName);
        }
      }
    );

    _templateApplyData($templateClone, data);

    return $templateClone;
  };

  /**
   * Click handler for click "+ Add" button between paragraphs.
   *
   * @type {Object}
   */
  Drupal.behaviors.modalAdd = {
    attach: function (context) {
      $('.paragraph-type-add-modal-button', context)
        .once('add-click-handler')
        .on('click', function (event) {
          var $button = $(this);

          // Stop default execution of click event.
          event.preventDefault();
          event.stopPropagation();

          // Global data for dialog template.
          // delta: '' -> means that paragraph will be added to end of list.
          var config = {delta: ''};

          // Get wrapper of elements used for submitting of add paragraph
          // functionality. It's also used as context for opening of dialog.
          var $addMoreWrapper;
          if ($button.attr('name') === 'first_button_add_modal') {
            // Case for last button in list (of for empty list).
            $addMoreWrapper = $button
              .parent()
              .siblings('.js-hide')
              .parent();
          }
          else {
            // For button between paragraphs.
            $addMoreWrapper = $button
              .closest('table')
              .parent()
              .find('.paragraphs-add-dialog-template')
              .parent();

            // Set delta to correct position in list of paragraphs.
            config.delta = $button.closest('tr').index();
          }

          // Get types from ComboBox.
          var paragraphTypes = Drupal.modalAddParagraphs.getParagraphTypes($addMoreWrapper.find('[name$="[add_more_select]"]'));

          // Open dialog in context of "add_more" element, with provided
          // configuration and list of paragraph types.
          Drupal.modalAddParagraphs.openDialog($addMoreWrapper, config, paragraphTypes);
        });
    }
  };

  /**
   * Namespace for modal related javascript methods.
   *
   * @type {Object}
   */
  Drupal.modalAddParagraphs = {};

  /**
   * Fetch list of paragraph types from options provided in combobox.
   *
   * @param {Object} $typeComboBox
   *   jQuery element of combobox with list of available paragraph types.
   *
   * @return {Array}
   *   List of paragraph type objects with type and it's name.
   */
  Drupal.modalAddParagraphs.getParagraphTypes = function ($typeComboBox) {
    var paragraphTypes = [];

    $typeComboBox.find('option').each(function (index, optionElement) {
      var $option = $(optionElement);

      paragraphTypes.push(
        {
          'type': $option.attr('value'),
          'type-name': $option.text()
        }
      );
    });

    return paragraphTypes;
  };

  /**
   * Open modal dialog for adding new paragraph in list.
   *
   * @param {Object} $context
   *   jQuery element of form wrapper used to submit request for adding new
   *   paragraph to list. Wrapper also contains dialog template.
   * @param {Object} config
   *   Global configuration for dialog template. It will be applied on dialog
   *   template to generated customized dialog.
   * @param {Array} paragraphTypes
   *   List of objects with information for paragraph type. Every object
   *   contains type and name for it and list is used to generate correct action
   *   elements in customized dialog.
   */
  Drupal.modalAddParagraphs.openDialog = function ($context, config, paragraphTypes) {

    // Get dialog template and apply data on it.
    var $dialogTemplate = $('.paragraphs-add-dialog-template', $context);
    var $dialog = _templateClone($dialogTemplate, config);

    // Get row template and duplicate it for every paragraph type.
    var $rowTemplate = $dialog.find('.paragraphs-add-dialog-row-template');
    for (var i = 0; i < paragraphTypes.length; i++) {
      _templateClone($rowTemplate, paragraphTypes[i]);
    }
    $rowTemplate.remove();

    // Open dialog after data is applied on template.
    $dialog.dialog({
      modal: true,
      resizable: false,
      close: function () {
        var $dialog = $(this);

        // Destroy dialog object.
        $dialog.dialog('destroy');

        // Remove created html element for dialog.
        $dialog.remove();
      }
    });

    // Attach behaviours to dialog action triggering elements.
    $('.paragraphs-add-type-trigger-element', $dialog)
      .once('add-click-handler')
      .on('click', function (event) {
        var $this = $(this);

        // Stop default execution of click event.
        event.preventDefault();
        event.stopPropagation();

        Drupal.modalAddParagraphs.setValues(
          $context,
          {
            add_more_select: $this.attr('data-type'),
            add_more_delta: $this.attr('data-delta')
          }
        );

        // Close dialog afterwards.
        $this.closest('div.ui-dialog-content').dialog('close');
      });
  };

  /**
   * Method to set hidden fields and trigger adding of paragraph.
   *
   * @param {Object} $context
   *   Jquery object containing element where form for submitting exists.
   * @param {Object} options
   *   Object with key value pair, where key is name of field that should be set
   *   and value is value for that field.
   */
  Drupal.modalAddParagraphs.setValues = function ($context, options) {
    var $submitButton = $('.js-hide input[name$="_add_more"]', $context);
    var $wrapper = $submitButton.parent();

    // Set all field values defined in options.
    $.each(options, function (fieldName, fieldValue) {
      var $field = $wrapper.find('[name$="[' + fieldName + ']"]');

      if ($field) {
        $field.val(fieldValue);
      }
    });

    // Trigger ajax call on add button.
    $submitButton.trigger('mousedown');
  };

}(jQuery, Drupal, drupalSettings));
