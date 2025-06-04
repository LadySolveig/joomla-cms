<?php

/**
 * @package       Joomla.Administrator
 * @subpackage    com_formbuilder
 *
 * @copyright     (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Guidedtours\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Formbuilder list controller class.
 *
 * @since __DEPLOY_VERSION__
 */

class FormsController extends AdminController
{
    /**
     * Proxy for getModel.
     *
     * @param   string $name   The model name. Optional.
     * @param   string $prefix The class prefix. Optional.
     * @param   array  $config The array of possible config values. Optional.
     *
     * @return \Joomla\CMS\MVC\Model\BaseDatabaseModel
     *
     * @since __DEPLOY_VERSION__
     */
    public function getModel($name = 'Form', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
