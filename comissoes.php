<?php

require_once("../includes/config.php");
require_once('../includes/functions.php');
require_once "pagar_comissao.php";

$conect = conexaoMysqlTest();

$log = false;

$tblFinalizado = array();
$tblComissoes = array();
$tblContas = array();

$sql = "SELECT * FROM tbl_contas;";
$select_contas = mysqli_query($conect, $sql);
while ($rs_conta = mysqli_fetch_assoc($select_contas)) {
    array_push($tblContas, $rs_conta);
}

$sql = "SELECT id, id_operadora, razao_social, n_apolice, contrato_dental  FROM tbl_finalizado;";
$select_finalizado = mysqli_query($conect, $sql);
while ($rs_finalizado = mysqli_fetch_assoc($select_finalizado)) {
    array_push($tblFinalizado, $rs_finalizado);
}

function processo($array, $salvarSistema)
{
    global $log;
    global $conect;
    global $tblContas;

    $comissoes = $array;

    $comissoesEncontradas = array();
    $comissoesPagas = array();
    $comissoesNaoEncontradas = array();

    $parcelasNaoPagas = array();

    for ($i = 0; $i < sizeof($comissoes); $i++) {

        $idFinalizado = 0;
        $idDestino = 1;
        $portabilidade = "";
        $idTransacao = 1;

        $comissaoRow = $comissoes[$i];

        $idBuscaComissao = $comissaoRow['id'];

        $referencia = (string) mb_strtoupper($comissaoRow['referencia']);
        $nomeContrato = $comissaoRow['nome_contrato'];
        $contrato = $comissaoRow['contrato_atual'];
        $proposta = $comissaoRow['proposta'];
        $valor_calc = $comissaoRow['comissao'];
        $parcela = intval($comissaoRow['parcela']);
        $porcentagem = $comissaoRow['porcentagem'];
        $dataPagamento = $comissaoRow['data_pagamento'];
        $baseComissao = $comissaoRow['base_comissao'];
        $idOperadora = $comissaoRow['id_operadora'];
        $idOrigem = $comissaoRow['id_conta'];
        $refDental = $comissaoRow['dental'];
        $valorBrutoComissao = $comissaoRow['base_comissao'];
        $statusComissao = $comissaoRow['paga'];
        $idTipoComissao = $comissaoRow['id_tipo_comissao'];
        $idFinalizado = $comissaoRow['id_finalizado'];
        $idFinalizadoInicial = $idFinalizado;

        $ref_arr = explode(" ", $referencia);
        switch (strtoupper($ref_arr[0])) {
            case "AFFINITY":
                $contrato = $proposta;
                break;
            case "INTERMEDICA-PME":
                if ($proposta > 0) {
                    $contrato = $proposta;
                }
                break;
            case "AMIL":
                $contrato = $proposta;
                break;
            case "NEXT":
                $contrato = $proposta;
                break;
            case "SULAMERICA":
                $contrato = $proposta;
                break;
            case "SOMPO":
                if ($parcela <= 3) {
                    $porcentagem = 100;
                    $imposto = number_format($valor_calc * 0.035, 2);
                    $valor_calc = $valor_calc - $imposto;
                }
                break;
            case "PORTO":
                if ($parcela <= 3) {
                    $imposto = number_format($valor_calc * 0.035, 2);
                    $valor_calc = $valor_calc - $imposto;
                }
                break;
            default:
                break;
        }

        if ($log) {
            echo "<br><p style='color:blue;'>-> <strong>$referencia</strong></p>";
            echo "<p><strong>Busca: </strong> (id_busca) - <strong>$nomeContrato</strong> (Nome Contrato) - $contrato (Numero da apolice)</p>";
            echo "<p><strong>Parcela: </strong>" . $parcela . "</p>";
            echo "<p><strong>Data Pagamento extraido: </strong>" . $dataPagamento . "</p>";
            echo "<p><strong>Comissão: </strong>R$ " . $valor_calc . "</p>";
            echo "<p><strong>Porcentagem: </strong>" . $porcentagem . "%</p>";
        }


        if ($parcela >= 6) {
            $parcelaPesquisa = 6;
        } else {
            $parcelaPesquisa = $parcela;
        }

        $sql = "SELECT * FROM tbl_porcentagem_comissoes WHERE id_operadora = $idOperadora AND parcela = $parcelaPesquisa;";
        // echo $sql;
        $select = mysqli_query($conect, $sql);
        $porcentagemComissoes = array();
        while ($rs_porcentagem = mysqli_fetch_assoc($select)) {
            array_push($porcentagemComissoes, $rs_porcentagem);
        }

        if (sizeof($porcentagemComissoes) > 0) {
            $porcentagemErrada = true;
            $porcentagemCorreta = 0;
            foreach ($porcentagemComissoes as $rs_porcentagem) {

                if ($rs_porcentagem['porcentagem'] == $porcentagem && $rs_porcentagem['parcela'] == $parcelaPesquisa) {
                    // echo "Comissao esta com a porcentagem certa!";
                    $porcentagemErrada = false;
                }
                if ($rs_porcentagem['parcela'] == $parcelaPesquisa) {
                    $porcentagemCorreta = $rs_porcentagem['porcentagem'];
                }
            }
            if ($porcentagemErrada) {
                if ($log) {
                    echo "<p style='background:orange;'>A porcentagem da comissao não está como o esperado! <br>A porcentagem correta seria $porcentagemCorreta%</p>";
                }
            }
        } else {
            if ($log) {
                echo "<p style='background:orange;'>A porcentagem da comissao não está como o esperado!</p>";
            }
        }





        if ($idOperadora > 0) {

            # Identificar a Conta de Origem 
            if ($idOperadora == 2) {
                for ($j = 0; $j <= sizeof($tblContas); $j++) {
                    $contasRow = $tblContas[$j];

                    if ($referencia == $contasRow['titulo']) {
                        $idOrigem = $contasRow['id'];
                        if ($log) {
                            echo "<p> <strong>id_conta: </strong>" . $idOrigem . "</p>";
                        }
                        break;
                    }
                }
            }

            if ($idOrigem > 0) {
                if ($idFinalizado == "" || $idFinalizado == null || $idFinalizado == 0) {
                    // echo "procurar sem id";
                    $res = procuraPeloNumeroContratoAtual($contrato, $idOperadora);
                    if ($res) {
                        $idFinalizado = $res['id'];
                        $portabilidade = $res['portabilidade'];
                        if ($refDental == 0) {
                            $contrato = $res['n_apolice'];
                        } else {
                            $contrato = $res['contrato_dental'];
                        }
                        $operadoraFinalizado = $res['id_operadora'];
                        $nomeContrato = $res['razao_social'];

                        if ($operadoraFinalizado == 5) {
                            $idOrigem = 22;
                        }
                    } else {
                        $res = procuraPelaRazaoSocial($nomeContrato, $idOperadora);
                        if ($res) {
                            $idFinalizado = $res['id'];
                            $portabilidade = $res['portabilidade'];
                            $nomeContrato = $res['razao_social'];
                        } else {
                            $buscaRazaoSocialArray = array();
                            if ($idOperadora == 2) {
                                // $sql = "select * from tbl_finalizado where razao_social like '%$nomeContrato%' and (id_operadora = $idOperadora or id_operadora = 5);";
                                $sql = "select id,id_operadora, id_tipo_plano, razao_social, n_apolice, valor from tbl_finalizado where replace(replace(replace(replace(replace(razao_social, '-', ''), '.',''), ' ',''), '/',''), '&', '')  like '%" . str_replace(' ', '', str_replace("&", "", $nomeContrato)) . "%' and (id_operadora = $idOperadora or id_operadora = 5);";
                            } else {
                                // $sql = "select * from tbl_finalizado where razao_social like '%$nomeContrato%' and where id_operadora = $idOperadora;";

                                $sql = "select id,id_operadora, id_tipo_plano, razao_social, n_apolice, valor from tbl_finalizado where replace(replace(replace(replace(replace(razao_social, '-', ''), '.',''), ' ',''), '/',''), '&', '')  like '%" . str_replace(' ', '', str_replace("&", "", $nomeContrato)) . "%' and id_operadora = $idOperadora;";
                            }
                            // echo "<p>$sql</p>";
                            $buscaRazaoSocial = mysqli_query($conect, $sql);
                            while ($rs_busca = mysqli_fetch_assoc($buscaRazaoSocial)) {
                                array_push($buscaRazaoSocialArray, $rs_busca);
                            }

                            if (sizeof($buscaRazaoSocialArray) == 1) {
                                $row = $buscaRazaoSocialArray[0];
                                if ($row['razao_social'] != "" && $row['razao_social'] != null) {
                                    $idFinalizado = intval($row['id']);
                                    $nomeContrato = $row['razao_social'];
                                } else {
                                    array_push($comissoesNaoEncontradas, $comissaoRow);
                                }
                            } else {
                                // echo "Mais de um contrato encontrado";
                            }
                        }
                    }
                } else {
                    $res = procuraFinalizado($idFinalizado);
                    if ($res) {
                        $idFinalizado = $res['id'];
                        $portabilidade = $res['portabilidade'];
                        if ($refDental == 0) {
                            $contrato = $res['n_apolice'];
                        } else {
                            $contrato = $res['contrato_dental'];
                        }
                        $operadoraFinalizado = $res['id_operadora'];
                        $nomeContrato = $res['razao_social'];

                        if ($operadoraFinalizado == 5) {
                            $idOrigem = 22;
                        }
                    }
                }

                $idCorretor = $res['id_corretor'];
                $idOperadora = $res['id_operadora'];
            } else {
                if ($log) {
                    echo "<p style='color:red;'>Não achou a conta de origem!</p>";
                }
            }
            if ($log) {
                // echo "<p style='background: yellow;'><strong>Razao Social: </strong>".$nomeContrato."</p>";
                echo "<p><strong>Busca: </strong> (id_busca) - <strong style='background: yellow;'>$nomeContrato</strong> (Nome Contrato) - $contrato (Numero da apolice)</p>";
            }


            if ($idFinalizado > 0) {
                if ($log) {
                    echo "<h5> ID: <strong style='color:green;'>ID FINALIZADO: $idFinalizado </strong> encontrou</h5>";
                }


                // Varificando as parcelas da Sulamerica
                // echo "<h5> ID: <strong style='color:red;'>$parcela</h5>";
                if ($idOperadora == 4 && $parcela <= 1 && $referencia == 'SULAMERICA') {
                    $sql = "SELECT max(parcela) as ultima_parcela from tbl_transacoes where id_origem = $idOrigem and id_finalizado = $idFinalizado;";
                    $buscaUltimaParcela = mysqli_query($conect, $sql);
                    while ($rs_busca = mysqli_fetch_assoc($buscaUltimaParcela)) {
                        // echo json_encode($rs_busca);
                        $parcela = $rs_busca['ultima_parcela'] + 1;
                    }
                }

                // echo "<h5> ID: <strong style='color:green;'>$parcela</h5><br>";








                $listaParcelas = array();
                $listaParcelasBanco = array();

                for ($k = 1; $k <= $parcela; $k++) {
                    array_push($listaParcelas, $k);
                }

                $trasacoes = array();
                $sql = "select * from tbl_transacoes where id_finalizado = $idFinalizado AND id_origem = $idOrigem;";

                // echo "<p>$sql</p>";
                $select_transacoes = mysqli_query($conect, $sql);
                while ($rs_trasacao = mysqli_fetch_assoc($select_transacoes)) {
                    // echo "<h6> ID: <song style='color:orange;'>ID TRANSACOES: ".$rs_trasacao['id']." </strong> encontrou</h6>";

                    array_push($trasacoes, $rs_trasacao);
                    array_push($listaParcelasBanco, intval($rs_trasacao['parcela']));
                }

                # Procurar novamente mas com outro id_origem
                if (sizeof($trasacoes) == 0 && $idOrigem == 18 && $parcela >= 1) {
                    $idOrigem = 22;
                    $sql = "select * from tbl_transacoes where id_finalizado = $idFinalizado AND id_origem = $idOrigem;";

                    // echo "<p>$sql</p>";
                    $select_transacoes = mysqli_query($conect, $sql);
                    while ($rs_trasacao = mysqli_fetch_assoc($select_transacoes)) {
                        // echo "<h6> ID: <song style='color:orange;'>ID TRANSACOES: ".$rs_trasacao['id']." </strong> encontrou</h6>";

                        array_push($trasacoes, $rs_trasacao);
                        array_push($listaParcelasBanco, intval($rs_trasacao['parcela']));
                    }
                }

                // echo "<p>Parcelas </p>";
                // var_dump($listaParcelas);
                // echo "<p>Parcelas Banco</p>";
                // var_dump($listaParcelasBanco);

                $listaParcelasNaoPagas = array_diff($listaParcelas, $listaParcelasBanco);
                // echo "<p>Result</p>";
                // var_dump($listaParcelasNaoPagas);
                $arrayParcelasNaoPagas = array();

                foreach ($listaParcelasNaoPagas as $key) {
                    array_push($arrayParcelasNaoPagas, $key);
                }

                $refComissaoPendente = false;


                foreach ($listaParcelasNaoPagas as $key) {
                    if ($key == $parcela) {
                        $refComissaoPendente = true;
                        // echo "<h5><strong style='color:red;'>Falta pagar a ultima parcela lançada pela operadora: ".$key."</strong></h5>";
                    } else {

                        // echo "<h2 style='display:block;'>$refDental</h2>";

                        if (intval($refDental) == 0) {
                            // echo "<h2 style='display:block;'>Qualquer coisa</h2>";

                            $sql = "";

                            if ($key <= 5) {
                                $sql = "SELECT * FROM tbl_transacoes WHERE id_origem = '$idOrigem' AND id_finalizado = '$idFinalizado' AND parcela < '$key' AND dental = 0 ORDER BY data, valor LIMIT 1;";
                            } else {
                                $sql = "SELECT * FROM tbl_transacoes WHERE id_origem = '$idOrigem' AND id_finalizado = '$idFinalizado' AND parcela > '$key' AND dental = 0 ORDER BY data, valor LIMIT 1;";
                            }
                            // echo "<h2 '>teste2 $sql</h2>";
                            $select = mysqli_query($conect, $sql);
                            while ($rs = mysqli_fetch_assoc($select)) {
                                $rs['parcela_faltando'] = $key;
                                $segundaComissao = $rs;

                                if ($idOperadora == 3) {
                                    $rs['comissao_calculada'] = $key == 4 || $key == 5 ? $rs['valor'] * 0.5 : $rs['valor'];

                                    $segundaComissao['comissao_calculada'] = $key == 4 || $key == 5 ? $segundaComissao['valor'] * 0.04 : "";
                                    // echo "parcela faltando: ".json_encode($segundaComissao);
                                    if ($segundaComissao['comissao_calculada'] != "") {
                                        array_push($parcelasNaoPagas, $segundaComissao);
                                    }
                                }
                                array_push($parcelasNaoPagas, $rs);
                                // echo sizeof($parcelasNaoPagas);
                                // echo "parcela faltando: ".json_encode($parcelasNaoPagas);
                            }
                            // echo "parcela faltando: ".json_encode($parcelasNaoPagas);

                            // echo $sql;


                        }
                        if ($log) {
                            echo "<h5><strong style='color:red;'>Falta Pagar a parcela: " . $key . " </strong>.</h5>";
                        }
                    }
                }

                // Regra para Pagar o valor bruto se a operadora for notredame
                if ($idOperadora == 1 && $parcela == 1 && $porcentagem == 100) {
                    if ($log) {
                        echo "<p><strong>Valor Base Comissao: </strong>R$ " . $baseComissao . " || " . $valor_calc . "</p>";
                        echo "<h5>" . ($baseComissao - $valor_calc) . "</h5>";
                    }
                    $valor_calc = $baseComissao;
                }
                if ($idOperadora == 12 && $parcela == 3) {
                    $valor_calc = $baseComissao;
                    $imposto = number_format($valor_calc * 0.035, 2);
                    $valor_calc = $valor_calc - $imposto;
                }

                if ($idOperadora == 4 && $porcentagem < 95) {
                    $idTransacao = 7;
                }


                if ($parcela > 3) {
                    $idTransacao = 7;
                }

                if ($idTipoComissao > 0) {
                    $idTransacao = 1;
                }

                if ($idOperadora == 4 && $porcentagem <= 40 && $parcela <= 3) {
                    $parcela = 4;
                }

                if ($idOperadora == 4 && ($valor_calc == 41.00 && $porcentagem == 100)) {
                    $refDental = true;
                }
                if ($idOperadora == 4 && ($idTipoComissao == 2 || $idTipoComissao == 1)) {
                    $idOrigem = 20;
                    $idTransacao = 1;
                }

                if ($idCorretor == 7) {
                    $idTransacao = 1;
                }

                $nomeContrato = str_replace("'", ' ', $nomeContrato);

                $dadosComissao = [
                    'idBuscaComissao' => $idBuscaComissao,
                    'txt_id_finalizado' => $idFinalizado,
                    'valor_calc' => $valor_calc,
                    'id_origem' => $idOrigem,
                    'id_destino' => $idDestino,
                    'descricao' => $nomeContrato,
                    'txt_parcela' => $parcela,
                    'portabilidade' => $portabilidade,
                    'n_apolice' => $contrato,
                    'porcentagem' => $porcentagem,
                    'id_transacao' => $idTransacao,
                    'dental' => $refDental,
                    'operadora' => $referencia,
                    'parcelasNaoPagas' => $arrayParcelasNaoPagas,
                    'valor_bruto' => $valorBrutoComissao,
                    'dataPagamento' => $dataPagamento,
                    'id_tipo_comissao' => $idTipoComissao
                ];

                if ((!$idFinalizadoInicial > 0) && $idBuscaComissao > 0) {
                    $updateComissao = "UPDATE 
                                            tbl_comissoes_operadora  
                                        SET 
                                            id_finalizado = $idFinalizado, 
                                            id_conta = $idOrigem,
                                            id_operadora = $idOperadora 
                                        WHERE 
                                            id=$idBuscaComissao;";

                    mysqli_query($conect, $updateComissao);
                }


                $ref = false;

                if (sizeof($trasacoes) > 0) {
                    $refTransacao = false;
                    // echo json_encode($trasacoes);
                    foreach ($trasacoes as $trasacao) {
                        // echo $trasacao;

                        if (strval($trasacao['parcela']) == strval($parcela) && strval($trasacao['valor']) == strval($valor_calc)) {
                            $refTransacao = true;
                            if ($log) {
                                echo "<p style='background:pink;'>" . $trasacao['valor'] . " - " . $trasacao['parcela'] . " || " . $valor_calc . " - " . $parcela . "</p>";
                            }
                        } else {
                            if ($log) {
                                echo "<p>" . $trasacao['valor'] . " - " . $trasacao['parcela'] . " || " . $valor_calc . " - " . $parcela . "</p>";
                            }
                        }
                    }
                    $ref = $refTransacao;
                } else {
                    $ref = false;
                    if ($log) {
                        echo "<p>Nao achou nenhum pagamento</p>";
                    }
                }


                if ($ref || $statusComissao == 1) {
                    array_push($comissoesPagas, $dadosComissao);
                    if ($log) {
                        echo "<h5><strong style='color:#5da170;'>Já foi paga a parcela: " . $parcela . " </strong></h5>";
                    }
                } else {
                    array_push($comissoesEncontradas, $dadosComissao);
                    if ($log) {
                        echo "<h5><strong style='color:red;'>=> Falta pagar a ultima parcela lançada pela operadora: " . $parcela . " </strong></h5>";
                    }
                }
            } else {
                array_push($comissoesNaoEncontradas, $comissaoRow);
                if ($log) {
                    echo "<p style='color:red;'>Não achou a o numero da apólice nem a razao social!</p>";
                }
            }
        } else {
            if ($log) {
                echo "<p> Não encontrou a operadora!</p>";
            }
        }
    }

    # Finalizar o lançamento das comissoes
    $comissoesPagarAParte = array();
    $comissoesNegativas = array();
    $count = 0;
    foreach ($comissoesEncontradas as $key => $comissao) {
        $count++;
        // echo json_encode($comissao);
        if ($comissao['valor_calc'] < 0.0) {
            array_push($comissoesNegativas, $comissao);
        } else {
            if ($salvarSistema) {
                # COLOCAR A PARTE AS COMISSOES QUE TEM INCLUSAO
                if ((intval($comissao['porcentagem']) < 20 && $comissao['parcela'] < 4) || $comissao['id_transacao'] == 7) {
                    array_push($comissoesPagarAParte, $comissao);
                    lancaComissaoVitaliciaSemDistribuicao($comissao);
                } else {
                    pagarComissoes($comissao);
                }

                array_push($comissoesPagas, $comissao);
                array_splice($comissoesEncontradas, $key);
            }
        }
    }

    foreach ($comissoesNaoEncontradas as $key => $comissao) {
        if (floatval($comissao['comissao']) < 0) {
            array_push($comissoesNegativas, $comissao);
        }
    }

    $data = ["data" => [
        "comissoesSemLancamento" => $comissoesEncontradas,
        "comissoesComLancamento" => $comissoesPagas,
        "comissoesNaoEncontradas" => $comissoesNaoEncontradas,
        "comissoesNegativas" => $comissoesNegativas
    ]];

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

function procuraFinalizado($idFinalizado)
{
    global $conect;

    $sql = "SELECT * FROM tbl_finalizado WHERE id = '$idFinalizado';";
    $selectFinalizado = mysqli_query($conect, $sql);
    if ($rsFinalizado = mysqli_fetch_assoc($selectFinalizado)) {
        return $rsFinalizado;
    } else {
        return false;
    }
}

function procuraPeloNumeroContratoAtual($numeroContrato, $idOperadora)
{
    global $tblFinalizado;

    if ($numeroContrato != "" && $numeroContrato != null && ($numeroContrato != 0 || $numeroContrato != "0")) {

        $numeroContratoSplit = explode("/", $numeroContrato);
        for ($i = 0; $i <= sizeof($tblFinalizado); $i++) {
            $finalizadoRow = $tblFinalizado[$i];
            // echo " ".$finalizadoRow['n_apolice'];
            if (
                strval($numeroContratoSplit[0]) == strval($finalizadoRow['n_apolice']) ||
                strval($numeroContrato) == strval($finalizadoRow['n_apolice']) ||
                strval($numeroContrato) == strval($finalizadoRow['contrato_dental'])
            ) {
                if ($idOperadora == 2) {
                    return $finalizadoRow;
                }
                if ($idOperadora == $finalizadoRow['id_operadora']) {
                    return $finalizadoRow;
                }
            }
        }
    }

    return false;
}

function procuraPelaRazaoSocial($nomeContrato, $idOperadora)
{
    global $tblFinalizado;

    if ($nomeContrato != "" && $nomeContrato != null) {

        for ($i = 0; $i <= sizeof($tblFinalizado); $i++) {
            $finalizadoRow = $tblFinalizado[$i];
            // echo "<p>".$nomeContrato."</p>";
            // echo " ".$finalizadoRow['razao_social'];
            if (strtoupper(trim($nomeContrato)) == strtoupper(trim($finalizadoRow['razao_social']))) {
                if ($idOperadora == 2) {
                    return $finalizadoRow;
                }
                if ($idOperadora == $finalizadoRow['id_operadora']) {
                    return $finalizadoRow;
                }
            }
        }
    }

    return false;
}


function teste()
{
    global $conect;
    $tblComissoes = array();
    $sql = "SELECT data_inicial, data_final, nome_contrato, data_pagamento, sum(comissao) as comissao, parcela, porcentagem, contrato_atual, id_operadora, id_conta, dental, referencia, contrato_atual, proposta, sum(base_comissao) as base_comissao, paga from tbl_comissoes_operadora where id_operadora = '3' and parcela > 21 and referencia not like 'SULAMERICA' and ((data_pagamento >= '2021-10-15' and data_pagamento <= '2021-10-15') or (data_inicial >= '2021-10-15' and data_final <= '2021-10-15')) group by nome_contrato, contrato_atual, porcentagem, data_pagamento, referencia, proposta, parcela, id_operadora, id_conta, dental, data_inicial, data_final, paga order by parcela;";
    $select = mysqli_query($conect, $sql);
    while ($rs = mysqli_fetch_assoc($select)) {
        array_push($tblComissoes, $rs);
    }

    processo($tblComissoes, false);
}

// $_POST['id_operadora'] = 3;
// $_POST['data_inicial'] = '2021-10-19';
// $_POST['data_final'] = '2021-10-19';
// $_POST['salvar'] = false;

if (isset($_POST['data_inicial']) && isset($_POST['data_final']) && isset($_POST['salvar'])) {

    $idOperadora = $_POST['id_operadora'];
    $dataInicial = $_POST['data_inicial'];
    $dataFinal = $_POST['data_final'];
    $salvar = $_POST['salvar'];

    if ($salvar == "false") {
        $salvar = false;
    } else {
        $salvar = true;
    }

    $buscaOperadoras = "";

    if ($idOperadora == "" || $idOperadora == null || $idOperadora == 0) {
        $buscaOperadoras = "> '0'";
    } else {
        $buscaOperadoras = "= '$idOperadora'";
    }

    $sql = "SELECT data_inicial, data_final, nome_contrato, data_pagamento, sum(comissao) as comissao, parcela, porcentagem, contrato_atual, id_operadora, id_conta, dental, referencia, contrato_atual, proposta, sum(base_comissao) as base_comissao, paga 
    from tbl_comissoes_operadora where id_operadora $buscaOperadoras and referencia not like 'SULAMERICA' and  ((data_pagamento >= '$dataInicial' and data_pagamento <= '$dataFinal') or (data_inicial >= '$dataInicial' and data_final <= '$dataFinal')) 
    group by nome_contrato, contrato_atual, porcentagem, data_pagamento, referencia, proposta, parcela, id_operadora, id_conta, dental, data_inicial, data_final, paga order by  parcela;";

    if (intval($idOperadora) == 4) {
        $sql = "SELECT * from tbl_comissoes_operadora 
        where id_operadora = $idOperadora and referencia like 'SULAMERICA' and (data_pagamento between '$dataInicial' and '$dataFinal') order by parcela, porcentagem desc;";
    } elseif (intval($idOperadora) == 1 || intval($idOperadora) == 2 || intval($idOperadora) == 5 || intval($idOperadora) == 8 || intval($idOperadora) == 11 || intval($idOperadora) == 12 || intval($idOperadora) == 6) {
        $sql = "SELECT 
                    id,
                    data_inicial,
                    data_final,
                    nome_contrato,
                    data_pagamento,
                    comissao,
                    parcela,
                    porcentagem,
                    contrato_atual,
                    id_operadora,
                    id_conta,
                    dental,
                    referencia,
                    contrato_atual,
                    proposta,
                    base_comissao,
                    paga,
                    id_finalizado
                FROM
                    tbl_comissoes_operadora 
                WHERE
                    id_operadora = $idOperadora
                        AND (data_pagamento between '$dataInicial' AND '$dataFinal')
                ORDER BY parcela;";
    }

    $select_comissoes = mysqli_query($conect, $sql);
    while ($rs_comissoes = mysqli_fetch_assoc($select_comissoes)) {
        array_push($tblComissoes, $rs_comissoes);
    }

    processo($tblComissoes, $salvar);
}


if (isset($_POST['data_inicial']) && isset($_POST['data_final']) && isset($_POST['total'])) {

    $idOperadora = $_POST['id_operadora'];
    $dataInicial = $_POST['data_inicial'];
    $dataFinal = $_POST['data_final'];

    $total = array();

    $sql = "SELECT data_pagamento, sum(comissao) as valor, referencia, dental FROM tbl_comissoes_operadora WHERE data_pagamento >= '$dataInicial' and data_pagamento <= '$dataFinal' and id_operadora = $idOperadora group by id_conta, data_pagamento, referencia, dental ORDER BY data_pagamento ;";

    $select = mysqli_query($conect, $sql);
    while ($rs = mysqli_fetch_assoc($select)) {

        $sql = "SELECT id, valor FROM tbl_comissoes_operadora_total_nota WHERE referencia LIKE '{$rs["referencia"]}' AND data_pagamento = '{$rs["data_pagamento"]}' AND dental = {$rs["dental"]} ORDER BY data_pagamento ;";
        $selectTotal = mysqli_query($conect, $sql);
        if ($rsTotalNota = mysqli_fetch_assoc($selectTotal)) {
            $aux = [
                "data_pagamento" => $rs['data_pagamento'],
                "referencia" => $rs['referencia'],
                "total_soma" => $rs['valor'],
                "total_nota" => $rsTotalNota['valor']
            ];
        }


        array_push($total, $aux);
    }

    echo json_encode($total, JSON_UNESCAPED_UNICODE);
}
