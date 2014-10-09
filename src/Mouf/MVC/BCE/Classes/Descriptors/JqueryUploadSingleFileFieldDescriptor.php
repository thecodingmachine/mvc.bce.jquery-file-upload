<?php
namespace Mouf\MVC\BCE\Classes\Descriptors;
use Mouf\Html\Widgets\JqueryFileUpload\JqueryFileUploadWidget;
use Mouf\MVC\BCE\BCEForm;
use Mouf\MVC\BCE\Classes\BCEException;

/**
 * This class is the simpliest FieldDescriptor:
 * it handles a field that has no "connections" to other objects (
 * as user name or login for example)
 * @Component
 */
class JqueryUploadSingleFileFieldDescriptor extends FieldDescriptor {

    /**
     * The name of the function that returns the value of the field from the bean.
     * For example, with $user->getLogin(), the $getter property should be "getLogin"
     * @Property
     * @var string
     */
    private $fullPathGetter;

    /**
     * The name of the function that sets the value of the field into the bean.
     * For example, with $user->setLogin($login), the $setter property should be "setLogin"
     * @Property
     * @var string
     */
    private $fileNameSetter;

    /**
     *
     * @var JqueryFileUploadWidget
     */
    protected $fileUploaderWidget;

    /**
     * The value of the field once the FiedDescriptor has been loaded
     * @var mixed
     */
    protected $value;

    /**
     * Whether the file should be overrided if it already exists
     * @var bool
     */
    protected $allowOverride = true;


    /**
     * @param string $fullPathGetter The name of the function that returns the full path to the file.
     * @param string $fileNameSetter The name of the function that sets the name of the file in the bean.
     */
    public function __construct($fullPathGetter, $fileNameSetter, JqueryFileUploadWidget $fileUploaderWidget) {
        $this->fullPathGetter = $fullPathGetter;
        $this->fileNameSetter = $fileNameSetter;
        $this->fileUploaderWidget = $fileUploaderWidget;
    }

    /**
     * Loads the values of the bean into the descriptors, calling main bean's getter
     * Eventually formats the value before displaying it
     * @param mixed $mainBean
     */
    public function load($mainBean, $id = null, &$form = null) {
        $fieldValue = $this->getValue($mainBean);
        $descriptorInstance = new FieldDescriptorInstance($this, $form, $id);

        $descriptorInstance->value = $fieldValue;
        $this->fileUploaderWidget->setMaxNumberOfFiles(1);
        return $descriptorInstance;
    }


    /**
     * (non-PHPdoc)
     * For a FieldDecsriptor instance, the preSave function id responsible for :
     *  - unformatting the posted value
     *  - valideting the value
     *  - setting the value into the bean (case of BaseFieldDescriptors)
     *  - settings the linked ids to associate in mapping table (Many2ManyFieldDEscriptors)
     * @see BCEFieldDescriptorInterface::preSave()
     */
    public function preSave($post, BCEForm &$form, $bean, $isNew) {}


    /**
     * (non-PHPdoc)
     * @see BCEFieldDescriptorInterface::postSave()
     */
    public function postSave($bean, $beanId, $postValues) {

        $fullPath = call_user_func(array($bean, $this->fullPathGetter));

        $this->fileUploaderWidget->setName($this->getFieldName());

        // Retrieve post to check if the user deletes a file

        // Let's manage removes.
        // First, let's get the name of the remove field.
        $deleteName = $this->fileUploaderWidget->getDeleteName();

        if($postValues != null) {
            $removes = isset($postValues[$deleteName]) ? $postValues[$deleteName] : null;
        } else {
            $removes = get($deleteName);
        }
        if($removes) {

           foreach ($removes as $fileMd5) {
                if (md5($fullPath) == $fileMd5) {
                    $result = unlink($fullPath);
                    if (!$result) {
                        throw new BCEException("Unable to delete file ".$fullPath);
                    }
                    call_user_func(array($bean, $this->fileNameSetter), null);
                }
            }

        }

        foreach ($this->fileUploaderWidget->getFiles($postValues[$this->getFieldName()]) as $file) {
            call_user_func(array($bean, $this->fileNameSetter), $file->getFileName());
            $fullPath2 = call_user_func(array($bean, $this->fullPathGetter));

            // Let's check that the file has not been deleted just after being uploaded.
            if($removes) {
                $abort = false;

                foreach ($removes as $fileMd5) {
                    if ($fileMd5 == md5($fullPath2)) {
                        $file->delete();
                        call_user_func(array($bean, $this->fileNameSetter), null);
                        $abort = true;
                        break;
                    }
                }
                if ($abort) {
                    continue;
                }
            }

            //delete the file if it already exists and allowOverride property is set to true
            if($this->allowOverride && $file->fileExists($fullPath2)){
                unlink($fullPath2);
            }
            $file->moveAndRename(dirname($fullPath2), basename($fullPath2));
        }
    }

    /**
     * Simply calls the setter of the descriptor's related field into the bean
     * @param mixed $mainBean
     * @param mixed $value
     */
    public function setValue($mainBean, $value) {
        call_user_func(array($mainBean, $this->fileNameSetter), $value);
    }

    public function getValue($mainBean){
        if ($mainBean == null){
            $fieldValue = null;
        }else{
            $fieldValue = call_user_func(array($mainBean, $this->fullPathGetter));
        }
        return $fieldValue;
    }

    /**
     * Returns the bean's value after loading the descriptor
     */
    public function getFieldValue() {
        return $this->value;
    }

    /**
     * Returns the label of the field
     */
    public function getFieldLabel() {
        return $this->label;
    }

    /**
     * @param boolean $allowOverride
     */
    public function setAllowOverride($allowOverride)
    {
        $this->allowOverride = $allowOverride;
    }

    /**
     * Returns the instance of FileUploaderWidget
     */
    public function getFileUploaderWidget() {
        return $this->fileUploaderWidget;
    }

}