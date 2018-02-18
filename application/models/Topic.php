<?php
// Classe Modèle Topic
//
// MANUAL - Zend_Db_Table : http://framework.zend.com/manual/fr/zend.db.table.html
// API    - Zend_Db_Table : http://framework.zend.com/apidoc/core/Zend_Db/Table/Zend_Db_Table.html

class Topic extends Zend_Db_Table {
    protected $_name = 'jforum_topic'; // nom de la table
    protected $_primary = 'id';        // clé primaire

   protected $_referenceMap    = array(
     'Board' => array(
            'columns'           => array('id_board'),    // colonne de la table "topic" servant de clé externe vers la table "board"
            'refTableClass'     => 'Board',              // classe modèle "board"
            'refColumns'        => 'id'                  // clé primaire de la table "board"
        ),
     'User' => array(
            'columns'           => array('id_user'),    // colonne de la table "topic" servant de clé externe vers la table "user"
            'refTableClass'     => 'User',              // classe modèle "user"
            'refColumns'        => 'id'                 // clé primaire de la table "user"
        )
    );

    // Retourne les "topic" sur base de la "board"
    public function findByBoard($id_board)
    {
        $where = $this->getAdapter()->quoteInto('id_board = ?', $id_board);
        return $this->fetchAll($where);
    }

    // Retourne la liste des "topic" pour une "board" donnée
    static public function liste($id_board)
    {
        $res = array(); $suj = new Topic();
        $topics = $suj->fetchAll(array('id_board = ' . $id_board), array('date'));

        foreach ($topics as $s) {
            $r = array();
            $r['info'    ] = $s->toArray();
            $r['board'   ] = $s->findParentBoard()->toArray();
            if (!is_null($s->id_user)) $r['user'] = $s->findParentUser()->toArray();
            $r['messages'] = Message::liste($s->id);
            array_push($res, $r);
        }
        return $res;
    }
}
?>