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
    <div class="command-dialog-search">
        <input type="text" name="command[search]" id="command_search" value="" class="form-control" aria-describedby="command_search-desc" placeholder="Search" inputmode="search">
    </div>
    <hr>
    <ul class="list-group" id="command-dialog-search-result">
    </ul>
</template>
