<?php

require_once "comissoes.php";


$comissoesEncontradas = array();
$comissoesPagas = array();
$comissoesNaoEncontradas = array();
$comissoesNegativas = array();

$comissoesEncontradasString = "";

$parcelasFaltando = array();

$salvarSistema = "";
$salvar = false;
if (isset($_GET['salva_banco'])) {
    $salvarSistema = "checked='checked'";
    $salvar = true;
}

if (isset($_GET['operadora']) && isset($_GET['data_inicial'])) {


    $idOperadora = $_GET['operadora'];
    $dataInicial = $_GET['data_inicial'];
    $dataFinal = $_GET['data_final'];

    $buscaOperadoras = "";
    
    if ($idOperadora == "" || $idOperadora == null || $idOperadora == 0) {
        $buscaOperadoras = "> '0'";
    } else {
        $buscaOperadoras = "= '$idOperadora'";
    }

    $sql = "SELECT data_inicial, data_final, nome_contrato, data_pagamento, sum(comissao) as comissao, parcela, porcentagem, contrato_atual, id_operadora, id_conta, dental, referencia, contrato_atual, proposta, base_comissao  
    from busca_comissoes where id_operadora $buscaOperadoras and referencia not like 'SULAMERICA' and  ((data_pagamento >= '$dataInicial' and data_pagamento <= '$dataInicial') or (data_inicial >= '$dataInicial' and data_final <= '$dataFinal')) 
    group by nome_contrato, contrato_atual, porcentagem, data_pagamento, referencia, proposta, parcela, id_operadora, id_conta, dental, data_inicial, data_final, base_comissao order by data_pagamento, parcela;";    

    

    if (intval($idOperadora) == 4) {
        $sql = "SELECT * from busca_comissoes where id_operadora = $idOperadora and referencia like 'SULAMERICA' and ((data_pagamento >= '$dataInicial' and data_pagamento <= '$dataFinal') or (data_inicial >= '$dataInicial' and data_final <= '$dataFinal'));";
    }


    $select_comissoes = mysqli_query($conect, $sql);
    while ($rs_comissoes = mysqli_fetch_assoc($select_comissoes)) {
        array_push($tblComissoes, $rs_comissoes);
    }
    // $comissoes = agruparComissoes($tblComissoes);
    $comissoesProcessadas = processo($tblComissoes, $salvar);

    $count = 0;

    $comissoesEncontradas = $comissoesProcessadas[0];
    $comissoesPagas = $comissoesProcessadas[1];
    $comissoesNaoEncontradas = $comissoesProcessadas[2];
    $comissoesNegativas = $comissoesProcessadas[3];

    // echo $sql;

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
        if ($dataPagamento != "" && $dataPagamento != null) {
            $dataPagamento = date('d/m/y', strtotime($dataPagamento));
        } else {
            $dataPagamento = "";
        }

        if (sizeof($comissao['parcelasNaoPagas'])) {
            array_push($parcelasFaltando, $comissao);
        }


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

        if (sizeof($comissao['parcelasNaoPagas'])) {
            array_push($parcelasFaltando, $comissao);
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
        if ($comissao['referencia'] == 'AMIL') {
            $comissao['contrato_atual'] = $comissao['proposta'];
        }

        if ($dataPagamento != "" && $dataPagamento != null) {
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





    $parcelasFaltandoString = '<div id="test5" class="col s12"><ul class="collapsible popout expandable">';

    $operadora = "";
    foreach ($parcelasFaltando as $comissao) {
        $parcela = $comissao['txt_parcela'];
        $dataPagamento = $comissao['dataPagamento'];
        if ($parcela < 10) {
            $parcela = "0" . $parcela;
        }


        $parcelasNaoPagas = "";
        foreach ($comissao['parcelasNaoPagas'] as $parc) {
            if ($parcelasNaoPagas == "") {
                $parcelasNaoPagas .= "<li><strong class='red-text'>Parcelas não lançadas: </strong>";
            }
            $parcelasNaoPagas .= $parc . ", ";
        }
        $parcelasNaoPagas .= "</li>";

        if ($operadora == "" || $operadora != $comissao['operadora']) {
            $operadora = $comissao['operadora'];
            // $comissoesEncontradasString .= "<h5>$operadora</h5>";
        }

        $parcelasFaltandoString .= '
            <li key="' . $comissao['txt_id_finalizado'] . '" class="active">
                <div class="collapsible-header">
                ' . $operadora . ' - ' . $comissao['descricao'] . '
                <span class="new badge"></span>
                </div>
                <div class="collapsible-body">
                    <ul>
                        <li><strong>Numero Apólice: </strong> ' . $comissao['n_apolice'] . '</li>
                        ' . $parcelasNaoPagas . '
                        <li><strong>ID Finalizado: </strong> ' .  $comissao['txt_id_finalizado'] . '</li>
                    </ul>
                </div>
            </li>';
    }
    $parcelasFaltandoString .= '</ul></div>';
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

    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <script>
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
                        // echo '<input type="date" id="data_final" name="data_final" value="' . date("Y-m-d") . '" />';
                        echo '<input type="date" id="data_final" name="data_final" value="" />';
                    }
                    ?>
                    <label for="data_final">Data Final</label>
                </div>
                <p>
                    <label>
                        <input type="checkbox" class="filled-in" id="ck_salvar" name="salva_banco" <?= $salvarSistema ?> onclick="verificarCheckBox();" />
                        <span>Salvar no Sistema</span>
                    </label>
                </p>

            </div>
            <div class="row">
                <div class="input-field col s6 ">
                    <button class="btn waves-effect waves-light" id="btn_pesquisa" type="submit" name="action" value="PESQUISAR">
                        PESQUISAR
                        <i class="material-icons right">search</i>
                    </button>
                    <button id="btn_limpar" class="btn waves-effect waves-light red darken-1" type="button">
                        LIMPAR
                        <i class="material-icons right ">clear</i>
                    </button>
                    <script>
                        const $btnLimpar = document.getElementById("btn_limpar");
                        var $btnPesquisa = document.getElementById("btn_pesquisa");

                        const redirecionar = () => window.location.href = "front.php";
                        $btnLimpar.addEventListener('click', () => redirecionar());

                        function verificarCheckBox() {
                            var $ckSalvar = document.getElementById("ck_salvar");

                            if ($ckSalvar.checked == true) {
                                console.log("true")
                                console.log($btnPesquisa)
                                $btnPesquisa.innerHTML = 'Salvar <i class="material-icons right">save</i>';
                            } else {
                                console.log("false")
                                $btnPesquisa.innerHTML = 'Pesquisar <i class="material-icons right">search</i>';
                            }
                        }
                    </script>
                </div>

            </div>
        </form>


    </div>
    <div class="container">

        <div class="progress">
            <div class="indeterminate"></div>
        </div>

        <!-- Relatório -->
        <div class="row">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Quantidade</th>
                        <th>Valor</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Há serem Lançadas</td>
                        <td>(<?= sizeof($comissoesEncontradas) ?>)</td>
                        <td>
                            <?php
                            $comissaoTotalEncontradas = 0.0;
                            foreach ($comissoesEncontradas as $comissao) {
                                $comissaoTotalEncontradas += $comissao['valor_calc'];
                            }
                            echo "R$ " . number_format($comissaoTotalEncontradas, 2, ',', '.');
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Lançadas</td>
                        <td>(<?= sizeof($comissoesPagas) ?>)</td>
                        <td>
                            <?php
                            $comissaoTotalPagas = 0.0;
                            foreach ($comissoesPagas as $comissao) {
                                $comissaoTotalPagas += $comissao['valor_calc'];
                            }
                            echo "R$ " . number_format($comissaoTotalPagas, 2, ',', '.');
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Não Encontradas</td>
                        <td>(<?= sizeof($comissoesNaoEncontradas) ?>)</td>
                        <td>
                            <?php
                            $comissaoTotalNaoEncontradas = 0.0;
                            foreach ($comissoesNaoEncontradas as $comissao) {
                                $comissaoTotalNaoEncontradas += $comissao['comissao'];
                            }
                            echo "R$ " . number_format($comissaoTotalNaoEncontradas, 2, ',', '.');
                            ?>
                        </td>
                    </tr>
                    <tr class="blue darken-1 white-text">
                        <td><strong>Total</strong></td>
                        <td>
                            <strong>
                                (<?= sizeof($comissoesNaoEncontradas) + sizeof($comissoesPagas) + sizeof($comissoesEncontradas) ?>)
                            </strong>
                        </td>
                        <td>
                            <strong>
                                <?php
                                $comissaoTotalNaoEncontradas = 0.0;
                                foreach ($comissoesNaoEncontradas as $comissao) {
                                    $comissaoTotalNaoEncontradas += $comissao['comissao'];
                                }
                                echo "R$ " . number_format($comissaoTotalEncontradas + $comissaoTotalPagas + $comissaoTotalNaoEncontradas, 2, ',', '.');
                                ?>
                            </strong>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        <div class="row">
            <ul class="tabs tabs-fixed-width tab-demo z-depth-1">
                <?php if (sizeof($comissoesEncontradas) > 0) { ?>
                    <li class="tab"><a class="active" href="#test1">Há serem Lançadas (<?= sizeof($comissoesEncontradas); ?>)</a></li>
                <?php } ?>
                <?php if (sizeof($comissoesPagas) > 0) { ?>
                    <li class="tab"><a href="#test2">Lançadas (<?= sizeof($comissoesPagas); ?>)</a></li>
                <?php } ?>
                <?php if (sizeof($comissoesNaoEncontradas) > 0) { ?>
                    <li class="tab"><a href="#test3">Nao encontradas (<?= sizeof($comissoesNaoEncontradas); ?>)</a></li>
                <?php } ?>
                <?php if (sizeof($comissoesNegativas) > 0) { ?>
                    <li class="tab"><a href="#test4">Negativas (<?= sizeof($comissoesNegativas); ?>)</a></li>
                <?php } ?>
                <?php if (sizeof($parcelasFaltando) > 0) { ?>
                    <li class="tab"><a href="#test5">Parcelas Faltando (<?= sizeof($parcelasFaltando); ?>)</a></li>
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
            <div id="test5" class="col s12">
                <?= $parcelasFaltandoString; ?>
            </div>
        </div>
    </div>

    <!-- Efeito do load -->
    <script>
        //código usando jQuery
            $(document).ready(function() {
                $('.progress').hide();
            });
            $('#btn_pesquisa').click(function() {
                if ($('#data_inicial').val() != "") {
                    $('.progress').show();
                }
            });
    </script>
</body>

</html>

<?php
    mysqli_close($conect);
?>