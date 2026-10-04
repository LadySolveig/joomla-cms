/**
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

import JoomlaDialog from 'joomla.dialog';

if (!window.Joomla) {
  throw new Error('Joomla API is not properly initialised');
}

/* global hotkeys */

/**
 * Helper to create an element
 * @param {String} nodeName
 * @param {String} text
 * @param {Array} classList
 * @returns {HTMLElement}
 */
const createEl = (nodeName, text = '', classList = []) => {
  const el = document.createElement(nodeName);
  el.textContent = text;
  if (classList && classList.length) {
    el.classList.add(...classList);
  }
  return el;
};

const isMac = /mac/i.test(navigator.userAgentData?.platform || navigator.platform);

// Edit views (menu hidden) open every result in a new tab, so the form and its check-out stay intact.
const isEditMode = Joomla.getOptions('plg_system_commands.editMode', false);

let commandsDialog;
let announce = () => {};
let loadIfNeeded = () => {};
let openedAt = 0;
const initCommandsModal = () => {
  if (commandsDialog) {
    return commandsDialog;
  }

  // Create the content for the dialog
  const content = createEl('div', '', ['p-3', 'command-dialog-content']);

  // Default template for dialog search
  const template = document.querySelector('#command-dialog-search');
  content.appendChild(template.content.cloneNode(true));

  // Footer: show the platform's modifier key (⌘ or Ctrl).
  content.querySelectorAll('[data-platform]').forEach((key) => {
    key.hidden = (key.dataset.platform === 'mac') !== isMac;
  });

  // Default template for the popup
  const popupTemplate = `<div class="joomla-dialog-container">
  <section class="joomla-dialog-body"></section>
  </div>`;

  const searchInput = content.querySelector('#command_search');
  const closeButton = content.querySelector('#command-dialog-close');
  const resultList = content.querySelector('#command-dialog-search-result');
  const noResults = content.querySelector('#command-dialog-no-results');
  const errorContainer = content.querySelector('#command-dialog-error');
  const status = content.querySelector('#command-dialog-status');
  const hint = content.querySelector('#command-dialog-hint');

  closeButton.addEventListener('click', () => commandsDialog.close());

  const openInNewTab = (href) => {
    window.open(href, '_blank', 'noopener');
    commandsDialog.close();
  };

  resultList.addEventListener('click', (event) => {
    const option = event.target.closest('[role="option"]');

    if (option && option.target === '_blank' && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
      event.preventDefault();
      openInNewTab(option.href);
    }
  });

  // Combobox pattern: focus stays in the input, aria-activedescendant points to the selected option.
  let activeIndex = -1;

  const getOptions = () => [...resultList.querySelectorAll('[role="option"]')];

  // aria-activedescendant only after ↑/↓: while typing, screen readers would otherwise read the
  // preselected first result on every keystroke and drown out the result count.
  let navigating = false;

  const setActive = (index) => {
    const options = getOptions();

    options.forEach((option, i) => {
      option.setAttribute('aria-selected', i === index ? 'true' : 'false');
    });

    activeIndex = options[index] ? index : -1;

    // Visual only: screen readers get the option through aria-activedescendant.
    hint.hidden = activeIndex === -1;
    hint.textContent = activeIndex === -1 ? '' : `↵ ${options[activeIndex].dataset.label}`;
    hint.parentElement.classList.toggle('has-hint', activeIndex !== -1);

    if (activeIndex === -1 || !navigating) {
      searchInput.removeAttribute('aria-activedescendant');
    } else {
      searchInput.setAttribute('aria-activedescendant', options[activeIndex].id);
    }

    if (activeIndex === -1) {
      return;
    }

    const option = options[activeIndex];
    const top = option.offsetTop;

    if (top < resultList.scrollTop) {
      resultList.scrollTop = top;
    } else if (top + option.offsetHeight > resultList.scrollTop + resultList.clientHeight) {
      resultList.scrollTop = top + option.offsetHeight - resultList.clientHeight;
    }
  };

  // The link itself is the option: no interactive element nested inside an option, and mouse
  // users keep middle-click and "open in new tab".
  // "Group: Title", without the group when the title already contains it ("Global Configuration").
  const getLabel = (group, title) => (group && !title.toLowerCase().includes(group.toLowerCase()) ? `${group}: ${title}` : title);

  // Wrap every occurrence of the search words in <mark>, built from text nodes (no innerHTML).
  const highlight = (text, words) => {
    const lower = text.toLowerCase();
    const ranges = [];

    words.forEach((word) => {
      const needle = word.toLowerCase();
      let index = lower.indexOf(needle);

      while (index !== -1) {
        ranges.push([index, index + needle.length]);
        index = lower.indexOf(needle, index + needle.length);
      }
    });

    ranges.sort((a, b) => a[0] - b[0]);

    const fragment = document.createDocumentFragment();
    let position = 0;

    ranges.forEach(([start, end]) => {
      if (end <= position) {
        return;
      }

      fragment.append(text.slice(position, Math.max(start, position)));
      fragment.append(createEl('mark', text.slice(Math.max(start, position), end)));
      position = end;
    });

    fragment.append(text.slice(position));

    return fragment;
  };

  const createOption = (label, href, words, isQuicktask = false, target = '') => {
    const option = createEl('a', '', ['list-group-item', 'list-group-item-action']);

    // Screen readers get one plain text: Orca reads the <mark> pieces of the highlighted label as
    // separate parts ("Content: link icles"), so that visual label is hidden from them.
    const visual = createEl('span');
    visual.setAttribute('aria-hidden', 'true');
    visual.append(highlight(label, words));
    const text = createEl('span', label, ['visually-hidden']);
    option.append(visual, text);
    option.id = `command-option-${resultList.children.length}`;
    option.setAttribute('role', 'option');
    option.href = href;
    option.tabIndex = -1;
    option.dataset.label = label;

    const isExternal = target === '_blank' || option.origin !== window.location.origin;

    if (isExternal || isEditMode) {
      option.target = '_blank';
      option.rel = 'noopener noreferrer';
    }

    // Own marker instead of Atum's (hidden in the CSS); not in edit mode, where the note covers all entries.
    if (isExternal && !isEditMode) {
      const icon = createEl('span', '', ['icon-external-link-alt', 'ms-2']);
      icon.setAttribute('aria-hidden', 'true');
      option.append(icon);
      text.textContent += `, ${Joomla.Text._('JGLOBAL_OPENS_IN_A_NEW_WINDOW')}`;
    }

    if (isQuicktask) {
      const icon = createEl('span', '', ['icon-plus']);
      icon.setAttribute('aria-hidden', 'true');
      option.classList.add('command-dialog-quicktask');
      option.prepend(icon);
    }

    resultList.appendChild(option);
  };

  // Static items, loaded once per page and filtered here; dynamic ones come per term from plugins.
  let staticItems = null;
  let statusTexts = [];
  let hasDynamic = false;
  let dynamicItems = [];

  // While the static items load the list is stale: dimmed, aria-busy, and Enter is queued.
  let busy = false;
  let pendingEnter = false;

  const setBusy = (value) => {
    busy = value;
    resultList.setAttribute('aria-busy', value ? 'true' : 'false');
    resultList.classList.toggle('opacity-50', value);
  };

  const getWords = (search) => search.toLowerCase().split(/\s+/).filter(Boolean);

  const matches = (group, title, words) => {
    const haystack = `${group}: ${title}`.toLowerCase();

    return words.every((word) => haystack.includes(word));
  };

  const filterStatic = (words) => {
    const results = [];

    staticItems.forEach((item) => {
      if (matches(item.group, item.title, words)) {
        results.push(item);
      } else if (item.quicktask && matches(item.group, item.quicktask.title, words)) {
        // Only the quicktask matches: list it on its own, so it gets preselected for Enter.
        results.push({ group: item.group, ...item.quicktask });
      }
    });

    return results;
  };

  // Clear first: screen readers skip a live region update that repeats the same text.
  // Delayed, so typing doesn't trigger an announcement per keystroke.
  // Right after opening, wait for the dialog announcement: Orca drops live region text that
  // changes while it is still processing the new dialog.
  let lastStatus = '';
  let announceTimeout;
  announce = (text = lastStatus) => {
    lastStatus = text;
    status.textContent = '';
    clearTimeout(announceTimeout);
    announceTimeout = setTimeout(() => {
      status.textContent = text;
    }, Math.max(500, openedAt + 1500 - Date.now()));
  };

  const renderItems = (items, statusText, search, keepHref = null) => {
    const words = getWords(search);

    resultList.replaceChildren();
    Joomla.removeMessages(errorContainer);
    noResults.textContent = statusText;
    noResults.hidden = items.length > 0;

    // Links to other sites after the backend pages, both alphabetically.
    // Menu links like the help preset's language forum link are backend URLs that redirect to the forum.
    const isExternal = (item) => item.target === '_blank'
      || new URL(item.link, window.location.href).origin !== window.location.origin;

    items
      .map((item) => ({ ...item, label: getLabel(item.group, item.title), external: isExternal(item) }))
      .sort((a, b) => a.external - b.external || a.label.localeCompare(b.label, undefined, { numeric: true }))
      .forEach((item) => {
        createOption(item.label, item.link, words, false, item.target);

        if (item.quicktask) {
          createOption(getLabel(item.group, item.quicktask.title), item.quicktask.link, words, true);
        }
      });

    const options = getOptions();
    const kept = keepHref ? options.findIndex((option) => option.href === keepHref) : -1;
    setActive(kept !== -1 ? kept : 0);

    // Enter pressed while loading: open the first result once it is there.
    // Not for new-tab links: a delayed window.open() would be caught by popup blockers.
    if (pendingEnter && options[0] && options[0].target !== '_blank') {
      window.location.assign(options[0].href);
    }

    pendingEnter = false;

    return options.length;
  };

  // Inside the dialog: the page behind a modal dialog is inert.
  const showError = (message, keepList = false) => {
    pendingEnter = false;

    if (!keepList) {
      resultList.replaceChildren();
      setActive(-1);
      noResults.hidden = true;
    }

    Joomla.renderMessages({ error: [message || Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED')] }, errorContainer);
  };

  const request = (params, onData, keepListOnError) => {
    const url = new URL(`${Joomla.getOptions('system.paths').baseFull}index.php?option=com_ajax&group=system&plugin=commands&format=json&${Joomla.getOptions('csrf.token')}=1`);
    Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));

    url.searchParams.set('context', Joomla.getOptions('plg_system_commands.context', ''));

    Joomla.request({
      url: `${url}`,
      method: 'GET',
      perform: true,
      onSuccess: (resp) => {
        let response = {};

        try {
          response = JSON.parse(resp);
        } catch (e) {
          onData(null, () => showError(undefined, keepListOnError));
          return;
        }

        if (response.success && response.data && response.data.items) {
          onData(response.data);
        } else {
          onData(null, () => showError(response.message, keepListOnError));
        }
      },
      onError: (xhr) => {
        let message;

        try {
          ({ message } = JSON.parse(xhr.responseText));
        } catch (e) {
          // Not a JSON error response - fall back to the generic message.
        }

        onData(null, () => showError(message, keepListOnError));
      },
    });
  };

  // Responses can arrive out of order; only the latest search may update the list.
  let latestRequest = 0;
  let staticCount = 0;

  const searchDynamic = (search) => {
    latestRequest += 1;
    const current = latestRequest;

    request({ mode: 'search', search, static: staticCount }, (data, fail) => {
      if (current !== latestRequest) {
        return;
      }

      if (!data) {
        fail();
        return;
      }

      dynamicItems = data.items;

      if (dynamicItems.length) {
        const selected = getOptions()[activeIndex];
        renderItems(filterStatic(getWords(search)).concat(dynamicItems), data.status, search, selected && selected.href);
        announce(withSelection(data.status));
      }
    }, true);
  };

  // The count plus the preselected result, so screen reader users know what Enter opens without
  // aria-activedescendant reading the first option on every keystroke.
  const withSelection = (countText) => {
    const selected = getOptions()[activeIndex];

    return selected
      ? Joomla.Text._('PLG_SYSTEM_COMMANDS_STATUS_SELECTED').replace('%1$s', countText).replace('%2$s', selected.dataset.label)
      : countText;
  };

  const getStatus = (count) => statusTexts[Math.min(count, statusTexts.length - 1)] || '';

  // Instant: filters the static items; dynamic results follow (debounced) if a plugin provides them.
  let searchTimeout;
  const update = () => {
    const search = searchInput.value;
    const words = getWords(search);
    const results = filterStatic(words);

    navigating = false;

    latestRequest += 1;
    dynamicItems = [];
    staticCount = renderItems(results, getStatus(results.length + results.filter((item) => item.quicktask).length), search);
    announce(withSelection(getStatus(staticCount)));

    clearTimeout(searchTimeout);

    if (hasDynamic && search.trim().length >= 2) {
      searchTimeout = setTimeout(() => searchDynamic(search), 300);
    }
  };

  searchInput.addEventListener('keydown', (event) => {
    if (event.isComposing) {
      return;
    }

    // Only plain Enter: a delayed window.open() would be caught by popup blockers.
    if (busy && event.key === 'Enter') {
      event.preventDefault();
      pendingEnter = !event.ctrlKey && !event.metaKey;
      return;
    }

    const options = getOptions();

    if (!options.length) {
      return;
    }

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      const step = event.key === 'ArrowDown' ? 1 : -1;
      navigating = true;
      setActive((activeIndex + step + options.length) % options.length);
    } else if (event.key === 'Enter' && activeIndex !== -1) {
      event.preventDefault();
      const { href, target } = options[activeIndex];

      if (event.ctrlKey || event.metaKey || target === '_blank') {
        openInNewTab(href);
      } else {
        window.location.assign(href);
      }
    }
  });

  searchInput.addEventListener('input', () => {
    pendingEnter = false;

    if (staticItems) {
      update();
    }
  });

  const load = () => {
    setBusy(true);
    request({ mode: 'load' }, (data, fail) => {
      setBusy(false);

      if (!data) {
        fail();
        return;
      }

      staticItems = data.items;
      statusTexts = data.status;
      hasDynamic = data.dynamic;
      update();
    }, false);
  };

  loadIfNeeded = () => {
    if (!staticItems && !busy) {
      load();
    }
  };

  load();

  commandsDialog = new JoomlaDialog({
    popupTemplate: popupTemplate,
    popupContent: content,
    popupButtons: [{ label: '', className: 'd-none', location: 'header' }],
    className: 'command-palette-dialog',
    ariaLabel: Joomla.Text._('PLG_SYSTEM_COMMANDS_DIALOG_LABEL'),
    width: '600px',
    height: 'fit-content',
    cancelable: true,
  });

  return commandsDialog;
};

