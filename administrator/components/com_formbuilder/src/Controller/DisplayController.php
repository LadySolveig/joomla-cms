<?php

/**
 * @package       Joomla.Administrator
 * @subpackage    com_formbuilder
 *
 * @copyright     (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Formbuilder\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Component Controller
 *
 * @since __DEPLOY_VERSION__
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var   string
     * @since __DEPLOY_VERSION__
     */
    protected $default_view = 'forms';

    /**
     * Method to display a view.
     *
     * @param   boolean $cachable   If true, the view output will be cached
     * @param   array   $urlparams  An array of safe URL parameters and their variable types.
     *                  @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
     *
     * @return  static |boolean  This object to support chaining. False on failure.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function display($cachable = false, $urlparams = [])
    {
        // $view   = $this->input->get('view', $this->default_view);
        // $layout = $this->input->get('layout', 'default');
        // $id     = $this->input->getInt('id');

        // Show messages about the disabled plugin @todo
        // if ($view === 'formbuilder' && !PluginHelper::isEnabled('system', 'formbuilder')) {
        //     $this->app->enqueueMessage(Text::_('COM_FORMBUILDER_PLUGIN_DISABLED'), 'error');
        // }

        return parent::display();
    }
}
