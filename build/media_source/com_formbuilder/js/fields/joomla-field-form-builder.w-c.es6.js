/**
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

if (!window.Joomla) {
  throw new Error('JoomlaEditors API require Joomla to be loaded.');
}

// Default template for the formbuilder
const formbuilderTemplate = document.createElement('template');

formbuilderTemplate.innerHTML = `
<slot></slot>`;

/**
 * JoomlaFormBuilder class for Joomla Form Builder implementation.
 * With use of <joomla-form-builder> custom element as form builder holder.
 */
class JoomlaFormBuilder extends HTMLElement {

  /**
   * An optional list of buttons, to be rendered in footer or header, or bottom or top of the popup body.
   * Example:
   *   [{label: 'Yes', onClick: () => popup.close()},
   *   {label: 'No', onClick: () => popup.close(), className: 'btn btn-danger'},
   *   {label: 'Click me', onClick: () => popup.close(), location: 'header'}]
   * @type {[]}
   */
  // popupButtons = [];

  /**
   * A template for the formbuilder.
   * @type {string|HTMLTemplateElement}
   */
  // formbuilderTemplate = formbuilderTemplate;

  /**
   * Class constructor
   * @param {Object} config
   */
  constructor(config) {
    super();

    this.formbuilderTemplate = formbuilderTemplate;

    this.renderLayout();

    if (!config) return;

    // Check configurable properties
    ['formbuilderTemplate', 'id'].forEach((key) => {
        if (config[key] !== undefined) {
          this[key] = config[key];
        }
      });

    // Check class name
    if (config.className) {
      this.classList.add(...config.className.split(' '));
    }

    // Check dataset properties
    if (config.data) {
      Object.entries(config.data).forEach(([k, v]) => {
        this.dataset[k] = v;
      });
    }
  }

  /**
   * Internal. Render a main layout, based on given template.
   * @returns {JoomlaFormBuilder}
   */
  renderLayout() {
    if (this.formBuilder) return this;

    // Render a template
    let templateContent;
    if (this.formbuilderTemplate.tagName && this.formbuilderTemplate.tagName === 'TEMPLATE') {
      templateContent = this.formbuilderTemplate.content.cloneNode(true);
    } else {
      const template = document.createElement('template');
      template.innerHTML = this.formbuilderTemplate;
      templateContent = template.content;
    }

    this.formbuilder = this.attachShadow({ mode: 'open' });
    this.formbuilder.appendChild(templateContent);

    return this;
  }

  /**
	 * Runs each time the element is appended to or moved in the DOM
	 */
	connectedCallback () {

    this.formbuilderFormItemsContainer = this.querySelector('.joomla-formbuilder-form-items');

    if (!this.formbuilderFormItemsContainer) {
      throw new Error('The form items container not found in the template.');
    }

    // Get template parts
    this.formbuilderAvailableItemsContainer = this.querySelector('.joomla-formbuilder-available-items');

    if (!this.formbuilderAvailableItemsContainer) {
      throw new Error('The availabel items container not found in the template.');
    }

    // Setup event listeners
		this.addEventListener('click', this);
    this.addEventListener('joomla-drop-list-item:up', this);
    this.addEventListener('joomla-drop-list-item:down', this);
    this.addEventListener('joomla-drop-list:dropped', this);
    this.addEventListener('joomla.tab.shown', this);
	}

  /**
	 * Runs when the element is removed from the DOM
	 */
	disconnectedCallback () {
		// Setup event listeners
		this.removeEventListener('click', this);
    this.removeEventListener('joomla-drop-list-item:up', this);
    this.removeEventListener('joomla-drop-list-item:down', this);
    this.removeEventListener('joomla-drop-list:drop', this);
    this.removeEventListener('joomla.tab.shown', this);
	}

  /**
   * Handle event listeners
   * @param  {Event} event The event object
   */
  handleEvent(event) {
    const handlerName = `on${event.type.replaceAll('-', '_').replaceAll(':', '_').replaceAll('.', '_')}`;
    if (typeof this[handlerName] === 'function') {
      this[handlerName](event);
    }
  }

