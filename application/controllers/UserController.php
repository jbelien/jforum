<?php
// Classe Contrôleur User
// URL : http://jforum.localhost/user
//
// MANUAL - Zend_Controller        : http://framework.zend.com/manual/fr/zend.controller.html
// API    - Zend_Controller_Action : http://framework.zend.com/apidoc/core/Zend_Controller/Zend_Controller_Action.html

class UserController extends Zend_Controller_Action
{
    // Fonction appelée avant toute autre opération
    function init()
    {
        // Recupère le Contrôleur et l'Action demandés
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $this->view->ctrl = $request->getControllerName();
        $this->view->actn = $request->getActionName();

        // Recupère les données de l'utilsateur connecté
        $this->view->auth = Zend_Auth::getInstance()->getIdentity();

        // Définit le rôle (guest | user/admin) en fonction de la connexion
        if (!Zend_Auth::getInstance()->hasIdentity()) $role = new Zend_Acl_Role('guest');
        else                                          $role = $this->view->auth->role;

        // Vérifie si l'accès est autorisé
        $acl = Zend_Registry::get('acl');
             if ($acl->has($this->view->ctrl . '_' . $this->view->actn)) $access = $acl->isAllowed($role, $this->view->ctrl . '_' . $this->view->actn);
        else if ($acl->has($this->view->ctrl))                           $access = $acl->isAllowed($role, $this->view->ctrl);
        else                                                             $access = FALSE;

        // Si l'accès est refusé : redirection vers ErrorController
        if (!$access) throw new Zend_Acl_Exception();
    }

    // Action "index" (par défaut) - URL : http://jforum.localhost/user || http://jforum.localhost/user/index
    public function indexAction()
    {
        // Redirection vers la liste des utilisateurs
        $this->_redirect('/user/list/');
    }

