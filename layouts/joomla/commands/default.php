<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Layout for the Joomla command dialog.
 *
 * @var    array  $displayData  The display data.
 */
$editMode = !empty($displayData['editMode']);

?>
<template id="command-dialog-search">
    <div class="command-dialog-search d-flex align-items-center gap-2">
        <?php // Label, description and footer are aria-hidden (still used via aria-labelledby/-describedby): Orca reads all
              // text of a dialog when it opens, then the focused field, so they would be heard twice. ?>
        <label for="command_search" class="visually-hidden" id="command_search-label" aria-hidden="true"><?php echo Text::_('PLG_SYSTEM_COMMANDS_SEARCH_LABEL'); ?></label>
        <div class="command-dialog-field position-relative flex-grow-1">
            <input type="search" name="command[search]" id="command_search" value="" class="form-control" autocomplete="off" role="combobox" aria-labelledby="command_search-label" aria-controls="command-dialog-search-result" aria-expanded="true" aria-autocomplete="list" aria-describedby="command_search-desc<?php echo $editMode ? ' command-dialog-edit-mode' : ''; ?>" placeholder="<?php echo Text::_('PLG_SYSTEM_COMMANDS_SEARCH_PLACEHOLDER'); ?>" inputmode="search">
            <span class="command-dialog-hint text-muted" id="command-dialog-hint" aria-hidden="true" hidden></span>
        </div>
        <button type="button" class="btn-close" id="command-dialog-close" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
    </div>
    <p id="command_search-desc" class="visually-hidden" aria-hidden="true"><?php echo Text::_('PLG_SYSTEM_COMMANDS_SEARCH_DESC'); ?></p>
    <?php if ($editMode) : ?>
        <p class="small text-muted mt-2 mb-0" id="command-dialog-edit-mode" aria-hidden="true">
            <span class="icon-external-link-alt me-1" aria-hidden="true"></span><?php echo Text::_('PLG_SYSTEM_COMMANDS_EDIT_MODE'); ?>
        </p>
    <?php endif; ?>
    <hr>
    <div class="list-group command-dialog-results" id="command-dialog-search-result" role="listbox" aria-label="<?php echo Text::_('PLG_SYSTEM_COMMANDS_RESULTS_LABEL'); ?>">
    </div>
    <p class="text-muted mb-0" id="command-dialog-no-results" hidden></p>
    <div id="command-dialog-error"></div>
    <div class="visually-hidden" id="command-dialog-status" aria-live="polite" aria-atomic="true"></div>
    <ul class="command-dialog-footer" aria-hidden="true">
        <li>
            <kbd>↑</kbd> <kbd>↓</kbd>
            <?php echo Text::_('PLG_SYSTEM_COMMANDS_FOOTER_NAVIGATE'); ?>
        </li>
        <li>
            <kbd>↵</kbd>
            <?php echo Text::_($editMode ? 'PLG_SYSTEM_COMMANDS_FOOTER_NEW_TAB' : 'PLG_SYSTEM_COMMANDS_FOOTER_OPEN'); ?>
        </li>
        <?php if (!$editMode) : ?>
        <li>
            <kbd data-platform="mac" hidden>⌘</kbd>
            <kbd data-platform="other">Ctrl</kbd>
            <kbd>↵</kbd>
            <?php echo Text::_('PLG_SYSTEM_COMMANDS_FOOTER_NEW_TAB'); ?>
        </li>
        <?php endif; ?>
        <li>
            <kbd>Esc</kbd>
            <?php echo Text::_('PLG_SYSTEM_COMMANDS_FOOTER_CLOSE'); ?>
        </li>
    </ul>
</template>
