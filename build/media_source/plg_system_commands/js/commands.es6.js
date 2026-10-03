/**
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// eslint-disable-next-line import/no-unresolved
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
  const createOption = (title, href) => {
    const option = createEl('a', title, ['list-group-item', 'list-group-item-action']);
    option.id = `command-option-${resultList.children.length}`;
    option.setAttribute('role', 'option');
    option.href = href;
    option.tabIndex = -1;

    resultList.appendChild(option);
  };

  const renderItems = (items, statusText) => {
    resultList.replaceChildren();
    noResults.textContent = statusText;
    noResults.hidden = items.length > 0;
    searchInput.setAttribute('aria-expanded', items.length > 0 ? 'true' : 'false');

    items.forEach((item) => {
      createOption(item.title, item.link);

      if (item.quicktask) {
        createOption(item.quicktask.title, item.quicktask.link);
      }
    });

    setActive(0);
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

  const searchCommands = (search) => {
    const url = new URL(`${Joomla.getOptions('system.paths').baseFull}index.php?option=com_ajax&group=system&plugin=commands&format=json&${Joomla.getOptions('csrf.token')}=1`);
    url.searchParams.set('search', search);

    Joomla.request({
      url: `${url}`,
      method: 'GET',
      perform: true,
      onSuccess: (resp) => {
        let response = {};

        try {
          response = JSON.parse(resp);
        } catch (e) {
          return;
        }

        if (response.success && response.data && response.data.items) {
          renderItems(response.data.items, response.data.status);
          announce(response.data.status);
        }
      },
      onError: () => {
        Joomla.renderMessages({ error: [Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED')] });
      },
    });
  };

  searchInput.addEventListener('keydown', (event) => {
    const options = getOptions();

    if (event.isComposing || !options.length) {
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
    // The list is outdated until the new results arrive - don't let Enter open a stale entry.
    setActive(-1);
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => searchCommands(event.target.value), 300);
  });

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

document.addEventListener('DOMContentLoaded', () => {
  const openHotkey = Joomla.getOptions('plg_system_commands.openhotkey');

  // Open the command palette only in the logged-in backend (not the login page)
  if (document.querySelector('nav')) {
    hotkeys(openHotkey, (event) => {
      showCommandsModal();
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
    });
  }
});