    // Action "profile" - URL : http://jforum.localhost/user/profile
    public function profileAction()
    {
        // Titre de la page (envoyé à la vue)
        $this->view->title = 'Profil';

        // Récupération des paramètres
        $params = $this->_request->getParams();
        // Affichage d'un profil - URL : http://jforum.localhost/user/profile/see/#
        if (isset($params['see'])) {
            // Récupération du "user" et envoi à la vue
            $idMember = intval($params['see']);
            $member = new User(); $this->view->member = $member->findById($idMember);
            // Récupération du "rank" du "user" et envoi à la vue
            $this->view->rank = User::getRank($idMember);
            // Récupération des "groups" du "user" et envoi à la vue
            $this->view->groups = User::getGroups($idMember);

            // Affichage de la vue "profile-see.phtml"
            $this->_helper->viewRenderer->setRender('profile-see');
        }
        // Edition d'un profil - URL : http://jforum.localhost/user/profile/edit/#
        else if (isset($params['edit'])) {
            // Récupération du "user" et envoi à la vue
            $idMember = intval($params['edit']);
            $member = new User(); $this->view->member = $member->findById($idMember);

            // Un "user" peut uniquement éditer son profil, si autre profil redirection vers l'affichage de ce profil
            if ($this->view->auth->id != $idMember) $this->_redirect('/user/profile/see/' . $idMember);

            // Si le formulaire a été soumis
            if ($this->_request->isPost()) {
                $errors = array(); $data = array();

                // Filtres : on enlève les tags HTML <...> & on convertit les caractères spéciaux
                $filter = new Zend_Filter();
                $filter->addFilter(new Zend_Filter_Alnum())
                       ->addFilter(new Zend_Filter_StripTags())
                       ->addFilter(new Zend_Filter_HtmlEntities(ENT_COMPAT, 'UTF-8'));

                // Vérifie que la chaîne n'est pas vide
                $valNotEmpty = new Zend_Validate_NotEmpty();
                $valNotEmpty->setMessage('La chaîne est vide.', Zend_Validate_NotEmpty::IS_EMPTY);

                // Vérifie que la chaîne est alphabétique
                $valAlpha = new Zend_Validate_Alpha();
                $valAlpha->setMessage('La chaîne \'%value%\' ne peut contenir que des caractères alphabétiques.', Zend_Validate_Alpha::NOT_ALPHA)
                         ->setMessage('La chaîne est vide.', Zend_Validate_Alpha::STRING_EMPTY);

                // Vérifie que la chaîne est bien une adresse e-mail valide
                $valEmail = new Zend_Validate_EmailAddress();
                $valEmail->setMessage('Adresse e-mail \'%value%\' invalide.', Zend_Validate_EmailAddress::INVALID)
                         ->setMessage('Hôte de l\'adresse e-mail \'%value%\' invalide.', Zend_Validate_EmailAddress::INVALID_HOSTNAME);

                // on récupère la variable 'pseudo' passée par la méthode POST, on applique le filtre, on enlève les espaces aux extrémités et
                // on met en majuscule la 1ère lettre
                $login = $filter->filter($this->_request->getPost('login'));
                $login = ucfirst(trim($login));

                // on récupère la variable 'email' passée par la méthode POST, on applique le filtre et on enlève les espaces aux extrémités
                $email = $this->_request->getPost('email');
                $email = trim($email);

                // on récupère les variables 'motdepasse' passées par la méthode POST
                $password  = md5($this->_request->getPost('password'));
                $password1 =     $this->_request->getPost('password1');
                $password2 =     $this->_request->getPost('password2');

                // on vérifie que les diverses variables passent bien les divers validateurs
                if (!$valAlpha->isValid($login))                                   { $messages = $valAlpha->getMessages();    $errors['login'    ] = current($messages); }
                if (!$valEmail->isValid($email))                                   { $messages = $valEmail->getMessages();    $errors['email'    ] = current($messages); }
                if ($valNotEmpty->isValid($password1) && $password1 != $password2) {                                          $errors['password2'] = 'La confirmation ne concorde pas.'; }

                // on vérifie que le mot de passe est bon
                if ($password != $this->view->member->password)                    {                                          $errors['password'] = 'Mot de passe invalide.'; }

                // instanciation d'un objet 'Membre'
                $user = new User();

                // s'il n'y a pas eu d'erreur on met à jour le "user"
                if (empty($errors)) {
                    $z = new Zend_Date();
                    $data = array(
                        'login'    => $login,
                        'email'    => $email,
                    );
                    if ($valNotEmpty->isValid($password1)) $data['password'] = md5($password1);
                    $user->update($data, "`id` = " . $idMember);

                    // Redirection vers l'affichage du profil
                    $this->_redirect('/user/profile/see/' . $idMember);
                }

                $this->view->errors = $errors;
                $this->view->login  = $login;
                $this->view->email  = $email;
            }

            // Affichage de la vue "profile-edit.phtml"
            $this->_helper->viewRenderer->setRender('profile-edit');
        }
        // Suppression d'un profil - URL : http://jforum.localhost/user/profile/delete/#
        else if (isset($params['delete'])) {
            // Récupération du "user" et envoi à la vue
            $idMember = intval($params['delete']);
            $member = new User(); $this->view->member = $member->findById($idMember);

            // Un "user" peut uniquement supprimer son profil, si autre profil redirection vers l'affichage de ce profil
            if ($this->view->auth->id != $idMember) $this->_redirect('/user/profile/see/' . $idMember);

            $errors = array();

            // Si le formulaire a été soumis
            if ($this->_request->isPost()) {
                $password = md5($this->_request->getPost('password'));

                // on vérifie que le mot de passe est bon
                if ($password != $this->view->member->password) { $errors['password'] = 'Mot de passe invalide.'; }

                // s'il n'y a pas eu d'erreur on met à jour le "user"
                if (empty($errors)) {
                    $u = new User();
                    $u->delete("`id` = " . $idMember);

                    // Redirection vers "logout"
                    $this->_redirect('/user/logout');
                }
            }

            $this->view->errors = $errors;

            // Affichage de la vue "profile-delete.phtml"
            $this->_helper->viewRenderer->setRender('profile-delete');
        }
        // Autre action
        else {
            // Redirection vers la liste des membres si pas d'utilisateur connecté
            if (!$this->view->auth) $this->_redirect('/user/list/');
            // Sinon redirection vers affichage du profil
            else                    $this->_redirect('/user/profile/see/' . $this->view->auth->id);
        }
    }

