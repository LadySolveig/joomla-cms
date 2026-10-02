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
const initCommandsModal = () => {
  if (commandsDialog) {
    return commandsDialog;
  }

  // Create the content for the dialog
  const content = createEl('div', '', ['p-3']);

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

  closeButton.addEventListener('click', () => commandsDialog.close());

  const renderItems = (items) => {
    resultList.replaceChildren();

    items.forEach((item) => {
      const li = createEl('li', '', ['list-group-item']);
      const link = createEl('a', item.title);
      link.href = item.link;
      li.appendChild(link);
      resultList.appendChild(li);

      if (item.quicktask) {
        const quicktaskLi = createEl('li', '', ['list-group-item']);
        const quicktaskLink = createEl('a', item.quicktask.title);
        quicktaskLink.href = item.quicktask.link;
        quicktaskLi.appendChild(quicktaskLink);
        resultList.appendChild(quicktaskLi);
      }
    });
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
          renderItems(response.data.items);
        }
      },
      onError: () => {
        Joomla.renderMessages({ error: [Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED')] });
      },
    });
  };

  let searchTimeout;
  searchInput.addEventListener('input', (event) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => searchCommands(event.target.value), 300);
  });

  searchCommands('');

  commandsDialog = new JoomlaDialog({
    popupTemplate: popupTemplate,
    popupContent: content,
    // Suppress the library's own auto-added header close button: we provide our own,
    // next to the search input, instead. A non-empty popupButtons list is the only way
    // to opt out of the default (see joomla-dialog.w-c.es6.js renderLayout()).
    popupButtons: [{ label: '', className: 'd-none', location: 'header' }],
    className: 'command-palette-dialog',
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
