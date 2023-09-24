<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Event\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Event class for \Joomla\CMS\Table\Table onBeforeCheckin event
 *
 * @since  4.0.0
 */
class BeforeCheckinEvent extends AbstractEvent
{
    /**
     * Constructor.
     *
     * Mandatory arguments:
     * subject      \Joomla\CMS\Table\TableInterface The table we are operating on
     * pk           mixed                            An optional primary key value to check out.
     *
     * @param   string  $name       The event name.
     * @param   array   $arguments  The event arguments.
     *
     * @throws  \BadMethodCallException
     */
    public function __construct($name, array $arguments = [])
    {
        if (!\array_key_exists('pk', $arguments)) {
            throw new \BadMethodCallException("Argument 'pk' is required for event $name");
        }

        parent::__construct($name, $arguments);
    }
}
