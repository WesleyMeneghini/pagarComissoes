<?php

require_once "comissoes.php";

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <!--Import Google Icon Font-->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!--Import materialize.css-->

    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">



    <!--Let browser know website is optimized for mobile-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Comissoes</title>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var elem = document.querySelector('.collapsible.expandable');
            var instance = M.Collapsible.init(elem, {
                accordion: false
            });
        });

        // Or with jQuery

        $(document).ready(function() {
            $('.collapsible').collapsible();
        });
    </script>


</head>

<body>
    <div class="container">

        <form action="" method="get">
            <div class="row">
                <div class="input-field col s6 ">

                    <select class="browser-default" name="operadora">
                        <option value="0" selected>Todas as Operadoras</option>
                        <?php
                        $sql = "SELECT * FROM tbl_operadora;";
                        $select = mysqli_query($conect, $sql) or die(mysqli_error($conect));
                        while ($rs_operadora = mysqli_fetch_array($select)) {
                            if ($_GET['operadora'] == $rs_operadora['id']) {
                                echo "<option value='" . $rs_operadora['id'] . "' selected>" . $rs_operadora['titulo'] . "</option>";
                            } else {
                                echo "<option value='" . $rs_operadora['id'] . "'>" . $rs_operadora['titulo'] . "</option>";
                            }
                        }

                        ?>

                    </select>
                </div>
                <div class="input-field col s3 ">
                    <?php
                    if (isset($_GET['data_inicial'])) {
                        $dataInicial = $_GET['data_inicial'];
                        echo "<input type='date' name='data_inicial' value='$dataInicial' required />";
                    } else {
                        echo '<input type="date" name="data_inicial" required />';
                    }
                    ?>
                </div>
                <div class="input-field col s3 ">
                    <?php
                    if (isset($_GET['data_final'])) {
                        $dataFinal = $_GET['data_final'];
                        echo "<input type='date' name='data_final' value='$dataFinal' />";
                    } else {
                        echo '<input type="date" name="data_final" />';
                    }
                    ?>
                </div>

            </div>
            <div class="row">
                <div class="input-field col s6 ">
                    <button class="btn waves-effect waves-light" type="submit" name="action" value="PESQUISAR">
                        PESQUISAR
                        <i class="material-icons right">send</i>
                    </button>
                    <button id="btn_limpar" class="btn waves-effect waves-light red darken-1" type="button">
                        LIMPAR
                        <i class="material-icons right ">clear</i>
                    </button>
                    <script>
                        const $btnLimpar = document.getElementById("btn_limpar");
                        const redirecionar = () => window.location.href = "front.php";
                        $btnLimpar.addEventListener('click', () => redirecionar());
                    </script>
                </div>

            </div>
        </form>


    </div>
    <div class="container">
        <?php
        if (isset($_GET['operadora']) && isset($_GET['data_inicial'])) {
            $idOperadora = $_GET['operadora'];
            $dataInicial = $_GET['data_inicial'];
            if ($idOperadora == "" || $idOperadora == null || $idOperadora == 0) {
                $sql = "select * FROM busca_comissoes where  data_inicial >= '$dataInicial';";
            } else {
                $sql = "select * FROM busca_comissoes where id_operadora = '$idOperadora' and data_inicial >= '$dataInicial';";
            }

            // echo $sql;
            $select_comissoes = mysqli_query($conect, $sql);
            while ($rs_comissoes = mysqli_fetch_array($select_comissoes)) {
                array_push($tblComissoes, $rs_comissoes);
            }
            $comissoes = agruparComissoes($tblComissoes);
            $teste = processo($comissoes);

            $count = 0;
            // var_dump($teste);
            echo '<ul class="collapsible popout expandable">';

            $operadora = "";
            foreach ($teste[0] as $te) {
                // echo "<p>".json_encode($te)."</p>";
                if ($operadora == "" || $operadora != $te['operadora']) {
                    $operadora = $te['operadora'];
                    echo "<h5>" . $te['operadora'] . "</h5>";
                }

                echo '
            
            <li key="' . $te['txt_id_finalizado'] . '" class="active">
                <div class="collapsible-header">
                ' . $te['descricao'] . '
                <span class="new badge"></span>
                </div>
                <div class="collapsible-body">
                    <ul>
                        <li><strong>Numero Apólice: </strong> ' . $te['n_apolice'] . '</li>
                        <li><strong>Porcentagem: </strong> ' . $te['porcentagem'] . '%</li>
                        <li><strong>Parcela: </strong> ' . $te['txt_parcela'] . '</li>
                        <li><strong>Valor: </strong> R$ ' . number_format($te['valor_calc'], 2, ',', '.') . '</li>
                    </ul>
                </div>
            </li>
            
            
            ';
            }
            echo '</ul>';
        }
        ?>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>


</body>

</html>