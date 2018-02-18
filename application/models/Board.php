<?php
// Classe Modèle Board
//
// MANUAL - Zend_Db_Table : http://framework.zend.com/manual/fr/zend.db.table.html
// API    - Zend_Db_Table : http://framework.zend.com/apidoc/core/Zend_Db/Table/Zend_Db_Table.html

class Board extends Zend_Db_Table {
    protected $_name = 'jforum_board';    // nom de la table
    protected $_primary = 'id';           // clé primaire

    protected $_referenceMap    = array(
     'Category' => array(
            'columns'           => array('id_category'),    // colonne de la table "board" servant de clé externe vers la table "category"
            'refTableClass'     => 'Category',              // classe modèle "category"
            'refColumns'        => 'id'                     // clé primaire de la table "category"
        ),
     'Parent' => array(
            'columns'           => array('id_parent'),    // colonne de la table "board" servant de clé vers la "board" parente
            'refTableClass'     => 'Board',               // classe modèle "board"
            'refColumns'        => 'id'                   // clé primaire de la table "board"
        )
    );

    // Retourne les "boards" parentes d'une "board" donnée par son "id"
    public function getParents($id_board) {
        $res = array(); $sec = new Board();

        $s = $sec->fetchRow('id = ' . $id_board);
        while(!is_null($s->id_parent)) {
            array_push($res, $s->findParentBoard()->toArray());
            $sec = new Board(); $s = $sec->fetchRow('id = ' . $s->id_parent);
        }

        return $res;
    }

    // Retourne la liste des "boards" pour une "catgory" donnée (et une "board" parente donnée")
    static public function liste($id_category, $id_parent = NULL) {
        $res = array(); $sec = new Board();
        if (is_null($id_parent))
            $boards = $sec->fetchAll(array('id_category = ' . $id_category, 'id_parent IS NULL'), array('id_category', 'order'));
        else
            $boards = $sec->fetchAll(array('id_category = ' . $id_category, 'id_parent = ' . $id_parent), array('id_category', 'order'));

        foreach ($boards as $s) {
            $r = array();
            $r['info'    ] = $s->toArray();
            $r['category'] = $s->findParentCategory()->toArray();
            $r['sub'     ] = Board::liste($id_category, $s->id);
            $r['topics'  ] = Topic::liste($s->id);
            if (!is_null($s->id_parent)) $r['parent'] = $s->findParentBoard()->toArray();
            array_push($res, $r);
        }
        return $res;
    }

}
?>