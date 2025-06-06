<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_formbuilder
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Formbuilder\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Table\Category;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Forms Model
 *
 * @since  __DEPLOY_VERSION__
 */
class FormsModel extends ListModel
{
    /**
     * Constructor
     *
     * @param   array                 $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
     * @param   ?MVCFactoryInterface  $factory  The factory.
     *
     * @since   __DEPLOY_VERSION__
     * @throws  \Exception
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'state', 'a.state',
                'access', 'a.access',
                'access_level',
                'context', 'a.context',
                'client', 'a.client',
                'note', 'a.note',
                'category_id', 'a.catid',
                // 'language', 'a.language',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'created_time', 'a.created_time',
                'created_user_id', 'a.created_user_id',
            ];
        }

        parent::__construct($config, $factory);
    }

    /**
     * Method to auto-populate the model state.
     *
     * This method should only be called once per instantiation and is designed
     * to be called on the first call to the getState() method unless the model
     * configuration flag to ignore the request is set.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function populateState($ordering = null, $direction = null)
    {
        // List state information.
        parent::populateState('a.id', 'asc');

        $client = $this->getUserStateFromRequest($this->context . 'forms.client', 'client', 'site', 'CMD');

        $this->setState('filter.client', $client);
    }

    /**
     * Method to get a store id based on the model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  An identifier string to generate the store id.
     *
     * @return  string  A store id.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.context');
        $id .= ':' . $this->getState('filter.client');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . serialize($this->getState('filter.category_id'));
        // $id .= ':' . serialize($this->getState('filter.assigned_cat_ids'));
        $id .= ':' . $this->getState('filter.state');
        // $id .= ':' . serialize($this->getState('filter.language'));

        return parent::getStoreId($id);
    }

    /**
     * Method to get a QueryInterface object for retrieving the data set from a database.
     *
     * @return  QueryInterface   An object implementing QueryInterface to retrieve the data set.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $user  = $this->getCurrentUser();
        $app   = Factory::getApplication();

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.access'),
                    $db->quoteName('a.checked_out'),
                    $db->quoteName('a.checked_out_time'),
                    $db->quoteName('a.note'),
                    $db->quoteName('a.state'),
                    $db->quoteName('a.created_time'),
                    $db->quoteName('a.created_user_id'),
                    $db->quoteName('a.language'),
                    $db->quoteName('a.params'),
                    $db->quoteName('a.catid', 'category_id'),
                    $db->quoteName('a.form_settings'),
                    $db->quoteName('a.context'),
                    $db->quoteName('a.client'),
                ]
            )
        )
            ->select(
                [
                    $db->quoteName('l.title', 'language_title'),
                    $db->quoteName('l.image', 'language_image'),
                    $db->quoteName('uc.name', 'editor'),
                    $db->quoteName('ag.title', 'access_level'),
                    $db->quoteName('ua.name', 'author_name'),
                ]
            )
            ->from('#__formbuilder_forms AS a')
            // Join over the language
            ->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = a.language')
            // Join over the users for the checked out user.
            ->join('LEFT', '#__users AS uc ON uc.id=a.checked_out')
            // Join over the asset groups.
            ->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access')
            // Join over the users for the author.
            ->join('LEFT', '#__users AS ua ON ua.id = a.created_user_id')
            // Join over the categories.
            ->join('LEFT', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'));

        // Filter by context
        if ($context = $this->getState('filter.context')) {
            $query->where($db->quoteName('a.context') . ' = :context')
                ->bind(':context', $context);
        }

        // Filter by client
        if ($client = $this->getState('filter.client')) {
            $client = (string) $client;
            $query->where($db->quoteName('a.client') . ' = :client')
                ->bind(':client', $client, ParameterType::STRING);
        }

        // Filter by access level.
        if ($access = $this->getState('filter.access')) {
            if (\is_array($access)) {
                $access = ArrayHelper::toInteger($access);
                $query->whereIn($db->quoteName('a.access'), $access);
            } else {
                $access = (int) $access;
                $query->where($db->quoteName('a.access') . ' = :access')
                    ->bind(':access', $access, ParameterType::INTEGER);
            }
        }

        // Filter by categories and by level
        $categoryId = $this->getState('filter.category_id', []);
        $level      = (int) $this->getState('filter.level');

        if (!\is_array($categoryId)) {
            $categoryId = $categoryId ? [$categoryId] : [];
        }

        // Case: Using both categories filter and by level filter
        if (\count($categoryId)) {
            $categoryId       = ArrayHelper::toInteger($categoryId);
            $categoryTable    = new Category($db);
            $subCatItemsWhere = [];

            foreach ($categoryId as $key => $filter_catid) {
                $categoryTable->load($filter_catid);

                // Because values to $query->bind() are passed by reference, using $query->bindArray() here instead to prevent overwriting.
                $valuesToBind = [$categoryTable->lft, $categoryTable->rgt];

                if ($level) {
                    $valuesToBind[] = $level + $categoryTable->level - 1;
                }

                // Bind values and get parameter names.
                $bounded = $query->bindArray($valuesToBind);

                $categoryWhere = $db->quoteName('c.lft') . ' >= ' . $bounded[0] . ' AND ' . $db->quoteName('c.rgt') . ' <= ' . $bounded[1];

                if ($level) {
                    $categoryWhere .= ' AND ' . $db->quoteName('c.level') . ' <= ' . $bounded[2];
                }

                $subCatItemsWhere[] = '(' . $categoryWhere . ')';
            }

            $query->where('(' . implode(' OR ', $subCatItemsWhere) . ')');
        } elseif ($level = (int) $level) {
            // Case: Using only the by level filter
            $query->where($db->quoteName('c.level') . ' <= :level')
                ->bind(':level', $level, ParameterType::INTEGER);
        }

        // @todo Implement View Level Access
        // if (!$app->isClient('administrator') || !$user->authorise('core.admin')) {
        //     $groups = $user->getAuthorisedViewLevels();
        //     $query->whereIn($db->quoteName('a.access'), $groups);
        //     $query->extendWhere(
        //         'AND',
        //         [
        //             $db->quoteName('a.group_id') . ' = 0',
        //             $db->quoteName('g.access') . ' IN (' . implode(',', $query->bindArray($groups, ParameterType::INTEGER)) . ')',
        //         ],
        //         'OR'
        //     );
        // }

        // @todo Filter by state
        $state = $this->getState('filter.state');

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $search = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')
                    ->bind(':id', $search, ParameterType::INTEGER);
            } elseif (stripos($search, 'author:') === 0) {
                $search = '%' . substr($search, 7) . '%';
                $query->where(
                    '(' .
                        $db->quoteName('ua.name') . ' LIKE :name OR ' .
                        $db->quoteName('ua.username') . ' LIKE :username' .
                    ')'
                )
                    ->bind(':name', $search)
                    ->bind(':username', $search);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where($db->quoteName('a.note') . ' LIKE :note')
                    ->bind(':note', $search);
            }
        }

        // Filter on the language.
        if ($language = $this->getState('filter.language')) {
            $language = (array) $language;

            $query->whereIn($db->quoteName('a.language'), $language, ParameterType::STRING);
        }

        // Add the list ordering clause
        $listOrdering  = $this->state->get('list.ordering', 'a.id');
        $orderDirn     = $this->state->get('list.direction', 'ASC');

        $query->order($db->escape($listOrdering) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Gets an array of objects from the results of database query.
     *
     * @param   string   $query       The query.
     * @param   integer  $limitstart  Offset.
     * @param   integer  $limit       The number of records.
     *
     * @return  array  An array of results.
     *
     * @since   __DEPLOY_VERSION__
     * @throws  \RuntimeException
     */
    protected function _getList($query, $limitstart = 0, $limit = 0)
    {
        $result = parent::_getList($query, $limitstart, $limit);

        if (\is_array($result)) {
            foreach ($result as $form) {
                $form->params = new Registry($form->params);
            }
        }

        return $result;
    }

    /**
     * Get the filter form
     *
     * @param   array    $data      data
     * @param   boolean  $loadData  load current data
     *
     * @return  \Joomla\CMS\Form\Form|bool  the Form object or false
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getFilterForm($data = [], $loadData = true)
    {
        $form = parent::getFilterForm($data, $loadData);

        if ($form) {
            $form->setValue('client', null, $this->getState('filter.client'));

            // Set extension for category filter or remove field if no context is set
            $context = $this->getState('filter.context');
            $parts = explode('.', $context);
            if (\count($parts) > 1 && \str_starts_with($parts[0], 'com_')) {
                $form->setFieldAttribute('category_id', 'extension', $parts[0], 'filter');
            } else {
                // If the context is not set or does not start with 'com_', we remove the category field
                $form->removeField('category_id', 'filter');
            }
        }

        return $form;
    }
}
