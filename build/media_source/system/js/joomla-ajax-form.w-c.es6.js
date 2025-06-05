/**
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// eslint-disable-next-line import/no-unresolved, max-classes-per-file
import { JoomlaEditor } from 'editor-api';

if (!window.Joomla) {
  throw new Error('JoomlaEditors API require Joomla to be loaded.');
}

/**
 * JoomlaAjaxForm class for Joomla Ajax Form implementation.
 * With use of <joomla-ajax-formr> custom element as ajax form holder.
 */
class JoomlaAjaxForm extends HTMLElement {

    /**
     * The class constructor object
     */
    constructor () {

        // Always call super first in constructor
        super();

        if (!Joomla) {
            throw new Error('Joomla API is not properly initiated');
        }

        // Add a form status element
        let announce = document.querySelector('#system-message-container');
        if (!announce) {
          announce = document.createElement('div');
          announce.setAttribute('role', 'status');
          this.prepend(announce);
        }

        // Set base properties
        this.announce = announce;
            //    console.log(this.innerHTML);
        this.form = this.querySelector('form');

        // Define options
        this.preventDefault = this.hasAttribute('prevent-default');
        let exludeTask = this.getAttribute('excludeTask');
        this.excludeTasks = exludeTask ? exludeTask.split(',').map(exludeTask => exludeTask.trim()) : null;
        this.msgSubmitting = this.getAttribute('msg-submitting') ?? 'Submitting...';
        this.msgSuccess = this.getAttribute('msg-success') ?? 'Success!';
        this.msgError = this.getAttribute('msg-error') ?? 'Something went wrong. Please try again.';
        let target = this.getAttribute('target');
        this.targets = target ? target.split(',').map(target => target.trim()) : null;
    }

    connectedCallback () {
      this.form = this.querySelector('form');
      // Listen for events
      this.form.addEventListener('submit', this);
    }

    disconnectedCallback () {
      // Listen for events
      this.form.removeEventListener('submit', this);
    }

    /**
     * Handle Events
     * @param  {Event} event The event object
     */
    async handleEvent (event) {


      // Find taskInput element in the DOM
      let taskInput = this.querySelector('input[name="task"]');

      // console.log('handleEvent', taskInput);

      if (taskInput) {
          // Check each excludeTask
          for (let task of this.excludeTasks) {

              if (taskInput.value === task) return;
          }
      }

      // If the form is already submitting,
      // OR if default should be prevented
      // Stop form from reloading the page
      if (this.isDisabled() || this.preventDefault) {
          event.preventDefault();
          console.log('prevent default submit - handle Event');
      }

      // If the form is already submitting, do nothing
      // Otherwise, disable future submissions
      if (this.isDisabled()) return;
      this.disable();

      // Emit a submit event (useful for validations)
      if (!this.emit('submit', this.getData())) return;

      try {
          if (document.body.querySelectorAll('joomla-core-loader').length === 0) {
            document.body.appendChild(document.createElement('joomla-core-loader'));
          }
          // Show status message
          this.showStatus(this.msgSubmitting);

          // If not preventing default behavior, end early
          if (!this.preventDefault) return;

          // Remove all potenital editor instances to reinitialize
          this.querySelectorAll('textarea').forEach(elem => {
            const editor = JoomlaEditor.get(elem.id);
            if (!editor) return;
            JoomlaEditor.unregister(elem.id);
          });

          // Call the API
          let {action, method} = event.target;
          let response = await fetch(action, {
              method,
              body: this.serialize(),
              headers: {
                  'Content-type': 'application/x-www-form-urlencoded'
              }
          });

          // If there's an error, throw
          if (!response.ok) throw response;

          // If UI should be updated, do so
          if (this.targets) {
              let str = await response.clone().text();

              this.render(str);

              // Dispatch joomla updated event to update the UI
              document.dispatchEvent(new CustomEvent('joomla:updated', {
                bubbles: true,
                cancelable: true,
              }));
          }

          // Emit a success event
          this.emit('success', response.clone());

          document.body.querySelectorAll('joomla-core-loader')?.forEach(elem => {
            elem.remove();
          });

          // Show success URL
          this.showStatus(this.msgSuccess);

          // Reinitalize the form
          this.form = this.querySelector('form');
          // Listen for events
          this.form.addEventListener('submit', this);

      } catch (error) {
          console.warn(error);
          this.showStatus(this.msgError, 'warning');
          this.emit('error', error);
          document.body.querySelectorAll('joomla-core-loader')?.forEach(elem => {
            elem.remove();
          });
      } finally {
          this.enable();
      }

    }
    /**
     * Emit a custom event
     * @param  {String} type   The event type
     * @param  {Object} detail Any details to pass along with the event
     */
    emit (type, detail = {}) {

        // Create a new event
        let event = new CustomEvent(`joomla-ajax-form:${type}`, {
            bubbles: true,
            cancelable: true,
            detail: detail
        });

        // Dispatch the event
        return this.dispatchEvent(event);

    }

