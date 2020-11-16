<?php

require_once "comissoes.php";

$comissoesEncontradas = array();
$comissoesPagas = array();
$comissoesNaoEncontradas = array();
$comissoesNegativas = array();

$comissoesEncontradasString = "";

if (isset($_GET['operadora']) && isset($_GET['data_inicial'])) {
    $idOperadora = $_GET['operadora'];
    $dataInicial = $_GET['data_inicial'];
    $dataFinal = $_GET['data_final'];
    if ($idOperadora == "" || $idOperadora == null || $idOperadora == 0) {
        $sql = "select * FROM busca_comissoes where  data_inicial >= '$dataInicial';";
    } else {
        $sql = "select * FROM busca_comissoes where id_operadora = '$idOperadora' and data_inicial >= '$dataInicial' and data_final <= '$dataFinal';";
    }

    // echo $sql;
    $select_comissoes = mysqli_query($conect, $sql);
    while ($rs_comissoes = mysqli_fetch_array($select_comissoes)) {
        array_push($tblComissoes, $rs_comissoes);
    }
    $comissoes = agruparComissoes($tblComissoes);
    $comissoesProcessadas = processo($comissoes);

    $count = 0;

    $comissoesEncontradas = $comissoesProcessadas[0];
    $comissoesPagas = $comissoesProcessadas[1];
    $comissoesNaoEncontradas = $comissoesProcessadas[2];
    $comissoesNegativas = $comissoesProcessadas[3];

    $comissoesEncontradasString = '<div id="test1" class="col s12"><ul class="collapsible popout expandable">';

    $operadora = "";
    foreach ($comissoesProcessadas[0] as $comissao) {
        $parcela = $comissao['txt_parcela'];
        $referencia = $comissao['operadora'];
        $dataPagamento = $comissao['dataPagamento'];
        if ($parcela < 10) {
            $parcela = "0" . $parcela;
        }

        if ($operadora == "" || $operadora != $comissao['operadora']) {
            $operadora = $comissao['operadora'];
            // $comissoesEncontradasString .= "<h5>$operadora</h5>";
        }
        if ($dataPagamento != "" && $dataPagamento != null){
            $dataPagamento = date('d/m/y', strtotime($comissao['data_pagamento']));
        }

        $parcelasNaoPagas = "";
        foreach ($comissao['parcelasNaoPagas'] as $parc) {
            if ($parcelasNaoPagas == "") {
                $parcelasNaoPagas .= "<li><strong class='red-text'>Parcelas não lançadas: </strong>";
            }
            $parcelasNaoPagas .= $parc . ", ";
        }
        $parcelasNaoPagas .= "</li>";

        $comissoesEncontradasString .= '
            <li key="' . $comissao['txt_id_finalizado'] . '" class="active">
                <div class="collapsible-header">
                ' . $operadora . ' - ' . $comissao['descricao'] . '
                <span class="new badge"></span>
                </div>
                <div class="collapsible-body">
                    <ul>
                        <li><strong>Numero Apólice: </strong> ' . $comissao['n_apolice'] . '</li>
                        <li><strong>Valor: </strong> R$ ' . number_format($comissao['valor_calc'], 2, ',', '.') . '</li>
                        <li><strong>Porcentagem: </strong> ' . $comissao['porcentagem'] . '%</li>
                        <li><strong>Parcela: </strong> ' . $parcela . '</li>
                        ' . $parcelasNaoPagas . '
                        <li><strong>Data de Pagamento: </strong> ' .  $dataPagamento . '</li>
                        <li><strong>ID Finalizado: </strong> ' .  $comissao['txt_id_finalizado'] . '</li>
                    </ul>
                </div>
            </li>';
    }
    $comissoesEncontradasString .= '</ul></div>';




    $comissoesPagasString = '<div id="test2" class="col s12"><ul class="collapsible popout expandable">';

    $operadora = "";
    foreach ($comissoesPagas as $comissao) {
        $parcela = $comissao['txt_parcela'];
        $dataPagamento = $comissao['dataPagamento'];
        if ($parcela < 10) {
            $parcela = "0" . $parcela;
        }

        if ($operadora == "" || $operadora != $comissao['operadora']) {
            $operadora = $comissao['operadora'];
            // $comissoesEncontradasString .= "<h5>$operadora</h5>";
        }

        $comissoesPagasString .= '
            <li key="' . $comissao['txt_id_finalizado'] . '" class="active">
                <div class="collapsible-header">
                ' . $operadora . ' - ' . $comissao['descricao'] . '
                <span class="new badge"></span>
                </div>
                <div class="collapsible-body">
                    <ul>
                        <li><strong>Numero Apólice: </strong> ' . $comissao['n_apolice'] . '</li>
                        <li><strong>Porcentagem: </strong> ' . $comissao['porcentagem'] . '%</li>
                        <li><strong>Parcela: </strong>' . $parcela . '</li>
                        <li><strong>Valor: </strong> R$ ' . number_format($comissao['valor_calc'], 2, ',', '.') . '</li>
                        <li><strong>ID Finalizado: </strong> ' .  $comissao['txt_id_finalizado'] . '</li>
                        <li><strong>Data Pagamento: </strong> ' .  $comissao['dataPagamento'] . '</li>
                    </ul>
                </div>
            </li>';
    }
    $comissoesPagasString .= '</ul></div>';




    $comissoesNaoEncontradasString = '<div id="test3" class="col s12"><ul class="collapsible popout expandable">';

    $referencia = "";
    foreach ($comissoesNaoEncontradas as $comissao) {
        $parcela = $comissao['txt_parcela'];
        $dataPagamento = $comissao['data_pagamento'];
        if ($parcela < 10) {
            $parcela = "0" . $parcela;
        }
        if($comissao['referencia'] == 'AMIL'){
            $comissao['contrato_atual'] = $comissao['proposta'];
        }

        if ($dataPagamento != "" && $dataPagamento != null){
            $dataPagamento = date('d/m/y', strtotime($comissao['data_pagamento']));
        }

        if ($referencia == "" || $referencia != $comissao['referencia']) {
            $operadora = $comissao['referencia'];
            // $comissoesEncontradasString .= "<h5>$referencia</h5>";
        }

        $comissoesNaoEncontradasString .= '
            <li key="' . $comissao['txt_id_finalizado'] . '" class="active">
                <div class="collapsible-header">
                ' . $comissao['referencia'] . ' - ' . $comissao['nome_contrato'] . '
                <span class="new badge"></span>
                </div>
                <div class="collapsible-body">
                    <ul>
                        <li><strong>Numero Apólice: </strong> ' . $comissao['contrato_atual'] . '</li>
                        <li><strong>Porcentagem: </strong> ' . $comissao['porcentagem'] . '%</li>
                        <li><strong>Parcela: </strong>' . $comissao['parcela'] . '</li>
                        <li><strong>Valor: </strong> R$ ' . number_format($comissao['comissao'], 2, ',', '.') . '</li>
                        <li><strong>Data Pagamento: </strong> ' . $dataPagamento . '</li>
                    </ul>
                </div>
            </li>';
    }
    $comissoesNaoEncontradasString .= '</ul></div>';



    $comissoesNegativasString = '<div id="test4" class="col s12"><ul class="collapsible popout expandable">';

    $operadora = "";
    foreach ($comissoesNegativas as $comissao) {
        $parcela = $comissao['txt_parcela'];
        if ($parcela < 10) {
            $parcela = "0" . $parcela;
        }

        if ($operadora == "" || $operadora != $comissao['operadora']) {
            $operadora = $comissao['operadora'];
            // $comissoesEncontradasString .= "<h5>$operadora</h5>";
        }

        $comissoesNegativasString .= '
            <li key="' . $comissao['txt_id_finalizado'] . '" class="active">
                <div class="collapsible-header">
                ' . $operadora . ' - ' . $comissao['descricao'] . '
                <span class="new badge"></span>
                </div>
                <div class="collapsible-body">
                    <ul>
                        <li><strong>Numero Apólice: </strong> ' . $comissao['n_apolice'] . '</li>
                        <li><strong>Porcentagem: </strong> ' . $comissao['porcentagem'] . '%</li>
                        <li><strong>Parcela: </strong>' . $parcela . '</li>
                        <li><strong>Valor: </strong> R$ ' . number_format($comissao['valor_calc'], 2, ',', '.') . '</li>
                        <li><strong>ID Finalizado: </strong> ' .  $comissao['txt_id_finalizado'] . '</li>
                    </ul>
                </div>
            </li>';
    }
    $comissoesNegativasString .= '</ul></div>';
    // Comissoes já pagas


    // $comissoesEncontradasString = "";


    // echo '
    //     <div id="swipe-2" class="col s12 " style="height: auto;">
    //         <ul class="collapsible popout expandable">';

    // foreach ($comissoesProcessadas[1] as $comissao) {
    //     // echo "<p>".json_encode($comissao)."</p>";
    //     if ($operadora == "" || $operadora != $comissao['operadora']) {
    //         $operadora = $comissao['operadora'];
    //         echo "<h5>" . $comissao['operadora'] . "</h5>";
    //     }

    //     echo '

    //             <li key="' . $comissao['txt_id_finalizado'] . '" class="active">
    //                 <div class="collapsible-header">
    //                 ' . $comissao['descricao'] . '
    //                 <span class="new badge"></span>
    //                 </div>
    //                 <div class="collapsible-body">
    //                     <ul>
    //                         <li><strong>Numero Apólice: </strong> ' . $comissao['n_apolice'] . '</li>
    //                         <li><strong>Porcentagem: </strong> ' . $comissao['porcentagem'] . '%</li>
    //                         <li><strong>Parcela: </strong> ' . $comissao['txt_parcela'] . '</li>
    //                         <li><strong>Valor: </strong> R$ ' . number_format($comissao['valor_calc'], 2, ',', '.') . '</li>
    //                         <li><strong>ID Finalizado: </strong> ' .  $comissao['txt_id_finalizado'] . '</li>
    //                     </ul>
    //                 </div>
    //             </li>';
    // }
    // echo '
    //         </ul>
    //     </div>';
}

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

    <!-- Colocar efeito de abrir e fechar para obter mais informaçoes -->
    <!-- <script>
        document.addEventListener('DOMContentLoaded', function() {
            var elem = document.querySelector('.collapsible.expandable');
            var instance = M.Collapsible.init(elem, {
                accordion: false
            });
        });

        $(document).ready(function() {
            $('.collapsible.expandable').collapsible({accordion: false});
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var elems = document.querySelectorAll('.sidenav');
            var instances = M.Sidenav.init(elems, options);
        });

        // Or with jQuery

        // $(document).ready(function() {
        //     $('.sidenav').sidenav();
        // });
    </script> -->



    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <script>
        // Efeito para trocar o conteudo das divs
        // $('ul.tabs').tabs({
        //     swipeable: true,
        //     responsiveThreshold: Infinity
        // });
        $(document).ready(function() {
            $('.tabs').tabs();
        });



        // Efeito para abrir e fechar as informaçoes do lançamento das comissoes
        $(document).ready(function() {
            $('.collapsible.expandable').collapsible({
                accordion: false
            });
        });
    </script>
    <style>
        .carousel .carousel-item {
            width: 100%;
        }
    </style>
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
                                echo "<option value='" . $rs_operadora['id'] . "' id='operadoras' selected>" . $rs_operadora['titulo'] . "</option>";
                            } else {
                                echo "<option value='" . $rs_operadora['id'] . "' id='operadoras'>" . $rs_operadora['titulo'] . "</option>";
                            }
                        }

                        ?>
                    </select>
                    <!-- <label>Operadoras</label> -->

                </div>
                <div class="input-field col s3 ">
                    <?php
                    if (isset($_GET['data_inicial'])) {
                        $dataInicial = $_GET['data_inicial'];
                        echo "<input type='date' id='data_inicial' name='data_inicial' value='$dataInicial' required />";
                    } else {
                        echo '<input type="date" id="data_inicial" name="data_inicial" required />';
                    }
                    ?>
                    <label for="data_inicial">Data Inicial</label>
                </div>
                <div class="input-field col s3 ">
                    <?php
                    if (isset($_GET['data_final'])) {
                        $dataFinal = $_GET['data_final'];
                        echo "<input type='date' id='data_final' name='data_final' value='$dataFinal' />";
                    } else {
                        echo '<input type="date" id="data_final" name="data_final" />';
                    }
                    ?>
                    <label for="data_final">Data Final</label>
                </div>
                <p>
                    <label>
                        <input type="checkbox" class="filled-in" />
                        <span>Salvar no Sistema</span>
                    </label>
                </p>

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
        <div class="row">
            <ul class="tabs tabs-fixed-width tab-demo z-depth-1">
                <?php if (sizeof($comissoesEncontradas) > 0) { ?>
                    <li class="tab"><a class="active" href="#test1">Encontradas (<?= sizeof($comissoesEncontradas); ?>)</a></li>
                <?php } ?>
                <?php if (sizeof($comissoesPagas) > 0) { ?>
                    <li class="tab"><a href="#test2">Pagas (<?= sizeof($comissoesPagas); ?>)</a></li>
                <?php } ?>
                <?php if (sizeof($comissoesEncontradas) > 0) { ?>
                    <li class="tab"><a href="#test3">Nao encontradas (<?= sizeof($comissoesNaoEncontradas); ?>)</a></li>
                <?php } ?>
                <?php if (sizeof($comissoesNegativas) > 0) { ?>
                    <li class="tab"><a href="#test4">Negativas (<?= sizeof($comissoesNegativas); ?>)</a></li>
                <?php } ?>
            </ul>
            <div id="test1" class="col s12">
                <?= $comissoesEncontradasString; ?>
            </div>
            <div id="test2" class="col s12">
                <?= $comissoesPagasString; ?>
            </div>
            <div id="test3" class="col s12">
                <?= $comissoesNaoEncontradasString; ?>
            </div>
            <div id="test4" class="col s12">
                <?= $comissoesNegativasString; ?>
            </div>
        </div>


        <?php
        // if (isset($_GET['operadora']) && isset($_GET['data_inicial'])) {
        //     $idOperadora = $_GET['operadora'];
        //     $dataInicial = $_GET['data_inicial'];
        //     if ($idOperadora == "" || $idOperadora == null || $idOperadora == 0) {
        //         $sql = "select * FROM busca_comissoes where  data_inicial >= '$dataInicial';";
        //     } else {
        //         $sql = "select * FROM busca_comissoes where id_operadora = '$idOperadora' and data_inicial >= '$dataInicial';";
        //     }

        //     // echo $sql;
        //     $select_comissoes = mysqli_query($conect, $sql);
        //     while ($rs_comissoes = mysqli_fetch_array($select_comissoes)) {
        //         array_push($tblComissoes, $rs_comissoes);
        //     }
        //     $comissoes = agruparComissoes($tblComissoes);
        //     $comissoesProcessadas = processo($comissoes);

        //     $count = 0;



        //     echo '
        //     <ul class="tabs tabs-fixed-width tab-demo z-depth-1">
        //         <li class="tab"><a class="active" href="#swipe-1">Encontradas (' . sizeof($comissoesProcessadas[0]) . ')</a></li>
        //         <li class="tab"><a href="#swipe-2">Pagas (' . sizeof($comissoesProcessadas[1]) . ')</a></li>
        //         <li class="tab"><a href="#swipe-3">Test 3</a></li>
        //     </ul>';


        //     // Comissoes a serem lançadas
        //     echo '
        //     <div id="swipe-1" class="col s12 " style="height: auto;">
        //         <ul class="collapsible popout expandable">';

        //     $operadora = "";
        //     foreach ($comissoesProcessadas[0] as $comissao) {
        //         // echo "<p>".json_encode($comissao)."</p>";
        //         if ($operadora == "" || $operadora != $comissao['operadora']) {
        //             $operadora = $comissao['operadora'];
        //             echo "<h5>" . $comissao['operadora'] . "</h5>";
        //         }

        //         echo '

        //                 <li key="' . $comissao['txt_id_finalizado'] . '" class="active">
        //                     <div class="collapsible-header">
        //                     ' . $comissao['descricao'] . '
        //                     <span class="new badge"></span>
        //                     </div>
        //                     <div class="collapsible-body">
        //                         <ul>
        //                             <li><strong>Numero Apólice: </strong> ' . $comissao['n_apolice'] . '</li>
        //                             <li><strong>Porcentagem: </strong> ' . $comissao['porcentagem'] . '%</li>
        //                             <li><strong>Parcela: </strong> ' . $comissao['txt_parcela'] . '</li>
        //                             <li><strong>Valor: </strong> R$ ' . number_format($comissao['valor_calc'], 2, ',', '.') . '</li>
        //                             <li><strong>ID Finalizado: </strong> ' .  $comissao['txt_id_finalizado'] . '</li>
        //                         </ul>
        //                     </div>
        //                 </li>';
        //     }
        //     echo '
        //             </ul>
        //         </div>';

        //     // Comissoes já pagas
        //     echo '
        //         <div id="swipe-2" class="col s12 " style="height: auto;">
        //             <ul class="collapsible popout expandable">';

        //     foreach ($comissoesProcessadas[1] as $comissao) {
        //         // echo "<p>".json_encode($comissao)."</p>";
        //         if ($operadora == "" || $operadora != $comissao['operadora']) {
        //             $operadora = $comissao['operadora'];
        //             echo "<h5>" . $comissao['operadora'] . "</h5>";
        //         }

        //         echo '

        //                 <li key="' . $comissao['txt_id_finalizado'] . '" class="active">
        //                     <div class="collapsible-header">
        //                     ' . $comissao['descricao'] . '
        //                     <span class="new badge"></span>
        //                     </div>
        //                     <div class="collapsible-body">
        //                         <ul>
        //                             <li><strong>Numero Apólice: </strong> ' . $comissao['n_apolice'] . '</li>
        //                             <li><strong>Porcentagem: </strong> ' . $comissao['porcentagem'] . '%</li>
        //                             <li><strong>Parcela: </strong> ' . $comissao['txt_parcela'] . '</li>
        //                             <li><strong>Valor: </strong> R$ ' . number_format($comissao['valor_calc'], 2, ',', '.') . '</li>
        //                             <li><strong>ID Finalizado: </strong> ' .  $comissao['txt_id_finalizado'] . '</li>
        //                         </ul>
        //                     </div>
        //                 </li>';
        //     }
        //     echo '
        //             </ul>
        //         </div>';
        // }
        ?>

    </div>
</body>

</html>