<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

?>
<template id="command-dialog-search">
    <div class="command-dialog-search d-flex align-items-center gap-2">
        <input type="text" name="command[search]" id="command_search" value="" class="form-control" aria-describedby="command_search-desc" placeholder="Search" inputmode="search">
        <button type="button" class="btn-close" id="command-dialog-close" aria-label="Close"></button>
    </div>
    <hr>
    <ul class="list-group" id="command-dialog-search-result">
    </ul>
</template>
