<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Form\Builder;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The formbuilder service.
 *
 * @since  __DEPLOY_VERSION__
 */
interface FormbuilderServiceInterface
{
    /**
     * Returns a valid section for the given section. If it is not valid then null
     * is returned.
     *
     * @param   string  $section  The section to get the mapping for
     * @param   string  $client   The client context (e.g. 'site', 'administrator')
     * @param   object  $item     The item
     *
     * @return  string|null  The new section
     *
     * @since   __DEPLOY_VERSION__
     */
    public function validateFormbuilderCustomFieldSection(string $section, string $client, object|null $item = null): string|null;

    /**
     * Returns valid contexts.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getFormbuilderContexts(): array;
}
