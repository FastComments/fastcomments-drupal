(function (Drupal, drupalSettings) {
  let scriptLoaded = false;

  Drupal.behaviors.fastcommentsCount = {
    attach() {
      if (scriptLoaded) {
        return;
      }

      const settings = drupalSettings.fastcomments;
      if (!settings || !settings.tenantId) {
        return;
      }

      const cdnUrl = settings.cdnUrl || 'https://cdn.fastcomments.com';

      window.FastCommentsBulkCountConfig =
        window.FastCommentsBulkCountConfig || {
          tenantId: settings.tenantId,
        };

      const script = document.createElement('script');
      script.src = `${cdnUrl}/js/embed-widget-comment-count-bulk.min.js`;
      script.async = true;
      document.body.appendChild(script);
      scriptLoaded = true;
    },
  };
})(Drupal, drupalSettings);