    // Action "list" - URL : http://jforum.localhost/user/list
    public function listAction()
    {
        // Titre de la page (envoyé à la vue)
        $this->view->title = 'Liste des membres';

        // Récupération de la liste des membres et envoi à la vue
        $users = new User();
        $this->view->membres = $users->showList();

    }

    // Action "register" - URL : http://jforum.localhost/user/register
    public function registerAction()
    {
        // Titre de la page (envoyé à la vue)
        $this->view->title = 'Inscription';

        // Si le formulaire a été soumis
        if ($this->_request->isPost()) {
            $errors = array(); $data = array();

            // Filtres : on enlève les tags HTML <...> & on convertit les caractères spéciaux
            $filter = new Zend_Filter();
            $filter->addFilter(new Zend_Filter_StripTags())
                   ->addFilter(new Zend_Filter_HtmlEntities(ENT_COMPAT, 'UTF-8'));

            // Vérifie que la chaîne n'est pas vide
            $valNotEmpty = new Zend_Validate_NotEmpty();
            $valNotEmpty->setMessage('La chaîne est vide.', Zend_Validate_NotEmpty::IS_EMPTY);

            // Vérifie que la chaîne est alphabétique
            $valAlpha = new Zend_Validate_Alpha();
            $valAlpha->setMessage('La chaîne \'%value%\' ne peut contenir que des caractères alphabétiques.', Zend_Validate_Alpha::NOT_ALPHA)
                     ->setMessage('La chaîne est vide.', Zend_Validate_Alpha::STRING_EMPTY);

            // Vérifie que la chaîne est bien une adresse e-mail valide
            $valEmail = new Zend_Validate_EmailAddress();
            $valEmail->setMessage('Adresse e-mail \'%value%\' invalide.', Zend_Validate_EmailAddress::INVALID)
                     ->setMessage('Hôte de l\'adresse e-mail \'%value%\' invalide.', Zend_Validate_EmailAddress::INVALID_HOSTNAME);

            // on récupère la variable 'pseudo' passée par la méthode POST, on applique le filtre, on enlève les espaces aux extrémités et
            // on met en majuscule la 1ère lettre
            $login = $filter->filter($this->_request->getPost('login'));
            $login = ucfirst(trim($login));

            // on récupère la variable 'email' passée par la méthode POST, on applique le filtre et on enlève les espaces aux extrémités
            $email = $this->_request->getPost('email');
            $email = trim($email);

            // on récupère les variables 'motdepasse' passées par la méthode POST
            $password1 = $this->_request->getPost('password1');
            $password2 = $this->_request->getPost('password2');

            // on vérifie que les diverses variables passent bien les divers validateurs
            if (!$valAlpha->isValid($login))        { $messages = $valAlpha->getMessages();    $errors['login'    ] = current($messages); }
            if (!$valEmail->isValid($email))        { $messages = $valEmail->getMessages();    $errors['email'    ] = current($messages); }
            if (!$valNotEmpty->isValid($password1)) { $messages = $valNotEmpty->getMessages(); $errors['password1'] = current($messages); }
            if (!$valNotEmpty->isValid($password2)) { $messages = $valNotEmpty->getMessages(); $errors['password1'] = current($messages); }
            if ($password1 != $password2)           {                                          $errors['password2'] = 'La confirmation ne concorde pas.'; }

            // instanciation d'un objet 'Membre'
            $user = new User();

            // on vérifie que le pseudo et l'adresse e-mail ne sont pas déjà utilisé
            if ($user->findByLogin($login)) { $errors['login'] = 'Ce pseudo est déjà utilisé.'; }
            if ($user->findByEmail($email)) { $errors['email'] = 'Cette adresse e-mail est déjà utilisée.'; }

            // s'il n'y a pas eu d'erreur on crée le membre dans la table 'membre'
            if (empty($errors)) {
                $z = new Zend_Date();
                $data = array(
                    'login'    => $login,
                    'email'    => $email,
                    'password' => md5($password1),
                    'datetime' => $z->toString("Y-M-d H:m:s")
                );
                //$user->insert($data);

                // on fait l'authentification
                $authAdapter = Zend_Registry::get('authAdapter');
                $authAdapter->setIdentity($login);
                $authAdapter->setCredential($password1);
                $authAdapter->authenticate();

                $data = $authAdapter->getResultRowObject(null, 'password');
                $data->role = new Zend_Acl_Role('user');
                Zend_Auth::getInstance()->getStorage()->write($data);

                // Redirection vers l'"index"
                $this->_redirect('/');
            }

            $this->view->errors = $errors;
            $this->view->login  = $login;
            $this->view->email  = $email;
        }
    }

