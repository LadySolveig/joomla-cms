<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.commands
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Commands\Extension;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Event\Plugin\System\Commands;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Menus\Administrator\Helper\MenusHelper;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\Submenu\Administrator\Menu\Menu;

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
            'onBeforeCompileHead'      => 'initialize',
            'onLoadCommands'           => 'addCommands',
            'onAfterRender'            => 'onAfterRender',
            'onAjaxCommands'           => 'onAjaxCommands',
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

        $context = $this->getApplication()->getInput()->get('option') . '.' . $this->getApplication()->getInput()->get('view');

        $commands = [];

        $event = new Commands\LoadCommandsEvent('onLoadCommands', ['context' => $context, 'commands' => $commands]);

        $this->getDispatcher()->dispatch('onLoadCommands', $event);

        $commands = $event->getCommands();

        // Text::script('PLG_SYSTEM_SHORTCUT_OVERVIEW_HINT');
        Text::script('PLG_SYSTEM_COMMANDS_OVERVIEW_TITLE');
        // Text::script('PLG_SYSTEM_SHORTCUT_OVERVIEW_DESC');
        // Text::script('PLG_SYSTEM_SHORTCUT_THEN');
        Text::script('JCLOSE');

        $document = $this->getApplication()->getDocument();
        $wa       = $document->getWebAssetManager();

        // @todo: move to asset.joomla.json
        $wa->registerAndUseScript(
            'plg_system_commands.commands',
            'plg_system_commands/commands.min.js',
            [],
            ['type' => 'module'],
            ['hotkeysjs', 'joomla.dialog']
        );

        $timeout = $this->params->get('timeout', 2000);

        $openHotKey = $this->params->get('openhotkey', 'cmd+alt+k,ctrl+alt+k');

        $document->addScriptOptions('plg_system_commands.commands', $commands);
        $document->addScriptOptions('plg_system_commands.timeout', $timeout);
        $document->addScriptOptions('plg_system_commands.openhotkey', $openHotKey);
    }

    /**
     * Add default commands and shortkeys to the document
     *
     * @param   Commands\LoadCommandsEvent  $event  The event
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function addCommands(Commands\LoadCommandsEvent $event)
    {
        $commands = $event->getCommands();

        $commands = array_merge(
            $commands,
            [
                'applyKey'   => (object) ['selector' => 'joomla-toolbar-button .button-apply', 'shortcut' => 'A', 'title' => $this->getApplication()->getLanguage()->_('JAPPLY')],
                'saveKey'    => (object) ['selector' => 'joomla-toolbar-button .button-save', 'shortcut' => 'S', 'title' => $this->getApplication()->getLanguage()->_('JTOOLBAR_SAVE')],
                'cancelKey'  => (object) ['selector' => 'joomla-toolbar-button .button-cancel', 'shortcut' => 'Q', 'title' => $this->getApplication()->getLanguage()->_('JCANCEL')],
                'newKey'     => (object) ['selector' => 'joomla-toolbar-button .button-new', 'shortcut' => 'N', 'title' => $this->getApplication()->getLanguage()->_('JTOOLBAR_NEW')],
                'searchKey'  => (object) ['selector' => 'input[placeholder=' . $this->getApplication()->getLanguage()->_('JSEARCH_FILTER') . ']', 'shortcut' => 'F', 'title' => $this->getApplication()->getLanguage()->_('JSEARCH_FILTER')],
                'optionKey'  => (object) ['selector' => 'joomla-toolbar-button .button-options', 'shortcut' => 'O', 'title' => $this->getApplication()->getLanguage()->_('JOPTIONS')],
                'helpKey'    => (object) ['selector' => 'joomla-toolbar-button .button-help', 'shortcut' => 'H', 'title' => $this->getApplication()->getLanguage()->_('JHELP')],
                'toggleMenu' => (object) ['selector' => '#menu-collapse', 'shortcut' => 'M', 'title' => $this->getApplication()->getLanguage()->_('JTOGGLE_SIDEBAR_MENU')],
                'dashboard'  => (object) ['selector' => (string) new Uri(Route::_('index.php?')), 'shortcut' => 'D', 'title' => $this->getApplication()->getLanguage()->_('JHOMEDASHBOARD')],
            ]
        );

        $event->setCommands($commands);
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
     * @return  void
     *
     * @since _DEPLOY_VERSION_
     *
     * @throws  Notallowed  If user is not allowed
     *
     */
    public function onAjaxCommands(AjaxEvent $event)
    {
        if (!Session::checkToken('request')) {
            throw new NotAllowed(Text::_('JINVALID_TOKEN'), 403);
        }

        /**
         * @var Joomla\CMS\Application\AdministratorApplication $app
         */
        $app = $this->getApplication();

        if (!$app->isClient('administrator') || !$this->isAllowedUser()) {
            throw new NotAllowed($this->getApplication()->getLanguage()->_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
        };

        $search = $app->getInput()->getString('search', '');

        /* if (empty($search)) {
            $response['success'] = false;
            $response['message'] = $this->getApplication()->getLanguage()->_('PLG_SYSTEM_COMMANDS_SEARCH_EMPTY');

            $event->updateEventResult($response);
            return;
        } */

        $adminMenuItem = MenusHelper::loadPreset('default');

        Menu::preprocess($adminMenuItem);

        $items = [];

        /**
         * Things to consider for next time
         * - Headings should become prefix of the items
         * - each menu item has only two (three) levels
         * |- Heading
         * |- Item | Dashboard and Quickicon
         *
         * e.g. Contacts: Add category
         * e.g. Content: Add article
         */
        if ($adminMenuItem->hasChildren()) {
            $items = $this->normalizeMenuItems($adminMenuItem);
        }

        $searchEvent = new Commands\CommandsSearchEvent(
            'onCommandsSearch',
            [
                'context' => $app->getInput()->get('option') . '.' . $app->getInput()->get('view'),
                'search'  => $search,
                'items'   => $items,
            ]
        );

        $this->getDispatcher()->dispatch('onCommandsSearch', $searchEvent);

        $items = $searchEvent->getItems();

        $items = array_filter($items, function ($item) use ($search) {
            return strpos($item['title'], $search) !== false;
        });

        usort($items, function ($a, $b) {
            return strcmp($a['title'], $b['title']);
        });

        $response['success'] = true;
        $response['items'] = $items;

        // https://github.com/joomla/joomla-cms/pull/43530 for J5.3
        $event->updateEventResult($response);
    }

    /**
     * Normalize menu items
     *
     * @return array
     */
    protected function normalizeMenuItems($parent): array
    {
        $menuItems = [];

        if (!$parent->hasChildren()) {
            return $menuItems;
        }

        $children = $parent->getChildren();

        foreach ($children as $item) {

            if ($item->type === 'component' && !in_array($item->type, ['heading', 'separator'])) {

                $menuItems[] = [
                    'title' => Text::_($item->title),
                    'link'  => Route::_($item->link),
                ];

                if ($item->getParams()->get('menu-quicktask')) {
                    $menuItems[count($menuItems) - 1]['quicktask'] = [
                        'title' => Text::_($item->getParams()->get('menu-quicktask-title')),
                        'link'  => Route::_($item->getParams()->get('menu-quicktask')),
                    ];
                }

                /* if ($item->getParams()->get('menu-quicktask')) {
                    $menuItems[] = [
                        'title' => Text::_($item->getParams()->get('menu-quicktask-title')),
                        'link'  => Route::_($item->getParams()->get('menu-quicktask')),
                    ];
                }*/
            }

            if ($item->hasChildren()) {
                $subMenu = $this->normalizeMenuItems($item);

                $menuItems = array_merge($menuItems, $subMenu);
            }
        }

        return $menuItems;
    }

    /**
     * Add Html-Template for the command palette.
     *
     * @return  void
     */
    public function onAfterRender()
    {
        /** @var \Joomla\Application\WebApplicationInterface $app */
        $app        = $this->getApplication();

        if (!$app->isClient('administrator') || !($app->getDocument() instanceof \Joomla\CMS\Document\HtmlDocument)) {
            return;
        }

        $bodyBuffer = $app->getBody();

        /*if ($this->checkTemplateAlreadyAvailabel($bodyBuffer)) {
            return;
        }*/

        if (!$this->isTemplateAppend) {

            $templateCommandPalette = LayoutHelper::render('joomla.commands.default');

            /*$templateCommandPalette = '<template id="command-palette-dialog-search">
                                        <div class="command-palette-dialog-search">
                                            <form action="#" method="post" name="adminCommandForm" id="adminCommandForm" data-multiselect="">
                                            <input type="text" name="command[search]" id="command_search" value="" class="form-control" aria-describedby="command_search-desc" placeholder="Search" inputmode="search">
                                            </form>
                                        </div>
                                        <hr>
                                        <ul class="list-group">
                                            <li class="list-group-item">
                                                Test
                                            </li>
                                            <li class="list-group-item">Test</li>
                                        </ul>
                                       </template>';*/

            $bodyBuffer = str_ireplace('</body>', $templateCommandPalette . '</body>', $bodyBuffer);

            $app->setBody($bodyBuffer);

            $this->isTemplateAppend = true;
        }
    }
}
