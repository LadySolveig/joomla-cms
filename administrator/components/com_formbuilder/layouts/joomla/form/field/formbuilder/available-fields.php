<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form    $form        The form instance for render the available fields
 * @var   array   $formFields  The form fields that are already allocated to the form.
 * @var   string  $basegroup   The base group name
 * @var   string  $group       Current group name
 * @var   array   $buttons     Array of the buttons that will be rendered
 */

?>

<?php $fieldsets = $form->getFieldsets(); ?>
<?php array_unshift($fieldsets, (object) ['name' => '']); ?>
<?php // foreach ($form->getFieldsets() as $fieldset) : ?>
<?php echo HTMLHelper::_('uitab.startTabSet', 'available-fields-tab', ['active' => 'available-fields-global', 'recall' => true, 'breakpoint' => 768]); ?>
<?php foreach ($fieldsets as $fieldset) : ?>
    <?php $fields = $form->getFieldset($fieldset->name); ?>
    <?php if (count($fields)) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'available-fields-tab', (empty($fieldset->name) ? 'available-fields-global' : $fieldset->name), empty($fieldset->label) ? Text::_('COM_FORMBUILDER_FIELDSET_JGLOBAL_TITLE') : trim(Text::_($fieldset->label))); // @todo global lang string Text::_('COM_CONTACT_NEW_CONTACT') : Text::_('COM_CONTACT_EDIT_CONTACT')); ?>
        <fieldset class="m-0">
            <!-- <?php if (isset($fieldset->label) && ($legend = trim(Text::_($fieldset->label))) !== '') : ?> -->
                <legend class="visually-hidden"><?php echo $legend; ?></legend>
            <?php endif; ?>
            <?php foreach ($fields as $field) : ?>
                <!-- @todo change type hidden where it is not needed from system -->
                <?php if ($field->type === 'hidden') : continue; endif; ?>
                <?php $fieldDataAttributes = $field->getDataAttributes(); ?>
                <?php if (isset($fieldDataAttributes['data-fieldset']) && (string) $fieldDataAttributes['data-fieldset'] !== $fieldset->name) : continue; endif; ?>
                <?php if (isset($fieldDataAttributes['data-formpresent']) && (string) $fieldDataAttributes['data-formpresent'] === 'true') : continue; endif; ?>
                <?php $builderFieldAttributesArray = \json_decode($fieldDataAttributes['data-formbuilder'], true); ?>
                <joomla-drop-list-item>
                    <div class="joomla-formbuilder-item"
                        data-formbuilder="<?php echo htmlspecialchars((string) $fieldDataAttributes['data-formbuilder'], ENT_COMPAT, 'UTF-8'); ?>">
                        <div class="control-group flex-column">
                            <?php echo $form->getLabel($field->fieldname, $field->group); ?>
                            <span class="text-muted">
                                <?php echo $field->type ?><?php echo isset($field->description) ? '- ' . $field->description : ''; ?>
                            </span>
                        </div>
                        <div class="joomla-form-builder_item-menu btn-group dropstart position-absolute top-0 end-0 m-1 ">
                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" role="menu" aria-expanded="false">
                                <span class="fa fa-solid fa-ellipsis fa-xl"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-start">
                                <li>
                                    <button tabindex="-1" role="button" class="dropdown-item" data-task="move">
                                        <span class="icon-plus icon-fw me-1" aria-hidden="true"></span><?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>
                                    </button>
                                </li>
                                <!-- @TODO language string -->
                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                            </ul>
                        </div>
                        <div class="joomla-formbuilder_item-badges position-absolute bottom-0 end-0 me-1">
                            <?php if ($builderFieldAttributesArray['required']) : ?>
                                <span class="badge badge-required text-bg-danger fs-5 fw-medium mt-1 me-0 m-2"><?php echo Text::_('JOPTION_REQUIRED'); ?></span>
                            <?php endif; ?>
                            <?php if ($builderFieldAttributesArray['hidden']) : ?>
                                <span class="badge badge-hidden text-bg-info fs-5 fw-medium mt-1 me-0 m-2"><?php echo Text::_('COM_FORMBUILDER_HIDDEN_LABEL'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </joomla-drop-list-item>
            <?php endforeach; ?>
        </fieldset>
    <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endforeach; ?>
<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