    /**
     * Disable a form so I can't be submitted while waiting for the API
     */
    disable () {
        this.setAttribute('form-submitting', '');
    }

    /**
     * Enable a form after the API returns
     */
    enable () {
        this.removeAttribute('form-submitting');
    }

    /**
     * Check if a form is submitting to the API
     * @return {Boolean} If true, the form is submitting
     */
    isDisabled () {
        return this.hasAttribute('form-submitting');
    }

    /**
     * Get the value of a form field by its [name]
     * @param  {String} id The field name
     * @return {String}    The value
     */
    getFieldValue (id) {

        // Get the field
        let field = this.form.querySelector(`[name="${id}"]`);
        if (!field) return;

        // If select element, get selected element text
        if (field.tagName.toLowerCase() === 'select') {
            return field.options[field.selectedIndex].textContent;
        }

        // Otherwise, return value
        return field.value;

    }

    /**
     * Replace placeholders in message with field values
     * @param  {String} msg The message text
     * @return {String}     The message text with placeholders replaced
     */
    getMessageText (msg) {
        let instance = this;
        return msg.replace(/\$\{([^}]+)\}/g, function (match) {

            // Remove the wrapping curly braces
            match = match.slice(2, -1);

            // Get the field value
            let value = instance.getFieldValue(match);

            // Return the string
            if (!value) return '{{' + match + '}}';
            return value;

        });
    }

    /**
     * Update the form status in a field
     * @param  {String} msg The message to display
     */
    showStatus (msg, type = 'success') {
      // Joomla.renderMessages({ notice: [result.message] });
      Joomla.renderMessages({[type]: [msg]});
        // this.announce.innerHTML = 'this.getMessageText(msg);
    }

    /**
     * Serialize all form data into an encoded query string
     * @return {String} The serialized form data
     */
    serialize () {
        let data = new FormData(this.form);
        let params = new URLSearchParams();
        for (let [key, val] of data) {
            params.append(key, val);
        }
        return params.toString();
    }

    /**
     * Serialize all form data into an object
     * @return {Object} The serialized form data
     */
    getData () {
        let data = new FormData(this.form);
        let obj = {};
        for (let [key, value] of data) {
            if (obj[key] !== undefined) {
                if (!Array.isArray(obj[key])) {
                    obj[key] = [obj[key]];
                }
                obj[key].push(value);
            } else {
                obj[key] = value;
            }
        }
        return obj;
    }

    /**
     * Render the updated UI into the DOM
     * @param  {String} str The HTML string for the updated UI
     */
    render (str) {

        // Parse returned string into HTML
        let parser = new DOMParser();
        let doc = parser.parseFromString(str, 'text/html');
        if (!doc.body) return;

        // Render each target
        for (let selector of this.targets) {

            // Find target element in the DOM
            let target = document.querySelector(selector);
            if (!target) continue;

            // Get the target element from the returned HTML
            let updated = doc.body.querySelector(selector);
            if (!updated) continue;

            // Update the UI
            target.replaceWith(updated);

        }

        doc.querySelectorAll('.joomla-script-options.new').forEach(element => {
            const str = element.text || element.textContent;
            const options = JSON.parse(str);
            Joomla.optionsStorage = options || {};
            const messages = Joomla.getOptions('joomla.messages');
            if (messages) {
                Object.keys(messages)
                .map((message) => Joomla.renderMessages(messages[message]));
            }
        });

    }

    /**
     * Reset the form element values
     */
    reset () {
        this.form.reset();
    }

};

customElements.define('joomla-ajax-form', JoomlaAjaxForm);

export default JoomlaAjaxForm;