  onjoomla_drop_list_item_up(event) {
    this.setFormItemsData(); // @todo throw error if not found or not possible
  }

  onjoomla_drop_list_item_down(event) {
    this.setFormItemsData(); // @todo throw error if not found or not possible
  }

  onjoomla_tab_shown(event) {
    // If the global tab is active, we need to disable the remove functionality
    if (!event.target?.tagName === 'BUTTON') {
      return;
    }

    if (!event.target.hasAttribute('aria-controls')) {
      return;
    }

    if (!event.target.closest('joomla-drop-list').hasAttribute('slotName') ||
        event.target.closest('joomla-drop-list')?.getAttribute('slotName') !== 'field-form') {
      return;
    }


    // Remove all edit buttons
    this.querySelectorAll('[data-task="edit-fieldset"]').forEach((btn) => {
      btn.remove();
    });

    if (event.target.getAttribute('aria-controls') === 'jglobal') {
      this.querySelector('[data-task="remove-fieldset"]')?.setAttribute('disabled', 'disabled');
    } else {
      this.querySelector('[data-task="remove-fieldset"]')?.removeAttribute('disabled');
      // Add a new button element next to the event target with an edit symbol
      const editButton = document.createElement('button');
      editButton.className = 'btn btn-secondary btn-sm';
      editButton.setAttribute('type', 'button');
      editButton.setAttribute('data-task', 'edit-fieldset');
      editButton.innerHTML = `<span class="mx-2 icon-edit icon-fw icon-lg" aria-hidden="true"></span><span class="visually-hidden">${Joomla.Text._('COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_EDIT_FIELDSET')}</span>`;
      event.target.insertAdjacentElement('afterEnd', editButton);
    }
  }

