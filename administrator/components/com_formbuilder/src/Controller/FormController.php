<?php

/**
 * @package       Joomla.Administrator
 * @subpackage    com_formbuilder
 *
 * @copyright     (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Formbuilder\Administrator\Controller;

use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController as LibFormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Controller for a single form
 *
 * @since __DEPLOY_VERSION__
 */
class FormController extends LibFormController
{

    /**
     * Method to edit a language string via ajax.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     *
     * @throws  \InvalidArgumentException
     */
    public function editlang()
    {
        if (!Session::checkToken('json')) {
            throw new \InvalidArgumentException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }

        $input   = $this->app->getInput();
        $content = $input->json;
        $data = [
            'key' => $content->getString('key'),
            'override' => $content->getString('override'),
            'id' => $content->getString('id')
        ];

        $this->editLangString($data, 'administrator');
    }

    /**
     * Method to edit a language string.
     *
     * @param   array  $data    The data to edit.
     * @param   string $client  The client to edit the language string for.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function editLangString($data = [], $client = 'administrator')
    {
        $lang = $this->app->getLanguage();

        // Parse the override.ini file in order to get the keys and strings.
        $fileName = \constant('JPATH_' . strtoupper($client)) . '/language/overrides/' . ($lang->getTag() ?: 'en-GB') . '.override.ini';
        $strings  = LanguageHelper::parseIniFile($fileName);

        if (isset($strings[$data['id']])) {
            // If an existent string was edited check whether
            // the name of the constant is still the same.
            if ($data['key'] == $data['id']) {
                // If yes, simply override it.
                $strings[$data['key']] = $data['override'];
            } else {
                // If no, delete the old string and prepend the new one.
                unset($strings[$data['id']]);
                $strings += [$data['key'] => $data['override']];
            }
        } else {
            // If it is a new override simply prepend it.
            $strings += [$data['key'] => $data['override']] ;
        }

        // Write override.ini file with the strings.
        if (LanguageHelper::saveToIniFile($fileName, $strings) === false) {
            echo new JsonResponse([], Text::_('COM_FORMBUILDER_FIELD_FORMBUILDER_ERROR_LANGUAGE_STRING_AJAX'), false);
            $this->app->close();
        }

        // Save also to client if needed
        if ($client === 'administrator') {
            $result['title'] = $data['override'];
            echo new JsonResponse($result, null, false);
            return $this->editlangstring($data, 'site');
        }
        $this->app->close();
    }

    /**
     * Method to reload a record.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function reload($key = null, $urlVar = 'id')
    {
        parent::reload($key, $urlVar);
    }
}
