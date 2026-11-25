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
 * Base class for the command palette events.
 *
 * Items need a "title" and a relative or http(s) "link"; "group", "target" and "quicktask" are
 * optional. Invalid items are dropped. Listeners only get the core.login.admin guarantee.
 *
 * @since  __DEPLOY_VERSION__
 */
abstract class AbstractCommandsEvent extends AbstractImmutableEvent
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
        $arguments['items']   ??= [];

        parent::__construct($name, $arguments);
    }

    /**
     * Get the context the palette is opened in, e.g. "com_content.articles".
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
     * Get the current list of items.
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
     * Replace the list of items.
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
        $this->arguments['items'] = $this->onSetItems($items);
    }

    /**
     * Setter for the items argument: drops invalid items and invalid quicktasks.
     *
     * @param   array  $value  The value to set
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function onSetItems(array $value): array
    {
        $isText = static fn ($text): bool => \is_string($text) && trim($text) !== '';

        // Relative or http(s) only, no javascript: and the like. parse_url() is false for broken URLs.
        $isLink = static function ($link) use ($isText): bool {
            if (!$isText($link)) {
                return false;
            }

            $scheme = parse_url(trim($link), PHP_URL_SCHEME);

            return $scheme === null || \is_string($scheme) && \in_array(strtolower($scheme), ['http', 'https'], true);
        };

        $items = [];

        foreach ($value as $item) {
            if (!\is_array($item) || !$isText($item['title'] ?? null) || !$isLink($item['link'] ?? null)) {
                continue;
            }

            if (isset($item['group']) && !\is_string($item['group'])) {
                unset($item['group']);
            }

            if (
                isset($item['quicktask'])
                && (!\is_array($item['quicktask']) || !$isText($item['quicktask']['title'] ?? null) || !$isLink($item['quicktask']['link'] ?? null))
            ) {
                unset($item['quicktask']);
            }

            $items[] = $item;
        }

        return $items;
    }
}
