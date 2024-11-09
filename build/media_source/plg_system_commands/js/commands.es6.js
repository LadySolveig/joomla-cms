/**
 * @copyright  (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// eslint-disable-next-line import/no-unresolved
import JoomlaDialog from 'joomla.dialog';

if (!window.Joomla) {
  throw new Error('Joomla API is not properly initialised');
}

/* global hotkeys */
Joomla.addShortcut = (hotkey, callback) => {
  hotkeys(hotkey, 'joomla', (event) => {
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    callback.call();
  });
};

Joomla.addClickShortcut = (hotkey, selector) => {
  Joomla.addShortcut(hotkey, () => {
    const element = document.querySelector(selector);
    if (element) {
      element.click();
    }
  });
};

Joomla.addFocusShortcut = (hotkey, selector) => {
  Joomla.addShortcut(hotkey, () => {
    const element = document.querySelector(selector);
    if (element) {
      element.focus();
    }
  });
};

Joomla.addLinkShortcut = (hotkey, selector) => {
  Joomla.addShortcut(hotkey, () => {
    window.location.href = selector;
  });
};

const setShortcutFilter = () => {
  hotkeys.filter = (event) => {
    const target = event.target || event.srcElement;
    const { tagName } = target;

    // Checkboxes should not block a shortcut event
    if (target.type === 'checkbox') {
      return true;
    }

    // Ignore TinyMCE joomlaHighlighter plugin,
    // @TODO: remove this when the joomlaHighlighter plugin will use JoomlaDialog
    if (target.classList.contains('tox-textarea-wrap') && target.closest('.joomla-highlighter-dialog')) {
      return false;
    }

    // Default hotkeys filter behavior
    return !(target.isContentEditable || tagName === 'INPUT' || tagName === 'SELECT' || tagName === 'TEXTAREA');
  };
};

