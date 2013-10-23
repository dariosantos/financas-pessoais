<?php

require_once __DIR__ . '/DBBase.php';

class DB extends DBBase {

// Custom methods

    static public function showEntity($id_entity) {
        $dataToUpdate = array(
            'hidden' => 0
        );
        $where = " id_entity = '" . $id_entity . "'";
        return self::update('entity', $dataToUpdate, $where);
    }

    static public function hideEntity($id_entity) {
        $dataToUpdate = array(
            'hidden' => 1
        );
        $where = " id_entity = '" . $id_entity . "'";
        return self::update('entity', $dataToUpdate, $where);
    }

    static public function insertEntity($dataToInsert) {
        return self::insert('entity', $dataToInsert);
    }

    static public function updateEntity($dataToUpdate, $whereClause) {

        return self::update('entity', $dataToUpdate, $whereClause);
    }

    static public function removeEntity($id_entity) {

        if ($id_entity == null || !is_numeric($id_entity)) {
            self::$last_error_message = '$id_entity (' . $id_entity . ') must be an number';
            return false;
        }

        $whereClause = " id_entity = '$id_entity' ";

        return self::delete('entity', $whereClause);
    }

    static public function getInfoEntity($id_entity) {

        if ($id_entity == null || !is_numeric($id_entity)) {
            self::$last_error_message = '$id_entity (' . $id_entity . ') must be an number';
            return false;
        }

        $query = "SELECT * FROM entity WHERE id_entity = '$id_entity' ";

        $resource = self::execute($query);

        $entity_info = self::toArray($resource);

        self::free($resource);

        return $entity_info;
    }

    static public function getListEntity($orderBy = ' last_update DESC ', $showHidden = false) {

        if (!is_string($orderBy) || trim($orderBy) == '') {
            self::$last_error_message = '$orderBy (' . $orderBy . ') must be a not empty string';
            return false;
        }

        if (!is_bool($showHidden)) {
            self::$last_error_message = '$showHidden (' . $showHidden . ') must be a boolean';
            return false;
        }

        $query = 'SELECT * FROM entity WHERE 1 ';

        if ($showHidden == false) {
            $query .= ' AND IFNULL(hidden, 0) <> 1 ';
        }
        $query .= " ORDER BY $orderBy ";

        $resource = self::execute($query);

        return $resource;
    }

    static public function validateDataEntity($dataToValidate) {

        $length = strlen($dataToValidate['title']);
        if ($length < 1 || $length > 255) {
            self::$last_error_message = 'A receita tem de ter Título. Tamanho de 1 a 255 caracteres.';
            return false;
        }

        $length = strlen($dataToValidate['ingredients']);
        if ($length < 1) {
            self::$last_error_message = 'A receita tem de ter Ingredientes.';
            return false;
        }

        $length = strlen($dataToValidate['preparation']);
        if ($length < 1) {
            self::$last_error_message = 'A receita tem de ter Preparação.';
            return false;
        }

        if (!isset($dataToValidate['image']) || empty($dataToValidate['image']) || !file_exists($dataToValidate['image'])) {
            self::$last_error_message = 'A receita tem de ter uma imagem.';
            return false;
        }

        $array = array('bebidas', 'entradas', 'carne', 'peixe', 'sobremesas');
        if (!in_array(strtolower($dataToValidate['category']), $array)) {
            self::$last_error_message = 'A receita tem de ter uma das seguintes categorias: "bebidas", "entradas", "carne", "peixe", "sobremesas".';
            return false;
        }

        return true;
    }

}

?>