  /**
   * Clear elements on click events
   * @param  {Event} event The event object
   */
  onclick(event) {

    const target = event.target.tagName === 'SPAN' ? event.target.closest('button, a') : event.target;

    // Get the task
    let task = target.getAttribute('data-task');
    if (!task) return;

    // Prevent submit
    event.preventDefault();

    // Move an item between available and form fields
    if (task === 'move') {
      const btn = target;
      const item = btn.closest('joomla-drop-list-item');
      if (!item) return;
      const slotName = item.closest('[slot="field-available"]') ? 'field-form' : 'field-available';
      if (!this.querySelector(`span[slot="${slotName}"]`)) {
        this.insertAdjacentHTML('beforeEnd', `<span slot="${slotName}"></span>`);
      }

      const spanSlot = this.querySelector(`span[slot="${slotName}"]`);
      if (spanSlot.querySelector('joomla-tab-element[active]')) {
        spanSlot.querySelector('joomla-tab-element[active]').appendChild(item);
      } else {
        spanSlot.appendChild(item);
      }

      this.setFormItemsData(); // @todo throw error if not found or not possible

      // Change the menu item text for add or remove from form
      btn.innerHTML = (slotName === 'field-form')
        ? `<span class="icon-minus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JGLOBAL_FIELD_REMOVE')}`
        : `<span class="icon-plus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JGLOBAL_FIELD_ADD')}`;

      // Add or remove sortable functionality
      const menu = btn.closest('.dropdown-menu');
      if (!menu) return;
      if (slotName === 'field-form') {
        this.addSortable(menu);
        this.addAdditionalActionButtons(menu);
        return;
      }
      this.removeSortable(menu);
      this.removeAdditionalActionButtons(menu);
    }

    // Set or remove required attribute from the field
    if (task === 'required') {
      const item = target.closest('[data-formbuilder]');
      if (!item) return;
      const data = JSON.parse(item.dataset.formbuilder);
      if (!data) return;
      const btn = target;
      if (data.required === true) {
        const badge = item.querySelector('.badge-required');
        if (badge) {
          badge.remove();
        }
        btn.innerHTML = `<span class="icon-lock icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JOPTION_REQUIRED')}`;
      } else {
        const badge = document.createElement('span');
        badge.className = 'badge badge-required text-bg-danger fs-5 fw-medium mt-1 me-0 m-2';
        badge.innerHTML = Joomla.Text._('JOPTION_REQUIRED');
        item.querySelector('.joomla-formbuilder_item-badges').prepend(badge);
        btn.innerHTML = `<span class="icon-unlock icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_REQUIRED_FALSE')}`;
      }
      data.required = !data.required;
      item.dataset.formbuilder = JSON.stringify(data);
      this.setFormItemsData(); // @todo throw error if not found or not possible
    }

    // Show or hide the field
    if (task === 'hide') {
      const item = target.closest('[data-formbuilder]');
      if (!item) return;
      const data = JSON.parse(item.dataset.formbuilder);
      if (!data) return;
      const btn = target;
      if (data.hidden === true) {
        const badge = item.querySelector('.badge-hidden');
        if (badge) {
          badge.remove();
        }
        btn.innerHTML = `<span class="icon-eye-slash icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JHIDE')}`;
      } else {
        const badge = document.createElement('span');
        badge.className = 'badge badge-hidden text-bg-info fs-5 fw-medium mt-1 me-0 m-2';
        badge.innerHTML = Joomla.Text._('COM_FORMBUILDER_FIELD_FORMBUILDER_HIDDEN_LABEL');
        item.querySelector('.joomla-formbuilder_item-badges').appendChild(badge);
        btn.innerHTML = `<span class="icon-eye icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JSHOW')}`;
      }
      data.hidden = !data.hidden;
      item.dataset.formbuilder = JSON.stringify(data);
      this.setFormItemsData(); // @todo throw error if not found or not possible
    }

    // Add a new fieldset
    if (task === 'add-fieldset') {
      const tabParent = this.querySelector('#form-fields-tab');
      if (!tabParent) {
        console.error('Debug: task add-fieldset: form-fields-tab not found');
        return;
      }
      const countTabElements = tabParent.querySelectorAll('joomla-tab-element').length;
      // Add Language String for new Fieldset
      const tabLabel = `COM_FORMBUILDER_FIELDSET_FORM_FIELDS_${countTabElements + 1}_TITLE`;
      this.editLanguageString(tabLabel, 'Fieldset ' + (countTabElements + 1)).then((title) => {
        const tabElement = document.createElement('joomla-tab-element');
        tabElement.id = `form-fields-${countTabElements + 1}`;
        tabElement.setAttribute('name', `${title}`);
        tabElement.setAttribute('role', 'tabpanel');
        tabParent.appendChild(tabElement);
      });

    }

    // Edit Fieldset Title
    if (task === 'edit-fieldset') {
      const tabParent = this.querySelector('#form-fields-tab');
      if (!tabParent) {
        console.error('Debug: task edit-fieldset: form-fields-tab not found');
        return;
      }
      const activeTab = tabParent.querySelector('joomla-tab-element[active]');
      if (!activeTab) {
        Joomla.renderMessages({
          error: [Joomla.Text._('COM_FORMBUILDER_ERROR_EDIT_FIELDSET')]
        });
        return;
      }
      const tabLabel = `COM_FORMBUILDER_FIELDSET_${activeTab.id.toString().toUpperCase().replaceAll('-', '_')}_TITLE`;
      this.editLanguageString(tabLabel).then((title) => {
        if (title === null) return; // User cancelled
        activeTab.setAttribute('name', `${title}`);
        const tabBtn =  tabParent.querySelector(`[aria-controls="${activeTab.id}"]`);
        if (tabBtn) {
          tabBtn.innerHTML = `${title}`;
        }
      });
    }

    // Remove Fieldset
    if (task === 'remove-fieldset') {
      const tabParent = this.querySelector('#form-fields-tab');
      if (!tabParent) {
        console.error('Debug: task remove-fieldset: form-fields-tab not found');
        return;
      }
      const activeTab = tabParent.querySelector('joomla-tab-element[active]');
      if (!activeTab) {
        Joomla.renderMessages({
          error: [Joomla.Text._('COM_FORMBUILDER_ERROR_REMOVE_FIELDSET')]
        });
        return;
      }
      if (activeTab.querySelectorAll('.joomla-formbuilder-item').length > 0) {
        Joomla.renderMessages({
          error: [Joomla.Text._('COM_FORMBUILDER_ERROR_REMOVE_FIELDSET_WITH_ITEMS')]
        });
        return;
      }
      const tabButton = tabParent.querySelector(`[aria-controls="${activeTab.id}"]`);
      activeTab.removeAttribute('active');
      tabButton.setAttribute('aria-selected', 'false');
      // Set the first tab as active
      const newActiveTab = tabParent.querySelectorAll('joomla-tab-element')[0]
      newActiveTab.setAttribute('active', 'true');
      tabParent.querySelector(`[aria-controls="${newActiveTab.id}"]`).setAttribute('aria-selected', 'true');
      // Remove the tab
      activeTab.remove();
      tabButton.remove();
    }

  }

