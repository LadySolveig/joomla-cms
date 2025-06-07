<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

 $helpURL    = 'https://docs.joomla.org/Special:MyLanguage/Help6.x:Formbuilder';
 $title      = Text::_('COM_FORMBUILDER_EMPTYSTATE_TITLE');
 $icon       = 'icon-puzzle';
 $content    = Text::_('COM_FORMBUILDER_EMPTYSTATE_CONTENT');
?>

<div class="px-4 py-5 my-5 text-center">
    <span class="fa-8x mb-4 <?php echo $icon; ?>" aria-hidden="true"></span>
    <h1 class="display-5 fw-bold"><?php echo $title; ?></h1>
    <div class="col-lg-6 mx-auto">
        <p class="lead mb-4">
            <?php echo $content; ?>
        </p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <?php if ($helpURL) : ?>
                <a href="<?php echo $helpURL; ?>" target="_blank"
                    class="btn btn-outline-secondary btn-lg px-4"><?php echo Text::_('JGLOBAL_LEARN_MORE'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
