/**
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * JoomlaDropListItem class for Joomla Drop List Item implementation.
 * With use of <joomla-drop-list-item> custom element as drop list item holder.
 */
class JoomlaDropListItem extends HTMLElement {
  constructor() {
    super();

    // You _cannot_ just set draggable without the string "true";
    // it will not work
    this.setAttribute("draggable", "true");

    const shadowRoot = this.attachShadow({ mode: "open" });
    shadowRoot.innerHTML = `
<slot></slot>
`;

    // Add the event listeners
    this.addEventListener("dragstart", this);
    this.addEventListener("dragend", this);
    this.addEventListener("dragenter", this);
    this.addEventListener("dragover", this);
    this.addEventListener("dragleave", this);
    this.addEventListener('click', this);
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

  // Important: we need to understand who's dragging so we can
  // grab in the dropzone
  ondragstart(event) {
    event.dataTransfer.setData("text/html", "test");
    this.setAttribute("dragging", "");
  }

  ondragend(event) {
    this.removeAttribute("over");
    this.removeAttribute("dragging");
  }

  ondragover(event) {
    const clientY = event?.detail?.clientY || event?.clientY;
    const closest = event.target.closest('joomla-drop-list-item');
    if (!closest || closest.hasAttribute('dragging')) return;
    const closestBox = closest.getBoundingClientRect();
    const offset = clientY - (closestBox.top + (closestBox.height / 2));
    if (offset >= 0 || offset < closest.offset) {
      closest.style['boxShadow'] = '0 8px var(--drop-list-bg), 0 10px var(--shadow-positioning-color)'; // @todo depending on gap
      return
    }
    closest.style.boxShadow = '';
    closest.style['boxShadow'] = '0 -8px var(--drop-list-bg), 0 -10px (--shadow-positioning-color)'; // @todo depending on gap
  }

  ondragenter(event) {
    if (this.hasAttribute("dragging")) {
      this.removeAttribute("over");
    } else {
      this.setAttribute("over", "");
    }
  }

  ondragleave(event) {
    this.removeAttribute("over");
    event.target.closest('joomla-drop-list-item').style["boxShadow"] = '';
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

    if (task === 'up') {
      const item = event.target instanceof JoomlaDropListItem ? event.target : event.target.closest('joomla-drop-list-item');
      if (!item) return;
      const prev = item.previousElementSibling;
      const container = item.parentNode;
      if (prev) {
        container.insertBefore(item, prev);
        this.emit('up', { item: item });
      }
    }

    if (task === 'down') {
      const item = event.target instanceof JoomlaDropListItem ? event.target : event.target.closest('joomla-drop-list-item');
      if (!item) return;
      const next = item.nextElementSibling;
      const container = item.parentNode;
      if (next) {
        container.insertBefore(next, item);
        this.emit('down', { item: item });
      }
    }
  }

  /**
     * Emit a custom event
     * @param  {Object} detail Any details to pass along with the event
     */
  emit (task = '', detail = {}) {

    // Create a new event
    let event = new CustomEvent(`joomla-drop-list-item${task ? `:${task}` : ''}`, {
        bubbles: true,
        cancelable: false,
        detail: detail
    });

    // Dispatch the event
    return this.dispatchEvent(event);

  }
};

customElements.define('joomla-drop-list-item', JoomlaDropListItem);

export default JoomlaDropListItem;