  editLanguageString(langKey, langString = '') {
    return new Promise((resolve) => {
      let newValue = '';
      if (!langString || typeof langString !== 'string' || langString.trim() === '') {
        // Prompt for new value
        newValue = prompt(
          Joomla.Text._('COM_FORMBUILDER_EDIT_LANGUAGE_STRING_PROMPT'),
          Joomla.Text._(langString)
        );
        if (newValue === null) return resolve(null); // User cancelled
      }
      const data = {
        key: `${langKey}`,
        override: `${langString || newValue}`,
        id: `${langKey}`
      };
      data[Joomla.getOptions('csrf.token', '')] = 1;

      Joomla.request({
        url: `index.php?option=com_formbuilder&task=form.editlang&format=json`,
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        data: JSON.stringify(data),
        onSuccess: (resp) => {
          const response = JSON.parse(resp);
          if (response.error && response.message) {
            Joomla.renderMessages({ error: [response.message] });
          }
          if (response.messages) {
            Joomla.renderMessages(response.messages);
          }
          if (response.data && response.data.title) {
            resolve(response.data.title);
          } else {
            resolve(langString || newValue);
          }
        },
        onError: () => {
          Joomla.renderMessages({
            error: [Joomla.Text._('COM_FORMBUILDER_LANGUAGE_STRING_ERROR_AJAX')]
          });
          resolve(null);
        },
      });
    });
  }

  /**
 * Functionality for the list container once and item has been dropped
 * @param {object} event drop
 */
  // __dzDropHandler(event) {
  onjoomla_drop_list_dropped(event) {

    // const value = this.getFormItemsData();

    // this.querySelector('input[name="jform[params][formbuilder]"]').value = JSON.stringify(value);
    this.setFormItemsData(); // @todo throw error if not found or not possible

    const slotName = event.detail.originEvent.target.querySelector('[slot]')
      ? event.detail.originEvent.target.querySelector('[slot]').getAttribute('slot')
      : event.detail.originEvent.target.closest('[slot]')?.getAttribute('slot') || '';


    const menu = event.detail.droppedElement.querySelector('.dropdown-menu');

    if (menu) {
      // Change the menu item text for add or remove from form
      menu.querySelector('[data-task="move"]').innerHTML = (slotName === 'field-form')
        ? `<span class="icon-minus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JGLOBAL_FIELD_REMOVE')}`
        : `<span class="icon-plus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JGLOBAL_FIELD_ADD')}`;
      // Add or remove sortable functionality
      if (slotName === 'field-form') {
        this.addSortable(menu);
        this.addAdditionalActionButtons(menu);
      } else {
        this.removeSortable(menu);
        this.removeAdditionalActionButtons(menu);
      }
    }
  }

