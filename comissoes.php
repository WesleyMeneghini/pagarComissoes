<?php

require_once("includes/config.php");
require_once('includes/functions.php');
require_once "agrupar_comissoes.php";
require_once "pagar_comissao.php";

$log = true;

$tblFinalizado = array();
$tblComissoes = array();
$tblContas = array();

// // $sql = "select * FROM busca_comissoes where id = 9764;";


# SELECIONAR AS COMISSOES QUE SERAO PROCESSADAS
// $sql = "select * from busca_comissoes where id_operadora = 4 and ((data_pagamento >= '2020-11-16' and data_pagamento <= '2020-11-22') or (data_inicial >= '2020-11-16' and data_final <= '2020-11-22'));";


// $select_comissoes = mysqli_query($conect, $sql);
// // var_dump(mysqli_fetch_assoc($select_comissoes));
// while ($rs_comissoes = mysqli_fetch_assoc($select_comissoes)){
//     array_push($tblComissoes, $rs_comissoes);
// }
// $comissoes = agruparComissoes($tblComissoes);

$sql = "SELECT * FROM tbl_contas;";
$select_contas = mysqli_query($conect, $sql);
while ($rs_conta = mysqli_fetch_assoc($select_contas)){
    array_push($tblContas, $rs_conta);
}

$sql = "SELECT id, id_operadora, razao_social, n_apolice, contrato_dental  FROM tbl_finalizado;";
$select_finalizado = mysqli_query($conect, $sql);
while ($rs_finalizado = mysqli_fetch_assoc($select_finalizado)){
    array_push($tblFinalizado, $rs_finalizado);
}
// echo sizeof($tblFinalizado)."-----------------------";

