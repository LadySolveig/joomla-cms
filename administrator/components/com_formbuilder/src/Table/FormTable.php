<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_formbuilder
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Formbuilder\Administrator\Table;

use Joomla\CMS\Access\Rules;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Forms Table
 *
 * @since  __DEPLOY_VERSION__
 */
class FormTable extends Table implements CurrentUserInterface
{
    use CurrentUserTrait;

    /**
     * An array of key names to be json encoded in the bind function
     *
     * @var    array
     * @since  __DEPLOY_VERSION__
     */
    protected $_jsonEncode = [
        'params',
    ];

    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     * @since  __DEPLOY_VERSION__
     */
    protected $_supportNullValue = true;

    /**
     * Class constructor.
     *
     * @param   DatabaseInterface     $db          Database connector object
     * @param   ?DispatcherInterface  $dispatcher  Event dispatcher for this table
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__formbuilder_forms', 'id', $db, $dispatcher);

        $this->setColumnAlias('published', 'state');
    }

    /**
     * Method to bind an associative array or object to the \Joomla\CMS\Table\Table instance.This
     * method only binds properties that are publicly accessible and optionally
     * takes an array of properties to ignore when binding.
     *
     * @param   mixed  $src     An associative array or object to bind to the \Joomla\CMS\Table\Table instance.
     * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
     *
     * @return  boolean  True on success.
     *
     * @since   __DEPLOY_VERSION__
     * @throws  \InvalidArgumentException
     */
    public function bind($src, $ignore = '')
    {
        return parent::bind($src, $ignore);
    }

    /**
     * Method to perform sanity checks on the \Joomla\CMS\Table\Table instance properties to ensure
     * they are safe to store in the database.  Child classes should override this
     * method to make sure the data they are storing in the database is safe and
     * as expected before storage.
     *
     * @return  boolean  True if the instance is sane and able to be stored in the database.
     *
     * @link    https://docs.joomla.org/Special:MyLanguage/JTable/check
     * @since   __DEPLOY_VERSION__
     */
    public function check()
    {
        if (empty($this->formbuilder)) {
            $this->formbuilder = '{}';
        }

        if (empty($this->params)) {
            $this->params = '{}';
        }

        $date = Factory::getDate()->toSql();
        $user = $this->getCurrentUser();

        // Set created date if not set.
        if (!(int) $this->created_time) {
            $this->created_time = $date;
        }

        if ($this->id) {
            // Existing item
            $this->modified_time = $date;
            $this->modified_by   = $user->id;
        } else {
            if (!(int) $this->modified_time) {
                $this->modified_time = $this->created_time;
            }

            if (empty($this->created_user_id)) {
                $this->created_user_id = $user->id;
            }

            if (empty($this->modified_by)) {
                $this->modified_by = $this->created_user_id;
            }
        }

        return true;
    }

    /**
     * Overloaded store function
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  mixed  False on failure, positive integer on success.
     *
     * @see     Table::store()
     * @since   __DEPLOY_VERSION_
     */
    public function store($updateNulls = true)
    {
        return parent::store($updateNulls);
    }

}
