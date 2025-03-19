<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Form\Field;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Field to show a form builder.
 *
 * @since  __DEPLOY_VERSION__
 */
class FormbuilderField extends FormField
{

    /**
     * The form field type.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $type = 'Formbuilder';

    /**
     * Name of the layout being used to render the field
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $layout = 'joomla.form.field.formbuilder.formbuilder';

    /**
     * Form source
     * @var string
     */
    protected $formsource;

    /**
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @param   string  $name  The property name for which to get the value.
     *
     * @return  mixed  The property value or null.
     *
     * @since   3.6
     */
    public function __get($name)
    {
        switch ($name) {
            case 'formsource':
            case 'buttons':
                return $this->$name;
        }

        return parent::__get($name);
    }

    /**
     * Method to set certain otherwise inaccessible properties of the form field object.
     *
     * @param   string  $name   The property name for which to set the value.
     * @param   mixed   $value  The value of the property.
     *
     * @return  void
     *
     * @since   3.6
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'formsource':
                $this->formsource = (string) $value;

                // Add root path if we have a path to XML file
                if (strrpos($this->formsource, '.xml') === \strlen($this->formsource) - 4) {
                    $this->formsource = Path::clean(JPATH_ROOT . '/' . $this->formsource);
                }

                break;

            case 'buttons':
                if (!$this->multiple) {
                    $this->buttons = [];
                    break;
                }

                if ($value && !\is_array($value)) {
                    $value = explode(',', (string) $value);
                    $value = array_fill_keys(array_filter($value), true);
                }

                if ($value) {
                    $value         = array_merge(['add' => false, 'remove' => false, 'move' => false], $value);
                    $this->buttons = $value;
                }

                break;

            case 'value':
                // We allow a json encoded string or an array
                if (\is_string($value)) {
                    $value = json_decode($value, true);
                }

                $this->value = $value !== null ? (array) $value : null;

                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Prepares the fields form
     *
     * @param   Form          $form  The form to change
     * @param   array|object  $data  The form data
     *
     * @return void
     */
    public function prepareForm(Form $form, $data)
    {
        $form->setFieldAttribute('formbuilder', 'layout', $this->layout);
    }

    /**
     * Method to get the data to be passed to the layout for rendering.
     *
     * @return  array
     *
     * @since 3.5
     */
    protected function getLayoutData()
    {
        /** @var Joomla\Component\Contact\Site\Model\FormModel */
        // $model = Factory::getApplication()->bootComponent('com_contact')
                    // ->getMVCFactory()->createModel('Contact', 'Site', ['ignore_request' => true]);
        // Form::addFormPath(\JPATH_ROOT . '/components/com_contact/forms/contact');
        // get the id of the current item
        $id = (int) Factory::getApplication()->getInput()->getInt('id');
        // load the form
        $formFactory = Factory::getContainer()->get(FormFactoryInterface::class);
        $form = $formFactory->createForm('com_contact.contact', ['control' => 'jformbuilder', 'load_data' => false]);
        $source = \JPATH_ROOT . '/components/com_contact/forms/contact.xml';
        $xpath = null;
        $form->loadFile($source, false, $xpath);
        Factory::getApplication()->getLanguage()->load('com_contact', \JPATH_SITE);
        // $model->setState('contact.id', '1');
        // @todo merge the current item with the global config if possible
        $params = ComponentHelper::getParams('com_contact');
        if (!$params->get('show_email_copy', 0)) {
            $form->removeField('contact_email_copy');
        }
        foreach ($form->getGroup('') as $field) {
            // set the data attributes for the formbuilder in each field
            $formbuilderFieldData =
            json_encode(
                [
                'name' => $field->fieldname,
                'group' => $field->group,
                'required' => $field->required,
                'hidden' => $field->hidden,
                'customfieldId' => ''],
                \JSON_FORCE_OBJECT
            );
            $form->setFieldAttribute($field->fieldname, 'data-formbuilder', $formbuilderFieldData);
            // disable the fields and remove required
            $form->setFieldAttribute($field->fieldname, 'disabled', 'disabled');
            $form->setFieldAttribute($field->fieldname, 'hidden', 'false');
            $form->setFieldAttribute($field->fieldname, 'required', 'false');
        }
        $label       = !empty($this->element['label']) ? (string) $this->element['label'] : null;
        $label       = $label && $this->translateLabel ? Text::_($label) : $label;
        $description = !empty($this->description) ? $this->description : null;
        $description = !empty($description) && $this->translateDescription ? Text::_($description) : $description;
        $alt         = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
        $options     = [
            'autocomplete'   => $this->autocomplete,
            'autofocus'      => $this->autofocus,
            'class'          => $this->class,
            'description'    => $description,
            'disabled'       => $this->disabled,
            'field'          => $this,
            'group'          => $this->group,
            'hidden'         => $this->hidden,
            'hint'           => $this->translateHint ? Text::alt($this->hint, $alt) : $this->hint,
            'id'             => $this->id,
            'label'          => $label,
            'labelclass'     => $this->labelclass,
            'multiple'       => $this->multiple,
            'name'           => $this->name,
            'onchange'       => $this->onchange,
            'onclick'        => $this->onclick,
            'pattern'        => $this->pattern,
            'validationtext' => $this->validationtext,
            'readonly'       => true,
            'repeat'         => $this->repeat,
            'required'       => (bool) $this->required,
            'size'           => $this->size,
            'spellcheck'     => $this->spellcheck,
            'validate'       => $this->validate,
            'value'          => $this->value,
            'dataAttribute'  => $this->renderDataAttributes(),
            'dataAttributes' => $this->dataAttributes,
            'parentclass'    => $this->parentclass,
            'form'           => $form,
        ];

        return $options;
    }

}
