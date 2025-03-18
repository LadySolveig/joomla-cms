<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form    $form       The form instance for render the available fields
 * @var   string  $basegroup  The base group name
 * @var   string  $group      Current group name
 * @var   array   $buttons    Array of the buttons that will be rendered
 */

?>

<?php foreach ($form->getGroup('') as $field) : ?>
<?php $builderFieldAttributes = $field->getDataAttributes()['data-formbuilder']; ?>
<?php $builderFieldAttributesArray = json_decode($builderFieldAttributes, true); ?>
<joomla-drop-list-item>
<div class="joomla-formbuilder-item"
    data-formbuilder="<?php echo htmlspecialchars($builderFieldAttributes, ENT_COMPAT, 'UTF-8');?>">
    <div class="control-group">
        <?php echo $field->renderField(); ?>
    </div>
    <div class="joomla-form-builder_item-menu btn-group dropstart position-absolute top-0 end-0 m-1 ">
        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" role="menu" aria-expanded="false">
            <span class="fa fa-solid fa-ellipsis fa-xl"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-start">
            <li><button role="button" class="dropdown-item" data-task="move"><span class="icon-plus icon-fw me-1" aria-hidden="true"></span>Add</button></li><!-- @TODO language string -->
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
        </ul>
    </div>
    <div class="joomla-formbuilder_item-badges position-absolute bottom-0 start 0">
    <?php if ($builderFieldAttributesArray['required']) : ?>
        <span class="badge badge-required text-bg-danger mt-1 me-0 m-2 "><?php echo Text::_('TODO: required'); ?></span> <!-- @todo Language String -->
    <?php endif; ?>
    </div>
</div>
</joomla-drop-list-item>
<?php endforeach; ?>
