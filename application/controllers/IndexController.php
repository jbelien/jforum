<?php
// Classe Contrôleur Index
// URL : http://jforum.localhost/ || http://jforum.localhost/index
//
// MANUAL - Zend_Controller        : http://framework.zend.com/manual/fr/zend.controller.html
// API    - Zend_Controller_Action : http://framework.zend.com/apidoc/core/Zend_Controller/Zend_Controller_Action.html

class IndexController extends Zend_Controller_Action
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

    // Action "index" (par défaut) - URL : http://jforum.localhost/ || http://jforum.localhost/index
    public function indexAction()
    {
        // Titre de la page (envoyé à la vue)
        $this->view->title = 'Accueil';

        // Récupération des "Category" et des "Boards" et envoie à la vue
        $categories = new Category(); $cat = $categories->liste();
        foreach ($cat as &$c) $c['boards'] = Board::liste($c['id']);
        $this->view->boards = $cat;
    }

    // Action "read" - URL : http://jforum.localhost/index/read
    public function readAction()
    {
        // Récupération des paramètres
        $params = $this->_request->getParams();

        // Lecture d'une "board" - URL : http://jforum.localhost/index/read/board/#
        if (isset($params['board'])) {
            // Récupération du paramètre "board"
            $id_board = intval($params['board']);

            // Récupération de la "board" et envoi à la vue
            $board = new Board(); $s = $board->find($id_board)->current();
            $this->view->board = $s->toArray();
            // Récupération de la "category" et envoi à la vue
            $c = $s->findParentCategory();
            $this->view->categorie = $c->toArray();

            // Récupération de la liste des sous-"board" et envoi à la vue
            $this->view->sub = Board::liste($s->id_category, $s->id);
            // Récupération de la liste des "topic" et envoi à la vue
            $this->view->topics = Topic::liste($s->id);

            // Récupère les parents de la "board" courante et envoie la suite de liens à la vue
            $parents = $board->getParents($s->id);
            $this->view->soustitre  = '<a href="/index/">' . $c->name . '</a>';
            for ($i = (count($parents) - 1); $i >= 0; $i--) $this->view->soustitre .= ' &not; <a href="/index/read/board/' . $parents[$i]['id'] . '">' . $parents[$i]['name'] . '</a>';
            $this->view->soustitre .= ' &not; ' . $s->name;

            // Titre de la page (envoyé à la vue)
            $this->view->title = $s->name;

            // Affichage de la vue "read-board.phtml"
            $this->_helper->viewRenderer->setRender('read-board');
        }
        // Lecture d'un "topic" - URL : http://jforum.localhost/index/read/topic/#
        else if (isset($params['topic'])) {
            // Récupération du paramètre "topic"
            $id_topic = intval($params['topic']);

            // Récupération du "topic" et envoi à la vue
            $topic = new Topic(); $s = $topic->find($id_topic)->current();
            $this->view->topic = $s->toArray();
            // Récupération de la "board" et envoi à la vue
            $board = $s->findParentBoard();
            $this->view->board = $board->toArray();
            // Récupération de la "category" et envoi à la vue
            $category = $board->findParentCategory();
            $this->view->category = $category->toArray();

            // Liste des messages et envoi à la vue
            $this->view->messages = Message::liste($s->id);

            // Récupère les parents de la "board" courante et envoie la suite de liens à la vue
            $bboard = new Board(); $parents = $bboard->getParents($board->id);
            $this->view->soustitre  = '<a href="/index/">' . $category->name . '</a>';
            for ($i = (count($parents) - 1); $i >= 0; $i--) $this->view->soustitre .= ' &not; <a href="/index/read/board/' . $parents[$i]['id'] . '">' . $parents[$i]['name'] . '</a>';
            $this->view->soustitre .= ' &not; <a href="/index/read/board/' . $board->id . '">' . $board->name . '</a>';
            $this->view->soustitre .= ' &not; ' . $s->title;

            // Titre de la page (envoyé à la vue)
            $this->view->title = $s->title;

            // Affichage de la vue "read-topic.phtml"
            $this->_helper->viewRenderer->setRender('read-topic');
        }
    }

    // Action "post" - URL : http://jforum.localhost/index/post
    public function postAction()
    {
        // Récupération des paramètres
        $params = $this->_request->getParams();

        // Ecriture d'un nouveau "topic" dans une "board" - URL : http://jforum.localhost/index/post/board/#
        if (isset($params['board'])) {
            // Si le formulaire a été soumis
            if ($this->_request->isPost()) {
                $topic = new Topic(); $message = new Message();

                // Récupération des données de l'utilisateur
                $auth = Zend_Auth::getInstance()->getIdentity();

                // Remplissage des données du nouveau "topic"
                $dTopic = array();
                $dTopic['title'   ] = $this->_request->getPost('title');
                $dTopic['date'    ] = date('Y-m-d H:i:s');
                $dTopic['id_board'] = $params['board'];
                $dTopic['id_user' ] = $auth->id;
                // Insertion dans la base de données
                //$sid = $topic->insert($dTopic);

                // Remplissage des données du premier message de ce nouveau "topic"
                $dMessage = array();
                $dMessage['id_topic'] = $sid;
                $dMessage['title'   ] = $this->_request->getPost('title');
                $dMessage['content' ] = $this->_request->getPost('content');
                $dMessage['date'    ] = date('Y-m-d H:i:s');
                $dMessage['id_user' ] = $auth->id;
                // Insertion dans la base de données
                //$message->insert($dMessage);

                // Redirection vers la lecture de ce nouveau "topic"
                $this->_redirect('/index/read/topic/' . $sid);
            }
            else {
                // Récupération de la "board" dans lequel on veut poster un nouveau "topic" et envoi à la vue
                $sid = intval($params['board']);
                $board = new Board(); $s = $board->find($sid)->current();
                $this->view->board = $s->toArray();

                // Récupération de la "category" de cette "board" et envoi à la vue
                $categorie = new Category(); $c = $categorie->find($s->id_category)->current()->toArray();
                $this->view->categorie = $c;

                // Récupèration des parents de la "board" et envoie la suite de liens à la vue
                $parents = $board->getParents($s->id);
                $this->view->soustitre  = '<a href="/index/">' . $c['name'] . '</a>';
                for ($i = (count($parents) - 1); $i >= 0; $i--) $this->view->soustitre .= ' &not; <a href="/index/read/board/' . $parents[$i]['id'] . '">' . $parents[$i]['name'] . '</a>';
                $this->view->soustitre .= ' &not; ' . $s->name;

                // Titre de la page (envoyé à la vue)
                $this->view->title = $s->name;

                // Affichage de la vue "post-topic.phtml"
                $this->_helper->viewRenderer->setRender('post-topic');
            }
        }
        // Ecriture d'un nouveau "mesasge" dans un "topic" - URL : http://jforum.localhost/index/post/topic/#
        else if (isset($params['topic'])) {
            // Si le formulaire a été soumis
            if ($this->_request->isPost()) {
                // Récupération des données de l'utilisateur
                $auth = Zend_Auth::getInstance()->getIdentity();

                // Remplissage des données du nouveau "message"
                $message = new Message();
                $dMessage = array();
                $dMessage['id_topic'] = $params['topic'];
                $dMessage['title'   ] = $this->_request->getPost('title');
                $dMessage['content' ] = $this->_request->getPost('content');
                $dMessage['date'    ] = date('Y-m-d H:i:s');
                $dMessage['id_user' ] = $auth->id;
                // Insertion dans la base de données
                //$message->insert($dMessage);

                // Redirection vers la lecture du "topic" dans lequel se trouve le nouveau message
                $this->_redirect('/index/read/topic/' . $params['topic']);
            }
            else {
                // Récupération du "topic" dans lequel on veut poster un nouveau "message" et envoi à la vue
                $id_topic = intval($params['topic']);
                $topic = new Topic(); $s = $topic->find($id_topic)->current();
                $this->view->topic = $s->toArray();
                // Récupération de la "board" dans laquelle se trouve le "topic"
                $board = $s->findParentBoard();
                $this->view->section = $board->toArray();
                // Récupération de la "category" de cette "board"
                $categorie = $board->findParentCategory();
                $this->view->categorie = $categorie->toArray();

                // Récupèration des parents de la "board" et envoie la suite de liens à la vue
                $bboard = new Board(); $parents = $bboard->getParents($board->id);
                $this->view->soustitre  = '<a href="/index/">' . $categorie->name . '</a>';
                for ($i = (count($parents) - 1); $i >= 0; $i--) $this->view->soustitre .= ' &not; <a href="/index/read/board/' . $parents[$i]['id'] . '">' . $parents[$i]['name'] . '</a>';
                $this->view->soustitre .= ' &not; <a href="/index/read/board/' . $board->id . '">' . $board->name . '</a>';
                $this->view->soustitre .= ' &not; ' . $s->title;

                // Titre de la page (envoyé à la vue)
                $this->view->title = $s->title;

                // Affichage de la vue "post-message.phtml"
                $this->_helper->viewRenderer->setRender('post-message');
            }
        }
    }
}