  /**
   * Add sortable functionality to the menu
   * @param {HTMLElement} menu The menu element
   */
  addSortable = (menu) => {
    if (!menu.querySelector('[data-task="down"]')) {
      let btnDown = document.createElement('li');
      btnDown.innerHTML = `<button tabindex="-1" role="button" class="dropdown-item" data-task="down"><span class="icon-arrow-down icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_DOWN')}</button>`;
      menu.firstElementChild.after(btnDown);
    }
    if (menu.querySelector('[data-task="up"]')) return;
    let btnUp = document.createElement('li');
    btnUp.innerHTML = `<button tabindex="-1" role="button" class="dropdown-item" data-task="up"><span class="icon-arrow-up icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_UP')}</button>`;
    menu.firstElementChild.after(btnUp);
  };

  /**
   * Remove sortable functionality from the menu
   * @param {HTMLElement} menu The menu element
   */
  removeSortable = (menu) => {
    menu.querySelector('[data-task="down"]')?.remove();
    menu.querySelector('[data-task="up"]')?.remove();
  };

  /**
   * Add additional action buttons to the menu
   * @param {HTMLElement} menu The menu element
   */
  addAdditionalActionButtons = (menu) => {
    const data = JSON.parse(menu.closest('[data-formbuilder]').dataset.formbuilder);
    if (!menu.querySelector('[data-task="required"]')) {
      let btnRequired = document.createElement('li');
      btnRequired.innerHTML =
      `<button tabindex="-1" role="button" class="dropdown-item" data-task="required">` +
        `<span class="icon-${data.required ? 'unlock' : 'lock'} icon-fw me-1" aria-hidden="true"></span>${data.required ? Joomla.Text._('COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_REQUIRED_FALSE') : Joomla.Text._('JOPTION_REQUIRED')}` +
      `</button>`;
      menu.firstElementChild.after(btnRequired);
    }
    if (menu.querySelector('[data-task="hide"]')) return;
    let btnHide = document.createElement('li');
    btnHide.innerHTML =
    `<button tabindex="-1" role="button" class="dropdown-item" data-task="hide">` +
      `<span class="icon-eye${data.hidden ? '': '-slash'} icon-fw me-1" aria-hidden="true"></span>${data.hidden ? Joomla.Text._('JSHOW') : Joomla.Text._('JHIDE')}` +
    `</button>`;
    menu.firstElementChild.after(btnHide);
  }

  /**
   * Remove additional action buttons from the menu
   * @param {HTMLElement} menu The menu element
   */
  removeAdditionalActionButtons = (menu) => {
    menu.querySelector('[data-task="required"]')?.remove();
    menu.querySelector('[data-task="hide"]')?.remove();
  }

  setFormItemsData() {
    const value = this.getFormItemsData();
    this.querySelector('input[name="jform[formbuilder]"], input[name="jform[params][formbuilder]"], input[name="jform[attribs][formbuilder]"], input[name="jform[formbuilder][formbuilder]"]').value = JSON.stringify(value);
  }

  /**
   * Returns an array of objects representing the form items.
   * Each object contains the index, the element, and parsed dataset data.
   * @returns {Array<Object>}
   */
  getFormItemsData() {
    const items = this.querySelector('joomla-drop-list.joomla-formbuilder-form-items')?.querySelectorAll('.joomla-formbuilder-item[data-formbuilder]');
    if (!items) return {};
    const result = {};
    items?.forEach((item, index) => {
      const tabElement = item.closest('joomla-tab-element');
      const groupKey = tabElement ? tabElement.id : 'form-fields-global';
      if (!result[groupKey]) {
        result[groupKey] = [];
      }
      result[groupKey].push(item.dataset.formbuilder ? JSON.parse(item.dataset.formbuilder) : {});
    });
    return result;
  }
}

customElements.define('joomla-form-builder', JoomlaFormBuilder);

export default JoomlaFormBuilder;
