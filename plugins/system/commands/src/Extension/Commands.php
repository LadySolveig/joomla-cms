<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.commands
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Commands\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

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

        // @todo: Implement unique event
        $event = new GenericEvent(
            'onLoadCommands',
            [
                'context'   => $context,
                'onLoadCommands' => $commands,
            ]
        );

        $this->getDispatcher()->dispatch('onLoadCommands', $event);

        $commands = $event->getArgument('commands');

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
     * @param   Event  $event  The event
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function addCommands(Event $event)
    {
        $commands = $event->getArgument('commands', []);

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

        $event->setArgument('commands', $commands);
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

        static $loaded = false;

        if (!$loaded) {

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

            $loaded = true;
        }
    }

    private function checkTemplateAlreadyAvailabel($content)
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $template = $dom->getElementById('command-dialog-search');
        if ($template) {
            return true;
        }
        return false;
    }
}
