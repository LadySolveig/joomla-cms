/**
 * @copyright  (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

if (!window.Joomla) {
  throw new Error('JoomlaEditors API require Joomla to be loaded.');
}

/**
   * Add sortable functionality to the menu
   * @param {HTMLElement} menu The menu element
   */
const addSortable = (menu) => {
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
const removeSortable = (menu) => {
  menu.querySelector('[data-task="down"]').remove();
  menu.querySelector('[data-task="up"]').remove();
};

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
  .joomla-formbuilder-available-items {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    height: auto;
    border: 2px dotted var(--atum-btn-info);
  }
  .joomla-formbuilder-form-items {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    height: auto;
    border: 2px dotted var(--atum-btn-info);
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
  :host(.dropstart .drop down-toggle::before) {
    border: none;
    display: none;
  }
  div[active] {
    border-color: var(--template-bg-dark-60);
    border-width: 3px;
    background-color: var(--template-bg-dark-30);
    color: white;
  }
  slot[name="field-form"],
  slot[name="field-available"] {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    height: auto;
  }
</style>
<div class="joomla-formbuilder-form-items">
    <slot name="field-form">
    </slot>
</div>
<div class="joomla-formbuilder-available-items">
    <slot name="field-available">
    </slot>
</div>`;

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
   * Internal. Connected Callback.
   */
  connectedCallback() {

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

    // Get template parts
    this.formbuilderAvailableItemsContainer = this.formbuilder.querySelector('.joomla-formbuilder-available-items');
    this.formbuilderFormItemsContainer = this.formbuilder.querySelector('.joomla-formbuilder-form-items');
    this.formbuilderTmplH = this.formbuilder.querySelector('.joomla-formbuilder-header');

    if (!this.formbuilderAvailableItemsContainer) {
      throw new Error('The availabel items container not found in the template.');
    }

    // Add the event listeners
    // When an element is dragged into or out of this container.
    this.formbuilderAvailableItemsContainer.addEventListener('dragleave', this.__dzDragLeave.bind(this.formbuilderAvailableItemsContainer));
    // During and right after the drop on this container.
    this.formbuilderAvailableItemsContainer.addEventListener('drop', this.__dzDropHandler.bind(this.formbuilderAvailableItemsContainer));
    this.formbuilderAvailableItemsContainer.addEventListener('dragover', this.__dzDragover.bind(this.formbuilderAvailableItemsContainer));

    // Add the dropzone attribute
    this.formbuilderAvailableItemsContainer.setAttribute("dropzone", "move");

    if (!this.formbuilderFormItemsContainer) {
      throw new Error('The form items container not found in the template.');
    }

    // Add the event listeners for the dropzone containers
    // When an element is dragged into or out of this container.
    this.formbuilderFormItemsContainer.addEventListener('dragleave', this.__dzDragLeave.bind(this.formbuilderFormItemsContainer));
    // During and right after the drop on this container.
    this.formbuilderFormItemsContainer.addEventListener('drop', this.__dzDropHandler.bind(this.formbuilderFormItemsContainer));
    this.formbuilderFormItemsContainer.addEventListener('dragover', this.__dzDragover.bind(this.formbuilderFormItemsContainer));

    // Add the dropzone attribute
    this.formbuilderFormItemsContainer.setAttribute("dropzone", "move");

    // Setup event listeners
		this.addEventListener('click', this);

    return this;
  }

  /**
   * Handle event listeners
   * @param  {Event} event The event object
   */
  handleEvent(event) {
    this[`on${event.type}`](event);
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
        ? `<span class="icon-minus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_MOVE_REMOVE')}`
        : `<span class="icon-plus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_MOVE_ADD')}`;

      // Add or remove sortable functionality
      const menu = btn.closest('.dropdown-menu');
      if (!menu) return;
      if (slotName === 'field-form') {
        addSortable(menu);
        return;
      }
      removeSortable(menu);

    }

    if (task === 'up') {
      const item = event.target.closest('joomla-drop-list-item');
      if (!item) return;
      const prev = item.previousElementSibling;
      const container = item.parentNode;
      if (prev) {
        container.insertBefore(item, prev);
      }
    }

    if (task === 'down') {
      const item = event.target.closest('joomla-drop-list-item');
      if (!item) return;
      const next = item.nextElementSibling;
      const container = item.parentNode;
      if (next) {
        container.insertBefore(next, item);
      }
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
        btn.innerHTML = `<span class="icon-lock icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_REQUIRED_TRUE')}`;
      } else {
        const badge = document.createElement('span');
        badge.className = 'badge badge-required text-bg-danger fs-5 fw-medium mt-1 me-0 m-2';
        badge.innerHTML = Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_REQUIRED_LABEL');
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
        btn.innerHTML = `<span class="icon-eye-slash icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_HIDDEN_HIDE')}`;
      } else {
        const badge = document.createElement('span');
        badge.className = 'badge badge-hidden text-bg-info fs-5 fw-medium mt-1 me-0 m-2';
        badge.innerHTML = Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_HIDDEN_LABEL');
        item.querySelector('.joomla-formbuilder_item-badges').appendChild(badge);
        btn.innerHTML = `<span class="icon-eye icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_HIDDEN_SHOW')}`;
      }
      data.hidden = !data.hidden;
      item.dataset.formbuilder = JSON.stringify(data);
    }

  }

  /**
 * Functionality for the list container once and item has been dropped
 * @param {object} event drop
 */
  __dzDropHandler(event) {
    event.preventDefault();

    if (!this.__draggingElement) {
      return;
    }

    // Only stop probagate if the dragged element is acceptable to the container. // @todo
    event.stopPropagation();

    // Get the correct slotname in this context
    const slotName = (this.classList.contains('joomla-formbuilder-available-items') ? 'field-available' : 'field-form');

    // Add the slot if not exists yet
    if (!this.getRootNode().host.querySelector(`span[slot="${slotName}"]`)) {
      this.getRootNode().host.insertAdjacentHTML('beforeEnd', `<span slot="${slotName}"></span>`);
    }
    // Get all items in this container
    const items = this.getRootNode().host.querySelector(`span[slot="${slotName}"]`).querySelectorAll('joomla-drop-list-item');

    if (items.length >= 1) {
      // Get the clientY from the drop position from the event
      const clientY = event?.detail?.clientY || event?.clientY;
      // Get the closest element to the dragging element
      const dropElementClosest = [...items].reduce((closest, curr) => {
        if (curr === this.__draggingElement) {
          return closest;
        }
        const currBox = curr.getBoundingClientRect();
        const offset = clientY - (currBox.top + (currBox.height / 2));
        if (offset >= 0 || offset < closest.offset) {
          return closest;
        }

        return {offset, element: curr};
      }, {offset: Number.NEGATIVE_INFINITY});

      // Insert the dragging element before the closest element if possible
      if (dropElementClosest.element) {
        dropElementClosest.element.before(this.__draggingElement);
      } else {
        this.getRootNode().host.querySelector(`span[slot="${slotName}"]`).appendChild(this.__draggingElement);
      }
    } else {
      // Append the dragging element to the container
      this.getRootNode().host.querySelector(`span[slot="${slotName}"]`).appendChild(this.__draggingElement);
    }

    const menu = this.__draggingElement.querySelector('.dropdown-menu');

    if (menu) {
      // Change the menu item text for add or remove from form
      menu.querySelector('[data-task="move"]').innerHTML = (slotName === 'field-form')
        ? `<span class="icon-minus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_MOVE_REMOVE')}`
        : `<span class="icon-plus icon-fw me-1" aria-hidden="true"></span>${Joomla.Text._('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_MOVE_ADD')}`;
      // Add or remove sortable functionality
      (slotName === 'field-form') ? addSortable(menu) : removeSortable(menu);
    }

    // Reset the dragging element and remove the active attribute
    this.__draggingElement = null;
    this.removeAttribute("active");
  }

  /**
   * Functionality for the list container once we leave the dropzone
   * @param {object} event drop
   */
  __dzDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();

    this.__draggingElement = null;

    // Remove the active attribute.
    this.removeAttribute("active");
  }

  /**
   * Functionality for the list container once we are hover on the list
   * @param {object} event drop
   */
  __dzDragover(event) {
    event.preventDefault();
    event.stopPropagation();

    // Add the active attribute.
    this.setAttribute("active", "");

    let found;

    if (!this.__draggingElement) {
      // find what we're looking for in the composed path that isn't a slot
      found = event.composedPath().find((i) => {
        if (i.nodeType === 1 && i.nodeName !== "SLOT") {
          return i;
        }
      });

      if (found) {
        // find where we are deep in the change
        const theLowestShadowRoot = found.getRootNode();
        this.__draggingElement = theLowestShadowRoot.querySelector(
          "[dragging]"
        );
        if (!this.__draggingElement) {
          this.__draggingElement = document.querySelector("[dragging]");
        }
      } else {
        this.__draggingElement = document.querySelector("[dragging]");
      }
    }
  }
}

customElements.define('joomla-form-builder', JoomlaFormBuilder);

export default JoomlaFormBuilder;
