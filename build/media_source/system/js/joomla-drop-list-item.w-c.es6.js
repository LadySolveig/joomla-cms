/**
 * @copyright  (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
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
<style>
 :host {
 display: block;
 padding: 0.5rem;
 background-color: var(--border-color);
 margin: 0.25rem;
 }
 :host([dragging]) {
 background-color: var(----button-and-icon-color);
 color: var(--template-text-light);
 }
</style>

<slot></slot>
`;

    // Add the event listeners
    this.addEventListener("dragstart", this);
    this.addEventListener("dragend", this);
    this.addEventListener("drop", this);
    this.addEventListener("dragover", this);
    this.addEventListener("dragleave", this);
  }

  /**
   * Handle event listeners
   * @param  {Event} event The event object
   */
  handleEvent(event) {
    this[`on${event.type}`](event);
  }

  // Important: we need to understand who's dragging so we can
  // grab in the dropzone
  ondragstart(event) {
    event.dataTransfer.setData("text/html", "test");
    this.setAttribute("dragging", "");
  }

  ondragend() {
    this.removeAttribute("over");
    this.removeAttribute("dragging");
  }

  ondragover() {
    if (this.hasAttribute("dragging")) {
      this.removeAttribute("over");
    } else {
      this.setAttribute("over", "");
    }
  }

  ondragleave() {
    this.removeAttribute("over");
  }
};

customElements.define('joomla-drop-list-item', JoomlaDropListItem);

export default JoomlaDropListItem;