const showCommandsModal = () => {
  openedAt = Date.now();
  const dialog = initCommandsModal();
  dialog.show();
  dialog.querySelector('#command_search')?.focus();
  loadIfNeeded();
  announce();
};

// Only the platform's variant of the configured combinations: "cmd" on macOS, "ctrl" elsewhere.
// Otherwise cmd+k would also bind Win+K on Windows. Combinations without either are always kept.
const getPlatformHotkeys = (hotkeyList) => {
  const foreign = isMac ? /\bctrl\b/i : /\b(cmd|command|⌘)\b/i;
  const all = hotkeyList.split(',').map((key) => key.trim()).filter(Boolean);
  const own = all.filter((key) => !foreign.test(key));

  return (own.length ? own : all).join(',');
};

document.addEventListener('DOMContentLoaded', () => {
  const openHotkey = getPlatformHotkeys(Joomla.getOptions('plg_system_commands.openhotkey'));

  // Open the command palette only in the logged-in backend (not the login page); edit views have no menu <nav>.
  if (document.querySelector('nav') || isEditMode) {
    hotkeys(openHotkey, (event) => {
      showCommandsModal();
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
    });

    if (typeof Joomla.addShortcut === 'function') {
      Joomla.addShortcut('K', showCommandsModal);
    }
  }
});
