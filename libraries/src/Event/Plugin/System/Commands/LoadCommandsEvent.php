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
 * Concrete event class for the onLoadCommands event.
 *
 * @since  __DEPLOY_VERSION__
 */
class LoadCommandsEvent extends AbstractImmutableEvent
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
        $arguments['context']  ??= '';
        $arguments['commands'] ??= [];

        parent::__construct($name, $arguments);
    }

    /**
     * Get the context the commands are being loaded for.
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
     * Get the current list of commands.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getCommands(): array
    {
        return $this->arguments['commands'];
    }

    /**
     * Set the list of commands.
     *
     * @param   array  $commands  The commands.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function setCommands(array $commands): void
    {
        $this->arguments['commands'] = $commands;
    }
}
