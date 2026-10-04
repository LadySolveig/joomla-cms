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

let commandsDialog;
let announce = () => {};
const initCommandsModal = () => {
  if (commandsDialog) {
    return commandsDialog;
  }

  // Create the content for the dialog
  const content = createEl('div', '', ['p-3', 'command-dialog-content']);

  // Default template for dialog search
  const template = document.querySelector('#command-dialog-search');
  content.appendChild(template.content.cloneNode(true));

  // Default template for the popup
  const popupTemplate = `<div class="joomla-dialog-container">
  <header class="joomla-dialog-header"></header>
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

  // Combobox pattern: focus stays in the input, aria-activedescendant points to the selected option.
  let activeIndex = -1;

  const getOptions = () => [...resultList.querySelectorAll('[role="option"]')];

  const setActive = (index) => {
    const options = getOptions();

    options.forEach((option, i) => {
      option.setAttribute('aria-selected', i === index ? 'true' : 'false');
    });

    activeIndex = options[index] ? index : -1;

    // Visual only: screen readers get the option through aria-activedescendant.
    hint.hidden = activeIndex === -1;
    hint.textContent = activeIndex === -1 ? '' : `↵ ${options[activeIndex].textContent}`;
    hint.parentElement.classList.toggle('has-hint', activeIndex !== -1);

    if (activeIndex === -1) {
      searchInput.removeAttribute('aria-activedescendant');
      return;
    }

    searchInput.setAttribute('aria-activedescendant', options[activeIndex].id);

    // Scroll only the list: scrollIntoView() would also scroll the dialog and move the search row away.
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

  const createOption = (label, href, words, isQuicktask = false) => {
    const option = createEl('a', '', ['list-group-item', 'list-group-item-action']);
    option.append(highlight(label, words));
    option.id = `command-option-${resultList.children.length}`;
    option.setAttribute('role', 'option');
    option.href = href;
    option.tabIndex = -1;

    // Indented with a "+" below the item it belongs to.
    if (isQuicktask) {
      const icon = createEl('span', '', ['icon-plus']);
      icon.setAttribute('aria-hidden', 'true');
      option.classList.add('command-dialog-quicktask');
      option.prepend(icon);
    }

    resultList.appendChild(option);
  };

  let busy = false;
  let pendingEnter = false;

  const setBusy = (value) => {
    busy = value;
    resultList.setAttribute('aria-busy', value ? 'true' : 'false');
    resultList.classList.toggle('opacity-50', value);
  };

  const renderItems = (items, statusText, search) => {
    const words = search.split(/\s+/).filter(Boolean);

    resultList.replaceChildren();
    Joomla.removeMessages(errorContainer);
    noResults.textContent = statusText;
    noResults.hidden = items.length > 0;
    searchInput.setAttribute('aria-expanded', items.length > 0 ? 'true' : 'false');

    items
      .map((item) => ({ ...item, label: getLabel(item.group, item.title) }))
      .sort((a, b) => a.label.localeCompare(b.label))
      .forEach((item) => {
        createOption(item.label, item.link, words);

        if (item.quicktask) {
          createOption(getLabel(item.group, item.quicktask.title), item.quicktask.link, words, true);
        }
      });

    setActive(0);

    // Enter pressed while searching: open the first result once it is there.
    const [first] = getOptions();

    if (pendingEnter && first) {
      window.location.assign(first.href);
    }

    pendingEnter = false;
  };

  // Clear first: screen readers skip a live region update that repeats the same text.
  let lastStatus = '';
  let announceTimeout;
  announce = (text = lastStatus) => {
    lastStatus = text;
    status.textContent = '';
    clearTimeout(announceTimeout);
    announceTimeout = setTimeout(() => {
      status.textContent = text;
    }, 100);
  };

  // Inside the dialog: the page behind a modal dialog is inert.
  const showError = (message) => {
    pendingEnter = false;
    resultList.replaceChildren();
    setActive(-1);
    noResults.hidden = true;
    searchInput.setAttribute('aria-expanded', 'false');
    Joomla.renderMessages({ error: [message || Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED')] }, errorContainer);
  };

  // Responses can arrive out of order; only the latest search may update the list.
  let latestRequest = 0;

  const searchCommands = (search) => {
    latestRequest += 1;
    const request = latestRequest;
    const url = new URL(`${Joomla.getOptions('system.paths').baseFull}index.php?option=com_ajax&group=system&plugin=commands&format=json&${Joomla.getOptions('csrf.token')}=1`);
    url.searchParams.set('search', search);

    Joomla.request({
      url: `${url}`,
      method: 'GET',
      perform: true,
      onSuccess: (resp) => {
        if (request !== latestRequest) {
          return;
        }

        setBusy(false);
        let response = {};

        try {
          response = JSON.parse(resp);
        } catch (e) {
          showError();
          return;
        }

        if (response.success && response.data && response.data.items) {
          renderItems(response.data.items, response.data.status, search);
          announce(response.data.status);
        } else {
          showError(response.message);
        }
      },
      onError: (xhr) => {
        if (request !== latestRequest) {
          return;
        }

        setBusy(false);
        let message;

        try {
          ({ message } = JSON.parse(xhr.responseText));
        } catch (e) {
          // Not a JSON error response - fall back to the generic message.
        }

        showError(message);
      },
    });
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
      setActive((activeIndex + step + options.length) % options.length);
    } else if (event.key === 'Enter' && activeIndex !== -1) {
      event.preventDefault();
      const { href } = options[activeIndex];

      if (event.ctrlKey || event.metaKey) {
        window.open(href, '_blank', 'noopener');
      } else {
        window.location.assign(href);
      }
    }
  });

  let searchTimeout;
  searchInput.addEventListener('input', (event) => {
    setActive(-1);
    setBusy(true);
    pendingEnter = false;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => searchCommands(event.target.value), 300);
  });

  setBusy(true);
  searchCommands('');

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
  const dialog = initCommandsModal();
  dialog.show();
  dialog.querySelector('#command_search')?.focus();
  announce();
};

// Only the platform's variant of the configured combinations: "cmd" on macOS, "ctrl" elsewhere.
// Otherwise cmd+k would also bind Win+K on Windows. Combinations without either are always kept.
const getPlatformHotkeys = (hotkeyList) => {
  const isMac = /mac/i.test(navigator.userAgentData?.platform || navigator.platform);
  const foreign = isMac ? /\bctrl\b/i : /\b(cmd|command|⌘)\b/i;
  const all = hotkeyList.split(',').map((key) => key.trim()).filter(Boolean);
  const own = all.filter((key) => !foreign.test(key));

  return (own.length ? own : all).join(',');
};

document.addEventListener('DOMContentLoaded', () => {
  const openHotkey = getPlatformHotkeys(Joomla.getOptions('plg_system_commands.openhotkey'));

  // Open the command palette only in the logged-in backend (not the login page)
  if (document.querySelector('nav')) {
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
