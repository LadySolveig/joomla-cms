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
<style>
  :host {
    display: grid;
    grid-template-columns: 2fr minmax(200px, 1fr);
    grid-gap: 10px;
    padding: 10px;
    min-height: 300px;
    width: 100%;
  }
  @media (width <= 768px) {
    :host {
      grid-template-columns: 1fr;
      grid-template-rows: minmax(min-content, 1.5fr) minmax(min-content, .5fr);
    }
  }
  :host > * {
    box-sizing: border-box;
  }
  .joomla-formbuilder-item {
    position: relative;
    width: 100%;
    height: 50px;
    color: var(--login-label-color);
    text-align: center;
    line-height: var(--body-line-height);
    background: var(--form-control-bg);
    border: var(--form-control-border);
  }
  slot[name="field-form"],
  slot[name="field-available"] {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    height: auto;
  }
</style>
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
	}

  /**
   * Handle event listeners
   * @param  {Event} event The event object
   */
  handleEvent(event) {
    this[`on${event.type.replaceAll('-', '_').replaceAll(':', '_')}`](event);
  }

  onjoomla_drop_list_item_up(event) {
    console.log('Debug: onjoomla_drop_list_item_up:', event);
  }

  onjoomla_drop_list_item_down(event) {
    console.log('Debug: onjoomla_drop_list_item_down:', event);
  }

  /**
   * Clear elements on click events
   * @param  {Event} event The event object
   */
  onclick(event) {

    // Get the task
    let task = event.target.getAttribute('data-task');
    if (!task) return;

    // Prevent submit
    event.preventDefault();

    // If move, move the element
    if (task === 'move') {
      const btn = event.target;
      const item = btn.closest('joomla-drop-list-item');
      if (!item) return;
      const slotName = item.closest('[slot="field-available"]') ? 'field-form' : 'field-available';
      if (!this.querySelector(`span[slot="${slotName}"]`)) {
        this.insertAdjacentHTML('beforeEnd', `<span slot="${slotName}"></span>`);
      }
      this.querySelector(`span[slot="${slotName}"]`).appendChild(item);
      // Change the menu item text for add or remove from form
      btn.innerHTML = (slotName === 'field-form')
        ? `<span class="icon-minus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JGLOBAL_FIELD_REMOVE')}`
        : `<span class="icon-plus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JGLOBAL_FIELD_ADD')}`;

      // Add or remove sortable functionality
      const menu = btn.closest('.dropdown-menu');
      if (!menu) return;
      if (slotName === 'field-form') {
        this.addSortable(menu);
        return;
      }
      this.removeSortable(menu);

    }

    if (task === 'required') {
      const item = event.target.closest('[data-formbuilder]');
      if (!item) return;
      console.log('Debug: task required:', item);
      const data = JSON.parse(item.dataset.formbuilder);
      if (!data) return;
      const btn = event.target;
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
        btn.innerHTML = `<span class="icon-unlock icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_REQUIRED_FALSE')}`;
      }
      data.required = !data.required;
      item.dataset.formbuilder = JSON.stringify(data);
    }

    if (task === 'hide') {
      const item = event.target.closest('[data-formbuilder]');
      if (!item) return;
      console.log('Debug: task hide:', item);
      const data = JSON.parse(item.dataset.formbuilder);
      if (!data) return;
      const btn = event.target;
      if (data.hidden === true) {
        const badge = item.querySelector('.badge-hidden');
        if (badge) {
          badge.remove();
        }
        btn.innerHTML = `<span class="icon-eye-slash icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JHIDE')}`;
      } else {
        const badge = document.createElement('span');
        badge.className = 'badge badge-hidden text-bg-info fs-5 fw-medium mt-1 me-0 m-2';
        badge.innerHTML = Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_HIDDEN_LABEL');
        item.querySelector('.joomla-formbuilder_item-badges').appendChild(badge);
        btn.innerHTML = `<span class="icon-eye icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JSHOW')}`;
      }
      data.hidden = !data.hidden;
      item.dataset.formbuilder = JSON.stringify(data);
    }

  }

  /**
 * Functionality for the list container once and item has been dropped
 * @param {object} event drop
 */
  // __dzDropHandler(event) {
  onjoomla_drop_list_dropped(event) {

    console.log('Debug: onjoomla_drop_list_drop:', event.detail.originEvent, event.detail.droppedElement);

    const slotName = event.detail.originEvent.target.querySelector('[slot]') ? event.detail.originEvent.target.querySelector('[slot]').getAttribute('slot') : '';

    console.log('Debug: slotName:', slotName);

    const menu = event.detail.droppedElement.querySelector('.dropdown-menu');

    if (menu) {
      // Change the menu item text for add or remove from form
      menu.querySelector('[data-task="move"]').innerHTML = (slotName === 'field-form')
        ? `<span class="icon-minus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JGLOBAL_FIELD_REMOVE')}`
        : `<span class="icon-plus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('JGLOBAL_FIELD_ADD')}`;
      // Add or remove sortable functionality
      (slotName === 'field-form') ? this.addSortable(menu) : this.removeSortable(menu);
    }
  }

  /**
   * Add sortable functionality to the menu
   * @param {HTMLElement} menu The menu element
   */
  addSortable = (menu) => {
    if (!menu.querySelector('[data-task="down"]')) {
      let btnDown = document.createElement('li');
      btnDown.innerHTML = `<button role="button" class="dropdown-item" data-task="down"><span class="icon-arrow-down icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_DOWN')}</button>`;
      menu.firstElementChild.after(btnDown);
    }
    if (menu.querySelector('[data-task="up"]')) return;
    let btnUp = document.createElement('li');
    btnUp.innerHTML = `<button role="button" class="dropdown-item" data-task="up"><span class="icon-arrow-up icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_UP')}</button>`;
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
}

customElements.define('joomla-form-builder', JoomlaFormBuilder);

export default JoomlaFormBuilder;