const startupShortcuts = () => {
  hotkeys('J', (event) => {
    // If we're already in the scope, it's a normal shortkey
    if (hotkeys.getScope() === 'joomla') {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    hotkeys.setScope('joomla');

    // Leave the scope after x milliseconds
    setTimeout(() => {
      hotkeys.setScope(false);
    }, Joomla.getOptions('plg_system_shortcut.timeout', 2000));
  });
};

const addOverviewHint = () => {
  const mainContainer = document.querySelector('.com_cpanel .container-main');
  if (mainContainer) {
    const containerElement = document.createElement('section');
    containerElement.className = 'content pt-4';
    containerElement.insertAdjacentHTML('beforeend', Joomla.Text._('PLG_SYSTEM_SHORTCUT_OVERVIEW_HINT'));
    mainContainer.appendChild(containerElement);
  }
};

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
const initCommandsModal = (options) => {
  if (commandsDialog) {
    return commandsDialog;
  }
  /* const dlItems = new Map();
  Object.values(options).forEach((value) => {
    if (!value.shortcut || !value.title) {
      return;
    }
    let titles = [];
    if (dlItems.has(value.shortcut)) {
      titles = dlItems.get(value.shortcut);
      titles.push(value.title);
    } else {
      titles = [value.title];
    }
    dlItems.set(value.shortcut, titles);
  }); */

  // Render:
  // <dl>
  // <div><dt><kbd>J</kbd><span>then</span><kbd>...</kbd><dd>....</dd></div>
  // <div><dt><kbd>J</kbd><span>then</span><kbd>...</kbd><dd>....</dd></div>
  // ...
  // </dl>
  /* const dl = createEl('dl');
  dlItems.forEach((titles, shortcut) => {
    const row = createEl('div');
    const dt = createEl('dt', '', ['d-inline-block']);
    row.appendChild(dt);
    dt.appendChild(createEl('kbd', 'J'));

    shortcut.split('+').forEach((key) => {
      dt.appendChild(createEl('span', Joomla.Text._('PLG_SYSTEM_SHORTCUT_THEN'), ['px-1']));
      dt.appendChild(createEl('kbd', key));
    });

    titles.forEach((title) => {
      const dd = createEl('dd', '', ['d-inline-block', 'ps-1']);
      dd.innerHTML = Joomla.sanitizeHtml(title);
      row.appendChild(dd);
    });

    dl.appendChild(row);
  });*/

  // Create the content for the dialog
  /* const intro = createEl('p');
  intro.innerHTML = Joomla.sanitizeHtml(Joomla.Text._('PLG_SYSTEM_SHORTCUT_OVERVIEW_DESC'), { kbd: '*' });
  const info = createEl('div');
  info.appendChild(dl); */
  const content = createEl('div', '', ['p-3']);
  // content.appendChild(intro);
  // content.appendChild(info);

  // Default template for dialog search
  const template = document.querySelector('#command-dialog-search');
  content.appendChild(template.content.cloneNode(true));

  // Default template for the popup
  const popupTemplate = `<div class="joomla-dialog-container">
  <section class="joomla-dialog-body"></section>
  </div>`;

  // url for ajax call
  const baseUrl = new URL(`${Joomla.getOptions('system.paths').baseFull}index.php?option=com_ajax&group=system&plugin=commands&format=json&${Joomla.getOptions('csrf.token')}=1`);

  console.log(baseUrl);

  /* alerts[0].querySelector('.eosnotify-snooze-btn').addEventListener('click', () => fetch(url, { headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token') || '' } })
      .then((response) => {
        if (response.ok) {
          alerts[0].closest('joomla-alert').close();
        }
      })); */
      // url: `${baseUrl}&type=${type}&plugin=SampledataApplyStep${step}&step=${step}`,
  Joomla.request({
    url: `${baseUrl}`,
    method: 'GET',
    perform: true,
    onSuccess: (resp) => {
      console.log('successfull response');
      let response = {};

      try {
        response = JSON.parse(resp);
      } catch (e) {
        // Joomla.renderMessages({ error: [Joomla.Text._('MOD_SAMPLEDATA_INVALID_RESPONSE')] }, `.sampledata-steps-${type}-${step}`);
        return;
      }

      if (response.success && response.data && response.data.length > 0) {
        console.log('successfull response');
        console.log(response.data);
      }
    },
    onError: () => {
      Joomla.renderMessages({ error: [Joomla.Text._('MOD_SAMPLEDATA_ERROR_RESPONSE')] });
      window.scroll({
        top: 0,
        left: 0,
        behavior: 'smooth',
      });
      // SampleData.inProgress = false;
    },
  });

  commandsDialog = new JoomlaDialog({
    // textHeader: Joomla.Text._('PLG_SYSTEM_COMMANDS_OVERVIEW_TITLE'),
    // textClose: Joomla.Text._('JCLOSE'),
    popupTemplate: popupTemplate,
    popupContent: content,
    popupButtons : [
      { label: 'Search', className: 'd-none' }
    ],
    width: '600px',
    height: 'fit-content',
    cancelable: true,
  });

  return commandsDialog;
};

const showCommandsModal = (options) => {
  initCommandsModal(options).show();
};

document.addEventListener('DOMContentLoaded', () => {
  const options = Joomla.getOptions('plg_system_commands.commands');
  // Open or hide shorcut
  const openHotkey = Joomla.getOptions('plg_system_commands.openhotkey');
  console.log(openHotkey);
  Object.values(options).forEach((value) => {
    if (!value.shortcut || !value.selector) {
      return;
    }
    if (value.selector.startsWith('/') || value.selector.startsWith('http://') || value.selector.startsWith('www.')) {
      Joomla.addLinkShortcut(value.shortcut, value.selector);
    } else if (value.selector.includes('input')) {
      Joomla.addFocusShortcut(value.shortcut, value.selector);
    } else {
      Joomla.addClickShortcut(value.shortcut, value.selector);
    }
  });
  // Show hint and overview on logged in backend only (not login page)
  // if (document.querySelector('nav')) {
    // hotkeys('X', 'joomla', () => {
      // showOverviewModal(options);
    // });
    // addOverviewHint();
  // }
  // Show hint and overview on logged in backend only (not login page)
  if (document.querySelector('nav')) {
    hotkeys(openHotkey, (event) => {
      console.log('pressed hotkey: ' + openHotkey);
      showCommandsModal(options);
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
    });
  }
  setShortcutFilter();
  startupShortcuts();
});
