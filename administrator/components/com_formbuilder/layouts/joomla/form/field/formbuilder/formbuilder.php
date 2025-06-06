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
// @todo needed?
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
<joomla-form-builder>
<div class="joomla-form-builder-actions d-grid mb-2 gap-2 d-md-flex justify-content-md-end">
    <button class="btn btn-success" type="button" data-task="add-fieldset">
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
        <?php echo LayoutHelper::render('joomla.form.field.formbuilder.form-fields', ['form' => $form, 'formFields' => $formFields], JPATH_ROOT . '/administrator/components/com_formbuilder/layouts'); ?>
    </span>
</joomla-drop-list>
<joomla-drop-list class="joomla-formbuilder-available-items" slotName="field-available">
    <span slot="field-available">
        <?php echo LayoutHelper::render('joomla.form.field.formbuilder.available-fields', ['form' => $form, 'formFields' => $formFields], JPATH_ROOT . '/administrator/components/com_formbuilder/layouts'); ?>
    </span>
</joomla-drop-list>
<input
    type="hidden"
    name="<?php echo $name; ?>"
    id="<?php echo $id; ?>"
    value="<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>"
    data-update="form_settings"
<?php echo $class, $disabled, $onchange, $dataAttribute; ?>>
</joomla-form-builder>
