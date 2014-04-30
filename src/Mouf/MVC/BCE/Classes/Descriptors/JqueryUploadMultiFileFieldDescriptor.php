<?php
namespace Mouf\MVC\BCE\Classes\Descriptors;

use Mouf\Database\DAOInterface;

use Mouf\Html\Widgets\FileUploaderWidget\SimpleFileUploaderWidget;

use Mouf\MVC\BCE\BCEForm;
use Mouf\Html\Widgets\JqueryFileUpload\JqueryFileUploadWidget;
use Mouf\MVC\BCE\FileBeanInterface;
use Mouf\MVC\BCE\Classes\BCEException;

/**
 * This class is used to manage the upload of multiple files in a bean.
 * 
 * Note: the use of this field descriptor assumes:
 * 
 * - that you have a "files" tables that points to your main bean via a foreign key.
 * - that this "files" table contains a column that stores the name of the file.
 * - that the DAO on this table implements the FileDaoInterface
 * - that the Bean on this table implements the FileBeanInterface
 * 
 */
class JqueryUploadMultiFileFieldDescriptor extends FieldDescriptor {

	/**
	 * The DAO pointing to the table that contains the list of files associated with the main bean.
	 * 
	 * @var FileDaoInterface
	 */
	protected $fileDao;
	
	/**
	 *
	 * @var JqueryFileUploadWidget
	 */
	protected $fileUploaderWidget;
	
	/**
	 * 
	 * @param FileDaoInterface $fileDao The DAO pointing to the table that contains the list of files associated with the main bean.
	 * @param JqueryFileUploadWidget $fileUploaderWidget The widget that will display the file uploader
	 */
	public function __construct(FileDaoInterface $fileDao, JqueryFileUploadWidget $fileUploaderWidget) {
		$this->fileDao = $fileDao;
		$this->fileUploaderWidget = $fileUploaderWidget;
	}
	
	/**
	 * The value of the field once the FiedDescriptor has been loaded
	 * @var array<FileBeanInterface>
	 */
	protected $values;

	/**
	 * Load main bean's values and available ones
	 *
	 * @see BCEFieldDescriptorInterface::load()
	 */
	public function load($bean, $mainBeanId = null, &$form = null){
		$this->values = $this->getValue($bean);
	
		$descriptorInstance = new FieldDescriptorInstance($this, $form, $mainBeanId);
		
		$values = array();
		if($this->values) {
			foreach ($this->values as $bean){
				/* @var $bean FileBeanInterface */
				$values[] = $bean->getFullPath();
			}
		}
		
		// The value property of descriptor instance contains the full path to the list of files.
		$descriptorInstance->value = $values;
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
	public function preSave($post, BCEForm &$form, $bean, $isNew) {
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see BCEFieldDescriptorInterface::postSave()
	 */
	public function postSave($bean, $beanId, $postValues) {
		
		$this->values = $this->getValue($bean);
		
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
			
			foreach ($this->values as $bean) {
				// Files available in default value
				foreach ($removes as $fileMd5) {
					if (md5($bean->getFullPath()) == $fileMd5) {
						$result = unlink($bean->getFullPath());
						if (!$result) {
							throw new BCEException("Unable to delete file ".$result);
						}
						$this->fileDao->delete($bean);
					}
				}
			}
		}
		
		
		foreach ($this->fileUploaderWidget->getFiles($postValues[$this->getFieldName()]) as $file) {
			// Let's check that the file has not been deleted just after being uploaded.
			if($removes) {
				$abort = false;
				foreach ($removes as $fileMd5) {
					if ($fileMd5 == md5($file->getFileName())) {
						$file->delete();
						$abort = true;
						break;
					}
				}
				if ($abort) {
					continue;
				}
			}
			
			$fileBean = $this->fileDao->create();
			/* @var $fileBean FileBeanInterface */
			$fileBean->setFileName($file->getFileName());
			$fileBean->setMainBean($bean);
			$this->fileDao->save($fileBean);
			$destination = $fileBean->getFullPath();
			$file->moveAndRename(dirname($destination), basename($destination));
		}
	} 
	
	/**
	 * Simply calls the setter of the descriptor's related field into the bean
	 * @param mixed $mainBean
	 * @param mixed $value
	 */
	public function setValue($mainBean, $value) {
		throw new \Exception("JQueryFileUploadMulti: cannot understand why this is needed. Needs investigation.");
		//call_user_func(array($mainBean, $this->setter), $value);
	}
	
	public function getValue($mainBean){
		if ($mainBean == null){
			$fieldValue = array();
		}else{
			$fieldValue = $this->fileDao->findFiles($mainBean);
		}
		return $fieldValue;
	}
	
	/**
	 * Returns the label of the field
	 */
	public function getFieldLabel() {
		return $this->label;
	}

	/**
	 * Returns the instance of jQueryFileUploadWidget
	 */
	public function getFileUploadWidget() {
		return $this->fileUploaderWidget;
	}

}