    // Action "login" - URL : http://jforum.localhost/user/login
    public function loginAction()
    {
        // Titre de la page (envoyé à la vue)
        $this->view->title = 'Connexion';

        // Si le formulaire a été soumis
        if ($this->_request->isPost()) {
            // Filtres : on enlève les tags HTML <...> & on convertit les caractères spéciaux
            $filter = new Zend_Filter();
            $filter->addFilter(new Zend_Filter_StripTags())
                   ->addFilter(new Zend_Filter_HtmlEntities(ENT_COMPAT, 'UTF-8'));

            // Vérifie que la chaîne est alphabétique
            $valAlpha = new Zend_Validate_Alpha();
            $valAlpha->setMessage('La chaîne \'%value%\' ne peut contenir que des caractères alphabétiques.', Zend_Validate_Alpha::NOT_ALPHA)
                     ->setMessage('La chaîne est vide.', Zend_Validate_Alpha::STRING_EMPTY);

            // on récupère la variable 'pseudo' passée par la méthode POST, on applique le filtre, on enlève les espaces aux extrémités et
            // on met en majuscule la 1ère lettre
            $login = $filter->filter($this->_request->getPost('login'));
            $login = ucfirst(trim($login));
            if (!$valAlpha->isValid($login)) { $messages = $valAlpha->getMessages(); $this->view->error = current($messages); }

            // on récupère la variable 'motdepasse' passée par la méthode POST
            $password = $this->_request->getPost('password');

            if (!isset($this->view->error)) {
                // on fait l'authentification
                $authAdapter = Zend_Registry::get('authAdapter');
                $authAdapter->setIdentity($login);
                $authAdapter->setCredential($password);
                $result = $authAdapter->authenticate();

                // si l'authentification échoue
                if (!$result->isValid()) {
                    Zend_Auth::getInstance()->clearIdentity();

                    switch ($result->getCode()) {
                        case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                            $this->view->error = 'L\'identifiant n\'existe pas.';
                            break;

                        case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                            $this->view->error = 'Le mot de passe est invalide.';
                            break;

                        default:
                            $this->view->error = 'Connexion échouée.';
                            break;
                    }
                }
                else {
                    // on récupère les données du "user" (excepté le "password") et on les stocke
                    $data = $authAdapter->getResultRowObject(null, 'password');

                    // définition du rôle en fonction du groupe
                    $role = new Zend_Acl_Role('user');
                    $groups = User::getGroups($data->id);
                    foreach ($groups as $g) { if ($g['name'] == "Administrateur") { $role = new Zend_Acl_Role('admin'); break; } }
                    $data->role = $role;

                    // stockage des données
                    Zend_Auth::getInstance()->getStorage()->write($data);

                    // Redirection vers l'"index"
                    $this->_redirect('/');
                }
            }
        }
    }

    // Action "logout" - URL : http://jforum.localhost/user/logout
    public function logoutAction()
    {
        // Suppression du singleton d'authentification
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();

        // Redirection vers l'"index"
        $this->_redirect('/');
    }
}