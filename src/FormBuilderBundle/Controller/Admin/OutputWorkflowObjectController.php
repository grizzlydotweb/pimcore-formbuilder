<?php

namespace FormBuilderBundle\Controller\Admin;

use FormBuilderBundle\Builder\ExtJsFormBuilder;
use FormBuilderBundle\Configuration\Configuration;
use FormBuilderBundle\Manager\FormDefinitionManager;
use FormBuilderBundle\Manager\OutputWorkflowManager;
use FormBuilderBundle\Model\FormDefinitionInterface;
use FormBuilderBundle\Registry\DynamicObjectResolverRegistry;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\DataObject;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OutputWorkflowObjectController extends AdminController
{
    protected Configuration $configuration;
    protected FormFactoryInterface $formFactory;
    protected FormDefinitionManager $formDefinitionManager;
    protected OutputWorkflowManager $outputWorkflowManager;
    protected DynamicObjectResolverRegistry $dynamicObjectResolverRegistry;
    protected ExtJsFormBuilder $extJsFormBuilder;

    public function __construct(
        Configuration $configuration,
        FormFactoryInterface $formFactory,
        FormDefinitionManager $formDefinitionManager,
        OutputWorkflowManager $outputWorkflowManager,
        DynamicObjectResolverRegistry $dynamicObjectResolverRegistry,
        ExtJsFormBuilder $extJsFormBuilder
    ) {
        $this->configuration = $configuration;
        $this->formFactory = $formFactory;
        $this->formDefinitionManager = $formDefinitionManager;
        $this->outputWorkflowManager = $outputWorkflowManager;
        $this->dynamicObjectResolverRegistry = $dynamicObjectResolverRegistry;
        $this->extJsFormBuilder = $extJsFormBuilder;
    }

    public function getObjectClassesAction(Request $request): JsonResponse
    {
        $list = new DataObject\ClassDefinition\Listing();

        $classList = [];
        foreach ($list->load() as $class) {
            $classList[] = [
                'key'   => $class->getName(),
                'label' => $class->getName()
            ];
        }

        return $this->adminJson([
            'success' => true,
            'types'   => $classList
        ]);
    }

    /**
     * @throws \Exception
     */
    public function getObjectClassesFieldsAction(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $id = $request->get('id');

        $fields = [];
        if ($type === 'fieldCollection') {
            $fieldCollectionDefinition = DataObject\Fieldcollection\Definition::getByKey($id);
            $fields = $fieldCollectionDefinition->getFieldDefinitions();
        } elseif ($type === 'dataClass') {
            $classDefinition = DataObject\ClassDefinition::getById($id);
            $fields = $classDefinition->getFieldDefinitions();
        }

        $flattenFields = [];
        foreach ($fields as $field) {
            $flattenFields[] = [
                'key'   => $field->getName(),
                'label' => empty($field->getTitle()) ? $field->getName() : $field->getTitle(),
            ];
        }

        return $this->adminJson([
            'success' => true,
            'fields'  => $flattenFields
        ]);
    }

    public function getFieldCollectionTypesForDataTypeAction(Request $request): JsonResponse
    {
        $classId = $request->get('classId');
        $fieldCollectionKey = $request->get('fieldCollectionKey');

        try {
            $classDefinition = DataObject\ClassDefinition::getById($classId);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }

        if (!$classDefinition instanceof DataObject\ClassDefinition) {
            return $this->json(['success' => false, 'message' => 'No class definition found.']);
        }

        $classDefinitionField = $classDefinition->getFieldDefinition($fieldCollectionKey);
        if (!$classDefinitionField instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
            return $this->json(['success' => false, 'message' => 'No valid field field collection found.']);
        }

        $allowedTypes = [];
        foreach ($classDefinitionField->getAllowedTypes() as $type) {
            $allowedTypes[] = [
                'key'   => $type,
                'label' => $type
            ];
        }

        return $this->adminJson([
            'success' => true,
            'types'   => $allowedTypes
        ]);
    }

    public function getFormDataAction(Request $request): JsonResponse
    {
        $formId = $request->get('id');
        $baseConfiguration = json_decode($request->get('baseConfiguration', ''), true);

        $classDefinition = null;
        $formDefinition = $this->formDefinitionManager->getById($formId);

        if (!$formDefinition instanceof FormDefinitionInterface) {
            return $this->json(['success' => false, 'message' => 'form is not available']);
        }

        $resolveStrategy = $baseConfiguration['resolveStrategy'];

        if ($resolveStrategy === 'newObject') {
            $resolvingObjectClass = $baseConfiguration['resolvingObjectClass'];
            $classDefinition = DataObject\ClassDefinition::getByName($resolvingObjectClass);
        } elseif ($resolveStrategy === 'existingObject') {
            $dataObject = DataObject::getById($baseConfiguration['resolvingObject']['id']);
            $classDefinition = $dataObject instanceof DataObject\Concrete ? $dataObject->getClass() : null;
        }

        if (!$classDefinition instanceof DataObject\ClassDefinition) {
            return $this->json(['success' => false, 'message' => 'No class definition found.']);
        }

        $configuration = [
            'classId'   => $classDefinition->getId(),
            'className' => $classDefinition->getName()
        ];

        try {
            $extJsFormFields = $this->extJsFormBuilder->generateExtJsFormFields($formDefinition);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }

        $configuration['formFieldDefinitions'] = $extJsFormFields;

        return $this->adminJson([
            'success'       => true,
            'configuration' => $configuration
        ]);
    }

    public function getDynamicObjectResolverAction(Request $request): JsonResponse
    {
        $services = $this->dynamicObjectResolverRegistry->getAll();

        $data = [];
        foreach ($services as $identifier => $service) {
            $data[] = ['label' => $service['label'], 'key' => $identifier];
        }

        return $this->adminJson([
            'success'  => true,
            'resolver' => $data
        ]);
    }
}
