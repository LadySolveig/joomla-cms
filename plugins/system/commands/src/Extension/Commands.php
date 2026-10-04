<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.commands
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Commands\Extension;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Event\Plugin\System\Commands as CommandsEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Menu\AdministratorMenuItem;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Menus\Administrator\Helper\MenusHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\Quickicon\Administrator\Helper\QuickIconHelper;
use Joomla\Module\Submenu\Administrator\Menu\Menu;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Commands plugin to add a command palette to the administrator templates.
 *
 * @since  __DEPLOY_VERSION__
 */
final class Commands extends CMSPlugin implements SubscriberInterface
{
    /**
     * Flag to check if command dialog template is append to body.
     *
     * @var    boolean
     * @since  __DEPLOY_VERSION__
     */
    protected $isTemplateAppend = false;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  - The method name to call (priority defaults to 0)
     *  - An array composed of the method name to call and the priority
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeCompileHead' => 'initialize',
            'onAfterRender'       => 'onAfterRender',
            'onAjaxCommands'      => 'onAjaxCommands',
            'onLoadShortcuts'     => 'addShortcut',
        ];
    }

    /**
     * Add the javascript for the command palette
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function initialize()
    {
        if (!$this->getApplication()->isClient('administrator')) {
            return;
        }

        // Load translations
        $this->loadLanguage();

        Text::script('JCLOSE');
        Text::script('PLG_SYSTEM_COMMANDS_DIALOG_LABEL');
        Text::script('JERROR_AN_ERROR_HAS_OCCURRED');
        Text::script('JGLOBAL_OPENS_IN_A_NEW_WINDOW');

        $document = $this->getApplication()->getDocument();
        $wa       = $document->getWebAssetManager();

        $wa->getRegistry()->addExtensionRegistryFile('plg_system_commands');
        $wa->usePreset('plg_system_commands.commands');

        $openHotKey = $this->params->get('openhotkey', 'cmd+k,ctrl+k');

        $document->addScriptOptions('plg_system_commands.openhotkey', $openHotKey);

        $input = $this->getApplication()->getInput();
        $document->addScriptOptions('plg_system_commands.context', $input->getCmd('option') . '.' . $input->getCmd('view'));

        $document->addScriptOptions('plg_system_commands.editMode', $input->getBool('hidemainmenu'));
    }

    /**
     * List "J, K" in plg_system_shortcut's overview; the key is bound in commands.es6.js.
     *
     * @param   Event  $event  The onLoadShortcuts event.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function addShortcut(Event $event): void
    {
        if (!$this->getApplication()->isClient('administrator') || !$this->isAllowedUser()) {
            return;
        }

        $this->loadLanguage();

        $shortcuts = $event->getArgument('shortcuts', []);

        $shortcuts['commandsKey'] = (object) [
            'shortcut' => 'K',
            'title'    => Text::_('PLG_SYSTEM_COMMANDS_SHORTCUT_TITLE'),
        ];

        $event->setArgument('shortcuts', $shortcuts);
    }

    /**
     * Check if current user is allowed to send the data.
     *
     * @return  bool
     *
     * @since __DEPLOY_VERSION__
     *
     * @throws \Exception
     */
    private function isAllowedUser(): bool
    {
        return $this->getApplication()->getIdentity()->authorise('core.login.admin');
    }

    /**
     * Ajax command to load command items
     *
     * @param   AjaxEvent  $event  The event object
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     *
     * @throws  NotAllowed  If the token is invalid or the user is not allowed
     */
    public function onAjaxCommands(AjaxEvent $event)
    {
        if (!Session::checkToken('request')) {
            throw new NotAllowed(Text::_('JINVALID_TOKEN'), 403);
        }

        /**
         * @var \Joomla\CMS\Application\AdministratorApplication $app
         */
        $app = $this->getApplication();

        if (!$app->isClient('administrator') || !$this->isAllowedUser()) {
            throw new NotAllowed($this->getApplication()->getLanguage()->_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
        }

        $this->loadLanguage();

        $input   = $app->getInput();
        $context = $input->getCmd('context', '');

        if ($input->getCmd('mode') === 'search') {
            // Dynamic results only; the static items are filtered in the browser.
            $searchEvent = new CommandsEvent\CommandsSearchEvent(
                'onCommandsSearch',
                [
                    'context' => $context,
                    'search'  => $input->getString('search', ''),
                    'items'   => [],
                ]
            );

            $this->getDispatcher()->dispatch('onCommandsSearch', $searchEvent);

            $items = $this->completeGroups($searchEvent->getItems());

            // The browser sends how many static results it shows, for the combined count.
            $response = [
                'success' => true,
                'items'   => $items,
                'status'  => Text::plural('PLG_SYSTEM_COMMANDS_N_RESULTS', $input->getUint('static', 0) + $this->countLinks($items)),
            ];

            $event->updateEventResult($response);

            return;
        }

        // Needed for mod_menu's own strings (e.g. MOD_MENU_COM_ACTIONLOGS).
        $app->getLanguage()->load('mod_menu', JPATH_ADMINISTRATOR);

        $items = [];

        foreach ($this->getMenuRoots() as $root) {
            Menu::preprocess($root);

            if ($root->hasChildren()) {
                $items = array_merge($items, $this->normalizeMenuItems($root));
            }
        }

        $items = array_merge($items, $this->getQuickIconItems());

        // Quick icons often link to the exact same screen as an existing menu item
        // (e.g. Tags/Categories) - keep the first (menu) occurrence of each destination.
        $seenLinks = [];
        $items     = array_values(array_filter($items, function ($item) use (&$seenLinks) {
            if (isset($seenLinks[$item['link']])) {
                return false;
            }

            $seenLinks[$item['link']] = true;

            return true;
        }));

        $loadEvent = new CommandsEvent\CommandsLoadEvent(
            'onCommandsLoad',
            [
                'context' => $context,
                'items'   => $items,
            ]
        );

        $this->getDispatcher()->dispatch('onCommandsLoad', $loadEvent);

        $items = $this->completeGroups($loadEvent->getItems());

        // The browser counts the results itself, but plural rules only exist in PHP: send the
        // status text for every possible count (index = number of shown links).
        $status = [];

        for ($count = 0, $total = $this->countLinks($items); $count <= $total; $count++) {
            $status[] = Text::plural('PLG_SYSTEM_COMMANDS_N_RESULTS', $count);
        }

        $response = [
            'success' => true,
            'items'   => $items,
            'status'  => $status,
            'dynamic' => !empty($this->getDispatcher()->getListeners('onCommandsSearch')),
        ];

        // https://github.com/joomla/joomla-cms/pull/43530 for J5.3
        $event->updateEventResult($response);
    }

    /**
     * All menus to list: default preset, published admin menu/dashboard modules, configured presets.
     *
     * @return  AdministratorMenuItem[]
     *
     * @since   __DEPLOY_VERSION__
     */
    private function getMenuRoots(): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([$db->quoteName('module'), $db->quoteName('params')])
            ->from($db->quoteName('#__modules'))
            ->where(
                [
                    $db->quoteName('client_id') . ' = 1',
                    $db->quoteName('published') . ' = 1',
                ]
            )
            ->whereIn($db->quoteName('module'), ['mod_menu', 'mod_submenu'], ParameterType::STRING)
            ->order($db->quoteName('ordering'));

        $presets   = ['default'];
        $menutypes = [];

        foreach ($db->setQuery($query)->loadObjectList() as $module) {
            $params   = new Registry($module->params);
            $menutype = $params->get('menutype', '*');

            if ($menutype === '*') {
                $presets[] = $params->get('preset', $module->module === 'mod_menu' ? 'default' : '');
            } else {
                $menutypes[] = $menutype;
            }
        }

        $presets = array_merge($presets, (array) $this->params->get('presets', []));
        $roots   = [];

        // No fallback: an unknown preset must not load "default" a second time.
        foreach (array_unique(array_filter($presets)) as $preset) {
            $roots[] = MenusHelper::loadPreset($preset, false);
        }

        foreach (array_unique($menutypes) as $menutype) {
            $roots[] = MenusHelper::getMenuItems($menutype, true);
        }

        return $roots;
    }

    /**
     * Give items without a group (e.g. from third-party plugins) the linked component's name.
     *
     * @param   array  $items  The items.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    private function completeGroups(array $items): array
    {
        foreach ($items as &$item) {
            if (empty($item['group'])) {
                $item['group'] = $this->getComponentGroup($item['link'] ?? '') ?: Text::_('PLG_SYSTEM_COMMANDS_GROUP_OTHER');
            }
        }

        return array_values($items);
    }

    /**
     * Number of links the browser renders for these items: every item plus its quicktask.
     *
     * @param   array  $items  The items.
     *
     * @return  integer
     *
     * @since   __DEPLOY_VERSION__
     */
    private function countLinks(array $items): int
    {
        return \count($items) + \count(array_filter(array_column($items, 'quicktask')));
    }

    /**
     * Normalize menu items
     *
     * @param   AdministratorMenuItem  $parent   The menu item to collect children from.
     * @param   string                 $context  Translated title of the enclosing heading or
     *                                           parent component, used as the group.
     *
     * @return array
     */
    protected function normalizeMenuItems($parent, string $context = ''): array
    {
        $menuItems = [];

        if (!$parent->hasChildren()) {
            return $menuItems;
        }

        $children = $parent->getChildren();

        foreach ($children as $item) {
            $title = Text::_($item->title);

            if ($item->type === 'heading' || ($item->type === 'component' && $item->hasChildren())) {
                $menuItems = array_merge($menuItems, $this->normalizeMenuItems($item, $title));

                continue;
            }

            // External links, e.g. the help preset's documentation and forum links.
            if ($item->type === 'url' && $item->link) {
                $menuItems[] = [
                    'group'  => $context,
                    'title'  => $title,
                    'link'   => $item->link,
                    'target' => $item->target,
                ];

                continue;
            }

            if ($item->type === 'component') {
                $group = $context ?: (!empty($item->element) ? Text::_(strtoupper($item->element)) : '');

                $menuItems[] = [
                    'group'  => $group,
                    'title'  => $title,
                    'link'   => Route::_($item->link, false),
                    'target' => $item->target,
                ];

                $quicktaskLink = $item->getParams()->get('menu-quicktask');

                if ($quicktaskLink) {
                    $permission = $item->getParams()->get('menu-quicktask-permission');
                    $scope      = $item->scope !== 'default' ? $item->scope : null;

                    if (!$permission || $this->getApplication()->getIdentity()->authorise($permission, $scope)) {
                        $quicktaskTitleKey = $item->getParams()->get('menu-quicktask-title');
                        $quicktaskTitle    = $quicktaskTitleKey
                            ? Text::_($quicktaskTitleKey)
                            : Text::sprintf('PLG_SYSTEM_COMMANDS_QUICKTASK_NEW', $title);

                        $menuItems[\count($menuItems) - 1]['quicktask'] = [
                            'title' => $quicktaskTitle,
                            'link'  => Route::_($quicktaskLink, false),
                        ];
                    }
                }
            }

            if ($item->hasChildren()) {
                $menuItems = array_merge($menuItems, $this->normalizeMenuItems($item, $context));
            }
        }

        return $menuItems;
    }

    /**
     * Collect mod_quickicon's dashboard buttons as palette items.
     *
     * All "show_*" flags are enabled: they are display preferences, not ACL - access is checked
     * per button instead.
     *
     * @return array
     *
     * @since __DEPLOY_VERSION__
     */
    protected function getQuickIconItems(): array
    {
        $app = $this->getApplication();

        // Read the flags from the module's manifest so new core buttons show up without changes here.
        $manifest = simplexml_load_file(JPATH_ADMINISTRATOR . '/modules/mod_quickicon/mod_quickicon.xml');
        $flags    = $manifest ? array_map('strval', $manifest->xpath('//field[starts-with(@name, "show_")]/@name') ?: []) : [];

        // 1, not 2: "show with count" would only add an ajaxurl the palette doesn't use.
        $buttons = (new QuickIconHelper())->getButtons(new Registry(array_fill_keys($flags, 1)), $app);

        $items = [];

        foreach ($buttons as $button) {
            $name = $button['name'] ?? $button['text'] ?? null;
            $link = $button['link'] ?? null;

            if (!$name || !$link) {
                continue;
            }

            if (!$this->isQuickIconAllowed($button['access'] ?? true)) {
                continue;
            }

            $link = str_replace('&amp;', '&', $link);

            $item = [
                'group' => $this->getComponentGroup($link),
                'title' => Text::_($name),
                'link'  => $link,
            ];

            if (!empty($button['linkadd'])) {
                $item['quicktask'] = [
                    'title' => Text::_($name . '_ADD'),
                    'link'  => str_replace('&amp;', '&', $button['linkadd']),
                ];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Group for a link: the linked component's name, plus the client if any.
     *
     * @param   string  $link  The link, not &amp;-escaped.
     *
     * @return  string
     *
     * @since __DEPLOY_VERSION__
     */
    private function getComponentGroup(string $link): string
    {
        $uri    = new Uri($link);
        $option = $uri->getVar('option');

        if (!$option) {
            return '';
        }

        $language = $this->getApplication()->getLanguage();
        $language->load($option . '.sys', JPATH_ADMINISTRATOR)
            || $language->load($option . '.sys', JPATH_ADMINISTRATOR . '/components/' . $option);

        // No translated component name (e.g. a third-party icon without a .sys file) - no group
        // rather than a raw COM_FOO key.
        if (!$language->hasKey(strtoupper($option))) {
            return '';
        }

        $component = Text::_(strtoupper($option));

        $client = match ($uri->getVar('client_id')) {
            '0'     => Text::_('JSITE'),
            '1'     => Text::_('JADMINISTRATOR'),
            default => '',
        };

        return $client !== '' ? $component . ' (' . $client . ')' : $component;
    }

    /**
     * Check a quick icon button's 'access' value the same way
     * \Joomla\CMS\HTML\Helpers\Icons::button() does.
     *
     * @param   array|bool  $access  The button's access value.
     *
     * @return  bool
     *
     * @since __DEPLOY_VERSION__
     */
    private function isQuickIconAllowed($access): bool
    {
        if (\is_bool($access)) {
            return $access;
        }

        $user = $this->getApplication()->getIdentity();

        for ($i = 0, $n = \count($access); $i < $n; $i += 2) {
            if (!$user->authorise($access[$i], $access[$i + 1] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add Html-Template for the command palette.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function onAfterRender()
    {
        /** @var \Joomla\Application\WebApplicationInterface $app */
        $app = $this->getApplication();

        if (!$app->isClient('administrator') || !($app->getDocument() instanceof \Joomla\CMS\Document\HtmlDocument)) {
            return;
        }

        $bodyBuffer = $app->getBody();

        if (!$this->isTemplateAppend) {
            $templateCommandPalette = LayoutHelper::render('joomla.commands.default', ['editMode' => $app->getInput()->getBool('hidemainmenu')]);

            $bodyBuffer = str_ireplace('</body>', $templateCommandPalette . '</body>', $bodyBuffer);

            $app->setBody($bodyBuffer);

            $this->isTemplateAppend = true;
        }
    }
}
