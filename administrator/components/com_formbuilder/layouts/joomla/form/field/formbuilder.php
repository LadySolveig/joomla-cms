<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form     $form            The form instance for render the available fields.
 * @var   array    $formFields      The form fields that are already allocated to the form.
 * @var   string   $autocomplete    Autocomplete attribute for the field.
 * @var   boolean  $autofocus       Is autofocus enabled?
 * @var   string   $class           Classes for the input.
 * @var   string   $description     Description of the field.
 * @var   boolean  $disabled        Is this field disabled?
 * @var   string   $group           Group the field belongs to. <fields> section in form XML.
 * @var   boolean  $hidden          Is this field hidden in the form?
 * @var   string   $hint            Placeholder for the field.
 * @var   string   $id              DOM id of the field.
 * @var   string   $label           Label of the field.
 * @var   string   $labelclass      Classes to apply to the label.
 * @var   boolean  $multiple        Does this field support multiple values?
 * @var   string   $name            Name of the input field.
 * @var   string   $onchange        Onchange attribute for the field.
 * @var   string   $onclick         Onclick attribute for the field.
 * @var   string   $pattern         Pattern (Reg Ex) of value of the form field.
 * @var   boolean  $readonly        Is this field read only?
 * @var   boolean  $repeat          Allows extensions to duplicate elements.
 * @var   boolean  $required        Is this field required?
 * @var   integer  $size            Size attribute of the input.
 * @var   boolean  $spellcheck       Spellcheck state for the form field.
 * @var   string   $validate        Validation rules to apply.
 * @var   string   $value           Value attribute of the field.
 * @var   array    $checkedOptions  Options that will be set as checked.
 * @var   boolean  $hasValue        Has this field a value assigned?
 * @var   array    $options         Options available for this field.
 * @var   string   $link            The link for the content history page
 * @var   string   $label           The label text
 * @var   string   $dataAttribute   Miscellaneous data attributes preprocessed for HTML output
 * @var   array    $dataAttributes  Miscellaneous data attributes for eg, data-*.
 */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_formbuilder');
$wa->useScript('webcomponent.drop-list')
    ->useScript('webcomponent.drop-list-item')
    ->usePreset('com_formbuilder.formbuilder');

Text::script('COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_REQUIRED_FALSE');
Text::script('COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_UP');
Text::script('COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_DOWN');
Text::script('COM_FORMBUILDER_FIELD_FORMBUILDER_HIDDEN_LABEL');
Text::script('COM_FORMBUILDER_FIELD_FORMBUILDER_ERROR_REMOVE_FIELDSET');
Text::script('COM_FORMBUILDER_FIELD_FORMBUILDER_ERROR_REMOVE_FIELDSET_WITH_ITEMS');
Text::script('COM_FORMBUILDER_FIELD_FORMBUILDER_ERROR_LANGUAGE_STRING_AJAX');
Text::script('COM_FORMBUILDER_FIELD_FORMBUILDER_ERROR_EDIT_FIELDSET');
Text::script('COM_FORMBUILDER_FIELD_FORMBUILDER_EDIT_LANGUAGE_STRING_PROMPT');
Text::script('JGLOBAL_FIELD_ADD');
Text::script('JGLOBAL_FIELD_REMOVE');
Text::script('JSHOW');
Text::script('JHIDE');
Text::script('JOPTION_REQUIRED');

?>
<?php if ($emptyState) : ?>
    <?php echo $this->sublayout('emptystate', $displayData); ?>
<?php else : ?>
    <joomla-form-builder>
        <div class="joomla-form-builder-actions d-grid mb-2 gap-2 d-md-flex justify-content-md-end">
            <button class="btn btn-success" type="button" data-task="add-tab">
                <span class="icon-add me-2" aria-hidden="true"></span>
                <?php echo Text::_('COM_FORMBUILDER_FIELD_FORMBUILDER_ADD_FIELDSET'); ?>
            </button>
        <button class="btn btn-danger" type="button" data-task="remove-fieldset">
                <span class="icon-delete fa-minus me-2" aria-hidden="true"></span>
                <?php echo Text::_('COM_FORMBUILDER_FIELD_FORMBUILDER_REMOVE_FIELDSET'); ?>
            </button>
        </div>
        <joomla-drop-list class="joomla-formbuilder-form-items" slotName="field-form">
            <span slot="field-form">
                <?php echo $this->sublayout('form-fields', ['form' => $form, 'formFields' => $formFields]); ?>
            </span>
        </joomla-drop-list>
        <joomla-drop-list class="joomla-formbuilder-available-items" slotName="field-available">
            <span slot="field-available">
                <?php echo $this->sublayout('available-fields', ['form' => $form, 'formFields' => $formFields]); ?>
            </span>
        </joomla-drop-list>
        <input
            type="hidden"
            name="<?php echo $name; ?>"
            id="<?php echo $id; ?>"
            value="<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>"
            data-update="formbuilder"
        <?php echo $class, $disabled, $onchange, $dataAttribute; ?>>
        <template id="new-fieldset-template">
            <joomla-drop-list-item drag-handle=".joomla-drop-list-item-drag-btn">
                <div class="joomla-formbuilder-item">
                    <button type="button" aria-label="COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_DRAG_FIELDSET"
                        class="btn btn-primary m-1 mb-3 btn-sm joomla-drop-list-item-drag-btn">
                        <span class="icon-move icon-lg" aria-hidden="true"></span>
                    </button>
                    <div class="joomla-form-builder_item-menu btn-group dropstart position-absolute top-0 end-0 m-1 ">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" role="menu" aria-expanded="false">
                            <span class="fa fa-solid fa-ellipsis fa-xl"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-start">
                            <li>
                                <button tabindex="-1" role="button" class="dropdown-item" data-task="move">
                                    <span class="icon-minus icon-fw me-1" aria-hidden="true"></span><?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>
                                </button>
                            </li>
                            <li>
                                <button tabindex="-1" role="button" class="dropdown-item" data-task="up">
                                    <span class="icon-arrow-up icon-fw me-1" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_UP'); ?>
                                </button>
                            </li>
                            <li>
                                <button tabindex="-1" role="button" class="dropdown-item" data-task="down">
                                    <span class="icon-arrow-down icon-fw me-1" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_FORMBUILDER_FIELD_FORMBUILDER_BUTTON_DOWN'); ?>
                                </button>
                            </li>
                            <li>
                                <button tabindex="-1" role="button" class="dropdown-item" data-task="required">
                                    <span class="icon-lock icon-fw me-1" aria-hidden="true"></span>
                                    <?php echo Text::_('JOPTION_REQUIRED'); ?>
                                </button>
                            </li>
                            <li>
                                <button tabindex="-1" role="button" class="dropdown-item" data-task="hide">
                                    <span class="icon-eye-slash icon-fw me-1" aria-hidden="true"></span>
                                    <?php echo Text::_('JHIDE'); ?>
                                </button>
                            </li>
                            <!-- @TODO language string -->
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </div>
                    <div class="joomla-formbuilder_item-badges position-absolute bottom-0 start 0">
                    </div>
                    <fieldset class="options-form">
                        <legend></legend>
                        <joomla-drop-list class="joomla-formbuilder-form-items" slot="field-form" dropzone="move">
                        </joomla-drop-list>
                    </fieldset>
                </div>
            </joomla-drop-list-item>
        </template>
    </joomla-form-builder>
<?php endif; ?>
