<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form     $form            The form instance for render the available fields
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
$wa->useScript('webcomponent.field-form-builder');
$wa->useScript('webcomponent.drop-list');
$wa->useScript('webcomponent.drop-list-item');
$wa->useStyle('webcomponent.field-form-builder');

// @todo needed?
// Populate the media config
// $config = [
    // 'canCreate'           => $user->authorise('core.create', 'com_media'),
    // 'canEdit'             => $user->authorise('core.edit', 'com_media'),
    // 'canDelete'           => $user->authorise('core.delete', 'com_media'),
// ];
// $this->getDocument()->addScriptOptions('com_media', $config);

Text::script('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_REQUIRED_FALSE');
Text::script('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_UP');
Text::script('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_BUTTON_DOWN');
Text::script('COM_CONTACT_FIELD_EMAIL_FORM_BUILDER_HIDDEN_LABEL');
Text::script('JGLOBAL_FIELD_ADD');
Text::script('JGLOBAL_FIELD_REMOVE');
Text::script('JSHOW');
Text::script('JHIDE');
Text::script('JOPTION_REQUIRED');

?>
<joomla-form-builder>
<joomla-drop-list class="joomla-formbuilder-form-items" slotName="field-form">
</joomla-drop-list>
<joomla-drop-list class="joomla-formbuilder-available-items" slotName="field-available">
    <span slot="field-available">
        <?php echo LayoutHelper::render('joomla.form.field.formbuilder.available-fields', ['form' => $form]); ?>
    </span>
</joomla-drop-list>
</joomla-form-builder>
