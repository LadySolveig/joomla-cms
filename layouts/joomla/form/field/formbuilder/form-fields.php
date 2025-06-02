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

<?php echo HTMLHelper::_('uitab.startTabSet', 'form-fields-tab', ['active' => 'form-fields-global', 'recall' => true, 'breakpoint' => 768]); ?>
<?php echo empty($formFields) ? HTMLHelper::_('uitab.addTab', 'form-fields-tab', 'jglobal', Text::_('COM_FORMBUILDER_FIELDSET_JGLOBAL_TITLE')) : ''; ; ?>
<?php foreach($formFields as $fieldsetKey => $formFieldsFieldsets) : ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'form-fields-tab', $fieldsetKey, empty($fieldsetKey) ? Text::_('COM_FORMBUILDER_FIELDSET_JGLOBAL_TITLE') : Text::_('COM_FORMBUILDER_FIELDSET_' . strtoupper(str_replace('-', '_', $fieldsetKey)) . '_TITLE')); ?>
    <?php foreach($formFieldsFieldsets as $formField) : ?>
        <?php $field = $form->getField($formField['name'], $formField['group']); ?>
        <?php if (!$field) : continue; endif; ?>
        <?php $builderFieldAttributes = $field->getDataAttributes()['data-formbuilder']; ?>
        <?php $builderFieldAttributesArray = \json_decode($builderFieldAttributes, true); ?>
        <?php $newAttributesArray = array_merge($builderFieldAttributesArray, $formField); // @todo $builderFieldAttributesArray = ?>
        <joomla-drop-list-item>
            <div class="joomla-formbuilder-item"
                data-formbuilder="<?php echo htmlspecialchars(\json_encode($newAttributesArray, \JSON_FORCE_OBJECT), ENT_COMPAT, 'UTF-8'); ?>">
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
                                <span class="icon-minus icon-fw me-1" aria-hidden="true"></span><?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>
                            </button>
                        </li>
                        <li>
                            <button tabindex="-1" role="button" class="dropdown-item" data-task="up">
                                <span class="icon-arrow-up icon-fw me-1" aria-hidden="true"></span>
                                <?php echo Text::_('COM_FORMBUILDER_BUTTON_UP'); ?>
                            </button>
                        </li>
                        <li>
                            <button tabindex="-1" role="button" class="dropdown-item" data-task="down">
                                <span class="icon-arrow-down icon-fw me-1" aria-hidden="true"></span>
                                <?php echo Text::_('COM_FORMBUILDER_BUTTON_DOWN'); ?>
                            </button>
                        </li>
                        <li>
                            <button tabindex="-1" role="button" class="dropdown-item" data-task="required">
                                <span class="icon-<?php echo $newAttributesArray['required'] ? 'unlock' : 'lock' ?> icon-fw me-1" aria-hidden="true"></span>
                                <?php echo $newAttributesArray['required']
                                    ? Text::_('COM_FORMBUILDER_BUTTON_REQUIRED_FALSE')
                                    : Text::_('JOPTION_REQUIRED'); ?>
                            </button>
                        </li>
                        <li>
                            <button tabindex="-1" role="button" class="dropdown-item" data-task="hide">
                                <span class="icon-eye<?php echo !$newAttributesArray['hidden'] ? '' : '-slash' ?> icon-fw me-1" aria-hidden="true"></span>
                                <?php echo $newAttributesArray['hidden']
                                    ? Text::_('JSHOW')
                                    : Text::_('JHIDE'); ?>
                            </button>
                        </li>
                        <!-- @TODO language string -->
                        <li><a class="dropdown-item" href="#">Another action</a></li>
                        <li><a class="dropdown-item" href="#">Something else here</a></li>
                    </ul>
                </div>
                <div class="joomla-formbuilder_item-badges position-absolute bottom-0 start 0">
                    <?php if ($newAttributesArray['required']) : ?>
                        <span class="badge badge-required text-bg-danger fs-5 fw-medium mt-1 me-0 m-2"><?php echo Text::_('JOPTION_REQUIRED'); ?></span>
                    <?php endif; ?>
                    <?php if ($newAttributesArray['hidden']) : ?>
                        <span class="badge badge-hidden text-bg-info fs-5 fw-medium mt-1 me-0 m-2"><?php echo Text::_('COM_FORMBUILDER_HIDDEN_LABEL'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </joomla-drop-list-item>
    <?php endforeach; ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endforeach; ?>
<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
