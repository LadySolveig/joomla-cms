<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Event\Plugin\System\Commands;

use Joomla\CMS\Event\AbstractImmutableEvent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Concrete event class for the onCommandsSearch event.
 *
 * For term-dependent results only; static items belong in onCommandsLoad.
 *
 * Listeners run inside the CSRF/core.login.admin gate already enforced by onAjaxCommands() - they
 * must not assume any stronger privilege is guaranteed.
 *
 * @since  __DEPLOY_VERSION__
 */
class CommandsSearchEvent extends AbstractImmutableEvent
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
        $arguments['context'] ??= '';
        $arguments['search']  ??= '';
        $arguments['items']   ??= [];

        parent::__construct($name, $arguments);
    }

    /**
     * Get the context the search is being performed for.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getContext(): string
    {
        return $this->arguments['context'];
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

    /**
     * Get the current list of searchable items.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getItems(): array
    {
        return $this->arguments['items'];
    }

    /**
     * Replace the list of searchable items.
     *
     * Not named setItems(): that collides with AbstractEvent's automatic setArgument() hook
     * and would null the value on construction (see core's AjaxEvent::updateEventResult()).
     *
     * @param   array  $items  The items.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function updateItems(array $items): void
    {
        $this->arguments['items'] = $items;
    }
}
