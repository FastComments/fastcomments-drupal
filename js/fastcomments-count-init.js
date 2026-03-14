(function (Drupal, drupalSettings) {
  'use strict';

  var scriptLoaded = false;

  Drupal.behaviors.fastcommentsCount = {
    attach: function (context) {
      if (scriptLoaded) {
        return;
      }

      var settings = drupalSettings.fastcomments;
      if (!settings || !settings.tenantId) {
        return;
      }

      var cdnUrl = settings.cdnUrl || 'https://cdn.fastcomments.com';

      window.FastCommentsBulkCountConfig = window.FastCommentsBulkCountConfig || {
        tenantId: settings.tenantId
      };

      var script = document.createElement('script');
      script.src = cdnUrl + '/js/embed-widget-comment-count-bulk.min.js';
      script.async = true;
      document.body.appendChild(script);
      scriptLoaded = true;
    }
  };
})(Drupal, drupalSettings);
