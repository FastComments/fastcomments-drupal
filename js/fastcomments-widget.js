/**
 * @file
 * JavaScript for loading FastComments widgets.
 *
 * Follows the Drupal behavior pattern: config is passed via drupalSettings,
 * CDN scripts are loaded dynamically at runtime.
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Map of commenting style to CDN script paths and global init function.
   */
  var STYLE_MAP = {
    comments:            { scripts: ['/js/embed-v2.min.js'],           initFn: 'FastCommentsUI' },
    livechat:            { scripts: ['/js/embed-live-chat.min.js'],    initFn: 'FastCommentsLiveChat' },
    collabchat:          { scripts: ['/js/embed-collab-chat.min.js'],  initFn: 'FastCommentsCollabChat' },
    imagechat:           { scripts: ['/js/embed-image-chat.min.js'],   initFn: 'FastCommentsImageChat' },
    collabchat_comments: {
      scripts: ['/js/embed-v2.min.js', '/js/embed-collab-chat.min.js']
    }
  };

  /** Track loaded CDN scripts to avoid duplicates. */
  var loadedScripts = {};

  /** Track initialized widget instances to avoid duplicates. */
  if (!window.fcInitializedById) {
    window.fcInitializedById = {};
  }

  function loadScript(url) {
    if (loadedScripts[url]) {
      return;
    }
    loadedScripts[url] = true;
    var s = document.createElement('script');
    s.src = url;
    s.async = true;
    document.head.appendChild(s);
  }

  function findMainContent() {
    // Prefer the article/node content area for collab chat, so the
    // annotation bar attaches to the actual content, not the full page.
    return document.querySelector('article .node__content') ||
           document.querySelector('article .field--name-body') ||
           document.querySelector('article') ||
           document.querySelector('[role="main"]') ||
           document.querySelector('main') ||
           document.getElementById('main-content');
  }

  function removeNoOverflow(el) {
    if (!el) return;
    el.classList.remove('no-overflow');
    var children = el.querySelectorAll('.no-overflow');
    for (var i = 0; i < children.length; i++) {
      children[i].classList.remove('no-overflow');
    }
  }

  function initWidget(instance) {
    var style = instance.commentingStyle;
    var config = instance.config;
    var elementId = instance.elementId;
    var dedupKey = style + ':' + config.urlId;

    if (window.fcInitializedById[dedupKey]) {
      return;
    }

    var styleInfo = STYLE_MAP[style];
    if (!styleInfo) {
      return;
    }

    // Load CDN scripts.
    var scripts = styleInfo.scripts;
    for (var i = 0; i < scripts.length; i++) {
      loadScript(instance.cdnUrl + scripts[i]);
    }

    var attempts = 0;
    function attemptInit() {
      attempts++;
      if (attempts > 200) return;

      if (style === 'collabchat_comments') {
        var target = document.getElementById(elementId);
        var main = findMainContent();
        if (window.FastCommentsUI && target && window.FastCommentsCollabChat && main) {
          window.fcInitializedById[dedupKey] = true;
          removeNoOverflow(main);
          window.FastCommentsCollabChat(main, config);
          window.FastCommentsUI(target, config);
          return;
        }
      } else if (style === 'collabchat') {
        var main = findMainContent();
        if (window.FastCommentsCollabChat && main) {
          window.fcInitializedById[dedupKey] = true;
          removeNoOverflow(main);
          window.FastCommentsCollabChat(main, config);
          return;
        }
      } else {
        var target = document.getElementById(elementId);
        if (window[styleInfo.initFn] && target) {
          window.fcInitializedById[dedupKey] = true;
          window[styleInfo.initFn](target, config);
          return;
        }
      }

      setTimeout(attemptInit, attempts > 50 ? 500 : 50);
    }
    attemptInit();
  }

  Drupal.behaviors.fastcommentsWidget = {
    attach: function (context, settings) {
      var widgets = settings.fastcommentsWidgets;
      if (!widgets) {
        return;
      }

      // Use document-level once per widget instance\.
      // BigPipe may deliver the JS after the DOM replacement,
      // so context-scoped once() can miss the element.
      for (var instanceId in widgets) {
        if (!widgets.hasOwnProperty(instanceId)) continue;
        once('fastcomments-widget-' + instanceId, 'body').forEach(function () {
          initWidget(widgets[instanceId]);
        });
      }
    }
  };

})(Drupal, drupalSettings, once);
