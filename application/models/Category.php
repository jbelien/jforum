<?php
// Classe Modèle Category
//
// MANUAL - Zend_Db_Table : http://framework.zend.com/manual/fr/zend.db.table.html
// API    - Zend_Db_Table : http://framework.zend.com/apidoc/core/Zend_Db/Table/Zend_Db_Table.html

class Category extends Zend_Db_Table {
    protected $_name = 'jforum_category';   // nom de la table
    protected $_primary = 'id';             // clé primaire

    // Retourne un tableau des "category" triées par "order"
    public function liste() {
        return $this->fetchAll(NULL, 'order')->toArray();
    }
}
?>