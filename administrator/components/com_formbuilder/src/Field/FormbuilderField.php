<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Formbuilder\Administrator\Field;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Event\DispatcherInterface;
use Joomla\CMS\Event\CustomFields\PrepareDomEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use stdClass;

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
     * The application instance.
     *
     * @var    \Joomla\CMS\Application\Application
     * @since  __DEPLOY_VERSION__
     */
    protected $app;

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
     * Form path to load the form XML file.
     * @var string
     */
    protected $formPath;

    /**
     * Name of the form to load.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $formName;

    /**
     * Name of the component to load the form from.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $component;

    /**
     * Name of the form to prepare.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $scope;

    /**
     * The application context for which the form is used.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $client = 'site';


    /**
     * Constant representing the site application context.
     *
     * @var int
     */
    protected const SITE = 1;

    /**
     * Constant representing the administrator application context.
     *
     * @var int
     */
    protected const ADMINISTRATOR = 2;

    /**
     * Maps client type constants to their corresponding string names.
     *
     * @var array<int, string> $clientNames
     */
    protected static $clientNames = [
        self::SITE => 'site',
        self::ADMINISTRATOR => 'administrator',
    ];

    /**
     * Array of field types.
     *
     * @var   array|null
     * @since __DEPLOY_VERSION__
     */
    protected $fieldTypes = null;

    /**
     * Method to instantiate the form field object.
     *
     * @param   Form  $form  The form to attach to the form field object.
     *
     * @since   1.7.0
     */
    public function __construct($form = null)
    {
        // Set the application instance
        $this->app = Factory::getApplication();

        // Retrieves and assigns the available field types using the FieldsHelper.
        // @see FieldsHelper::getFieldTypes()
        $this->fieldTypes = FieldsHelper::getFieldTypes();

        // Call the parent constructor
        parent::__construct($form);
    }
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
            case 'formPath':
                if (empty($this->formPath)) {
                    $this->formPath = 'components/' . $this->component . '/forms';
                }

                return $this->formPath;
            case 'formName':
            case 'component':
            case 'scope':
            case 'buttons':
            case 'client':
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
     * @since   __DEPLOY_VERSION__
     */
    public function __set($name, $value)
    {
        switch ($name) {
            // case 'formsource':
            //     $this->formsource = (string) $value;

            //     // Add root path if we have a path to XML file
            //     if (strrpos($this->formsource, '.xml') === \strlen($this->formsource) - 4) {
            //         $this->formsource = Path::clean(JPATH_ROOT . '/' . $this->formsource);
            //     }

            //     break;

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

            case 'formPath':
            case 'formName':
            case 'component':
            case 'scope':
            case 'client':
               $this->$name = (string) $value;

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
    // public function prepareForm(Form $form, $data)
    // {
        // $form->setFieldAttribute('formbuilder', 'layout', $this->layout);
    // }

    /**
     * Method to attach a Form object to the field.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed              $value    The form field value to validate.
     * @param   string             $group    The field name group control value. This acts as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     *
     * @return  boolean  True on success.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {

        if(!parent::setup($element, $value, $group)) {
            return false;
        }

        $attributes = [
            'formPath', 'formName', 'component', 'scope', 'client'
        ];

        foreach ($attributes as $attributeName) {
            $this->__set($attributeName, $element[$attributeName]);
        }

        $this->fieldTypes = FieldsHelper::getFieldTypes();

        return true;
    }

    /**
     * Method to get the data to be passed to the layout for rendering.
     *
     * @return  array
     *
     * @since __DEPLOY_VERSION__
     */
    protected function getLayoutData()
    {
        $this->app->getLanguage()->load('com_formbuilder', \JPATH_ADMINISTRATOR);

        Form::addFormPath(\JPATH_ROOT . '/' . $this->formPath);

        $params = (object) []; // @todo get the global params from the component

        $jinput = Factory::getApplication()->getInput();
        $catId = (int) $jinput->get('catid', 0, 'INT');
        // $catId = (int) isset($params->catid) ? $params->catid : 0;

        // Load the defined form fields from the value
        $formFields = !empty($this->value) ? \json_decode($this->value) : [];
        $formFields = ArrayHelper::fromObject((object) $formFields);
        // load the form
        $formFactory = Factory::getContainer()->get(FormFactoryInterface::class);
        $form = $formFactory->createForm($this->component . '.' . $this->formName, ['control' => 'jformbuilder', 'load_data' => false]);
        $source = \JPATH_ROOT . '/' . $this->formPath . '/' . $this->formName . '.xml';
        $xpath = null;
        $form->loadFile($source, false, $xpath);
        // Load the custom fields into the form
        FieldsHelper::prepareForm($this->component . '.' . $this->scope, $form, (object) ['catid' => $catId]);
        // Load the language file for the component
        Factory::getApplication()->getLanguage()->load($this->component, \JPATH_SITE);
        // @todo merge the current item with the global config if possible
        $globalParams = ComponentHelper::getParams($this->component);
        $params = new Registry($params);
        $params->merge($globalParams);

        // @todo only mail - remove the email copy field if not needed
        // if ((int) $params->get('show_email_copy', 0) === 0) {
            // $form->removeField('contact_email_copy');
        // }

        // Get the custom fields for the context and catid
        $customFields = FieldsHelper::getFields($this->component . '.' . $this->scope, ['id' => 0, 'catid' => $catId], false, null, false);

        // Check if we have to add custom fields with different context
        // $addCustomFields = []; // @todo performance improvement
        foreach ($customFields as $customField) {
            if ($customField->params->get('show_on', '') !== '' &&
                !$this->app->isClient(self::$clientNames[(int)$customField->params->get('show_on')])) {
                $this->addCustomFieldToForm($customField, $form);
            }
        }

        $fieldsets = $form->getFieldsets();
        foreach ($fieldsets as $fieldset) {
            $fields = $form->getFieldset($fieldset->name);
            if (count($fields)) {
                foreach ($fields as $field) {
                    // set the data attribute fieldset for the formbuilder in each field
                    $form->setFieldAttribute($field->fieldname, 'data-fieldset', $fieldset->name, $field->group ?? null);
                }
            }
        }

        $currentAvailableFormFields = [];

        foreach ($form->getGroup('') as $field) {
            $currentAvailableFormFields[] = $field->fieldname;

            // Check for custom field - get the custom field id and check show_on for client
            // Example input: jformbuilder[com_fields][foo][bar][customfield->name]
            $customFieldId = '';
            $removeField = false;
            $pattern = '/^jformbuilder(?:\[[^\]]+\])+\[([^\]]+)\]$/';
            foreach ($customFields as $customField) {
                // $pattern = '/^jformbuilder(?:\[[^\]]+\])+\[([^\]' . $customField->name . ']+)\]$/';
                if (preg_match($pattern, $field->name, $matches)) {
                    if ($matches[1] === $customField->name) {
                        $customFieldId = (string) $customField->id;

                        // Check if the custom field should be removed based on the show_on parameter
                        $removeField = $customField->params->get('show_on', '') !== '' &&
                            $this->client !== self::$clientNames[(int)$customField->params->get('show_on')];
                        break;
                    }
                }
            }

            if ($removeField) {
                $form->removeField($field->fieldname, $field->group);

                // Check if we also have to remove this from previous used $formFields area
                // @todo
                foreach($formFields as $fieldset) {
                    foreach($fieldset as $index => $formbuilderField) {
                        if ($formbuilderField['name'] === $field->fieldname) {
                            unset($formFields[$index]);
                            $this->app->enqueueMessage('Field is not available anymore `' . $formbuilderField['name'] . '` - custom field show_on', 'warning'); // @todo lang string
                        }
                    }
                }

                continue;
            }

            // set the data attributes for the formbuilder in each field
            $formbuilderFieldData =
            json_encode(
                [
                'name' => $field->fieldname,
                'group' => $field->group,
                'required' => $field->required,
                'hidden' => $field->hidden,
                'id' => $field->id,
                'customfieldId' => $customFieldId],
                \JSON_FORCE_OBJECT
            );

            // Check if present in formFields and set a data-formpresent attribute
            foreach($formFields as $fieldset) {
                if (array_search($field->id, array_column($fieldset, 'id')) !== false) {
                    // If the field is already present in formFields, we update the data-formpresent attribute
                    $form->setFieldAttribute($field->fieldname, 'data-formpresent', 'true', $field->group ?? null);
                }
            }

            // @todo $field->params->get('show_on', 0); @see displayFieldOnForm($field) administrator/components/com_fields/src/Helper/FieldsHelper.php

            $form->setFieldAttribute($field->fieldname, 'data-formbuilder', $formbuilderFieldData, $field->group ?? null);
            // $form->setFieldAttribute($field->fieldname, 'dataFormbuilder', $formbuilderFieldData);
            // $field->dataFormbuilder = $formbuilderFieldData;
            // disable the fields and remove required
            $form->setFieldAttribute($field->fieldname, 'disabled', 'disabled', $field->group ?? null);
            $form->setFieldAttribute($field->fieldname, 'hidden', 'false', $field->group ?? null);
            $form->setFieldAttribute($field->fieldname, 'required', 'false', $field->group ?? null);
        }

        // Check if previous used $formFields are not present anymore
        foreach($formFields as $fieldset) {
            foreach($fieldset as $index => $formbuilderField) {
                if (!in_array($formbuilderField['name'], $currentAvailableFormFields)) {
                    unset($formFields[$index]);
                    $this->app->enqueueMessage('Field is not available anymore `' . $formbuilderField['name'] . '`', 'warning'); // @todo lang string
                }
            }
        }

        $label       = !empty($this->element['label']) ? (string) $this->element['label'] : null;
        $label       = $label && $this->translateLabel ? Text::_($label) : $label;
        $description = !empty($this->description) ? $this->description : null;
        $description = !empty($description) && $this->translateDescription ? Text::_($description) : $description;
        $alt         = \preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
        $options     = [
            // 'autocomplete'   => $this->autocomplete,
            // 'autofocus'      => $this->autofocus,
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
            // 'multiple'       => $this->multiple,
            'name'           => $this->name,
            'onchange'       => $this->onchange,
            // 'onclick'        => $this->onclick,
            // 'pattern'        => $this->pattern,
            'validationtext' => $this->validationtext,
            // 'readonly'       => true,
            // 'repeat'         => $this->repeat,
            'required'       => (bool) $this->required,
            // 'size'           => $this->size,
            // 'spellcheck'     => $this->spellcheck,
            // 'validate'       => $this->validate,
            'value'          => $this->value,
            'dataAttribute'  => $this->renderDataAttributes(),
            // 'dataAttributes' => $this->dataAttributes,
            'parentclass'    => $this->parentclass,
            'form'           => $form,
            'formFields'     => $formFields,
        ];

        return $options;
    }

    /**
     * Allow to override renderer include paths in child fields
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getLayoutPaths()
    {
        $renderer = new FileLayout('default');

        $includePaths = $renderer->getDefaultIncludePaths();
        if (!in_array(\JPATH_ROOT . '/administrator/components/com_formbuilder/layouts', $includePaths)) {
            // Add the layout path for the formbuilder component
            $includePaths[] = \JPATH_ROOT . '/administrator/components/com_formbuilder/layouts';
        }

        return $includePaths;
    }


    /**
     * Add a custom field to the form.
     *
     * @param   object                  $field  The field to add.
     * @param   \Joomla\CMS\Form\Form   $form   The form to which the field should be added.
     *
     * @return  void
     */
    protected function addCustomFieldToForm(object $field, \Joomla\CMS\Form\Form $form) {
        // Creating the dom
        $xml        = new \DOMDocument('1.0', 'UTF-8');
        $fieldsNode = $xml->appendChild(new \DOMElement('form'))->appendChild(new \DOMElement('fields'));
        $fieldsNode->setAttribute('name', 'com_fields');

        if (!\array_key_exists($field->type, $this->fieldTypes)) {
            // Field type is not available
            return;
        }

        if ($path = $this->fieldTypes[$field->type]['path']) {
            // Add the lookup path for the field
            FormHelper::addFieldPath($path);
        }

        if ($path = $this->fieldTypes[$field->type]['rules']) {
            // Add the lookup path for the rule
            FormHelper::addRulePath($path);
        }

        // $fieldsPerGroup[$field->group_id][] = $field;

        $modelFields = Factory::getApplication()->bootComponent('com_fields')
            ->getMVCFactory()->createModel('Groups', 'Administrator', ['ignore_request' => true]);
        $modelFields->setState('filter.context', $this->component . '.' . $this->scope);

        $groups = $modelFields->getItems();
        $group = new \stdClass;
        foreach($groups as $matchingGroup) {
            if ($matchingGroup->id === $field->group_id) {
                $group = $matchingGroup;
            }
        }

        // Defining the field set
        /** @var \DOMElement $fieldset */
        $fieldset = $fieldsNode->appendChild(new \DOMElement('fieldset'));
        $fieldset->setAttribute('name', 'fields-' . $field->group_id);
        $fieldset->setAttribute('addfieldpath', '/administrator/components/' . $this->component . '/models/fields');
        $fieldset->setAttribute('addrulepath', '/administrator/components/' . $this->component . '/models/rules');

        $label       = $group->title ?? '';
        $description = $group->description ?? '';

        if (!$label) {
            $key = strtoupper($this->component . '_FIELDS_' . $this->scope . '_LABEL');

            if (!$this->app->getLanguage()->hasKey($key)) {
                $key = 'JGLOBAL_FIELDS';
            }

            $label = $key;
        }

        if (!$description) {
            $key = strtoupper($this->component . '_FIELDS_' . $this->scope . '_DESC');

            if ($this->app->getLanguage()->hasKey($key)) {
                $description = $key;
            }
        }

        $fieldset->setAttribute('label', $label);
        $fieldset->setAttribute('description', strip_tags($description));

        // Create the node
        $node = $fieldset->appendChild(new \DOMElement('field'));

        // Set the attributes
        // - we do not need the specific field attributes, only these that are needed or could be set with the formbuilder
        $node->setAttribute('name', $field->name);
        $node->setAttribute('type', $field->type);
        $node->setAttribute('label', $field->label);
        $node->setAttribute('description', $field->description);
        $node->setAttribute('required', $field->required ? 'true' : 'false');
        $node->setAttribute('hidden', 'false'); // @todo check if there are any exceptions here

        // When the field set is empty, then remove it
        if (!$fieldset->hasChildNodes()) {
            $fieldsNode->removeChild($fieldset);
        }

        // Loading the XML fields string into the form
        $form->load($xml->saveXML());
    }
}
