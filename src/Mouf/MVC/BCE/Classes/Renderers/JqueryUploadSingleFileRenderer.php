<?php
/**
 * Base class for rendering tree select field
 */

namespace Mouf\MVC\BCE\Classes\Renderers;

use Mouf\Html\Utils\WebLibraryManager\WebLibraryManager;
use Mouf\MVC\BCE\Classes\Descriptors\BCEFieldDescriptorInterface;
use Mouf\MVC\BCE\Classes\Descriptors\FieldDescriptorInstance;
use Mouf\Html\Widgets\Form;
use Mouf\MVC\BCE\Classes\Descriptors\JqueryUploadSingleFileFieldDescriptor;
use Mouf\MVC\BCE\Classes\ValidationHandlers\BCEValidationUtils;
use Mouf\MVC\BCE\Classes\BCEException;
use Mouf\Html\Widgets\JqueryFileUpload\FileWidget;
use Mouf\Html\Widgets\JqueryFileUpload\JqueryFileUploadField;

class JqueryUploadSingleFileRenderer extends DefaultViewFieldRenderer implements SingleFieldRendererInterface {

    /**
     * (non-PHPdoc)
     * @see FieldRendererInterface::render()
     */
    public function renderEdit($descriptorInstance){
    	/* @var $descriptorInstance FieldDescriptorInstance */
    	
        $descriptor = $descriptorInstance->fieldDescriptor;

        if (!$descriptor instanceof JqueryUploadSingleFileFieldDescriptor) {
        	throw new BCEException("You can only use JqueryUploadMultiFileRenderer on instances of JqueryUploadMultiFileFieldDescriptor");
        }
        
        $fileUploadWidget = $descriptor->getFileUploaderWidget();
        
        $fileUploadWidget->setName($descriptor->getFieldName());
        if($descriptorInstance->getFieldValue() != null){
            $fileUploadWidget->addDefaultFile(new FileWidget($descriptorInstance->getFieldValue(), md5($descriptorInstance->getFieldValue())));
        }

        $fileUploadField = new JqueryFileUploadField($descriptor->getFieldLabel());
        $fileUploadField->setRequired(BCEValidationUtils::hasRequiredValidator($descriptorInstance->fieldDescriptor->getValidators()));
        $fileUploadField->setJqueryFileUploadWidget($fileUploadWidget);
        
        ob_start();
        $fileUploadField->toHtml();
        return ob_get_clean();
    }

    /**
     * (non-PHPdoc)
     * @see FieldRendererInterface::getJS()
     */
    public function getJSEdit(BCEFieldDescriptorInterface $descriptor, $bean, $id, WebLibraryManager $webLibraryManager){
        /* @var $descriptorInstance FieldDescriptorInstance */
        return array();
    }
}
