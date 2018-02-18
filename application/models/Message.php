<?php
// Classe Modèle Message
//
// MANUAL - Zend_Db_Table : http://framework.zend.com/manual/fr/zend.db.table.html
// API    - Zend_Db_Table : http://framework.zend.com/apidoc/core/Zend_Db/Table/Zend_Db_Table.html

class Message extends Zend_Db_Table {
    protected $_name = 'jforum_message';  // nom de la table
    protected $_primary = 'id';           // clé primaire

    // Lien avec les autres classes / tables
    protected $_referenceMap    = array(
     'Topic' => array(
            'columns'           => array('id_topic'),   // colonne de la table "message" servant de clé externe vers la table "topic"
            'refTableClass'     => 'Topic',             // classe modèle "topic"
            'refColumns'        => 'id'                 // clé primaire de la table "topic"
     ),
     'User' => array(
            'columns'           => array('id_user'),    // colonne de la table "message" servant de clé externe vers la table "user"
            'refTableClass'     => 'User',              // classe modèle "user"
            'refColumns'        => 'id'                 // clé primaire de la table "user"
     )
    );

    // Retourne les "message" sur base du "topic"
    public function findByTopic($id_topic)
    {
        $where = $this->getAdapter()->quoteInto('id_topic = ?', $id_topic);
        return $this->fetchAll($where);
    }

    // Retourne les "message" sur base du "user"
    public function findByUser($id_user)
    {
        $where = $this->getAdapter()->quoteInto('id_user = ?', $id_user);
        return $this->fetchAll($where);
    }

    // Retourne le nombre de "message" dans un "topic" donné
    static public function getCountMessageByTopic($id_topic)
    {
        return count(Message::liste($id_topic));
    }

    // Retourne le nombre de "message" dans une "board" donnée
    static public function getCountMessageByBoard($id_board)
    {
        $topics = Topic::liste($id_board);
        $res = 0;
        return $res;
    }

    // Retourne le nombre de "message" pour un "user" donné
    static public function getCountMessageByUser($id_user)
    {
        $m = new Message();
        return count($m->findByUser($id_user)->toArray());
    }

    // Retourne la liste des "message" pour un "topic" donné
    static public function liste($id_topic)
    {
        $res = array(); $msg = new Message();
        $messages = $msg->fetchAll(array('id_topic = ' . $id_topic), array('date'));

        foreach ($messages as $m) {
            $r = array();
            $r['info'  ] = $m->toArray();
            $r['topic' ] = $m->findParentTopic()->toArray();
            if (!is_null($m->id_user)) $r['user'] = $m->findParentUser()->toArray();
            array_push($res, $r);
        }
        return $res;
    }
}
?>