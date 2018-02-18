<?php
// Classe Modèle User
//
// MANUAL - Zend_Db_Table : http://framework.zend.com/manual/fr/zend.db.table.html
// API    - Zend_Db_Table : http://framework.zend.com/apidoc/core/Zend_Db/Table/Zend_Db_Table.html

class User extends Zend_Db_Table {
    protected $_name = 'jforum_user'; // nom de la table
    protected $_primary = 'id';       // clé primaire

    protected $_referenceMap    = array(
     'Group' => array(
            'columns'           => array('id_group'),    // colonne de la table "user" servant de clé externe vers la table "group"
            'refTableClass'     => 'Group',              // classe modèle "group"
            'refColumns'        => 'id'                  // clé primaire de la table "group"
     )
    );

    // Retourne un "user" en fonction de son "id"
    public function findById($id_user)
    {
        $where = $this->getAdapter()->quoteInto('id = ?', $id_user);
        return $this->fetchRow($where);
    }

    // Retourne un "user" en fonction de son "login"
    public function findByLogin($login)
    {
        $where = $this->getAdapter()->quoteInto('login = ?', $login);
        return $this->fetchRow($where);
    }

    // Retourne un "user" en fonction de son "email"
    public function findByEmail($email)
    {
        $where = $this->getAdapter()->quoteInto('email = ?', $email);
        return $this->fetchRow($where);
    }

    // Retourne la liste des "user" (triée par défaut par le "login")
    public function showList($sort = 'login')
    {
        $list = $this->fetchAll(NULL, $sort)->toArray();
        foreach ($list as &$v) {
            $v['rank'  ] = User::getRank($v['id']);
            $v['groups'] = User::getGroups($v['id']);
        }
        return $list;
    }

    // Retourne le "rank" d'un "user" sur base de son "id"
    static public function getRank($id_user) {
        $count = Message::getCountMessageByUser($id_user);

        $gr = new Group();
        $where = $gr->getAdapter()->quoteInto('nbmsg <= ?', $count);
        $q = $gr->fetchRow($where);
        return $q->toArray();
    }

    // Retourne les "group" d'un "user" sur base de son "id"
    static public function getGroups($id_user) {
        $user = new User();
        $user = $user->findById($id_user);

        $groups = array();

        if (!is_null($user->id_group)) {
            $gr = split(':', $user->id_group);
            foreach ($gr as $g) {
                $group = new Group();
                $where = $group->getAdapter()->quoteInto('id = ?', $g);
                $q = $group->fetchRow($where);
                array_push($groups, $q->toArray());
            }
        }
        return $groups;
    }
}
?>