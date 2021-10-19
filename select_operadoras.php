<?php

ini_set('error_reporting', E_ALL); // mesmo resultado de: error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../includes/functions.php";
require_once "../includes/connection.php";

$conect = conexaoMysql();

if (isset($_GET['id'])) {

    $operadoras = array();

    $sql = "SELECT * from tbl_operadora;";
    $select = mysqli_query($conect, $sql);

    while ($rsOperadora = mysqli_fetch_assoc($select)) {
        $operadora = [
            'id' => $rsOperadora['id'],
            'titulo' => $rsOperadora['titulo'],
            'min_vidas' => $rsOperadora['min_vidas']
        ];
        array_push($operadoras, $operadora);
    }
    echo json_encode($operadoras, JSON_UNESCAPED_UNICODE);
}
