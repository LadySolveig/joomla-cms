/**
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * JoomlaDropList class for Joomla Drop List implementation.
 * With use of <joomla-drop-list> custom element as drop list holder.
 */
class JoomlaDropList extends HTMLElement {
  constructor() {
    super();

    const shadowRoot = this.attachShadow({ mode: "open" });
    shadowRoot.innerHTML = `
<slot></slot>
`;

    if (this.hasAttribute("slotName")) {
      this.shadowRoot.querySelector("slot").setAttribute("name", this.getAttribute("slotName"));
    }

    // Add the event listeners
    this.addEventListener("drop", this);
    this.addEventListener("dragover", this);
    this.addEventListener("dragleave", this);

    // Set the dropzone attribute
    this.setAttribute("dropzone", "move");
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

  /**
   * Functionality for the list container once and item has been dropped
   * @param {object} event drop
   */
  ondrop(event) {
    event.preventDefault();

    if (!this.__draggingElement) {
      this.removeAttribute("active");
      return;
    }

    const slotName = this.hasAttribute("slotName") ? this.getAttribute("slotName") : null;

    // Add the slot if not exists yet
    if (slotName && !this.querySelector(`span[slot="${slotName}"]`)) {
      this.insertAdjacentHTML('beforeEnd', `<span slot="${slotName}"></span>`);
    }

    // Get all items in this container
    const items = slotName
      ? this.querySelector(`span[slot="${slotName}"]`).querySelectorAll('joomla-drop-list-item[draggable="true"]')
      : this.querySelectorAll('joomla-drop-list-item[draggable="true"]');

    if (items.length >= 1) {
      // Get the clientY from the drop position from the event
      const clientY = event?.detail?.clientY || event?.clientY;
      // Get the closest element to the dragging element
      const dropElementClosest = [...items].reduce((closest, curr) => {
        if (curr === this.__draggingElement) {
          return closest;
        }

        // Reset the boxShadow for positioning
        curr.style["boxShadow"] = '';

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
        // Emit the drop event
        this.emit('dropped', { originEvent: event , droppedElement: this.__draggingElement});
        this.__draggingElement = null;
        this.removeAttribute("active");
        return;
      }
    }

    let dragContainer = this;
    // Append the dragging element to the container
    if (slotName) {
      dragContainer = this.querySelector(`span[slot="${slotName}"]`);
    };

    if (dragContainer.querySelector('joomla-tab-element[active]')) {
      // If the container has a tab element with active attribute, append to that
      dragContainer = dragContainer.querySelector('joomla-tab-element[active]');
    }

    dragContainer.appendChild(this.__draggingElement);

    // Emit the drop event
    this.emit('dropped', { originEvent: event , droppedElement: this.__draggingElement});

    this.__draggingElement = null;
    this.removeAttribute("active");
  }

  /**
   * Functionality for the list container once we are leave the dropzone
    */
  ondragleave(event) {
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
  ondragover(event) {
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
      } else {
        this.__draggingElement = document.querySelector("[dragging]");
      }
    }
  }

  /**
     * Emit a custom event
     * @param  {Object} detail Any details to pass along with the event
     */
  emit (task = '', detail = {}) {
    // Create a new event
    let event = new CustomEvent(`joomla-drop-list${task ? `:${task}` : ''}`, {
        bubbles: true,
        cancelable: false,
        detail: detail
    });

    // Dispatch the event
    return this.dispatchEvent(event);
  };

};

customElements.define('joomla-drop-list', JoomlaDropList);

export default JoomlaDropList;