function processo($array, $salvarSistema){
    $salvar = $salvarSistema;

    global $log;
    global $conect;
    global $salvar;
    global $tblContas;

    $comissoes = $array;

    $comissoesEncontradas = array();
    $comissoesPagas = array();
    $comissoesNaoEncontradas = array();

    for($i = 0 ; $i < sizeof($comissoes); $i++){

        $idFinalizado = 0;
        $idDestino = 1;
        $portabilidade = "";
        $idTransacao = 1;

        $comissaoRow = $comissoes[$i];

        // echo "<p>".json_encode($comissaoRow)."</p>";
        
        $idBuscaComissao = $comissaoRow['id'];

        $referencia = (string) strtoupper($comissaoRow['referencia']);
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
        // $idFinalizado = $comissaoRow['id_finalizado'];

        $ref_arr = explode(" ", $referencia);
        switch(strtoupper($ref_arr[0])){
            case "AFFINITY":
                $contrato = $proposta;
                break;
            case "INTERMEDICA-PME":
                if ($proposta > 0){
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
            // case "BRADESCO-DENTAL":
            //     $contrato = $contrato - 1;
            //     break;
            default:
                break;

        }

        if ($log){

            // echo "<br><p style='color:blue;'>-> <strong>$operadora</strong></p>";
            echo "<br><p style='color:blue;'>-> <strong>$referencia</strong></p>";
            echo "<p><strong>Busca: </strong> (id_busca) - <strong>$nomeContrato</strong> (Nome Contrato) - $contrato (Numero da apolice)</p>";
            echo "<p><strong>Parcela: </strong>".$parcela."</p>";
            echo "<p><strong>Data Pagamento extraido: </strong>".$dataPagamento."</p>";
            echo "<p><strong>Comissão: </strong>R$ ".$valor_calc."</p>";
            echo "<p><strong>Porcentagem: </strong>".$porcentagem."%</p>";
        }


        if ($parcela >= 6){
            $parcelaPesquisa = 6;
        }else{
            $parcelaPesquisa = $parcela;
        }

        $sql = "SELECT * FROM tbl_porcentagem_comissoes WHERE id_operadora = $idOperadora AND parcela = $parcelaPesquisa;";
        // echo $sql;
        $select = mysqli_query($conect, $sql);
        $porcentagemComissoes = array();
        while ($rs_porcentagem = mysqli_fetch_assoc($select)){
            array_push($porcentagemComissoes, $rs_porcentagem);
            
        }
        // echo "<h1>".sizeof($porcentagemComissoes)."</h1>";
        if (sizeof($porcentagemComissoes) > 0){
            $porcentagemErrada = true;
            $porcentagemCorreta = 0;
            foreach($porcentagemComissoes as $rs_porcentagem){

                if ($rs_porcentagem['porcentagem'] == $porcentagem && $rs_porcentagem['parcela'] == $parcelaPesquisa){
                    // echo "Comissao esta com a porcentagem certa!";
                    $porcentagemErrada = false;
                }
                if ($rs_porcentagem['parcela'] == $parcelaPesquisa){
                    $porcentagemCorreta = $rs_porcentagem['porcentagem'];
                }
            }
            if ($porcentagemErrada){
                if($log){
                    echo "<p style='background:orange;'>A porcentagem da comissao não está como o esperado! <br>A porcentagem correta seria $porcentagemCorreta%</p>";
                }
            }
        }else{
            if ($log){
                echo "<p style='background:orange;'>A porcentagem da comissao não está como o esperado!</p>";
        
            }
        }


        


        if ($idOperadora > 0){
            
            # Identificar a Conta de Origem 
            if ($idOperadora == 2){
                for($j = 0 ; $j <= sizeof($tblContas); $j++){
                    $contasRow = $tblContas[$j];
                    
                    if( $referencia == $contasRow['titulo']){
                        $idOrigem = $contasRow['id'];
                        if ($log){
                            echo "<p> <strong>id_conta: </strong>".$idOrigem."</p>";
                        }
                        break;
                    }
                }
            }

            if($idOrigem > 0){
                if($idFinalizado == "" || $idFinalizado == null || $idFinalizado == 0){
                    $res = procuraPeloNumeroContratoAtual($contrato, $idOperadora);
                    if($res){
                        $idFinalizado = $res['id'];
                        $portabilidade = $res['portabilidade'];
                        if($refDental == 0){
                            $contrato = $res['n_apolice'];
                        }else{
                            $contrato = $res['contrato_dental'];
                        }
                        $operadoraFinalizado = $res['id_operadora'];
                        $nomeContrato = $res['razao_social'];

                        if ($operadoraFinalizado == 5){
                            $idOrigem = 22;
                        }
                        

                    }else{
                        $res = procuraPelaRazaoSocial($nomeContrato, $idOperadora);
                        if($res){
                            $idFinalizado = $res['id'];
                            $portabilidade = $res['portabilidade'];
                            $nomeContrato = $res['razao_social'];
                        }else{
                            $buscaRazaoSocialArray = array();
                            if($idOperadora == 2){
                                // $sql = "select * from tbl_finalizado where razao_social like '%$nomeContrato%' and (id_operadora = $idOperadora or id_operadora = 5);";
                                $sql = "select id,id_operadora, id_tipo_plano, razao_social, n_apolice, valor from tbl_finalizado where replace(replace(replace(replace(replace(razao_social, '-', ''), '.',''), ' ',''), '/',''), '&', '')  like '%".str_replace(' ', '', str_replace("&", "", $nomeContrato))."%' and (id_operadora = $idOperadora or id_operadora = 5);";
                            }else{
                                // $sql = "select * from tbl_finalizado where razao_social like '%$nomeContrato%' and where id_operadora = $idOperadora;";

                                $sql = "select id,id_operadora, id_tipo_plano, razao_social, n_apolice, valor from tbl_finalizado where replace(replace(replace(replace(replace(razao_social, '-', ''), '.',''), ' ',''), '/',''), '&', '')  like '%".str_replace(' ', '', str_replace( "&", "", $nomeContrato))."%' and id_operadora = $idOperadora;";
                            }
                            // echo "<p>$sql</p>";
                            $buscaRazaoSocial = mysqli_query($conect, $sql);
                            while ($rs_busca = mysqli_fetch_assoc($buscaRazaoSocial)){
                                array_push($buscaRazaoSocialArray, $rs_busca);
                            }

                            if(sizeof($buscaRazaoSocialArray) == 1){
                                $row = $buscaRazaoSocialArray[0];
                                if($row['razao_social'] != "" && $row['razao_social'] != null){
                                    $idFinalizado = intval($row['id']);
                                    $nomeContrato = $row['razao_social'];
                                }else{
                                    array_push($comissoesNaoEncontradas, $comissaoRow);
                                }
                                
                            }else{
                                // echo "Mais de um contrato encontrado";
                            }

                            
                        }
                    }
                }
            }else{
                if ($log){
                    echo "<p style='color:red;'>Não achou a conta de origem!</p>";
                }
            }
            if ($log){
                // echo "<p style='background: yellow;'><strong>Razao Social: </strong>".$nomeContrato."</p>";
                echo "<p><strong>Busca: </strong> (id_busca) - <strong style='background: yellow;'>$nomeContrato</strong> (Nome Contrato) - $contrato (Numero da apolice)</p>";
            }
            

            if ($idFinalizado > 0){
                if ($log){
                // echo "<h5> ID: <strong style='color:green;'>ID FINALIZADO: $idFinalizado </strong> encontrou</h5>";
                }


                // Varificando as parcelas da Sulamerica
                // echo "<h5> ID: <strong style='color:red;'>$parcela</h5>";
                if ($idOperadora == 4 && $parcela >= 1 && $referencia == 'SULAMERICA'){
                    $sql = "SELECT max(parcela) as ultima_parcela from tbl_transacoes where id_origem = $idOrigem and id_finalizado = $idFinalizado;";
                    $buscaUltimaParcela = mysqli_query($conect, $sql);
                    while ($rs_busca = mysqli_fetch_assoc($buscaUltimaParcela)){
                        // echo json_encode($rs_busca);
                        $parcela = $rs_busca['ultima_parcela']+1;
                    }
                }

                // echo "<h5> ID: <strong style='color:green;'>$parcela</h5><br>";
                







                $listaParcelas = array();
                $listaParcelasBanco = array();

                for ($k = 1; $k <= $parcela; $k++){
                    array_push($listaParcelas, $k);
                }
                
                $trasacoes = array();
                $sql = "select * from tbl_transacoes where id_finalizado = $idFinalizado AND id_origem = $idOrigem;";
                
                // echo "<p>$sql</p>";
                $select_transacoes = mysqli_query($conect, $sql);
                while ($rs_trasacao = mysqli_fetch_assoc($select_transacoes)){
                    // echo "<h6> ID: <song style='color:orange;'>ID TRANSACOES: ".$rs_trasacao['id']." </strong> encontrou</h6>";

                    array_push($trasacoes, $rs_trasacao);
                    array_push($listaParcelasBanco, intval($rs_trasacao['parcela']));
                }
                
                # Procurar novamente mas com outro id_origem
                if (sizeof($trasacoes) == 0 && $idOrigem == 18 && $parcela >= 1){
                    $idOrigem = 22;
                    $sql = "select * from tbl_transacoes where id_finalizado = $idFinalizado AND id_origem = $idOrigem;";
                
                    // echo "<p>$sql</p>";
                    $select_transacoes = mysqli_query($conect, $sql);
                    while ($rs_trasacao = mysqli_fetch_assoc($select_transacoes)){
                        // echo "<h6> ID: <song style='color:orange;'>ID TRANSACOES: ".$rs_trasacao['id']." </strong> encontrou</h6>";

                        array_push($trasacoes, $rs_trasacao);
                        array_push($listaParcelasBanco, intval($rs_trasacao['parcela']));
                    }
                }
                
                // echo "<p> </p>";
                // var_dump($listaParcelas);
                // echo "<p></p>";
                // var_dump($listaParcelasBanco);

                $listaParcelasNaoPagas = array_diff($listaParcelas, $listaParcelasBanco);
                // echo "<p></p>";
                // var_dump($listaParcelasNaoPagas);
                $refComissaoPendente = false;
                $parcelasNaoPagas = array();

                foreach($listaParcelasNaoPagas as $key){
                    if ($key == $parcela){
                        $refComissaoPendente = true;
                        // echo "<h5><strong style='color:red;'>Falta pagar a ultima parcela lançada pela operadora: ".$key."</strong></h5>";
                    }else{
                        array_push($parcelasNaoPagas, $key);
                        if ($log){
                            echo "<h5><strong style='color:red;'>Falta Pagar a parcela: ".$key." </strong>.</h5>";
                        }
                    }
                }
                
                // Regra para Pagar o valor bruto se a operadora for notredame
                if ($idOperadora == 1 && $parcela == 1 && $porcentagem == 100){
                    if($log){
                        echo "<p><strong>Valor Base Comissao: </strong>R$ ".$baseComissao." || ".$valor_calc."</p>";
                        echo "<h5>".($baseComissao-$valor_calc)."</h5>";
                    }
                    $valor_calc = $baseComissao;
                }
                

                if ($parcela > 3){
                    $idTransacao = 7;
                }

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
                    'parcelasNaoPagas' => $parcelasNaoPagas,
                    'valor_bruto' => $valorBrutoComissao,
                    'dataPagamento' => $dataPagamento
                ];
                

                $ref = false;

                if (sizeof($trasacoes) > 0){
                    $refTransacao = false;
                    // echo json_encode($trasacoes);
                    foreach($trasacoes as $trasacao){
                        // echo $trasacao;
                        
                        if (strval($trasacao['parcela']) == strval($parcela) && strval($trasacao['valor']) == strval($valor_calc)){
                            $refTransacao = true;
                            if ($log){
                                echo "<p style='background:pink;'>".$trasacao['valor']." - ".$trasacao['parcela'] ." || ".$valor_calc." - ".$parcela ."</p>";
                            }
                        }else{
                            if ($log){
                                echo "<p>".$trasacao['valor']." - ".$trasacao['parcela'] ." || ".$valor_calc." - ".$parcela ."</p>";
                            }
                        }
                    }
                    $ref = $refTransacao;
                }else{
                    $ref = false;
                    if ($log){
                    echo "<p>Nao achou nenhum pagamento</p>";
                    }
                }
                

                if ($ref || $statusComissao == 1){
                    array_push($comissoesPagas, $dadosComissao);
                    if ($log){
                        echo "<h5><strong style='color:#5da170;'>Já foi paga a parcela: ".$parcela." </strong></h5>";
                    }
                }else{
                    array_push($comissoesEncontradas, $dadosComissao);
                    if ($log){
                        echo "<h5><strong style='color:red;'>=> Falta pagar a ultima parcela lançada pela operadora: ".$parcela." </strong></h5>";
                    }
                }

            }else{
                array_push($comissoesNaoEncontradas, $comissaoRow);
                if ($log){
                echo "<p style='color:red;'>Não achou a o numero da apólice nem a razao social!</p>";
                }
            }
        }else{
            if ($log){
            echo "<p> Não encontrou a operadora!</p>";
            }
        }

        
    }

    $comissoesEncontradasJson = json_encode($comissoesEncontradas);
    $comissoesNaoEncontradasJson = json_encode($comissoesNaoEncontradas);
    echo "
    <script> 
        let teste1 = $comissoesEncontradasJson ; console.log('Encontrados: ',teste1);
        let teste2 = $comissoesNaoEncontradasJson ; console.log('Nao Encontrados :', teste2);
    </script>";


    # Finalizar o lançamento das comissoes
    $comissoesPagarAParte = array();
    $comissoesNegativas = array();
    if ($salvar){
        $count = 0;
        foreach($comissoesEncontradas as $comissao){
            $count ++;
            // echo json_encode($comissao);
            if ($comissao['valor_calc'] < 0.0){
                array_push($comissoesNegativas, $comissao);
            }else{
                # COLOCAR A PARTE AS COMISSOES QUE TEM INCLUSAO
                if (intval($comissao['porcentagem']) < 20 && $comissao['parcela'] < 4){
                    // echo "<p>Pagar a parte ".$comissao['razao_social']."</p>";
                    array_push($comissoesPagarAParte, $comissao);
                    lancaComissaoVitaliciaSemDistribuicao($comissao);
                }else{
                    // echo "<p>Pagar a parte ".$comissao['descricao']."</p>";
                    
                    pagarComissoes($comissao);
                }
            }

            
        }
        if ($log){
            echo "<br>$count";
        }
    }else{
        // echo "Nao salva no banco";
    }

    // echo $comissoesEncontradas;
    return [$comissoesEncontradas, $comissoesPagas, $comissoesNaoEncontradas, $comissoesNegativas];

}
// processo($tblComissoes, false);


