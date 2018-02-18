<?php
// Classe Contrôleur d'erreur
//
// MANUAL - Zend_Controller        : http://framework.zend.com/manual/fr/zend.controller.html
// API    - Zend_Controller_Action : http://framework.zend.com/apidoc/core/Zend_Controller/Zend_Controller_Action.html
// Autre :
// * MANUAL - Zend_Controller_Plugin_ErrorHandler : http://framework.zend.com/manual/fr/zend.controller.plugins.html#zend.controller.plugins.standard.errorhandler

class ErrorController extends Zend_Controller_Action
{
    private $_exception;
    private static $errorMessage;
    private static $httpCode;

    public function preDispatch()
    {
        $this->_exception = $this->_getParam('error_handler');
        $this->_response->clearBody(); // on vide le contenu de la réponse

        switch ($this->_exception->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // erreur 404 -- contrôleur ou action non trouvé
                self::$httpCode = 404;
                self::$errorMessage = 'Page introuvable.';
            break;
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:
                switch (get_class($this->_exception->exception)) {
                    // erreur dans une vue
                    case 'Zend_View_Exception' :
                        self::$httpCode = 500;
                        self::$errorMessage = 'Erreur de traitement d\'une vue.';
                    break;
                    // erreur SQL
                    case 'Zend_Db_Exception' :
                        self::$httpCode = 500;
                        self::$errorMessage = 'Erreur de traitement dans la base de données.';
                    break;
                    // accès interdit
                    case 'Zend_Acl_Exception' :
                        self::$httpCode = 500;
                        self::$errorMessage = 'Accès interdit.';
                    break;
                    default:
                        self::$httpCode = 500;
                        self::$errorMessage = 'Erreur inconnue.';
                    break;
                }
            break;
            default:
                self::$httpCode = 500;
                self::$errorMessage = 'Erreur inconnue.';
            break;
        }

    }

    public function errorAction()
    {
        // code de reponse HTTP : http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
        $this->getResponse()->setHttpResponseCode(self::$httpCode);
        // message d'erreur
        $this->view->content = sprintf("<p>%s</p>",self::$errorMessage);
    }

    public function postDispatch()
    {
        // informations supplémentaires sur l'erreur (affichée si debug)
        $this->view->trace = sprintf('<hr>DEBUG INFOS :<br /><strong>Exception de type <em>%s</em> <u>%s</u> envoyée dans %s à la ligne %d </strong> <p>Stack Trace :<br /> %s </p>',
                                get_class($this->_exception->exception),
                                $this->_exception->exception->getMessage(),
                                $this->_exception->exception->getFile(),
                                $this->_exception->exception->getLine(),
                                $this->_exception->exception->getTraceAsString()
                               );
    }
}