/**
 * @file
 * JavaScript for loading FastComments widgets.
 *
 * Follows the Drupal behavior pattern: config is passed via drupalSettings,
 * CDN scripts are loaded dynamically at runtime.
 */
(function (Drupal, drupalSettings, once) {
  /**
   * Map of commenting style to CDN script paths and global init function.
   */
  const STYLE_MAP = {
    comments: { scripts: ['/js/embed-v2.min.js'], initFn: 'FastCommentsUI' },
    livechat: {
      scripts: ['/js/embed-live-chat.min.js'],
      initFn: 'FastCommentsLiveChat',
    },
    collabchat: {
      scripts: ['/js/embed-collab-chat.min.js'],
      initFn: 'FastCommentsCollabChat',
    },
    imagechat: {
      scripts: ['/js/embed-image-chat.min.js'],
      initFn: 'FastCommentsImageChat',
    },
    collabchat_comments: {
      scripts: ['/js/embed-v2.min.js', '/js/embed-collab-chat.min.js'],
    },
  };

  /** Track loaded CDN scripts to avoid duplicates. */
  const loadedScripts = {};

  /** Track initialized widget instances to avoid duplicates. */
  if (!window.fcInitializedById) {
    window.fcInitializedById = {};
  }

  function loadScript(url) {
    if (loadedScripts[url]) {
      return;
    }
    loadedScripts[url] = true;
    const s = document.createElement('script');
    s.src = url;
    s.async = true;
    document.head.appendChild(s);
  }

  function findMainContent() {
    // Prefer the article/node content area for collab chat, so the
    // annotation bar attaches to the actual content, not the full page.
    return (
      document.querySelector('article .node__content') ||
      document.querySelector('article .field--name-body') ||
      document.querySelector('article') ||
      document.querySelector('[role="main"]') ||
      document.querySelector('main') ||
      document.getElementById('main-content')
    );
  }

  function removeNoOverflow(el) {
    if (!el) return;
    el.classList.remove('no-overflow');
    const children = el.querySelectorAll('.no-overflow');
    for (let i = 0; i < children.length; i++) {
      children[i].classList.remove('no-overflow');
    }
  }

  function initWidget(instance) {
    const style = instance.commentingStyle;
    const config = instance.config;
    const elementId = instance.elementId;
    const dedupKey = `${style}:${config.urlId}`;

    if (window.fcInitializedById[dedupKey]) {
      return;
    }

    const styleInfo = STYLE_MAP[style];
    if (!styleInfo) {
      return;
    }

    // Load CDN scripts.
    const scripts = styleInfo.scripts;
    for (let i = 0; i < scripts.length; i++) {
      loadScript(instance.cdnUrl + scripts[i]);
    }

    let attempts = 0;
    function attemptInit() {
      attempts++;
      if (attempts > 200) return;

      if (style === 'collabchat_comments') {
        const target = document.getElementById(elementId);
        const main = findMainContent();
        if (
          window.FastCommentsUI &&
          target &&
          window.FastCommentsCollabChat &&
          main
        ) {
          window.fcInitializedById[dedupKey] = true;
          removeNoOverflow(main);
          window.FastCommentsCollabChat(main, config);
          window.FastCommentsUI(target, config);
          return;
        }
      } else if (style === 'collabchat') {
        const main = findMainContent();
        if (window.FastCommentsCollabChat && main) {
          window.fcInitializedById[dedupKey] = true;
          removeNoOverflow(main);
          window.FastCommentsCollabChat(main, config);
          return;
        }
      } else if (style === 'imagechat') {
        // Image chat attaches to individual <img> elements.
        const main = findMainContent();
        if (window.FastCommentsImageChat && main) {
          const images = main.querySelectorAll(
            'img:not([data-fc-image-chat])',
          );
          if (images.length > 0) {
            window.fcInitializedById[dedupKey] = true;
            for (let j = 0; j < images.length; j++) {
              images[j].setAttribute('data-fc-image-chat', 'true');
              window.FastCommentsImageChat(images[j], config);
            }
            return;
          }
        }
      } else {
        const target = document.getElementById(elementId);
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
    attach(context, settings) {
      const widgets = settings.fastcommentsWidgets;
      if (!widgets) {
        return;
      }

      // Use document-level once per widget instance.
      // BigPipe may deliver the JS after the DOM replacement,
      // so context-scoped once() can miss the element.
      Object.keys(widgets).forEach((instanceId) => {
        once(`fastcomments-widget-${instanceId}`, 'body').forEach(() => {
          initWidget(widgets[instanceId]);
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
