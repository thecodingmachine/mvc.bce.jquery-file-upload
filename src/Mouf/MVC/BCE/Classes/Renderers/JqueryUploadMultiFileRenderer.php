<?php
/**
 * Base class for rendering tree select field
 */

namespace Mouf\MVC\BCE\Classes\Renderers;

use Mouf\Html\Widgets\TreeSelectField\TreeSelectField;
use Mouf\MVC\BCE\Classes\Descriptors\FieldDescriptorInstance;
use Mouf\Html\Widgets\Form;
use Mouf\MVC\BCE\Classes\ValidationHandlers\BCEValidationUtils;
use Mouf\MVC\BCE\Classes\Renderers\DefaultViewFieldRenderer;
use Mouf\MVC\BCE\Classes\Renderers\SingleFieldRendererInterface;
use Mouf\MVC\BCE\Classes\Descriptors\JqueryUploadMultiFileFieldDescriptor;
use Mouf\MVC\BCE\Classes\BCEException;
use Mouf\Html\Widgets\JqueryFileUpload\FileWidget;
use Mouf\Html\Widgets\JqueryFileUpload\JqueryFileUploadField;

class JqueryUploadMultiFileRenderer extends DefaultViewFieldRenderer implements SingleFieldRendererInterface {

    /**
     * (non-PHPdoc)
     * @see FieldRendererInterface::render()
     */
    public function renderEdit($descriptorInstance){
    	/* @var $descriptorInstance FieldDescriptorInstance */
    	
        $descriptor = $descriptorInstance->fieldDescriptor;

        if (!$descriptor instanceof JqueryUploadMultiFileFieldDescriptor) {
        	throw new BCEException("You can only use JqueryUploadMultiFileRenderer on instances of JqueryUploadMultiFileFieldDescriptor");
        }
        
        $fileUploadWidget = $descriptor->getFileUploadWidget();
        
        $fileUploadWidget->setName($descriptor->getFieldName());
        foreach ($descriptorInstance->getFieldValue() as $file) {
        	$fileUploadWidget->addDefaultFile(new FileWidget($file, md5($file)));
        }
        //$fileUploadWidget->setRequired(BCEValidationUtils::hasRequiredValidator($descriptorInstance->fieldDescriptor->getValidators()));

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
    public function getJSEdit($descriptor, $bean, $id){
        /* @var $descriptorInstance FieldDescriptorInstance */
        return array();
    }
}
