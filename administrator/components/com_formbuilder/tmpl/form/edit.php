<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_formbuilder
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Formbuilder\Administrator\View\Formbuilder\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate')
    ->useScript('webcomponent.ajax-form')
    ->useScript('com_formbuilder.admin-formbuilder');

?>
<joomla-ajax-form prevent-default="" target="#content" excludeTask="form.save2new,form.save2copy,form.save,form.cancel">
    <form action="<?php echo Route::_('index.php?option=com_formbuilder&amp;view=formbuilder&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm">
        <div class="main-card">
            <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
                <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', empty($this->item->id) ? Text::_('COM_FORMBUILDER_NEW_FORM') : Text::_('COM_FORMBUILDER_EDIT_FORM')); ?>
                <div class="row">
                    <div class="col-lg-9 form-grid">
                        <?php echo $this->form->renderField('client'); ?>
                        <?php echo $this->form->renderField('context'); ?>
                        <?php echo $this->form->renderField('catid'); ?>
                        <?php echo $this->form->renderField('form_settings'); ?>
                    </div>
                    <div class="col-lg-3">
                        <?php $this->fields = [
                                [
                                    'published',
                                    'state',
                                    'enabled',
                                ],
                                'access',
                                'language',
                                'note',
                            ]; ?>
                        <?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
                        <?php $this->fields = null; ?>
                    </div>
                </div>
                <?php echo HTMLHelper::_('uitab.endTab'); ?>
                <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
                    <fieldset id="fieldset-publishingdata" class="options-form">
                        <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
                            <div>
                                <?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
                            </div>
                    </fieldset>
                <?php echo HTMLHelper::_('uitab.endTab'); ?>
                <?php if ($this->canDo->get('core.admin')) : ?>
                    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'rules', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
                        <fieldset id="fieldset-rules" class="options-form">
                            <legend><?php echo Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL'); ?></legend>
                                <div>
                                    <?php echo $this->form->getInput('rules'); ?>
                                </div>
                        </fieldset>
                    <?php echo HTMLHelper::_('uitab.endTab'); ?>
                <?php endif; ?>
            <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
            <input type="hidden" name="task" value="">
            <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </form>
</joomla-ajax-form>
