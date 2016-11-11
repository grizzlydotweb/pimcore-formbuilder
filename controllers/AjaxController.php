<?phpuse Formbuilder\Controller\Action;use Formbuilder\Model\Form;use Formbuilder\Lib\Frontend as FormFrontEnd;use Formbuilder\Lib\Mailer;use Formbuilder\Lib\FileHandler;use Formbuilder\Lib\PackageHandler;use Formbuilder\Tool\Session;class Formbuilder_AjaxController extends Action {    /**     * @var FileHandler     */    private $fileHandler = NULL;    public function init()    {        parent::init();        $this->fileHandler = new FileHandler();    }    public function addFromUploadAction()    {        $this->setPlainHeader();        $method = $_SERVER['REQUEST_METHOD'];        $formId = (int) $this->getParam('_formId');        if ($method === 'POST')        {            $result = $this->fileHandler->handleUpload();            $result['uploadName'] = $this->fileHandler->getRealFileName();            if( isset( $result['success']) && $result['success'] === TRUE )            {                //add uuid to session to find it again later!                Session::addToTmpSession($formId, $result['uuid'], $result['uploadName']);            }            echo json_encode( $result );        }        // for delete file requests        else if ($method === 'DELETE')        {            $this->deleteFromUploadAction();        }        else        {            $this->getResponse()->setHttpResponseCode(405);            exit;        }    }    public function deleteFromUploadAction()    {        $this->setPlainHeader();        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);        $tokens = explode('/', $url);        $uuid = $tokens[sizeof($tokens)-1];        $formId = (int) $this->getParam('_formId');        //remove tmp element from session!        Session::removeFromTmpSession($formId, $uuid);        $result = $this->fileHandler->handleDelete( $uuid );        echo json_encode( $result );    }    public function chunkDoneAction()    {        $this->setPlainHeader();        $formId = (int) $this->getParam('_formId');        $result = $this->fileHandler->combineChunks();        // To return a name used for uploaded file you can use the following line.        $result['uploadName'] = $this->fileHandler->getRealFileName();        if( isset( $result['success']) && $result['success'] === TRUE )        {            //add uuid to session to find it again later!            Session::addToTmpSession($formId, $result['uuid'], $result['uploadName']);        }        echo json_encode( $result );    }    public function parseAction()    {        $formId = $this->getParam('_formId');        $locale = $this->getParam('_language');        $templateId = $this->getParam('_mailTemplate');        $valid = FALSE;        $redirect = FALSE;        $message = '';        $validationData = FALSE;        $mainList = new Form();        $formData = $mainList->getById( $formId );        if( $formData instanceof Form )        {            $frontendLib = new FormFrontEnd();            $form = $frontendLib->getForm($formData->getId(), $locale, TRUE);            $form = $frontendLib->addDefaultValuesToForm( $form, array( 'formId' => $formId, 'locale' => $locale, 'mailTemplate' => $templateId) );            $params = $frontendLib->parseFormParams( $this->getAllParams(), $form );            $formValid = TRUE;            $valid = FALSE;            if( $frontendLib->hasRecaptchaV2() )            {                $formValid = $form->isValid( $params, $frontendLib->getRecaptchaV2Key() );            }            if( $formValid === TRUE )            {                $valid = $form->isValid( $params );            }            if( $valid )            {                if( $templateId !== NULL )                {                    $data = $form->getValues();                    //set upload data!                    $packageHandler = new PackageHandler();                    $boundedFiles = Session::getFromTmpSession( $formId );                    $asset = $packageHandler->createZipAsset( $boundedFiles, $formData->getName(), $templateId );                    //remove tmp element from session!                    Session::removeFromTmpSession($formId);                    if( $asset instanceof \Pimcore\Model\Asset)                    {                        $http = 'http://';                        if (!empty($_SERVER['HTTPS']))                        {                            $http = 'https://';                        }                        $websiteUrl = $http . \Pimcore\Tool::getHostname();                        $data['attachmentFile'] = $websiteUrl . $asset->getRealFullPath();                    }                    $send = Mailer::sendForm( $templateId, array('data' => $data ) );                    if( $send === TRUE )                    {                        $return = $this->afterSend($templateId);                        $valid = $return['valid'];                        $redirect = $return['redirect'];                        $message = $valid === FALSE ? $return['message'] : $return['html'];                    }                }            }            else            {                $validationData = $form->getMessages();            }        }        $this->_helper->json(            array(                'success'           => $valid,                'message'           => $message,                'validationData'    => $validationData,                'redirect'          => $redirect            )        );    }    private function afterSend( $mailTemplateId )    {        $redirect = FALSE;        $error = FALSE;        $successMessage = '';        $statusMessage = '';        $mailTemplate = \Pimcore\Model\Document::getById( $mailTemplateId );        $afterSuccess = $mailTemplate->getProperty('mail_successfully_sent');        //get the content from a snippet        if( $afterSuccess instanceof \Pimcore\Model\Document\Snippet )        {            $params['document'] = $afterSuccess;            if( $this->view instanceof \Pimcore\View )            {                try                {                    $successMessage = $this->view->action($afterSuccess->getAction(), $afterSuccess->getController(), $afterSuccess->getModule(), $params);                }                catch(\Exception $e)                {                    $error = TRUE;                    $statusMessage = $e->getMessage();                }            }        }        //it's a redirect!        else if( $afterSuccess instanceof \Pimcore\Model\Document)        {            $redirect = TRUE;            $successMessage = $afterSuccess->getFullPath();        }        //it's just a string!        else if( is_string( $afterSuccess ) )        {            $successMessage = $afterSuccess;        }        return array(            'valid'     => $error === FALSE,            'message'   => $statusMessage,            'redirect'  => $redirect,            'html'      => $successMessage        );    }    private function setPlainHeader()    {        $this->disableViewAutoRender();        $this->getResponse()            ->setHeader('Content-type', 'text/plain')            ->setHeader('Cache-Control','no-cache');    }}