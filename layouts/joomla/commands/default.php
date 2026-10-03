<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<template id="command-dialog-search">
    <div class="command-dialog-search d-flex align-items-center gap-2">
        <label for="command_search" class="visually-hidden"><?php echo Text::_('PLG_SYSTEM_COMMANDS_SEARCH_LABEL'); ?></label>
        <input type="text" name="command[search]" id="command_search" value="" class="form-control" aria-describedby="command_search-desc" placeholder="<?php echo Text::_('PLG_SYSTEM_COMMANDS_SEARCH_PLACEHOLDER'); ?>" inputmode="search">
        <button type="button" class="btn-close" id="command-dialog-close" aria-label="Close"></button>
    </div>
    <p id="command_search-desc" class="visually-hidden"><?php echo Text::_('PLG_SYSTEM_COMMANDS_SEARCH_DESC'); ?></p>
    <hr>
    <ul class="list-group" id="command-dialog-search-result">
    </ul>
    <p class="text-muted mb-0" id="command-dialog-no-results" hidden></p>
    <div class="visually-hidden" id="command-dialog-status" role="status"></div>
</template>
