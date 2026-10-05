<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Event\Plugin\System\Commands;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Concrete event class for the onCommandsSearch event.
 *
 * For term-dependent results only; static items belong in onCommandsLoad.
 *
 * @since  __DEPLOY_VERSION__
 */
class CommandsSearchEvent extends AbstractCommandsEvent
{
    /**
     * Constructor.
     *
     * @param   string  $name       The event name.
     * @param   array   $arguments  The event arguments.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct($name, array $arguments = [])
    {
        $arguments['search'] ??= '';

        parent::__construct($name, $arguments);
    }

    /**
     * Get the raw search term entered by the user.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getSearch(): string
    {
        return $this->arguments['search'];
    }
}
