<?php
// Classe Modèle Group
//
// MANUAL - Zend_Db_Table : http://framework.zend.com/manual/fr/zend.db.table.html
// API    - Zend_Db_Table : http://framework.zend.com/apidoc/core/Zend_Db/Table/Zend_Db_Table.html

class Group extends Zend_Db_Table {
    protected $_name = 'jforum_group';   // nom de la table
    protected $_primary = 'id';          // clé primaire

    // Retourne un "group" en fonction de son "id"
    public function findById($id)
    {
        $where = $this->getAdapter()->quoteInto('id = ?', $id);
        return $this->fetchRow($where);
    }
}
?>