function procuraPeloNumeroContratoAtual( $numeroContrato, $idOperadora){
    global $tblFinalizado;

    if ($numeroContrato != "" && $numeroContrato != null){
    
        $numeroContratoSplit = explode("/", $numeroContrato);
        for($i = 0 ; $i <= sizeof($tblFinalizado); $i++){
            $finalizadoRow = $tblFinalizado[$i];
            // echo " ".$finalizadoRow['n_apolice'];
            if( strval($numeroContratoSplit[0]) == strval($finalizadoRow['n_apolice']) || 
            strval($numeroContrato) == strval($finalizadoRow['n_apolice']) || 
            strval($numeroContrato) == strval($finalizadoRow['contrato_dental'])){
                if ($idOperadora == 2){
                    return $finalizadoRow;
                }
                if ($idOperadora == $finalizadoRow['id_operadora']){
                    return $finalizadoRow;
                }
            }
        }
    }

    return false;

}

function procuraPelaRazaoSocial( $nomeContrato, $idOperadora){
    global $tblFinalizado;

    if ($nomeContrato != "" && $nomeContrato != null){

        for($i = 0 ; $i <= sizeof($tblFinalizado); $i++){
            $finalizadoRow = $tblFinalizado[$i];
            // echo "<p>".$nomeContrato."</p>";
            // echo " ".$finalizadoRow['razao_social'];
            if( strtoupper(trim($nomeContrato)) == strtoupper(trim($finalizadoRow['razao_social'])) ){
                if ($idOperadora == 2){
                    return $finalizadoRow;
                }
                if ($idOperadora == $finalizadoRow['id_operadora']){
                    return $finalizadoRow;
                }
            }
        }
    }

    return false;
}

