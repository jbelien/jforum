<?php
// Affichage des erreurs
error_reporting(E_ALL|E_STRICT); ini_set('display_errors', 1);
// Décalage horaire
date_default_timezone_set('Europe/Brussels');

// Mise en place des répertoires et chargement des classes
set_include_path('.'
            . PATH_SEPARATOR . '../library'
            . PATH_SEPARATOR . '../application/models/'
            . PATH_SEPARATOR . get_include_path());

require('Zend/Loader.php'); Zend_Loader::registerAutoload();

// Lecture du fichier de configuration
$config = new Zend_Config_Ini('../application/config.ini');

// Contrôleurs
$frontController = Zend_Controller_Front::getInstance();
$frontController->setControllerDirectory('../application/controllers');

// Layouts
$layoutOpt['layoutPath'] = '../application/layouts';
Zend_Layout::startMvc($layoutOpt);

// Connexion Base De Données
$dbAdapter = Zend_Db::factory($config->database);
Zend_Db_Table::setDefaultAdapter($dbAdapter);

// Adapteur pour Authentification
$authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);
$authAdapter->setTableName('jforum_user')
            ->setIdentityColumn('login')
            ->setCredentialColumn('password')
            ->setCredentialTreatment('MD5(?)');

// Permissions
$acl = new Zend_Acl();

$acl->addRole(new Zend_Acl_Role('guest'))
    ->addRole(new Zend_Acl_Role('user'))
    ->addRole(new Zend_Acl_Role('admin'));

$acl->add(new Zend_Acl_Resource('index'))                   // pages "index"
    ->add(new Zend_Acl_Resource('user'))                    // pages "user"
    ->add(new Zend_Acl_Resource('user_list'), 'user')       // page "user/list"
    ->add(new Zend_Acl_Resource('user_profile'), 'user');   // page "user/profile"

$acl->allow('admin');                                       // le rôle "admin" a tous les droits

$acl->allow('user', array('index', 'user'));                // le rôle "user" peut accéder aux pages "index" et "user"

$acl->allow('guest', array('index', 'user'));               // le rôle "guest" a pas accès aux pages "index" et "user"
$acl->deny('guest', array('user_list', 'user_profile'));    // sauf "user/list" et "user/profile"

// Stockage dans le registre
Zend_Registry::set('dbAdapter'  , $dbAdapter);
Zend_Registry::set('authAdapter', $authAdapter);
Zend_Registry::set('acl'        , $acl);
Zend_Registry::set('name'       , $config->main->name);
Zend_Registry::set('debug'      , $config->main->debug);

// C'est parti !
$frontController->dispatch();

