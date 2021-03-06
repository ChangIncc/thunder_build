/**
 * @file
 * Parse inline JSON and initialize the breakpointSettings global object.
 */

(function () {

  "use strict";

  var settingsElement = document.querySelector('script[type="application/json"][data-breakpoint-settings="breakpoint-settings-json"]');

  /**
   * Variable generated by Breakpoint settings.
   *
   * @global
   *
   * @type {object}
   */
  window.breakpointSettings = {};

  if (settingsElement !== null) {
    window.breakpointSettings = JSON.parse(settingsElement.textContent);
  }
})();
